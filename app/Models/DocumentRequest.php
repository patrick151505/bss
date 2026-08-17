<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    protected $table = 'eb_document_requests';

    protected $fillable = [
        'document_type_id',
        'doc_number',
        'citizen_id',
        'created_by',
        'purpose',
        'status',
        'custom_fields',
        'body_override',
        'is_paid',
        'fee',
        'fee_paid',
        'amount_paid',
        'or_number',
        'remarks',
        'approved_by',
        'approved_at',
        'released_at',
        'print_count',
        'template_version_id',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'is_paid'       => 'boolean',
        'fee_paid'      => 'boolean',
        'fee'           => 'decimal:2',
        'amount_paid'   => 'decimal:2',
        'approved_at'   => 'datetime',
        'released_at'   => 'datetime',
    ];

    public function templateVersion()
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The formatted control number, e.g. "BRG-00001".
     * Uses the stored per-type sequence + the document type's prefix.
     */
    public function getControlNumberAttribute(): string
    {
        if ($this->doc_number === null) {
            return '';
        }

        $type = $this->relationLoaded('documentType')
            ? $this->documentType
            : $this->documentType()->first();

        // Fall back to a padded number if the type is somehow missing.
        return $type
            ? $type->formatDocNumber($this->doc_number)
            : str_pad((string) $this->doc_number, 5, '0', STR_PAD_LEFT);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'  => 'warning',
            'approved' => 'info',
            'released' => 'success',
            'rejected' => 'danger',
            default    => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function renderHeader(): string
    {
        $settings = Setting::instance();
        $style    = $this->documentType->header_style ?? 'classic';
        $brgy     = e($settings->barangay_name ?? 'Barangay');
        $city     = e($settings->municipality ?? '');
        $province = e($settings->province ?? '');
        $location = collect([$city, $province])->filter()->implode(', ');
        $title    = e($this->documentType->name ?? '');
        $logo     = $settings->logo ? asset('storage/' . $settings->logo) : null;
        $logoTag  = $logo ? "<img src=\"{$logo}\" style=\"width:70px;height:70px;object-fit:contain;\">" : '';

        return match ($style) {
            'side' => "
                <div style=\"display:flex;align-items:center;gap:16px;padding-bottom:12px;border-bottom:2px solid #1e40af;\">
                    <div style=\"flex-shrink:0;\">{$logoTag}</div>
                    <div>
                        <div style=\"font-size:9pt;letter-spacing:1px;\">Republic of the Philippines</div>
                        <div style=\"font-size:14pt;font-weight:700;line-height:1.2;\">{$brgy}</div>
                        <div style=\"font-size:10pt;\">{$location}</div>
                    </div>
                </div>
                <div style=\"text-align:center;margin-top:16px;margin-bottom:8px;\">
                    <div style=\"font-size:13pt;font-weight:700;letter-spacing:2px;text-transform:uppercase;\">{$title}</div>
                </div>",

            'minimal' => "
                <div style=\"text-align:center;padding-bottom:10px;border-bottom:1px solid #374151;\">
                    <div style=\"font-size:13pt;font-weight:700;\">{$brgy}</div>
                    <div style=\"font-size:9pt;color:#6b7280;\">{$location}</div>
                </div>
                <div style=\"text-align:center;margin-top:14px;margin-bottom:6px;\">
                    <div style=\"font-size:12pt;font-weight:700;letter-spacing:2px;text-transform:uppercase;text-decoration:underline;\">{$title}</div>
                </div>",

            default => "
                <div style=\"text-align:center;padding-bottom:12px;border-bottom:2px solid #1e40af;\">
                    <div style=\"margin-bottom:6px;\">{$logoTag}</div>
                    <div style=\"font-size:9pt;letter-spacing:1px;\">Republic of the Philippines</div>
                    <div style=\"font-size:15pt;font-weight:700;line-height:1.2;\">{$brgy}</div>
                    <div style=\"font-size:10pt;\">{$location}</div>
                </div>
                <div style=\"text-align:center;margin-top:16px;margin-bottom:8px;\">
                    <div style=\"font-size:13pt;font-weight:700;letter-spacing:2px;text-transform:uppercase;\">{$title}</div>
                </div>",
        };
    }

    // Build the base {{ tag }} => value map (citizen + date + barangay).
    // Shared by resolveTemplate() and any other place that needs to resolve
    // placeholders (e.g. a custom field's default_value) without a persisted request.
    public static function basePlaceholderMap(?Citizen $citizen, ?DocumentRequest $request = null): array
    {
        $settings = Setting::instance();
        $now      = now();

        // Ordinal suffix helper (1st, 2nd, 3rd, 15th…)
        $ordinal = function (int $n): string {
            $s = ['th','st','nd','rd'];
            $v = $n % 100;
            return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
        };

        // Civil status description from related model
        $civilStatus = '';
        if ($citizen) {
            $citizen->loadMissing('civilStatus');
            $civilStatus = $citizen->civilStatus?->description ?? '';
        }

        return [
            // ── Citizen ──────────────────────────────────────────────
            'fullname'     => $citizen?->full_name ?? '',
            'firstname'    => $citizen?->fname ?? '',
            'middlename'   => $citizen?->mname ?? '',
            'lastname'     => $citizen?->lname ?? '',
            'suffix'       => $citizen?->suffix ?? '',
            'gender'       => match((int)($citizen?->gender)) { 1 => 'Male', 2 => 'Female', default => '' },
            'civil_status' => $civilStatus,
            'birthday'     => $citizen?->bday?->format('F d, Y') ?? '',
            'birthplace'   => $citizen?->bplace ?? '',
            'age'          => $citizen?->age !== null ? (string)$citizen->age : '',
            'address'      => $citizen?->complete_address ?? '',
            'contact'      => $citizen?->contact ?? '',
            'email'        => $citizen?->email ?? '',
            'occupation'   => $citizen?->occupation ?? '',
            'year_stay'    => $citizen?->year_stay?->format('F Y') ?? '',
            'qrcode'       => $citizen?->qrcode ?? '',
            'profile_photo_link' => $citizen?->profile
                                ? asset(str_replace('public/', 'storage/', $citizen->profile))
                                : '',

            // ── Date issued (at time of render) ──────────────────────
            'date_day'     => $now->format('d'),
            'date_day_th'  => $ordinal((int)$now->format('j')),
            'date_month'   => $now->format('F'),
            'date_year'    => $now->format('Y'),
            'date_full'    => $now->format('F j, Y'),

            // ── Expiry (validity from issue date) ─────────────────────
            'expiry_3months' => $now->copy()->addMonths(3)->format('F j, Y'),
            'expiry_6months' => $now->copy()->addMonths(6)->format('F j, Y'),
            'expiry_1year'   => $now->copy()->addYear()->format('F j, Y'),

            // ── Barangay & request ────────────────────────────────────
            'brgy_name'    => $settings->barangay_name ?? '',
            'city'         => $settings->municipality ?? '',
            'province'     => $settings->province ?? '',
            'captain'      => $settings->captain_name ?? '',
            'captain_signature' => $settings->captain_signature
                                ? '<img src="' . asset('storage/' . str_replace('public/', '', $settings->captain_signature)) . '" style="max-height:60px;" alt="Signature">'
                                : '',
            'issued_by'    => auth()->user()?->name ?? '',
            'or_number'    => $request?->or_number ?? '',
            'doc_number'   => $request?->control_number ?? '',
            'purpose'      => $request?->purpose ?? '',
        ];
    }

    // Replace {{ tag }} placeholders in $text using the base map (+ optional extra tags).
    public static function resolvePlaceholders(string $text, ?Citizen $citizen, array $extra = []): string
    {
        $map = array_merge(self::basePlaceholderMap($citizen), $extra);

        foreach ($map as $key => $value) {
            $text = preg_replace_callback(
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                fn () => $value ?? '',
                $text
            );
        }

        return $text;
    }

    // Resolve {{ placeholders }} against citizen + custom fields + barangay settings
    public function resolveTemplate(): string
    {
        $citizen = $this->citizen;
        $custom  = $this->custom_fields ?? [];
        $map     = array_merge(self::basePlaceholderMap($citizen, $this), $custom);

        // Use the per-request edited body when one was saved (types that allow
        // editing), otherwise fall back to the document type's template.
        $body = ($this->body_override !== null && $this->body_override !== '')
            ? $this->body_override
            : ($this->documentType->template_body ?? '');

        foreach ($map as $key => $value) {
            $body = preg_replace_callback(
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                fn () => $value ?? '',
                $body
            );
        }

        // Handle {{ profile_photo [width,height] }} with custom dimensions
        // e.g. {{ profile_photo [80,100] }} or {{ profile_photo [120] }} (square)
        if ($citizen?->profile) {
            $src = asset(str_replace('public/', 'storage/', $citizen->profile));
            $body = preg_replace_callback(
                '/\{\{\s*profile_photo\s*\[(\d+)(?:,(\d+))?\]\s*\}\}/',
                function ($m) use ($src) {
                    $w = $m[1];
                    $h = $m[2] ?? $m[1]; // square if only one value given
                    return "<img src=\"{$src}\" width=\"{$w}\" height=\"{$h}\" style=\"object-fit:cover;border-radius:4px;\">";
                },
                $body
            );
        } else {
            // Remove unresolved profile_photo size tags when no photo exists
            $body = preg_replace('/\{\{\s*profile_photo\s*\[\d+(?:,\d+)?\]\s*\}\}/', '', $body);
        }

        // Handle {{ qr_image }} or {{ qr_image [size] }} — renders a real, scannable
        // QR code from the citizen's qrcode value (default 100px).
        $qrValue = $citizen?->qrcode;
        if ($qrValue) {
            $body = preg_replace_callback(
                '/\{\{\s*qr_image\s*(?:\[(\d+)\])?\s*\}\}/',
                function ($m) use ($qrValue) {
                    $px  = (int) ($m[1] ?? 100);
                    // Local QR (no internet) as an inline SVG data URI.
                    $uri = \App\Support\Qr::svgDataUri($qrValue, $px);
                    return "<img src=\"{$uri}\" width=\"{$px}\" height=\"{$px}\" style=\"display:inline-block;\" alt=\"QR\">";
                },
                $body
            );
        } else {
            $body = preg_replace('/\{\{\s*qr_image\s*(?:\[\d+\])?\s*\}\}/', '', $body);
        }

        // Handle {{ captain_signature [width,height] }} with custom dimensions.
        // e.g. {{ captain_signature [150,60] }} or {{ captain_signature [150] }}
        // (width only — height auto to keep the aspect ratio). The plain
        // {{ captain_signature }} tag (fixed size) is handled by the base map above.
        $settings = Setting::instance();
        $sigPath  = $settings->captain_signature;
        if ($sigPath) {
            $sigSrc = asset('storage/' . str_replace('public/', '', $sigPath));
            $body = preg_replace_callback(
                '/\{\{\s*captain_signature\s*\[(\d+)(?:,(\d+))?\]\s*\}\}/',
                function ($m) use ($sigSrc) {
                    $w = $m[1];
                    // If a height is given, use it; otherwise keep aspect ratio.
                    $sizeAttrs = isset($m[2])
                        ? "width=\"{$w}\" height=\"{$m[2]}\""
                        : "width=\"{$w}\" height=\"auto\"";
                    return "<img src=\"{$sigSrc}\" {$sizeAttrs} style=\"object-fit:contain;display:inline-block;\" alt=\"Signature\">";
                },
                $body
            );
        } else {
            $body = preg_replace('/\{\{\s*captain_signature\s*\[\d+(?:,\d+)?\]\s*\}\}/', '', $body);
        }

        return $body;
    }
}
