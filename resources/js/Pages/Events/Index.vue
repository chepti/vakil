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
              <div
                v-for="b in cell.birthdays"
                :key="'b' + b.id"
                class="cell-birthday"
                :title="'יום הולדת: ' + b.name"
              >🎂 {{ b.name }}</div>
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
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { gregorianToHebrewParts } from '@/utils/hebrewDate'

const props = defineProps({
  events: { type: Array, default: () => [] },
  birthdays: { type: Array, default: () => [] },
})

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
.cell-birthday {
  background: #ffe7ef; color: #b03a68;
  font-size: 0.7rem; padding: 0.12rem 0.35rem; border-radius: 6px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

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
