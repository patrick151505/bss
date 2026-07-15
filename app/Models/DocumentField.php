<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentField extends Model
{
    protected $table = 'eb_document_fields';

    protected $fillable = [
        'document_type_id',
        'field_key',
        'field_label',
        'field_type',
        'column_width',
        'field_options',
        'default_value',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required'    => 'boolean',
        'field_options'  => 'array',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
