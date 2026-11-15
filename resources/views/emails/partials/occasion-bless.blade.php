@if (!empty($uid) && !empty($it['pid']))
@php
    $mk = fn ($k) => \Illuminate\Support\Facades\URL::signedRoute('occasion.bless', [
        'person' => $it['pid'], 'user' => $uid, 'occasion' => $it['occasion'], 'year' => $it['year'], 'kind' => $k,
    ]);
@endphp
<div style="margin-top:6px;">
    <a href="{{ $mk('mazal_tov') }}" style="display:inline-block;background:#fff;color:#2563eb;border:1.5px solid #93c5fd;text-decoration:none;font-weight:700;font-size:12px;padding:4px 14px;border-radius:50px;margin-left:4px;">מזל טוב</a>
    <a href="{{ $mk('shefa') }}" style="display:inline-block;background:#fff;color:#a07830;border:1.5px solid #f0d898;text-decoration:none;font-weight:700;font-size:12px;padding:4px 14px;border-radius:50px;">שפע ברכות</a>
</div>
@endif
