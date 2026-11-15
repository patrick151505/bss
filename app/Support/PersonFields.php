<?php

namespace App\Support;

use App\Models\Citizen;
use App\Models\CivilStatus;
use App\Models\DocumentType;

/**
 * Which personal details a document type needs, read from the {{ placeholders }}
 * in its wording and in its custom fields' defaults. Used to ask a walk-in only
 * for what the certificate will print, and to warn when a registered citizen's
 * record is missing something the certificate needs.
 */
class PersonFields
{
    /**
     * Fields a person record can supply. Name and birthday are always asked of a
     * walk-in (needed to recognise them on their next visit), so they're not here.
     * key = eb_citizen column; 'tags' = placeholders that need it.
     */
    const FIELDS = [
        'bplace'           => ['label' => 'Birthplace',       'input' => 'text',   'tags' => ['birthplace']],
        'gender'           => ['label' => 'Gender',           'input' => 'select', 'tags' => ['gender']],
        'civil_status'     => ['label' => 'Civil status',     'input' => 'select', 'tags' => ['civil_status']],
        'complete_address' => ['label' => 'Address',          'input' => 'text',   'tags' => ['address']],
        'contact'          => ['label' => 'Contact number',   'input' => 'text',   'tags' => ['contact']],
        'email'            => ['label' => 'Email',            'input' => 'email',  'tags' => ['email']],
        'occupation'       => ['label' => 'Occupation',       'input' => 'text',   'tags' => ['occupation']],
        'year_stay'        => ['label' => 'Resident since',   'input' => 'month',  'tags' => ['year_stay']],
    ];

    /** Placeholders that print a QR code — walk-ins have none. */
    const QR_TAGS = ['qrcode', 'qr_image'];

    /** Every placeholder name used by the type's wording (or an override) and custom-field defaults. */
    public static function tagsUsed(DocumentType $type, ?string $bodyOverride = null): array
    {
        $text = ($bodyOverride !== null && $bodyOverride !== '' ? $bodyOverride : (string) $type->template_body)
            . ' ' . $type->fields->pluck('default_value')->implode(' ');
        preg_match_all('/\{\{\s*([a-z_]+)/i', $text, $m);
        return array_values(array_unique(array_map('strtolower', $m[1])));
    }

    /** Person fields (keys of FIELDS) this document type needs. */
    public static function required(DocumentType $type, ?string $bodyOverride = null): array
    {
        $tags = self::tagsUsed($type, $bodyOverride);
        return array_keys(array_filter(self::FIELDS, fn($f) => array_intersect($f['tags'], $tags)));
    }

    public static function usesQr(DocumentType $type, ?string $bodyOverride = null): bool
    {
        return (bool) array_intersect(self::QR_TAGS, self::tagsUsed($type, $bodyOverride));
    }

    public static function isEmpty(Citizen $c, string $key): bool
    {
        $v = $c->getAttribute($key);
        return match ($key) {
            'gender', 'civil_status' => !(int) $v,
            // Most records hold the address only in its parts (Blk/Lot/zone).
            'complete_address'       => trim($c->full_address) === '',
            default                  => $v === null || trim((string) ($v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v)) === '',
        };
    }

    /** Required fields this person has no value for. */
    public static function missing(DocumentType $type, Citizen $c, ?string $bodyOverride = null): array
    {
        return array_values(array_filter(self::required($type, $bodyOverride), fn($k) => self::isEmpty($c, $k)));
    }

    /** Field definitions for the form, with select options and the person's current value. */
    public static function describe(array $keys, ?Citizen $c = null): array
    {
        $out = [];
        foreach ($keys as $key) {
            $f = self::FIELDS[$key];
            $value = $c?->getAttribute($key);
            if ($value instanceof \DateTimeInterface) $value = $value->format('Y-m');
            $out[] = [
                'key'     => $key,
                'label'   => $f['label'],
                'input'   => $f['input'],
                'options' => match ($key) {
                    'gender'       => [['value' => 1, 'label' => 'Male'], ['value' => 2, 'label' => 'Female']],
                    'civil_status' => CivilStatus::orderBy('id')->get()->map(fn($s) => ['value' => $s->id, 'label' => $s->description])->all(),
                    default        => null,
                },
                'value'   => $c && !self::isEmpty($c, $key) ? $value : null,
                'missing' => !$c || self::isEmpty($c, $key),
            ];
        }
        return $out;
    }

    /** Validation rules for submitted person[...] values. */
    public static function rules(array $keys): array
    {
        $rules = [];
        foreach ($keys as $key) {
            $rules["person.$key"] = match ($key) {
                'gender'       => 'nullable|in:1,2',
                'civil_status' => 'nullable|integer|exists:eb_civil,id',
                'email'        => 'nullable|email|max:255',
                'year_stay'    => 'nullable|date_format:Y-m',
                'contact'      => 'nullable|string|max:20',
                default        => 'nullable|string|max:500',
            };
        }
        return $rules;
    }

    /** Copy submitted person[...] values onto the record (not saved). */
    public static function fill(Citizen $c, array $keys, array $input): void
    {
        foreach ($keys as $key) {
            $v = $input[$key] ?? null;
            if ($v === null || trim((string) $v) === '') continue;
            $c->setAttribute($key, $key === 'year_stay' ? $v . '-01' : trim((string) $v));
        }
    }
}
