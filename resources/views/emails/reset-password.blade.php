{{-- "Set a new password" email. Table layout + inline styles so it looks right in Gmail, Outlook and phone mail apps. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Set a new password</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
{{-- Inbox preview line --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Use the button inside to set a new password for your {{ $bName }} account. The link works for {{ $minutes }} minutes.</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;">
<tr><td align="center" style="padding:32px 16px;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
        {{-- Header --}}
        <tr><td style="background:#3e60d5;background-image:linear-gradient(135deg,#3e60d5,#6b5eae);border-radius:16px 16px 0 0;padding:28px 32px;" align="center">
            @if($logo)
            <img src="{{ $logo }}" alt="" width="64" height="64" style="display:block;width:64px;height:64px;border-radius:50%;background:#fff;margin:0 auto 12px;object-fit:cover;">
            @endif
            <div style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-.01em;">{{ $bName }}</div>
            @if($place)<div style="color:#dbe3ff;font-size:13px;margin-top:2px;">{{ $place }}</div>@endif
        </td></tr>

        {{-- Body --}}
        <tr><td style="background:#ffffff;padding:36px 32px 28px;">
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                <tr><td align="center" style="width:56px;height:56px;border-radius:50%;background:#eef2ff;font-size:26px;line-height:56px;">🔑</td></tr>
            </table>

            <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;text-align:center;color:#0f172a;">Set a new password</h1>

            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Hello {{ $user->name }},</p>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;">
                @if($sentBy)
                    <strong>{{ $sentBy }}</strong> started a password reset for your account
                    (<span style="color:#3e60d5;">{{ $user->email }}</span>). Click the button below to choose a new password.
                @else
                    We got a request to reset the password for your account
                    (<span style="color:#3e60d5;">{{ $user->email }}</span>). Click the button below to choose a new password.
                @endif
            </p>

            {{-- Button (bulletproof: works in Outlook too) --}}
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr><td align="center" bgcolor="#3e60d5" style="border-radius:10px;">
                    <a href="{{ $url }}" target="_blank"
                       style="display:inline-block;padding:14px 34px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;background:#3e60d5;">
                        Set new password
                    </a>
                </td></tr>
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                <tr><td style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;font-size:13px;line-height:1.5;color:#9a3412;">
                    ⏱ This link works for <strong>{{ $minutes }} minutes</strong> and can be used once.
                    After that, ask your administrator for a new one.
                </td></tr>
            </table>

            <p style="margin:0 0 6px;font-size:13px;color:#64748b;">Button not working? Copy this link into your browser:</p>
            <p style="margin:0 0 24px;font-size:12px;line-height:1.5;word-break:break-all;"><a href="{{ $url }}" style="color:#3e60d5;">{{ $url }}</a></p>

            <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 18px;">
            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                Didn't expect this email? You can ignore it — your password stays the same until you set a new one.
                Never share this link with anyone.
            </p>
        </td></tr>

        {{-- Footer --}}
        <tr><td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center;font-size:12px;line-height:1.6;color:#94a3b8;">
            {{ $bName }}@if($place) · {{ $place }}@endif<br>
            @if($contact){{ $contact }}@endif @if($contact && $email) · @endif @if($email){{ $email }}@endif
            @if($contact || $email)<br>@endif
            This is an automatic message. Please don't reply.
        </td></tr>
    </table>

</td></tr>
</table>
</body>
</html>
