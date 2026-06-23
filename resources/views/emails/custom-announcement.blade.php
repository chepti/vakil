<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Rubik', Arial, sans-serif; background-color: #f0f7ff; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(59,130,246,0.12); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%); padding: 36px 32px 28px; text-align: center; }
        .header-emoji { font-size: 44px; margin-bottom: 8px; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; direction: rtl; }
        .header p { color: rgba(255,255,255,0.9); font-size: 15px; margin-top: 6px; }
        .body { padding: 32px 36px; direction: rtl; text-align: right; }
        .greeting { font-size: 17px; color: #1e3a5f; font-weight: 600; margin-bottom: 20px; }
        .content { font-size: 15px; color: #374151; line-height: 1.75; white-space: pre-line; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">🌳</div>
            <h1>{{ $mailSubject }}</h1>
            <p>{{ config('app.name') }}</p>
        </div>

        <div class="body">
            <p class="greeting">
                @if ($recipientName) שלום {{ $recipientName }}, @else שלום, @endif
            </p>
            <div class="content">{{ $body }}</div>
        </div>

        <div class="footer">
            קיבלת מייל זה כי הינך רשום/ה באתר {{ config('app.name') }}.<br>
            <a href="{{ $profileUrl }}">לפרופיל שלי</a>
        </div>
    </div>
</body>
</html>
