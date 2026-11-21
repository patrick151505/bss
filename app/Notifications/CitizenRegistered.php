<?php

namespace App\Notifications;

use App\Models\CitizenRegistration;
use Illuminate\Notifications\Notification;

/**
 * Shown in the top-bar bell of staff who can approve citizens when someone
 * registers online. Marked read for everyone once the registration is reviewed.
 */
class CitizenRegistered extends Notification
{
    public function __construct(public CitizenRegistration $registration) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $r = $this->registration;
        $groups = collect(['is_senior' => 'Senior', 'is_pwd' => 'PWD', 'is_soloparents' => 'Solo parent'])
            ->filter(fn($label, $key) => ($r->data[$key] ?? 0) == 1)->values()->all();

        return [
            'kind'            => 'citizen_registered',
            'registration_id' => $r->id,
            'title'           => 'New online registration',
            'message'         => trim("{$r->fname} {$r->lname}") . ' registered and is waiting for review.',
            'groups'          => $groups,
            'icon'            => 'mgc_user_add_line',
            'url'             => route('citizens.registrations.show', $r->id),
        ];
    }

    /** Mark this registration's notification read for every staff member (it has been reviewed). */
    public static function markReviewed(int $registrationId): void
    {
        \Illuminate\Notifications\DatabaseNotification::query()
            ->where('type', static::class)
            ->whereNull('read_at')
            ->where('data', 'like', '%"registration_id":' . $registrationId . ',%')
            ->update(['read_at' => now()]);
    }
}
