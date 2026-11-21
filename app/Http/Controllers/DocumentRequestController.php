<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\DocumentPurpose;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredRequests($request)
            ->with(['documentType', 'citizen', 'createdBy'])
            ->latest();

        $requests = $query->paginate(20)->appends($request->query());
        $types    = DocumentType::active()->orderBy('name')->get(['id', 'name']);
        // Purposes actually used on requests, so the filter only lists real values.
        $purposes = DocumentRequest::whereNotNull('purpose')->where('purpose', '!=', '')
            ->distinct()->orderBy('purpose')->pluck('purpose');

        // ── Stat cards — follow the active filters ─────────────────────
        // Status breakdown ignores the status filter itself, so picking
        // "Pending" doesn't zero out the other cards.
        $byStatus = $this->filteredRequests($request, withStatus: false)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')->pluck('c', 'status');

        // Money, in one query. "Collected" is what was actually received:
        // the full fee when paid in full (or no amount recorded), only the
        // amount received when short — change handed back isn't revenue.
        // "Expected" is every billable request that wasn't rejected.
        $money = $this->filteredRequests($request)
            ->where('is_paid', true)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status NOT IN ('rejected', 'voided') THEN fee ELSE 0 END), 0) AS expected,
                COALESCE(SUM(CASE WHEN status = 'released' THEN " . DocumentRequest::COLLECTED_SQL . " ELSE 0 END), 0) AS collected,
                COALESCE(SUM(CASE WHEN status = 'released' AND amount_paid IS NOT NULL AND amount_paid < fee
                    THEN fee - amount_paid ELSE 0 END), 0) AS short_total,
                COALESCE(SUM(CASE WHEN status IN ('pending', 'approved', 'ready_for_release')
                    THEN fee ELSE 0 END), 0) AS awaiting,
                SUM(CASE WHEN status = 'released' AND amount_paid IS NOT NULL AND amount_paid < fee
                    THEN 1 ELSE 0 END) AS short_count
            ")->first();

        $stats = [
            'filtered'    => $requests->total(),
            'all'         => DocumentRequest::count(),
            'pending'     => (int) ($byStatus['pending']  ?? 0),
            // "Ready for release" groups both approved and auto-ready (no-approval) requests.
            'approved'    => (int) ($byStatus['approved'] ?? 0) + (int) ($byStatus['ready_for_release'] ?? 0),
            'released'    => (int) ($byStatus['released'] ?? 0),
            // Per-status counts for the status tabs (they ignore the status filter).
            'by_status'   => collect(['pending', 'approved', 'ready_for_release', 'released', 'rejected', 'voided'])
                ->mapWithKeys(fn($s) => [$s => (int) ($byStatus[$s] ?? 0)])->all(),
            'status_all'  => (int) $byStatus->sum(),
            'expected'    => (float) $money->expected,
            'collected'   => (float) $money->collected,
            'short_total' => (float) $money->short_total,
            'short_count' => (int) $money->short_count,
            'awaiting'    => (float) $money->awaiting,
            'is_filtered' => collect($request->only(['search', 'type', 'purpose', 'status', 'source']))->filter(fn($v) => filled($v))->isNotEmpty()
                || $this->dateRange($request) !== null,
        ];

        // Human label for the chosen period, shown above the stat cards.
        $bounds = $this->dateRange($request);
        $rangeLabel = $bounds ? trim(match ($this->rangeKey($request)) {
            'today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', default => 'Custom',
        } . ' (' . ($bounds[0]->isSameDay($bounds[1]) ? $bounds[0]->format('M d, Y')
            : $bounds[0]->format('M d') . ' – ' . $bounds[1]->format('M d, Y')) . ')') : null;

        $rangeKey = $this->rangeKey($request);

        // Money figures are only for roles with documents.fees.
        $feeSummary = $request->user()->can('documents.fees') ? $this->feeSummary($request) : null;

        return view('documents.requests.index', compact('requests', 'types', 'stats', 'purposes', 'rangeLabel', 'rangeKey', 'feeSummary'));
    }

    /**
     * Printable fee summary for the current list filters.
     */
    public function feeSummaryPrint(Request $request)
    {
        $summary = $this->feeSummary($request);
        $bounds  = $this->dateRange($request);
        $period  = $bounds
            ? ($bounds[0]->isSameDay($bounds[1]) ? $bounds[0]->format('F d, Y')
                : $bounds[0]->format('F d, Y') . ' – ' . $bounds[1]->format('F d, Y'))
            : 'All time';

        // Other filters in words, so the printout says what it covers.
        $filters = collect([
            'Document type' => $request->filled('type') ? DocumentType::find($request->type)?->name : null,
            'Purpose'       => $request->input('purpose'),
            'Status'        => $request->filled('status') ? ucwords(str_replace('_', ' ', $request->status)) : null,
            'Search'        => $request->input('search'),
        ])->filter(fn($v) => filled($v));

        $settings = \App\Models\Setting::instance();
        // Letterhead paper chosen in Settings → Print Paper (null = plain built-in header).
        $paper = \App\Support\Paper::resolve($settings->fees_paper_template_id, $settings->fees_paper_version_id);

        return view('documents.requests.fee-summary-print', compact('summary', 'period', 'filters', 'settings', 'paper'));
    }

    /**
     * Possible income by document type for the current filters.
     *
     * "Possible income" is every billable request that wasn't rejected or
     * voided — i.e. what the barangay earns if everything is released and
     * paid in full. It splits into collected (released, money received),
     * awaiting release (pending / approved / ready) and short (released with
     * less than the fee). collected + awaiting + short = possible income.
     */
    private function feeSummary(Request $request): array
    {
        $rows = $this->filteredRequests($request)
            ->where('is_paid', true)
            ->whereNotIn('status', ['rejected', 'voided'])
            ->groupBy('document_type_id')
            ->selectRaw("
                document_type_id,
                COUNT(*) AS requests,
                MIN(fee) AS fee_min,
                MAX(fee) AS fee_max,
                COALESCE(SUM(fee), 0) AS possible,
                COALESCE(SUM(CASE WHEN status = 'released' THEN " . DocumentRequest::COLLECTED_SQL . " ELSE 0 END), 0) AS collected,
                COALESCE(SUM(CASE WHEN status IN ('pending', 'approved', 'ready_for_release') THEN fee ELSE 0 END), 0) AS awaiting,
                COALESCE(SUM(CASE WHEN status = 'released' AND amount_paid IS NOT NULL AND amount_paid < fee
                    THEN fee - amount_paid ELSE 0 END), 0) AS short,
                SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS released
            ")
            ->get();

        $names = DocumentType::whereIn('id', $rows->pluck('document_type_id'))->get(['id', 'name', 'color'])->keyBy('id');

        $rows = $rows->map(fn($r) => [
            'name'      => $names[$r->document_type_id]->name ?? 'Unknown type',
            'color'     => $names[$r->document_type_id]->badge_color ?? '#9ca3af',
            'requests'  => (int) $r->requests,
            'released'  => (int) $r->released,
            'fee_min'   => (float) $r->fee_min,
            'fee_max'   => (float) $r->fee_max,
            'possible'  => (float) $r->possible,
            'collected' => (float) $r->collected,
            'awaiting'  => (float) $r->awaiting,
            'short'     => (float) $r->short,
        ])->sortByDesc('possible')->values();

        $totals = [];
        foreach (['requests', 'released', 'possible', 'collected', 'awaiting', 'short'] as $k) {
            $totals[$k] = $rows->sum($k);
        }

        // Shown as footnotes: free documents and requests that don't count.
        $free     = (clone $this->filteredRequests($request))->where('is_paid', false)->whereNotIn('status', ['rejected', 'voided'])->count();
        $excluded = $this->filteredRequests($request)->where('is_paid', true)->whereIn('status', ['rejected', 'voided'])
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(fee), 0) AS amount')->first();

        return [
            'rows'            => $rows,
            'totals'          => $totals,
            'free'            => $free,
            'excluded'        => (int) $excluded->n,
            'excluded_amount' => (float) $excluded->amount,
        ];
    }

    /**
     * The selected date preset. Defaults to "today" so the page opens on the
     * day's requests; "all" removes the date limit.
     */
    private function rangeKey(Request $request): string
    {
        $key = $request->input('range', 'today');

        return in_array($key, ['all', 'today', 'week', 'month', 'custom'], true) ? $key : 'today';
    }

    /**
     * [from, to] for the date-range filter (by request date), or null for
     * all time. Presets: today (default) | week | month | custom | all.
     */
    private function dateRange(Request $request): ?array
    {
        // A malformed from/to (e.g. hand-edited URL) is ignored, not a 500.
        $day = fn($v) => filled($v) ? rescue(fn() => \Illuminate\Support\Carbon::parse($v), null, false) : null;

        if ($this->rangeKey($request) === 'custom') {
            [$from, $to] = [$day($request->from), $day($request->to)];
            if (! $from && ! $to) {
                return null;
            }
            $from = $from?->startOfDay() ?? \Illuminate\Support\Carbon::create(2000);
            $to   = $to?->endOfDay() ?? now()->endOfDay();

            return $from <= $to ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return match ($this->rangeKey($request)) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null,
        };
    }

    /**
     * Requests matching the list filters (search, type, purpose, status, date range).
     * Shared by the list and its stat cards so the two always agree.
     */
    private function filteredRequests(Request $request, bool $withStatus = true)
    {
        $query = DocumentRequest::query();

        if ($withStatus && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('document_type_id', $request->type);
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        if (in_array($request->source, ['kiosk', 'counter'], true)) {
            $query->where('source', $request->source);
        }

        if ($bounds = $this->dateRange($request)) {
            $query->whereBetween('created_at', $bounds);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            // Search by citizen name (first/middle/last, incl. combined full name)
            // or address, OR by the request's control number / doc number.
            $like   = '%' . $search . '%';
            // Strip a prefix like "BRGY-" so "BRGY-00011" also matches doc_number 11.
            $numPart = ltrim(preg_replace('/^[A-Za-z]+-?/', '', $search), '0') ?: $search;

            $query->where(function ($q) use ($search, $like, $numPart) {
                $q->whereHas('citizen', function ($c) use ($search, $like) {
                    // Name parts in any order, plus the citizen's address.
                    $c->searchName($search)
                      ->orWhere('complete_address', 'like', $like);
                })
                // Control number: match the raw doc_number, or the type's prefix.
                ->orWhere('doc_number', 'like', '%' . $numPart . '%')
                ->orWhereHas('documentType', function ($t) use ($like) {
                    $t->where('prefix', 'like', $like);
                });
            });
        }

        return $query;
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
        $recentCitizens = Citizen::with(['activeHold', 'addressZone'])->where('is_active', 1)
            ->orderByDesc('date_last_updated')
            ->limit(6)
            ->get();

        $selectedType = $request->filled('type')
            ? $types->firstWhere('id', (int) $request->type)
            : null;

        $purposes = DocumentPurpose::active()->orderBy('sort_order')->orderBy('name')->get();

        // Allows deep-linking from the citizen list straight into a request
        // for that person.
        $selectedCitizen = $request->filled('citizen')
            ? Citizen::withWalkins()->find($request->citizen)
            : null;

        return view('documents.requests.create', compact(
            'types', 'recentCitizens', 'selectedType', 'purposes', 'selectedCitizen'
        ));
    }

    // ─── Walk-ins ──────────────────────────────────────────────────────────
    // People who are not registered residents but need a document. They get a
    // minimal record (hidden from everything except document requests) so a
    // return visit only fills what is still missing.

    private function walkinPayload(Citizen $c): array
    {
        $last = DocumentRequest::where('citizen_id', $c->id)->max('created_at');
        return [
            'id'         => $c->id,
            'name'       => $c->full_name,
            'code'       => $c->walkin_code,
            'bday'       => $c->bday?->format('M d, Y'),
            'address'    => $c->full_address,
            'contact'    => $c->contact,
            'qrcode'     => '',
            'profile'    => $c->profile_url,
            'last_visit' => $last ? \Carbon\Carbon::parse($last)->format('M d, Y') : null,
            'walkin'     => true,
            'voter' => false, 'senior' => ($c->age ?? 0) >= 60, 'pwd' => false, 'solo' => false,
            'hold'       => $c->activeHold?->reason,
        ];
    }

    // Saved walk-ins by name, or by reference (WI-00012).
    public function walkinSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $query = Citizen::onlyWalkins()->with('activeHold');
        if (preg_match('/^WI-?0*(\d+)$/i', $q, $m)) {
            $query->whereKey((int) $m[1]);
        } else {
            $query->searchName($q);
        }

        return response()->json($query->orderBy('lname')->orderBy('fname')->limit(10)->get()
            ->map(fn($c) => $this->walkinPayload($c))->values());
    }

    public function walkinStore(Request $request)
    {
        $data = $request->validate([
            'fname'            => 'required|string|max:100',
            'mname'            => 'nullable|string|max:100',
            'lname'            => 'required|string|max:100',
            'suffix'           => 'nullable|string|max:20',
            'bday'             => 'required|date|before_or_equal:today',
            'contact'          => 'nullable|string|max:20',
            'walkin_id_type'   => 'nullable|string|max:60',
            'walkin_id_number' => 'nullable|string|max:60',
            'consent'          => 'accepted',
        ], [
            'consent.accepted' => 'Please confirm the person agreed to the collection of their information (Data Privacy Act).',
        ]);

        $same = fn($q) => $q->whereRaw('LOWER(TRIM(fname)) = ?', [mb_strtolower(trim($data['fname']))])
            ->whereRaw('LOWER(TRIM(lname)) = ?', [mb_strtolower(trim($data['lname']))])
            ->whereDate('bday', $data['bday']);

        // Already a registered resident → use their record instead.
        if ($resident = $same(Citizen::query())->first()) {
            return response()->json([
                'duplicate' => 'resident',
                'message'   => "{$resident->full_name} is already a registered resident. Select them from the search instead.",
                'citizen'   => ['id' => $resident->id, 'name' => $resident->full_name, 'address' => $resident->full_address,
                                'contact' => $resident->contact, 'qrcode' => $resident->qrcode, 'profile' => $resident->profile_url,
                                'voter' => (bool) $resident->voters, 'senior' => ($resident->age ?? 0) >= 60, 'pwd' => (bool) $resident->is_pwd,
                                'solo' => (bool) $resident->is_soloparents, 'hold' => $resident->activeHold?->reason],
            ], 409);
        }
        // Came before as a walk-in → reuse that record.
        if ($existing = $same(Citizen::onlyWalkins())->first()) {
            return response()->json([
                'duplicate' => 'walkin',
                'message'   => "{$existing->full_name} has visited before ({$existing->walkin_code}) — using the saved details.",
                'walkin'    => $this->walkinPayload($existing),
            ], 409);
        }

        $walkin = Citizen::create([
            'is_walkin'         => true,
            'qrcode'            => md5(\Illuminate\Support\Str::random(16) . microtime()),   // required column; never printed
            'fname'             => trim($data['fname']),
            'mname'             => trim((string) ($data['mname'] ?? '')) ?: null,
            'lname'             => trim($data['lname']),
            'suffix'            => trim((string) ($data['suffix'] ?? '')) ?: null,
            'bday'              => $data['bday'],
            'contact'           => $data['contact'] ?? null,
            'walkin_id_type'    => $data['walkin_id_type'] ?? null,
            'walkin_id_number'  => $data['walkin_id_number'] ?? null,
            'walkin_consent_at' => now(),
            // Required columns with neutral values — asked only if a document prints them.
            'bplace'            => '',
            'civil_status'      => 0,
            'gender'            => 0,
            'owner_status'      => 0,
            'is_pwd'            => 0,
            'voters'            => 0,
            'is_active'         => 1,
            'date_created'      => now(),
            'date_last_updated' => now(),
        ]);

        ActivityLog::record('walkin_created', 'citizen', $walkin->id, "Walk-in {$walkin->walkin_code} added: {$walkin->full_name}");

        return response()->json(['success' => true, 'walkin' => $this->walkinPayload($walkin)]);
    }

    // What the chosen document needs from the chosen person, and what is missing.
    public function personCheck(Request $request)
    {
        $request->validate([
            'citizen_id'       => 'required|exists:eb_citizen,id',
            'document_type_id' => 'required|exists:eb_document_types,id',
        ]);
        $citizen = Citizen::withWalkins()->findOrFail($request->citizen_id);
        $type    = DocumentType::with('fields')->findOrFail($request->document_type_id);
        $required = \App\Support\PersonFields::required($type);

        return response()->json([
            'walkin'  => (bool) $citizen->is_walkin,
            'allowed' => !$citizen->is_walkin || (bool) $type->allow_walkin,
            'fields'  => \App\Support\PersonFields::describe($required, $citizen),
            'missing' => \App\Support\PersonFields::missing($type, $citizen),
            'qr'      => $citizen->is_walkin && \App\Support\PersonFields::usesQr($type),
            // The document prints a photo: show the current one so staff can retake or replace it.
            'photo'   => \App\Support\PersonFields::usesPhoto($type) ? ['url' => $citizen->profile_url] : null,
        ]);
    }

    // Photo taken or uploaded on the request form (residents and walk-ins alike).
    // Saved as the person's profile photo, so the certificate prints it.
    public function personPhoto(Request $request)
    {
        $request->validate([
            'citizen_id' => 'required|integer',
            'photo'      => 'required|image|mimes:jpeg,png,jpg|max:4096',
        ]);
        $citizen = Citizen::withWalkins()->findOrFail($request->citizen_id);
        $old     = $citizen->replaceProfilePhoto($request->file('photo'));

        ActivityLog::record(
            'updated', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname}: profile photo " . ($old ? 'changed' : 'added') . ' while requesting a document',
            ['changed' => ['profile' => ['from' => $old ? 'Photo' : '—', 'to' => 'New photo']]]
        );

        return response()->json(['url' => $citizen->fresh()->profile_url]);
    }

    // For a walk-in: the type must allow walk-ins, and every detail the document
    // prints must be on file (submitted person[...] values are applied first).
    // Returns an error message, or null when fine. $save writes the details back.
    private function prepareWalkin(Citizen $citizen, DocumentType $type, Request $request, bool $save): ?array
    {
        if (!$citizen->is_walkin) return null;
        if (!$type->allow_walkin) {
            return ['citizen_id' => ["{$type->name} is not available for walk-ins. Register the person as a resident first."]];
        }

        $override = ($type->allow_body_edit && $request->filled('body_override')) ? $request->input('body_override') : null;
        $required = \App\Support\PersonFields::required($type, $override);
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), \App\Support\PersonFields::rules($required));
        if ($validator->fails()) return $validator->errors()->toArray();

        \App\Support\PersonFields::fill($citizen, $required, (array) $request->input('person', []));
        $missing = array_values(array_filter($required, fn($k) => \App\Support\PersonFields::isEmpty($citizen, $k)));
        if ($missing) {
            $labels = array_map(fn($k) => \App\Support\PersonFields::FIELDS[$k]['label'], $missing);
            return ['person' => ['This document needs: ' . implode(', ', $labels) . '.']];
        }
        if ($save && $citizen->isDirty()) {
            $citizen->date_last_updated = now();
            $citizen->save();
        }
        return null;
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
        $citizen = $request->filled('citizen_id') ? Citizen::withWalkins()->find($request->citizen_id) : null;

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
    public static function buildCustomFields(DocumentType $type, Request $request, ?Citizen $citizen): array
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

        $citizen = Citizen::withWalkins()->find($request->citizen_id);
        $purpose = trim((string) $request->input('purpose', ''));

        if ($holdMessage = $this->holdMessage($citizen)) {
            return response()->json(['errors' => ['citizen_id' => [$holdMessage]]], 422);
        }
        if ($walkinErrors = $this->prepareWalkin($citizen, $type, $request, false)) {
            return response()->json(['errors' => $walkinErrors], 422);
        }

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

        $citizen = Citizen::withWalkins()->find($request->citizen_id);

        if ($holdMessage = $this->holdMessage($citizen)) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['citizen_id' => [$holdMessage]]], 422);
            }
            return back()->withErrors(['citizen_id' => $holdMessage])->withInput();
        }
        if ($walkinErrors = $this->prepareWalkin($citizen, $type, $request, true)) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $walkinErrors], 422);
            }
            return back()->withErrors($walkinErrors)->withInput();
        }
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
        $documentRequest->load(['documentType.fields', 'templateVersion', 'citizen.civilStatus', 'citizen.activeHold', 'approvedBy', 'createdBy', 'voidedBy']);
        $header  = $documentRequest->renderHeader();
        $preview = $documentRequest->resolveTemplate();

        return view('documents.requests.show', compact('documentRequest', 'header', 'preview'));
    }

    /**
     * Why this citizen may not be issued documents right now, or null if
     * they may. A hold placed after a request was filed also blocks
     * approving and releasing that request.
     */
    private function holdMessage(?Citizen $citizen): ?string
    {
        $hold = $citizen?->activeHold;

        return $hold
            ? 'This citizen is on hold and cannot be issued documents. Reason: ' . $hold->reason
            : null;
    }

    public function approve(DocumentRequest $documentRequest)
    {
        if ($holdMessage = $this->holdMessage($documentRequest->citizen)) {
            return back()->with('error', $holdMessage);
        }

        $documentRequest->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($documentRequest->source === 'kiosk') \App\Notifications\KioskDocumentRequested::markHandled($documentRequest->id);

        return back()->with('success', 'Request approved.');
    }

    public function release(Request $request, DocumentRequest $documentRequest)
    {
        if ($holdMessage = $this->holdMessage($documentRequest->citizen)) {
            return back()->with('error', $holdMessage);
        }

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

        // Paid only if the full fee was received. A short release (confirmed
        // with "Release anyway") is recorded as not fully paid, so it can't
        // read as "✓ Paid" next to a shortfall. No amount entered (e.g. paid
        // earlier) counts as paid, as before.
        $amountPaid = $documentRequest->is_paid && $request->filled('amount_paid')
            ? (float) $request->input('amount_paid')
            : null;
        $feePaid = $documentRequest->is_paid
            && ($amountPaid === null || $amountPaid >= (float) $documentRequest->fee);

        $documentRequest->update([
            'status'       => 'released',
            'or_number'    => $orValue,
            'fee_paid'     => $feePaid,
            'amount_paid'  => $amountPaid,
            'released_at'  => now(),
        ]);

        if ($documentRequest->source === 'kiosk') \App\Notifications\KioskDocumentRequested::markHandled($documentRequest->id);

        // Auto-open the print dialog once, right after releasing (one-time flash).
        return back()
            ->with('success', 'Document released successfully.')
            ->with('auto_print', true);
    }

    /**
     * Void a RELEASED document: it stays on record (control number kept, so
     * the sequence has no gaps) but is cancelled, can't be printed, and no
     * longer counts toward fees. Before release, use Reject instead.
     */
    public function void(Request $request, DocumentRequest $documentRequest)
    {
        $data = $request->validate([
            'void_reason' => 'required|string|max:1000',
        ], [
            'void_reason.required' => 'Please give the reason for voiding this document.',
        ]);

        if ($documentRequest->status !== 'released') {
            return back()->with('error', 'Only released documents can be voided. Use Reject for requests that have not been released.');
        }

        $documentRequest->update([
            'status'      => 'voided',
            'void_reason' => trim($data['void_reason']),
            'voided_at'   => now(),
            'voided_by'   => auth()->id(),
        ]);

        ActivityLog::record(
            'voided', 'document_request', $documentRequest->id,
            "Voided {$documentRequest->control_number} ({$documentRequest->documentType?->name}) for {$documentRequest->citizen?->full_name}",
            ['reason' => trim($data['void_reason']), 'fee' => (float) $documentRequest->fee, 'amount_paid' => $documentRequest->amount_paid]
        );

        return back()->with('success', 'Document voided. It stays on record but can no longer be printed and is excluded from fee totals.');
    }

    public function reject(Request $request, DocumentRequest $documentRequest)
    {
        $request->validate(['remarks' => 'required|string']);

        $documentRequest->update([
            'status'  => 'rejected',
            'remarks' => $request->remarks,
        ]);

        if ($documentRequest->source === 'kiosk') \App\Notifications\KioskDocumentRequested::markHandled($documentRequest->id);

        return back()->with('success', 'Request rejected.');
    }

    // Adjust the print counter. Defaults to +1 (a print); pass step=-1 to undo
    // (e.g. staff cancelled the print dialog). Never drops below zero.
    public function countPrint(Request $request, DocumentRequest $documentRequest)
    {
        $step = (int) $request->input('step', 1) < 0 ? -1 : 1;

        if ($step > 0 && $documentRequest->isVoided()) {
            return response()->json(['ok' => false, 'message' => 'This document has been voided and cannot be printed.'], 422);
        }

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
