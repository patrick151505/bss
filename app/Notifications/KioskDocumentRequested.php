<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Illuminate\Notifications\Notification;

/**
 * Bell notification for staff who can approve document requests when a resident
 * requests a document at the kiosk. Marked read for everyone once handled.
 */
class KioskDocumentRequested extends Notification
{
    public function __construct(public DocumentRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $r   = $this->request;
        $fee = $r->is_paid ? '₱' . number_format((float) $r->fee, 2) : 'free';

        return [
            'kind'       => 'kiosk_document',
            'request_id' => $r->id,
            'title'      => "Kiosk request {$r->queue_no}",
            'message'    => trim(($r->citizen?->fname ?? '') . ' ' . ($r->citizen?->lname ?? ''))
                . " — {$r->documentType?->name} ({$fee}). Check and approve.",
            'icon'       => 'mgc_computer_line',
            'url'        => route('documents.requests.show', $r->id),
        ];
    }

    /** Mark this request's notification read for every staff member (it has been handled). */
    public static function markHandled(int $requestId): void
    {
        \Illuminate\Notifications\DatabaseNotification::query()
            ->where('type', static::class)
            ->whereNull('read_at')
            ->where('data', 'like', '%"request_id":' . $requestId . ',%')
            ->update(['read_at' => now()]);
    }
}
