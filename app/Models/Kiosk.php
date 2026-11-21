<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Kiosk helpers. The kiosk is any device logged in with an account that has
 * kiosk.use — there is no device pairing. The eb_kiosks table only remains so
 * requests made from the old paired devices still show that device's name.
 */
class Kiosk extends Model
{
    protected $table = 'eb_kiosks';

    protected $guarded = ['*'];

    /** Session flag: this browser opened the kiosk screen (registration pages then offer "back to kiosk"). */
    const SESSION = 'on_kiosk';

    /** Is this request from the kiosk screen (a kiosk account, or a browser that opened the kiosk)? */
    public static function onKiosk(Request $request): bool
    {
        $user = $request->user();
        if (!$user || !$user->can('kiosk.use')) return false;
        return $user->hasRole('Kiosk') || $request->session()->get(self::SESSION, false);
    }

    /** The registration link residents use when they tap Register on this kiosk account (made on first use). */
    public static function registrationLinkFor(User $user): RegistrationToken
    {
        $label = 'Kiosk: ' . $user->name;
        return RegistrationToken::where('created_by', $user->id)->where('label', $label)->where('is_active', true)->latest('id')->first()
            ?? RegistrationToken::create([
                'token'      => RegistrationToken::newToken(),
                'label'      => $label,
                'is_active'  => true,
                'created_by' => $user->id,
            ]);
    }

    /** Next queue number for today, across all kiosks: K-001, K-002… Call inside a transaction. */
    public static function nextQueueNo(): string
    {
        $n = DocumentRequest::where('source', 'kiosk')
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count();
        return 'K-' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT);
    }
}
