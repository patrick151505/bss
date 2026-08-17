<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'eb_document_types';

    protected $fillable = [
        'name',
        'short_name',
        'prefix',
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
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'requires_approval' => 'boolean',
        'allow_body_edit'   => 'boolean',
        'is_active'         => 'boolean',
        'fee'               => 'decimal:2',
    ];

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
