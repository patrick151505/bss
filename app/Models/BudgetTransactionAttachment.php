<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetTransactionAttachment extends Model
{
    protected $table = 'eb_budget_transaction_attachments';

    protected $fillable = [
        'transaction_id', 'original_name', 'stored_name',
        'mime_type', 'size', 'uploaded_by',
    ];

    protected $casts = ['size' => 'integer'];

    public function transaction()
    {
        return $this->belongsTo(BudgetTransaction::class, 'transaction_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function storagePath(): string
    {
        return storage_path("app/budget-attachments/{$this->transaction_id}/{$this->stored_name}");
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function iconClass(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'pdf')   => 'mgc_pdf_line text-danger',
            str_contains($this->mime_type, 'image') => 'mgc_pic_line text-success',
            str_contains($this->mime_type, 'word')  => 'mgc_word_line text-primary',
            str_contains($this->mime_type, 'excel') || str_contains($this->mime_type, 'spreadsheet') => 'mgc_excel_line text-success',
            default                                  => 'mgc_file_line text-gray-400',
        };
    }
}
