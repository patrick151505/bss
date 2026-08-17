<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\DocumentPurpose;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with(['documentType', 'citizen', 'createdBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('document_type_id', $request->type);
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            // Search by citizen name (first/middle/last, incl. combined full name)
            // or address, OR by the request's control number / doc number.
            $like   = '%' . $search . '%';
            // Strip a prefix like "BRGY-" so "BRGY-00011" also matches doc_number 11.
            $numPart = ltrim(preg_replace('/^[A-Za-z]+-?/', '', $search), '0') ?: $search;

            $query->where(function ($q) use ($search, $like, $numPart) {
                $q->whereHas('citizen', function ($c) use ($like) {
                    $c->where('fname', 'like', $like)
                      ->orWhere('mname', 'like', $like)
                      ->orWhere('lname', 'like', $like)
                      ->orWhere('complete_address', 'like', $like)
                      // Combined name — with middle (Roberto Cruz Santos) and without (Roberto Santos).
                      ->orWhereRaw("CONCAT_WS(' ', fname, mname, lname) LIKE ?", [$like])
                      ->orWhereRaw("CONCAT_WS(' ', fname, lname) LIKE ?", [$like]);
                })
                // Control number: match the raw doc_number, or the type's prefix.
                ->orWhere('doc_number', 'like', '%' . $numPart . '%')
                ->orWhereHas('documentType', function ($t) use ($like) {
                    $t->where('prefix', 'like', $like);
                });
            });
        }

        $requests = $query->paginate(20)->appends($request->query());
        $types    = DocumentType::active()->orderBy('name')->get(['id', 'name']);
        // Purposes actually used on requests, so the filter only lists real values.
        $purposes = DocumentRequest::whereNotNull('purpose')->where('purpose', '!=', '')
            ->distinct()->orderBy('purpose')->pluck('purpose');

        // All status counts in ONE grouped query instead of four separate COUNTs.
        $byStatus = DocumentRequest::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $stats = [
            'total'    => (int) $byStatus->sum(),
            'pending'  => (int) ($byStatus['pending']  ?? 0),
            // "Ready for release" groups both approved and auto-ready (no-approval) requests.
            'approved' => (int) ($byStatus['approved'] ?? 0) + (int) ($byStatus['ready_for_release'] ?? 0),
            'released' => (int) ($byStatus['released'] ?? 0),
        ];

        return view('documents.requests.index', compact('requests', 'types', 'stats', 'purposes'));
    }

    // Whether the given user may issue/request the given document type.
    // Super Admin (gate bypass) passes automatically; otherwise the role must
    // hold the per-type "issue_document.{id}" permission. If that permission
    // doesn't exist yet (e.g. a type created before this feature), allow it so
    // nothing silently breaks.
    private function canIssue(?\App\Models\User $user, DocumentType $type): bool
    {
        if (!$user) {
            return false;
        }
        $perm = $type->issuePermissionName();
        $exists = \Spatie\Permission\Models\Permission::where('name', $perm)->exists();
        return $exists ? $user->can($perm) : true;
    }

    public function create(Request $request)
    {
        $user = $request->user();

        // Filter to issuable types WITHOUT a query per type: fetch the set of
        // existing "issue_document.*" permission names once, plus the user's own
        // permission names once, then decide in-memory.
        $types = DocumentType::active()->with('fields')->orderBy('name')->get();

        if ($user && !$user->hasRole('Super Admin')) {
            $issuePerms = \Spatie\Permission\Models\Permission::where('name', 'like', 'issue_document.%')
                ->pluck('name')->flip();                 // [name => idx] for O(1) lookup
            $userPerms  = $user->getAllPermissions()->pluck('name')->flip();

            $types = $types->filter(function ($t) use ($issuePerms, $userPerms) {
                $perm = $t->issuePermissionName();
                // If no per-type permission was ever created, the type is open.
                return !$issuePerms->has($perm) || $userPerms->has($perm);
            })->values();
        }

        // 6 citizens most recently updated
        $recentCitizens = Citizen::where('is_active', 1)
            ->orderByDesc('date_last_updated')
            ->limit(6)
            ->get();

        $selectedType = $request->filled('type')
            ? $types->firstWhere('id', (int) $request->type)
            : null;

        $purposes = DocumentPurpose::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('documents.requests.create', compact('types', 'recentCitizens', 'selectedType', 'purposes'));
    }

    // Resolve {{ placeholder }} default values for a document type's custom
    // fields against a specific citizen — used by the create-request form to
    // show real data (e.g. the citizen's actual first name) instead of the
    // raw {{ firstname }} tag while the request hasn't been submitted yet.
    public function resolveDefaults(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|exists:eb_document_types,id',
            'citizen_id'        => 'nullable|exists:eb_citizen,id',
        ]);

        $type    = DocumentType::with('fields')->findOrFail($request->document_type_id);
        $citizen = $request->filled('citizen_id') ? Citizen::find($request->citizen_id) : null;

        $resolved = [];
        foreach ($type->fields as $field) {
            if ($field->default_value) {
                $resolved[$field->field_key] = DocumentRequest::resolvePlaceholders($field->default_value, $citizen);
            }
        }

        return response()->json($resolved);
    }

    // Build the resolved custom_fields array for a document type from request
    // input, applying each field's default_value (with placeholders resolved)
    // as a fallback when left blank. Shared by preview() and store() so both
    // apply exactly the same rules. Throws if a required field ends up blank.
    private function buildCustomFields(DocumentType $type, Request $request, ?Citizen $citizen): array
    {
        $customFields = [];

        foreach ($type->fields as $field) {
            $value = (string) $request->input('field_' . $field->field_key, '');

            if ($value === '' && $field->default_value) {
                $value = DocumentRequest::resolvePlaceholders($field->default_value, $citizen);
            }

            if ($field->is_required && $value === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field->field_key => $field->field_label . ' is required.',
                ]);
            }
            $customFields[$field->field_key] = $value;
        }

        return $customFields;
    }

    // Normalize a submitted purpose: trim it, reuse an existing entry when one
    // matches (case-insensitively) so we never duplicate, and add it to the
    // managed list when it's genuinely new. Returns the canonical purpose string.
    private function resolvePurpose(Request $request): string
    {
        $purpose = trim((string) $request->input('purpose', ''));
        if ($purpose === '') {
            return '';
        }

        $existing = DocumentPurpose::whereRaw('LOWER(name) = ?', [mb_strtolower($purpose)])->first();
        if ($existing) {
            // Reuse the stored casing so the value stays consistent.
            return $existing->name;
        }

        DocumentPurpose::create([
            'name'       => $purpose,
            'is_active'  => true,
            'sort_order' => (int) DocumentPurpose::max('sort_order') + 1,
        ]);

        return $purpose;
    }

    // Render the certificate exactly as it will look once submitted — without
    // saving anything — so staff can review before committing to a request.
    public function preview(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|exists:eb_document_types,id',
            'citizen_id'       => 'required|exists:eb_citizen,id',
            'purpose'          => 'required|string|max:255',
        ], [
            'purpose.required' => 'Purpose is required.',
        ]);

        $type    = DocumentType::with(['fields', 'template', 'pinnedVersion'])->findOrFail($request->document_type_id);

        if (!$this->canIssue($request->user(), $type)) {
            return response()->json(['errors' => ['document_type_id' => ['You are not allowed to issue this document type.']]], 422);
        }

        $citizen = Citizen::find($request->citizen_id);
        $purpose = trim((string) $request->input('purpose', ''));

        try {
            $customFields = $this->buildCustomFields($type, $request, $citizen);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $templateVersionId = $type->document_template_version_id ?? $type->template?->current_version_id;

        // In-memory only — never saved.
        $draft = new DocumentRequest([
            'document_type_id' => $type->id,
            'citizen_id'       => $citizen->id,
            'purpose'          => $purpose,
            'custom_fields'    => $customFields,
            'is_paid'          => $type->is_paid,
            'fee'              => $type->fee,
            'remarks'          => $request->remarks,
            'template_version_id' => $templateVersionId,
        ]);
        $draft->setRelation('documentType', $type);
        $draft->setRelation('citizen', $citizen);
        if ($templateVersionId) {
            $draft->setRelation('templateVersion', \App\Models\DocumentTemplateVersion::find($templateVersionId));
        }

        $ver       = $draft->templateVersion;
        $bgUrl     = $ver?->paper_bg ? asset('storage/' . $ver->paper_bg) : null;

        return response()->json([
            'header'     => $draft->renderHeader(),
            'body'       => $draft->resolveTemplate(),
            'allow_body_edit' => (bool) $type->allow_body_edit,
            'bg_url'     => $bgUrl,
            'paper_size'  => $ver?->paper_size  ?? 'letter',
            'orientation' => $ver?->orientation ?? 'portrait',
            'padding'    => [
                'top'    => $ver?->padding_top    ?? 50,
                'right'  => $ver?->padding_right  ?? 50,
                'bottom' => $ver?->padding_bottom ?? 20,
                'left'   => $ver?->padding_left   ?? 50,
            ],
            'summary' => [
                'citizen_name'   => $citizen->full_name,
                'document_type'  => $type->name,
                'purpose'        => $purpose,
                'is_paid'        => (bool) $type->is_paid,
                'fee'            => (float) $type->fee,
                'requires_approval' => (bool) $type->requires_approval,
                'remarks'        => $request->remarks,
                'fields'         => collect($type->fields)->map(fn ($f) => [
                    'label' => $f->field_label,
                    'value' => $customFields[$f->field_key] ?? '',
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Validate — return JSON errors for AJAX so the create tab can show them.
        try {
            $request->validate([
                'document_type_id' => 'required|exists:eb_document_types,id',
                'citizen_id'       => 'required|exists:eb_citizen,id',
                'purpose'          => 'required|string|max:255',
            ], [
                'purpose.required' => 'Purpose is required.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        }

        $type    = DocumentType::with(['fields', 'template', 'pinnedVersion'])->findOrFail($request->document_type_id);

        if (!$this->canIssue($request->user(), $type)) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['document_type_id' => ['You are not allowed to issue this document type.']]], 422);
            }
            return back()->withErrors(['document_type_id' => 'You are not allowed to issue this document type.'])->withInput();
        }

        $citizen = Citizen::find($request->citizen_id);
        $purpose = $this->resolvePurpose($request);

        try {
            $customFields = $this->buildCustomFields($type, $request, $citizen);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        }

        // Determine the starting status based on the type's approval setting:
        //  • requires approval        → pending (must be approved first)
        //  • no approval, but paid    → ready_for_release (skip approval; staff
        //                               still releases so the OR number is captured)
        //  • no approval, and free    → released (fully done — auto-issued)
        $attrs = [
            'document_type_id'   => $type->id,
            'doc_number'         => $type->allocateNextNumber(),
            'citizen_id'         => $request->citizen_id,
            'created_by'         => $request->user()?->id,
            'purpose'            => $purpose,
            'status'             => 'pending',
            'custom_fields'      => $customFields,
            'body_override'      => ($type->allow_body_edit && $request->filled('body_override'))
                                        ? $request->input('body_override')
                                        : null,
            'is_paid'            => $type->is_paid,
            'fee'                => $type->fee,
            'fee_paid'           => false,
            'remarks'            => $request->remarks,
            'template_version_id'=> $type->document_template_version_id ?? $type->template?->current_version_id,
        ];

        if (!$type->requires_approval) {
            $attrs['approved_by'] = $request->user()?->id;
            $attrs['approved_at'] = now();

            if (!$type->is_paid) {
                // Free + no approval → auto-issue.
                $attrs['status']      = 'released';
                $attrs['released_at'] = now();
            } else {
                // Paid + no approval → ready to release; OR number captured at release.
                $attrs['status'] = 'ready_for_release';
            }
        }

        $docRequest = DocumentRequest::create($attrs);

        $message = match ($docRequest->status) {
            'released'          => 'Document issued successfully.',
            'ready_for_release' => 'Request created — ready for release.',
            default             => 'Document request created successfully.',
        };

        // Free + no-approval → auto-issued: signal the show page to auto-print.
        $autoPrint = $docRequest->status === 'released' && !$docRequest->is_paid;

        // AJAX (create page's new-tab flow) → return the show URL to open in a new tab.
        if ($request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => $message,
                'show_url' => route('documents.requests.show', $docRequest)
                                . ($autoPrint ? '?auto_print=1' : ''),
            ]);
        }

        $redirect = redirect()->route('documents.requests.show', $docRequest)
            ->with('success', $message);

        // Flash a one-time flag so the show page opens the print dialog on load.
        if ($autoPrint) {
            $redirect->with('auto_print', true);
        }

        return $redirect;
    }

    public function show(DocumentRequest $documentRequest)
    {
        $documentRequest->load(['documentType.fields', 'templateVersion', 'citizen.civilStatus', 'approvedBy', 'createdBy']);
        $header  = $documentRequest->renderHeader();
        $preview = $documentRequest->resolveTemplate();

        return view('documents.requests.show', compact('documentRequest', 'header', 'preview'));
    }

    public function approve(DocumentRequest $documentRequest)
    {
        $documentRequest->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Request approved.');
    }

    public function release(Request $request, DocumentRequest $documentRequest)
    {
        $noOr = $request->boolean('no_or') || $request->filled('no_or_reason');

        $request->validate([
            // OR number required for paid docs UNLESS "No OR" (with a reason) is used.
            'or_number'    => ($documentRequest->is_paid && !$noOr) ? 'required|string|max:100' : 'nullable|string|max:100',
            'no_or_reason' => ($documentRequest->is_paid && $noOr) ? 'required|string|max:255' : 'nullable|string|max:255',
            'amount_paid'  => 'nullable|numeric|min:0',
        ], [
            'no_or_reason.required' => 'A reason is required when there is no OR number.',
        ]);

        // Record the reason in place of the OR number for the audit trail.
        $orValue = ($documentRequest->is_paid && $noOr)
            ? 'NO OR: ' . trim($request->no_or_reason)
            : $request->or_number;

        $documentRequest->update([
            'status'       => 'released',
            'or_number'    => $orValue,
            'fee_paid'     => $documentRequest->is_paid ? true : false,
            'amount_paid'  => $documentRequest->is_paid ? $request->input('amount_paid') : null,
            'released_at'  => now(),
        ]);

        // Auto-open the print dialog once, right after releasing (one-time flash).
        return back()
            ->with('success', 'Document released successfully.')
            ->with('auto_print', true);
    }

    public function reject(Request $request, DocumentRequest $documentRequest)
    {
        $request->validate(['remarks' => 'required|string']);

        $documentRequest->update([
            'status'  => 'rejected',
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Request rejected.');
    }

    // Adjust the print counter. Defaults to +1 (a print); pass step=-1 to undo
    // (e.g. staff cancelled the print dialog). Never drops below zero.
    public function countPrint(Request $request, DocumentRequest $documentRequest)
    {
        $step = (int) $request->input('step', 1) < 0 ? -1 : 1;

        if ($step > 0) {
            $documentRequest->increment('print_count');
        } elseif ($documentRequest->print_count > 0) {
            $documentRequest->decrement('print_count');
        }

        return response()->json([
            'ok'          => true,
            'print_count' => $documentRequest->fresh()->print_count,
        ]);
    }

    public function destroy(DocumentRequest $documentRequest)
    {
        $documentRequest->delete();

        return redirect()->route('documents.requests.index')
            ->with('success', 'Request deleted.');
    }
}
