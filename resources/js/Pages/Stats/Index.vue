<template>
  <AppLayout title="סטטיסטיקות">
    <div class="stats-page" dir="rtl">
      <div class="page-header">
        <h1>📊 המשפחה במספרים</h1>
        <p class="subtitle">מבט מהיר על משפחת ואקיל · חודש {{ hebMonth.monthHe }} {{ hebMonth.yearHe }}</p>
      </div>

      <!-- כרטיסי מספרים -->
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-num">{{ stats.total }}</div>
          <div class="stat-label">בני משפחה בעץ</div>
        </div>
        <div class="stat-card living">
          <div class="stat-num">{{ stats.living }}</div>
          <div class="stat-label">בחיים</div>
        </div>
        <div class="stat-card">
          <div class="stat-num">{{ cities.length }}</div>
          <div class="stat-label">ערים</div>
        </div>
        <div class="stat-card">
          <div class="stat-num">{{ stats.photos }}</div>
          <div class="stat-label">תמונות</div>
        </div>
      </div>

      <div class="stats-grid">
        <!-- תינוקות מהשנה האחרונה -->
        <section class="panel" v-if="babies.length">
          <h2>👶 נולדו השנה</h2>
          <ul class="baby-list">
            <li v-for="b in babies" :key="b.id">
              <Link :href="`/people/${b.id}`" class="baby-link">
                <span class="baby-avatar">
                  <img v-if="b.photo_url" :src="b.photo_url" :alt="b.name" />
                  <span v-else>{{ b.gender === 'female' ? '👧' : '👦' }}</span>
                </span>
                <span class="baby-text">
                  <strong>{{ b.name }}</strong>
                  <span class="baby-chain" v-if="b.chain.length">
                    {{ b.gender === 'female' ? 'בתה' : 'בנה' }} של {{ b.chain.join(', של ') }}
                  </span>
                  <span class="baby-date">
                    <b>{{ b.hebFull }}</b>
                    <span class="greg"> · {{ b.greg }}</span>
                  </span>
                </span>
              </Link>
            </li>
          </ul>
        </section>

        <!-- ימי הולדת בחודש העברי הנוכחי -->
        <section class="panel">
          <h2>🎂 ימי הולדת בחודש {{ hebMonth.monthHe }}</h2>
          <ul class="event-list" v-if="birthdays.length">
            <li v-for="b in birthdays" :key="b.id">
              <Link :href="`/people/${b.id}`" class="event-link">
                <span class="event-date">
                  <b>{{ b.hebDM }}</b>
                  <span class="greg">{{ b.greg }}</span>
                </span>
                <span class="event-name">{{ b.full_name }}</span>
                <span class="event-extra">{{ b.turning }} 🎉</span>
              </Link>
            </li>
          </ul>
          <p v-else class="empty">אין ימי הולדת החודש</p>
        </section>

        <!-- ימי נישואין החודש -->
        <section class="panel">
          <h2>💍 ימי נישואין בחודש {{ hebMonth.monthHe }}</h2>
          <ul class="event-list" v-if="anniversaries.length">
            <li v-for="(a, i) in anniversaries" :key="i">
              <span class="event-date">
                <b>{{ a.hebDM }}</b>
                <span class="greg">{{ a.greg }}</span>
              </span>
              <span class="event-name">{{ a.couple }}</span>
              <span class="event-extra">{{ a.years }} שנה</span>
            </li>
          </ul>
          <p v-else class="empty">אין ימי נישואין החודש (או שלא הוזנו תאריכי חתונה)</p>
        </section>

        <!-- פיזור גאוגרפי — מפה -->
        <section class="panel map-panel" v-if="cities.length">
          <h2>🗺️ פיזור גאוגרפי</h2>

          <div v-if="placing" class="placing-banner">
            👆 לחצו על המפה כדי למקם את <b>{{ placing }}</b>
            <button class="cancel-place" @click="placing = null">ביטול</button>
          </div>
          <div v-else-if="saveMsg" class="save-msg">{{ saveMsg }}</div>

          <div ref="mapEl" class="map"></div>

          <div v-if="unplaced.length" class="unplaced">
            <span class="unplaced-title">ללא מיקום על המפה — לחצו "מקם" כדי לסמן (יישמר לכולם):</span>
            <div class="unplaced-chips">
              <button v-for="u in unplaced" :key="u.city" class="place-chip" @click="startPlacing(u.city)">
                📍 {{ u.city }} ({{ u.count }})
              </button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  gregorianToHebrewParts,
  hebrewDayMonth,
  currentHebrewMonth,
} from '@/utils/hebrewDate'
import { geocodeCity, ISRAEL_CENTER } from '@/utils/israelGeo'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  stats:                 { type: Object, default: () => ({}) },
  cities:                { type: Array, default: () => [] },
  savedLocations:        { type: Object, default: () => ({}) },
  babies:                { type: Array, default: () => [] },
  birthdayCandidates:    { type: Array, default: () => [] },
  anniversaryCandidates: { type: Array, default: () => [] },
})

const hebMonth = currentHebrewMonth()

// ── תינוקות עם תאריך עברי מלא ──
const babies = computed(() =>
  props.babies.map(b => {
    const p = gregorianToHebrewParts(b.iso)
    return { ...b, hebFull: p ? `${p.dayHe} ב${p.monthHe} ${p.yearHe}` : b.greg }
  })
)

// ── ימי הולדת בחודש העברי הנוכחי ──
const birthdays = computed(() =>
  props.birthdayCandidates
    .map(b => {
      const p = gregorianToHebrewParts(b.iso)
      return p ? { ...b, parts: p, hebDM: `${p.dayHe} ${p.monthHe}`, turning: hebMonth.year - p.year } : null
    })
    .filter(b => b && b.parts.monthHe === hebMonth.monthHe)
    .sort((a, b) => a.parts.day - b.parts.day)
)

// ── ימי נישואין בחודש העברי הנוכחי ──
const anniversaries = computed(() =>
  props.anniversaryCandidates
    .map(a => {
      const p = gregorianToHebrewParts(a.iso)
      return p ? { ...a, parts: p, hebDM: `${p.dayHe} ${p.monthHe}`, years: hebMonth.year - p.year } : null
    })
    .filter(a => a && a.parts.monthHe === hebMonth.monthHe)
    .sort((a, b) => a.parts.day - b.parts.day)
)

// ── מפה: פתרון מיקום לעיר — קודם מיקומים ידניים שמורים, אחר כך הטבלה המובנית ──
const overrides = ref({ ...props.savedLocations })

function resolve(cityText) {
  const o = overrides.value[cityText]
  if (o) return { lat: Number(o.lat), lng: Number(o.lng), name: cityText }
  return geocodeCity(cityText)
}

const placed = computed(() => {
  const byPlace = {}
  for (const c of props.cities) {
    const g = resolve(c.city)
    if (!g) continue
    if (!byPlace[g.name]) byPlace[g.name] = { name: g.name, lat: g.lat, lng: g.lng, count: 0, people: [] }
    byPlace[g.name].count += c.count
    byPlace[g.name].people.push(...(c.people || []))
  }
  return Object.values(byPlace)
})

const unplaced = computed(() => props.cities.filter(c => !resolve(c.city)))

const mapEl = ref(null)
const placing = ref(null)      // מחרוזת העיר שממקמים כרגע
const saveMsg = ref('')
let map = null
let L = null
const markers = {}             // name → marker (כדי לעדכן/למנוע כפילות)

function markerHtml(p) {
  const names = p.people.join('، ')
  return `<div dir="rtl" style="font-family:Rubik,sans-serif"><b>${p.name}</b> · ${p.count}<br>${names}</div>`
}

function addMarker(p, maxCount) {
  if (!L || !map) return
  const radius = 8 + Math.round((p.count / Math.max(maxCount, 1)) * 18)
  const m = L.circleMarker([p.lat, p.lng], {
    radius, color: '#2d6be4', fillColor: '#4d8bf5', fillOpacity: 0.55, weight: 2,
  })
    .bindTooltip(markerHtml(p), { direction: 'top', opacity: 0.97 })
    .bindPopup(markerHtml(p))
    .addTo(map)
  markers[p.name] = m
}

function startPlacing(cityText) {
  placing.value = cityText
  saveMsg.value = ''
  if (mapEl.value) mapEl.value.classList.add('placing')
}

async function onMapClick(e) {
  if (!placing.value) return
  const cityText = placing.value
  const { lat, lng } = e.latlng
  try {
    await window.axios.post('/stats/location', { name: cityText, lat, lng })
    overrides.value[cityText] = { lat, lng }
    // הוסף סמן מיידית (מאוחד עם count של אותה עיר)
    const c = props.cities.find(x => x.city === cityText)
    addMarker({ name: cityText, lat, lng, count: c?.count || 1, people: c?.people || [] },
              Math.max(...placed.value.map(p => p.count), 1))
    saveMsg.value = `📍 "${cityText}" מוקם ונשמר לכולם`
  } catch {
    saveMsg.value = 'שמירת המיקום נכשלה, נסו שוב'
  } finally {
    placing.value = null
    if (mapEl.value) mapEl.value.classList.remove('placing')
  }
}

onMounted(async () => {
  if (!mapEl.value) return
  L = (await import('leaflet')).default

  map = L.map(mapEl.value, { scrollWheelZoom: false, attributionControl: true }).setView(ISRAEL_CENTER, 8)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenStreetMap',
  }).addTo(map)

  map.on('click', onMapClick)

  const maxCount = Math.max(...placed.value.map(p => p.count), 1)
  const bounds = []
  for (const p of placed.value) {
    addMarker(p, maxCount)
    bounds.push([p.lat, p.lng])
  }
  if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] })
})

onBeforeUnmount(() => { if (map) { map.remove(); map = null } })
</script>

<style scoped>
.stats-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.6rem; color: #1a3a6b; margin: 0; }
.subtitle { color: #6b7a99; margin: 0.25rem 0 0; }

.stat-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}
.stat-card {
  background: white; border-radius: 14px; padding: 1.25rem 1rem; text-align: center;
  box-shadow: 0 2px 10px rgba(0,50,150,0.06); border: 1px solid #e6eefb;
}
.stat-card.living { background: linear-gradient(135deg, #e8f5ff, #f4f8ff); }
.stat-num { font-size: 2rem; font-weight: 700; color: #2d6be4; }
.stat-label { font-size: 0.85rem; color: #6b7a99; margin-top: 0.25rem; }

.stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; }
.map-panel { grid-column: 1 / -1; }

.panel {
  background: white; border-radius: 14px; padding: 1.25rem 1.5rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.05); border: 1px solid #e6eefb;
}
.panel h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0 0 1rem; }

ul { list-style: none; margin: 0; padding: 0; }

/* תינוקות */
.baby-list li { margin-bottom: 0.65rem; }
.baby-link { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit; }
.baby-avatar {
  width: 44px; height: 44px; border-radius: 50%; background: #edf3ff;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; overflow: hidden; flex-shrink: 0;
}
.baby-avatar img { width: 100%; height: 100%; object-fit: cover; }
.baby-text { display: flex; flex-direction: column; line-height: 1.45; }
.baby-text strong { color: #1a3a6b; }
.baby-chain { font-size: 0.85rem; color: #6b7a99; }
.baby-date { font-size: 0.82rem; color: #2d6be4; }
.baby-date .greg { color: #9aa7c0; font-size: 0.75rem; }

/* רשימות אירועים */
.event-list li, .city-list li {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.55rem 0; border-bottom: 1px solid #f0f4fb;
}
.event-list li:last-child { border-bottom: none; }
.event-link { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit; flex: 1; }
.event-date {
  background: #edf3ff; border-radius: 8px; padding: 0.25rem 0.55rem;
  min-width: 76px; text-align: center; line-height: 1.25;
  display: flex; flex-direction: column;
}
.event-date b { color: #2d6be4; font-size: 0.82rem; }
.event-date .greg { color: #9aa7c0; font-size: 0.68rem; }
.event-name { flex: 1; color: #2d4a7a; }
.event-extra { font-size: 0.85rem; color: #6b7a99; white-space: nowrap; }

.empty { color: #9aa7c0; font-size: 0.9rem; }

/* מפה */
.map { height: 380px; border-radius: 12px; overflow: hidden; border: 1px solid #e6eefb; }
.map.placing { cursor: crosshair; outline: 3px solid #f0b65a; }
.map.placing :deep(.leaflet-container) { cursor: crosshair; }
.map-note { font-size: 0.8rem; color: #9aa7c0; margin: 0.6rem 0 0; }

.placing-banner {
  background: #fff4e6; border: 1px solid #f0c896; color: #b06a1a;
  border-radius: 9px; padding: 0.55rem 0.9rem; margin-bottom: 0.6rem; font-size: 0.9rem;
  display: flex; align-items: center; gap: 0.6rem;
}
.cancel-place {
  margin-right: auto; background: none; border: none; color: #c0392b;
  cursor: pointer; font-family: inherit; font-size: 0.85rem;
}
.save-msg {
  background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46;
  border-radius: 9px; padding: 0.5rem 0.9rem; margin-bottom: 0.6rem; font-size: 0.88rem;
}

.unplaced { margin-top: 0.85rem; }
.unplaced-title { font-size: 0.8rem; color: #9aa7c0; }
.unplaced-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
.place-chip {
  background: #fff4e6; border: 1px dashed #f0c896; color: #b06a1a;
  border-radius: 20px; padding: 0.25rem 0.7rem; font-size: 0.82rem;
  cursor: pointer; font-family: 'Rubik', sans-serif;
}
.place-chip:hover { background: #ffe9cc; }

@media (max-width: 720px) {
  .stat-cards { grid-template-columns: repeat(2, 1fr); }
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
