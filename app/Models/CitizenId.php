<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenId extends Model
{
    protected $table = 'eb_citizen_ids';

    protected $fillable = ['citizen_id', 'generated_by', 'valid_until', 'sig_front'];

    protected $casts = ['valid_until' => 'date'];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Build the placeholder => value map used to fill an ID template.
     * This is the single source of truth for both the printed ID and the
     * designer's live preview, so what you design is what prints.
     *
     * $dateIssued / $validUntil default to "now" and "now+2y" for previewing
     * a citizen who doesn't have an actual ID record yet.
     */
    public static function placeholderValues(
        Citizen $c,
        ?\Carbon\Carbon $dateIssued = null,
        ?\Carbon\Carbon $validUntil = null
    ): array {
        $setting = Setting::instance();

        $lname   = strtoupper($c->lname ?? '');
        $fname   = $c->fname ?? '';
        $mname   = $c->mname ?? '';
        $suffix  = $c->suffix ?? '';
        $midInit = $mname ? strtoupper(substr($mname, 0, 1)) . '.' : '';

        $fullNameFormal = trim(($lname ? $lname . ',' : '') . ' '
            . trim(implode(' ', array_filter([$fname, $midInit, $suffix]))));
        $fullNamePlain  = trim(implode(' ', array_filter([$fname, $midInit, $lname, $suffix])));

        $profile  = $c->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null;
        $photoUrl = $profile ?? 'https://ui-avatars.com/api/?name=' . urlencode($fullNamePlain) . '&size=200&background=94a3b8&color=fff';

        $qrValue = $c->qrcode ?? $setting->formatCitizenId($c->id);

        return [
            'full_name'       => strtoupper($fullNameFormal),
            'fname'           => $fname,
            'lname'           => $lname,
            'mname'           => $mname,
            'suffix'          => $suffix,
            'id_no'           => $setting->formatCitizenId($c->id),
            'qrcode_value'    => $qrValue,
            'bday'            => $c->bday?->format('M d, Y') ?? '—',
            'gender'          => match ((int) ($c->gender ?? 0)) { 1 => 'Male', 2 => 'Female', default => '—' },
            'address'         => $c->complete_address ?? ($c->addressZone?->description ?? '—'),
            'contact'         => $c->contact ?? '—',
            'since'           => $c->year_stay?->format('Y') ?? '—',
            'valid_until'     => ($validUntil ?? now()->addYears(2))->format('M d, Y'),
            'date_issued'     => ($dateIssued ?? now())->format('F d, Y'),
            'precinct_no'     => $c->pricinct_no ?: '—',   // note: column typo `pricinct_no`
            'brgy_name'       => $setting->barangay_name ?? 'BARANGAY',
            'municipality'    => $setting->municipality ?? '',
            'province'        => $setting->province ?? '',
            'captain'         => $setting->captain_name ?? '—',
            'captain_pos'     => 'Barangay Captain',
            'ic_name'         => $c->ic_fullname ?? '—',
            'ic_contact'      => $c->ic_contact ?? '—',
            'ic_relationship' => $c->ic_relationship ?? '—',
            'ic_address'      => $c->ic_address ?? '—',
            'photo_url'       => $photoUrl,
            'qr_url'          => \App\Support\Qr::svgDataUri($qrValue, 200),
        ];
    }
}
