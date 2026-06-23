<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>דמות חדשה נוספה לעץ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Rubik', Arial, sans-serif; background-color: #f0f7ff; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(59,130,246,0.12); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%); padding: 36px 32px 28px; text-align: center; }
        .header-emoji { font-size: 44px; margin-bottom: 8px; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; }
        .body { padding: 32px 36px; direction: rtl; text-align: right; }
        .text { font-size: 15px; color: #374151; line-height: 1.7; margin-bottom: 14px; direction: rtl; text-align: right; }
        .person-card { display: block; background: #eff6ff; border-radius: 12px; padding: 18px; margin: 18px 0; text-align: center; }
        .person-photo { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; display: block; }
        .person-name { font-size: 19px; font-weight: 700; color: #1e3a5f; }
        .person-meta { font-size: 13px; color: #6b7a99; margin-top: 4px; }
        .btn-wrap { text-align: center; margin: 24px 0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: 600; padding: 13px 36px; border-radius: 50px; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">🌳</div>
            <h1>דמות חדשה בעץ המשפחה</h1>
        </div>

        <div class="body">
            <p class="text">
                @if ($recipientName) שלום {{ $recipientName }}, @else שלום, @endif
                נוספה דמות חדשה לעץ המשפחה של {{ config('app.name') }}:
            </p>

            <div class="person-card">
                @if ($person->profile_photo_url)
                <img src="{{ $person->profile_photo_url }}" alt="" class="person-photo">
                @endif
                <div class="person-name">{{ $person->full_name }}</div>
                @if ($addedBy)
                <div class="person-meta">נוסף/ה על ידי {{ $addedBy }}</div>
                @endif
            </div>

            <div class="btn-wrap">
                <a href="{{ $personUrl }}" class="btn">לצפייה בכרטיס</a>
            </div>
        </div>

        <div class="footer">
            קיבלת מייל זה כי ביקשת לקבל התראה על דמויות חדשות.<br>
            לא רוצה לקבל התראות אלו? <a href="{{ $profileUrl }}">עדכן/י העדפות בפרופיל</a>.
        </div>
    </div>
</body>
</html>
