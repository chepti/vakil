<template>
  <AppLayout :title="person.full_name">
    <div class="show-page" dir="rtl">

      <!-- כרטיס ראשי -->
      <div class="person-hero" :class="[person.gender, { deceased: person.is_deceased }]">
        <div class="hero-content">
          <div class="avatar-wrap">
            <img v-if="person.photo_url" :src="person.photo_url" :alt="person.full_name" />
            <div v-else class="initials-large">{{ initials(person.full_name) }}</div>
          </div>

          <div class="hero-info">
            <div class="name-row">
              <h1>{{ person.full_name }}</h1>
              <span v-if="person.is_deceased" class="deceased-badge">ז"ל</span>
            </div>

            <div class="meta-chips">
              <span v-if="person.birth_date_gregorian" class="chip">
                🎂 {{ formatDate(person.birth_date_gregorian) }}
                <span v-if="person.birth_date_hebrew" class="hebrew-date"> / {{ person.birth_date_hebrew }}</span>
              </span>
              <span v-if="person.is_deceased && person.death_date_gregorian" class="chip chip-gray">
                {{ formatDate(person.death_date_gregorian) }}
                <span v-if="person.death_date_hebrew"> / {{ person.death_date_hebrew }}</span>
              </span>
              <span v-if="person.city" class="chip">📍 {{ person.city }}</span>
              <span v-if="person.current_occupation" class="chip">💼 {{ person.current_occupation }}</span>
            </div>

            <p v-if="person.bio" class="bio-text">{{ person.bio }}</p>
          </div>

          <!-- כפתורי פעולות -->
          <div class="hero-actions">
            <Link :href="`/people/${person.id}/edit`" class="btn-edit">עריכה</Link>
            <button v-if="$page.props.auth.user.role === 'admin'" class="btn-delete" @click="confirmDelete">מחיקה</button>
          </div>
        </div>
      </div>

      <!-- משפחה -->
      <div class="family-grid">

        <!-- הורים -->
        <div class="family-section" v-if="parents.length">
          <h2>הורים</h2>
          <div class="family-cards">
            <Link v-for="p in parents" :key="p.id" :href="`/people/${p.id}`" class="mini-card">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
          </div>
        </div>

        <!-- בני/בנות זוג -->
        <div class="family-section" v-if="spouses.length">
          <h2>{{ spouses.length === 1 ? 'בן/בת זוג' : 'בני/בנות זוג' }}</h2>
          <div class="family-cards">
            <Link v-for="p in spouses" :key="p.id" :href="`/people/${p.id}`" class="mini-card">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
          </div>
        </div>

        <!-- ילדים -->
        <div class="family-section" v-if="children.length">
          <h2>ילדים ({{ children.length }})</h2>
          <div class="family-cards">
            <Link v-for="p in children" :key="p.id" :href="`/people/${p.id}`" class="mini-card" :class="p.gender">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- כפתורי פעולות תחתונים -->
      <div class="bottom-actions" dir="rtl">
        <Link :href="`/people/${person.id}/edit`" class="btn-action btn-secondary-action">✏️ ערוך פרטים</Link>
        <button class="btn-action btn-primary-action" @click="showMazalTov = true">🎉 מזל טוב</button>
        <button class="btn-action btn-ghost-action" @click="showMessage = true">💬 השאר הודעה</button>
      </div>

    </div>

    <!-- Confirm Delete Modal -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal" dir="rtl">
        <h3>מחיקת דמות</h3>
        <p>האם אתה בטוח שברצונך למחוק את <strong>{{ person.full_name }}</strong>?</p>
        <p class="modal-warning">פעולה זו תמחק גם את כל הקשרים של הדמות ואינה ניתנת לביטול.</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showDeleteConfirm = false">ביטול</button>
          <button class="btn-delete-confirm" @click="deletePerson" :disabled="deleting">
            {{ deleting ? 'מוחק...' : 'כן, מחק' }}
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  person:   { type: Object, required: true },
  parents:  { type: Array,  default: () => [] },
  children: { type: Array,  default: () => [] },
  spouses:  { type: Array,  default: () => [] },
})

const showDeleteConfirm = ref(false)
const showMazalTov = ref(false)
const showMessage = ref(false)
const deleting = ref(false)

function initials(name) {
  return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2)
}

function formatDate(d) {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

function confirmDelete() {
  showDeleteConfirm.value = true
}

function deletePerson() {
  deleting.value = true
  router.delete(`/people/${props.person.id}`, {
    onFinish: () => { deleting.value = false },
  })
}
</script>

<style scoped>
.show-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.person-hero {
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  background: white;
  box-shadow: 0 4px 20px rgba(0,50,150,0.08);
  border-right: 6px solid #2d6be4;
}

.person-hero.female { border-right-color: #8b5cf6; }
.person-hero.deceased { border-right-color: #9ca3af; }

.hero-content {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  flex-wrap: wrap;
}

.avatar-wrap {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  overflow: hidden;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.initials-large {
  font-size: 2.2rem;
  font-weight: 700;
  color: #2d6be4;
}

.hero-info { flex: 1; min-width: 200px; }

.name-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

h1 {
  font-size: 1.8rem;
  color: #1a3a6b;
  margin: 0 0 0.75rem;
}

.deceased-badge {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #64748b;
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

.meta-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.chip {
  background: #f0f7ff;
  border: 1px solid #d1e5fb;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.85rem;
  color: #2d4a7a;
}

.chip-gray { background: #f1f5f9; border-color: #e2e8f0; color: #64748b; }

.hebrew-date { opacity: 0.8; }

.bio-text {
  color: #4a5568;
  font-size: 0.95rem;
  line-height: 1.6;
  margin: 0;
}

.hero-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-self: flex-start;
}

.btn-edit {
  background: #e8f0fe;
  color: #2d6be4;
  padding: 0.5rem 1.2rem;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  text-align: center;
}

.btn-delete {
  background: none;
  border: 1.5px solid #fca5a5;
  color: #e74c3c;
  padding: 0.45rem 1.2rem;
  border-radius: 8px;
  font-size: 0.9rem;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
}

.family-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.family-section {
  background: white;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.06);
}

h2 {
  font-size: 1rem;
  color: #2d4a7a;
  margin: 0 0 1rem;
  font-weight: 600;
}

.family-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.mini-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  text-decoration: none;
  color: #1a3a6b;
  font-size: 0.85rem;
  padding: 0.75rem;
  border-radius: 10px;
  background: #f8faff;
  border: 1px solid #e4eefb;
  transition: all 0.2s;
  min-width: 80px;
  text-align: center;
}

.mini-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,50,150,0.1); }
.mini-card.female { background: #fdf4ff; border-color: #e9d5ff; }

.mini-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mini-avatar img { width: 100%; height: 100%; object-fit: cover; }
.mini-initials { font-size: 1rem; font-weight: 700; color: #2d6be4; }

.bottom-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  flex-wrap: wrap;
  justify-content: center;
}

.btn-action {
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary-action { background: #2d6be4; color: white; border: none; }
.btn-primary-action:hover { background: #1a55c8; }
.btn-secondary-action { background: #e8f0fe; color: #2d6be4; border: none; text-decoration: none; display: inline-flex; align-items: center; }
.btn-ghost-action { background: white; border: 1.5px solid #d1dce8; color: #4a5568; }
.btn-ghost-action:hover { border-color: #2d6be4; color: #2d6be4; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.modal h3 { margin: 0 0 1rem; color: #1a3a6b; }
.modal p { color: #4a5568; margin-bottom: 0.75rem; }
.modal-warning { color: #e74c3c; font-size: 0.88rem; }

.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-cancel {
  background: white;
  border: 1.5px solid #d1dce8;
  color: #4a5568;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
}

.btn-delete-confirm {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 0.6rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-weight: 600;
}

.btn-delete-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
