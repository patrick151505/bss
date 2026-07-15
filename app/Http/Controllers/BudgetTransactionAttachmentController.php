<?php

namespace App\Http\Controllers;

use App\Models\BudgetLog;
use App\Models\BudgetTransaction;
use App\Models\BudgetTransactionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BudgetTransactionAttachmentController extends Controller
{
    const ALLOWED_MIMES = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif',
        'doc', 'docx', 'xls', 'xlsx',
    ];

    const MAX_SIZE_KB = 10240; // 10 MB

    public function store(Request $request, BudgetTransaction $transaction)
    {
        $request->validate([
            'files'   => 'required|array|max:10',
            'files.*' => 'required|file|max:' . self::MAX_SIZE_KB . '|mimes:' . implode(',', self::ALLOWED_MIMES),
        ], [
            'files.required'  => 'Please select at least one file to upload.',
            'files.*.file'    => 'One or more of the selected files is invalid.',
            'files.*.max'     => 'Each file must be no larger than 10 MB.',
            'files.*.mimes'   => 'Allowed file types: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX.',
        ]);

        $dir = "budget-attachments/{$transaction->id}";

        foreach ($request->file('files') as $file) {
            $ext        = $file->getClientOriginalExtension();
            $stored     = Str::uuid() . '.' . $ext;

            $file->storeAs($dir, $stored, 'local');

            BudgetTransactionAttachment::create([
                'transaction_id' => $transaction->id,
                'original_name'  => $file->getClientOriginalName(),
                'stored_name'    => $stored,
                'mime_type'      => $file->getMimeType(),
                'size'           => $file->getSize(),
                'uploaded_by'    => auth()->id(),
            ]);
        }

        $count = count($request->file('files'));
        BudgetLog::record('uploaded', 'attachment', $transaction->id,
            "{$count} file(s) attached to transaction #{$transaction->id}."
        );

        return back()->with('success', "{$count} file(s) uploaded successfully.");
    }

    public function download(BudgetTransactionAttachment $attachment)
    {
        $path = $attachment->storagePath();

        abort_if(! file_exists($path), 404, 'File not found.');

        return response()->download($path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(BudgetTransactionAttachment $attachment)
    {
        $path = $attachment->storagePath();

        if (file_exists($path)) {
            unlink($path);
        }

        BudgetLog::record('removed', 'attachment', $attachment->transaction_id,
            "Attachment '{$attachment->original_name}' removed from transaction #{$attachment->transaction_id}."
        );
        $attachment->delete();

        return back()->with('success', "Attachment '{$attachment->original_name}' removed.");
    }
}
