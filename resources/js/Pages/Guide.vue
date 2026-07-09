<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, reactive, computed } from 'vue';
import { Copy, Check } from 'lucide-vue-next';

defineProps({ canLogin: Boolean });

const STORAGE_PREFIX = 'vakil-guide:';
const STEP_KEYS = [
    't-git', 't-node', 't-php', 't-github', 't-ai',
    's-fork', 's-clone', 's-setup', 's-env',
    'r-signup', 'r-project', 'r-db', 'r-env', 'r-migrate', 'r-admin',
    'u-onboard', 'u-people', 'u-invite', 'u-photos', 'u-admin', 'u-seed',
];

const checked = reactive({});
STEP_KEYS.forEach(k => { checked[k] = false; });

onMounted(() => {
    STEP_KEYS.forEach(k => {
        try { checked[k] = localStorage.getItem(STORAGE_PREFIX + k) === '1'; } catch (e) {}
    });

    const obs = new IntersectionObserver(
        entries => entries.forEach(e => e.isIntersecting && e.target.classList.add('visible')),
        { threshold: 0.1 }
    );
    document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
});

function toggle(key) {
    checked[key] = !checked[key];
    try { localStorage.setItem(STORAGE_PREFIX + key, checked[key] ? '1' : '0'); } catch (e) {}
}

const doneCount = computed(() => STEP_KEYS.filter(k => checked[k]).length);

const copiedKey = reactive({ value: null });
function copy(text, key) {
    const done = () => { copiedKey.value = key; setTimeout(() => { if (copiedKey.value === key) copiedKey.value = null; }, 1500); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else {
        fallbackCopy(text, done);
    }
}
function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); done(); } catch (e) {}
    document.body.removeChild(ta);
}
</script>

<template>
    <Head title="מדריך הקמה" />
    <div dir="rtl" style="font-family:'Rubik','Figtree',sans-serif" class="min-h-screen bg-white text-gray-800">

        <!-- ═══ NAV ═══ -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur-sm border-b border-blue-50 shadow-sm">
            <div class="max-w-3xl mx-auto px-5 h-16 flex items-center justify-between gap-3">
                <Link href="/" class="flex items-center gap-2.5">
                    <img src="/favicon.svg" class="h-9 w-9" alt="" />
                    <span class="font-bold text-lg hidden sm:inline" style="color:#1a3a6b">אתר המשפחה</span>
                </Link>
                <span class="progress-chip">{{ doneCount }} / {{ STEP_KEYS.length }} הושלמו</span>
                <Link v-if="canLogin" :href="route('login')"
                    class="inline-flex items-center text-white text-sm font-semibold px-4 py-2 rounded-full transition-all hover:-translate-y-0.5 whitespace-nowrap"
                    style="background:#2d6be4;box-shadow:0 4px 14px rgba(45,107,228,0.35)">
                    כניסה לאתר
                </Link>
            </div>
        </nav>

        <main class="max-w-3xl mx-auto px-5 pt-32 pb-24">

            <!-- ═══ HERO ═══ -->
            <section class="reveal mb-14">
                <div class="eyebrow">🧭 לפני שמתחילים</div>
                <h1 class="hero-title">להקים אתר משפחה משלכם</h1>
                <p class="hero-lede">
                    המדריך הזה מיועד למי שאין לו רקע טכני, אבל <strong>יש לו עוזר AI לקוד</strong> —
                    Claude Code, Codex, או כלי דומה. בכל שלב מוסבר בעברית פשוטה מה קורה ולמה,
                    ולא רק אילו פקודות להריץ. קטעים שמסומנים ״הוראה לעוזר״ אפשר להדביק אליו ישירות.
                </p>
            </section>

            <!-- ═══ CONCEPT FLOW ═══ -->
            <section class="reveal mb-16">
                <h2 class="section-title">מה הקשר בין הדברים</h2>
                <p class="section-sub">ארבעה חלקים, שכל אחד עושה תפקיד אחר.</p>
                <div class="flow">
                    <div class="flow-node">
                        <span class="glyph">🧩</span>
                        <div class="label">הקוד ב-GitHub</div>
                        <div class="sub">שרטוט של אתר. עדיין לא אתר פעיל.</div>
                    </div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-node">
                        <span class="glyph">💻</span>
                        <div class="label">המחשב שלכם</div>
                        <div class="sub">מכינים את השרטוט, בעזרת עוזר ה-AI.</div>
                    </div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-node">
                        <span class="glyph">☁️</span>
                        <div class="label">שרת בענן</div>
                        <div class="sub">שם האתר חי, כל הזמן, ומחזיק גם את מסד הנתונים.</div>
                    </div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-node">
                        <span class="glyph">📧</span>
                        <div class="label">הזמנות במייל</div>
                        <div class="sub">כל דמות עם כתובת מייל — מוזמנת להצטרף.</div>
                    </div>
                </div>
                <div class="callout">
                    <strong>שימו לב:</strong> אתם לא צריכים להתקין מסד נתונים במחשב שלכם.
                    השרת בענן (בהמשך נשתמש ב-Railway) מספק אחד מוכן, בלחיצת כפתור.
                    המחשב שלכם הוא ״בית מלאכה״ חד־פעמי — שם מכינים את הקוד לפני שהוא עולה לאוויר.
                </div>
            </section>

            <!-- ═══ PHASE 1 — TOOLS ═══ -->
            <section class="reveal phase mb-16">
                <div class="phase-head">
                    <div class="phase-num">1</div>
                    <h2 class="phase-title">התקנת הכלים</h2>
                </div>
                <p class="phase-desc">
                    ארבעה כלים בסיסיים, התקנה חד־פעמית. לכל אחד — מה הוא עושה, פקודת בדיקה אם כבר קיים, וקישור הורדה.
                </p>

                <div class="card" :class="{ 'is-done': checked['t-git'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['t-git']" @change="toggle('t-git')">
                        <div class="card-body">
                            <div class="card-title">Git <span class="pill">שולט בגרסאות הקוד</span></div>
                            <p class="card-text">מעביר את הקוד מ-GitHub למחשב שלכם, ובחזרה. בלעדיו אי אפשר לקבל את הפרויקט או לעדכן אותו בעתיד.</p>
                            <div class="codeblock">
                                <pre class="mono" dir="ltr">git --version</pre>
                                <button class="copy-btn" type="button" @click="copy('git --version', 'git')" :aria-label="'העתק פקודה'">
                                    <Check v-if="copiedKey.value === 'git'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                            <div class="expect">מצופה: <span class="mono">git version 2.x.x</span></div>
                            <a class="pill pill-link" href="https://git-scm.com/downloads" target="_blank" rel="noopener">⬇ הורדה — git-scm.com</a>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['t-node'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['t-node']" @change="toggle('t-node')">
                        <div class="card-body">
                            <div class="card-title">Node.js <span class="pill">בונה את מה שרואים בדפדפן</span></div>
                            <p class="card-text">אחראי על הכפתורים, עץ המשפחה והטפסים. מתקינים אותו ומקבלים גם כלי בשם npm בחינם.</p>
                            <div class="codeblock">
                                <pre class="mono" dir="ltr">node -v
npm -v</pre>
                                <button class="copy-btn" type="button" @click="copy('node -v\nnpm -v', 'node')" aria-label="העתק פקודה">
                                    <Check v-if="copiedKey.value === 'node'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                            <div class="expect">מצופה: <span class="mono">v20.x.x</span> ו-<span class="mono">10.x.x</span></div>
                            <a class="pill pill-link" href="https://nodejs.org" target="_blank" rel="noopener">⬇ הורדה — nodejs.org (כפתור LTS)</a>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['t-php'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['t-php']" @change="toggle('t-php')">
                        <div class="card-body">
                            <div class="card-title">PHP + Composer <span class="pill">המנוע של האתר</span></div>
                            <p class="card-text">ה״מוח״ שמריץ את הלוגיקה — שומר דמויות, שולח מיילים, מחשב תאריך עברי. Composer הוא מנהל התוספים של PHP.</p>
                            <div class="codeblock">
                                <pre class="mono" dir="ltr">php -v
composer --version</pre>
                                <button class="copy-btn" type="button" @click="copy('php -v\ncomposer --version', 'php')" aria-label="העתק פקודה">
                                    <Check v-if="copiedKey.value === 'php'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                            <div class="expect">מצופה: <span class="mono">PHP 8.3.x</span> וגרסת Composer כלשהי</div>

                            <div class="divider-note">שתי דרכים להתקין — בוחרים אחת</div>
                            <div class="choices">
                                <div class="choice">
                                    <h4>🅰️ הכל בחבילה אחת</h4>
                                    <p>מומלץ אם זו הפעם הראשונה שלכם עם קוד. מתקין PHP, שרת מקומי ו-MySQL בהתקנה אחת.</p>
                                    <a class="pill pill-link" href="https://laragon.org/download/" target="_blank" rel="noopener">⬇ Laragon (Windows)</a>
                                </div>
                                <div class="choice">
                                    <h4>🅱️ התקנה נפרדת</h4>
                                    <p>מומלץ אם תרצו להשתמש בכלים האלה גם בפרויקטים אחרים בעתיד.</p>
                                    <a class="pill pill-link" href="https://www.php.net/downloads" target="_blank" rel="noopener">⬇ PHP</a>
                                    <a class="pill pill-link" href="https://getcomposer.org/download/" target="_blank" rel="noopener">⬇ Composer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['t-github'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['t-github']" @change="toggle('t-github')">
                        <div class="card-body">
                            <div class="card-title">חשבון GitHub <span class="pill">התיקייה המשפחתית בענן</span></div>
                            <p class="card-text">שם יישמר העותק שלכם של הקוד. חינמי, ולוקח שתי דקות. אם כבר יש לכם חשבון — פשוט להתחבר.</p>
                            <a class="pill pill-link" href="https://github.com/join" target="_blank" rel="noopener">📝 הרשמה — github.com/join</a>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['t-ai'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['t-ai']" @change="toggle('t-ai')">
                        <div class="card-body">
                            <div class="card-title">עוזר AI לקוד <span class="pill">מבצע בשבילכם את השלבים הטכניים</span></div>
                            <p class="card-text">כל קטע שמסומן ״הוראה לעוזר״ אפשר להדביק ישירות אליו — הוא זה שמריץ את הפקודות בפועל.</p>
                            <a class="pill pill-link" href="https://claude.com/claude-code" target="_blank" rel="noopener">🔗 Claude Code</a>
                            <span class="pill pill-muted">או כל כלי דומה שיש לכם</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ PHASE 2 — CODE ═══ -->
            <section class="reveal phase mb-16">
                <div class="phase-head">
                    <div class="phase-num">2</div>
                    <h2 class="phase-title">הכנת הקוד</h2>
                </div>
                <p class="phase-desc">כאן יוצרים עותק פרטי של הפרויקט, ומכינים אותו לפעולה — הכול על המחשב שלכם.</p>

                <div class="card" :class="{ 'is-done': checked['s-fork'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['s-fork']" @change="toggle('s-fork')">
                        <div class="card-body">
                            <div class="card-title">2.1 — עותק פרטי (Fork)</div>
                            <p class="card-text">בעמוד הפרויקט ב-GitHub, לוחצים על הכפתור Fork בפינה הימנית העליונה. נוצר עותק זהה, רשום על שמכם, שאתם שולטים בו לגמרי.</p>
                            <a class="pill pill-link" href="https://github.com/chepti/vakil" target="_blank" rel="noopener">🔗 עמוד הפרויקט המקורי</a>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['s-clone'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['s-clone']" @change="toggle('s-clone')">
                        <div class="card-body">
                            <div class="card-title">2.2 — הורדה למחשב</div>
                            <p class="card-text">פותחים טרמינל בתיקייה שנוח לכם, ומדביקים (מחליפים <span class="mono">YOUR-USERNAME</span> בשם המשתמש שלכם ב-GitHub):</p>
                            <div class="codeblock">
                                <pre class="mono" dir="ltr">git clone https://github.com/YOUR-USERNAME/vakil.git
cd vakil</pre>
                                <button class="copy-btn" type="button" @click="copy('git clone https://github.com/YOUR-USERNAME/vakil.git\ncd vakil', 'clone')" aria-label="העתק פקודה">
                                    <Check v-if="copiedKey.value === 'clone'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['s-setup'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['s-setup']" @change="toggle('s-setup')">
                        <div class="card-body">
                            <div class="card-title">2.3 — הוראה לעוזר: הכנת התלויות</div>
                            <p class="card-text">פותחים את התיקייה עם עוזר ה-AI, ומדביקים:</p>
                            <div class="codeblock">
                                <pre dir="ltr">התקן את כל התלויות של הפרויקט (composer install, npm install),
ואז צור קובץ .env מתוך .env.example.</pre>
                                <button class="copy-btn" type="button" @click="copy('התקן את כל התלויות של הפרויקט (composer install, npm install), ואז צור קובץ .env מתוך .env.example.', 'setup')" aria-label="העתק הוראה">
                                    <Check v-if="copiedKey.value === 'setup'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['s-env'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['s-env']" @change="toggle('s-env')">
                        <div class="card-body">
                            <div class="card-title">2.4 — כרטיס הזהות של האתר</div>
                            <p class="card-text">
                                הקובץ <span class="mono">.env</span> הוא כרטיס פרטים אישי לאתר — שם המשפחה, כתובת מייל ליצירת קשר,
                                ופרטי חיבור למסד הנתונים. את פרטי מסד הנתונים תקבלו בשלב 3, מ-Railway — אפשר להשאיר ריק בינתיים.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ PHASE 3 — DEPLOY ═══ -->
            <section class="reveal phase mb-16">
                <div class="phase-head">
                    <div class="phase-num">3</div>
                    <h2 class="phase-title">עלייה לאוויר</h2>
                </div>
                <p class="phase-desc">
                    מעבירים את האתר משרת מקומי (רואים רק אתם) לשרת ציבורי, שכל המשפחה יכולה להיכנס אליו.
                    נעזר ב-Railway — חינם לחודש הראשון (5$ קרדיט), ואז עלות קטנה של דולר-שניים לחודש.
                </p>

                <div class="card" :class="{ 'is-done': checked['r-signup'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-signup']" @change="toggle('r-signup')">
                        <div class="card-body">
                            <div class="card-title">3.1 — הרשמה ל-Railway</div>
                            <p class="card-text">נרשמים עם חשבון GitHub — זה גם מקשר אוטומטית בין השניים.</p>
                            <a class="pill pill-link" href="https://railway.app" target="_blank" rel="noopener">🔗 railway.app</a>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['r-project'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-project']" @change="toggle('r-project')">
                        <div class="card-body">
                            <div class="card-title">3.2 — פרויקט חדש מהקוד שלכם</div>
                            <p class="card-text">
                                <span class="mono">New Project</span> ← <span class="mono">Deploy from GitHub repo</span> ←
                                בוחרים בעותק שיצרתם בשלב 2.1. Railway בונה את האתר אוטומטית מהקוד, ומחדש בכל עדכון עתידי.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['r-db'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-db']" @change="toggle('r-db')">
                        <div class="card-body">
                            <div class="card-title">3.3 — הוספת מסד נתונים</div>
                            <p class="card-text">
                                בתוך הפרויקט ב-Railway: <span class="mono">+ New</span> ← <span class="mono">Database</span> ←
                                <span class="mono">MySQL</span>. זה ה״ארון״ שבו יישמרו כל הדמויות, התמונות והאירועים. מוכן תוך דקה.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['r-env'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-env']" @change="toggle('r-env')">
                        <div class="card-body">
                            <div class="card-title">3.4 — העברת כרטיס הזהות</div>
                            <p class="card-text">
                                בהגדרות הפרויקט יש לשונית <span class="mono">Variables</span> — שם מדביקים את מה שהיה בקובץ
                                <span class="mono">.env</span>, כולל פרטי החיבור למסד הנתונים (Railway מציג אותם אוטומטית בלשונית ה-MySQL).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['r-migrate'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-migrate']" @change="toggle('r-migrate')">
                        <div class="card-body">
                            <div class="card-title">3.5 — הוראה לעוזר: בניית הטבלאות</div>
                            <p class="card-text">מדביקים לעוזר ה-AI (הוא יכול להשתמש ב-Railway CLI):</p>
                            <div class="codeblock">
                                <pre dir="ltr">התחבר ל-Railway CLI והרץ php artisan migrate כדי לבנות
את כל הטבלאות במסד הנתונים החדש.</pre>
                                <button class="copy-btn" type="button" @click="copy('התחבר ל-Railway CLI והרץ php artisan migrate כדי לבנות את כל הטבלאות במסד הנתונים החדש.', 'migrate')" aria-label="העתק הוראה">
                                    <Check v-if="copiedKey.value === 'migrate'" :size="14" />
                                    <Copy v-else :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['r-admin'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['r-admin']" @change="toggle('r-admin')">
                        <div class="card-body">
                            <div class="card-title">3.6 — יצירת המנהל/ת הראשונה</div>
                            <p class="card-text">
                                מדביקים לעוזר: <span class="mono">הרץ php artisan db:seed --class=AdminSeeder</span> —
                                זה יוצר את החשבון הראשון עם הרשאת ניהול מלאה.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="callout accent">
                    💡 רוצים חלופה בלי Railway? אפשר גם אחסון משותף (כמו Hostinger) — עובד, אבל דורש
                    יותר שלבי הגדרה ידניים בשרת. Railway מתאים יותר למי שרוצה להתחיל בלי SSH.
                </div>
            </section>

            <!-- ═══ PHASE 4 — FIRST USE ═══ -->
            <section class="reveal phase mb-16">
                <div class="phase-head">
                    <div class="phase-num">4</div>
                    <h2 class="phase-title">שלבי המילוי — האתר באוויר, ומה עכשיו</h2>
                </div>
                <p class="phase-desc">מכאן זה כבר לא טכני. סדר טבעי שממנו כל בני המשפחה יכולים להמשיך בעצמם.</p>

                <div class="card" :class="{ 'is-done': checked['u-onboard'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-onboard']" @change="toggle('u-onboard')">
                        <div class="card-body">
                            <div class="card-title">4.1 — כניסה ראשונה</div>
                            <p class="card-text">נכנסים עם החשבון שנוצר בשלב 3.6, וממלאים את הפרופיל האישי. אתם הופכים אוטומטית לדמות הראשונה בעץ.</p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['u-people'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-people']" @change="toggle('u-people')">
                        <div class="card-body">
                            <div class="card-title">4.2 — הוספת דמויות ובחירת דמות ראשית</div>
                            <p class="card-text">
                                מוסיפים הורים, בני/בנות זוג, ילדים ואחים. לכל דמות: שם, תאריך לידה (עברי ולועזי מומרים אוטומטית), ותמונה אם יש.
                            </p>
                            <p class="card-text">
                                בעמוד <span class="mono">/family-tree</span>, בלחיצה על דמות ייפתח כרטיס עם כפתור
                                <strong>״הגדר כברירת מחדל״</strong> (למנהלים בלבד). כדאי לבחור את הדמות המשמעותית ביותר במשפחה —
                                היא תהפוך למרכז העץ, לשורש במשחק הניחוש, ולנקודת ההתייחסות ב״של X של Y״ שמופיע לצד שמות בכל האתר.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['u-invite'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-invite']" @change="toggle('u-invite')">
                        <div class="card-body">
                            <div class="card-title">4.3 — הזמנת בני משפחה</div>
                            <p class="card-text">
                                <strong>זה החלק החשוב:</strong> כל דמות שמזינים לה כתובת מייל מקבלת אוטומטית הזמנה, ויכולה
                                להיכנס ולהשלים את הפרטים של עצמה. אתם לא צריכים למלא הכול לבד — המשימה מתחלקת בין כל המשפחה.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['u-photos'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-photos']" @change="toggle('u-photos')">
                        <div class="card-body">
                            <div class="card-title">4.4 — תמונות ותיוג</div>
                            <p class="card-text">
                                מעלים תמונה קבוצתית מאירוע, ומסמנים בתמונה מי כל אחד מהמופיעים בה. כל תמונה מתויגת מקושרת
                                אוטומטית לכרטיס של כל מי שסומן בה — גם אם יש בה עשרות אנשים.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['u-admin'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-admin']" @change="toggle('u-admin')">
                        <div class="card-body">
                            <div class="card-title">4.5 — סיור בעמוד הניהול</div>
                            <p class="card-text">
                                בעמוד <span class="mono">/admin</span> (מנהלים בלבד) יש כמה כלים שמכוונים לאן להשקיע זמן בהתחלה:
                            </p>
                            <ul class="feat-list-guide">
                                <li>✓ רשימה מוכנה של דמויות <strong>בלי תאריך לידה</strong> ובלי תמונה — כדי לדעת בדיוק מי חסר</li>
                                <li>✓ מעקב הזמנות — מי הוזמן, מי פעיל, מי פג תוקף</li>
                                <li>✓ ניהול משתמשים — קידום למנהל, מחיקת משתמש (הדמות בעץ נשארת)</li>
                                <li>✓ ארכיון מסמכים משפחתיים — קבצים, לא רק תמונות</li>
                                <li>✓ ייצוא CSV — כל המשפחה, רשימת משתמשים, או רשימת ימי הולדת</li>
                                <li>✓ שליחת עדכון חודשי (תצוגה מקדימה לעצמכם, או לכולם) והודעה מותאמת אישית</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card" :class="{ 'is-done': checked['u-seed'] }">
                    <div class="card-row">
                        <input type="checkbox" class="card-check" :checked="checked['u-seed']" @change="toggle('u-seed')">
                        <div class="card-body">
                            <div class="card-title">4.6 — תוכן ראשוני, כדי שהאתר לא יתחיל ריק</div>
                            <p class="card-text">
                                לפני שמזמינים את כל המשפחה, שווה להוסיף דוגמה אחת מכל סוג — כך שמי שנכנס בפעם הראשונה מבין מה אפשר לעשות:
                            </p>
                            <ul class="feat-list-guide">
                                <li>✓ אירוע אחד ב-<span class="mono">/events/create</span> — לידה, יום נישואין, כל דבר קרוב</li>
                                <li>✓ מתכון אחד ב-<span class="mono">/recipes/create</span> — מתכון משפחתי ותיק, מקושר לדמות שהמתכון שלה</li>
                                <li>✓ סיפור שם אחד ב-<span class="mono">/name-stories/create</span> — למה קראו למישהו בשמו, ועל שם מי</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="guide-footer">
                <p>
                    נתקעתם באמצע? כתבו למי שהקימה את הפרויקט המקורי —
                    <a href="mailto:chepti@gmail.com">chepti@gmail.com</a> —
                    או עיינו בקובץ README בתוך
                    <a href="https://github.com/chepti/vakil" target="_blank" rel="noopener">עמוד הפרויקט</a>.
                </p>
                <p class="mt-2">הסימונים שלכם נשמרים במחשב הזה בלבד, כדי שתוכלו להמשיך מאיפה שהפסקתם.</p>
                <Link href="/" class="pill pill-link mt-4 inline-block">← חזרה לעמוד הבית</Link>
            </footer>

        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.mono{ font-family:ui-monospace,"Cascadia Code","SF Mono",Consolas,"Courier New",monospace; direction:ltr; unicode-bidi:isolate; }

.reveal{ opacity:0; transform:translateY(20px); transition:opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible{ opacity:1; transform:translateY(0); }

.progress-chip{
    font-size:0.78rem; font-weight:700; color:#a6711f;
    background:#faf1e2; padding:5px 13px; border-radius:100px;
    font-variant-numeric:tabular-nums; white-space:nowrap;
}

.eyebrow{ display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:700; color:#a6711f; margin-bottom:14px; }
.hero-title{ font-size:2rem; font-weight:700; letter-spacing:-0.01em; color:#1a3a6b; margin-bottom:14px; line-height:1.3; }
.hero-lede{ color:#6b7a99; font-size:1.02rem; line-height:1.75; max-width:60ch; }
.hero-lede strong{ color:#1a3a6b; }

.section-title{ font-size:1.3rem; font-weight:700; color:#1a3a6b; margin-bottom:6px; }
.section-sub{ color:#6b7a99; font-size:0.92rem; margin-bottom:20px; }

.flow{ display:flex; align-items:stretch; gap:0; margin-bottom:20px; flex-wrap:wrap; }
.flow-node{ flex:1 1 130px; background:#fff; border:1px solid #e7eefb; border-radius:14px; padding:14px 12px; text-align:center; box-shadow:0 4px 18px rgba(26,58,107,0.06); }
.flow-node .glyph{ font-size:1.4rem; display:block; margin-bottom:6px; }
.flow-node .label{ font-weight:700; font-size:0.84rem; color:#1a3a6b; margin-bottom:4px; }
.flow-node .sub{ font-size:0.72rem; color:#8896ad; line-height:1.5; }
.flow-arrow{ flex:0 0 auto; width:28px; display:flex; align-items:center; justify-content:center; color:#b7c2d8; font-size:1.05rem; }
@media (max-width:640px){
    .flow{ flex-direction:column; }
    .flow-arrow{ width:100%; height:20px; transform:rotate(-90deg); }
}

.callout{ background:#f7f9fd; border:1px solid #e7eefb; border-radius:14px; padding:15px 18px; margin:18px 0; font-size:0.9rem; color:#6b7a99; line-height:1.7; }
.callout strong{ color:#1a3a6b; }
.callout.accent{ border-color:#f0d898; background:#fef8ec; color:#8a6a30; }

.phase-head{ display:flex; align-items:baseline; gap:13px; margin-bottom:6px; }
.phase-num{ flex:0 0 auto; width:32px; height:32px; border-radius:9px; background:#faf1e2; color:#a6711f; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.95rem; }
.phase-title{ font-size:1.3rem; font-weight:700; color:#1a3a6b; }
.phase-desc{ color:#6b7a99; margin:0 0 20px; padding-inline-start:45px; max-width:58ch; font-size:0.94rem; line-height:1.7; }

.card{ background:#fff; border:1px solid #e7eefb; border-radius:14px; padding:16px 18px; margin-bottom:10px; box-shadow:0 4px 18px rgba(26,58,107,0.05); transition:border-color 0.2s; }
.card.is-done{ border-color:#2f7d54; }
.card.is-done .card-title{ color:#2f7d54; }
.card-row{ display:flex; align-items:flex-start; gap:11px; }
.card-check{ flex:0 0 auto; width:20px; height:20px; margin-top:2px; accent-color:#2f7d54; cursor:pointer; }
.card-body{ flex:1; min-width:0; }
.card-title{ font-weight:700; font-size:0.98rem; color:#1a3a6b; margin-bottom:5px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.card-text{ color:#6b7a99; font-size:0.9rem; line-height:1.7; margin-bottom:9px; }
.card-text:last-child{ margin-bottom:0; }
.card-text .mono{ font-size:0.85em; color:#1a3a6b; }

.feat-list-guide{ list-style:none; padding:0; display:flex; flex-direction:column; gap:5px; margin-top:4px; }
.feat-list-guide li{ font-size:0.86rem; color:#374151; line-height:1.6; }
.feat-list-guide .mono{ font-size:0.85em; color:#2d6be4; }

.pill{ display:inline-flex; align-items:center; gap:5px; font-size:0.7rem; font-weight:700; padding:3px 10px; border-radius:100px; background:#eaf2ff; color:#2454a6; }
.pill-link{ text-decoration:none; }
.pill-link:hover{ text-decoration:underline; }
.pill-muted{ background:#f2f4f9; color:#8896ad; }

.codeblock{ position:relative; background:#101a2e; border-radius:10px; margin:8px 0 4px; overflow:hidden; }
.codeblock pre{ margin:0; padding:12px 40px 12px 14px; overflow-x:auto; color:#d7e3f5; font-size:0.83rem; line-height:1.6; }
.copy-btn{
    position:absolute; top:8px; left:8px;
    background:rgba(255,255,255,0.08); color:#d7e3f5;
    border:1px solid rgba(215,227,245,0.15); border-radius:6px;
    width:26px; height:26px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background 0.15s;
}
.copy-btn:hover{ background:rgba(255,255,255,0.18); }

.expect{ font-size:0.78rem; color:#8896ad; margin:6px 0 9px; }
.expect .mono{ color:#6b7a99; }

.divider-note{ display:flex; align-items:center; gap:10px; margin:16px 0 10px; font-size:0.76rem; color:#8896ad; font-weight:600; }
.divider-note::before, .divider-note::after{ content:""; flex:1; height:1px; background:#eef2fb; }

.choices{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }
@media (max-width:560px){ .choices{ grid-template-columns:1fr; } }
.choice{ background:#f7f9fd; border:1px solid #eef2fb; border-radius:11px; padding:12px 14px; }
.choice h4{ font-size:0.84rem; margin-bottom:5px; color:#1a3a6b; }
.choice p{ font-size:0.78rem; color:#6b7a99; margin:0 0 8px; line-height:1.6; }

.guide-footer{ border-top:1px solid #eef2fb; padding-top:24px; margin-top:16px; color:#8896ad; font-size:0.86rem; line-height:1.7; }
.guide-footer a{ color:#2d6be4; }

@media (prefers-reduced-motion: reduce){
    .reveal{ transition:none; }
    .copy-btn{ transition:none; }
}
</style>
