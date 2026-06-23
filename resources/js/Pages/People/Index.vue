<template>
  <AppLayout title="בני המשפחה">
    <div class="people-page" dir="rtl">
      <div class="page-header">
        <div>
          <h1>בני משפחת ואקיל</h1>
          <p class="count-label">{{ people.length }} דמויות בעץ</p>
        </div>
        <Link href="/people/create" class="btn-primary">+ הוסף דמות</Link>
      </div>

      <!-- חיפוש -->
      <div class="search-bar">
        <input v-model="search" type="text" placeholder="חיפוש לפי שם..." />
      </div>

      <!-- כרטיסיות -->
      <div v-if="filtered.length === 0" class="empty-state">
        <div v-if="people.length === 0">
          <p>העץ ריק עדיין</p>
          <Link href="/onboarding" class="btn-primary">התחל עכשיו</Link>
        </div>
        <div v-else>
          <p>לא נמצאו תוצאות עבור "{{ search }}"</p>
        </div>
      </div>

      <div class="people-grid" v-else>
        <Link
          v-for="person in filtered"
          :key="person.id"
          :href="`/people/${person.id}`"
          class="person-card"
          :class="[person.gender, { deceased: person.is_deceased }]"
        >
          <div class="person-avatar">
            <img v-if="person.photo_url" :src="person.photo_url" :alt="person.full_name" />
            <div v-else class="initials">{{ initials(person.full_name) }}</div>
          </div>
          <div class="person-info">
            <div class="person-name">{{ person.full_name }}</div>
            <div class="person-meta">
              <span v-if="person.birth_year">נולד/ה {{ person.birth_year }}</span>
              <span v-if="person.is_deceased" class="deceased-badge">ז"ל</span>
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

const props = defineProps({
  people: { type: Array, default: () => [] },
})

const search = ref('')

const filtered = computed(() => {
  if (!search.value.trim()) return props.people
  const q = search.value.trim().toLowerCase()
  return props.people.filter(p => p.full_name.toLowerCase().includes(q))
})

function initials(name) {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2)
}
</script>

<style scoped>
.people-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

h1 {
  font-size: 1.7rem;
  color: #1a3a6b;
  margin: 0 0 0.25rem;
}

.count-label {
  color: #8a9ab5;
  font-size: 0.9rem;
  margin: 0;
}

.btn-primary {
  background: #2d6be4;
  color: white;
  padding: 0.6rem 1.4rem;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  transition: background 0.2s;
}

.btn-primary:hover { background: #1a55c8; }

.search-bar {
  margin-bottom: 1.5rem;
}

.search-bar input {
  width: 100%;
  padding: 0.65rem 1rem;
  border: 1.5px solid #d1dce8;
  border-radius: 10px;
  font-size: 1rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  max-width: 380px;
  box-sizing: border-box;
}

.search-bar input:focus {
  outline: none;
  border-color: #2d6be4;
}

.people-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.person-card {
  background: white;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0,50,150,0.07);
  padding: 1.25rem 1rem;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  transition: transform 0.2s, box-shadow 0.2s;
  border-top: 4px solid transparent;
}

.person-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0,50,150,0.13);
}

.person-card.male { border-top-color: #2d6be4; }
.person-card.female { border-top-color: #8b5cf6; }
.person-card.deceased { opacity: 0.75; filter: grayscale(30%); }

.person-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  overflow: hidden;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
}

.person-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.initials {
  font-size: 1.4rem;
  font-weight: 700;
  color: #2d6be4;
}

.person-info { text-align: center; }

.person-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1a3a6b;
}

.person-meta {
  font-size: 0.8rem;
  color: #8a9ab5;
  margin-top: 0.2rem;
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
}

.deceased-badge {
  background: #e8e8e8;
  border-radius: 4px;
  padding: 0 0.3rem;
  font-size: 0.75rem;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #8a9ab5;
}
</style>
