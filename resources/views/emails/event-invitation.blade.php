<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>אירוע חדש</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Rubik', Arial, sans-serif; background-color: #fdf6ec; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(200,164,90,0.18); }
        .header { background: linear-gradient(135deg, #1a3a6b 0%, #2d6be4 60%, #5a9bf0 100%); padding: 36px 32px 28px; text-align: center; }
        .header-emoji { font-size: 44px; margin-bottom: 8px; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; }
        .body { padding: 32px 36px; direction: rtl; text-align: right; }
        .text { font-size: 15px; color: #374151; line-height: 1.7; margin-bottom: 14px; direction: rtl; text-align: right; }
        .event-card { background: #fdf6ec; border: 1px solid #f0d898; border-radius: 12px; padding: 18px 20px; margin: 18px 0; }
        .event-title { font-size: 20px; font-weight: 700; color: #1e3a5f; margin-bottom: 6px; }
        .event-row { font-size: 14px; color: #374151; margin: 3px 0; }
        .event-row b { color: #a07830; }
        .btn-wrap { text-align: center; margin: 26px 0 10px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #c8a45a, #b8903f); color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: 700; padding: 14px 38px; border-radius: 50px; }
        .btn-sub { text-align: center; font-size: 13px; color: #9ca3af; margin-bottom: 8px; }
        .audience-note { text-align: center; font-size: 12px; color: #a07830; background: #fdf6ec; border-radius: 20px; padding: 6px 14px; display: inline-block; margin: 0 auto 4px; }
        .audience-wrap { text-align: center; margin-bottom: 6px; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">💌</div>
            <h1>אירוע משפחתי חדש</h1>
        </div>

        <div class="body">
            <p class="text">{{ $greeting }} — יש אירוע חדש ב-{{ config('app.name') }}:</p>

            @if (count($audienceParts))
            <p class="text" style="color:#a07830; font-size:13.5px;">
                <b>מיועד ל:</b> {{ implode(' · ', $audienceParts) }}
            </p>
            @endif

            @php
                $eventImg = $event->invitation_image_url ?? ($event->person?->profile_photo_url ?? null);
            @endphp
            <div class="event-card">
                @if ($eventImg)
                <div style="text-align:center;margin-bottom:14px;">
                    <img src="{{ $eventImg }}" alt="" style="max-width:100%;width:220px;height:220px;object-fit:cover;border-radius:10px;display:inline-block;">
                </div>
                @endif
                <div class="event-title">{{ $event->title ?: 'אירוע' }}</div>
                @if ($personName)<div class="event-row"><b>למי:</b> {{ $personName }}</div>@endif
                @if ($hebrewDate)<div class="event-row"><b>תאריך:</b> {{ $hebrewDate }}@if ($gregDate) ({{ $gregDate }})@endif</div>@endif
                @if ($event->event_time)<div class="event-row"><b>שעה:</b> {{ \Illuminate\Support\Str::of($event->event_time)->substr(0,5) }}</div>@endif
                @if ($event->location)<div class="event-row"><b>מיקום:</b> {{ $event->location }}</div>@endif
                @if ($event->description)<div class="event-row" style="margin-top:8px; color:#6b7a99;">{{ $event->description }}</div>@endif
                @if ($addedBy)<div class="event-row" style="margin-top:8px; color:#9ca3af; font-size:13px;">נוסף על ידי {{ $addedBy }}</div>@endif
            </div>

            @if ($event->invitation_image)
            <p class="text" style="text-align:center; font-size:13px; color:#9ca3af;">💡 ההזמנה מצורפת גם כקובץ למייל הזה</p>
            @endif

            <div class="btn-wrap">
                <a href="{{ $eventUrl }}" class="btn">כניסה לאירוע וכתיבת ברכה 💬</a>
            </div>
        </div>

        <div class="footer">
            קיבלת עדכון זה מ-{{ config('app.name') }}.<br>
            לא רוצה לקבל עדכוני אירועים? <a href="{{ $profileUrl }}">עדכן/י העדפות בפרופיל</a>.
        </div>
    </div>
</body>
</html>
