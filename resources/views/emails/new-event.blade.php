<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>אירוע חדש בעץ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Rubik', Arial, sans-serif; background-color: #f0f7ff; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(59,130,246,0.12); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%); padding: 36px 32px 28px; text-align: center; }
        .header-emoji { font-size: 44px; margin-bottom: 8px; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; }
        .body { padding: 32px 36px; direction: rtl; text-align: right; }
        .text { font-size: 15px; color: #374151; line-height: 1.7; margin-bottom: 14px; direction: rtl; text-align: right; }
        .event-card { background: #eff6ff; border-radius: 12px; padding: 18px 20px; margin: 18px 0; }
        .event-title { font-size: 19px; font-weight: 700; color: #1e3a5f; margin-bottom: 6px; }
        .event-row { font-size: 14px; color: #374151; margin: 3px 0; }
        .event-row b { color: #1e40af; }
        .btn-wrap { text-align: center; margin: 24px 0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: 600; padding: 13px 36px; border-radius: 50px; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">📅</div>
            <h1>אירוע חדש בעץ המשפחה</h1>
        </div>

        <div class="body">
            <p class="text">
                @if ($recipientName) שלום {{ $recipientName }}, @else שלום, @endif
                נוסף אירוע חדש ללוח של משפחת ואקיל:
            </p>

            @php
                $eventImg = $event->invitation_image_url ?? ($event->person?->profile_photo_url ?? null);
            @endphp
            <div class="event-card">
                @if ($eventImg)
                <div style="text-align:center;margin-bottom:14px;">
                    <img src="{{ $eventImg }}" alt="" style="max-width:100%;width:200px;height:200px;object-fit:cover;border-radius:10px;display:inline-block;">
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

            <div class="btn-wrap">
                <a href="{{ $eventUrl }}" class="btn">לעמוד האירוע</a>
            </div>
        </div>

        <div class="footer">
            קיבלת מייל זה כי ביקשת לקבל התראה על אירועים חדשים.<br>
            לא רוצה לקבל התראות אלו? <a href="{{ $profileUrl }}">עדכן/י העדפות בפרופיל</a>.
        </div>
    </div>
</body>
</html>
