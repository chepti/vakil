<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הזמנה ל{{ config('app.name') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rubik', Arial, sans-serif;
            background-color: #f0f7ff;
            direction: rtl;
            text-align: right;
        }

        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(59, 130, 246, 0.12);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%);
            padding: 40px 32px 32px;
            text-align: center;
        }

        .header-emoji {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 15px;
        }

        /* Body */
        .body {
            padding: 36px 40px;
            direction: rtl;
            text-align: right;
        }

        .greeting {
            font-size: 18px;
            color: #1e3a5f;
            font-weight: 600;
            margin-bottom: 16px;
            direction: rtl;
            text-align: right;
        }

        .text {
            font-size: 15px;
            color: #374151;
            line-height: 1.7;
            margin-bottom: 12px;
            direction: rtl;
            text-align: right;
        }

        .highlight-box {
            background: #eff6ff;
            border-right: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 20px 0;
            font-size: 15px;
            color: #1e40af;
            direction: rtl;
            text-align: right;
        }

        /* CTA Button */
        .btn-wrap {
            text-align: center;
            margin: 32px 0 24px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 17px;
            font-weight: 600;
            padding: 14px 40px;
            border-radius: 50px;
            letter-spacing: 0.3px;
        }

        .expiry-note {
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 24px;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 24px 0;
        }

        .link-fallback {
            font-size: 12px;
            color: #9ca3af;
            word-break: break-all;
            direction: rtl;
            text-align: right;
        }

        .link-fallback a {
            color: #3b82f6;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <!-- Header -->
        <div class="header">
            <div class="header-emoji">🌳</div>
            <h1>{{ config('app.name') }}</h1>
            <p>אתר העץ המשפחתי הפרטי שלנו</p>
        </div>

        <!-- Body -->
        <div class="body">

            <p class="greeting">שלום! 👋</p>

            <p class="text">
                <strong>{{ $inviterName }}</strong> הזמין/ה אותך להצטרף לאתר העץ המשפחתי של {{ config('app.name') }}.
            </p>

            @if ($personName)
            <div class="highlight-box">
                ✨ כבר יש לך כרטיס בעץ בשם <strong>{{ $personName }}</strong> — לאחר ההרשמה תיכנס/י ישירות אליו.
            </div>
            @endif

            <p class="text">
                באתר תוכל/י לראות את עץ המשפחה המלא, לצפות בתמונות, לכתוב ברכות ולהוסיף פרטים על בני המשפחה.
            </p>

            <div class="btn-wrap">
                <a href="{{ $registerUrl }}" class="btn">הצטרף/י עכשיו</a>
            </div>

            <div class="divider"></div>

            <p class="text" style="font-size:14px; color:#4b5563;">
                כבר יצרת/ה חשבון?
                <a href="{{ $loginUrl }}" style="color:#3b82f6; font-weight:600;">לחץ/י כאן להתחברות לאתר</a>
            </p>

            <div class="divider"></div>

            <p class="link-fallback">
                אם כפתור ההרשמה לא עובד, העתק/י את הקישור הבא:<br>
                <a href="{{ $registerUrl }}">{{ $registerUrl }}</a>
            </p>

        </div>

        <!-- Footer -->
        <div class="footer">
            אתר זה פרטי ומיועד לבני {{ config('app.name') }} בלבד.<br>
            קיבלת מייל זה כי {{ $inviterName }} הזמין/ה אותך.
        </div>
    </div>
</body>
</html>
