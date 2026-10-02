<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Heaven Gate Camp</title></head>
<body style="margin:0;background:#F3EADC;font-family:{{ app()->getLocale() === 'ar' ? "'IBM Plex Sans Arabic'," : (app()->getLocale() === 'he' ? 'Assistant,' : '') }}Helvetica,Arial,sans-serif;color:#1D1A16;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EADC;padding:32px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FAF6EF;border-radius:14px;overflow:hidden;">
    <tr><td style="background:#0B1424;padding:36px 32px;text-align:center;">
        <div style="font-family:Georgia,serif;font-size:28px;color:#E2C29C;letter-spacing:1px;">✦ Heaven Gate</div>
        <div style="font-size:11px;letter-spacing:4px;color:#B8875A;margin-top:6px;">CAMP · NUWEIBA</div>
    </td></tr>
    <tr><td style="padding:36px 32px;">@yield('body')</td></tr>
    <tr><td style="padding:24px 32px;border-top:1px solid #E8D9C2;font-size:12px;color:#8F877C;text-align:center;">
        {{ __('mail.questions') }}<br>{{ setting('contact_phone') }} · {{ setting('contact_email') }}<br>{{ setting('address') }}
    </td></tr>
</table>
</td></tr></table>
</body></html>
