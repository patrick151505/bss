<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventWinner;
use App\Models\Citizen;
use App\Models\Address;
use App\Models\ActivityLog;

class EventController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    // "Who can attend" — limit an event to residents of selected address zones.
    private const AREA_RULES = [
        'area_mode'           => 'nullable|in:all,selected',
        'allowed_addresses'   => 'required_if:area_mode,selected|array',
        'allowed_addresses.*' => 'integer|exists:eb_address,id',
        // Extra rules: age, gender, groups and tags.
        'min_age'             => 'nullable|integer|min:0|max:150',
        'max_age'             => 'nullable|integer|min:0|max:150|gte:min_age',
        'eligible_gender'     => 'nullable|in:0,1',
        'eligible_groups'     => 'nullable|array',
        'eligible_groups.*'   => 'in:senior,pwd,solo,voter',
        'groups_match'        => 'nullable|in:any,all',
        'eligible_tags'       => 'nullable|array',
        'eligible_tags.*'     => 'integer|exists:eb_tags,id',
    ];
    private const AREA_MESSAGES = [
        'allowed_addresses.required_if' => 'Pick at least one area, or choose All areas.',
        'max_age.gte'                   => 'The maximum age must be the same as or above the minimum age.',
    ];

    /** Eligibility rules from the form, or null when none are set. */
    private function eligibilityFrom(Request $request): ?array
    {
        $rules = [
            'min_age'      => $request->filled('min_age') ? (int) $request->min_age : null,
            'max_age'      => $request->filled('max_age') ? (int) $request->max_age : null,
            'gender'       => $request->filled('eligible_gender') ? (int) $request->eligible_gender : null,
            'groups'       => array_values(array_unique($request->input('eligible_groups', []))),
            'groups_match' => $request->input('groups_match') === 'all' ? 'all' : 'any',
            'tags'         => collect($request->input('eligible_tags', []))->map(fn($v) => (int) $v)->filter()->unique()->sort()->values()->all(),
        ];
        $any = $rules['min_age'] !== null || $rules['max_age'] !== null || $rules['gender'] !== null || $rules['groups'] || $rules['tags'];
        return $any ? $rules : null;
    }

    private function allowedAddressesFrom(Request $request): ?array
    {
        if ($request->input('area_mode') !== 'selected') return null;
        $ids = collect($request->input('allowed_addresses', []))
            ->map(fn($v) => (int) $v)->filter()->unique()->sort()->values()->all();
        return $ids ?: null;
    }

    /** Error response when a citizen may not attend (outside the allowed areas or fails a rule), or null if allowed. */
    private function areaRefusal(Event $event, Citizen $citizen): ?\Illuminate\Http\JsonResponse
    {
        if (!$event->allowsAddress($citizen->address)) {
            $zone = $citizen->addressZone?->description ?? 'no recorded address';
            return response()->json([
                'success' => false,
                'message' => "{$citizen->full_name} ({$zone}) can't join — this event is only for "
                    . $event->allowedAddresses()->pluck('description')->implode(', ') . '.',
            ]);
        }
        if ($why = $event->eligibilityFailure($citizen)) {
            return response()->json([
                'success' => false,
                'message' => "{$citizen->full_name} can't join — {$why}.",
            ]);
        }
        return null;
    }

    public function index()
    {
        return view('events.index');
    }

    // ─── KPI ─────────────────────────────────────────────────────────────────

    public function kpi()
    {
        $total      = Event::count();
        $open       = Event::where('status', 'open')->count();
        $closed     = Event::where('status', 'closed')->count();
        $attendance = EventAttendance::count();

        return response()->json([
            'success'    => true,
            'total'      => $total,
            'open'       => $open,
            'closed'     => $closed,
            'attendance' => $attendance,
        ]);
    }

    // ─── Paginated list (AJAX) ────────────────────────────────────────────────

    public function retrieve(Request $request)
    {
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = (int) $request->input('limit', 9);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Event::withCount('attendance')
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                ->orWhere('category', 'like', "%$search%")
                ->orWhere('venue', 'like', "%$search%"))
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('event_date')
            ->orderByDesc('date_created');

        $total  = $query->count();
        $events = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $data = $events->map(fn(Event $e) => [
            'id'                  => $e->id,
            'title'               => $e->title,
            'category'            => $e->category,
            'venue'               => $e->venue,
            'event_date'          => $e->event_date?->format('M d, Y'),
            'event_start'         => $e->event_start ? Carbon::parse($e->event_start)->format('g:i A') : null,
            'event_end'           => $e->event_end   ? Carbon::parse($e->event_end)->format('g:i A')   : null,
            'status'              => $e->effective_status,
            'raffle_enabled'      => (bool) $e->raffle_enabled,
            'cover_photo_url'     => $e->cover_photo_url,
            'attendance_count'    => $e->attendance_count,
        ]);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    // ─── Create form ──────────────────────────────────────────────────────────

    public function create()
    {
        $addresses = Address::where('is_active', true)->orderBy('description')->get();
        $tags      = \App\Models\Tag::orderBy('name')->get(['id', 'name', 'color']);
        return view('events.create', compact('addresses', 'tags'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:200',
            'event_date' => 'required|date',
        ] + self::AREA_RULES, self::AREA_MESSAGES);

        $data = [
            'title'               => $request->title,
            'description'         => $request->description,
            'category'            => $request->category,
            'venue'               => $request->venue,
            'event_date'          => $request->event_date,
            'event_start'         => $request->event_start ?: null,
            'event_end'           => $request->event_end   ?: null,
            'raffle_enabled'      => $request->boolean('raffle_enabled') ? 1 : 0,
            'allow_winner_repeat' => $request->boolean('allow_winner_repeat') ? 1 : 0,
            'allow_kiosk'         => $request->boolean('allow_kiosk'),
            'allowed_addresses'   => $this->allowedAddressesFrom($request),
            'eligibility'         => $this->eligibilityFrom($request),
            'status'              => 'open',
            'user_id'             => auth()->id(),
            'date_created'        => now(),
            'date_updated'        => now(),
        ];

        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $request->file('cover_photo')->store('public/events');
        }

        $event = Event::create($data);

        return redirect()->route('events.show', $event)->with('success', 'Event created successfully.');
    }

    // ─── Show (detail page) ───────────────────────────────────────────────────

    public function show(Event $event)
    {
        $addresses = Address::where('is_active', true)
            ->when($event->restrictsAddresses(), fn($q) => $q->whereIn('id', $event->allowedAddressIds()))
            ->orderBy('description')->get();
        return view('events.show', compact('event', 'addresses'));
    }

    // ─── Raffle draw page (logged-in staff, one screen) ────────────────────────

    public function rafflePage(Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);
        return view('events.raffle', compact('event'));
    }

    // ─── Raffle: record several winners from one draw ─────────────────────────
    // All-or-nothing: every citizen must be an attendee (and not a past winner
    // unless repeats are allowed), then they get consecutive rounds in one transaction.

    public function recordWinners(Request $request, Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);

        $request->validate([
            'citizenIds'   => 'required|array|min:1|max:50',
            'citizenIds.*' => 'integer',
            'prize_label'  => 'nullable|string|max:150',
        ]);

        $ids   = collect($request->input('citizenIds'))->map(fn($id) => (int) $id)->unique()->values();
        $prize = trim((string) $request->input('prize_label', '')) ?: null;

        $attending = EventAttendance::where('eventId', $event->id)->whereIn('citizenId', $ids)->pluck('citizenId');
        if ($attending->count() !== $ids->count()) {
            return response()->json(['success' => false, 'message' => 'Some drawn people are not on this event\'s attendance list.']);
        }

        if (!$event->allow_winner_repeat) {
            $already = EventWinner::where('eventId', $event->id)->whereIn('citizenId', $ids)->pluck('citizenId');
            if ($already->isNotEmpty()) {
                $names = Citizen::whereIn('id', $already)->get()->pluck('full_name')->implode(', ');
                return response()->json(['success' => false, 'message' => "Already won in this event: {$names}."]);
            }
        }

        $rounds = DB::transaction(function () use ($event, $ids, $prize) {
            // Lock this event's winner rows so two staff drawing at once can't get the same round numbers.
            $round  = (int) EventWinner::where('eventId', $event->id)->lockForUpdate()->max('round');
            $rounds = [];
            foreach ($ids as $citizenId) {
                $rounds[$citizenId] = ++$round;
                EventWinner::create([
                    'eventId'     => $event->id,
                    'citizenId'   => $citizenId,
                    'round'       => $round,
                    'prize_label' => $prize,
                    'drawn_at'    => now(),
                ]);
            }
            return $rounds;
        });

        ActivityLog::record('raffle_batch_draw', 'event', $event->id,
            'Raffle draw: ' . $ids->count() . ' winners drawn at once' . ($prize ? " (prize: {$prize})" : ''),
            ['citizen_ids' => $ids->all(), 'rounds' => array_values($rounds), 'prize_label' => $prize]
        );

        return response()->json(['success' => true, 'rounds' => $rounds]);
    }

    // ─── Raffle: undo a confirmed winner ──────────────────────────────────────
    // For a mistaken Confirm. Refused once the prize is claimed; the deleted row
    // is kept in the activity log together with the reason.

    public function undoWinner(Request $request, Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);

        $request->validate([
            'round'  => 'required|integer',
            'reason' => 'required|string|min:3|max:200',
        ]);

        $winner = EventWinner::with('citizen')->where('eventId', $event->id)->where('round', (int) $request->round)->first();
        if (!$winner) {
            return response()->json(['success' => false, 'message' => 'That winner no longer exists.']);
        }
        if ($winner->claimed_at) {
            return response()->json(['success' => false, 'message' => 'This prize was already claimed — it can no longer be undone here.']);
        }

        $name   = $winner->citizen?->full_name ?? 'unknown citizen';
        $reason = trim($request->reason);
        $winner->delete();

        ActivityLog::record('raffle_winner_undone', 'event', $event->id,
            "Raffle: undid winner {$name} (round #{$winner->round}" . ($winner->prize_label ? ", prize: {$winner->prize_label}" : '') . ") — {$reason}",
            [
                'citizen_id'  => $winner->citizenId,
                'round'       => $winner->round,
                'prize_label' => $winner->prize_label,
                'drawn_at'    => $winner->drawn_at?->toDateTimeString(),
                'reason'      => $reason,
                'kept_out'    => $request->boolean('keep_out'),
            ]
        );

        return response()->json(['success' => true, 'name' => $name]);
    }

    // ─── Raffle: drawn person not present ─────────────────────────────────────
    // Nothing is recorded as a win; the skip is logged so a re-draw is traceable.

    public function noShow(Request $request, Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);

        $citizen = Citizen::find((int) $request->input('citizenId'));
        $prize   = trim((string) $request->input('prize_label', ''));

        ActivityLog::record('raffle_no_show', 'event', $event->id,
            'Raffle draw: ' . ($citizen?->full_name ?? 'unknown citizen') . ' was not present — drew again'
                . ($prize !== '' ? " (prize: {$prize})" : ''),
            ['citizen_id' => $citizen?->id, 'prize_label' => $prize ?: null]
        );

        return response()->json(['success' => true]);
    }

    // ─── Edit form ────────────────────────────────────────────────────────────

    public function edit(Event $event)
    {
        $addresses = Address::where('is_active', true)->orderBy('description')->get();
        $tags      = \App\Models\Tag::orderBy('name')->get(['id', 'name', 'color']);
        return view('events.edit', compact('event', 'addresses', 'tags'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title'      => 'required|string|max:200',
            'event_date' => 'required|date',
        ] + self::AREA_RULES, self::AREA_MESSAGES);

        $data = [
            'title'               => $request->title,
            'description'         => $request->description,
            'category'            => $request->category,
            'venue'               => $request->venue,
            'event_date'          => $request->event_date,
            'event_start'         => $request->event_start ?: null,
            'event_end'           => $request->event_end   ?: null,
            'raffle_enabled'      => $request->boolean('raffle_enabled') ? 1 : 0,
            'allow_winner_repeat' => $request->boolean('allow_winner_repeat') ? 1 : 0,
            'allow_kiosk'         => $request->boolean('allow_kiosk'),
            'allowed_addresses'   => $this->allowedAddressesFrom($request),
            'eligibility'         => $this->eligibilityFrom($request),
            'date_updated'        => now(),
        ];

        if ($request->hasFile('cover_photo')) {
            if ($event->cover_photo) Storage::delete($event->cover_photo);
            $data['cover_photo'] = $request->file('cover_photo')->store('public/events');
        } elseif ($request->boolean('remove_cover') && $event->cover_photo) {
            Storage::delete($event->cover_photo);
            $data['cover_photo'] = null;
        }

        $event->update($data);

        return redirect()->route('events.show', $event)->with('success', 'Event updated.');
    }

    // ─── Toggle open/closed ───────────────────────────────────────────────────

    // Close or reopen, based on what staff see (the effective status, which
    // includes auto-close). Reopening after the scheduled end keeps the event open
    // until it is closed again.
    public function toggleStatus(Event $event)
    {
        $reopen = !$event->is_open;

        $event->update([
            'status'       => $reopen ? 'open' : 'closed',
            'reopened_at'  => $reopen && $event->isPastSchedule() ? now() : null,
            'date_updated' => now(),
        ]);

        ActivityLog::record($reopen ? 'reopened' : 'closed', 'event', $event->id,
            ($reopen ? 'Reopened' : 'Closed') . " event: {$event->title}");

        return response()->json(['success' => true, 'status' => $event->effective_status]);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(Event $event)
    {
        if ($event->cover_photo) Storage::delete($event->cover_photo);
        EventAttendance::where('eventId', $event->id)->delete();
        EventWinner::where('eventId', $event->id)->delete();
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }

    // ─── Attendance: AJAX list ────────────────────────────────────────────────

    public function attendanceList(Request $request, Event $event)
    {
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = 10;
        $search = $request->input('search');

        $query = EventAttendance::with('citizen.addressZone')
            ->where('eventId', $event->id)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('citizen', fn($c) => $c->searchName($search));
            })
            ->orderByDesc('time_in');

        $total   = $query->count();
        $records = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $data = $records->map(fn(EventAttendance $a) => [
            'id'       => $a->id,
            'citizen'  => $a->citizen?->full_name,
            'photo'    => $a->citizen?->profile
                ? asset(str_replace('public/', 'storage/', $a->citizen->profile))
                : null,
            'zone'     => $a->citizen?->addressZone?->description,
            'age'      => $a->citizen?->age,
            'gender'   => $a->citizen?->gender === 1 ? 'Male' : 'Female',
            'time_in'  => $a->time_in->format('M d, Y g:i A'),
            'method'   => $a->method,
        ]);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    // ─── Attendance: check-in by QR ──────────────────────────────────────────

    public function checkinQr(Request $request, Event $event)
    {
        $qrcode = trim($request->input('qrcode'));
        $citizen = Citizen::where('qrcode', $qrcode)->where('is_active', 1)->first();

        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'QR code not recognised.']);
        }

        return $this->recordAttendance($event, $citizen->id, 'qr');
    }

    // ─── Attendance: check-in manual (by citizenId) ───────────────────────────

    public function checkinManual(Request $request, Event $event)
    {
        return $this->recordAttendance($event, (int) $request->citizenId, 'manual');
    }

    // ─── Attendance: bulk add ─────────────────────────────────────────────────

    public function bulkPreview(Request $request, Event $event)
    {
        $citizens = $this->buildBulkQuery($request, $event)->get(['id', 'fname', 'mname', 'lname', 'bday', 'gender', 'address']);

        $alreadyIn = EventAttendance::where('eventId', $event->id)
            ->pluck('citizenId')->toArray();

        $data = $citizens->map(fn(Citizen $c) => [
            'id'           => $c->id,
            'name'         => $c->full_name,
            'age'          => $c->age,
            'gender'       => $c->gender === 1 ? 'Male' : 'Female',
            'already_in'   => in_array($c->id, $alreadyIn),
        ]);

        return response()->json([
            'success' => true,
            'total'   => $data->count(),
            'new'     => $data->where('already_in', false)->count(),
            'data'    => $data->values(),
        ]);
    }

    public function bulkAdd(Request $request, Event $event)
    {
        $citizens = $this->buildBulkQuery($request, $event)->pluck('id');

        $alreadyIn = EventAttendance::where('eventId', $event->id)
            ->pluck('citizenId')->toArray();

        $toInsert = $citizens->filter(fn($id) => !in_array($id, $alreadyIn));

        $now = now();
        $rows = $toInsert->map(fn($id) => [
            'eventId'   => $event->id,
            'citizenId' => $id,
            'time_in'   => $now,
            'method'    => 'bulk',
        ])->values()->toArray();

        if (count($rows)) {
            DB::table('eb_event_attendance')->insert($rows);
        }

        return response()->json([
            'success' => true,
            'added'   => count($rows),
            'skipped' => count($alreadyIn),
        ]);
    }

    // ─── Attendance: remove ───────────────────────────────────────────────────

    public function removeAttendance(Request $request, Event $event)
    {
        EventAttendance::where('eventId', $event->id)
            ->where('id', $request->attendanceId)
            ->delete();

        return response()->json(['success' => true]);
    }

    // ─── Citizen search (for manual check-in picker) ─────────────────────────

    public function searchCitizens(Request $request, Event $event)
    {
        $search = $request->input('search');
        $limit  = 8;
        $page   = max(1, (int) $request->input('page', 1));

        $alreadyIn = EventAttendance::where('eventId', $event->id)->pluck('citizenId');

        $query = Citizen::with('addressZone')
            ->where('is_active', 1)
            ->notRejected()
            ->when($search, fn($q) => $q->searchName($search))
            ->tap(fn($q) => $event->applyEligibility($q))
            ->orderBy('lname')->orderBy('fname');

        $total    = $query->count();
        $citizens = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $data = $citizens->map(fn(Citizen $c) => [
            'id'         => $c->id,
            'name'       => $c->full_name,
            'age'        => $c->age,
            'gender'     => $c->gender === 1 ? 'Male' : 'Female',
            'zone'       => $c->addressZone?->description,
            'photo'      => $c->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null,
            'already_in' => $alreadyIn->contains($c->id),
        ]);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    // ─── Raffle: get eligible attendees ──────────────────────────────────────

    public function rafflePool(Event $event)
    {
        $wonIds = $event->allow_winner_repeat
            ? collect()
            : EventWinner::where('eventId', $event->id)->pluck('citizenId');

        $pool = EventAttendance::with(['citizen.addressZone', 'citizen.tags:id'])
            ->where('eventId', $event->id)
            ->whereNotIn('citizenId', $wonIds)
            ->get()
            // Attendees checked in before the event was limited (areas or rules) stay on
            // the attendance list but can't win.
            ->filter(fn(EventAttendance $a) => $a->citizen
                && $event->allowsAddress($a->citizen->address) && !$event->eligibilityFailure($a->citizen))
            ->values()
            ->map(fn(EventAttendance $a) => [
                'citizenId' => $a->citizenId,
                // Shown under the name on the draw cards (e.g. EBT-000472).
                'code'      => \App\Models\Setting::instance()->formatCitizenId($a->citizenId),
                'name'      => $a->citizen?->full_name ?? '—',
                // Lets the draw screen filter the pool by area (e.g. one subdivision only).
                'addressId' => $a->citizen?->address ? (int) $a->citizen->address : null,
                'address'   => $a->citizen?->addressZone?->description,
                'photo'     => $a->citizen?->profile
                    ? asset(str_replace('public/', 'storage/', $a->citizen->profile))
                    : null,
            ]);

        // The event's allowed areas (null = everyone) — the raffle's area filter lists exactly these.
        $areas = $event->restrictsAddresses()
            ? $event->allowedAddresses()->map(fn($a) => ['id' => $a->id, 'label' => $a->description])->values()
            : null;

        return response()->json(['success' => true, 'pool' => $pool, 'areas' => $areas]);
    }

    // ─── Raffle: record winner ────────────────────────────────────────────────

    public function recordWinner(Request $request, Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);

        $citizenId  = (int) $request->citizenId;
        $prizeLabel = $request->input('prize_label');

        if (!EventAttendance::where('eventId', $event->id)->where('citizenId', $citizenId)->exists()) {
            return response()->json(['success' => false, 'message' => 'This person is not on this event\'s attendance list.']);
        }

        // Guard: if repeat not allowed, ensure not already a winner
        if (!$event->allow_winner_repeat) {
            $alreadyWon = EventWinner::where('eventId', $event->id)
                ->where('citizenId', $citizenId)->exists();
            if ($alreadyWon) {
                return response()->json(['success' => false, 'message' => 'This citizen already won in this event.']);
            }
        }

        $round = EventWinner::where('eventId', $event->id)->max('round') + 1;

        $winner = EventWinner::create([
            'eventId'     => $event->id,
            'citizenId'   => $citizenId,
            'round'       => $round,
            'prize_label' => $prizeLabel ?: null,
            'drawn_at'    => now(),
        ]);

        $citizen = Citizen::find($citizenId);

        return response()->json([
            'success'     => true,
            'round'       => $round,
            'winner_id'   => $winner->id,
            'citizen_id'  => $citizenId,
            'name'        => $citizen?->full_name,
            'photo'       => $citizen?->profile
                ? asset(str_replace('public/', 'storage/', $citizen->profile))
                : null,
        ]);
    }

    // ─── Raffle: winner history ───────────────────────────────────────────────

    public function winnerHistory(Event $event)
    {
        $winners = EventWinner::with('citizen')
            ->where('eventId', $event->id)
            ->orderBy('round')
            ->get()
            ->map(fn(EventWinner $w) => [
                'round'       => $w->round,
                'citizenId'   => $w->citizenId,
                'name'        => $w->citizen?->full_name ?? '—',
                'prize_label' => $w->prize_label,
                'drawn_at'    => $w->drawn_at->format('g:i A'),
                'claimed'     => (bool) $w->claimed_at,   // claimed wins can't be undone from the draw screen
                'photo'       => $w->citizen?->profile
                    ? asset(str_replace('public/', 'storage/', $w->citizen->profile))
                    : null,
            ]);

        return response()->json(['success' => true, 'winners' => $winners]);
    }

    // ─── Private: record attendance ───────────────────────────────────────────

    private function recordAttendance(Event $event, int $citizenId, string $method): \Illuminate\Http\JsonResponse
    {
        if (!$event->is_open) {
            return response()->json(['success' => false, 'message' => 'This event is closed.']);
        }

        $citizen = Citizen::find($citizenId);
        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'Citizen not found.']);
        }
        if ($refusal = $this->areaRefusal($event, $citizen)) return $refusal;

        $exists = EventAttendance::where('eventId', $event->id)
            ->where('citizenId', $citizenId)->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'already' => true,
                'message' => $citizen->full_name . ' is already checked in.',
            ]);
        }

        EventAttendance::create([
            'eventId'   => $event->id,
            'citizenId' => $citizenId,
            'time_in'   => now(),
            'method'    => $method,
        ]);

        return response()->json([
            'success' => true,
            'name'    => $citizen->full_name,
            'photo'   => $citizen->profile
                ? asset(str_replace('public/', 'storage/', $citizen->profile))
                : null,
        ]);
    }

    // ─── Private: build bulk filter query ────────────────────────────────────

    private function buildBulkQuery(Request $request, Event $event)
    {
        $query = Citizen::where('is_active', 1)->notRejected();

        // The event's own areas and rules always apply; the wizard's filters narrow further.
        $event->applyEligibility($query);

        if ($request->filled('addressId')) {
            $query->where('address', $request->addressId);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('min_age')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= ?', [$request->min_age]);
        }
        if ($request->filled('max_age')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) <= ?', [$request->max_age]);
        }
        if ($request->boolean('voters')) {
            $query->where('voters', 1);
        }
        if ($request->boolean('birthday_month')) {
            $query->whereRaw('MONTH(bday) = ?', [now()->month]);
        }
        if ($request->boolean('senior')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60');
        }
        if ($request->boolean('pwd')) {
            $query->where('is_pwd', 1);
        }
        if ($request->boolean('soloparents')) {
            $query->where('is_soloparents', 1);
        }

        return $query;
    }
}
