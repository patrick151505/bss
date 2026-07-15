<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplateVersion extends Model
{
    protected $table = 'eb_document_template_versions';

    protected $fillable = [
        'document_template_id',
        'version',
        'paper_bg',
        'paper_size',
        'orientation',
        'padding_top',
        'padding_bottom',
        'padding_left',
        'padding_right',
        'change_note',
        'created_by',
    ];

    protected $casts = [
        'padding_top'    => 'integer',
        'padding_bottom' => 'integer',
        'padding_left'   => 'integer',
        'padding_right'  => 'integer',
        'version'        => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests()
    {
        return $this->hasMany(DocumentRequest::class, 'template_version_id');
    }

    // A version is "used" once at least one document request has been created
    // against it — only then must further paper edits create a new version
    // instead of updating this one in place.
    public function isUsed(): bool
    {
        return $this->requests()->exists();
    }

    public function getPaperBgUrlAttribute(): ?string
    {
        return $this->paper_bg ? asset('storage/' . $this->paper_bg) : null;
    }

    public function getPaddingStyleAttribute(): string
    {
        return "{$this->padding_top}px {$this->padding_right}px {$this->padding_bottom}px {$this->padding_left}px";
    }
}
