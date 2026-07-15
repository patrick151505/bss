<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $table = 'eb_document_templates';

    protected $fillable = [
        'name',
        'description',
        'paper_size',
        'orientation',
        'is_active',
        'current_version_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions()
    {
        return $this->hasMany(DocumentTemplateVersion::class, 'document_template_id')
                    ->orderByDesc('version');
    }

    public function currentVersion()
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'current_version_id');
    }

    public function documentTypes()
    {
        return $this->hasMany(DocumentType::class, 'document_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Convenience accessors that delegate to current version
    public function getPaperBgAttribute(): ?string
    {
        return $this->currentVersion?->paper_bg;
    }

    public function getPaperBgUrlAttribute(): ?string
    {
        $bg = $this->currentVersion?->paper_bg;
        return $bg ? asset('storage/' . $bg) : null;
    }

    public function getPaddingTopAttribute(): int
    {
        return $this->currentVersion?->padding_top ?? 160;
    }

    public function getPaddingBottomAttribute(): int
    {
        return $this->currentVersion?->padding_bottom ?? 72;
    }

    public function getPaddingLeftAttribute(): int
    {
        return $this->currentVersion?->padding_left ?? 72;
    }

    public function getPaddingRightAttribute(): int
    {
        return $this->currentVersion?->padding_right ?? 72;
    }

    public function getPaddingStyleAttribute(): string
    {
        return "{$this->padding_top}px {$this->padding_right}px {$this->padding_bottom}px {$this->padding_left}px";
    }
}
