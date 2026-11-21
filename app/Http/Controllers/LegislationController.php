<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Legislation;
use App\Models\LegislationEvent;
use App\Models\Official;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Ordinances & Resolutions of the Sangguniang Barangay.
 *
 * Status changes go through advance() (legislation.approve) so every step lands
 * on the timeline. On the create/edit form only users with approve may set the
 * status directly — used when encoding measures passed before the system.
 */
class LegislationController extends Controller
{
    /** Signed copies are kept off the public disk; served through file(). */
    const DISK = 'local';
    const DIR  = 'legislation';

    public function index(Request $request)
    {
        $query = Legislation::with('authors')
            ->ofType($request->type)
            ->search($request->search)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->year, fn($q, $y) => $q->where('series_year', $y))
            ->when($request->category, fn($q, $c) => $q->where('category', $c));

        $legislations = $query->orderByDesc('series_year')->orderByDesc('number')->paginate(15)->withQueryString();

        $stats = [
            'ordinances'  => Legislation::where('type', 'ordinance')->count(),
            'resolutions' => Legislation::where('type', 'resolution')->count(),
            'in_force'    => Legislation::where('status', 'effective')->count(),
            'pending'     => Legislation::whereIn('status', ['draft', 'first_reading', 'second_reading', 'third_reading'])->count(),
        ];
        $years = Legislation::select('series_year')->distinct()->orderByDesc('series_year')->pluck('series_year');

        return view('legislation.index', compact('legislations', 'stats', 'years'));
    }

    public function create(Request $request)
    {
        $type = isset(Legislation::TYPES[$request->type]) ? $request->type : 'ordinance';
        $legislation = new Legislation([
            'type'        => $type,
            'series_year' => (int) now()->year,
            'number'      => Legislation::nextNumber($type, (int) now()->year),
            'status'      => 'draft',
            'date_introduced' => now()->toDateString(),
        ]);

        return view('legislation.create', $this->formData($legislation));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $legislation = DB::transaction(function () use ($request, $data) {
            $data['status']     = $this->statusFor($request, null);
            $data['created_by'] = $data['updated_by'] = auth()->id();
            if ($request->hasFile('file')) {
                $data['file_path'] = $request->file('file')->store(self::DIR, self::DISK);
            }
            $legislation = Legislation::create($data);
            $this->syncAuthors($legislation, $request->input('authors', []));

            // Seed the timeline from the dates entered.
            if ($legislation->date_introduced) $this->event($legislation, 'introduced', $legislation->date_introduced);
            if ($legislation->status !== 'draft' && !$legislation->hasPassed()) {
                $this->event($legislation, $legislation->status, now());
            }
            if ($legislation->hasPassed()) $this->event($legislation, 'approved', $legislation->date_approved ?? now());
            if ($legislation->status === 'effective') $this->event($legislation, 'effective', $legislation->date_effective ?? now());
            if ($legislation->status === 'vetoed') $this->event($legislation, 'vetoed', now());

            $this->syncLinks($legislation, $request->input('repeals', []));
            return $legislation;
        });

        $this->log('created', $legislation, "Recorded {$legislation->reference()} — {$legislation->title}",
            ['details' => array_filter($this->snapshot($legislation->fresh()), fn($v) => $v !== '—')]);

        return redirect()->route('legislation.show', $legislation)->with('success', "{$legislation->reference()} saved.");
    }

    public function show(Legislation $legislation)
    {
        $legislation->load(['authors', 'events.user', 'amends', 'amendments', 'repealedBy', 'repeals', 'creator']);

        // Who changed what (Activity Logs), for users allowed to see the logs.
        $history = auth()->user()->can('activity_logs.view')
            ? ActivityLog::with('user:id,name')->where('subject_type', self::LOG_TYPE)->where('subject_id', $legislation->id)
                ->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get()
            : null;
        $historyCount = $history ? ActivityLog::where('subject_type', self::LOG_TYPE)->where('subject_id', $legislation->id)->count() : 0;

        return view('legislation.show', compact('legislation', 'history', 'historyCount'));
    }

    public function edit(Legislation $legislation)
    {
        $legislation->load('authors', 'repeals');
        return view('legislation.edit', $this->formData($legislation));
    }

    public function update(Request $request, Legislation $legislation)
    {
        $data   = $this->validated($request, $legislation);
        $before = $this->snapshot($legislation);
        $fileNote = $request->hasFile('file')
            ? ($legislation->file_path ? 'Replaced' : 'Uploaded')
            : ($request->boolean('remove_file') && $legislation->file_path ? 'Removed' : null);

        DB::transaction(function () use ($request, $data, $legislation) {
            $before = $legislation->status;
            $data['status']     = $this->statusFor($request, $legislation);
            $data['updated_by'] = auth()->id();
            if ($request->hasFile('file')) {
                $old = $legislation->file_path;
                $data['file_path'] = $request->file('file')->store(self::DIR, self::DISK);
                if ($old) Storage::disk(self::DISK)->delete($old);
            } elseif ($request->boolean('remove_file') && $legislation->file_path) {
                Storage::disk(self::DISK)->delete($legislation->file_path);
                $data['file_path'] = null;
            }
            $legislation->update($data);
            $this->syncAuthors($legislation, $request->input('authors', []));

            if ($before !== $legislation->status) {
                $date = match ($legislation->status) {
                    'approved'  => $legislation->date_approved,
                    'effective' => $legislation->date_effective,
                    default     => null,
                } ?? now();
                $this->event($legislation, $legislation->status, $date, 'Status set on the edit form');
            }
            $this->syncLinks($legislation, $request->input('repeals', []));
        });

        $changed = $this->diff($before, $this->snapshot($legislation->fresh()));
        if ($fileNote) {
            $changed['Signed copy'] = ['from' => $fileNote === 'Uploaded' ? 'None' : 'Old file', 'to' => $fileNote === 'Removed' ? 'None' : 'New file'];
        }
        $this->log('updated', $legislation,
            "Updated {$legislation->reference()}" . ($changed ? '' : ' (no changes)'),
            $changed ? ['changed' => $changed] : []);

        return redirect()->route('legislation.show', $legislation)->with('success', 'Changes saved.');
    }

    public function destroy(Legislation $legislation)
    {
        $details = array_filter($this->snapshot($legislation), fn($v) => $v !== '—');
        DB::transaction(function () use ($legislation) {
            // Measures this one repealed are back in force once it's gone.
            $this->syncLinks($legislation, [], true);
            $legislation->delete();
        });
        $this->log('deleted', $legislation, "Deleted {$legislation->reference()} — {$legislation->title}", ['details' => $details]);

        return redirect()->route('legislation.index')->with('success', "{$legislation->reference()} deleted.");
    }

    /** Move to the next status on the path (or veto). Writes the timeline and dates. */
    public function advance(Request $request, Legislation $legislation)
    {
        $allowed = array_filter([$legislation->nextStatus(), $legislation->canBeVetoed() ? 'vetoed' : null]);
        $request->validate([
            'to'      => ['required', Rule::in($allowed)],
            'date'    => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'votes_yes'     => ['nullable', 'integer', 'min:0', 'max:99'],
            'votes_no'      => ['nullable', 'integer', 'min:0', 'max:99'],
            'votes_abstain' => ['nullable', 'integer', 'min:0', 'max:99'],
        ], ['to.in' => 'That step is not available for this measure any more. Reload the page.']);

        $to     = $request->to;
        $before = $this->snapshot($legislation);
        DB::transaction(function () use ($request, $legislation, $to) {
            $updates = ['status' => $to, 'updated_by' => auth()->id()];
            if ($to === 'approved')  $updates['date_approved']  = $request->date;
            if ($to === 'effective') $updates['date_effective'] = $request->date;
            foreach (['votes_yes', 'votes_no', 'votes_abstain'] as $v) {
                if ($request->filled($v)) $updates[$v] = $request->integer($v);
            }
            $legislation->update($updates);
            $this->event($legislation, $to, $request->date, $request->remarks);
            if ($legislation->hasPassed()) {
                $this->syncLinks($legislation, $legislation->repeals()->pluck('id')->all());
            }
        });

        $label   = $legislation->labelFor($to);
        $changed = $this->diff($before, $this->snapshot($legislation->fresh()));
        if ($request->filled('remarks')) {
            $changed['Remarks'] = ['from' => '—', 'to' => $request->remarks];
        }
        $repealed = $legislation->hasPassed() ? $legislation->repeals()->get()->map->reference()->all() : [];
        $this->log('updated', $legislation,
            "{$legislation->reference()}: marked {$label} on " . \Carbon\Carbon::parse($request->date)->format('M d, Y')
                . ($repealed ? ' — repeals ' . implode(', ', $repealed) : ''),
            ['changed' => $changed]);

        return back()->with('success', "{$legislation->reference()} marked {$label}.");
    }

    /** Add a posting date or a note to the timeline. */
    public function addEvent(Request $request, Legislation $legislation)
    {
        $request->validate([
            'event'   => ['required', Rule::in(LegislationEvent::MANUAL)],
            'date'    => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000', 'required_if:event,note'],
        ], ['remarks.required_if' => 'Write the note.']);

        $entry = $this->event($legislation, $request->event, $request->date, $request->remarks);
        $this->log('updated', $legislation, "{$legislation->reference()}: added timeline entry — {$entry->meta()[0]}", [
            'details' => array_filter([
                'Date'    => \Carbon\Carbon::parse($request->date)->format('M d, Y'),
                'Remarks' => $request->remarks,
            ]),
        ]);

        return back()->with('success', 'Added to the timeline.');
    }

    public function destroyEvent(Legislation $legislation, LegislationEvent $event)
    {
        abort_unless($event->legislation_id === $legislation->id && in_array($event->event, LegislationEvent::MANUAL, true), 404);
        $event->delete();
        $this->log('updated', $legislation, "{$legislation->reference()}: removed timeline entry — {$event->meta()[0]}", [
            'details' => array_filter([
                'Date'    => $event->event_date?->format('M d, Y'),
                'Remarks' => $event->remarks,
            ]),
        ]);
        return back()->with('success', 'Timeline entry removed.');
    }

    /** Stream the signed copy (inline, so the browser's PDF viewer shows it). */
    public function file(Request $request, Legislation $legislation)
    {
        $path = $legislation->file_path;
        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404);
        $name = str_replace(['/', '\\'], '-', $legislation->reference()) . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return $request->boolean('download')
            ? Storage::disk(self::DISK)->download($path, $name)
            : response()->file(Storage::disk(self::DISK)->path($path), ['Content-Disposition' => 'inline; filename="' . $name . '"']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function formData(Legislation $legislation): array
    {
        $others = Legislation::whereKeyNot($legislation->id ?? 0)
            ->orderByDesc('series_year')->orderByDesc('number')
            ->get(['id', 'type', 'number', 'title', 'status', 'repealed_by_id']);

        return [
            'legislation' => $legislation,
            'officials'   => Official::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'position']),
            'others'      => $others,
            'repealIds'   => $legislation->exists ? $legislation->repeals->pluck('id')->all() : [],
            'nextNumbers' => [
                'ordinance'  => Legislation::nextNumber('ordinance', (int) now()->year),
                'resolution' => Legislation::nextNumber('resolution', (int) now()->year),
            ],
        ];
    }

    private function validated(Request $request, ?Legislation $legislation = null): array
    {
        $id = $legislation?->id;
        $request->validate([
            'type'            => ['required', Rule::in(array_keys(Legislation::TYPES))],
            'number'          => ['required', 'string', 'max:50',
                                  Rule::unique('eb_legislations', 'number')->where('type', $request->type)->ignore($id)],
            'series_year'     => ['required', 'integer', 'min:1950', 'max:2100'],
            'title'           => ['required', 'string', 'max:500'],
            'summary'         => ['nullable', 'string', 'max:5000'],
            'category'        => ['nullable', 'string', 'max:100', 'not_in:__new'],
            'status'          => ['nullable', Rule::in(array_keys(Legislation::STATUSES))],
            'date_introduced' => ['nullable', 'date'],
            'date_approved'   => ['nullable', 'date'],
            'date_effective'  => ['nullable', 'date', 'after_or_equal:date_approved'],
            'votes_yes'       => ['nullable', 'integer', 'min:0', 'max:99'],
            'votes_no'        => ['nullable', 'integer', 'min:0', 'max:99'],
            'votes_abstain'   => ['nullable', 'integer', 'min:0', 'max:99'],
            'amends_id'       => ['nullable', 'integer', 'exists:eb_legislations,id', Rule::notIn(array_filter([$id]))],
            'repeals'         => ['nullable', 'array'],
            'repeals.*'       => ['integer', 'exists:eb_legislations,id', Rule::notIn(array_filter([$id]))],
            'authors'         => ['nullable', 'array'],
            'authors.*.name'  => ['nullable', 'string', 'max:255'],
            'authors.*.role'  => ['nullable', Rule::in(array_keys(Legislation::AUTHOR_ROLES))],
            'file'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ], [
            'number.unique'                 => 'This number is already used by another ' . ($request->type === 'resolution' ? 'resolution' : 'ordinance') . '.',
            'date_effective.after_or_equal' => 'The effectivity date cannot be before the approval date.',
            'amends_id.not_in'              => 'A measure cannot amend itself.',
            'repeals.*.not_in'              => 'A measure cannot repeal itself.',
            'file.max'                      => 'The file may not be larger than 20 MB.',
        ]);

        return $request->only([
            'type', 'number', 'series_year', 'title', 'summary', 'category',
            'date_introduced', 'date_approved', 'date_effective',
            'votes_yes', 'votes_no', 'votes_abstain', 'amends_id',
        ]);
    }

    /** Without legislation.approve: new records start as Draft; edits keep the status. */
    private function statusFor(Request $request, ?Legislation $legislation): string
    {
        if ($request->user()->can('legislation.approve') && $request->filled('status')) {
            return $request->status;
        }
        return $legislation?->status ?? 'draft';
    }

    private function syncAuthors(Legislation $legislation, array $rows): void
    {
        $officials = Official::pluck('id', 'name')->mapWithKeys(fn($id, $n) => [mb_strtolower(trim($n)) => $id]);
        $legislation->authors()->delete();
        $seen = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $role = $row['role'] ?? 'author';
            if ($name === '' || isset($seen[mb_strtolower($name) . $role])) continue;
            $seen[mb_strtolower($name) . $role] = true;
            $legislation->authors()->create([
                'name'        => $name,
                'role'        => isset(Legislation::AUTHOR_ROLES[$role]) ? $role : 'author',
                'official_id' => $officials[mb_strtolower($name)] ?? null,
            ]);
        }
    }

    /**
     * Keep "repeals" and "amends" in step.
     * - Measures listed in $repealIds point to this one (repealed_by_id). Once this
     *   one has passed they become Repealed; unlinked ones go back to their earlier status.
     * - The amended measure gets an "Amended" timeline entry once this one passes.
     */
    private function syncLinks(Legislation $legislation, array $repealIds, bool $removing = false): void
    {
        $repealIds = array_map('intval', $repealIds);
        $passed = !$removing && $legislation->hasPassed();

        // Unlinked: release them.
        Legislation::where('repealed_by_id', $legislation->id)->whereNotIn('id', $repealIds ?: [0])->get()
            ->each(function (Legislation $old) use ($legislation) {
                $restore = $old->status === 'repealed' ? ($old->date_effective ? 'effective' : 'approved') : $old->status;
                $old->update(['repealed_by_id' => null, 'status' => $restore]);
                if ($restore !== 'repealed' && $old->wasChanged('status')) {
                    $this->event($old, 'note', now(), "No longer repealed by {$legislation->reference()}");
                }
            });

        foreach (Legislation::whereIn('id', $repealIds)->get() as $old) {
            $old->repealed_by_id = $legislation->id;
            if ($passed && $old->status !== 'repealed') {
                $old->status = 'repealed';
                $old->save();
                $this->event($old, 'repealed', $legislation->date_effective ?? $legislation->date_approved ?? now(), "Repealed by {$legislation->reference()}");
            } else {
                $old->save();
            }
        }

        if ($passed && $legislation->amends_id) {
            $target = Legislation::find($legislation->amends_id);
            $note = "Amended by {$legislation->reference()}";
            if ($target && !$target->events()->where('event', 'amended')->where('remarks', $note)->exists()) {
                $this->event($target, 'amended', $legislation->date_effective ?? $legislation->date_approved ?? now(), $note);
            }
        }
    }

    // ── Activity log ───────────────────────────────────────────────────────

    /** Activity-log type for ordinances and resolutions (Activity Logs → Module filter). */
    const LOG_TYPE = 'legislation';

    private function log(string $action, Legislation $legislation, string $description, array $props = []): void
    {
        ActivityLog::record($action, self::LOG_TYPE, $legislation->id, $description,
            ['reference' => $legislation->reference()] + $props);
    }

    /** The measure as readable "Field => value" pairs, to compare before and after a change. */
    private function snapshot(Legislation $l): array
    {
        $l->loadMissing(['authors', 'repeals', 'amends']);
        $date  = fn($d) => $d ? $d->format('M d, Y') : '—';
        $votes = collect(['Yes' => $l->votes_yes, 'No' => $l->votes_no, 'Abstain' => $l->votes_abstain])
            ->filter(fn($v) => $v !== null)->map(fn($v, $k) => "{$k} {$v}")->implode(', ');

        return [
            'Type'        => $l->typeLabel(),
            'Number'      => (string) $l->number,
            'Series'      => (string) $l->series_year,
            'Title'       => (string) $l->title,
            'Summary'     => \Illuminate\Support\Str::limit((string) $l->summary, 120) ?: '—',
            'Category'    => $l->category ?: '—',
            'Status'      => $l->statusLabel(),
            'Introduced'  => $date($l->date_introduced),
            'Approved'    => $date($l->date_approved),
            'In force'    => $date($l->date_effective),
            'Votes'       => $votes ?: '—',
            'Authors'     => $l->authors->map(fn($a) => $a->name . ($a->role !== 'author' ? ' (' . (Legislation::AUTHOR_ROLES[$a->role] ?? $a->role) . ')' : ''))->implode(', ') ?: '—',
            'Amends'      => $l->amends?->reference() ?? '—',
            'Repeals'     => $l->repeals->map->reference()->implode(', ') ?: '—',
        ];
    }

    /** Fields that differ, as ['Field' => ['from' => …, 'to' => …]] (the Activity Logs page shows these). */
    private function diff(array $before, array $after): array
    {
        $out = [];
        foreach ($after as $field => $to) {
            $from = $before[$field] ?? '—';
            if ($from !== $to) $out[$field] = ['from' => $from, 'to' => $to];
        }
        return $out;
    }

    private function event(Legislation $legislation, string $event, $date, ?string $remarks = null): LegislationEvent
    {
        return $legislation->events()->create([
            'event'      => $event,
            'event_date' => \Carbon\Carbon::parse($date)->toDateString(),
            'remarks'    => $remarks,
            'user_id'    => auth()->id(),
        ]);
    }
}
