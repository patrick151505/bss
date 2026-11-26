<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'eb_document_types';

    /**
     * Preset colors offered when creating/editing a type. Chosen to be easy
     * to tell apart on charts and readable in light and dark mode.
     */
    public const PALETTE = [
        '#727cf5', // indigo
        '#0acf97', // green
        '#fa5c7c', // red
        '#ffbc00', // amber
        '#39afd1', // sky
        '#6b5eae', // purple
        '#fd7e14', // orange
        '#ff679b', // pink
        '#02a8b5', // teal
        '#8bc34a', // lime
        '#795548', // brown
        '#607d8b', // slate
        '#1e40af', // navy
        '#be185d', // raspberry
        '#a16207', // ochre
        '#9333ea', // violet
    ];

    protected $fillable = [
        'name',
        'short_name',
        'prefix',
        'color',
        'description',
        'is_paid',
        'fee',
        'requires_approval',
        'document_template_id',
        'document_template_version_id',
        'template_body',
        'allow_body_edit',
        'is_active',
        'sort_order',
        'allow_walkin',
        'allow_kiosk',
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'requires_approval' => 'boolean',
        'allow_body_edit'   => 'boolean',
        'allow_walkin'      => 'boolean',
        'allow_kiosk'       => 'boolean',
        'is_active'         => 'boolean',
        'fee'               => 'decimal:2',
    ];

    /** The type's color, falling back to the primary color if none is set. */
    public function getBadgeColorAttribute(): string
    {
        return $this->color ?: '#727cf5';
    }

    /**
     * First preset color not used by another type; once the palette is
     * exhausted, a generated color that is also unused.
     */
    public static function nextAvailableColor(?int $exceptId = null): string
    {
        $used = static::query()
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->whereNotNull('color')->pluck('color')
            ->map(fn($c) => strtolower($c))->all();

        foreach (self::PALETTE as $color) {
            if (! in_array($color, $used, true)) {
                return $color;
            }
        }

        for ($i = count(self::PALETTE); ; $i++) {
            $color = self::generatedColor($i);
            if (! in_array($color, $used, true)) {
                return $color;
            }
        }
    }

    /** A distinct hue for index $i (golden-angle spacing), as #rrggbb. */
    public static function generatedColor(int $i): string
    {
        $h = fmod($i * 137.508, 360) / 360;
        [$s, $l] = [0.62, 0.52];
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $rgb = array_map(function ($t) use ($p, $q) {
            $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);
            $v = $t < 1 / 6 ? $p + ($q - $p) * 6 * $t
               : ($t < 1 / 2 ? $q
               : ($t < 2 / 3 ? $p + ($q - $p) * (2 / 3 - $t) * 6 : $p));
            return sprintf('%02x', (int) round($v * 255));
        }, [$h + 1 / 3, $h, $h - 1 / 3]);

        return '#' . implode('', $rgb);
    }

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function pinnedVersion()
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'document_template_version_id');
    }

    public function fields()
    {
        return $this->hasMany(DocumentField::class)->orderBy('sort_order');
    }

    public function requests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * The permission name that gates who may issue/request this document type,
     * e.g. "issue_document.5". Managed on the Roles page.
     */
    public function issuePermissionName(): string
    {
        return 'issue_document.' . $this->id;
    }

    /** Roles with this may issue every document type, including types added later. */
    public const ISSUE_ALL = 'issue_document.all';

    protected static function booted(): void
    {
        // Auto-create the per-type "issue" permission when a type is created.
        static::created(function (DocumentType $type) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name'       => $type->issuePermissionName(),
                'guard_name' => 'web',
            ]);
            // Ensure Super Admin keeps every permission.
            $sa = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
            $sa?->givePermissionTo($type->issuePermissionName());
        });

        // Clean up the permission when a type is deleted.
        static::deleted(function (DocumentType $type) {
            \Spatie\Permission\Models\Permission::where('name', $type->issuePermissionName())->delete();
        });
    }

    public function getFeeDisplayAttribute(): string
    {
        return $this->is_paid ? '₱ ' . number_format($this->fee, 2) : 'FREE';
    }

    /**
     * Reserve the next sequential control number for this document type.
     * The sequence is per-type: BRG-00001, BRG-00002 … are independent of
     * other document types. Uses a locked read inside a transaction so two
     * simultaneous requests can't grab the same number.
     */
    public function allocateNextNumber(): int
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $last = DocumentRequest::where('document_type_id', $this->id)
                ->lockForUpdate()
                ->max('doc_number');

            return (int) $last + 1;
        });
    }

    /**
     * Format a sequence number into the display control number, e.g.
     * "BRG-00001". Falls back to a plain padded number when no prefix is set.
     */
    public function formatDocNumber(?int $number): string
    {
        if ($number === null) {
            return '';
        }

        $padded = str_pad((string) $number, 5, '0', STR_PAD_LEFT);

        return $this->prefix
            ? $this->prefix . '-' . $padded
            : $padded;
    }
}
