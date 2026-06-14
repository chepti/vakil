<template>
  <AppLayout title="הוספת דמות">
    <div class="create-page" dir="rtl">
      <div class="page-header">
        <Link href="/people" class="btn-back">← חזור לרשימה</Link>
        <h1>הוספת דמות חדשה</h1>
      </div>

      <form @submit.prevent="submit" class="person-form">
        <!-- פרטים בסיסיים -->
        <div class="form-section">
          <h2>פרטים אישיים</h2>

          <div class="form-row">
            <div class="form-group">
              <label>שם פרטי *</label>
              <input v-model="form.first_name" type="text" :class="{ 'is-error': errors.first_name }" />
              <span class="error-msg" v-if="errors.first_name">{{ errors.first_name }}</span>
            </div>
            <div class="form-group">
              <label>שם משפחה *</label>
              <input v-model="form.last_name" type="text" :class="{ 'is-error': errors.last_name }" />
              <span class="error-msg" v-if="errors.last_name">{{ errors.last_name }}</span>
            </div>
            <div v-if="form.gender === 'female'" class="form-group">
              <label>שם נעורים</label>
              <input v-model="form.maiden_name" type="text" placeholder="שם לפני הנישואין" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>מגדר *</label>
              <div class="gender-toggle">
                <button type="button" :class="{ active: form.gender === 'male' }" @click="form.gender = 'male'">זכר</button>
                <button type="button" :class="{ active: form.gender === 'female' }" @click="form.gender = 'female'">נקבה</button>
              </div>
              <span class="error-msg" v-if="errors.gender">{{ errors.gender }}</span>
            </div>
            <div class="form-group">
              <label>תאריך לידה (לועזי)</label>
              <input v-model="form.birth_date_gregorian" type="date" />
            </div>
            <div class="form-group">
              <label>תאריך לידה עברי</label>
              <input v-model="form.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>מה עושה כיום</label>
              <input v-model="form.current_occupation" type="text" placeholder="מקצוע, עיסוק, תחביב..." />
            </div>
            <div class="form-group">
              <label>עיר מגורים</label>
              <input v-model="form.city" type="text" />
            </div>
          </div>

          <div class="form-group">
            <label>ביוגרפיה קצרה</label>
            <textarea v-model="form.bio" rows="3" placeholder="כמה מילים על האדם..."></textarea>
          </div>

          <!-- נפטר -->
          <div class="deceased-section">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_deceased" />
              <span>נפטר/ה</span>
            </label>

            <div v-if="form.is_deceased" class="form-row deceased-dates">
              <div class="form-group">
                <label>תאריך פטירה (לועזי)</label>
                <input v-model="form.death_date_gregorian" type="date" />
              </div>
              <div class="form-group">
                <label>תאריך פטירה עברי</label>
                <input v-model="form.death_date_hebrew" type="text" placeholder='י"ד אדר תשפ"ה' />
              </div>
            </div>
          </div>
        </div>

        <!-- קשרי משפחה -->
        <div class="form-section">
          <h2>קשרי משפחה</h2>

          <!-- הורים -->
          <div class="form-group">
            <label>הורים (עד 2)</label>
            <div class="multi-select">
              <div v-for="(pid, i) in form.parent_ids" :key="i" class="selected-person">
                <span>{{ getPersonName(pid) }}</span>
                <button type="button" @click="form.parent_ids.splice(i, 1)">×</button>
              </div>
              <select v-if="form.parent_ids.length < 2" @change="addParent($event)" class="add-select">
                <option value="">+ בחר הורה מהרשימה</option>
                <option
                  v-for="p in availablePeople"
                  :key="p.id"
                  :value="p.id"
                  :disabled="form.parent_ids.includes(p.id)"
                >{{ p.label }}</option>
              </select>
            </div>
          </div>

          <!-- בן/בת זוג -->
          <div class="form-group">
            <label>בן/בת זוג</label>
            <div v-if="form.spouse_id" class="selected-person">
              <span>{{ getPersonName(form.spouse_id) }}</span>
              <button type="button" @click="form.spouse_id = null">×</button>
            </div>
            <select v-else @change="form.spouse_id = $event.target.value || null; $event.target.value = ''" class="add-select">
              <option value="">+ בחר מהרשימה</option>
              <option v-for="p in availablePeople" :key="p.id" :value="p.id">{{ p.label }}</option>
            </select>
          </div>
        </div>

        <!-- ילדים — הוספה bulk -->
        <div class="form-section">
          <div class="section-header">
            <h2>ילדים</h2>
            <div class="bulk-add-row">
              <input
                v-model.number="bulkCount"
                type="number" min="1" max="20"
                class="bulk-input"
                @keydown.enter.prevent
              />
              <button type="button" class="btn-bulk-add" @click="addBulkChildren">
                הוסף {{ bulkCount }} ילדים
              </button>
            </div>
          </div>

          <p class="section-hint" v-if="form.children.length === 0">
            הוסף ילדים ריקים — לחץ על כל כרטיס להזין פרטים
          </p>

          <div v-for="(child, i) in form.children" :key="i" class="child-card">
            <div class="child-card-header">
              <span class="child-num">ילד {{ i + 1 }}</span>
              <button type="button" class="btn-remove" @click="form.children.splice(i, 1)">הסר</button>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>שם פרטי *</label>
                <input v-model="child.first_name" type="text" :class="{ 'is-error': errors[`children.${i}.first_name`] }" />
              </div>
              <div class="form-group">
                <label>שם משפחה</label>
                <input v-model="child.last_name" type="text" :placeholder="form.last_name" />
              </div>
              <div class="form-group form-group-sm">
                <label>מגדר *</label>
                <div class="gender-toggle">
                  <button type="button" :class="{ active: child.gender === 'male' }" @click="child.gender = 'male'">ז</button>
                  <button type="button" :class="{ active: child.gender === 'female' }" @click="child.gender = 'female'">נ</button>
                </div>
              </div>
              <div class="form-group">
                <label>תאריך לידה</label>
                <input v-model="child.birth_date_gregorian" type="date" />
              </div>
            </div>
          </div>

          <div v-if="form.children.length > 0" class="children-summary">
            {{ form.children.length }} {{ form.children.length === 1 ? 'ילד' : 'ילדים' }} יתווספו
          </div>
        </div>

        <!-- כפתורי שמירה -->
        <div class="form-actions">
          <Link href="/people" class="btn-cancel">ביטול</Link>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ form.processing ? 'שומר...' : 'שמור דמות' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  allPeople: { type: Array, default: () => [] },
})

const bulkCount = ref(3)

const form = useForm({
  first_name: '',
  last_name: '',
  maiden_name: '',
  gender: '',
  birth_date_gregorian: '',
  birth_date_hebrew: '',
  is_deceased: false,
  death_date_gregorian: '',
  death_date_hebrew: '',
  current_occupation: '',
  bio: '',
  city: '',
  parent_ids: [],
  spouse_id: null,
  children: [],
})

const errors = computed(() => form.errors)

const availablePeople = computed(() => props.allPeople)

function getPersonName(id) {
  return props.allPeople.find(p => p.id == id)?.label ?? `#${id}`
}

function addParent(e) {
  const id = parseInt(e.target.value)
  if (id && !form.parent_ids.includes(id)) {
    form.parent_ids.push(id)
  }
  e.target.value = ''
}

function addChild() {
  form.children.push({ first_name: '', last_name: '', gender: '', birth_date_gregorian: '' })
}

function addBulkChildren() {
  const n = Math.max(1, Math.min(20, bulkCount.value || 1))
  for (let i = 0; i < n; i++) {
    form.children.push({ first_name: '', last_name: '', gender: '', birth_date_gregorian: '' })
  }
}

function submit() {
  form.post('/people')
}
</script>

<style scoped>
.create-page {
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

h1 {
  font-size: 1.5rem;
  color: #1a3a6b;
  margin: 0;
}

.btn-back {
  color: #2d6be4;
  text-decoration: none;
  font-size: 0.9rem;
  white-space: nowrap;
}

.person-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.07);
  padding: 1.75rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

h2 {
  font-size: 1.1rem;
  color: #1a3a6b;
  margin: 0 0 1.25rem;
}

.section-header h2 { margin: 0; }

.section-hint {
  color: #8a9ab5;
  font-size: 0.88rem;
  margin-top: 0;
  margin-bottom: 0.5rem;
}

.form-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.form-group {
  flex: 1;
  min-width: 140px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

.form-group-sm {
  flex: 0 0 90px;
  min-width: 90px;
}

label {
  font-size: 0.85rem;
  color: #4a5568;
  font-weight: 500;
}

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
  transition: border-color 0.2s;
  background: white;
}

input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #2d6be4;
}

input.is-error { border-color: #e74c3c; }

textarea { resize: vertical; }

.error-msg {
  color: #e74c3c;
  font-size: 0.8rem;
}

.gender-toggle {
  display: flex;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  overflow: hidden;
}

.gender-toggle button {
  flex: 1;
  padding: 0.5rem;
  border: none;
  background: white;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.9rem;
  color: #6b7a99;
  transition: all 0.2s;
}

.gender-toggle button.active {
  background: #2d6be4;
  color: white;
}

.deceased-section {
  margin-top: 0.5rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.95rem;
  color: #4a5568;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.deceased-dates { margin-top: 1rem; }

.multi-select {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.selected-person {
  background: #e8f0fe;
  border-radius: 20px;
  padding: 0.3rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.88rem;
  color: #1a3a6b;
}

.selected-person button {
  background: none;
  border: none;
  color: #4a5568;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
  padding: 0;
}

.add-select {
  flex: 1;
  min-width: 160px;
}

.bulk-add-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.bulk-input {
  width: 60px;
  padding: 0.45rem 0.5rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.95rem;
  text-align: center;
  font-family: 'Rubik', sans-serif;
}

.btn-bulk-add {
  background: #2d6be4;
  color: white;
  border: none;
  padding: 0.45rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  transition: background 0.2s;
  white-space: nowrap;
}

.btn-bulk-add:hover { background: #1a55c8; }

.btn-add-child {
  background: none;
  border: 1.5px dashed #2d6be4;
  color: #2d6be4;
  padding: 0.45rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.9rem;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-add-child:hover { background: #edf3ff; }

.child-card {
  background: #f8faff;
  border: 1px solid #d1dce8;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 0.75rem;
}

.child-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.child-num {
  font-size: 0.85rem;
  font-weight: 600;
  color: #2d4a7a;
}

.btn-remove {
  background: none;
  border: none;
  color: #e74c3c;
  cursor: pointer;
  font-size: 0.85rem;
  font-family: 'Rubik', sans-serif;
  padding: 0.2rem 0.5rem;
}

.children-summary {
  text-align: center;
  color: #2d6be4;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.5rem;
  background: #edf3ff;
  border-radius: 8px;
  margin-top: 0.5rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 0 2rem;
}

.btn-primary {
  background: #2d6be4;
  color: white;
  border: none;
  padding: 0.7rem 2rem;
  border-radius: 10px;
  font-size: 1rem;
  font-family: 'Rubik', sans-serif;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) { background: #1a55c8; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-cancel {
  color: #6b7a99;
  text-decoration: none;
  padding: 0.7rem 1.5rem;
  border-radius: 10px;
  font-size: 1rem;
  border: 1.5px solid #d1dce8;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s;
}

.btn-cancel:hover { border-color: #aab; }
</style>
