<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>הברכה נשלחה</title>
<style>
body{margin:0;font-family:'Rubik',Arial,sans-serif;background:#f0f7ff;display:flex;min-height:100vh;align-items:center;justify-content:center;color:#1a3a6b}
.card{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(59,130,246,.15);padding:2rem 1.75rem;max-width:380px;text-align:center}
.ic{width:56px;height:56px;margin:0 auto 12px}
h1{font-size:1.4rem;margin:.2rem 0}
p{color:#6b7a99;font-size:.95rem;line-height:1.6}
a{display:inline-block;margin-top:.8rem;background:#2d6be4;color:#fff;text-decoration:none;padding:.65rem 1.6rem;border-radius:50px;font-weight:600}
</style></head>
<body><div class="card">
<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="#2d6be4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
<h1>הברכה "{{ $label }}" נשלחה</h1>
<p>{{ $event->title }}<br>עד עכשיו נשלחו {{ $count }} ברכות.</p>
<a href="{{ $url }}">לכל הברכות באתר</a>
</div></body></html>
