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

          <div class="hero-actions">
            <Link :href="`/people/${person.id}/edit`" class="btn-edit">✏️ עריכה</Link>
            <button v-if="$page.props.auth.user.role === 'admin'" class="btn-delete" @click="showDeleteConfirm = true">🗑 מחיקה</button>
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

        <!-- בן/בת זוג -->
        <div class="family-section">
          <div class="section-header">
            <h2>{{ spouses.length === 1 ? 'בן/בת זוג' : spouses.length > 1 ? 'בני/בנות זוג' : 'בן/בת זוג' }}</h2>
            <button class="btn-add-inline" @click="showAddSpouse = true">+ הוסף/י</button>
          </div>
          <div class="family-cards">
            <Link v-for="p in spouses" :key="p.id" :href="`/people/${p.id}`" class="mini-card">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
            <div v-if="spouses.length === 0" class="empty-family">לא הוגדר</div>
          </div>
        </div>

        <!-- ילדים -->
        <div class="family-section">
          <div class="section-header">
            <h2>ילדים {{ children.length ? `(${children.length})` : '' }}</h2>
            <button class="btn-add-inline" @click="openAddChild">+ הוסף ילד/ה</button>
          </div>
          <div class="family-cards">
            <Link v-for="p in children" :key="p.id" :href="`/people/${p.id}`" class="mini-card" :class="p.gender">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
            <div v-if="children.length === 0" class="empty-family">אין ילדים רשומים</div>
          </div>
        </div>

      </div>

      <!-- כפתורי פעולות תחתונים -->
      <div class="bottom-actions">
        <Link :href="`/people/${person.id}/edit`" class="btn-action btn-secondary-action">✏️ ערוך פרטים</Link>
        <button class="btn-action btn-ghost-action">🎉 מזל טוב</button>
        <button class="btn-action btn-ghost-action">💬 השאר הודעה</button>
      </div>

    </div>

    <!-- ─── Modal: הוספת ילד ─────────────────────────── -->
    <div v-if="showAddChild" class="modal-overlay" @click.self="showAddChild = false">
      <div class="modal" dir="rtl">
        <h3>הוספת ילד/ה ל{{ person.full_name }}</h3>

        <div class="form-row">
          <div class="form-group">
            <label>שם פרטי *</label>
            <input v-model="childForm.first_name" type="text" autofocus />
          </div>
          <div class="form-group">
            <label>שם משפחה</label>
            <input v-model="childForm.last_name" type="text" :placeholder="person.last_name" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>מגדר *</label>
            <div class="gender-toggle">
              <button type="button" :class="{ active: childForm.gender === 'male' }" @click="childForm.gender = 'male'">זכר</button>
              <button type="button" :class="{ active: childForm.gender === 'female' }" @click="childForm.gender = 'female'">נקבה</button>
            </div>
          </div>
          <div class="form-group">
            <label>תאריך לידה</label>
            <input v-model="childForm.birth_date_gregorian" type="date" />
          </div>
        </div>

        <!-- בן/בת זוג שני כהורה שני -->
        <div class="form-group" v-if="spouses.length">
          <label class="checkbox-label">
            <input type="checkbox" v-model="childForm.add_spouse_as_parent" />
            <span>הוסף גם את {{ spouses[0].full_name }} כהורה שני</span>
          </label>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddChild = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitChild"
            :disabled="!childForm.first_name || !childForm.gender || savingChild">
            {{ savingChild ? 'שומר...' : 'הוסף ילד/ה' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הוספת בן/בת זוג ────────────────────── -->
    <div v-if="showAddSpouse" class="modal-overlay" @click.self="showAddSpouse = false">
      <div class="modal" dir="rtl">
        <h3>הוספת בן/בת זוג ל{{ person.full_name }}</h3>

        <!-- טאבים: קיים / חדש -->
        <div class="tab-bar">
          <button :class="['tab', { active: spouseTab === 'new' }]" @click="spouseTab = 'new'">דמות חדשה</button>
          <button :class="['tab', { active: spouseTab === 'existing' }]" @click="spouseTab = 'existing'">מהעץ הקיים</button>
        </div>

        <!-- דמות חדשה -->
        <div v-if="spouseTab === 'new'">
          <div class="form-row">
            <div class="form-group">
              <label>שם פרטי *</label>
              <input v-model="spouseForm.first_name" type="text" autofocus />
            </div>
            <div class="form-group">
              <label>שם משפחה</label>
              <input v-model="spouseForm.last_name" type="text" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>מגדר *</label>
              <div class="gender-toggle">
                <button type="button" :class="{ active: spouseForm.gender === 'male' }" @click="spouseForm.gender = 'male'">זכר</button>
                <button type="button" :class="{ active: spouseForm.gender === 'female' }" @click="spouseForm.gender = 'female'">נקבה</button>
              </div>
            </div>
            <div class="form-group">
              <label>תאריך לידה</label>
              <input v-model="spouseForm.birth_date_gregorian" type="date" />
            </div>
          </div>
        </div>

        <!-- מהעץ הקיים -->
        <div v-if="spouseTab === 'existing'">
          <div class="form-group">
            <label>חיפוש בעץ</label>
            <input v-model="spouseSearch" type="text" placeholder="הקלד שם..." @input="filterSpouses" />
          </div>
          <div class="existing-list">
            <button
              v-for="p in filteredAllPeople"
              :key="p.id"
              :class="['existing-item', { selected: spouseForm.existing_id === p.id }]"
              @click="spouseForm.existing_id = p.id"
            >
              <div class="mini-initials-sm">{{ initials(p.label) }}</div>
              {{ p.label }}
            </button>
            <div v-if="filteredAllPeople.length === 0" class="empty-family">לא נמצאו תוצאות</div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddSpouse = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitSpouse" :disabled="!canSubmitSpouse || savingSpouse">
            {{ savingSpouse ? 'שומר...' : 'הוסף/י' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: אישור מחיקה ─────────────────────────── -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal" dir="rtl">
        <h3>מחיקת דמות</h3>
        <p>האם למחוק את <strong>{{ person.full_name }}</strong>?</p>
        <p class="modal-warning">פעולה זו תמחק את כל הקשרים ואינה ניתנת לביטול.</p>
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
import { ref, computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  person:    { type: Object, required: true },
  parents:   { type: Array,  default: () => [] },
  children:  { type: Array,  default: () => [] },
  spouses:   { type: Array,  default: () => [] },
  allPeople: { type: Array,  default: () => [] },
})

// ─── Modals state ────────────────────────────────────────
const showDeleteConfirm = ref(false)
const showAddChild      = ref(false)
const showAddSpouse     = ref(false)
const deleting          = ref(false)
const savingChild       = ref(false)
const savingSpouse      = ref(false)

// ─── Child form ──────────────────────────────────────────
const childForm = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', add_spouse_as_parent: false,
})

function openAddChild() {
  childForm.first_name = ''; childForm.last_name = ''; childForm.gender = ''
  childForm.birth_date_gregorian = ''; childForm.add_spouse_as_parent = false
  showAddChild.value = true
}

function submitChild() {
  savingChild.value = true
  const parentIds = [props.person.id]
  if (childForm.add_spouse_as_parent && props.spouses[0]) parentIds.push(props.spouses[0].id)

  router.post('/people', {
    first_name:           childForm.first_name,
    last_name:            childForm.last_name || props.person.last_name,
    gender:               childForm.gender,
    birth_date_gregorian: childForm.birth_date_gregorian || null,
    parent_ids:           parentIds,
  }, {
    onSuccess: () => { showAddChild.value = false },
    onFinish:  () => { savingChild.value = false },
  })
}

// ─── Spouse form ─────────────────────────────────────────
const spouseTab    = ref('new')
const spouseSearch = ref('')
const spouseForm   = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', existing_id: null,
})

const filteredAllPeople = computed(() => {
  const q = spouseSearch.value.toLowerCase()
  const excludeIds = new Set([props.person.id, ...props.spouses.map(s => s.id)])
  return props.allPeople
    .filter(p => !excludeIds.has(p.id))
    .filter(p => !q || p.label.toLowerCase().includes(q))
    .slice(0, 8)
})

const canSubmitSpouse = computed(() => {
  if (spouseTab.value === 'new') return spouseForm.first_name && spouseForm.gender
  return !!spouseForm.existing_id
})

function submitSpouse() {
  savingSpouse.value = true
  if (spouseTab.value === 'existing') {
    // קשר בן/בת זוג קיים
    router.post(`/people/${props.person.id}/spouse`, {
      spouse_id: spouseForm.existing_id,
    }, {
      onSuccess: () => { showAddSpouse.value = false },
      onFinish:  () => { savingSpouse.value = false },
    })
  } else {
    // יצירת דמות חדשה + קשר
    router.post('/people', {
      first_name:           spouseForm.first_name,
      last_name:            spouseForm.last_name || '',
      gender:               spouseForm.gender,
      birth_date_gregorian: spouseForm.birth_date_gregorian || null,
      spouse_id:            props.person.id,
    }, {
      onSuccess: () => { showAddSpouse.value = false },
      onFinish:  () => { savingSpouse.value = false },
    })
  }
}

// ─── Delete ──────────────────────────────────────────────
function deletePerson() {
  deleting.value = true
  router.delete(`/people/${props.person.id}`, {
    onFinish: () => { deleting.value = false },
  })
}

// ─── Helpers ─────────────────────────────────────────────
function initials(name) { return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2) }
function formatDate(d)  { if (!d) return ''; const [y,m,day] = d.split('-'); return `${day}/${m}/${y}` }
</script>

<style scoped>
.show-page { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; font-family: 'Rubik', sans-serif; }

.person-hero { border-radius: 20px; padding: 2rem; margin-bottom: 1.5rem; background: white; box-shadow: 0 4px 20px rgba(0,50,150,0.08); border-right: 6px solid #2d6be4; }
.person-hero.female  { border-right-color: #8b5cf6; }
.person-hero.deceased { border-right-color: #9ca3af; }

.hero-content { display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap; }

.avatar-wrap { width: 110px; height: 110px; border-radius: 50%; overflow: hidden; background: #e8f0fe; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
.initials-large { font-size: 2.2rem; font-weight: 700; color: #2d6be4; }

.hero-info { flex: 1; min-width: 200px; }
.name-row { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
h1 { font-size: 1.8rem; color: #1a3a6b; margin: 0; }
.deceased-badge { background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.9rem; }

.meta-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
.chip { background: #f0f7ff; border: 1px solid #d1e5fb; border-radius: 20px; padding: 0.25rem 0.75rem; font-size: 0.85rem; color: #2d4a7a; }
.chip-gray { background: #f1f5f9; border-color: #e2e8f0; color: #64748b; }
.hebrew-date { opacity: 0.8; }
.bio-text { color: #4a5568; font-size: 0.95rem; line-height: 1.6; margin: 0; }

.hero-actions { display: flex; flex-direction: column; gap: 0.5rem; align-self: flex-start; }
.btn-edit { background: #e8f0fe; color: #2d6be4; padding: 0.5rem 1.2rem; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; text-align: center; }
.btn-delete { background: none; border: 1.5px solid #fca5a5; color: #e74c3c; padding: 0.45rem 1.2rem; border-radius: 8px; font-size: 0.9rem; cursor: pointer; font-family: 'Rubik', sans-serif; }

.family-grid { display: flex; flex-direction: column; gap: 1.25rem; }
.family-section { background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 10px rgba(0,50,150,0.06); }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
h2 { font-size: 1rem; color: #2d4a7a; margin: 0; font-weight: 600; }
.btn-add-inline { background: none; border: 1.5px dashed #2d6be4; color: #2d6be4; padding: 0.3rem 0.8rem; border-radius: 7px; font-size: 0.85rem; cursor: pointer; font-family: 'Rubik', sans-serif; transition: background 0.2s; }
.btn-add-inline:hover { background: #edf3ff; }

.family-cards { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.empty-family { color: #aab; font-size: 0.88rem; padding: 0.5rem 0; }

.mini-card { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; text-decoration: none; color: #1a3a6b; font-size: 0.85rem; padding: 0.75rem; border-radius: 10px; background: #f8faff; border: 1px solid #e4eefb; transition: all 0.2s; min-width: 80px; text-align: center; }
.mini-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,50,150,0.1); }
.mini-card.female { background: #fdf4ff; border-color: #e9d5ff; }
.mini-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #e8f0fe; display: flex; align-items: center; justify-content: center; }
.mini-avatar img { width: 100%; height: 100%; object-fit: cover; }
.mini-initials { font-size: 1rem; font-weight: 700; color: #2d6be4; }

.bottom-actions { display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; padding-bottom: 2rem; }
.btn-action { padding: 0.65rem 1.5rem; border-radius: 10px; font-size: 0.95rem; font-family: 'Rubik', sans-serif; cursor: pointer; font-weight: 500; transition: all 0.2s; }
.btn-secondary-action { background: #e8f0fe; color: #2d6be4; border: none; text-decoration: none; display: inline-flex; align-items: center; }
.btn-ghost-action { background: white; border: 1.5px solid #d1dce8; color: #4a5568; }
.btn-ghost-action:hover { border-color: #2d6be4; color: #2d6be4; }

/* ─── Modals ─── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal { background: white; border-radius: 18px; padding: 2rem; max-width: 460px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.18); }
.modal h3 { margin: 0 0 1.25rem; color: #1a3a6b; font-size: 1.15rem; }

.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 130px; display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem; }
label { font-size: 0.85rem; color: #4a5568; font-weight: 500; }
input[type="text"], input[type="date"] { padding: 0.55rem 0.75rem; border: 1.5px solid #d1dce8; border-radius: 8px; font-size: 0.95rem; font-family: 'Rubik', sans-serif; direction: rtl; }
input:focus { outline: none; border-color: #2d6be4; }

.gender-toggle { display: flex; border: 1.5px solid #d1dce8; border-radius: 8px; overflow: hidden; }
.gender-toggle button { flex: 1; padding: 0.5rem; border: none; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #6b7a99; transition: all 0.2s; }
.gender-toggle button.active { background: #2d6be4; color: white; }

.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: #4a5568; }
.checkbox-label input { width: 16px; height: 16px; cursor: pointer; }

.tab-bar { display: flex; gap: 0; border: 1.5px solid #d1dce8; border-radius: 9px; overflow: hidden; margin-bottom: 1.25rem; }
.tab { flex: 1; padding: 0.55rem; border: none; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #6b7a99; transition: all 0.2s; }
.tab.active { background: #2d6be4; color: white; font-weight: 600; }

.existing-list { display: flex; flex-direction: column; gap: 0.35rem; max-height: 220px; overflow-y: auto; margin-top: 0.5rem; }
.existing-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border: 1.5px solid #e4eefb; border-radius: 9px; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #2d4a7a; text-align: right; transition: all 0.15s; }
.existing-item:hover { border-color: #2d6be4; background: #edf3ff; }
.existing-item.selected { border-color: #2d6be4; background: #dbeafe; font-weight: 600; }
.mini-initials-sm { width: 28px; height: 28px; border-radius: 50%; background: #e8f0fe; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #2d6be4; flex-shrink: 0; }

.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
.btn-cancel { background: white; border: 1.5px solid #d1dce8; color: #4a5568; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; }
.btn-primary-modal { background: #2d6be4; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; font-weight: 600; }
.btn-primary-modal:disabled { opacity: 0.55; cursor: not-allowed; }

.modal-warning { color: #e74c3c; font-size: 0.88rem; margin-bottom: 0.5rem; }
.btn-delete-confirm { background: #e74c3c; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; font-weight: 600; }
.btn-delete-confirm:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
