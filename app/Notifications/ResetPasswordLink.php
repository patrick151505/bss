<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The "set a new password" email, with the barangay's name and logo. Sent when
 * an admin clicks Reset password in Users, or from Forgot password.
 */
class ResetPasswordLink extends Notification
{
    /** @param string|null $sentBy  name of the admin who asked for it (null = the user asked) */
    public function __construct(public string $token, public ?string $sentBy = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function urlFor(object $notifiable, string $token): string
    {
        return route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brgy  = Setting::instance();
        $bName = $brgy->barangay_name ? 'Barangay ' . preg_replace('/^barangay\s+/i', '', $brgy->barangay_name) : config('app.name');

        return (new MailMessage)
            ->subject("Set a new password · {$bName}")
            ->view('emails.reset-password', [
                'user'    => $notifiable,
                'url'     => self::urlFor($notifiable, $this->token),
                'minutes' => (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60),
                'sentBy'  => $this->sentBy,
                'bName'   => $bName,
                'place'   => collect([$brgy->municipality, $brgy->province])->filter()->implode(', '),
                'logo'    => $brgy->logo ? asset('storage/' . preg_replace('#^public/#', '', $brgy->logo)) : null,
                'contact' => $brgy->contact,
                'email'   => $brgy->email,
            ]);
    }
}
