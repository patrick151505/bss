<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Citizen;
use App\Models\CitizenRegistration;
use App\Models\CivilStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Citizens → Registrations: review what people submitted through a registration
 * link. Approve creates the citizen record; reject keeps the entry with a reason.
 */
class CitizenRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $q      = trim((string) $request->input('q', ''));

        $registrations = CitizenRegistration::with('token', 'reviewer')
            ->when(array_key_exists($status, CitizenRegistration::STATUSES), fn($x) => $x->where('status', $status))
            ->when($q !== '', fn($x) => $x->where(fn($w) => $w->where('fname', 'like', "%{$q}%")->orWhere('lname', 'like', "%{$q}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = CitizenRegistration::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return view('citizens.registrations.index', compact('registrations', 'counts', 'status', 'q'));
    }

    public function show(CitizenRegistration $registration)
    {
        $registration->load('token', 'reviewer', 'citizen');

        return view('citizens.registrations.show', [
            'r'          => $registration,
            'duplicates' => $registration->status === 'pending' ? $registration->possibleDuplicates() : collect(),
            'zone'       => Address::find($registration->field('address')),
            'civil'      => CivilStatus::find($registration->field('civil_status')),
            'addresses'     => Address::where('is_active', 1)->orderBy('description')->get(),
            'civilStatuses' => CivilStatus::orderBy('id')->get(),
        ]);
    }

    // Stream a private ID proof (front/back) — only through this authenticated route.
    public function proof(CitizenRegistration $registration, string $group, string $side)
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $path = $registration->data['proofs'][$group][$side] ?? null;
        abort_unless($path && Storage::exists($path), 404);

        return response()->file(Storage::path($path), ['Cache-Control' => 'private, no-store']);
    }

    // Save corrections without approving yet.
    public function update(Request $request, CitizenRegistration $registration)
    {
        abort_unless($registration->status === 'pending', 422, 'This registration was already reviewed.');
        $this->applyEdits($request, $registration, false);

        return back()->with('success', 'Changes saved. The registration is still waiting for approval.');
    }

    /**
     * Validate and save the staff's corrections (every field can be edited) and
     * their ID-proof checks. On approval, each submitted ID must be marked
     * Verified or Not valid. Changes are written to the activity log.
     */
    private function applyEdits(Request $request, CitizenRegistration $r, bool $forApproval): void
    {
        $rules    = PublicRegistrationController::fieldRules($request);
        $messages = ['pricinct_no.required' => 'Precinct No. is required for registered voters.'];
        $checks   = [];   // proof key => label, for groups that sent an ID and are still ticked
        foreach (PublicRegistrationController::PROOF_GROUPS as $flag => $g) {
            if (!empty($r->data['proofs'][$g['key']]) && $request->input($flag) == 1) {
                $checks[$g['key']] = $g['label'];
                $rules["verify_{$g['key']}"]      = ($forApproval ? 'required' : 'nullable') . '|in:verified,invalid';
                $rules["verify_{$g['key']}_note"] = 'nullable|string|max:300';
                $messages["verify_{$g['key']}.required"] = "Check the {$g['label']} and mark it Verified or Not valid.";
            }
        }
        $data = $request->validate($rules, $messages);

        $verification = $r->data['verification'] ?? [];
        foreach ($checks as $key => $label) {
            if (empty($data["verify_{$key}"])) continue;
            $prev = $verification[$key] ?? [];
            $note = trim((string) ($data["verify_{$key}_note"] ?? '')) ?: null;
            if (($prev['status'] ?? null) !== $data["verify_{$key}"] || ($prev['note'] ?? null) !== $note) {
                $verification[$key] = [
                    'status'  => $data["verify_{$key}"],
                    'note'    => $note,
                    'by'      => auth()->id(),
                    'by_name' => auth()->user()->name,
                    'at'      => now()->format('Y-m-d H:i:s'),
                ];
            }
        }

        $newData = collect($data)->only(PublicRegistrationController::DATA_FIELDS)
            ->map(fn($v) => is_string($v) ? trim($v) : $v)->all();
        $newData['pricinct_no'] = ($newData['voters'] ?? 0) == 1 && !empty($newData['pricinct_no'])
            ? strtoupper($newData['pricinct_no']) : null;
        $newData['proofs']       = $r->data['proofs'] ?? [];
        $newData['verification'] = $verification;

        // What changed (for the activity log).
        $before = ['fname' => $r->fname, 'mname' => $r->mname, 'lname' => $r->lname, 'suffix' => $r->suffix,
                   'bday' => $r->bday?->format('Y-m-d')] + ($r->data ?? []);
        $after  = ['fname' => trim($data['fname']), 'mname' => $data['mname'] ?? null, 'lname' => trim($data['lname']),
                   'suffix' => $data['suffix'] ?? null, 'bday' => $data['bday']] + $newData;
        $changed = [];
        foreach (array_merge(['fname', 'mname', 'lname', 'suffix', 'bday'], PublicRegistrationController::DATA_FIELDS) as $k) {
            $from = (string) ($before[$k] ?? '');
            $to   = (string) ($after[$k] ?? '');
            if ($from !== $to) $changed[$k] = ['from' => $from === '' ? '—' : $from, 'to' => $to === '' ? '—' : $to];
        }

        $r->update([
            'fname'  => $after['fname'],
            'mname'  => $after['mname'],
            'lname'  => $after['lname'],
            'suffix' => $after['suffix'],
            'bday'   => $after['bday'],
            'data'   => $newData,
        ]);

        if ($changed) {
            ActivityLog::record('updated', 'citizen_registration', $r->id,
                "Online registration of {$r->fname} {$r->lname} corrected before approval (" . implode(', ', array_keys($changed)) . ')',
                ['changed' => $changed]);
        }
    }

    public function approve(Request $request, CitizenRegistration $registration)
    {
        abort_unless($registration->status === 'pending', 422, 'This registration was already reviewed.');

        $this->applyEdits($request, $registration, true);
        $registration->refresh();

        if ($registration->possibleDuplicates()->isNotEmpty() && !$request->boolean('confirm_duplicate')) {
            return back()->withErrors(['duplicate' => 'A citizen with the same name and birthday already exists. Tick the confirmation to approve anyway.']);
        }

        $citizen = DB::transaction(function () use ($registration) {
            $d      = $registration->data;
            $ver    = $d['verification'] ?? [];
            // A group whose ID was marked Not valid is not given to the citizen.
            $keep   = fn(string $flag, string $key) => (($d[$flag] ?? 0) == 1 && (($ver[$key]['status'] ?? null) !== 'invalid')) ? 1 : 0;
            $notes  = ['Registered online (registration #' . $registration->id . ').'];
            foreach (PublicRegistrationController::PROOF_GROUPS as $g) {
                if (!empty($ver[$g['key']])) {
                    $notes[] = $g['label'] . ' ' . ($ver[$g['key']]['status'] === 'verified' ? 'verified' : 'NOT valid')
                        . ' by ' . ($ver[$g['key']]['by_name'] ?? 'staff')
                        . (!empty($ver[$g['key']]['note']) ? ' — ' . $ver[$g['key']]['note'] : '') . '.';
                }
            }
            $qrcode = md5(Str::random(12) . now()->format('Y-m-d H:i:s'));

            // Move the submitted photo to where citizen photos live (public/citizen/<qrcode>.<ext>).
            $profile = null;
            if ($registration->photo && Storage::exists($registration->photo)) {
                $ext     = pathinfo($registration->photo, PATHINFO_EXTENSION) ?: 'jpg';
                $profile = 'public/citizen/' . $qrcode . '.' . strtolower($ext);
                Storage::copy($registration->photo, $profile);
            }

            $citizen = Citizen::create([
                'fname'             => $registration->fname,
                'mname'             => $registration->mname,
                'lname'             => $registration->lname,
                'suffix'            => $registration->suffix,
                'bday'              => $registration->bday->format('Y-m-d'),
                'gender'            => $d['gender'] ?? null,
                'civil_status'      => $d['civil_status'] ?? null,
                'bplace'            => $d['bplace'] ?? null,
                'citizenship'       => $d['citizenship'] ?? 'Filipino',
                'occupation'        => $d['occupation'] ?? null,
                'address'           => $d['address'] ?? null,
                'no'                => $d['no'] ?? null,
                'street'            => $d['street'] ?? null,
                'blk'               => $d['blk'] ?? null,
                'lot'               => $d['lot'] ?? null,
                'phase'             => $d['phase'] ?? null,
                'complete_address'  => $d['complete_address'] ?? null,
                'year_stay'         => !empty($d['year_stay']) ? $d['year_stay'] . '-01' : null,
                'owner_status'      => $d['owner_status'] ?? 0,
                'house_owner_name'  => (int) ($d['owner_status'] ?? 0) === 0 ? ($d['house_owner_name'] ?? null ?: null) : null,
                'contact'           => $d['contact'] ?? null,
                'email'             => $d['email'] ?? null,
                'approval_status'   => 2,                       // Approved
                'is_active'         => 1,
                'is_pwd'            => $keep('is_pwd', 'pwd'),
                'is_soloparents'    => $keep('is_soloparents', 'solo'),
                'voters'            => $d['voters'] ?? 0,
                'pricinct_no'       => ($d['voters'] ?? 0) == 1 ? ($d['pricinct_no'] ?? null) : null,
                'is_verify'         => 0,
                'ic_fullname'       => $d['ic_fullname'] ?? null,
                'ic_relationship'   => $d['ic_relationship'] ?? null,
                'ic_contact'        => $d['ic_contact'] ?? null,
                'ic_email'          => $d['ic_email'] ?? null,
                'ic_address'        => $d['ic_address'] ?? null,
                'note'              => implode(' ', $notes),
                'profile'           => $profile,
                'qrcode'            => $qrcode,
                'date_approved'     => now(),
                'user_id_approved'  => auth()->id(),
                'date_created'      => now(),
                'date_last_updated' => now(),
            ]);

            // The photo now lives with the citizen; drop the public registration copy.
            if ($profile) Storage::delete($registration->photo);

            $registration->update([
                'status'      => 'approved',
                'photo'       => $profile ?? $registration->photo,
                'citizen_id'  => $citizen->id,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $citizen;
        });

        \App\Notifications\CitizenRegistered::markReviewed($registration->id);

        ActivityLog::record('created', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was registered (online registration #{$registration->id} approved)",
            ['citizen_id' => \App\Models\Setting::instance()->formatCitizenId($citizen->id), 'registration_id' => $registration->id]);

        return redirect()->route('citizens.registrations.index')
            ->with('success', "{$citizen->fname} {$citizen->lname} was approved and added to the citizens list.");
    }

    public function reject(Request $request, CitizenRegistration $registration)
    {
        abort_unless($registration->status === 'pending', 422, 'This registration was already reviewed.');

        $data = $request->validate(['reject_reason' => 'required|string|min:3|max:500']);

        // Data Privacy Act: a rejected person's photo and ID copies are not kept.
        // The typed details stay so the reason and history can still be seen.
        $files = array_filter(array_merge(
            [$registration->photo],
            collect($registration->data['proofs'] ?? [])->flatten()->all()
        ));
        $dataNoProofs = $registration->data;
        $dataNoProofs['proofs'] = [];

        $registration->update([
            'status'        => 'rejected',
            'photo'         => null,
            'data'          => $dataNoProofs,
            'reject_reason' => $data['reject_reason'],
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
        ]);

        if ($files) Storage::delete($files);

        \App\Notifications\CitizenRegistered::markReviewed($registration->id);

        ActivityLog::record('rejected', 'citizen_registration', $registration->id,
            "Online registration of {$registration->fname} {$registration->lname} rejected — {$data['reject_reason']}");

        return redirect()->route('citizens.registrations.index')->with('success', 'Registration rejected.');
    }
}
