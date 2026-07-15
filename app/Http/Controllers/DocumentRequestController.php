<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with(['documentType', 'citizen'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('document_type_id', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('citizen', function ($q) use ($search) {
                $q->where('fname', 'like', "%$search%")
                  ->orWhere('lname', 'like', "%$search%");
            });
        }

        $requests = $query->paginate(20)->appends($request->query());
        $types    = DocumentType::active()->orderBy('name')->get();

        $stats = [
            'total'    => DocumentRequest::count(),
            'pending'  => DocumentRequest::where('status', 'pending')->count(),
            'approved' => DocumentRequest::where('status', 'approved')->count(),
            'released' => DocumentRequest::where('status', 'released')->count(),
        ];

        return view('documents.requests.index', compact('requests', 'types', 'stats'));
    }

    public function create(Request $request)
    {
        $types = DocumentType::active()->with('fields')->orderBy('name')->get();

        // 6 citizens most recently updated
        $recentCitizens = Citizen::where('is_active', 1)
            ->orderByDesc('date_last_updated')
            ->limit(6)
            ->get();

        $selectedType = $request->filled('type')
            ? $types->find($request->type)
            : null;

        return view('documents.requests.create', compact('types', 'recentCitizens', 'selectedType'));
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

    // Render the certificate exactly as it will look once submitted — without
    // saving anything — so staff can review before committing to a request.
    public function preview(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|exists:eb_document_types,id',
            'citizen_id'       => 'required|exists:eb_citizen,id',
        ]);

        $type    = DocumentType::with(['fields', 'template', 'pinnedVersion'])->findOrFail($request->document_type_id);
        $citizen = Citizen::find($request->citizen_id);

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
            'bg_url'     => $bgUrl,
            'padding'    => [
                'top'    => $ver?->padding_top    ?? 50,
                'right'  => $ver?->padding_right  ?? 50,
                'bottom' => $ver?->padding_bottom ?? 20,
                'left'   => $ver?->padding_left   ?? 50,
            ],
            'summary' => [
                'citizen_name'   => $citizen->full_name,
                'document_type'  => $type->name,
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
        $request->validate([
            'document_type_id' => 'required|exists:eb_document_types,id',
            'citizen_id'       => 'required|exists:eb_citizen,id',
        ]);

        $type    = DocumentType::with(['fields', 'template', 'pinnedVersion'])->findOrFail($request->document_type_id);
        $citizen = Citizen::find($request->citizen_id);

        try {
            $customFields = $this->buildCustomFields($type, $request, $citizen);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $docRequest = DocumentRequest::create([
            'document_type_id'   => $type->id,
            'citizen_id'         => $request->citizen_id,
            'status'             => 'pending',
            'custom_fields'      => $customFields,
            'is_paid'            => $type->is_paid,
            'fee'                => $type->fee,
            'fee_paid'           => false,
            'remarks'            => $request->remarks,
            'template_version_id'=> $type->document_template_version_id ?? $type->template?->current_version_id,
        ]);

        return redirect()->route('documents.requests.show', $docRequest)
            ->with('success', 'Document request created successfully.');
    }

    public function show(DocumentRequest $documentRequest)
    {
        $documentRequest->load(['documentType.fields', 'templateVersion', 'citizen.civilStatus', 'approvedBy']);
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
        $request->validate([
            'or_number' => $documentRequest->is_paid ? 'required|string|max:100' : 'nullable|string|max:100',
        ]);

        $documentRequest->update([
            'status'       => 'released',
            'or_number'    => $request->or_number,
            'fee_paid'     => $documentRequest->is_paid ? true : false,
            'released_at'  => now(),
        ]);

        return back()->with('success', 'Document released successfully.');
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

    public function destroy(DocumentRequest $documentRequest)
    {
        $documentRequest->delete();

        return redirect()->route('documents.requests.index')
            ->with('success', 'Request deleted.');
    }
}
