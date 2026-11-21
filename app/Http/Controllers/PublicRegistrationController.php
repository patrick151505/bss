<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\CitizenRegistration;
use App\Models\CivilStatus;
use App\Models\RegistrationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public citizen self-registration through a link from Settings → Registration Links.
 * No login. Submissions wait in Citizens → Registrations until staff approve them.
 */
class PublicRegistrationController extends Controller
{
    /** Fields stored in the registration's data JSON (names match the staff citizen form). */
    const DATA_FIELDS = [
        'gender', 'civil_status', 'bplace', 'citizenship', 'occupation',
        'address', 'no', 'street', 'blk', 'lot', 'phase', 'complete_address', 'year_stay', 'owner_status',
        'contact', 'email', 'is_pwd', 'is_soloparents', 'is_senior', 'voters', 'pricinct_no',
        'ic_fullname', 'ic_relationship', 'ic_contact', 'ic_address', 'ic_email',
    ];

    /** Groups that need proof (front + back of the ID/certificate): checkbox field => label. */
    const PROOF_GROUPS = [
        'is_pwd'         => ['key' => 'pwd',    'label' => 'PWD ID'],
        'is_soloparents' => ['key' => 'solo',   'label' => 'Solo Parent ID'],
        'is_senior'      => ['key' => 'senior', 'label' => 'Senior Citizen ID'],
    ];

    public function show(string $token)
    {
        $link = RegistrationToken::where('token', $token)->first();
        if (!$link || !$link->isUsable()) {
            return response()->view('registration.unavailable', ['state' => $link?->state() ?? 'missing'], 410);
        }

        return view('registration.form', [
            'link'          => $link,
            'addresses'     => Address::where('is_active', 1)->orderBy('description')->get(),
            'civilStatuses' => CivilStatus::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $link = RegistrationToken::where('token', $token)->first();
        if (!$link || !$link->isUsable()) {
            return response()->view('registration.unavailable', ['state' => $link?->state() ?? 'missing'], 410);
        }

        // Honeypot: real people never see or fill this field.
        if ($request->filled('website')) {
            return redirect()->route('register.citizen.done', $token);
        }

        $data = $request->validate($this->rules($request), [
            'bday.before'          => 'Birthday must be a past date.',
            'pricinct_no.required' => 'Precinct No. is required for registered voters.',
            'consent.accepted'     => 'Please agree to the data privacy notice.',
        ] + $this->proofMessages());

        // Same person already waiting for review → don't create a second entry.
        $already = CitizenRegistration::where('status', 'pending')
            ->where('fname', $data['fname'])->where('lname', $data['lname'])
            ->whereDate('bday', $data['bday'])->exists();

        if (!$already) {
            $created = null;
            DB::transaction(function () use ($request, $data, $link, &$created) {
                // Count the use under a lock so a limited link can't be overrun.
                $fresh = RegistrationToken::whereKey($link->id)->lockForUpdate()->first();
                abort_unless($fresh && $fresh->isUsable(), 410, 'This registration link is no longer accepting registrations.');

                $photo = $request->hasFile('photo')
                    ? $request->file('photo')->store('public/registrations')
                    : null;

                // ID proofs are private (storage/app/registrations/proofs) — staff open
                // them through an authenticated route, never a public URL.
                $proofs = [];
                foreach (self::PROOF_GROUPS as $flag => $g) {
                    if (($data[$flag] ?? 0) != 1) continue;
                    foreach (['front', 'back'] as $side) {
                        $proofs[$g['key']][$side] = $request->file("proof_{$g['key']}_{$side}")->store('registrations/proofs');
                    }
                }

                $created = CitizenRegistration::create([
                    'token_id' => $fresh->id,
                    'status'   => 'pending',
                    'fname'    => $data['fname'],
                    'mname'    => $data['mname'] ?? null,
                    'lname'    => $data['lname'],
                    'suffix'   => $data['suffix'] ?? null,
                    'bday'     => $data['bday'],
                    'data'     => collect($data)->only(self::DATA_FIELDS)->all() + ['proofs' => $proofs],
                    'photo'    => $photo,
                    'ip'       => $request->ip(),
                ]);
                $fresh->increment('uses');
            });

            if ($created) $this->notifyReviewers($created);
        }

        return redirect()->route('register.citizen.done', $token);
    }

    // Bell notification for every active user who can approve registrations.
    // A failure here must never lose the person's registration.
    private function notifyReviewers(CitizenRegistration $registration): void
    {
        try {
            $reviewers = \App\Models\User::where('is_active', 1)->get()
                ->filter(fn($u) => $u->hasRole('Super Admin') || $u->can('citizens.approve'));
            \Illuminate\Support\Facades\Notification::send($reviewers, new \App\Notifications\CitizenRegistered($registration));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function done(string $token)
    {
        return view('registration.done');
    }

    private function proofMessages(): array
    {
        $m = [];
        foreach (self::PROOF_GROUPS as $g) {
            foreach (['front' => 'front', 'back' => 'back'] as $side => $word) {
                $m["proof_{$g['key']}_{$side}.required"] = "Please upload the {$word} of your {$g['label']}.";
                $m["proof_{$g['key']}_{$side}.image"]    = "The {$word} of your {$g['label']} must be a picture (JPG or PNG).";
                $m["proof_{$g['key']}_{$side}.max"]      = "The {$word} of your {$g['label']} is too large (max 8 MB).";
            }
        }
        return $m;
    }

    /** Public form: the person's details + photo, consent and ID proofs. */
    private function rules(Request $request): array
    {
        $rules = self::fieldRules($request) + [
            'photo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'consent' => 'accepted',
        ];

        // Ticked a group → front and back of its ID are required.
        foreach (self::PROOF_GROUPS as $flag => $g) {
            foreach (['front', 'back'] as $side) {
                $rules["proof_{$g['key']}_{$side}"] = ($request->input($flag) == 1 ? 'required' : 'nullable')
                    . '|image|mimes:jpeg,png,jpg,webp|max:8192';
            }
        }

        return $rules;
    }

    /**
     * Rules for the person's details — shared by the public form and the staff
     * approval form (where every field can be corrected before approving).
     */
    public static function fieldRules(Request $request): array
    {
        $phone = function ($attr, $value, $fail) {
            if ($value && (substr($value, 0, 2) !== '09' || strlen($value) !== 11 || !ctype_digit($value))) {
                $fail('Mobile numbers must be 11 digits starting with 09.');
            }
        };

        $rules = [
            'fname'            => 'required|string|max:250',
            'mname'            => 'nullable|string|max:250',
            'lname'            => 'required|string|max:250',
            'suffix'           => 'nullable|string|max:50',
            'gender'           => 'required|in:0,1',
            'civil_status'     => 'required|exists:eb_civil,id',
            'bday'             => 'required|date|before:today|after:1900-01-01',
            'bplace'           => 'required|string|max:250',
            'citizenship'      => 'required|string|max:250',
            'occupation'       => 'nullable|string|max:250',
            'address'          => 'required|exists:eb_address,id',
            'no'               => 'nullable|string|max:50',
            'street'           => 'nullable|string|max:250',
            'blk'              => 'nullable|numeric|min:1|max:9999',
            'lot'              => 'nullable|numeric|min:1|max:9999',
            'phase'            => 'nullable|numeric|min:1|max:99',
            'complete_address' => 'nullable|string|max:500',
            'year_stay'        => 'required|date_format:Y-m',
            'owner_status'     => 'required|in:0,1',
            'contact'          => ['required', $phone],
            'email'            => 'nullable|email|max:250',
            'is_pwd'           => 'required|in:0,1',
            'is_soloparents'   => 'required|in:0,1',
            'is_senior'        => 'nullable|in:0,1',
            'voters'           => 'required|in:0,1',
            'pricinct_no'      => 'nullable|string|min:5|max:6',
            'ic_fullname'      => 'nullable|string|max:250',
            'ic_relationship'  => 'nullable|string|max:250',
            'ic_contact'       => ['nullable', $phone],
            'ic_address'       => 'nullable|string|max:500',
            'ic_email'         => 'nullable|email|max:250',
        ];

        if ($request->voters == 1) {
            $rules['pricinct_no'] = 'required|string|min:5|max:6';
        }

        // Senior citizen only from age 60.
        $rules['is_senior'] = ['nullable', 'in:0,1', function ($attr, $value, $fail) use ($request) {
            if ($value == 1) {
                try {
                    $age = \Carbon\Carbon::parse($request->bday)->age;
                } catch (\Throwable $e) {
                    $age = 0;
                }
                if ($age < 60) $fail('Senior citizen is only for ages 60 and above.');
            }
        }];

        // Zone-specific required parts (same rule as the staff citizen form).
        $zone = Address::find($request->address);
        if ($zone && $zone->rules_required) {
            foreach (array_filter(array_map('trim', explode(',', $zone->rules_required))) as $field) {
                $rules[$field] = match ($field) {
                    'street' => 'required|string|max:250',
                    'phase'  => 'required|numeric|min:1|max:99',
                    default  => 'required|numeric|min:1|max:9999',
                };
            }
        }

        return $rules;
    }
}
