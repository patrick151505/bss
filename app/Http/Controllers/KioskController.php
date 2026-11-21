<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\DocumentPurpose;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Kiosk;
use App\Models\KioskSlide;
use App\Support\PersonFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The self-service kiosk at the barangay hall. Any device can be the kiosk: it
 * only needs to be logged in with an account that has kiosk.use. Residents scan their Barangay ID, pick a document and purpose, check
 * the preview and get a queue number. Every request waits for staff approval,
 * and staff print it at the counter. They can also check themselves in to
 * today's events that staff opened for the kiosk.
 */
class KioskController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'no-store');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            return $response;
        });
    }

    /** The kiosk account's name, for logs ("Hall Kiosk"). */
    private function where(): string
    {
        return auth()->user()->name;
    }

    public function home(Request $request)
    {
        $request->session()->put(Kiosk::SESSION, true);
        return view('kiosk.screen', [
            'slides' => KioskSlide::forScreen(),
            'brgy'   => \App\Models\Setting::instance(),
            // For the "Update my details" form.
            'zones'  => \App\Models\Address::where('is_active', 1)->orderBy('description')->get(['id', 'description', 'is_subd', 'rules_required']),
            'civils' => \App\Models\CivilStatus::orderBy('id')->get(['id', 'description']),
        ]);
    }

    // Register: the normal registration wizard through this kiosk account's own link.
    public function register(Request $request)
    {
        $request->session()->put(Kiosk::SESSION, true);   // registration pages show "back to kiosk"
        return redirect()->route('register.citizen', Kiosk::registrationLinkFor($request->user())->token);
    }

    // A Barangay ID was scanned: who it is and which documents they can request here.
    public function scan(Request $request)
    {
        // Testing without a scanner (debug mode only): Ctrl + a number on the kiosk
        // "scans" the ID of the citizen with that record number.
        $testCode = null;
        if ($request->filled('test_id') && config('app.debug')) {
            $testCode = Citizen::whereKey((int) $request->input('test_id'))->value('qrcode');
            if (!$testCode) {
                return response()->json(['message' => "Test scan: no active citizen #{$request->input('test_id')} with a QR code."], 404);
            }
            $request->merge(['code' => $testCode]);
        }

        $request->validate(['code' => 'required|string|max:100']);
        $citizen = $this->findCitizen($request->input('code'));
        if ($citizen instanceof \Illuminate\Http\JsonResponse) return $citizen;

        $types = DocumentType::active()->where('allow_kiosk', true)->with('fields')
            ->orderBy('sort_order')->orderBy('name')->get();

        // Already waiting today → don't let the same document be requested twice.
        $waiting = DocumentRequest::where('citizen_id', $citizen->id)
            ->where('source', 'kiosk')
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'approved', 'ready_for_release'])
            ->pluck('queue_no', 'document_type_id');

        return response()->json([
            'citizen' => [
                'first' => $citizen->fname,
                'name'  => trim($citizen->fname . ' ' . $citizen->lname),
                'photo' => $citizen->profile ? $citizen->profile_url : null,
            ],
            'details' => $this->detailsFor($citizen),
            'form'    => $this->formFor($citizen),
            'pending_update' => \App\Models\CitizenUpdateRequest::where('citizen_id', $citizen->id)->where('status', 'pending')
                ->latest()->value('created_at')?->format('M d, Y'),
            'types' => $types->map(fn(DocumentType $t) => $this->typeFor($t, $citizen, $waiting[$t->id] ?? null))->values(),
            'purposes' => DocumentPurpose::active()->orderBy('sort_order')->orderBy('name')->limit(12)->pluck('name'),
            'events'   => $this->eventsFor($citizen),
        ] + ($testCode ? ['code' => $testCode] : []));
    }

    // Render the document exactly as it will print, without saving anything.
    public function preview(Request $request)
    {
        $ctx = $this->context($request);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$citizen, $type, $purpose, $customFields] = $ctx;

        $versionId = $type->document_template_version_id ?? $type->template?->current_version_id;
        $draft = new DocumentRequest([
            'document_type_id'    => $type->id,
            'citizen_id'          => $citizen->id,
            'purpose'             => $purpose,
            'custom_fields'       => $customFields,
            'is_paid'             => $type->is_paid,
            'fee'                 => $type->fee,
            'template_version_id' => $versionId,
        ]);
        $draft->setRelation('documentType', $type);
        $draft->setRelation('citizen', $citizen);
        if ($versionId) $draft->setRelation('templateVersion', \App\Models\DocumentTemplateVersion::find($versionId));
        $ver = $draft->templateVersion;

        return response()->json([
            'header'      => $draft->renderHeader(),
            'body'        => $draft->resolveTemplate(),
            'bg_url'      => $ver?->paper_bg ? asset('storage/' . $ver->paper_bg) : null,
            'paper_size'  => $ver?->paper_size ?? 'letter',
            'orientation' => $ver?->orientation ?? 'portrait',
            'padding'     => [
                'top'    => $ver?->padding_top    ?? 50,
                'right'  => $ver?->padding_right  ?? 50,
                'bottom' => $ver?->padding_bottom ?? 20,
                'left'   => $ver?->padding_left   ?? 50,
            ],
        ]);
    }

    // The resident confirmed the preview: save the request (always waits for approval).
    public function submit(Request $request)
    {
        $ctx = $this->context($request);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$citizen, $type, $purpose, $customFields] = $ctx;

        $doc = DB::transaction(function () use ($citizen, $type, $purpose, $customFields) {
            return DocumentRequest::create([
                'document_type_id'    => $type->id,
                'doc_number'          => $type->allocateNextNumber(),
                'citizen_id'          => $citizen->id,
                'created_by'          => null,
                'source'              => 'kiosk',
                'kiosk_id'            => null,
                'queue_no'            => Kiosk::nextQueueNo(),
                'purpose'             => $purpose,
                'status'              => 'pending',
                'custom_fields'       => $customFields,
                'is_paid'             => $type->is_paid,
                'fee'                 => $type->fee,
                'fee_paid'            => false,
                'template_version_id' => $type->document_template_version_id ?? $type->template?->current_version_id,
            ]);
        });
        $doc->setRelation('citizen', $citizen)->setRelation('documentType', $type);

        ActivityLog::record('created', 'document_request', $doc->id,
            "Kiosk request {$doc->queue_no}: {$type->name} for {$citizen->lname}, {$citizen->fname} ({$this->where()})");

        try {
            $staff = \App\Models\User::where('is_active', 1)->get()
                ->filter(fn($u) => $u->hasRole('Super Admin') || $u->can('documents.edit'));
            \Illuminate\Support\Facades\Notification::send($staff, new \App\Notifications\KioskDocumentRequested($doc));
        } catch (\Throwable $e) {
            report($e);   // never lose the request over a notification
        }

        return response()->json([
            'queue_no' => $doc->queue_no,
            'document' => $type->name,
            'is_paid'  => (bool) $type->is_paid,
            'fee'      => (float) $type->fee,
        ]);
    }

    /**
     * "Update my details": the resident's changes are saved as a request for staff
     * to approve (Citizens → Detail Updates) — the record itself is not touched.
     * Mobile and email fields left blank mean "keep the one on record".
     */
    public function updateDetails(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);
        $citizen = $this->findCitizen($request->input('code'));
        if ($citizen instanceof \Illuminate\Http\JsonResponse) return $citizen;

        $fields = array_keys(\App\Models\CitizenUpdateRequest::FIELDS);
        $base   = PublicRegistrationController::fieldRules($request);
        $phone  = $base['ic_contact'];                       // nullable + 09xxxxxxxxx check
        $rules  = array_intersect_key($base, array_flip($fields));
        $rules['contact'] = $phone;
        $rules['email']   = 'nullable|email|max:250';
        $data = $request->validate($rules, ['pricinct_no.required' => 'Precinct No. is required for registered voters.']);

        // Masked fields: blank = keep what is on record.
        foreach (['contact', 'email', 'ic_contact', 'ic_email'] as $f) {
            if (trim((string) ($data[$f] ?? '')) === '') unset($data[$f]);
        }
        if (isset($data['voters']) && (int) $data['voters'] !== 1) $data['pricinct_no'] = null;
        if (!empty($data['pricinct_no'])) $data['pricinct_no'] = strtoupper(trim($data['pricinct_no']));

        $changes = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f, $data)) continue;
            $to   = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
            $from = $citizen->getAttribute($f);
            if ((string) $from !== (string) ($to ?? '')) {
                $changes[$f] = ['from' => $from, 'to' => $to ?? ''];
            }
        }
        if (!$changes) {
            return response()->json(['message' => 'Nothing was changed.'], 422);
        }

        // One waiting request per person: a new one replaces the old.
        \App\Models\CitizenUpdateRequest::where('citizen_id', $citizen->id)->where('status', 'pending')->get()
            ->each(function ($old) {
                $old->update(['status' => 'rejected', 'reject_reason' => 'Replaced by a newer request from the kiosk.', 'reviewed_at' => now()]);
                \App\Notifications\CitizenUpdateRequested::markHandled($old->id);
            });

        $req = \App\Models\CitizenUpdateRequest::create([
            'citizen_id' => $citizen->id,
            'kiosk_id'   => null,
            'changes'    => $changes,
            'status'     => 'pending',
        ]);
        $req->setRelation('citizen', $citizen);

        ActivityLog::record('created', 'citizen_update_request', $req->id,
            "{$citizen->lname}, {$citizen->fname} asked at the kiosk to update: " . implode(', ', array_map(fn($f) => strtolower(\App\Models\CitizenUpdateRequest::FIELDS[$f]), array_keys($changes))));

        try {
            $staff = \App\Models\User::where('is_active', 1)->get()
                ->filter(fn($u) => $u->hasRole('Super Admin') || $u->can('citizens.edit'));
            \Illuminate\Support\Facades\Notification::send($staff, new \App\Notifications\CitizenUpdateRequested($req));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'count'   => count($changes),
            'details' => $this->detailsFor($citizen),
        ]);
    }

    /** "Join an event": check the resident in to an event that is open at the kiosk today. */
    public function join(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100', 'event_id' => 'required|integer']);
        $citizen = $this->findCitizen($request->input('code'));
        if ($citizen instanceof \Illuminate\Http\JsonResponse) return $citizen;

        $event = $this->kioskEvents()->find($request->input('event_id'));
        if (!$event) {
            return response()->json(['message' => 'This event is not open at the kiosk. Please ask the staff.'], 422);
        }

        $time = DB::transaction(function () use ($event, $citizen) {
            // Locks the event row so two quick taps can't check the same person in twice.
            Event::whereKey($event->id)->lockForUpdate()->first();
            $info = $this->eventFor($event, $citizen);
            if (!$info['can_join']) {
                return $info;
            }
            $row = EventAttendance::create([
                'eventId'   => $event->id,
                'citizenId' => $citizen->id,
                'time_in'   => now(),
                'method'    => 'kiosk',
            ]);
            return $row->time_in;
        });
        if (is_array($time)) {
            return response()->json(['message' => $time['reason']], 422);
        }

        ActivityLog::record('created', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} checked in to \"{$event->title}\" at the kiosk ({$this->where()})");

        return response()->json([
            'event'  => $event->title,
            'time'   => $time->format('g:i A'),
            'venue'  => $event->venue,
            'raffle' => (bool) $event->raffle_enabled,
            'events' => $this->eventsFor($citizen),
        ]);
    }

    /** Today's events open for kiosk check-in, so the scan screen can say what the check-in is for. */
    public function events()
    {
        $time = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : null;
        return response()->json([
            'events' => $this->kioskEvents()->orderBy('event_start')->get()->filter(fn(Event $e) => $e->is_open)
                ->map(fn(Event $e) => [
                    'id'     => $e->id,
                    'title'  => $e->title,
                    'time'   => collect([$time($e->event_start), $time($e->event_end)])->filter()->implode(' – ') ?: null,
                    'venue'  => $e->venue,
                    'cover'  => $e->cover_photo_url,
                    'raffle' => (bool) $e->raffle_enabled,
                ])->values(),
        ]);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** What the "Check your details" screen shows. Phone numbers and emails are partly hidden. */
    private function detailsFor(Citizen $c): array
    {
        $M = \App\Models\CitizenUpdateRequest::class;
        $groups = collect([
            'Senior citizen' => ($c->age ?? 0) >= 60,
            'PWD'            => (int) $c->is_pwd === 1,
            'Solo parent'    => (int) $c->is_soloparents === 1,
        ])->filter()->keys()->implode(', ');

        return [
            ['icon' => 'mgc_user_3_line',     'label' => 'Full name',        'value' => $c->full_name, 'locked' => true],
            ['icon' => 'mgc_cake_line',       'label' => 'Birthday',         'value' => $c->bday ? $c->bday->format('F j, Y') . ' (' . $c->age . ' yrs)' : '—', 'locked' => true],
            ['icon' => 'mgc_heart_line', 'label' => 'Sex · Civil status', 'value' => ((int) $c->gender === 1 ? 'Male' : 'Female') . ' · ' . ($c->civilStatus?->description ?? '—')],
            ['icon' => 'mgc_map_pin_line',    'label' => 'Address',          'value' => $c->full_address ?: '—'],
            ['icon' => 'mgc_phone_line',      'label' => 'Mobile',           'value' => $M::maskPhone($c->contact) ?? '—'],
            ['icon' => 'mgc_mail_line',       'label' => 'Email',            'value' => $M::maskEmail($c->email) ?? '—'],
            ['icon' => 'mgc_briefcase_line',  'label' => 'Occupation',       'value' => $c->occupation ?: '—'],
            ['icon' => 'mgc_check_circle_line', 'label' => 'Registered voter', 'value' => (int) $c->voters === 1 ? 'Yes' . ($c->pricinct_no ? ' · Precinct ' . $c->pricinct_no : '') : 'No'],
            ['icon' => 'mgc_group_line',      'label' => 'Groups',           'value' => $groups ?: 'None'],
            ['icon' => 'mgc_phone_call_line', 'label' => 'Emergency contact', 'value' => $c->ic_fullname
                ? trim($c->ic_fullname . ($c->ic_relationship ? ' (' . $c->ic_relationship . ')' : '') . ($c->ic_contact ? ' · ' . $M::maskPhone($c->ic_contact) : ''))
                : '—'],
        ];
    }

    /** Current values for the update form (phone numbers and emails are not sent — only hints). */
    private function formFor(Citizen $c): array
    {
        $M = \App\Models\CitizenUpdateRequest::class;
        return [
            'civil_status' => $c->civil_status, 'occupation' => $c->occupation,
            'address' => $c->address, 'no' => $c->no, 'street' => $c->street, 'blk' => $c->blk, 'lot' => $c->lot, 'phase' => $c->phase,
            'complete_address' => $c->complete_address,
            'voters' => (int) $c->voters, 'pricinct_no' => $c->pricinct_no,
            'ic_fullname' => $c->ic_fullname, 'ic_relationship' => $c->ic_relationship, 'ic_address' => $c->ic_address,
            'hint_contact' => $M::maskPhone($c->contact), 'hint_email' => $M::maskEmail($c->email),
            'hint_ic_contact' => $M::maskPhone($c->ic_contact), 'hint_ic_email' => $M::maskEmail($c->ic_email),
        ];
    }

    /** Events offered at the kiosk: open, kiosk check-in on, and today (or reopened by staff). */
    private function kioskEvents()
    {
        return Event::where('allow_kiosk', true)->where('status', '!=', 'closed')
            ->where(fn($q) => $q->whereDate('event_date', today())
                ->orWhere(fn($q) => $q->whereNotNull('reopened_at')->whereDate('event_date', '<', today())));
    }

    /** Today's kiosk events plus the next two weeks' (shown as "opens on …"), for this resident. */
    private function eventsFor(Citizen $c): array
    {
        $today = $this->kioskEvents()->get()->filter(fn(Event $e) => $e->is_open);
        $soon  = Event::where('allow_kiosk', true)->where('status', '!=', 'closed')
            ->whereDate('event_date', '>', today())->whereDate('event_date', '<=', today()->addDays(14))
            ->orderBy('event_date')->orderBy('event_start')->limit(6)->get();

        return $today->sortBy('event_start')->values()->map(fn(Event $e) => $this->eventFor($e, $c))
            ->concat($soon->map(fn(Event $e) => $this->eventFor($e, $c, false)))
            ->values()->all();
    }

    /** One event card for this resident: can they join, and if not, why. */
    private function eventFor(Event $e, Citizen $c, bool $today = true): array
    {
        $joined = EventAttendance::where('eventId', $e->id)->where('citizenId', $c->id)->value('time_in');
        $reason = null;
        if (!$today) {
            $reason = 'Check-in opens on ' . $e->event_date->format('F j') . '.';
        } elseif ($joined) {
            $reason = 'You are already checked in (' . \Carbon\Carbon::parse($joined)->format('g:i A') . ').';
        } elseif (!$e->allowsAddress($c->address)) {
            $reason = 'This event is only for ' . $e->allowedAddresses()->pluck('description')->implode(', ') . '.';
        } elseif ($why = $e->eligibilityFailure($c)) {
            // The model words it for staff ("they are 45; …") — say it to the resident instead.
            $reason = ucfirst(str_replace(['their birthday', 'they are'], ['your birthday', 'you are'], $why)) . '.';
        }

        $time = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : null;
        return [
            'id'       => $e->id,
            'title'    => $e->title,
            'category' => $e->category,
            'venue'    => $e->venue,
            'date'     => $e->event_date?->format('D, M j'),
            'time'     => collect([$time($e->event_start), $time($e->event_end)])->filter()->implode(' – ') ?: null,
            'cover'    => $e->cover_photo_url,
            'raffle'   => (bool) $e->raffle_enabled,
            'rules'    => array_values(array_filter(array_merge(
                $e->restrictsAddresses() ? ['For ' . $e->allowedAddresses()->pluck('description')->implode(', ')] : [],
                $e->eligibilityLabels()
            ))),
            'today'    => $today,
            'joined'   => (bool) $joined,
            'can_join' => $reason === null,
            'reason'   => $reason,
        ];
    }

    /** Active resident with this ID's QR code, or a JSON message for the screen. */
    private function findCitizen(string $code)
    {
        $citizen = Citizen::with('activeHold')->where('qrcode', trim($code))->first();
        if (!$citizen) {
            return response()->json(['message' => "We couldn't find this ID. Please go to the counter."], 404);
        }
        if ($citizen->activeHold) {
            // The hold reason may be private — never show it on a public screen.
            return response()->json(['message' => 'Please go to the counter. Staff need to check something on your record first.'], 423);
        }
        return $citizen;
    }

    /** What the kiosk shows for one document type for this person. */
    private function typeFor(DocumentType $t, Citizen $c, ?string $waitingQueue): array
    {
        $reason = null;
        if ($waitingQueue) {
            $reason = "You already requested this today ({$waitingQueue}). Please go to the counter.";
        } elseif ($missing = PersonFields::missing($t, $c)) {
            $labels = array_map(fn($k) => strtolower(PersonFields::FIELDS[$k]['label']), $missing);
            $reason = 'Your record has no ' . implode(', ', $labels) . ' yet. Please request this at the counter.';
        } elseif (PersonFields::usesPhoto($t) && !$c->profile) {
            $reason = 'This document needs your photo. Please request it at the counter.';
        }

        // Required details the resident must type (no default fills them).
        $ask = $t->fields->filter(fn($f) => $f->is_required
                && ($f->default_value ? DocumentRequest::resolvePlaceholders($f->default_value, $c) : '') === '')
            ->map(fn($f) => [
                'key'     => $f->field_key,
                'label'   => $f->field_label,
                'type'    => in_array($f->field_type, ['select', 'date', 'number', 'textarea'], true) ? $f->field_type : 'text',
                'options' => $f->field_type === 'select' ? array_values((array) $f->field_options) : [],
            ])->values();

        return [
            'id'        => $t->id,
            'name'      => $t->name,
            'is_paid'   => (bool) $t->is_paid,
            'fee'       => (float) $t->fee,
            'available' => $reason === null,
            'reason'    => $reason,
            'fields'    => $ask,
        ];
    }

    /** Validated [citizen, type, purpose, customFields] for preview/submit, or a JSON error. */
    private function context(Request $request)
    {
        $request->validate([
            'code'             => 'required|string|max:100',
            'document_type_id' => 'required|integer',
            'purpose'          => 'required|string|max:255',
        ], ['purpose.required' => 'Please choose a purpose.']);

        $citizen = $this->findCitizen($request->input('code'));
        if ($citizen instanceof \Illuminate\Http\JsonResponse) return $citizen;

        $type = DocumentType::active()->where('allow_kiosk', true)->with(['fields', 'template', 'pinnedVersion'])
            ->find($request->input('document_type_id'));
        if (!$type) {
            return response()->json(['message' => 'This document is not available at the kiosk.'], 422);
        }

        $waiting = DocumentRequest::where('citizen_id', $citizen->id)->where('document_type_id', $type->id)
            ->where('source', 'kiosk')->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'approved', 'ready_for_release'])->value('queue_no');
        $info = $this->typeFor($type, $citizen, $waiting);
        if (!$info['available']) {
            return response()->json(['message' => $info['reason']], 422);
        }

        // Use the saved spelling of a known purpose; a typed one is kept as is (not added to the list).
        $purpose = trim($request->input('purpose'));
        $purpose = DocumentPurpose::whereRaw('LOWER(name) = ?', [mb_strtolower($purpose)])->value('name') ?? $purpose;

        try {
            $customFields = DocumentRequestController::buildCustomFields($type, $request, $citizen);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return [$citizen, $type, $purpose, $customFields];
    }
}
