<?php

namespace App\Notifications;

use App\Models\CitizenUpdateRequest;
use Illuminate\Notifications\Notification;

/** Bell notification: a resident asked at the kiosk to change their details. */
class CitizenUpdateRequested extends Notification
{
    public function __construct(public CitizenUpdateRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $r = $this->request;
        $labels = collect(array_keys($r->changes))->map(fn($f) => strtolower(CitizenUpdateRequest::FIELDS[$f] ?? $f))->implode(', ');

        return [
            'kind'              => 'citizen_update',
            'update_request_id' => $r->id,
            'title'             => 'Detail update from the kiosk',
            'message'           => trim(($r->citizen?->fname ?? '') . ' ' . ($r->citizen?->lname ?? '')) . " wants to update: {$labels}.",
            'icon'              => 'mgc_edit_line',
            'url'               => route('citizens.updates.index'),
        ];
    }

    public static function markHandled(int $id): void
    {
        \Illuminate\Notifications\DatabaseNotification::query()
            ->where('type', static::class)
            ->whereNull('read_at')
            ->where('data', 'like', '%"update_request_id":' . $id . ',%')
            ->update(['read_at' => now()]);
    }
}
