<template>
  <AppLayout title="לוח אירועים">
    <div class="events-page" dir="rtl">
      <div class="page-head">
        <h1>📅 לוח אירועים</h1>
        <Link href="/events/create" class="btn-new">+ אירוע חדש</Link>
      </div>

      <!-- לוח שנה -->
      <div class="calendar">
        <div class="cal-header">
          <div class="cal-nav">
            <button @click="prevMonth" class="nav-btn">‹</button>
            <button @click="goToday" class="today-btn">היום</button>
            <button @click="nextMonth" class="nav-btn">›</button>
          </div>
          <div class="cal-title">
            <span class="heb-title">{{ hebrewTitle }}</span>
            <span class="greg-title">{{ gregTitle }}</span>
          </div>
        </div>

        <div class="weekdays">
          <div v-for="d in weekdays" :key="d" class="weekday">{{ d }}</div>
        </div>

        <div class="cal-grid">
          <div
            v-for="cell in grid"
            :key="cell.date"
            class="cal-cell"
            :class="{ 'out-month': !cell.inMonth, today: cell.isToday }"
          >
            <div class="cell-dates">
              <span class="greg-day">{{ cell.day }}</span>
              <span class="heb-day">{{ cell.hebDay }}</span>
            </div>
            <div class="cell-items">
              <Link
                v-for="ev in cell.events"
                :key="'e' + ev.id"
                :href="ev.url"
                class="cell-event"
                :title="ev.title"
              >{{ ev.title }}</Link>
              <button
                v-for="b in cell.birthdays"
                :key="'b' + b.id"
                type="button"
                class="cell-birthday"
                :title="'יום הולדת: ' + b.name"
                @click="openBirthday(b, cell.date)"
              >🎂 {{ b.name }}</button>
              <button
                v-for="a in cell.anniversaries"
                :key="'a' + a.id"
                type="button"
                class="cell-anniversary"
                :title="'יום נישואין: ' + a.names"
                @click="openAnniversary(a, cell.date)"
              >💍 {{ a.names }}</button>
            </div>
          </div>
        </div>
      </div>

      <!-- אירועים קרובים -->
      <div class="upcoming">
        <h2>אירועים קרובים</h2>
        <p v-if="!upcoming.length" class="empty">אין אירועים מתוכננים. <Link href="/events/create">להוספת אירוע ›</Link></p>
        <Link v-for="ev in upcoming" :key="ev.id" :href="ev.url" class="up-row">
          <img v-if="ev.invitation_image" :src="ev.invitation_image" class="up-thumb" alt="" />
          <div v-else class="up-thumb up-thumb-ph">🎉</div>
          <div class="up-info">
            <div class="up-title">{{ ev.title }}</div>
            <div class="up-date">
              <span v-if="ev.hebrew_date">{{ ev.hebrew_date }}</span>
              <span v-if="ev.event_date" class="up-greg">{{ formatGreg(ev.event_date) }}<span v-if="ev.event_time"> · {{ ev.event_time }}</span></span>
            </div>
          </div>
        </Link>
      </div>

      <!-- תפריט צד — דמות / זוג -->
      <Transition name="panel-slide">
        <div v-if="panel" class="side-panel" dir="rtl">
          <button class="panel-close" @click="panel = null">×</button>

          <!-- יום הולדת -->
          <template v-if="panel.kind === 'birthday'">
            <div class="panel-avatar">
              <img v-if="panel.person.photo" :src="panel.person.photo" :alt="panel.person.name" />
              <div v-else class="panel-initials">{{ initials(panel.person.name) }}</div>
            </div>
            <h2 class="panel-name">{{ panel.person.name }}</h2>
            <div class="panel-occasion">🎂 יום הולדת</div>
            <div class="panel-date">
              <strong>{{ panel.hebrew }}</strong>
              <span class="panel-greg">{{ panel.greg }}</span>
            </div>
            <div v-if="panel.age" class="panel-age">{{ panel.age }}</div>

            <div class="panel-info">
              <div v-if="panel.person.maiden_name" class="info-row"><span class="info-ic">👰</span>{{ panel.person.maiden_name }}</div>
              <div v-if="panel.person.spouse" class="info-row"><span class="info-ic">💍</span>{{ panel.person.spouse }}</div>
              <div v-if="panel.person.occupation" class="info-row"><span class="info-ic">💼</span>{{ panel.person.occupation }}</div>
              <div v-if="panel.person.city" class="info-row"><span class="info-ic">📍</span>{{ panel.person.city }}</div>
              <div v-if="panel.person.bio" class="info-row info-bio"><span class="info-ic">📝</span>{{ panel.person.bio }}</div>
              <a v-if="panel.person.email" :href="`mailto:${panel.person.email}`" class="info-row info-link" dir="ltr"><span class="info-ic">✉️</span>{{ panel.person.email }}</a>
              <a v-if="panel.person.phone" :href="`tel:${panel.person.phone}`" class="info-row info-link" dir="ltr"><span class="info-ic">📞</span>{{ panel.person.phone }}</a>
            </div>

            <Link :href="`/people/${panel.person.id}`" class="panel-btn">כרטיס מלא ›</Link>
          </template>

          <!-- יום נישואין -->
          <template v-else>
            <div class="panel-occasion big">💍 יום נישואין</div>
            <h2 class="panel-name">{{ panel.couple.names }}</h2>
            <div class="panel-date">
              <strong>{{ panel.hebrew }}</strong>
              <span class="panel-greg">{{ panel.greg }}</span>
            </div>
            <div v-if="panel.years" class="panel-age">{{ panel.years }}</div>
            <div class="panel-links">
              <Link :href="`/people/${panel.couple.person1.id}`" class="panel-btn">{{ panel.couple.person1.name }} ›</Link>
              <Link :href="`/people/${panel.couple.person2.id}`" class="panel-btn">{{ panel.couple.person2.name }} ›</Link>
            </div>
          </template>
        </div>
      </Transition>
      <div v-if="panel" class="panel-overlay" @click="panel = null"></div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { gregorianToHebrewParts, gregorianToHebrew } from '@/utils/hebrewDate'

const props = defineProps({
  events: { type: Array, default: () => [] },
  birthdays: { type: Array, default: () => [] },
  anniversaries: { type: Array, default: () => [] },
})

const panel = ref(null)

const weekdays = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש']
const GREG_MONTHS = ['ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני', 'יולי', 'אוגוסט', 'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר']

const today = new Date()
const todayStr = iso(today)

const view = ref({ year: today.getFullYear(), month: today.getMonth() })

function pad2(n) { return String(n).padStart(2, '0') }
function iso(d) { return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}` }
function formatGreg(s) { const [y, m, d] = s.split('-'); return `${d}.${m}.${y}` }

// אירועים לפי תאריך
const eventsByDate = computed(() => {
  const map = {}
  for (const ev of props.events) {
    if (!ev.event_date) continue
    ;(map[ev.event_date] ||= []).push(ev)
  }
  return map
})

// ימי הולדת לפי התאריך העברי (יום+חודש עברי) — חוזר כל שנה עברית
const birthdaysByHebKey = computed(() => {
  const map = {}
  for (const b of props.birthdays) {
    if (!b.birth_date) continue
    const p = gregorianToHebrewParts(b.birth_date)
    if (!p) continue
    const key = `${p.day}-${p.monthEn}` // יום עברי + חודש עברי
    ;(map[key] ||= []).push(b)
  }
  return map
})

// ימי נישואין לפי התאריך העברי (יום+חודש עברי)
const anniversariesByHebKey = computed(() => {
  const map = {}
  for (const a of props.anniversaries) {
    if (!a.marriage_date) continue
    const p = gregorianToHebrewParts(a.marriage_date)
    if (!p) continue
    const key = `${p.day}-${p.monthEn}`
    ;(map[key] ||= []).push(a)
  }
  return map
})

const grid = computed(() => {
  const { year, month } = view.value
  const first = new Date(year, month, 1)
  const startWeekday = first.getDay() // 0=ראשון
  const cells = []
  const start = new Date(year, month, 1 - startWeekday)

  for (let i = 0; i < 42; i++) {
    const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i)
    const dateStr = iso(d)
    const parts = gregorianToHebrewParts(dateStr)
    const hebKey = parts ? `${parts.day}-${parts.monthEn}` : null
    cells.push({
      date: dateStr,
      day: d.getDate(),
      inMonth: d.getMonth() === month,
      isToday: dateStr === todayStr,
      hebDay: parts ? parts.dayHe : '',
      events: eventsByDate.value[dateStr] || [],
      birthdays: (hebKey && birthdaysByHebKey.value[hebKey]) || [],
      anniversaries: (hebKey && anniversariesByHebKey.value[hebKey]) || [],
    })
  }
  return cells
})

const gregTitle = computed(() => `${GREG_MONTHS[view.value.month]} ${view.value.year}`)

const hebrewTitle = computed(() => {
  const { year, month } = view.value
  const a = gregorianToHebrewParts(iso(new Date(year, month, 1)))
  const b = gregorianToHebrewParts(iso(new Date(year, month + 1, 0)))
  if (!a) return ''
  if (b && b.monthHe !== a.monthHe) return `${a.monthHe}–${b.monthHe} ${b.yearHe}`
  return `${a.monthHe} ${a.yearHe}`
})

function prevMonth() {
  const m = view.value.month - 1
  view.value = m < 0 ? { year: view.value.year - 1, month: 11 } : { year: view.value.year, month: m }
}
function nextMonth() {
  const m = view.value.month + 1
  view.value = m > 11 ? { year: view.value.year + 1, month: 0 } : { year: view.value.year, month: m }
}
function goToday() {
  view.value = { year: today.getFullYear(), month: today.getMonth() }
}

// ─── תפריט צד ──────────────────────────────────────────────────
function initials(name) {
  return (name || '').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('')
}
function isFemale(g) {
  return ['female', 'f', 'F', 'נקבה'].includes(g)
}

function openBirthday(b, dateStr) {
  const bp = gregorianToHebrewParts(b.birth_date)
  const cp = gregorianToHebrewParts(dateStr)
  const age = (bp && cp) ? cp.year - bp.year : null
  panel.value = {
    kind: 'birthday',
    person: b,
    hebrew: gregorianToHebrew(dateStr),
    greg: formatGreg(dateStr),
    age: age && age > 0 ? `${isFemale(b.gender) ? 'חוגגת' : 'חוגג'} ${age}` : null,
  }
}

function openAnniversary(a, dateStr) {
  const mp = gregorianToHebrewParts(a.marriage_date)
  const cp = gregorianToHebrewParts(dateStr)
  const years = (mp && cp) ? cp.year - mp.year : null
  panel.value = {
    kind: 'anniversary',
    couple: a,
    hebrew: gregorianToHebrew(dateStr),
    greg: formatGreg(dateStr),
    years: years && years > 0 ? `${years} שנות נישואין` : null,
  }
}

const upcoming = computed(() =>
  [...props.events]
    .filter(e => e.event_date && e.event_date >= todayStr)
    .sort((a, b) => a.event_date.localeCompare(b.event_date))
    .slice(0, 10)
)
</script>

<style scoped>
.events-page {
  max-width: 980px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
  font-family: 'Rubik', sans-serif;
}

.page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
.page-head h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }
.btn-new {
  background: #2d6be4; color: white; text-decoration: none;
  padding: 0.5rem 1.2rem; border-radius: 10px; font-weight: 600; font-size: 0.92rem;
}
.btn-new:hover { background: #1a55c8; }

/* לוח שנה */
.calendar {
  background: #fffdf8;
  border: 1px solid #efe6d4;
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(120,90,30,0.07);
  padding: 1.25rem;
  margin-bottom: 2rem;
}

.cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.cal-nav { display: flex; align-items: center; gap: 0.5rem; }
.nav-btn {
  width: 36px; height: 36px; border-radius: 10px;
  border: 1px solid #e0eaf8; background: white; color: #2d6be4;
  font-size: 1.3rem; cursor: pointer; line-height: 1;
}
.nav-btn:hover { background: #edf3ff; }
.today-btn {
  border: 1px solid #cfe0ff; background: #eaf2ff; color: #2d6be4;
  padding: 0 0.9rem; height: 36px; border-radius: 10px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.9rem; font-weight: 600;
}
.today-btn:hover { background: #dbe9ff; }

.cal-title { text-align: left; }
.heb-title { display: block; font-size: 1.35rem; font-weight: 700; color: #6b4f2a; }
.greg-title { display: block; font-size: 0.9rem; color: #a99873; }

.weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 4px; }
.weekday { text-align: center; font-size: 0.85rem; font-weight: 600; color: #a99873; padding: 0.35rem 0; }

.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-cell {
  min-height: 86px;
  background: #fbeed9;
  border-radius: 10px;
  padding: 0.35rem 0.4rem;
  display: flex; flex-direction: column;
  overflow: hidden;
}
.cal-cell.out-month { background: #f6f1e7; opacity: 0.55; }
.cal-cell.today { outline: 2px solid #2d6be4; background: #eaf2ff; }

.cell-dates { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.2rem; }
.greg-day { font-size: 0.95rem; font-weight: 600; color: #5a4a2e; }
.heb-day { font-size: 0.8rem; color: #b08d4d; }

.cell-items { display: flex; flex-direction: column; gap: 2px; overflow: hidden; }
.cell-event {
  background: #2d6be4; color: white; text-decoration: none;
  font-size: 0.72rem; padding: 0.12rem 0.35rem; border-radius: 6px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cell-event:hover { background: #1a55c8; }
.cell-birthday, .cell-anniversary {
  display: block; width: 100%; text-align: right;
  border: none; cursor: pointer; font-family: 'Rubik', sans-serif;
  font-size: 0.7rem; padding: 0.12rem 0.35rem; border-radius: 6px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cell-birthday { background: #ffe7ef; color: #b03a68; }
.cell-birthday:hover { background: #ffd3e3; }
.cell-anniversary { background: #fff2cc; color: #9a6a16; }
.cell-anniversary:hover { background: #ffe9a8; }

/* תפריט צד */
.panel-overlay {
  position: fixed; inset: 0; background: rgba(20,40,80,0.25); z-index: 200;
}
.side-panel {
  position: fixed; top: 0; right: 0; bottom: 0; width: 320px; max-width: 88vw;
  background: white; z-index: 201;
  box-shadow: -4px 0 24px rgba(0,40,120,0.15);
  padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center;
  font-family: 'Rubik', sans-serif; text-align: center;
}
.panel-close {
  position: absolute; top: 0.75rem; left: 0.9rem;
  background: none; border: none; font-size: 1.6rem; color: #9aa7bd;
  cursor: pointer; line-height: 1;
}
.panel-close:hover { color: #2d4a7a; }
.panel-avatar {
  width: 96px; height: 96px; border-radius: 50%; overflow: hidden;
  background: #eaf2ff; display: flex; align-items: center; justify-content: center;
  margin-bottom: 1rem;
}
.panel-avatar img { width: 100%; height: 100%; object-fit: cover; }
.panel-initials { font-size: 2rem; font-weight: 700; color: #2d6be4; }
.panel-name { font-size: 1.3rem; color: #1a3a6b; margin: 0 0 0.5rem; }
.panel-occasion { font-size: 0.95rem; color: #b03a68; font-weight: 600; margin-bottom: 0.75rem; }
.panel-occasion.big { font-size: 1.1rem; color: #9a6a16; margin-bottom: 1rem; }
.panel-date { margin-bottom: 0.5rem; }
.panel-date strong { display: block; color: #6b4f2a; font-size: 1.05rem; }
.panel-greg { display: block; color: #a99873; font-size: 0.85rem; margin-top: 0.2rem; }
.panel-age { color: #2d6be4; font-weight: 600; margin: 0.5rem 0 1rem; }

.panel-info {
  width: 100%; text-align: right;
  border-top: 1px solid #eef2f8; padding-top: 0.85rem; margin-bottom: 0.5rem;
  display: flex; flex-direction: column; gap: 0.5rem;
}
.info-row {
  display: flex; align-items: flex-start; gap: 0.5rem;
  font-size: 0.9rem; color: #3a4a63; text-decoration: none;
}
.info-ic { flex-shrink: 0; }
.info-bio { color: #5a6a85; line-height: 1.5; }
.info-link { color: #2d6be4; }
.info-link:hover { text-decoration: underline; }
.panel-btn {
  display: inline-block; background: #2d6be4; color: white; text-decoration: none;
  padding: 0.5rem 1.3rem; border-radius: 10px; font-weight: 600; font-size: 0.92rem;
  margin-top: 0.5rem;
}
.panel-btn:hover { background: #1a55c8; }
.panel-links { display: flex; flex-direction: column; gap: 0.5rem; width: 100%; margin-top: 0.75rem; }
.panel-links .panel-btn { width: 100%; }

.panel-slide-enter-active, .panel-slide-leave-active { transition: transform 0.25s ease; }
.panel-slide-enter-from, .panel-slide-leave-to { transform: translateX(100%); }

/* אירועים קרובים */
.upcoming h2 { font-size: 1.2rem; color: #1a3a6b; margin: 0 0 1rem; }
.empty { color: #8a9ab5; }
.empty a { color: #2d6be4; }

.up-row {
  display: flex; align-items: center; gap: 1rem;
  background: white; border-radius: 14px; padding: 0.75rem 1rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.06);
  text-decoration: none; margin-bottom: 0.75rem;
  transition: transform 0.15s;
}
.up-row:hover { transform: translateY(-1px); }
.up-thumb { width: 54px; height: 54px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
.up-thumb-ph { display: flex; align-items: center; justify-content: center; background: #fdf1e0; font-size: 1.4rem; }
.up-info { flex: 1; }
.up-title { font-weight: 600; color: #1a3a6b; font-size: 1rem; }
.up-date { font-size: 0.85rem; color: #6b7a99; margin-top: 0.2rem; }
.up-greg { color: #a3b0c7; margin-right: 0.5rem; }

@media (max-width: 640px) {
  .cal-cell { min-height: 64px; }
  .greg-day { font-size: 0.82rem; }
  .heb-day { font-size: 0.68rem; }
  .cell-event, .cell-birthday { font-size: 0.62rem; }
}
</style>
