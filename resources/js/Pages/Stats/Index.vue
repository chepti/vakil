<template>
  <AppLayout title="סטטיסטיקות">
    <div class="stats-page" dir="rtl">
      <div class="page-header">
        <h1>📊 המשפחה במספרים</h1>
        <p class="subtitle">מבט מהיר על משפחת ואקיל</p>
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
                  <span class="baby-date">{{ b.birth_date }}</span>
                </span>
              </Link>
            </li>
          </ul>
        </section>

        <!-- ימי הולדת קרובים -->
        <section class="panel">
          <h2>🎂 ימי הולדת ב-30 הימים הקרובים</h2>
          <ul class="event-list" v-if="birthdays.length">
            <li v-for="b in birthdays" :key="b.id">
              <Link :href="`/people/${b.id}`" class="event-link">
                <span class="event-date">{{ b.date }}</span>
                <span class="event-name">{{ b.full_name }}</span>
                <span class="event-extra">{{ b.turning }} 🎉</span>
              </Link>
            </li>
          </ul>
          <p v-else class="empty">אין ימי הולדת בקרוב</p>
        </section>

        <!-- ימי נישואין -->
        <section class="panel">
          <h2>💍 ימי נישואין קרובים</h2>
          <ul class="event-list" v-if="anniversaries.length">
            <li v-for="(a, i) in anniversaries" :key="i">
              <span class="event-date">{{ a.date }}</span>
              <span class="event-name">{{ a.couple }}</span>
              <span class="event-extra">{{ a.years }} שנה</span>
            </li>
          </ul>
          <p v-else class="empty">אין ימי נישואין בקרוב (או שלא הוזנו תאריכי חתונה)</p>
        </section>

        <!-- ערים -->
        <section class="panel" v-if="cities.length">
          <h2>🏙️ פיזור גאוגרפי</h2>
          <ul class="city-list">
            <li v-for="c in cities" :key="c.city">
              <span class="city-name">{{ c.city }}</span>
              <span class="city-count">{{ c.count }}</span>
            </li>
          </ul>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  stats:         { type: Object, default: () => ({}) },
  cities:        { type: Array, default: () => [] },
  babies:        { type: Array, default: () => [] },
  birthdays:     { type: Array, default: () => [] },
  anniversaries: { type: Array, default: () => [] },
})
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

/* כרטיסי מספרים */
.stat-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.stat-card {
  background: white;
  border-radius: 14px;
  padding: 1.25rem 1rem;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0,50,150,0.06);
  border: 1px solid #e6eefb;
}

.stat-card.living { background: linear-gradient(135deg, #e8f5ff, #f4f8ff); }
.stat-num { font-size: 2rem; font-weight: 700; color: #2d6be4; }
.stat-label { font-size: 0.85rem; color: #6b7a99; margin-top: 0.25rem; }

/* גריד פאנלים */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.panel {
  background: white;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.05);
  border: 1px solid #e6eefb;
}

.panel h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0 0 1rem; }

ul { list-style: none; margin: 0; padding: 0; }

/* תינוקות */
.baby-list li { margin-bottom: 0.65rem; }
.baby-link { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit; }
.baby-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: #edf3ff; display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; overflow: hidden; flex-shrink: 0;
}
.baby-avatar img { width: 100%; height: 100%; object-fit: cover; }
.baby-text { display: flex; flex-direction: column; line-height: 1.4; }
.baby-text strong { color: #1a3a6b; }
.baby-chain { font-size: 0.85rem; color: #6b7a99; }
.baby-date { font-size: 0.75rem; color: #9aa7c0; }

/* רשימות אירועים */
.event-list li, .city-list li {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.55rem 0; border-bottom: 1px solid #f0f4fb;
}
.event-list li:last-child, .city-list li:last-child { border-bottom: none; }
.event-link { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit; flex: 1; }
.event-date {
  background: #edf3ff; color: #2d6be4; font-weight: 600;
  border-radius: 8px; padding: 0.25rem 0.6rem; font-size: 0.85rem; min-width: 48px; text-align: center;
}
.event-name { flex: 1; color: #2d4a7a; }
.event-extra { font-size: 0.85rem; color: #6b7a99; }

/* ערים */
.city-name { flex: 1; color: #2d4a7a; }
.city-count {
  background: #f0f4fb; color: #6b7a99; border-radius: 20px;
  padding: 0.1rem 0.7rem; font-size: 0.85rem; font-weight: 600;
}

.empty { color: #9aa7c0; font-size: 0.9rem; }

@media (max-width: 720px) {
  .stat-cards { grid-template-columns: repeat(2, 1fr); }
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
