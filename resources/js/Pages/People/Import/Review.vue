<template>
  <AppLayout title="תצוגה מקדימה לייבוא">
    <div class="review-page" dir="rtl">
      <div class="page-header">
        <Link href="/people-import" class="btn-back">← חזור להעלאה</Link>
        <h1>תצוגה מקדימה לייבוא</h1>
      </div>

      <p class="hint">
        {{ totalRows }} שורות, מקובצות ל-{{ groups.length }} ענפים. לכל שורה עם הצעת התאמה לדמות קיימת
        אפשר לבחור "שיוך לדמות קיימת" או להשאיר "דמות חדשה". שורות עם כמה מועמדים מסומנות ומחייבות בחירה.
      </p>

      <form @submit.prevent="submit">
        <div v-for="(groupIds, gi) in groups" :key="gi" class="branch-card">
          <h2 class="branch-title">
            ענף {{ gi + 1 }}: {{ rowsMap[groupIds[0]].first_name }} {{ rowsMap[groupIds[0]].last_name }}
          </h2>

          <div v-for="rowId in groupIds" :key="rowId" class="row-card" :class="{ ambiguous: isAmbiguous(rowId) }">
            <div class="row-top">
              <span class="relation-tag" :class="rowsMap[rowId].relation">{{ relationLabel(rowsMap[rowId]) }}</span>
              <span v-if="rowsMap[rowId].ref_row_id" class="ref-note">
                ← {{ rowsMap[rowId].relation === 'spouse' ? 'בן/בת זוג של' : 'ילד/ה של' }}
                {{ rowsMap[rowsMap[rowId].ref_row_id]?.first_name }}
              </span>
              <span class="source-note">{{ rowsMap[rowId].source_page }}</span>
            </div>

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
                <label>כתובת/עיר</label>
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

            <div class="decision-row">
              <label>שיוך בעץ המשפחה</label>
              <select v-model="rowsMap[rowId].decision">
                <option value="new">דמות חדשה</option>
                <option v-for="c in rowsMap[rowId].candidates" :key="c.id" :value="`match:${c.id}`">
                  שיוך לדמות קיימת: {{ c.full_name }} ({{ c.city || '—' }}, {{ c.phone || '—' }}) · ניקוד {{ c.score }}
                </option>
              </select>
              <span v-if="isAmbiguous(rowId)" class="ambiguous-note">כמה מועמדים אפשריים — בדקו לפני ייבוא</span>
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
  token:   { type: String, required: true },
  grouped: { type: Array, required: true }, // array of arrays of row objects
})

const submitting = ref(false)

const rowsMap = reactive({})
const groups = props.grouped.map(group => group.map(row => {
  rowsMap[row.row_id] = { ...row, decision: row.suggested_decision }
  return row.row_id
}))

const totalRows = computed(() => Object.keys(rowsMap).length)

function isAmbiguous(rowId) {
  return (rowsMap[rowId].candidates?.length ?? 0) > 1
}

function relationLabel(row) {
  if (row.relation === 'root_of_branch') return 'שורש ענף (ללא הורה עדיין)'
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

.hint {
  color: #4a5568;
  font-size: 0.9rem;
  line-height: 1.6;
  margin: 0 0 1.5rem;
}

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

.row-card {
  border: 1.5px solid #e2e8f4;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  background: #f8faff;
}

.row-card.ambiguous {
  border-color: #f0b429;
  background: #fffaf0;
}

.row-top {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.6rem;
  flex-wrap: wrap;
}

.relation-tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  background: #e8f0fe;
  color: #1a3a6b;
}

.relation-tag.root_of_branch { background: #e8f7ee; color: #1a6b3a; }
.relation-tag.spouse { background: #fdeef6; color: #a01a6b; }

.ref-note, .source-note {
  font-size: 0.78rem;
  color: #8a9ab5;
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

input[type="text"], input[type="date"], textarea, select {
  padding: 0.45rem 0.6rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.88rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  background: white;
}

input:focus, textarea:focus, select:focus { outline: none; border-color: #2d6be4; }

textarea { resize: vertical; }

.gender-toggle { display: flex; border: 1.5px solid #d1dce8; border-radius: 8px; overflow: hidden; }
.gender-toggle button {
  flex: 1; padding: 0.4rem; border: none; background: white; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.85rem; color: #6b7a99;
}
.gender-toggle button.active { background: #2d6be4; color: white; }

.decision-row {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #d1dce8;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.decision-row select { width: 100%; }

.ambiguous-note {
  color: #b7791f;
  font-size: 0.8rem;
  font-weight: 600;
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
