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
        .item-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; display: block; }
        .item-avatar-ph { width: 48px; height: 48px; border-radius: 50%; background: #dbeafe; font-size: 22px; text-align: center; line-height: 48px; }
        .item-avatar-sq { width: 54px; height: 54px; border-radius: 8px; object-fit: cover; display: block; }
        .item-avatar-sq-ph { width: 54px; height: 54px; border-radius: 8px; background: #dbeafe; font-size: 24px; text-align: center; line-height: 54px; }
        .badge { display: inline-block; border-radius: 20px; padding: 3px 11px; font-size: 12px; font-weight: 700; margin-left: 6px; background: #eff6ff; color: #1e40af; }
        .badge-bar  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-bat  { background: #fdf2f8; color: #86198f; border: 1px solid #f0abfc; }
        .badge-wed  { background: #fce7f3; color: #9d174d; border: 1px solid #fbcfe8; }
        .badge-birth { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-death { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .date-chip { background: #edf3ff; border-radius: 8px; padding: 6px 10px; text-align: center; white-space: nowrap; display: inline-block; }
        .dc-heb { color: #2d6be4; font-size: 12px; font-weight: 700; display: block; }
        .dc-greg { color: #9aa7c0; font-size: 11px; display: block; }
        .empty { font-size: 14px; color: #9ca3af; }
        .footer { background: #f8fafc; padding: 18px 36px; text-align: center; font-size: 12px; color: #9ca3af; direction: rtl; }
        .footer a { color: #6b7a99; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-emoji">🌳</div>
            <h1>חודש טוב&rlm;!</h1>
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
                    {{-- table: chip(right) | avatar | text(left) --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $baby['hebrewBirth'] }}</span>
                                    <span class="dc-greg">{{ $baby['dateGreg'] ?? '' }}</span>
                                </div>
                            </td>
                            <td width="48" align="center" valign="middle" style="padding-left:14px; width:48px;">
                                @if ($baby['photoUrl'])
                                <img src="{{ $baby['photoUrl'] }}" alt="" class="item-avatar">
                                @else
                                <div class="item-avatar-ph">👶</div>
                                @endif
                            </td>
                            <td valign="middle">
                                <div><a href="{{ $baby['url'] }}">{{ $baby['name'] }}</a>@if ($baby['context'] ?? '') <span class="meta"> {{ $baby['context'] }}</span>@endif</div>
                                <div class="meta">@php echo match($baby['gender'] ?? null) { 'male' => 'נולד', 'female' => 'נולדה', default => 'נולד/ה' }; @endphp</div>
                            </td>
                        </tr>
                    </table>
                </div>
                @endforeach
            </div>
            @endif

            {{-- אירועים בחודש הקרוב --}}
            @if (count($d['events']))
            <div class="section">
                <div class="section-title">📅 אירועים החודש</div>
                @foreach ($d['events'] as $ev)
                @php
                    $evImg = $ev['invitationImgUrl'] ?? ($ev['personPhotoUrl'] ?? null);
                    $badgeCss = match($ev['type'] ?? '') {
                        'bar_mitzvah' => 'badge badge-bar',
                        'bat_mitzvah' => 'badge badge-bat',
                        'wedding'     => 'badge badge-wed',
                        'birth'       => 'badge badge-birth',
                        'death'       => 'badge badge-death',
                        default       => 'badge',
                    };
                @endphp
                <div class="item">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $ev['hebrewDate'] }}</span>
                                    <span class="dc-greg">{{ $ev['date'] }}</span>
                                </div>
                            </td>
                            <td width="54" align="center" valign="middle" style="padding-left:14px; width:54px;">
                                @if ($evImg)
                                <img src="{{ $evImg }}" alt="" class="item-avatar-sq">
                                @else
                                <div class="item-avatar-sq-ph">📅</div>
                                @endif
                            </td>
                            <td valign="middle">
                                <div><span class="{{ $badgeCss }}">{{ $ev['typeLabel'] }}</span><a href="{{ $ev['url'] }}">{{ $ev['title'] }}</a></div>
                                @if ($ev['personName'])<div class="meta">{{ $ev['personName'] }}</div>@endif
                                @if ($ev['location'])<div class="meta">{{ $ev['location'] }}</div>@endif
                            </td>
                        </tr>
                    </table>
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
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $bd['dayMonth'] }}</span>
                                    <span class="dc-greg">{{ $bd['dateGreg'] ?? '' }}</span>
                                </div>
                            </td>
                            <td width="48" align="center" valign="middle" style="padding-left:14px; width:48px;">
                                @if ($bd['photoUrl'] ?? null)
                                <img src="{{ $bd['photoUrl'] }}" alt="" class="item-avatar">
                                @else
                                <div class="item-avatar-ph">🎂</div>
                                @endif
                            </td>
                            <td valign="middle">
                                <div><a href="{{ $bd['url'] }}">{{ $bd['name'] }}</a>@if ($bd['context'] ?? '') <span class="meta"> {{ $bd['context'] }}</span>@endif</div>
                                <div class="meta">חוגג/ת {{ $bd['age'] }}</div>
                            </td>
                        </tr>
                    </table>
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
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $an['dayMonth'] }}</span>
                                    <span class="dc-greg">{{ $an['dateGreg'] ?? '' }}</span>
                                </div>
                            </td>
                            <td width="48" align="center" valign="middle" style="padding-left:14px; width:48px;">
                                <div class="item-avatar-ph">💍</div>
                            </td>
                            <td valign="middle">
                                <div><a href="{{ $an['url'] }}">{{ $an['names'] }}</a></div>
                                <div class="meta">{{ $an['years'] }} שנות נישואין</div>
                            </td>
                        </tr>
                    </table>
                </div>
                @endforeach
            </div>
            @endif

            {{-- סעיף ענף ברזולוציה גבוהה (לפי בחירת המשתמש) --}}
            @php $branchHas = $branch && (count($branch['birthdays']) || count($branch['anniversaries'])); @endphp
            @if ($branchHas)
            <div class="section">
                <div class="section-title">🌿 הענף שלך — {{ $branch['rootName'] }}</div>
                @foreach ($branch['birthdays'] as $bd)
                <div class="item">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $bd['dayMonth'] }}</span>
                                    <span class="dc-greg">{{ $bd['dateGreg'] ?? '' }}</span>
                                </div>
                            </td>
                            <td width="48" align="center" valign="middle" style="padding-left:14px; width:48px;">
                                @if ($bd['photoUrl'] ?? null)
                                <img src="{{ $bd['photoUrl'] }}" alt="" class="item-avatar">
                                @else
                                <div class="item-avatar-ph">🎂</div>
                                @endif
                            </td>
                            <td valign="middle">
                                <div><a href="{{ $bd['url'] }}">{{ $bd['name'] }}</a>@if ($bd['context'] ?? '') <span class="meta"> {{ $bd['context'] }}</span>@endif</div>
                                <div class="meta">יום הולדת {{ $bd['age'] }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
                @endforeach
                @foreach ($branch['anniversaries'] as $an)
                <div class="item">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                        <tr>
                            <td width="96" align="center" valign="middle" style="padding-left:12px; width:96px;">
                                <div class="date-chip">
                                    <span class="dc-heb">{{ $an['dayMonth'] }}</span>
                                    <span class="dc-greg">{{ $an['dateGreg'] ?? '' }}</span>
                                </div>
                            </td>
                            <td width="48" align="center" valign="middle" style="padding-left:14px; width:48px;">
                                <div class="item-avatar-ph">💍</div>
                            </td>
                            <td valign="middle">
                                <div><a href="{{ $an['url'] }}">{{ $an['names'] }}</a></div>
                                <div class="meta">{{ $an['years'] }} שנות נישואין</div>
                            </td>
                        </tr>
                    </table>
                </div>
                @endforeach
            </div>
            @endif

            @if ($d['isEmpty'] && ! $branchHas)
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
