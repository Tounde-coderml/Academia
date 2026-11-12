<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
    </head>
    <body style="margin: 0; padding: 32px 16px; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif;">
        <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 16px; background: #ffffff; border-spacing: 0;">
            <tr>
                <td style="padding: 32px;">
                    <p style="margin: 0 0 24px; color: #312e81; font-size: 14px; font-weight: bold; letter-spacing: 1px;">ACADEMIA · ESGC VAK</p>
                    <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 1.3;">{{ $title }}</h1>
                    <p style="margin: 0 0 24px; color: #334155; font-size: 16px; line-height: 1.6;">{{ $introduction }}</p>
                    <p style="margin: 0 0 24px;">
                        <a href="{{ $url }}" style="display: inline-block; border-radius: 8px; background: #312e81; padding: 14px 20px; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none;">{{ $actionLabel }}</a>
                    </p>
                    <p style="margin: 0 0 16px; color: #475569; font-size: 14px; line-height: 1.6;">{{ $instructions }}</p>
                    <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.6;">{{ $closing }}</p>
                </td>
            </tr>
        </table>
    </body>
</html>
