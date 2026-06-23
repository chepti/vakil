<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({ canLogin: Boolean });

onMounted(() => {
    const obs = new IntersectionObserver(
        (entries) => entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        }),
        { threshold: 0.1 }
    );
    document.querySelectorAll('.reveal, .reveal-r, .reveal-l').forEach(el => obs.observe(el));
});
</script>

<template>
    <Head title="משפחת ואקיל — האתר המשפחתי שלנו" />

    <div dir="rtl" class="min-h-screen bg-white text-gray-800 overflow-x-hidden" style="font-family:'Rubik','Figtree',sans-serif">

        <!-- ═══ NAV ═══ -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur-sm border-b border-blue-50 shadow-sm">
            <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img src="/favicon.svg" class="h-9 w-9" alt="" />
                    <span class="font-bold text-lg" style="color:#1a3a6b">משפחת ואקיל</span>
                </div>
                <Link v-if="canLogin" :href="route('login')"
                    class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-all duration-200 hover:-translate-y-0.5"
                    style="background:#2d6be4; box-shadow:0 4px 14px rgba(45,107,228,0.35)">
                    כניסה לאתר
                </Link>
            </div>
        </nav>

        <!-- ═══ HERO ═══ -->
        <section class="hero-bg pt-16">
            <div class="max-w-6xl mx-auto px-5 py-20 lg:py-32 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                <!-- Text -->
                <div class="reveal-r">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 border"
                        style="background:rgba(255,255,255,0.12);color:#cde;border-color:rgba(255,255,255,0.18)">
                        🪬 פלטפורמה משפחתית פרטית ומוגנת
                    </div>
                    <h1 class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white leading-snug mb-6">
                        כל המשפחה,<br>במקום אחד
                    </h1>
                    <p class="text-lg mb-10 leading-relaxed max-w-md" style="color:#b8d0f0">
                        עץ משפחה אינטרקטיבי, אירועי חיים, גלריית תמונות עם תיוג פנים, ומשחק טריוויה — פלטפורמה פרטית שמחברת את כולם.
                    </p>
                    <Link v-if="canLogin" :href="route('login')"
                        class="inline-flex items-center gap-2 text-white font-bold px-8 py-4 rounded-full text-base transition-all duration-200 hover:opacity-90 hover:-translate-y-1"
                        style="background:#c8a45a; box-shadow:0 6px 24px rgba(200,164,90,0.4)">
                        כניסה לאתר ←
                    </Link>
                </div>

                <!-- Hero tree card -->
                <div class="reveal-l">
                    <div class="hero-card">
                        <div class="hero-card-header">🌳 עץ משפחה ואקיל</div>

                        <!-- Grandparents row -->
                        <div class="flex items-center justify-center gap-3 mt-4">
                            <div class="person-card-hero male-card">
                                <div class="text-2xl">👴</div>
                                <div class="text-xs font-bold mt-1">יצחק</div>
                            </div>
                            <div class="w-8 h-0.5 opacity-40" style="background:white"></div>
                            <div class="person-card-hero female-card">
                                <div class="text-2xl">👵</div>
                                <div class="text-xs font-bold mt-1">ואקיל</div>
                            </div>
                        </div>

                        <!-- Connector -->
                        <div class="flex flex-col items-center my-2">
                            <div class="w-0.5 h-4 opacity-40" style="background:white"></div>
                            <div class="w-40 h-0.5 opacity-40" style="background:white"></div>
                        </div>

                        <!-- Children row -->
                        <div class="flex items-start justify-around">
                            <div class="person-card-hero male-card">
                                <div class="text-xl">👨</div>
                                <div class="text-xs font-bold mt-1">נחום</div>
                            </div>
                            <div class="person-card-hero female-card pulse-card">
                                <div class="text-xl">👩</div>
                                <div class="text-xs font-bold mt-1">שרה</div>
                            </div>
                            <div class="person-card-hero male-card">
                                <div class="text-xl">👨</div>
                                <div class="text-xs font-bold mt-1">משה</div>
                            </div>
                        </div>

                        <!-- Stats badges -->
                        <div class="flex flex-wrap justify-center gap-2 mt-5">
                            <span class="hero-badge">47 בני משפחה</span>
                            <span class="hero-badge">3 דורות</span>
                            <span class="hero-badge">12 אירועים</span>
                            <span class="hero-badge">94 תמונות</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ QUICK FEATURES ═══ -->
        <section class="py-16" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5">
                <h2 class="text-center text-2xl lg:text-3xl font-bold mb-12 reveal" style="color:#1a3a6b">
                    כל מה שמשפחה צריכה
                </h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="(f, i) in features" :key="i" class="feature-chip reveal" :style="`transition-delay:${i*0.08}s`">
                        <div class="text-3xl mb-3">{{ f.icon }}</div>
                        <div class="font-bold text-sm leading-tight" style="color:#1a3a6b">{{ f.title }}</div>
                        <div class="text-xs mt-1" style="color:#6b7a99">{{ f.sub }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURE 1: TREE ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(45,107,228,0.14)">
                        <div class="demo-card-header">🌳 עץ המשפחה</div>
                        <!-- mini tree -->
                        <div class="flex flex-col items-center gap-1 py-3">
                            <div class="flex items-center gap-5">
                                <div class="mini-node"><div class="dot male-dot"></div><span>יצחק</span></div>
                                <div class="text-gray-300 text-lg">—</div>
                                <div class="mini-node"><div class="dot female-dot"></div><span>ואקיל</span></div>
                            </div>
                            <div class="w-px h-4" style="background:#d1dce8"></div>
                            <div class="w-36 h-px" style="background:#d1dce8"></div>
                            <div class="flex items-start gap-5">
                                <div class="mini-node"><div class="dot male-dot"></div><span>נחום</span></div>
                                <div class="mini-node ring-node"><div class="dot female-dot dot-ring"></div><span class="text-blue-600">שרה</span></div>
                                <div class="mini-node"><div class="dot male-dot"></div><span>משה</span></div>
                            </div>
                            <div class="w-px h-4" style="background:#d1dce8"></div>
                            <div class="flex items-start gap-5">
                                <div class="mini-node"><div class="dot female-dot dot-sm"></div><span>יעל</span></div>
                                <div class="mini-node"><div class="dot male-dot dot-sm"></div><span>דני</span></div>
                                <div class="mini-node"><div class="dot female-dot dot-sm"></div><span>תמר</span></div>
                            </div>
                        </div>
                        <div class="mt-3 text-center text-xs" style="color:#6b7a99">לחיצה על כל אדם פותחת את פרופילו</div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#eaf2ff;color:#2d6be4">🌳 עץ משפחה</div>
                    <h2 class="feat-title">ויזואליזציה של כל הדורות</h2>
                    <p class="feat-desc">עץ אינטרקטיבי שמציג את כל בני המשפחה בצורה גרפית. ניתן לחפש, לנווט, לערוך ישירות ולהדפיס ל-PDF.</p>
                    <ul class="feat-list">
                        <li>✓ מצבי תצוגה: אנכי, רדיאלי, עצי ענפים</li>
                        <li>✓ עריכה ישירה בתוך העץ ללא טעינה מחדש</li>
                        <li>✓ חיפוש מהיר לפי שם</li>
                        <li>✓ הדפסה ל-PDF</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURE 2: EVENTS ═══ -->
        <section class="py-20" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="reveal order-2 lg:order-1">
                    <div class="feat-tag" style="background:#fef8ec;color:#a07830">🎉 אירועים</div>
                    <h2 class="feat-title">כל אירועי החיים, בשני הלוחות</h2>
                    <p class="feat-desc">מלידה ועד בר מצווה, מחתונה ועד פטירה — תיעוד מלא עם תאריכים עבריים ולועזיים, מיקום, תמונת הזמנה וברכות.</p>
                    <ul class="feat-list">
                        <li>✓ תאריכים עבריים ולועזיים</li>
                        <li>✓ ברכות ואמוג'ים מהמשפחה</li>
                        <li>✓ שליחה לכל המשפחה או לענף ספציפי</li>
                        <li>✓ קישור לגלריית תמונות האירוע</li>
                    </ul>
                </div>
                <div class="reveal-l order-1 lg:order-2">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(200,164,90,0.18)">
                        <div class="demo-card-header">📅 אירועים אחרונים</div>
                        <div class="flex flex-col gap-3 mt-2">
                            <div class="event-row" style="border-right-color:#7ba3b0">
                                <span class="text-xl">👶</span>
                                <div>
                                    <div class="event-title">לידת נועה כהן</div>
                                    <div class="event-meta">כ"ה אלול תשפ"ה · 18.9.2025</div>
                                    <div class="event-bless">💬 12 ברכות · ❤️ 8</div>
                                </div>
                            </div>
                            <div class="event-row" style="border-right-color:#c8a45a">
                                <span class="text-xl">📖</span>
                                <div>
                                    <div class="event-title">בר מצווה — יוסי לוי</div>
                                    <div class="event-meta">ב' אב תשפ"ה · 27.7.2025</div>
                                    <div class="event-bless">💬 28 ברכות · 🎉 21</div>
                                </div>
                            </div>
                            <div class="event-row" style="border-right-color:#c48a92">
                                <span class="text-xl">💍</span>
                                <div>
                                    <div class="event-title">חתונת רונה ואייל</div>
                                    <div class="event-meta">י"ב סיון תשפ"ה · 8.6.2025</div>
                                    <div class="event-bless">💬 47 ברכות · 💝 39</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURE 3: PHOTOS ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(128,80,160,0.13)">
                        <div class="demo-card-header">📸 גלריית תמונות</div>
                        <!-- Fake photo with face tags -->
                        <div class="fake-photo">
                            <div class="face-zone" style="top:18%;right:18%">
                                <div class="face-circle" style="background:#7ba3b0"></div>
                                <div class="face-label">יצחק</div>
                            </div>
                            <div class="face-zone face-active" style="top:18%;right:44%">
                                <div class="face-circle face-ring" style="background:#c48a92"></div>
                                <div class="face-label face-label-visible">שרה ✓</div>
                            </div>
                            <div class="face-zone" style="top:18%;right:70%">
                                <div class="face-circle" style="background:#7ba3b0"></div>
                                <div class="face-label">משה</div>
                            </div>
                            <div class="photo-footer">חנוכת הבית · 2024 · 14 מתויגים</div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <div class="photo-thumb" style="background:linear-gradient(135deg,#c8d8e8,#a8b8c8)">🏖️</div>
                            <div class="photo-thumb" style="background:linear-gradient(135deg,#d8c8e8,#b8a8c8)">🎂</div>
                            <div class="photo-thumb" style="background:linear-gradient(135deg,#c8e8d8,#a8c8b8)">💒</div>
                        </div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#f3eeff;color:#7040a0">📸 תמונות</div>
                    <h2 class="feat-title">גלריה עם תיוג פנים חכם</h2>
                    <p class="feat-desc">העלאת תמונות קבוצתיות עם תיוג מדויק של כל פנים. לחיצה על פנים בתמונה — עוברים ישר לפרופיל האדם.</p>
                    <ul class="feat-list">
                        <li>✓ תיוג לפי קואורדינטות מדויקות</li>
                        <li>✓ חיפוש תמונות לפי אדם</li>
                        <li>✓ חיתוך תמונת פרופיל בדפדפן</li>
                        <li>✓ גלריה ייעודית לכל אירוע</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURE 4: GAME ═══ -->
        <section class="py-20" style="background:#f0f6ff">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="reveal order-2 lg:order-1">
                    <div class="feat-tag" style="background:#efffef;color:#207040">🎮 משחק</div>
                    <h2 class="feat-title">משחק טריוויה משפחתי</h2>
                    <p class="feat-desc">"הדרך אל סבתא" — משחק ניחוש שמשתמש בנתוני העץ האמיתי. שאלות על קרבת משפחה עם רמזים חכמים ומסיחים מגובשים.</p>
                    <ul class="feat-list">
                        <li>✓ שאלות מבוססות עץ המשפחה האמיתי</li>
                        <li>✓ רמזים מדורגים</li>
                        <li>✓ מסיחים חכמים לפי מגדר ודור</li>
                    </ul>
                </div>
                <div class="reveal-l order-1 lg:order-2">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(40,160,90,0.13)">
                        <div class="demo-card-header">🎮 הדרך אל סבתא</div>
                        <div class="game-q">מיהו האב של <strong>שרה</strong>?</div>
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div class="game-opt opt-wrong">נחום</div>
                            <div class="game-opt opt-right">יצחק ✓</div>
                            <div class="game-opt">אברהם</div>
                            <div class="game-opt">שלמה</div>
                        </div>
                        <div class="game-hint">💡 רמז: האב גר בתל אביב ומקצועו רופא</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FEATURE 5: EMAIL ═══ -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="reveal-r">
                    <div class="demo-card" style="box-shadow:0 8px 40px rgba(26,58,107,0.14)">
                        <div class="demo-card-header">✉️ מייל חודשי</div>
                        <div class="email-subject">📅 סיכום חודש אב תשפ"ה — משפחת ואקיל</div>
                        <div class="email-sec">🎂 ימי הולדת החודש</div>
                        <div class="email-row">• יעל ואקיל — 15 לחודש</div>
                        <div class="email-row">• דני כהן — 22 לחודש</div>
                        <div class="email-sec">🎉 אירועים קרובים</div>
                        <div class="email-row">• בר מצווה יוסי — 27.7</div>
                        <div class="email-row">• ברית מילה — אחיה — 2.8</div>
                        <div class="email-foot">3 בני משפחה חדשים הצטרפו החודש 🎊</div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="feat-tag" style="background:#eaf2ff;color:#1a3a6b">✉️ מיילים אוטומטיים</div>
                    <h2 class="feat-title">עדכונים אוטומטיים לכל המשפחה</h2>
                    <p class="feat-desc">מערכת מייל חכמה עם הזמנות, עדכוני אירועים, חברי משפחה חדשים, וסיכום חודשי — עם סינון לפי ענף עץ.</p>
                    <ul class="feat-list">
                        <li>✓ הזמנות בקישור אישי (תוקף 7 ימים)</li>
                        <li>✓ דייג'סט חודשי בעברית עם לוח עברי</li>
                        <li>✓ סינון לפי ענף משפחה</li>
                        <li>✓ כל משתמש שולט על ההעדפות שלו</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ CTA ═══ -->
        <section class="py-24 cta-bg">
            <div class="max-w-xl mx-auto px-5 text-center reveal">
                <div class="text-5xl mb-6">🪬</div>
                <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
                    רוצה כזה למשפחה שלך?
                </h2>
                <p class="text-lg mb-8 leading-relaxed" style="color:#b8d0f0">
                    הפלטפורמה בקוד פתוח — ניתן להתאים לכל משפחה.<br>
                    לפרטים, עזרה בהתקנה או שיתוף פעולה:
                </p>
                <a href="mailto:chepti@gmail.com"
                    class="inline-flex items-center gap-2 text-white font-bold px-8 py-4 rounded-full text-base transition-all duration-200 hover:opacity-90 hover:-translate-y-1"
                    style="background:#c8a45a;box-shadow:0 6px 24px rgba(200,164,90,0.4)">
                    ✉️ chepti@gmail.com
                </a>
                <p class="mt-5 text-sm" style="color:#8aace0">
                    קוד פתוח ב-GitHub:
                    <a href="https://github.com/chepti/vakil" target="_blank"
                        class="underline text-white transition-colors hover:text-yellow-300">
                        github.com/chepti/vakil
                    </a>
                </p>
            </div>
        </section>

        <!-- ═══ FOOTER ═══ -->
        <footer class="py-6 text-center text-sm" style="background:#0a1f45;color:#8aace0">
            פותח ב-❤️ עבור משפחת ואקיל
        </footer>

    </div>
</template>

<script>
export default {
    data() {
        return {
            features: [
                { icon: '🌳', title: 'עץ משפחה', sub: 'אינטרקטיבי ועם חיפוש' },
                { icon: '🎉', title: 'אירועי חיים', sub: 'לוח עברי ולועזי' },
                { icon: '📸', title: 'גלריית תמונות', sub: 'עם תיוג פנים' },
                { icon: '🎮', title: 'משחק טריוויה', sub: 'מבוסס העץ האמיתי' },
            ],
        };
    },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

/* BGs */
.hero-bg {
    background: linear-gradient(135deg, #0f2448 0%, #1a3a6b 55%, #1e4888 100%);
    position: relative;
    overflow: hidden;
}
.hero-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(45,107,228,0.22) 0%, transparent 65%);
    pointer-events: none;
}
.cta-bg {
    background: linear-gradient(135deg, #0f2448 0%, #1a3a6b 100%);
}

/* REVEAL */
.reveal, .reveal-r, .reveal-l {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.65s ease, transform 0.65s ease;
}
.reveal-r { transform: translateX(36px); }
.reveal-l { transform: translateX(-36px); }
.reveal.visible, .reveal-r.visible, .reveal-l.visible {
    opacity: 1;
    transform: translate(0, 0);
}

/* HERO CARD */
.hero-card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 22px;
    padding: 22px;
    color: white;
}
.hero-card-header {
    font-weight: 700;
    font-size: 0.9rem;
    opacity: 0.9;
    text-align: center;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
}
.person-card-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 14px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(8px);
    min-width: 70px;
    transition: transform 0.2s;
}
.person-card-hero:hover { transform: scale(1.05); }
.male-card { background: rgba(120,159,172,0.2); border-color: rgba(120,159,172,0.4); }
.female-card { background: rgba(196,138,146,0.2); border-color: rgba(196,138,146,0.4); }
.pulse-card { animation: pulse 2.5s ease-in-out infinite; }
.hero-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1px solid rgba(200,164,90,0.4);
    background: rgba(200,164,90,0.15);
    color: #f0d090;
}

/* FEATURE CHIPS */
.feature-chip {
    background: white;
    border-radius: 18px;
    padding: 22px 16px;
    text-align: center;
    border: 1px solid #e7eefb;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 18px rgba(26,58,107,0.07);
}
.feature-chip:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(26,58,107,0.14); }

/* DEMO CARDS */
.demo-card {
    background: white;
    border-radius: 20px;
    padding: 18px;
    border: 1px solid #e7eefb;
}
.demo-card-header {
    font-weight: 700;
    font-size: 0.88rem;
    color: #1a3a6b;
    padding-bottom: 10px;
    margin-bottom: 4px;
    border-bottom: 1px solid #eef2fb;
}

/* MINI TREE */
.mini-node { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.mini-node span { font-size: 0.7rem; font-weight: 600; color: #4a5568; }
.dot { width: 36px; height: 36px; border-radius: 50%; }
.dot-sm { width: 26px; height: 26px; }
.male-dot { background: rgb(120,159,172); }
.female-dot { background: rgb(196,138,146); }
.dot-ring {
    box-shadow: 0 0 0 3px rgba(45,107,228,0.3);
    animation: ring-pulse 2s ease-in-out infinite;
}

/* FEATURE TEXT */
.feat-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 12px;
}
.feat-title {
    font-size: 1.65rem;
    font-weight: 700;
    color: #1a3a6b;
    line-height: 1.3;
    margin-bottom: 10px;
}
.feat-desc { color: #6b7a99; line-height: 1.75; margin-bottom: 14px; font-size: 0.95rem; }
.feat-list { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.feat-list li { font-size: 0.88rem; color: #374151; }

/* EVENTS */
.event-row {
    display: flex;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    border-right: 3px solid #e7eefb;
    background: #f8faff;
}
.event-title { font-weight: 600; font-size: 0.85rem; color: #1a3a6b; }
.event-meta { font-size: 0.72rem; color: #6b7a99; margin: 2px 0; }
.event-bless { font-size: 0.72rem; color: #2d6be4; }

/* PHOTO DEMO */
.fake-photo {
    height: 165px;
    border-radius: 12px;
    background: linear-gradient(135deg, #c8d8e8 0%, #aababc 100%);
    position: relative;
    overflow: hidden;
}
.face-zone { position: absolute; display: flex; flex-direction: column; align-items: center; }
.face-circle { width: 42px; height: 42px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.6); }
.face-ring {
    border: 2px solid #2d6be4;
    animation: ring-pulse 2s ease-in-out infinite;
}
.face-label {
    background: rgba(0,0,0,0.55);
    color: white;
    font-size: 0.62rem;
    padding: 2px 6px;
    border-radius: 4px;
    margin-top: 3px;
    opacity: 0;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.face-label-visible { opacity: 1; background: #2d6be4; }
.face-zone:hover .face-label { opacity: 1; }
.photo-footer {
    position: absolute;
    bottom: 0; right: 0; left: 0;
    background: rgba(0,0,0,0.5);
    color: white;
    font-size: 0.7rem;
    padding: 5px 10px;
    text-align: right;
}
.photo-thumb {
    height: 52px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

/* GAME */
.game-q {
    background: #f0f6ff;
    border-radius: 10px;
    padding: 12px;
    font-size: 0.9rem;
    color: #1a3a6b;
    font-weight: 500;
    text-align: center;
    margin-top: 8px;
}
.game-opt {
    padding: 9px 12px;
    border-radius: 9px;
    font-size: 0.82rem;
    font-weight: 600;
    text-align: center;
    background: #f8faff;
    border: 1px solid #d1dce8;
    color: #374151;
}
.opt-right { background: #ecfdf5; border-color: #34d399; color: #065f46; }
.opt-wrong { background: #fff1f2; border-color: #fda4af; color: #881337; text-decoration: line-through; opacity: 0.6; }
.game-hint { font-size: 0.75rem; color: #6b7a99; text-align: center; padding: 7px; background: #fffbeb; border-radius: 8px; margin-top: 8px; }

/* EMAIL */
.email-subject { font-weight: 700; font-size: 0.8rem; color: #1a3a6b; background: #f0f6ff; padding: 8px 10px; border-radius: 8px; margin-bottom: 8px; }
.email-sec { font-weight: 700; font-size: 0.76rem; color: #2d6be4; margin: 8px 0 4px; }
.email-row { font-size: 0.78rem; color: #4a5568; padding-right: 10px; border-right: 2px solid #e7eefb; margin-bottom: 3px; }
.email-foot { font-size: 0.72rem; color: #6b7a99; margin-top: 10px; padding-top: 8px; border-top: 1px solid #eef2fb; }

/* KEYFRAMES */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.04); }
}
@keyframes ring-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(45,107,228,0.3); }
    50% { box-shadow: 0 0 0 7px rgba(45,107,228,0); }
}
</style>
