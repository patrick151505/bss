<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blotter;
use App\Models\BlotterAction;
use App\Models\BlotterParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlotterController extends Controller
{
    public function index(Request $request)
    {
        $query = Blotter::with('parties')
            ->when($request->search, function ($q, $v) {
                $q->where(function ($q) use ($v) {
                    $q->where('blotter_no', 'like', "%{$v}%")
                      ->orWhere('incident_location', 'like', "%{$v}%")
                      ->orWhereHas('parties', fn($p) => $p->where('name', 'like', "%{$v}%"));
                });
            })
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->type,   fn($q, $v) => $q->where('type',   $v))
            ->when($request->date_from, fn($q, $v) => $q->where('filed_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->where('filed_date', '<=', $v))
            ->orderByDesc('filed_date')
            ->orderByDesc('id');

        $blotters = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => Blotter::count(),
            'filed'    => Blotter::where('status', 'filed')->count(),
            'ongoing'  => Blotter::where('status', 'ongoing')->count(),
            'settled'  => Blotter::where('status', 'settled')->count(),
        ];

        return view('blotter.index', compact('blotters', 'stats'));
    }

    public function create()
    {
        $blotterNo = Blotter::generateBlotterNo();
        return view('blotter.create', compact('blotterNo'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'type' => $request->type === 'other' && $request->filled('type_other')
                ? trim($request->type_other)
                : $request->type,
        ]);

        $validated = $request->validate([
            'filed_date'        => 'required|date',
            'incident_date'     => 'required|date',
            'incident_time'     => 'nullable|date_format:H:i',
            'type'              => 'required|string|max:100',
            'incident_location' => 'nullable|string|max:255',
            'complainants'      => 'required|array|min:1',
            'complainants.*.name'    => 'required|string|max:255',
            'complainants.*.address' => 'nullable|string|max:255',
            'complainants.*.contact' => 'nullable|string|max:30',
            'complainants.*.citizen_id' => 'nullable|integer',
            'respondents'       => 'required|array|min:1',
            'respondents.*.name'    => 'required|string|max:255',
            'respondents.*.address' => 'nullable|string|max:255',
            'respondents.*.contact' => 'nullable|string|max:30',
            'respondents.*.citizen_id' => 'nullable|integer',
            'narrative'         => 'required|string',
            'witnesses'         => 'nullable|string',
            'attending_officer' => 'nullable|string|max:255',
            'remarks'           => 'nullable|string',
            'photos.*'          => 'nullable|image|max:4096',
        ], [
            'complainants.required'        => 'At least one complainant is required.',
            'complainants.*.name.required' => 'Each complainant must have a name.',
            'respondents.required'         => 'At least one respondent is required.',
            'respondents.*.name.required'  => 'Each respondent must have a name.',
        ]);

        $complainants = $validated['complainants'];
        $respondents  = $validated['respondents'];

        // Store blotter-level photos into a tmp folder; moved after we have the real blotter id below
        $photoPaths = $this->storePhotos($request, 'blotter/tmp');

        $data = [
            'blotter_no'          => Blotter::generateBlotterNo(),
            'filed_date'          => $validated['filed_date'],
            'incident_date'       => $validated['incident_date'],
            'incident_time'       => $validated['incident_time'] ?? null,
            'type'                => $validated['type'],
            'incident_location'   => $validated['incident_location'] ?? null,
            'narrative'           => $validated['narrative'],
            'witnesses'           => $validated['witnesses'] ?? null,
            'attending_officer'   => $validated['attending_officer'] ?? null,
            'remarks'             => $validated['remarks'] ?? null,
            'photos'              => $photoPaths ?: null,
            'status'              => 'filed',
            'created_by'          => auth()->id(),
            // legacy single columns — keep in sync with first party
            'complainant_name'       => $complainants[0]['name'],
            'complainant_address'    => $complainants[0]['address'] ?? null,
            'complainant_contact'    => $complainants[0]['contact'] ?? null,
            'complainant_citizen_id' => $complainants[0]['citizen_id'] ?: null,
            'respondent_name'        => $respondents[0]['name'],
            'respondent_address'     => $respondents[0]['address'] ?? null,
            'respondent_contact'     => $respondents[0]['contact'] ?? null,
            'respondent_citizen_id'  => $respondents[0]['citizen_id'] ?: null,
        ];

        $blotter = Blotter::create($data);

        // Move tmp photos to the real blotter folder now that we have the ID
        if (!empty($photoPaths)) {
            $movedPaths = [];
            foreach ($photoPaths as $tmpPath) {
                $filename   = basename($tmpPath);
                $destPath   = "blotter/{$blotter->id}/{$filename}";
                Storage::disk('public')->move($tmpPath, $destPath);
                $movedPaths[] = $destPath;
            }
            $blotter->update(['photos' => $movedPaths]);
        }

        $this->syncParties($blotter, $complainants, $respondents);

        ActivityLog::record('created', 'blotter', $blotter->id,
            "Blotter #{$blotter->blotter_no} filed.",
            [
                'type'             => $blotter->typeLabel(),
                'complainants'     => $blotter->partyNames('complainant'),
                'respondents'      => $blotter->partyNames('respondent'),
                'incident_date'    => $blotter->incident_date->format('M d, Y'),
                'incident_location'=> $blotter->incident_location,
                'status'           => $blotter->statusLabel(),
            ]
        );

        $redirectUrl = route('blotters.show', $blotter);

        if ($request->ajax()) {
            return response()->json([
                'success'      => true,
                'message'      => "Blotter #{$blotter->blotter_no} filed successfully.",
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)
            ->with('success', "Blotter #{$blotter->blotter_no} filed successfully.");
    }

    public function show(Blotter $blotter)
    {
        $blotter->load(['parties.citizen', 'actions.creator', 'actions.versions.savedBy']);

        $logs = ActivityLog::where('subject_type', 'blotter')
            ->where('subject_id', $blotter->id)
            ->orderByDesc('created_at')
            ->get();

        $complainants = $blotter->parties->where('role', 'complainant')->values();
        $respondents  = $blotter->parties->where('role', 'respondent')->values();
        $actions      = $blotter->actions;

        return view('blotter.show', compact('blotter', 'logs', 'actions', 'complainants', 'respondents'));
    }

    public function storeAction(Request $request, Blotter $blotter)
    {
        $request->validate([
            'action_type'                  => 'required|string|max:50',
            'scheduled_date'               => 'required|date',
            'scheduled_time'               => 'nullable|date_format:H:i',
            'venue'                        => 'nullable|string|max:255',
            'conducted_by'                 => 'nullable|string|max:255',
            'complainants_attendance.*'    => 'nullable|in:present,absent',
            'respondents_attendance.*'     => 'nullable|in:present,absent',
            'notes'                        => 'nullable|string',
            'photos.*'                     => 'nullable|image|max:4096',
        ]);

        $complainantAtt = $request->input('complainants_attendance', []);
        $respondentAtt  = $request->input('respondents_attendance', []);

        $photos = $this->storePhotos($request, "blotter/{$blotter->id}/actions");

        $action = BlotterAction::create([
            'blotter_id'             => $blotter->id,
            'action_type'            => $request->action_type,
            'scheduled_date'         => $request->scheduled_date,
            'scheduled_time'         => $request->scheduled_time,
            'venue'                  => $request->venue,
            'conducted_by'           => $request->conducted_by,
            'outcome'                => 'pending',
            'complainant_attendance' => $complainantAtt ?: new \stdClass(),
            'respondent_attendance'  => $respondentAtt  ?: new \stdClass(),
            'notes'                  => $request->notes,
            'photos'                 => $photos ?: null,
            'created_by'             => auth()->id(),
        ]);

        $label = BlotterAction::ACTION_TYPES[$request->action_type] ?? ucfirst($request->action_type);
        ActivityLog::record('action_added', 'blotter', $blotter->id,
            "{$label} scheduled on " . $action->scheduled_date->format('M d, Y') . ".",
            array_filter([
                'type'         => $label,
                'date'         => $action->scheduled_date->format('M d, Y'),
                'time'         => $request->scheduled_time,
                'venue'        => $request->venue,
                'conducted_by' => $request->conducted_by,
                'notes'        => $request->notes,
            ])
        );

        return back()->with('success', "{$label} scheduled successfully.");
    }

    public function updateActionOutcome(Request $request, Blotter $blotter, BlotterAction $action)
    {
        $request->validate([
            'outcome'                      => 'required|in:' . implode(',', array_keys(BlotterAction::OUTCOMES)),
            'complainants_attendance.*'    => 'nullable|in:present,absent',
            'respondents_attendance.*'     => 'nullable|in:present,absent',
            'notes'                        => 'nullable|string',
            'keep_photos'                  => 'nullable|array',
            'keep_photos.*'                => 'nullable|string',
            'photos.*'                     => 'nullable|image|max:4096',
        ]);

        $complainantAtt = $request->input('complainants_attendance', []);
        $respondentAtt  = $request->input('respondents_attendance', []);

        $keptPhotos = $request->input('keep_photos', []);
        $newPhotos  = $this->storePhotos($request, "blotter/{$blotter->id}/actions");
        $allPhotos  = array_merge($keptPhotos, $newPhotos);

        // Snapshot the current state BEFORE update
        \App\Models\BlotterActionVersion::create([
            'action_id'              => $action->id,
            'blotter_id'             => $blotter->id,
            'outcome'                => $action->outcome,
            'complainant_attendance' => $action->complainant_attendance,
            'respondent_attendance'  => $action->respondent_attendance,
            'notes'                  => $action->notes,
            'photos'                 => $action->photos,
            'saved_by'               => auth()->id(),
        ]);

        $action->update([
            'outcome'                => $request->outcome,
            'complainant_attendance' => $complainantAtt ?: new \stdClass(),
            'respondent_attendance'  => $respondentAtt  ?: new \stdClass(),
            'notes'                  => $request->notes,
            'photos'                 => $allPhotos ?: null,
        ]);

        ActivityLog::record('updated', 'blotter', $blotter->id,
            "{$action->actionLabel()} #{$action->id} outcome updated to {$action->outcomeLabel()}."
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Outcome updated to {$action->outcomeLabel()}."]);
        }

        return back()->with('success', "Outcome updated to {$action->outcomeLabel()}.");
    }

    private function storePhotos(Request $request, string $folder): array
    {
        $paths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $ts       = now()->format('Ymd_His');
                $unique   = uniqid('', true);
                $ext      = $file->getClientOriginalExtension() ?: $file->extension();
                $filename = "{$ts}_{$unique}.{$ext}";
                $paths[]  = $file->storeAs($folder, $filename, 'public');
            }
        }
        return $paths;
    }

    public function destroyAction(Blotter $blotter, BlotterAction $action)
    {
        $label = $action->actionLabel();
        $date  = $action->scheduled_date->format('M d, Y');
        $action->delete();

        ActivityLog::record('action_deleted', 'blotter', $blotter->id,
            "{$label} scheduled on {$date} was removed."
        );

        return back()->with('success', "{$label} entry deleted.");
    }

    public function edit(Blotter $blotter)
    {
        return view('blotter.edit', compact('blotter'));
    }

    public function update(Request $request, Blotter $blotter)
    {
        $request->merge([
            'type' => $request->type === 'other' && $request->filled('type_other')
                ? trim($request->type_other)
                : $request->type,
        ]);

        $data = $request->validate([
            'filed_date'          => 'required|date',
            'incident_date'       => 'required|date',
            'incident_time'       => 'nullable|date_format:H:i',
            'type'                => 'required|string|max:100',
            'incident_location'   => 'nullable|string|max:255',
            'complainant_citizen_id' => 'nullable|exists:eb_citizen,id',
            'complainant_name'    => 'required|string|max:255',
            'complainant_address' => 'nullable|string|max:255',
            'complainant_contact' => 'nullable|string|max:30',
            'respondent_citizen_id'  => 'nullable|exists:eb_citizen,id',
            'respondent_name'     => 'required|string|max:255',
            'respondent_address'  => 'nullable|string|max:255',
            'respondent_contact'  => 'nullable|string|max:30',
            'narrative'           => 'required|string',
            'witnesses'           => 'nullable|string',
            'attending_officer'   => 'nullable|string|max:255',
            'remarks'             => 'nullable|string',
        ]);

        $data['updated_by'] = auth()->id();

        // Sync multi-party lists
        $complainants = $request->input('complainants', []);
        $respondents  = $request->input('respondents', []);
        if (!empty($complainants[0]['name'])) {
            $data['complainant_name']       = $complainants[0]['name'];
            $data['complainant_address']    = $complainants[0]['address'] ?? null;
            $data['complainant_contact']    = $complainants[0]['contact'] ?? null;
            $data['complainant_citizen_id'] = $complainants[0]['citizen_id'] ?: null;
        }
        if (!empty($respondents[0]['name'])) {
            $data['respondent_name']       = $respondents[0]['name'];
            $data['respondent_address']    = $respondents[0]['address'] ?? null;
            $data['respondent_contact']    = $respondents[0]['contact'] ?? null;
            $data['respondent_citizen_id'] = $respondents[0]['citizen_id'] ?: null;
        }

        // Capture changes before saving
        $changes = [];
        $watchFields = [
            'type'               => 'Nature/Type',
            'incident_date'      => 'Incident Date',
            'incident_time'      => 'Incident Time',
            'incident_location'  => 'Location',
            'complainant_name'   => 'Complainant',
            'respondent_name'    => 'Respondent',
            'attending_officer'  => 'Attending Officer',
            'narrative'          => 'Narrative',
        ];
        foreach ($watchFields as $field => $label) {
            // date casts return Carbon; normalise to string for comparison
            $oldRaw = $blotter->$field;
            $old = $oldRaw instanceof \Carbon\Carbon ? $oldRaw->toDateString() : (string) ($oldRaw ?? '');
            $new = (string) ($data[$field] ?? '');
            if ($old !== $new) {
                $changes[$label] = ['from' => $old ?: '—', 'to' => $new ?: '—'];
            }
        }

        $blotter->update($data);

        $this->syncParties($blotter, $complainants, $respondents);

        ActivityLog::record('updated', 'blotter', $blotter->id,
            "Blotter #{$blotter->blotter_no} details updated.",
            $changes ?: ['note' => 'No field changes detected']
        );

        return redirect()->route('blotters.show', $blotter)
            ->with('success', "Blotter #{$blotter->blotter_no} updated successfully.");
    }

    public function updateStatus(Request $request, Blotter $blotter)
    {
        $data = $request->validate([
            'status'       => 'required|in:' . implode(',', array_keys(Blotter::STATUSES)),
            'action_taken' => 'nullable|string',
            'settled_date' => 'nullable|date',
            'referred_to'  => 'nullable|string|max:255',
            'remarks'      => 'nullable|string',
        ]);

        if ($data['status'] === 'settled' && empty($data['settled_date'])) {
            $data['settled_date'] = now()->toDateString();
        }

        $oldStatus = $blotter->statusLabel();
        $data['updated_by'] = auth()->id();
        $blotter->update($data);

        $props = ['status' => ['from' => $oldStatus, 'to' => $blotter->statusLabel()]];
        if (!empty($data['action_taken'])) $props['action_taken'] = $data['action_taken'];
        if (!empty($data['settled_date'])) $props['settled_date'] = $data['settled_date'];
        if (!empty($data['referred_to']))  $props['referred_to']  = $data['referred_to'];
        if (!empty($data['remarks']))      $props['remarks']      = $data['remarks'];

        ActivityLog::record('status_updated', 'blotter', $blotter->id,
            "Status changed from {$oldStatus} to {$blotter->statusLabel()}.",
            $props
        );

        return back()->with('success', "Status updated to " . Blotter::STATUSES[$data['status']] . ".");
    }

    public function destroy(Blotter $blotter)
    {
        $no = $blotter->blotter_no;

        ActivityLog::record('deleted', 'blotter', $blotter->id,
            "Blotter #{$no} deleted."
        );

        $blotter->delete();

        return redirect()->route('blotters.index')
            ->with('success', "Blotter #{$no} has been deleted.");
    }

    private function syncParties(Blotter $blotter, array $complainants, array $respondents): void
    {
        $blotter->parties()->delete();

        $rows = [];
        foreach ($complainants as $i => $p) {
            $name = trim($p['name'] ?? '');
            if ($name === '') continue;
            $rows[] = [
                'blotter_id' => $blotter->id,
                'role'       => 'complainant',
                'citizen_id' => $p['citizen_id'] ?: null,
                'name'       => $name,
                'address'    => $p['address'] ?? null,
                'contact'    => $p['contact']  ?? null,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach ($respondents as $i => $p) {
            $name = trim($p['name'] ?? '');
            if ($name === '') continue;
            $rows[] = [
                'blotter_id' => $blotter->id,
                'role'       => 'respondent',
                'citizen_id' => $p['citizen_id'] ?: null,
                'name'       => $name,
                'address'    => $p['address'] ?? null,
                'contact'    => $p['contact']  ?? null,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            BlotterParty::insert($rows);
        }
    }
}
