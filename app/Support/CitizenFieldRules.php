<?php

namespace App\Support;

use App\Models\Citizen;
use App\Models\Setting;

/**
 * Settings → Citizen Fields: which fields of the citizen forms (Add, Edit,
 * Register Minor) are required, and which must be unique across all records.
 *
 * Stored on eb_settings.citizen_field_rules as
 *   {"required": [...], "unique": [...], "dup_middle_name": bool}
 * Empty / missing = the defaults below, which match how the forms worked
 * before this setting existed.
 */
class CitizenFieldRules
{
    /** Always required (the database needs them, or the record makes no sense without them). */
    public const LOCKED = [
        'fname'        => 'First name',
        'lname'        => 'Last name',
        'gender'       => 'Gender',
        'bday'         => 'Birthday',
        'bplace'       => 'Place of birth',
        'civil_status' => 'Civil status',
        'address'      => 'Zone / address',
    ];

    /**
     * Configurable fields.
     * name => [label, group, required by default, can be made unique, on the Register Minor form]
     */
    public const FIELDS = [
        'mname'           => ['Middle name',                 'Personal',          false, false, true],
        'suffix'          => ['Suffix (Jr., Sr., III)',      'Personal',          false, false, true],
        'citizenship'     => ['Citizenship',                 'Personal',          true,  false, true],
        'occupation'      => ['Occupation',                  'Personal',          false, false, false],
        'profile'         => ['Photo',                       'Personal',          false, false, true],
        'year_stay'       => ['Year of stay (resident since)', 'Residence',       true,  false, false],
        'contact'         => ['Contact no.',                 'Contact',           false, true,  false],
        'email'           => ['Email',                       'Contact',           false, true,  false],
        'ic_fullname'     => ['Emergency contact — name',    'Emergency contact', false, false, false],
        'ic_relationship' => ['Emergency contact — relationship', 'Emergency contact', false, false, false],
        'ic_contact'      => ['Emergency contact — contact no.',  'Emergency contact', false, false, false],
        'ic_address'      => ['Emergency contact — address', 'Emergency contact', false, false, false],
        'ic_email'        => ['Emergency contact — email',   'Emergency contact', false, false, false],
    ];

    private static ?array $memo = null;

    public static function config(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        $saved = Setting::instance()->citizen_field_rules;
        $saved = is_array($saved) ? $saved : [];

        $defaults = array_keys(array_filter(self::FIELDS, fn ($f) => $f[2]));
        $uniqueOk = array_keys(array_filter(self::FIELDS, fn ($f) => $f[3]));

        return self::$memo = [
            'required'        => array_values(array_intersect($saved['required'] ?? $defaults, array_keys(self::FIELDS))),
            'unique'          => array_values(array_intersect($saved['unique'] ?? [], $uniqueOk)),
            'dup_middle_name' => (bool) ($saved['dup_middle_name'] ?? false),
        ];
    }

    public static function flush(): void
    {
        self::$memo = null;
    }

    public static function required(string $field): bool
    {
        return isset(self::LOCKED[$field]) || in_array($field, self::config()['required'], true);
    }

    public static function unique(string $field): bool
    {
        return in_array($field, self::config()['unique'], true);
    }

    public static function matchMiddleName(): bool
    {
        return self::config()['dup_middle_name'];
    }

    /** Fields that appear on the given form ('citizen' = Add / Edit, 'minor' = Register Minor). */
    public static function fieldsFor(string $form): array
    {
        return array_keys(array_filter(self::FIELDS, fn ($f) => $form !== 'minor' || $f[4]));
    }

    /**
     * Apply the settings to a form's validation rules.
     * $citizen = the record being edited (unique checks skip it; a required
     * photo is satisfied by the one already on file).
     */
    public static function apply(array $rules, string $form = 'citizen', ?Citizen $citizen = null): array
    {
        foreach (self::fieldsFor($form) as $field) {
            $rule = $rules[$field] ?? [];
            $rule = is_array($rule) ? $rule : explode('|', $rule);
            $rule = array_values(array_filter($rule, fn ($r) => !is_string($r) || !in_array($r, ['required', 'nullable'], true)));

            $required = self::required($field);
            if ($field === 'profile' && $citizen && $citizen->profile) {
                $required = false;                    // already has a photo
            }
            array_unshift($rule, $required ? 'required' : 'nullable');

            if (self::unique($field)) {
                $rule[] = self::uniqueRule($field, $citizen);
            }
            $rules[$field] = $rule;
        }

        return $rules;
    }

    /** Friendly names for the error messages ("Emergency contact — name is required"). */
    public static function attributes(): array
    {
        return array_map(fn ($f) => $f[0], self::FIELDS) + self::LOCKED;
    }

    /** Field names that are required on this form, for the asterisks. */
    public static function requiredOnForm(string $form): array
    {
        return array_values(array_filter(self::fieldsFor($form), fn ($f) => self::required($f)));
    }

    /** How many values are already shared by 2+ citizens, per unique-able field (shown in Settings). */
    public static function existingRepeats(): array
    {
        $out = [];
        foreach (self::FIELDS as $field => $f) {
            if (!$f[3]) {
                continue;
            }
            $out[$field] = \Illuminate\Support\Facades\DB::table('eb_citizen')
                ->whereNotNull($field)->where($field, '!=', '')
                ->select($field)->groupBy($field)->havingRaw('COUNT(*) > 1')
                ->get()->count();
        }
        return $out;
    }

    private static function uniqueRule(string $field, ?Citizen $citizen): \Closure
    {
        return function ($attribute, $value, $fail) use ($field, $citizen) {
            $value = trim((string) $value);
            if ($value === '') {
                return;
            }
            // Editing without changing it: leave old shared values alone.
            if ($citizen && trim((string) $citizen->{$field}) === $value) {
                return;
            }
            $other = Citizen::withoutGlobalScopes()
                ->where($field, $value)
                ->when($citizen, fn ($q) => $q->where('id', '!=', $citizen->id))
                ->first(['id', 'fname', 'lname', 'is_active', 'is_walkin']);
            if ($other) {
                $label = mb_strtolower(self::FIELDS[$field][0]);
                $where = !$other->is_active ? ' (archived)' : ($other->is_walkin ? ' (walk-in)' : '');
                $fail("This {$label} is already used by {$other->lname}, {$other->fname} ("
                    . Setting::instance()->formatCitizenId($other->id) . "){$where}. Each citizen must have their own.");
            }
        };
    }
}
