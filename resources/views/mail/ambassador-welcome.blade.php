@php
    $category = \App\Enums\VolunteerCategory::tryFrom((string) $record->volunteer_category)?->label();
    $location = collect([$record->ward?->name, $record->lga?->name, $record->state?->name])->filter()->implode(' · ');
    $homeUrl = url('/');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Welcome to TMG</title>
</head>
<body style="margin:0;padding:0;background:#f1eeee;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0c0a0a;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#f1eeee;">
        Your TMG Ambassador reference is {{ $record->reference }}. Welcome to the movement.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1eeee;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 14px 40px rgba(12,10,10,0.10);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#e11b22,#8f0a10);padding:30px 32px;">
                            <div style="font-size:11px;font-weight:800;letter-spacing:0.20em;text-transform:uppercase;color:rgba(255,255,255,0.82);">Tinubu Must Go</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:900;line-height:1.2;color:#ffffff;">You are now a TMG Ambassador</div>
                            <div style="margin-top:6px;font-size:14px;font-weight:700;color:rgba(255,255,255,0.9);">Volunteer to Save Nigeria.</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $record->full_name }},</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.75;color:#4a4242;">
                                Thank you for stepping forward. Your registration is confirmed, and you are now part of the
                                TMG Ambassador Programme — the volunteers who organise at polling-unit and ward level to
                                protect the vote and turnout our people.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0c0a0a;border-radius:16px;">
                                <tr>
                                    <td style="padding:22px 24px;">
                                        <div style="font-size:11px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.60);">Your member reference</div>
                                        <div style="margin-top:10px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:25px;font-weight:800;letter-spacing:0.04em;color:#ffffff;">{{ $record->reference }}</div>
                                        <div style="margin-top:10px;font-size:12px;line-height:1.6;color:rgba(255,255,255,0.62);">
                                            Keep this reference — it identifies you at polling-unit and ward level.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;border:1px solid #e6e0e0;border-radius:16px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:11px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#6d6464;">Your registration</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;font-size:14px;line-height:1.7;color:#0c0a0a;">
                                            @if ($location !== '')
                                                <tr>
                                                    <td style="padding:3px 0;color:#6d6464;width:150px;">Ward / LGA / State</td>
                                                    <td style="padding:3px 0;font-weight:600;">{{ $location }}</td>
                                                </tr>
                                            @endif
                                            @if ($record->pollingUnit?->name)
                                                <tr>
                                                    <td style="padding:3px 0;color:#6d6464;width:150px;">Polling unit</td>
                                                    <td style="padding:3px 0;font-weight:600;">{{ $record->pollingUnit->name }}</td>
                                                </tr>
                                            @endif
                                            @if ($category)
                                                <tr>
                                                    <td style="padding:3px 0;color:#6d6464;width:150px;">Volunteer category</td>
                                                    <td style="padding:3px 0;font-weight:600;">{{ $category }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 12px;font-size:15px;font-weight:800;">As a TMG Ambassador, you will:</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;line-height:1.7;color:#4a4242;">
                                @foreach ([
                                    "Support TMG's chosen candidate, Atiku Abubakar.",
                                    'Mobilise and campaign at polling-unit and ward level.',
                                    'Recruit and organise other TMG volunteers.',
                                    'Encourage eligible voters to turn out and vote.',
                                ] as $duty)
                                    <tr>
                                        <td width="18" valign="top" style="padding:4px 0;color:#e11b22;font-weight:900;">&bull;</td>
                                        <td style="padding:4px 0;">{{ $duty }}</td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:30px;">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#e11b22,#8f0a10);border-radius:999px;">
                                        <a href="{{ $homeUrl }}" style="display:inline-block;padding:14px 30px;font-size:14px;font-weight:800;color:#ffffff;text-decoration:none;">Open the mission space</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 32px 28px;border-top:1px solid #e6e0e0;background:#faf8f8;">
                            <p style="margin:0;font-size:12px;line-height:1.7;color:#6d6464;">
                                You received this because you registered as a TMG Ambassador. If this was not you, please ignore this email.
                            </p>
                            <p style="margin:8px 0 0;font-size:12px;line-height:1.7;color:#6d6464;">
                                Privacy requests: <a href="mailto:privacy@tinubumustgo.org" style="color:#8f0a10;text-decoration:underline;">privacy@tinubumustgo.org</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
