<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPurpose extends Model
{
    protected $table = 'eb_document_purposes';

    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
