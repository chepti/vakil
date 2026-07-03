<template>
  <AppLayout title="תצוגה מקדימה לייבוא">
    <div class="review-page" dir="rtl">
      <div class="page-header">
        <Link href="/people-import" class="btn-back">← חזור להעלאה</Link>
        <h1>תצוגה מקדימה לייבוא</h1>
      </div>

      <div class="summary-bar">
        <span class="summary-item"><span class="dot dot-new"></span>{{ newCount }} דמויות חדשות</span>
        <span class="summary-item"><span class="dot dot-match"></span>{{ matchCount }} עם התאמה אפשרית</span>
        <span class="summary-item" v-if="ambiguousCount"><span class="dot dot-ambig"></span>{{ ambiguousCount }} עם כמה מועמדים</span>
      </div>

      <form @submit.prevent="submit">
        <div v-for="(groupIds, gi) in groups" :key="gi" class="branch-card">
          <h2 class="branch-title">
            ענף {{ gi + 1 }}: {{ rowsMap[groupIds[0]].first_name }} {{ rowsMap[groupIds[0]].last_name }}
          </h2>

          <div class="table-list">
            <div v-for="rowId in groupIds" :key="rowId" class="row-block">
              <div class="row-compact" :class="rowColorClass(rowId)" @click="toggleExpand(rowId)">
                <button type="button" class="chevron">{{ expanded[rowId] ? '▾' : '▸' }}</button>

                <span class="r-name">{{ rowsMap[rowId].first_name }} {{ rowsMap[rowId].last_name }}</span>

                <span class="relation-tag" :class="rowsMap[rowId].relation">{{ relationLabel(rowsMap[rowId]) }}</span>

                <select v-model="rowsMap[rowId].decision" @click.stop class="decision-select">
                  <option value="new">דמות חדשה</option>
                  <option v-for="c in rowsMap[rowId].candidates" :key="c.id" :value="`match:${c.id}`">
                    שיוך: {{ c.full_name }} ({{ c.city || '—' }}) · {{ c.score }}
                  </option>
                  <option value="skip">אל תייבא (דלג על השורה)</option>
                </select>

                <span class="match-hint" :class="rowColorClass(rowId)">
                  {{ rowsMap[rowId].decision === 'skip'
                      ? 'לא ייובא'
                      : (rowsMap[rowId].candidates.length ? `נמצאה התאמה (${rowsMap[rowId].candidates.length})` : 'לא נמצאה בעץ') }}
                </span>
              </div>

              <div v-if="expanded[rowId]" class="row-details">
                <div class="row-fields">
                  <div class="f-group f-name">
                    <label>שם פרטי</label>
                    <input v-model="rowsMap[rowId].first_name" type="text" />
                  </div>
                  <div class="f-group f-name">
                    <label>שם משפחה</label>
                    <input v-model="rowsMap[rowId].last_name" type="text" />
                  </div>
                  <div class="f-group f-name" v-if="rowsMap[rowId].gender === 'female'">
                    <label>שם נעורים</label>
                    <input v-model="rowsMap[rowId].maiden_name" type="text" />
                  </div>
                  <div class="f-group f-gender">
                    <label>מגדר</label>
                    <div class="gender-toggle">
                      <button type="button" :class="{ active: rowsMap[rowId].gender === 'male' }" @click="rowsMap[rowId].gender = 'male'">ז</button>
                      <button type="button" :class="{ active: rowsMap[rowId].gender === 'female' }" @click="rowsMap[rowId].gender = 'female'">נ</button>
                    </div>
                  </div>
                  <div class="f-group">
                    <label>טלפון</label>
                    <input v-model="rowsMap[rowId].phone" type="text" />
                  </div>
                  <div class="f-group">
                    <label>
                      כתובת/עיר
                      <span v-if="rowsMap[rowId].city_inherited" class="inherited-note">(ירשה מההורה)</span>
                    </label>
                    <input v-model="rowsMap[rowId].city" type="text" />
                  </div>
                  <div class="f-group">
                    <label>תאריך לידה (הערכה)</label>
                    <input v-model="rowsMap[rowId].birth_date_estimate" type="date" />
                  </div>
                  <div class="f-group f-wide">
                    <label>עיסוק</label>
                    <input v-model="rowsMap[rowId].current_occupation" type="text" />
                  </div>
                  <div class="f-group f-wide">
                    <label>ביו קצר</label>
                    <textarea v-model="rowsMap[rowId].bio" rows="2"></textarea>
                  </div>
                </div>
                <div class="ref-line" v-if="rowsMap[rowId].ref_row_id">
                  {{ rowsMap[rowId].relation === 'spouse' ? 'בן/בת זוג של' : 'ילד/ה של' }}
                  {{ rowsMap[rowsMap[rowId].ref_row_id]?.first_name }} · מקור: {{ rowsMap[rowId].source_page }}
                </div>

                <div class="manual-search">
                  <label>לא מצאת את ההתאמה הנכונה למעלה? חפשו כל דמות קיימת בעץ:</label>
                  <input
                    type="text"
                    v-model="searchQuery[rowId]"
                    placeholder="הקלידו שם לחיפוש..."
                    @focus="activeSearch = rowId"
                  />
                  <div v-if="activeSearch === rowId && searchQuery[rowId]" class="search-results">
                    <div
                      v-for="p in searchResults(rowId)"
                      :key="p.id"
                      class="search-result"
                      @click="pickManualMatch(rowId, p)"
                    >
                      {{ p.full_name }} <span class="sr-meta">{{ p.city || '—' }} · {{ p.phone || '—' }}</span>
                    </div>
                    <div v-if="searchResults(rowId).length === 0" class="search-empty">אין תוצאות</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <Link href="/people-import" class="btn-cancel">ביטול</Link>
          <button type="submit" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'מייבא...' : `ייבא ${totalRows} דמויות` }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  token:     { type: String, required: true },
  grouped:   { type: Array, required: true }, // array of arrays of row objects
  allPeople: { type: Array, default: () => [] },
})

const submitting = ref(false)
const expanded = reactive({})
const searchQuery = reactive({})
const activeSearch = ref(null)

const rowsMap = reactive({})
const groups = props.grouped.map(group => group.map(row => {
  rowsMap[row.row_id] = { ...row, decision: row.suggested_decision }
  return row.row_id
}))

function searchResults(rowId) {
  const q = (searchQuery[rowId] || '').trim()
  if (!q) return []
  return props.allPeople.filter(p => p.full_name.includes(q)).slice(0, 8)
}

function pickManualMatch(rowId, person) {
  const row = rowsMap[rowId]
  if (!row.candidates.some(c => c.id === person.id)) {
    row.candidates.push({ id: person.id, full_name: person.full_name, city: person.city, phone: person.phone, score: 0 })
  }
  row.decision = `match:${person.id}`
  searchQuery[rowId] = ''
  activeSearch.value = null
}

const totalRows = computed(() => Object.keys(rowsMap).length)
const newCount = computed(() => Object.values(rowsMap).filter(r => r.candidates.length === 0).length)
const matchCount = computed(() => Object.values(rowsMap).filter(r => r.candidates.length > 0).length)
const ambiguousCount = computed(() => Object.values(rowsMap).filter(r => r.candidates.length > 1).length)

function toggleExpand(rowId) {
  expanded[rowId] = !expanded[rowId]
}

function rowColorClass(rowId) {
  if (rowsMap[rowId].decision === 'skip') return 'c-skip'
  const n = rowsMap[rowId].candidates.length
  if (n > 1) return 'c-ambiguous'
  if (n === 1) return 'c-match'
  return 'c-new'
}

function relationLabel(row) {
  if (row.relation === 'root_of_branch') return 'שורש ענף'
  if (row.relation === 'spouse') return 'בן/בת זוג'
  return 'ילד/ה'
}

function submit() {
  submitting.value = true
  const rows = Object.values(rowsMap).map(r => ({
    row_id: r.row_id,
    decision: r.decision,
    first_name: r.first_name,
    last_name: r.last_name,
    maiden_name: r.maiden_name,
    gender: r.gender,
    phone: r.phone,
    city: r.city,
    current_occupation: r.current_occupation,
    birth_date_estimate: r.birth_date_estimate || null,
    bio: r.bio,
  }))

  router.post('/people-import/commit', { token: props.token, rows }, {
    onFinish: () => { submitting.value = false },
  })
}
</script>

<style scoped>
.review-page {
  max-width: 980px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1rem;
}

h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }

.btn-back {
  color: #2d6be4;
  text-decoration: none;
  font-size: 0.9rem;
  white-space: nowrap;
}

.summary-bar {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
  font-size: 0.88rem;
  color: #4a5568;
}

.summary-item { display: flex; align-items: center; gap: 0.4rem; }

.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.dot-new   { background: #f0b429; }
.dot-match { background: #38a169; }
.dot-ambig { background: #e74c3c; }

.branch-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.07);
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}

.branch-title {
  font-size: 1.1rem;
  color: #1a3a6b;
  margin: 0 0 1rem;
}

.table-list { display: flex; flex-direction: column; gap: 0.4rem; }

.row-compact {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  border-radius: 10px;
  cursor: pointer;
  border: 1.5px solid transparent;
}

.row-compact.c-new     { background: #fffaf0; border-color: #f0b429; }
.row-compact.c-match   { background: #f0fff4; border-color: #38a169; }
.row-compact.c-ambiguous { background: #fff5f5; border-color: #e74c3c; }
.row-compact.c-skip    { background: #f2f2f2; border-color: #aab; opacity: 0.7; }

.chevron {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
  color: #6b7a99;
  width: 1.2rem;
  flex-shrink: 0;
}

.r-name {
  font-weight: 600;
  color: #1a3a6b;
  min-width: 130px;
  flex-shrink: 0;
}

.relation-tag {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 20px;
  background: #e8f0fe;
  color: #1a3a6b;
  flex-shrink: 0;
  white-space: nowrap;
}

.relation-tag.root_of_branch { background: #e8f7ee; color: #1a6b3a; }
.relation-tag.spouse { background: #fdeef6; color: #a01a6b; }

.decision-select {
  flex: 1;
  min-width: 160px;
  padding: 0.35rem 0.5rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.82rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  background: white;
}

.match-hint {
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
  flex-shrink: 0;
}

.match-hint.c-new { color: #b7791f; }
.match-hint.c-match { color: #2f855a; }
.match-hint.c-ambiguous { color: #c0392b; }
.match-hint.c-skip { color: #6b7a99; }

.row-details {
  padding: 0.85rem 1rem 1rem 2.2rem;
  background: #f8faff;
  border-radius: 0 0 10px 10px;
  margin-top: -0.3rem;
}

.row-fields {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.f-group {
  flex: 1;
  min-width: 130px;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.f-group.f-name { min-width: 110px; }
.f-group.f-gender { flex: 0 0 70px; min-width: 70px; }
.f-group.f-wide { flex: 1 1 100%; }

label {
  font-size: 0.78rem;
  color: #4a5568;
  font-weight: 500;
}

.inherited-note {
  color: #2d6be4;
  font-weight: 400;
  font-size: 0.72rem;
}

input[type="text"], input[type="date"], textarea {
  padding: 0.45rem 0.6rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.88rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  background: white;
}

input:focus, textarea:focus { outline: none; border-color: #2d6be4; }

textarea { resize: vertical; }

.gender-toggle { display: flex; border: 1.5px solid #d1dce8; border-radius: 8px; overflow: hidden; }
.gender-toggle button {
  flex: 1; padding: 0.4rem; border: none; background: white; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.85rem; color: #6b7a99;
}
.gender-toggle button.active { background: #2d6be4; color: white; }

.ref-line {
  margin-top: 0.6rem;
  font-size: 0.78rem;
  color: #8a9ab5;
}

.manual-search {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #d1dce8;
  position: relative;
}

.manual-search label {
  display: block;
  margin-bottom: 0.4rem;
}

.manual-search input[type="text"] {
  width: 100%;
}

.search-results {
  position: absolute;
  z-index: 5;
  left: 0;
  right: 0;
  background: white;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  margin-top: 0.3rem;
  max-height: 220px;
  overflow-y: auto;
  box-shadow: 0 4px 16px rgba(0,50,150,0.12);
}

.search-result {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.85rem;
  color: #1a3a6b;
  border-bottom: 1px solid #f0f2f8;
}

.search-result:last-child { border-bottom: none; }
.search-result:hover { background: #edf3ff; }

.sr-meta {
  color: #8a9ab5;
  font-size: 0.78rem;
  margin-right: 0.5rem;
}

.search-empty {
  padding: 0.5rem 0.75rem;
  font-size: 0.82rem;
  color: #8a9ab5;
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
}

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
}
</style>
