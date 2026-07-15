<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'eb_document_types';

    protected $fillable = [
        'name',
        'short_name',
        'description',
        'is_paid',
        'fee',
        'requires_approval',
        'document_template_id',
        'document_template_version_id',
        'template_body',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'requires_approval' => 'boolean',
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

    public function getFeeDisplayAttribute(): string
    {
        return $this->is_paid ? '₱ ' . number_format($this->fee, 2) : 'FREE';
    }
}
