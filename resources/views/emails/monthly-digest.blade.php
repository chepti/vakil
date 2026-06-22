<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חודש טוב — {{ $d['monthName'] }} {{ $d['yearGematria'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Rubik', Arial, sans-serif; background-color: #f0f7ff; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(59,130,246,0.12); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%); padding: 36px 32px 28px; text-align: center; }
        .header-emoji { font-size: 44px; margin-bottom: 8px; }
        .header h1 { color: #ffffff; font-size: 26px; font-weight: 700; margin-bottom: 4px; }
        .header p { color: rgba(255,255,255,0.9); font-size: 15px; }
        .body { padding: 32px 36px; direction: rtl; text-align: right; }
        .greeting { font-size: 17px; color: #1e3a5f; font-weight: 600; margin-bottom: 20px; direction: rtl; text-align: right; }
        .section { margin: 0 0 26px; direction: rtl; text-align: right; }
        .section-title { font-size: 16px; font-weight: 700; color: #1e40af; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #eff6ff; }
        .item { padding: 10px 14px; background: #f8fafc; border-radius: 10px; margin-bottom: 8px; font-size: 14px; color: #374151; line-height: 1.6; direction: rtl; text-align: right; }
        .item a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .item .meta { color: #6b7a99; font-size: 13px; }
        .badge { display: inline-block; background: #eff6ff; color: #1e40af; border-radius: 20px; padding: 2px 10px; font-size: 12px; font-weight: 600; margin-right: 6px; }
        .empty { font-size: 14px; color: #9ca3af; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">🌳</div>
            <h1>חודש טוב!</h1>
            <p>{{ $d['monthName'] }} {{ $d['yearGematria'] }} · משפחת ואקיל</p>
        </div>

        <div class="body">
            <p class="greeting">
                @if ($recipientName) שלום {{ $recipientName }}, @else שלום, @endif
                הנה מה שמתחדש במשפחה החודש 💙
            </p>

            {{-- מי נולד לאחרונה --}}
            @if (count($d['newBabies']))
            <div class="section">
                <div class="section-title">👶 נולדו לאחרונה</div>
                @foreach ($d['newBabies'] as $baby)
                <div class="item">
                    <a href="{{ $baby['url'] }}">{{ $baby['name'] }}</a>
                    <span class="meta"> · נולד/ה ב{{ $baby['hebrewBirth'] }}</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- אירועים בחודש הקרוב --}}
            @if (count($d['events']))
            <div class="section">
                <div class="section-title">📅 אירועים החודש</div>
                @foreach ($d['events'] as $ev)
                <div class="item">
                    <span class="badge">{{ $ev['typeLabel'] }}</span>
                    <a href="{{ $ev['url'] }}">{{ $ev['title'] }}</a>
                    @if ($ev['personName']) <span class="meta">· {{ $ev['personName'] }}</span> @endif
                    <div class="meta">
                        {{ $ev['hebrewDate'] }} ({{ $ev['date'] }})@if ($ev['location']) · {{ $ev['location'] }}@endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ימי הולדת עגולים --}}
            @if (count($d['roundBirthdays']))
            <div class="section">
                <div class="section-title">🎂 ימי הולדת עגולים</div>
                @foreach ($d['roundBirthdays'] as $bd)
                <div class="item">
                    <a href="{{ $bd['url'] }}">{{ $bd['name'] }}</a>
                    <span class="meta"> · חוגג/ת {{ $bd['age'] }} ({{ $bd['dayMonth'] }})</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ימי נישואין עגולים --}}
            @if (count($d['roundAnniversaries']))
            <div class="section">
                <div class="section-title">💍 ימי נישואין עגולים</div>
                @foreach ($d['roundAnniversaries'] as $an)
                <div class="item">
                    <a href="{{ $an['url'] }}">{{ $an['names'] }}</a>
                    <span class="meta"> · {{ $an['years'] }} שנות נישואין ({{ $an['dayMonth'] }})</span>
                </div>
                @endforeach
            </div>
            @endif

            @if ($d['isEmpty'])
            <p class="empty">החודש אין עדכונים מיוחדים — אבל תמיד אפשר להיכנס לעץ ולגלות משהו חדש 🙂</p>
            @endif
        </div>

        <div class="footer">
            קיבלת מייל זה כי הינך רשום/ה באתר משפחת ואקיל.<br>
            לא רוצה לקבל את העדכון החודשי? <a href="{{ $profileUrl }}">עדכן/י העדפות בפרופיל</a>.
        </div>
    </div>
</body>
</html>
