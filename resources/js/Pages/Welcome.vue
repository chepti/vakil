<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({ canLogin: Boolean });

onMounted(() => {
    const revObs = new IntersectionObserver(
        entries => entries.forEach(e => e.isIntersecting && e.target.classList.add('visible')),
        { threshold: 0.1 }
    );
    document.querySelectorAll('.reveal, .reveal-r, .reveal-l').forEach(el => revObs.observe(el));

    const treeObs = new IntersectionObserver(
        entries => entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('tree-play'); treeObs.unobserve(e.target); }
        }),
        { threshold: 0.25 }
    );
    document.querySelectorAll('.tree-svg').forEach(el => treeObs.observe(el));
});
</script>

<template>
    <Head title="אתר המשפחה" />
    <div dir="rtl" style="font-family:'Rubik','Figtree',sans-serif" class="min-h-screen bg-white text-gray-800 overflow-x-hidden">

        <!-- ═══ NAV ═══ -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur-sm border-b border-blue-50 shadow-sm">
            <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img src="/favicon.svg" class="h-9 w-9" alt="" />
                    <span class="font-bold text-lg" style="color:#1a3a6b">אתר המשפחה</span>
                </div>
                <Link v-if="canLogin" :href="route('login')"
                    class="inline-flex items-center text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-all hover:-translate-y-0.5"
                    style="background:#2d6be4;box-shadow:0 4px 14px rgba(45,107,228,0.35)">
                    כניסה לאתר
                </Link>
            </div>
        </nav>

        <!-- ═══ HERO ═══ -->
        <section class="hero-bg pt-16">
            <div class="max-w-6xl mx-auto px-5 py-20 lg:py-28 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div class="reveal-r">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-full mb-5 border"
                        style="background:rgba(255,255,255,0.12);color:#cde;border-color:rgba(255,255,255,0.18)">
                        🪬 פלטפורמה משפחתית פרטית ומוגנת
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-bold text-white leading-snug mb-5">
                        כל המשפחה,<br>במקום אחד
                    </h1>
                    <p class="text-lg mb-8 leading-relaxed" style="color:#b8d0f0">
                        מותאמת למשפחות ברוכות ילדים — עץ משפחה אינטרקטיבי, לוח אירועים בתאריך עברי, גלריית תמונות עם תיוג פנים, ומשחק לגלות בני משפחה.
                    </p>
                    <Link v-if="canLogin" :href="route('login')"
                        class="inline-flex items-center gap-2 text-white font-bold px-7 py-3.5 rounded-full transition-all hover:opacity-90 hover:-translate-y-1"
                        style="background:#c8a45a;box-shadow:0 6px 20px rgba(200,164,90,0.4)">
                        כניסה לאתר ←
                    </Link>
                </div>

                <!-- Radial tree -->
                <div class="reveal-l">
                    <div class="hero-card">
                        <div class="hero-card-hdr">🌳 עץ המשפחה</div>
                        <svg viewBox="0 0 320 262" class="tree-svg w-full" xmlns="http://www.w3.org/2000/svg">
                            <!-- Lines: center → children -->
                            <line x1="162" y1="128" x2="242" y2="128" stroke="rgba(255,255,255,0.22)" stroke-width="1.5" class="tl tl1"/>
                            <line x1="162" y1="128" x2="200" y2="202" stroke="rgba(255,255,255,0.22)" stroke-width="1.5" class="tl tl2"/>
                            <line x1="162" y1="128" x2="95"  y2="198" stroke="rgba(255,255,255,0.22)" stroke-width="1.5" class="tl tl3"/>
                            <line x1="162" y1="128" x2="82"  y2="128" stroke="rgba(255,255,255,0.22)" stroke-width="1.5" class="tl tl4"/>
                            <line x1="162" y1="128" x2="162" y2="48"  stroke="rgba(255,255,255,0.22)" stroke-width="1.5" class="tl tl5"/>
                            <!-- Lines: children → grandchildren -->
                            <line x1="242" y1="128" x2="296" y2="96"  stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl6"/>
                            <line x1="242" y1="128" x2="296" y2="160" stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl7"/>
                            <line x1="200" y1="202" x2="214" y2="250" stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl8"/>
                            <line x1="95"  y1="198" x2="74"  y2="250" stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl9"/>
                            <line x1="82"  y1="128" x2="22"  y2="96"  stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl10"/>
                            <line x1="162" y1="48"  x2="162" y2="12"  stroke="rgba(255,255,255,0.14)" stroke-width="1.2" class="tl tl11"/>
                            <!-- Couple connector -->
                            <line x1="162" y1="128" x2="166" y2="128" stroke="rgba(255,255,255,0.4)" stroke-width="2.5" class="tl tl0"/>
                            <!-- Center couple -->
                            <g class="tn0">
                                <circle cx="144" cy="128" r="17" fill="#c48a92"/>
                                <text x="144" y="150" font-size="8.5" text-anchor="middle" fill="white" font-weight="600">מרים</text>
                                <circle cx="181" cy="128" r="17" fill="#7ba3b0"/>
                                <text x="181" y="150" font-size="8.5" text-anchor="middle" fill="white" font-weight="600">יוסף</text>
                            </g>
                            <!-- Children -->
                            <g class="tn tn1"><circle cx="242" cy="128" r="13" fill="#7ba3b0"/><text x="242" y="145" font-size="8" text-anchor="middle" fill="white" font-weight="600">נחום</text></g>
                            <g class="tn tn2"><circle cx="200" cy="202" r="13" fill="#c48a92"/><text x="200" y="219" font-size="8" text-anchor="middle" fill="white" font-weight="600">שרה</text></g>
                            <g class="tn tn3"><circle cx="95"  cy="198" r="13" fill="#7ba3b0"/><text x="95"  y="215" font-size="8" text-anchor="middle" fill="white" font-weight="600">דני</text></g>
                            <g class="tn tn4"><circle cx="82"  cy="128" r="13" fill="#c48a92"/><text x="82"  y="145" font-size="8" text-anchor="middle" fill="white" font-weight="600">רחל</text></g>
                            <g class="tn tn5"><circle cx="162" cy="48"  r="13" fill="#7ba3b0"/><text x="162" y="65"  font-size="8" text-anchor="middle" fill="white" font-weight="600">אמיר</text></g>
                            <!-- Grandchildren (dots only) -->
                            <g class="tn tg1"><circle cx="296" cy="96"  r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg2"><circle cx="296" cy="160" r="9" fill="#c48a92" opacity="0.8"/></g>
                            <g class="tn tg3"><circle cx="214" cy="250" r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg4"><circle cx="74"  cy="250" r="9" fill="#c48a92" opacity="0.8"/></g>
                            <g class="tn tg5"><circle cx="22"  cy="96"  r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg6"><circle cx="162" cy="12"  r="9" fill="#c48a92" opacity="0.8"/></g>
                        </svg>
                        <div class="flex flex-wrap justify-center gap-2 mt-1 pb-1">
                            <span class="hero-badge">3 דורות</span>
                            <span class="hero-badge">תאריך עברי</span>
                            <span class="hero-badge">ברוכי ילדים</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ QUICK FEATURES ═══ -->
        <section class="py-14" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5">
                <h2 class="text-center text-2xl lg:text-3xl font-bold mb-10 reveal" style="color:#1a3a6b">כל מה שמשפחה צריכה</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="(f,i) in features" :key="i" class="feat-chip reveal" :style="`transition-delay:${i*0.08}s`">
                        <div class="text-3xl mb-2">{{f.icon}}</div>
                        <div class="font-bold text-sm" style="color:#1a3a6b">{{f.title}}</div>
                        <div class="text-xs mt-1" style="color:#6b7a99">{{f.sub}}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ SECTION: TREE ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="background:#0f2448;box-shadow:0 8px 40px rgba(15,36,72,0.4)">
                        <div class="demo-hdr" style="color:#b8d0f0;border-bottom-color:rgba(255,255,255,0.08)">🌳 תצוגת עץ המשפחה</div>
                        <svg viewBox="0 0 320 262" class="tree-svg w-full" xmlns="http://www.w3.org/2000/svg">
                            <line x1="162" y1="128" x2="242" y2="128" stroke="rgba(255,255,255,0.18)" stroke-width="1.5" class="tl tl1"/>
                            <line x1="162" y1="128" x2="200" y2="202" stroke="rgba(255,255,255,0.18)" stroke-width="1.5" class="tl tl2"/>
                            <line x1="162" y1="128" x2="95"  y2="198" stroke="rgba(255,255,255,0.18)" stroke-width="1.5" class="tl tl3"/>
                            <line x1="162" y1="128" x2="82"  y2="128" stroke="rgba(255,255,255,0.18)" stroke-width="1.5" class="tl tl4"/>
                            <line x1="162" y1="128" x2="162" y2="48"  stroke="rgba(255,255,255,0.18)" stroke-width="1.5" class="tl tl5"/>
                            <line x1="242" y1="128" x2="296" y2="96"  stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl6"/>
                            <line x1="242" y1="128" x2="296" y2="160" stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl7"/>
                            <line x1="200" y1="202" x2="214" y2="250" stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl8"/>
                            <line x1="95"  y1="198" x2="74"  y2="250" stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl9"/>
                            <line x1="82"  y1="128" x2="22"  y2="96"  stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl10"/>
                            <line x1="162" y1="48"  x2="162" y2="12"  stroke="rgba(255,255,255,0.1)" stroke-width="1.2" class="tl tl11"/>
                            <line x1="162" y1="128" x2="166" y2="128" stroke="rgba(255,255,255,0.4)" stroke-width="2.5" class="tl tl0"/>
                            <g class="tn0">
                                <circle cx="144" cy="128" r="17" fill="#c48a92"/>
                                <text x="144" y="150" font-size="8.5" text-anchor="middle" fill="white" font-weight="600">מרים</text>
                                <circle cx="181" cy="128" r="17" fill="#7ba3b0"/>
                                <text x="181" y="150" font-size="8.5" text-anchor="middle" fill="white" font-weight="600">יוסף</text>
                            </g>
                            <g class="tn tn1"><circle cx="242" cy="128" r="13" fill="#7ba3b0"/><text x="242" y="145" font-size="8" text-anchor="middle" fill="white" font-weight="600">נחום</text></g>
                            <g class="tn tn2"><circle cx="200" cy="202" r="13" fill="#c48a92"/><text x="200" y="219" font-size="8" text-anchor="middle" fill="white" font-weight="600">שרה</text></g>
                            <g class="tn tn3"><circle cx="95"  cy="198" r="13" fill="#7ba3b0"/><text x="95"  y="215" font-size="8" text-anchor="middle" fill="white" font-weight="600">דני</text></g>
                            <g class="tn tn4"><circle cx="82"  cy="128" r="13" fill="#c48a92"/><text x="82"  y="145" font-size="8" text-anchor="middle" fill="white" font-weight="600">רחל</text></g>
                            <g class="tn tn5"><circle cx="162" cy="48"  r="13" fill="#7ba3b0"/><text x="162" y="65"  font-size="8" text-anchor="middle" fill="white" font-weight="600">אמיר</text></g>
                            <g class="tn tg1"><circle cx="296" cy="96"  r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg2"><circle cx="296" cy="160" r="9" fill="#c48a92" opacity="0.8"/></g>
                            <g class="tn tg3"><circle cx="214" cy="250" r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg4"><circle cx="74"  cy="250" r="9" fill="#c48a92" opacity="0.8"/></g>
                            <g class="tn tg5"><circle cx="22"  cy="96"  r="9" fill="#7ba3b0" opacity="0.8"/></g>
                            <g class="tn tg6"><circle cx="162" cy="12"  r="9" fill="#c48a92" opacity="0.8"/></g>
                        </svg>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#eaf2ff;color:#2d6be4">🌳 עץ משפחה</div>
                    <h2 class="feat-title">תצוגת כל הדורות בבת אחת</h2>
                    <p class="feat-desc">עץ אינטרקטיבי המציג את כל בני המשפחה בצורה גרפית. ניתן לחפש, לנווט, לערוך ישירות ולהדפיס ל-PDF.</p>
                    <ul class="feat-list">
                        <li>✓ תצוגה רדיאלית, אנכית וענפי אב/אם</li>
                        <li>✓ עריכה ישירה בתוך העץ</li>
                        <li>✓ חיפוש מהיר לפי שם</li>
                        <li>✓ הדפסה ל-PDF</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ SECTION: EVENTS (Calendar + Event detail) ═══ -->
        <section class="py-20" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-14 items-start">
                <div class="reveal order-2 lg:order-1">
                    <div class="feat-tag" style="background:#fef8ec;color:#a07830">🎉 אירועים</div>
                    <h2 class="feat-title">לוח אירועים בתאריך עברי</h2>
                    <p class="feat-desc">לידות, בר/בת מצוות, חתונות ופטירות — תיעוד מלא עם תאריכים עבריים ולועזיים, מיקום, תמונת הזמנה וברכות מהמשפחה.</p>
                    <ul class="feat-list">
                        <li>✓ תאריכים עבריים ולועזיים אוטומטיים</li>
                        <li>✓ ברכות ואמוג'ים מהמשפחה</li>
                        <li>✓ שליחה לכל המשפחה או ענף ספציפי</li>
                        <li>✓ תמונת הזמנה וקישור לגלריה</li>
                    </ul>
                </div>
                <div class="reveal-l order-1 lg:order-2">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(200,164,90,0.18)">
                        <div class="demo-hdr">📅 לוח אירועים — תמוז תשפ"ו</div>
                        <!-- Mini calendar -->
                        <div class="cal-grid">
                            <div v-for="h in calHeaders" :key="h" class="cal-th">{{h}}</div>
                            <template v-for="(d,i) in calData" :key="i">
                                <div class="cal-cell" :class="{'cal-today':d.today,'cal-empty':!d.day}">
                                    <template v-if="d.day">
                                        <span class="cal-greg">{{d.day}}</span>
                                        <span class="cal-heb">{{d.heb}}</span>
                                        <span v-if="d.event" class="cal-evt">{{d.event}}</span>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <!-- Event detail card -->
                        <div class="evt-preview">
                            <div class="evt-inv">
                                <div class="evt-inv-deco"></div>
                                <div class="evt-inv-body">
                                    <div class="evt-inv-lbl">שמחים להזמינכם לבת המצווה של</div>
                                    <div class="evt-inv-name">אחינועם</div>
                                    <div class="evt-inv-date">כ"ה תמוז תשפ"ו · יום שלישי 19.7</div>
                                    <div class="evt-inv-loc">בית ספר שחריות, 19:00</div>
                                </div>
                            </div>
                            <div class="evt-meta">
                                <span class="evt-badge">🎉 בת מצווה</span>
                                <div class="evt-row">👤 אחינועם בת-שלמה</div>
                                <div class="evt-row">📅 כ"ה תמוז תשפ"ו · 19.7.2026 · 19:00</div>
                                <div class="evt-row">📍 בית ספר שחריות, רח' הגדול 4</div>
                                <div class="evt-row" style="color:#2d6be4">💬 18 ברכות · ❤️ 12</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ SECTION: PHOTOS ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(128,80,160,0.13)">
                        <div class="demo-hdr">📸 גלריית תמונות</div>
                        <div class="photo-wrap">
                            <div class="face-sq" style="top:14%;right:13%;width:24%;padding-top:24%">
                                <div class="face-sq-lbl">יוסף</div>
                            </div>
                            <div class="face-sq face-sq-on" style="top:14%;right:41%;width:24%;padding-top:24%">
                                <div class="face-sq-lbl face-sq-lbl-on">מרים ✓</div>
                            </div>
                            <div class="face-sq" style="top:14%;right:69%;width:24%;padding-top:24%">
                                <div class="face-sq-lbl">נחום</div>
                            </div>
                            <div class="photo-cap">כינוס משפחתי · 2024 · 14 מתויגים</div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <div class="thumb" style="background:linear-gradient(135deg,#c8d8e8,#a8b8c8)">🏖️</div>
                            <div class="thumb" style="background:linear-gradient(135deg,#d8c8e8,#b8a8c8)">🎂</div>
                            <div class="thumb" style="background:linear-gradient(135deg,#c8e8d8,#a8c8b8)">💒</div>
                        </div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#f3eeff;color:#7040a0">📸 תמונות</div>
                    <h2 class="feat-title">גלריה עם תיוג פנים</h2>
                    <p class="feat-desc">העלאת תמונות קבוצתיות עם תיוג מדויק של כל פנים. לחיצה על פנים בתמונה — עוברים ישר לפרופיל האדם.</p>
                    <ul class="feat-list">
                        <li>✓ תיוג פנים בריבוע לפי קואורדינטות מדויקות</li>
                        <li>✓ חיפוש תמונות לפי אדם</li>
                        <li>✓ חיתוך תמונת פרופיל בדפדפן</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ SECTION: GAME ═══ -->
        <section class="py-20" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div class="reveal order-2 lg:order-1">
                    <div class="feat-tag" style="background:#efffef;color:#207040">🎮 משחק</div>
                    <h2 class="feat-title">לגלות בני משפחה שאולי פחות הכרנו</h2>
                    <p class="feat-desc">משחק ניחוש שמשתמש בנתוני העץ האמיתי — שאלות על קרבת משפחה עם רמזים חכמים. מושלם למפגשים משפחתיים.</p>
                    <ul class="feat-list">
                        <li>✓ שאלות מבוססות העץ האמיתי</li>
                        <li>✓ רמזים מדורגים</li>
                        <li>✓ מסיחים חכמים לפי מגדר ודור</li>
                    </ul>
                </div>
                <div class="reveal-l order-1 lg:order-2">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(40,160,90,0.13)">
                        <div class="demo-hdr">🎮 משחק ניחוש משפחתי</div>
                        <div class="game-q">מיהו האב של <strong>שרה</strong>?</div>
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div class="gopt gopt-wrong">נחום</div>
                            <div class="gopt gopt-right">יוסף ✓</div>
                            <div class="gopt">אהרון</div>
                            <div class="gopt">שלמה</div>
                        </div>
                        <div class="game-hint">💡 רמז: האב נולד בירושלים ועסק ברפואה</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ SECTION: EMAIL ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(26,58,107,0.14)">
                        <div class="demo-hdr">✉️ עדכון חודשי</div>
                        <div class="email-subj">📅 סיכום חודש תמוז תשפ"ו — אתר המשפחה</div>
                        <div class="email-sec">🎂 ימי הולדת החודש</div>
                        <div class="email-row">• מיכל כהן — 3 לחודש</div>
                        <div class="email-row">• דוד לוי — 8 לחודש</div>
                        <div class="email-sec">🎉 אירועים קרובים</div>
                        <div class="email-row">• בת מצווה אחינועם — 19.7</div>
                        <div class="email-row">• חתונת ענת ואיתי — 25.7</div>
                        <div class="email-foot">2 בני משפחה חדשים הצטרפו החודש 🎊</div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#eaf2ff;color:#1a3a6b">✉️ עדכונים</div>
                    <h2 class="feat-title">עדכונים אוטומטיים לכל המשפחה</h2>
                    <p class="feat-desc">מערכת מייל חכמה עם הזמנות, עדכוני אירועים, חברי משפחה חדשים, ועדכון חודשי בעברית — עם סינון לפי ענף עץ.</p>
                    <ul class="feat-list">
                        <li>✓ הזמנות בקישור אישי (תוקף 7 ימים)</li>
                        <li>✓ עדכון חודשי בעברית עם לוח עברי</li>
                        <li>✓ סינון לפי ענף משפחה</li>
                        <li>✓ כל משתמש שולט על ההעדפות שלו</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ CTA ═══ -->
        <section class="py-24 cta-bg">
            <div class="max-w-xl mx-auto px-5 text-center reveal">
                <div class="text-5xl mb-5">🪬</div>
                <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">רוצה כזה למשפחה שלך?</h2>
                <p class="text-lg mb-8 leading-relaxed" style="color:#b8d0f0">
                    הפלטפורמה בקוד פתוח — ניתן להתאים לכל משפחה.<br>
                    לפרטים, עזרה בהתקנה או שיתוף פעולה:
                </p>
                <a href="mailto:chepti@gmail.com"
                    class="inline-flex items-center gap-2 text-white font-bold px-8 py-4 rounded-full text-base transition-all hover:opacity-90 hover:-translate-y-1"
                    style="background:#c8a45a;box-shadow:0 6px 24px rgba(200,164,90,0.4)">
                    ✉️ chepti@gmail.com
                </a>
                <p class="mt-5 text-sm" style="color:#8aace0">
                    קוד פתוח:
                    <a href="https://github.com/chepti/vakil" target="_blank" class="underline text-white hover:text-yellow-300 transition-colors">github.com/chepti/vakil</a>
                </p>
            </div>
        </section>

        <footer class="py-6 text-center text-sm" style="background:#0a1f45;color:#8aace0">
            פותח ב-❤️ עבור המשפחה
        </footer>
    </div>
</template>

<script>
export default {
    data() {
        return {
            features: [
                { icon: '🌳', title: 'עץ משפחה', sub: 'תצוגה רדיאלית אינטרקטיבית' },
                { icon: '🎉', title: 'לוח אירועים', sub: 'תאריך עברי ולועזי' },
                { icon: '📸', title: 'גלריית תמונות', sub: 'עם תיוג פנים' },
                { icon: '🎮', title: 'משחק גילוי', sub: 'לגלות בני המשפחה' },
            ],
            calHeaders: ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'],
            calData: [
                // Week 1 (starts on Tuesday = index 2)
                {day:null},{day:null},{day:1,heb:"י'"},{day:2,heb:'י"א'},{day:3,heb:'י"ב',event:'🎂'},{day:4,heb:'י"ג'},{day:5,heb:'י"ד'},
                // Week 2
                {day:6,heb:'ט"ו'},{day:7,heb:'ט"ז'},{day:8,heb:'י"ז',event:'🎂'},{day:9,heb:'י"ח'},{day:10,heb:'י"ט'},{day:11,heb:"כ'"},{day:12,heb:'כ"א'},
                // Week 3
                {day:13,heb:'כ"ב'},{day:14,heb:'כ"ג'},{day:15,heb:'כ"ד',event:'💍'},{day:16,heb:'כ"ה'},{day:17,heb:'כ"ו'},{day:18,heb:'כ"ז'},{day:19,heb:'כ"ח',event:'🎉',today:true},
                // Week 4
                {day:20,heb:'כ"ט'},{day:21,heb:"א'"},{day:22,heb:"ב'"},{day:23,heb:"ג'"},{day:24,heb:"ד'"},{day:25,heb:"ה'",event:'💍'},{day:26,heb:"ו'"},
            ],
        };
    },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.hero-bg {
    background: linear-gradient(135deg, #0f2448 0%, #1a3a6b 55%, #1e4888 100%);
    position: relative; overflow: hidden;
}
.hero-bg::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(45,107,228,0.2) 0%, transparent 65%);
    pointer-events: none;
}
.cta-bg { background: linear-gradient(135deg, #0f2448 0%, #1a3a6b 100%); }

/* REVEAL */
.reveal, .reveal-r, .reveal-l {
    opacity: 0; transform: translateY(26px);
    transition: opacity 0.65s ease, transform 0.65s ease;
}
.reveal-r { transform: translateX(34px); }
.reveal-l { transform: translateX(-34px); }
.reveal.visible, .reveal-r.visible, .reveal-l.visible { opacity: 1; transform: translate(0,0); }

/* HERO CARD */
.hero-card {
    background: rgba(255,255,255,0.1); backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.18); border-radius: 22px; padding: 18px; color: white;
}
.hero-card-hdr { font-weight: 700; font-size: 0.9rem; opacity: 0.9; text-align: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.12); margin-bottom: 2px; }
.hero-badge { font-size: 0.68rem; font-weight: 600; padding: 3px 9px; border-radius: 20px; border: 1px solid rgba(200,164,90,0.4); background: rgba(200,164,90,0.15); color: #f0d090; }

/* FEAT CHIPS */
.feat-chip { background: white; border-radius: 16px; padding: 18px 14px; text-align: center; border: 1px solid #e7eefb; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 18px rgba(26,58,107,0.07); }
.feat-chip:hover { transform: translateY(-4px); box-shadow: 0 8px 28px rgba(26,58,107,0.13); }

/* DEMO CARDS */
.demo-card { background: white; border-radius: 20px; padding: 18px; border: 1px solid #e7eefb; }
.demo-hdr { font-weight: 700; font-size: 0.88rem; color: #1a3a6b; padding-bottom: 10px; margin-bottom: 8px; border-bottom: 1px solid #eef2fb; }

/* FEAT TEXT */
.feat-tag { display: inline-flex; gap: 5px; font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; margin-bottom: 10px; }
.feat-title { font-size: 1.6rem; font-weight: 700; color: #1a3a6b; line-height: 1.3; margin-bottom: 10px; }
.feat-desc { color: #6b7a99; line-height: 1.75; margin-bottom: 14px; font-size: 0.94rem; }
.feat-list { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.feat-list li { font-size: 0.88rem; color: #374151; }

/* ═══ TREE SVG ANIMATIONS ═══ */
/* All tree nodes/lines start hidden */
.tl, .tn, .tn0 { opacity: 0; }

/* When parent SVG gets .tree-play, animations trigger */
.tree-play .tl0 { animation: tl-fade 0.3s ease-out 0.2s both; }
.tree-play .tl1 { animation: tl-fade 0.3s ease-out 1.1s both; }
.tree-play .tl2 { animation: tl-fade 0.3s ease-out 1.4s both; }
.tree-play .tl3 { animation: tl-fade 0.3s ease-out 1.7s both; }
.tree-play .tl4 { animation: tl-fade 0.3s ease-out 2.0s both; }
.tree-play .tl5 { animation: tl-fade 0.3s ease-out 2.3s both; }
.tree-play .tl6 { animation: tl-fade 0.25s ease-out 2.7s both; }
.tree-play .tl7 { animation: tl-fade 0.25s ease-out 2.85s both; }
.tree-play .tl8 { animation: tl-fade 0.25s ease-out 3.0s both; }
.tree-play .tl9 { animation: tl-fade 0.25s ease-out 3.15s both; }
.tree-play .tl10 { animation: tl-fade 0.25s ease-out 3.3s both; }
.tree-play .tl11 { animation: tl-fade 0.25s ease-out 3.45s both; }

.tree-play .tn0 { animation: tn-appear 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.2s both; }
/* Children slide from center (162,128) */
.tree-play .tn1 { animation: tn-c1 0.55s cubic-bezier(0.34,1.56,0.64,1) 1.1s both; } /* → right */
.tree-play .tn2 { animation: tn-c2 0.55s cubic-bezier(0.34,1.56,0.64,1) 1.4s both; } /* → lower-right */
.tree-play .tn3 { animation: tn-c3 0.55s cubic-bezier(0.34,1.56,0.64,1) 1.7s both; } /* → lower-left */
.tree-play .tn4 { animation: tn-c4 0.55s cubic-bezier(0.34,1.56,0.64,1) 2.0s both; } /* → left */
.tree-play .tn5 { animation: tn-c5 0.55s cubic-bezier(0.34,1.56,0.64,1) 2.3s both; } /* → top */
/* Grandchildren slide from parents */
.tree-play .tg1 { animation: tg-1 0.4s ease-out 2.7s both; }
.tree-play .tg2 { animation: tg-2 0.4s ease-out 2.85s both; }
.tree-play .tg3 { animation: tg-3 0.4s ease-out 3.0s both; }
.tree-play .tg4 { animation: tg-4 0.4s ease-out 3.15s both; }
.tree-play .tg5 { animation: tg-5 0.4s ease-out 3.3s both; }
.tree-play .tg6 { animation: tg-6 0.4s ease-out 3.45s both; }

@keyframes tl-fade { from { opacity: 0; } to { opacity: 1; } }
@keyframes tn-appear { from { opacity: 0; transform: scale(0.3); } to { opacity: 1; transform: scale(1); } }
/* Each child translates FROM center to its position */
@keyframes tn-c1 { from { transform: translate(-80px, 0);     opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tn-c2 { from { transform: translate(-38px, -74px); opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tn-c3 { from { transform: translate(67px, -70px);  opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tn-c4 { from { transform: translate(80px, 0);      opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tn-c5 { from { transform: translate(0, 80px);      opacity: 0; } to { transform: none; opacity: 1; } }
/* Each grandchild translates FROM its parent */
@keyframes tg-1 { from { transform: translate(-54px, 32px);  opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tg-2 { from { transform: translate(-54px, -32px); opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tg-3 { from { transform: translate(-14px, -48px); opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tg-4 { from { transform: translate(21px, -52px);  opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tg-5 { from { transform: translate(60px, 32px);   opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes tg-6 { from { transform: translate(0, 36px);      opacity: 0; } to { transform: none; opacity: 1; } }

/* ═══ CALENDAR ═══ */
.cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; margin-bottom: 12px; }
.cal-th { text-align: center; font-size: 0.68rem; font-weight: 700; color: #6b7a99; padding: 4px 0; }
.cal-cell { background: #f8faff; border-radius: 6px; padding: 3px; min-height: 44px; display: flex; flex-direction: column; align-items: center; gap: 1px; }
.cal-empty { background: transparent; }
.cal-today { background: #2d6be4 !important; border-radius: 8px; }
.cal-greg { font-size: 0.8rem; font-weight: 700; color: #1a3a6b; }
.cal-today .cal-greg { color: white; }
.cal-heb { font-size: 0.58rem; color: #a0b0c8; }
.cal-today .cal-heb { color: rgba(255,255,255,0.7); }
.cal-evt { font-size: 0.72rem; line-height: 1; }

/* ═══ EVENT PREVIEW ═══ */
.evt-preview { border-top: 1px solid #eef2fb; padding-top: 12px; margin-top: 4px; display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px; align-items: start; }
.evt-inv { background: #fdf6ec; border: 1.5px solid #c8a45a; border-radius: 10px; padding: 12px 10px; text-align: center; position: relative; overflow: hidden; }
.evt-inv-deco { position: absolute; inset: 3px; border: 1px solid rgba(200,164,90,0.35); border-radius: 7px; pointer-events: none; }
.evt-inv-lbl { font-size: 0.6rem; color: #a07830; font-weight: 500; margin-bottom: 4px; }
.evt-inv-name { font-size: 1.25rem; font-weight: 700; color: #5a3a1a; line-height: 1.2; }
.evt-inv-date { font-size: 0.65rem; color: #7a6a50; margin-top: 4px; }
.evt-inv-loc { font-size: 0.6rem; color: #a09080; margin-top: 2px; }
.evt-meta { display: flex; flex-direction: column; gap: 5px; }
.evt-badge { display: inline-flex; font-size: 0.72rem; font-weight: 700; background: #fef8ec; color: #a07830; padding: 3px 8px; border-radius: 12px; border: 1px solid #f0d898; margin-bottom: 2px; }
.evt-row { font-size: 0.76rem; color: #374151; }

/* ═══ PHOTO / FACE TAGS ═══ */
.photo-wrap { background: linear-gradient(135deg, #c8d8e8 0%, #aabbc8 100%); border-radius: 12px; height: 160px; position: relative; overflow: hidden; margin-bottom: 2px; }
.face-sq { position: absolute; border: 2px solid rgba(255,255,255,0.55); border-radius: 3px; }
.face-sq-on { border-color: #2d6be4; animation: sq-pulse 2s ease-in-out infinite; }
.face-sq-lbl { position: absolute; bottom: calc(-100% - 4px); left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.55); color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 3px; opacity: 0; transition: opacity 0.2s; white-space: nowrap; pointer-events: none; }
.face-sq-lbl-on { opacity: 1; background: #2d6be4; }
.face-sq:hover .face-sq-lbl { opacity: 1; }
.photo-cap { position: absolute; bottom: 0; right: 0; left: 0; background: rgba(0,0,0,0.5); color: white; font-size: 0.68rem; padding: 5px 10px; text-align: right; }
.thumb { height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

/* ═══ GAME ═══ */
.game-q { background: #f0f6ff; border-radius: 10px; padding: 12px; font-size: 0.9rem; color: #1a3a6b; font-weight: 500; text-align: center; margin-top: 8px; }
.gopt { padding: 9px 12px; border-radius: 9px; font-size: 0.82rem; font-weight: 600; text-align: center; background: #f8faff; border: 1px solid #d1dce8; color: #374151; }
.gopt-right { background: #ecfdf5; border-color: #34d399; color: #065f46; }
.gopt-wrong { background: #fff1f2; border-color: #fda4af; color: #881337; text-decoration: line-through; opacity: 0.6; }
.game-hint { font-size: 0.75rem; color: #6b7a99; text-align: center; padding: 7px; background: #fffbeb; border-radius: 8px; margin-top: 8px; }

/* ═══ EMAIL ═══ */
.email-subj { font-weight: 700; font-size: 0.8rem; color: #1a3a6b; background: #f0f6ff; padding: 8px 10px; border-radius: 8px; margin-bottom: 8px; }
.email-sec { font-weight: 700; font-size: 0.76rem; color: #2d6be4; margin: 8px 0 4px; }
.email-row { font-size: 0.78rem; color: #4a5568; padding-right: 10px; border-right: 2px solid #e7eefb; margin-bottom: 3px; }
.email-foot { font-size: 0.72rem; color: #6b7a99; margin-top: 10px; padding-top: 8px; border-top: 1px solid #eef2fb; }

/* KEYFRAMES */
@keyframes sq-pulse { 0%,100% { box-shadow: 0 0 0 2px rgba(45,107,228,0.3); } 50% { box-shadow: 0 0 0 5px rgba(45,107,228,0); } }
</style>
