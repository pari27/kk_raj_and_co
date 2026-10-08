<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body style="margin:0; padding:0; background:#f4f5f7; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background:#101b3d; padding: 20px 28px;">
                            <span style="color:#ffffff; font-size: 18px; font-weight: bold;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px;">
                            <p style="font-size: 16px; color:#111; margin: 0 0 16px;">Hi {{ $employee->name }},</p>
                            <p style="font-size: 14px; color:#444; line-height: 1.6; margin: 0 0 20px;">
                                An account has been created for you on {{ config('app.name') }}. Click the button below to set your password and sign in.
                            </p>
                            <p style="text-align: center; margin: 28px 0;">
                                <a href="{{ $setPasswordUrl }}" style="background:#4a9b3e; color:#ffffff; text-decoration:none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: bold; display:inline-block;">Set your password</a>
                            </p>
                            <p style="font-size: 12px; color:#888; line-height: 1.6; margin: 0;">
                                This link will expire in 7 days. If you weren't expecting this email, you can ignore it.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
