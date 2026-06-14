<template>
  <AppLayout :title="`עריכת ${person.full_name}`">
    <div class="edit-page" dir="rtl">
      <div class="page-header">
        <Link :href="`/people/${person.id}`" class="btn-back">← חזור לכרטיס</Link>
        <h1>עריכת {{ person.full_name }}</h1>
      </div>

      <form @submit.prevent="submit" class="person-form">
        <!-- פרטים בסיסיים -->
        <div class="form-section">
          <h2>פרטים אישיים</h2>

          <div class="form-row">
            <div class="form-group">
              <label>שם פרטי *</label>
              <input v-model="form.first_name" type="text" :class="{ 'is-error': form.errors.first_name }" />
              <span class="error-msg" v-if="form.errors.first_name">{{ form.errors.first_name }}</span>
            </div>
            <div class="form-group">
              <label>שם משפחה *</label>
              <input v-model="form.last_name" type="text" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>מגדר *</label>
              <div class="gender-toggle">
                <button type="button" :class="{ active: form.gender === 'male' }" @click="form.gender = 'male'">זכר</button>
                <button type="button" :class="{ active: form.gender === 'female' }" @click="form.gender = 'female'">נקבה</button>
              </div>
            </div>
            <div class="form-group">
              <label>תאריך לידה (לועזי)</label>
              <input v-model="form.birth_date_gregorian" type="date" />
            </div>
            <div class="form-group">
              <label>תאריך לידה עברי</label>
              <input v-model="form.birth_date_hebrew" type="text" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>מה עושה כיום</label>
              <input v-model="form.current_occupation" type="text" />
            </div>
            <div class="form-group">
              <label>עיר מגורים</label>
              <input v-model="form.city" type="text" />
            </div>
            <div class="form-group">
              <label>אימייל</label>
              <input v-model="form.email" type="email" dir="ltr" />
            </div>
          </div>

          <div class="form-group">
            <label>ביוגרפיה</label>
            <textarea v-model="form.bio" rows="3"></textarea>
          </div>

          <div class="deceased-section">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_deceased" />
              <span>נפטר/ה</span>
            </label>
            <div v-if="form.is_deceased" class="form-row" style="margin-top: 1rem">
              <div class="form-group">
                <label>תאריך פטירה (לועזי)</label>
                <input v-model="form.death_date_gregorian" type="date" />
              </div>
              <div class="form-group">
                <label>תאריך פטירה עברי</label>
                <input v-model="form.death_date_hebrew" type="text" />
              </div>
            </div>
          </div>
        </div>

        <!-- קשרי משפחה -->
        <div class="form-section">
          <h2>קשרי משפחה</h2>

          <div class="form-group">
            <label>הורים (עד 2)</label>
            <div class="multi-select">
              <div v-for="(pid, i) in form.parent_ids" :key="i" class="selected-person">
                <span>{{ getPersonName(pid) }}</span>
                <button type="button" @click="form.parent_ids.splice(i, 1)">×</button>
              </div>
              <select v-if="form.parent_ids.length < 2" @change="addParent($event)" class="add-select">
                <option value="">+ הוסף הורה</option>
                <option v-for="p in availablePeople" :key="p.id" :value="p.id" :disabled="form.parent_ids.includes(p.id)">
                  {{ p.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>בן/בת זוג</label>
            <div v-if="form.spouse_id" class="selected-person">
              <span>{{ getPersonName(form.spouse_id) }}</span>
              <button type="button" @click="form.spouse_id = null">×</button>
            </div>
            <select v-else @change="form.spouse_id = $event.target.value || null; $event.target.value = ''" class="add-select">
              <option value="">+ הוסף בן/בת זוג</option>
              <option v-for="p in availablePeople" :key="p.id" :value="p.id">{{ p.label }}</option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <Link :href="`/people/${person.id}`" class="btn-cancel">ביטול</Link>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ form.processing ? 'שומר...' : 'שמור שינויים' }}
          </button>
        </div>
      </form>

      <!-- העלאת תמונה — טופס נפרד -->
      <div class="form-section photo-section">
        <h2>תמונת פרופיל</h2>
        <div class="photo-row">
          <div class="photo-preview-wrap">
            <img v-if="photoPreview || person.photo_url" :src="photoPreview || person.photo_url" class="photo-preview" />
            <div v-else class="photo-placeholder">{{ initials(person.full_name) }}</div>
          </div>
          <div class="photo-controls">
            <label class="btn-choose-photo">
              📷 בחר תמונה
              <input type="file" accept="image/jpeg,image/png,image/webp" @change="handlePhotoSelect" hidden />
            </label>
            <button v-if="photoForm.profile_photo" @click="submitPhoto"
              class="btn-primary" :disabled="photoForm.processing" style="margin-top:0.5rem">
              {{ photoForm.processing ? 'מעלה...' : 'העלה' }}
            </button>
            <p v-if="photoForm.errors.profile_photo" class="error-msg">{{ photoForm.errors.profile_photo }}</p>
            <p class="photo-hint">JPG / PNG / WebP עד 5MB</p>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  person:    { type: Object, required: true },
  allPeople: { type: Array,  default: () => [] },
  parentIds: { type: Array,  default: () => [] },
  spouseId:  { type: [Number, null], default: null },
})

const form = useForm({
  first_name:            props.person.first_name,
  last_name:             props.person.last_name,
  gender:                props.person.gender,
  birth_date_gregorian:  props.person.birth_date_gregorian ?? '',
  birth_date_hebrew:     props.person.birth_date_hebrew ?? '',
  is_deceased:           props.person.is_deceased,
  death_date_gregorian:  props.person.death_date_gregorian ?? '',
  death_date_hebrew:     props.person.death_date_hebrew ?? '',
  current_occupation:    props.person.current_occupation ?? '',
  bio:                   props.person.bio ?? '',
  city:                  props.person.city ?? '',
  email:                 props.person.email ?? '',
  parent_ids:            [...(props.parentIds || [])],
  spouse_id:             props.spouseId,
})

const availablePeople = computed(() => props.allPeople)

function getPersonName(id) {
  return props.allPeople.find(p => p.id == id)?.label ?? `#${id}`
}

function addParent(e) {
  const id = parseInt(e.target.value)
  if (id && !form.parent_ids.includes(id)) form.parent_ids.push(id)
  e.target.value = ''
}

function submit() {
  form.patch(`/people/${props.person.id}`)
}

// ── Photo upload ──────────────────────────────────────────────────
const photoPreview = ref(null)
const photoForm = useForm({ profile_photo: null })

function initials(name) {
  return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2)
}

function handlePhotoSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  photoForm.profile_photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function submitPhoto() {
  photoForm.post(`/people/${props.person.id}/photo`, {
    onSuccess: () => { photoPreview.value = null },
  })
}
</script>

<style scoped>
.edit-page {
  max-width: 780px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }
.btn-back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; white-space: nowrap; }

.person-form { display: flex; flex-direction: column; gap: 1.5rem; }

.form-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.07);
  padding: 1.75rem;
}

h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0 0 1.25rem; }

.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }

.form-group {
  flex: 1;
  min-width: 140px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

label { font-size: 0.85rem; color: #4a5568; font-weight: 500; }

input[type="text"],
input[type="date"],
textarea,
select {
  padding: 0.55rem 0.75rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  background: white;
}

input:focus, textarea:focus, select:focus { outline: none; border-color: #2d6be4; }
input.is-error { border-color: #e74c3c; }
textarea { resize: vertical; }
.error-msg { color: #e74c3c; font-size: 0.8rem; }

.gender-toggle { display: flex; border: 1.5px solid #d1dce8; border-radius: 8px; overflow: hidden; }
.gender-toggle button { flex: 1; padding: 0.5rem; border: none; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #6b7a99; }
.gender-toggle button.active { background: #2d6be4; color: white; }

.deceased-section { margin-top: 0.5rem; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem; color: #4a5568; }
.checkbox-label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

.multi-select { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.selected-person { background: #e8f0fe; border-radius: 20px; padding: 0.3rem 0.75rem; display: flex; align-items: center; gap: 0.4rem; font-size: 0.88rem; color: #1a3a6b; }
.selected-person button { background: none; border: none; color: #4a5568; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; }
.add-select { flex: 1; min-width: 160px; }

.form-actions { display: flex; justify-content: flex-end; gap: 1rem; padding: 1rem 0 2rem; }

.btn-primary {
  background: #2d6be4; color: white; border: none;
  padding: 0.7rem 2rem; border-radius: 10px;
  font-size: 1rem; font-family: 'Rubik', sans-serif; font-weight: 600; cursor: pointer;
}
.btn-primary:hover:not(:disabled) { background: #1a55c8; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-cancel {
  color: #6b7a99; text-decoration: none;
  padding: 0.7rem 1.5rem; border-radius: 10px;
  border: 1.5px solid #d1dce8; display: inline-flex; align-items: center;
}

/* ── Photo section ── */
.photo-section { margin-top: 1.5rem; }
.photo-row { display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap; }
.photo-preview-wrap { flex-shrink: 0; }
.photo-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #dbeafe; }
.photo-placeholder {
  width: 100px; height: 100px; border-radius: 50%;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 700; color: #2d6be4;
}
.photo-controls { display: flex; flex-direction: column; justify-content: center; }
.btn-choose-photo {
  display: inline-block; cursor: pointer; padding: 0.5rem 1rem;
  background: #f0f7ff; border: 1.5px solid #2d6be4; color: #2d6be4;
  border-radius: 8px; font-size: 0.9rem; font-family: 'Rubik', sans-serif;
  transition: all 0.2s;
}
.btn-choose-photo:hover { background: #dbeafe; }
.photo-hint { font-size: 0.78rem; color: #94a3b8; margin-top: 0.5rem; }
</style>
