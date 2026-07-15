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

class EventController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

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
        return view('events.create');
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:200',
            'event_date' => 'required|date',
        ]);

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
        $addresses = Address::where('is_active', true)->orderBy('description')->get();
        return view('events.show', compact('event', 'addresses'));
    }

    // ─── Raffle page (organizer) ──────────────────────────────────────────────

    public function rafflePage(Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);
        return view('events.raffle', compact('event'));
    }

    // ─── Raffle: generate / refresh PIN ──────────────────────────────────────

    public function generatePin(Event $event)
    {
        abort_if(!$event->raffle_enabled, 404);

        $pin = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));

        $event->update([
            'raffle_pin'            => $pin,
            'raffle_pin_expires_at' => now()->addHours(8),
            'date_updated'          => now(),
        ]);

        return response()->json([
            'success'    => true,
            'pin'        => $pin,
            'expires_at' => $event->raffle_pin_expires_at->format('M d, Y g:i A'),
            'public_url' => route('events.raffle.public', ['event' => $event->id, 'pin' => $pin]),
        ]);
    }

    // ─── Public raffle audience page (no auth) ────────────────────────────────

    public function publicRafflePage(Request $request, Event $event)
    {
        if (!$event->raffle_enabled) {
            return view('events.raffle-inactive', ['reason' => 'no_raffle', 'event' => $event]);
        }
        if (!$event->hasValidPin()) {
            return view('events.raffle-inactive', ['reason' => 'expired', 'event' => $event]);
        }
        if ($request->input('pin') !== $event->raffle_pin) {
            return view('events.raffle-inactive', ['reason' => 'invalid_pin', 'event' => $event]);
        }

        return view('events.raffle-public', compact('event'));
    }

    // ─── Public raffle: QR check-in (no auth, pin-protected) ────────────────

    public function publicCheckinQr(Request $request, Event $event)
    {
        if (!$event->raffle_enabled || !$event->hasValidPin() || $request->input('pin') !== $event->raffle_pin) {
            return response()->json(['success' => false, 'message' => 'Session expired or invalid.']);
        }

        $qrcode  = trim($request->input('qrcode'));
        $citizen = Citizen::with('addressZone')->where('qrcode', $qrcode)->where('is_active', 1)->first();

        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'QR code not recognised.']);
        }

        $already = EventAttendance::where('eventId', $event->id)
            ->where('citizenId', $citizen->id)->exists();

        if (!$already) {
            EventAttendance::create([
                'eventId'   => $event->id,
                'citizenId' => $citizen->id,
                'time_in'   => now(),
                'method'    => 'qr',
            ]);
        }

        return response()->json([
            'success'    => true,
            'already'    => $already,
            'name'       => $citizen->full_name,
            'photo'      => $citizen->profile
                ? asset(str_replace('public/', 'storage/', $citizen->profile))
                : null,
            'address'    => $citizen->addressZone?->description ?? '—',
            'age'        => $citizen->age,
            'gender'     => $citizen->gender === 1 ? 'Male' : 'Female',
            'time_in'    => now()->format('M d, Y g:i A'),
        ]);
    }

    // ─── Public raffle: record winner (no auth, pin-protected) ──────────────

    public function publicRecordWinner(Request $request, Event $event)
    {
        if (!$event->raffle_enabled || !$event->hasValidPin() || $request->input('pin') !== $event->raffle_pin) {
            return response()->json(['success' => false, 'message' => 'Session expired or invalid.']);
        }

        return $this->recordWinner($request, $event);
    }

    // ─── Public raffle: pool (no auth, pin-protected) ─────────────────────────

    public function publicRafflePool(Request $request, Event $event)
    {
        if (!$event->raffle_enabled || !$event->hasValidPin() || $request->input('pin') !== $event->raffle_pin) {
            return response()->json(['success' => false, 'message' => 'Session ended.']);
        }

        return $this->rafflePool($event);
    }

    // ─── Public raffle: winner history (no auth, pin-protected) ──────────────

    public function publicWinnerHistory(Request $request, Event $event)
    {
        if (!$event->raffle_enabled || !$event->hasValidPin() || $request->input('pin') !== $event->raffle_pin) {
            return response()->json(['success' => false, 'message' => 'Session ended.']);
        }

        return $this->winnerHistory($event);
    }

    // ─── Edit form ────────────────────────────────────────────────────────────

    public function edit(Event $event)
    {
        return view('events.edit', compact('event'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title'      => 'required|string|max:200',
            'event_date' => 'required|date',
        ]);

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

    public function toggleStatus(Event $event)
    {
        $event->update([
            'status'       => $event->status === 'open' ? 'closed' : 'open',
            'date_updated' => now(),
        ]);

        return response()->json(['success' => true, 'status' => $event->status]);
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
                $q->whereHas('citizen', fn($c) =>
                    $c->where('fname', 'like', "%$search%")
                      ->orWhere('lname', 'like', "%$search%")
                );
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
        $citizens = $this->buildBulkQuery($request)->get(['id', 'fname', 'mname', 'lname', 'bday', 'gender', 'address']);

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
        $citizens = $this->buildBulkQuery($request)->pluck('id');

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
            ->where('approval_status', 1)
            ->when($search, fn($q) => $q->where(fn($q) =>
                $q->where('fname', 'like', "%$search%")
                  ->orWhere('lname', 'like', "%$search%")
                  ->orWhere('mname', 'like', "%$search%")
            ))
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

        $pool = EventAttendance::with('citizen')
            ->where('eventId', $event->id)
            ->whereNotIn('citizenId', $wonIds)
            ->get()
            ->map(fn(EventAttendance $a) => [
                'citizenId' => $a->citizenId,
                'name'      => $a->citizen?->full_name ?? '—',
                'photo'     => $a->citizen?->profile
                    ? asset(str_replace('public/', 'storage/', $a->citizen->profile))
                    : null,
            ]);

        return response()->json(['success' => true, 'pool' => $pool]);
    }

    // ─── Raffle: record winner ────────────────────────────────────────────────

    public function recordWinner(Request $request, Event $event)
    {
        $citizenId  = (int) $request->citizenId;
        $prizeLabel = $request->input('prize_label');

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

    private function buildBulkQuery(Request $request)
    {
        $query = Citizen::where('is_active', 1)->where('approval_status', 1);

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
