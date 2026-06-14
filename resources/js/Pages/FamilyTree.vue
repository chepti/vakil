<template>
  <AppLayout title="עץ המשפחה">
    <div class="tree-page" dir="rtl">

      <!-- Header -->
      <div class="tree-header">
        <div class="tree-title">
          <h1>🌳 עץ משפחת ואקיל</h1>
          <span class="people-count">{{ totalPeople }} דמויות</span>
        </div>
        <div class="tree-controls">
          <button class="ctrl-btn" @click="centerTree" title="חזור למרכז">⊕ מרכז</button>
          <button class="ctrl-btn" @click="goToRoot" title="הצג מהאב הקדמון">🌳 שורש</button>
          <button class="ctrl-btn" :class="{ active: showSiblings }" @click="toggleSiblings" title="הצג/הסתר אחים">
            {{ showSiblings ? '👥 הסתר אחים' : '👥 הצג אחים' }}
          </button>
          <div class="depth-ctrl">
            <button class="ctrl-btn icon-btn" @click="changeDepth(-1)" title="פחות דורות" :disabled="depth <= 1">−</button>
            <span class="depth-label">{{ depth }} דורות</span>
            <button class="ctrl-btn icon-btn" @click="changeDepth(1)" title="יותר דורות" :disabled="depth >= 7">+</button>
          </div>
          <Link v-if="isAdmin" href="/people/create" class="ctrl-btn-primary">+ הוסף דמות</Link>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="nodes.length === 0" class="empty-tree">
        <div class="empty-icon">🌱</div>
        <h2>העץ ריק עדיין</h2>
        <p>התחל בהוספת הדמות הראשונה</p>
        <Link href="/onboarding" class="btn-start">התחל עכשיו</Link>
      </div>

      <!-- Tree container -->
      <div v-else class="tree-wrap">
        <div ref="chartContainer" id="FamilyChart" class="f3"></div>
      </div>

      <!-- Side panel -->
      <Transition name="panel-slide">
        <div v-if="selectedPerson" class="side-panel" dir="rtl">
          <button class="panel-close" @click="selectedPerson = null; addRelType = null">×</button>

          <div class="panel-avatar">
            <img v-if="selectedPerson.avatar" :src="selectedPerson.avatar" :alt="fullName(selectedPerson)" />
            <div v-else class="panel-initials">{{ initials(fullName(selectedPerson)) }}</div>
          </div>

          <div class="panel-name">
            <h2>{{ fullName(selectedPerson) }}</h2>
            <span v-if="selectedPerson.is_deceased" class="badge-deceased">ז"ל</span>
            <span v-if="currentDefaultId === String(selectedPerson.id)" class="badge-main">⭐ ראשי</span>
          </div>

          <div class="panel-details">
            <div v-if="selectedPerson.birthday" class="detail-row">
              <span class="detail-icon">🎂</span>
              <span>{{ formatDate(selectedPerson.birthday) }}
                <span v-if="selectedPerson.birthday_he" class="detail-sub"> / {{ selectedPerson.birthday_he }}</span>
              </span>
            </div>
            <div v-if="selectedPerson.is_deceased && selectedPerson.death_date" class="detail-row">
              <span class="detail-icon">🕯</span>
              <span>{{ formatDate(selectedPerson.death_date) }}</span>
            </div>
            <div v-if="selectedPerson.occupation" class="detail-row">
              <span class="detail-icon">💼</span>
              <span>{{ selectedPerson.occupation }}</span>
            </div>
            <div v-if="selectedPerson.city" class="detail-row">
              <span class="detail-icon">📍</span>
              <span>{{ selectedPerson.city }}</span>
            </div>
            <div v-if="selectedPerson.email" class="detail-row">
              <span class="detail-icon">✉️</span>
              <span dir="ltr">{{ selectedPerson.email }}</span>
            </div>
          </div>

          <!-- ─── Add relative ─── -->
          <div class="add-relative-section">
            <div class="add-rel-title">הוסף קרוב משפחה</div>
            <div class="add-rel-buttons">
              <button v-for="rel in relTypes" :key="rel.key"
                class="add-rel-btn"
                :class="{ active: addRelType === rel.key }"
                @click="openAddRel(rel)"
              >{{ rel.label }}</button>
            </div>

            <div v-if="addRelType" class="add-rel-form">
              <input v-model="addRelForm.first_name" type="text" placeholder="שם פרטי *" class="rel-input" />
              <input v-model="addRelForm.last_name" type="text" placeholder="שם משפחה" class="rel-input" />
              <div class="rel-gender">
                <button type="button" :class="{ active: addRelForm.gender === 'M' }" @click="addRelForm.gender = 'M'">זכר</button>
                <button type="button" :class="{ active: addRelForm.gender === 'F' }" @click="addRelForm.gender = 'F'">נקבה</button>
              </div>
              <div class="add-rel-form-actions">
                <button class="rel-cancel" @click="addRelType = null">ביטול</button>
                <button class="rel-submit" @click="submitAddRel"
                  :disabled="!addRelForm.first_name || !addRelForm.gender || addRelSaving">
                  {{ addRelSaving ? '...' : 'הוסף' }}
                </button>
              </div>
            </div>
          </div>

          <div class="panel-actions">
            <Link :href="`/people/${selectedPerson.id}`" class="panel-btn-primary">כרטיס מלא</Link>
            <button v-if="isAdmin" @click="setAsDefault(selectedPerson.id)"
              class="panel-btn-star" :class="{ active: currentDefaultId === String(selectedPerson.id) }">
              {{ currentDefaultId === String(selectedPerson.id) ? '⭐ ברירת מחדל לכולם' : '☆ הגדר כברירת מחדל' }}
            </button>
          </div>
        </div>
      </Transition>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { createChart } from 'family-chart'
import 'family-chart/styles/family-chart.css'

const props = defineProps({
  nodes:               { type: Array,   default: () => [] },
  totalPeople:         { type: Number,  default: 0 },
  isAdmin:             { type: Boolean, default: false },
  rootPersonId:        { type: String,  default: null },
  defaultMainPersonId: { type: String,  default: null },
})

const chartContainer   = ref(null)
const selectedPerson   = ref(null)
const currentDefaultId = ref(props.defaultMainPersonId)
const depth            = ref(4)
const showSiblings     = ref(true)
let chartInstance      = null

// ─── Add relative ─────────────────────────────────────────
const relTypes = [
  { key: 'father',  label: '+ אב',          gender: 'M', relsKey: 'children' },
  { key: 'mother',  label: '+ אם',          gender: 'F', relsKey: 'children' },
  { key: 'spouse',  label: '+ בן/בת זוג',   gender: null, relsKey: 'spouses' },
  { key: 'son',     label: '+ בן',          gender: 'M', relsKey: 'parents' },
  { key: 'daughter',label: '+ בת',          gender: 'F', relsKey: 'parents' },
  { key: 'sibling', label: '+ אח/אחות',    gender: null, relsKey: 'siblings' },
]
const addRelType    = ref(null)
const addRelSaving  = ref(false)
const addRelForm    = ref({ first_name: '', last_name: '', gender: '' })

function openAddRel(rel) {
  if (addRelType.value === rel.key) { addRelType.value = null; return }
  addRelType.value = rel.key

  let gender = rel.gender ?? ''
  if (rel.key === 'spouse' && selectedPerson.value) {
    const sg = selectedPerson.value.gender
    gender = sg === 'M' ? 'F' : sg === 'F' ? 'M' : ''
  }

  addRelForm.value = {
    first_name: '',
    last_name:  selectedPerson.value?.['last name'] ?? '',
    gender,
  }
}

async function submitAddRel() {
  if (!selectedPerson.value || !addRelForm.value.first_name || !addRelForm.value.gender) return
  addRelSaving.value = true

  const selfId  = String(selectedPerson.value.id)
  const relMeta = relTypes.find(r => r.key === addRelType.value)

  // For sibling: find parent IDs from nodes
  let datum
  const baseData = {
    'first name': addRelForm.value.first_name,
    'last name':  addRelForm.value.last_name,
    gender:       addRelForm.value.gender,
    birthday:     '',
    occupation:   '',
    city:         '',
  }

  if (addRelType.value === 'sibling') {
    const selfNode  = props.nodes.find(n => n.id === selfId)
    const parentIds = selfNode?.rels?.parents ?? []
    datum = {
      id: 'new-sibling',
      data: baseData,
      rels: { parents: parentIds, spouses: [], children: [] },
    }
  } else {
    datum = {
      id: 'new-' + addRelType.value,
      data: baseData,
      rels: {
        parents:  relMeta.relsKey === 'parents'  ? [selfId] : [],
        spouses:  relMeta.relsKey === 'spouses'  ? [selfId] : [],
        children: relMeta.relsKey === 'children' ? [selfId] : [],
      },
    }
  }

  try {
    const freshNodes = await apiPost('/api/family-tree/person', datum)
    chartInstance.updateData(freshNodes).updateTree({ tree_position: 'inherit' })
    addRelType.value = null
  } catch (err) {
    alert('שגיאה בהוספה')
    console.error(err)
  } finally {
    addRelSaving.value = false
  }
}

onMounted(() => {
  if (!chartContainer.value || props.nodes.length === 0) return
  initChart()
})

onUnmounted(() => { chartInstance = null })

function csrfToken() {
  return document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function apiPost(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    body: body ? JSON.stringify(body) : undefined,
  })
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json()
}

async function apiDelete(url) {
  const res = await fetch(url, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrfToken() },
  })
  if (res.status === 403) throw new Error('403')
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json()
}

function initChart() {
  const cont = chartContainer.value
  chartInstance = createChart(cont, props.nodes)

  chartInstance
    .setCardHtml()
    .setCardDisplay([
      d => `${d.data['first name'] || ''} ${d.data['last name'] || ''}`.trim(),
      d => d.data.birthday ? formatDate(d.data.birthday) : '',
    ])
    .setCardDim({ width: 210, height: 90, text_x: 80, text_y: 20, img_x: 6, img_y: 6, img_w: 62, img_h: 62 })
    .setStyle('imageCircleRect')
    .setCardImageField('avatar')
    .setOnCardClick((e, d) => {
      selectedPerson.value = { id: d.data.id, ...d.data.data }
      addRelType.value = null
      chartInstance.updateMainId(d.data.id)
    })

  // ── כפתורי + ועריכה inline ────────────────────────────────────
  chartInstance.editTree()
    .setFields([
      { type: 'text', label: 'שם פרטי',    id: 'first name' },
      { type: 'text', label: 'שם משפחה',   id: 'last name' },
      { type: 'date', label: 'תאריך לידה', id: 'birthday' },
      { type: 'text', label: 'עיסוק',      id: 'occupation' },
    ])
    .setAddRelLabels({
      father:  'הוסף אב',
      mother:  'הוסף אם',
      spouse:  'הוסף בן/בת זוג',
      son:     'הוסף בן',
      daughter:'הוסף בת',
    })
    .setCanDelete(() => props.isAdmin)
    .setOnSubmit(async (e, datum, applyChanges, postSubmit) => {
      try {
        const freshNodes = await apiPost('/api/family-tree/person', datum)
        chartInstance.updateData(freshNodes).updateTree({ tree_position: 'inherit' })
        postSubmit()
      } catch (err) {
        alert('שגיאה בשמירה')
        console.error(err)
      }
    })
    .setOnDelete(async (datum, _deleteFn, postSubmit) => {
      const name = `${datum.data['first name'] || ''} ${datum.data['last name'] || ''}`.trim()
      if (!confirm(`למחוק את ${name}?`)) return
      try {
        const freshNodes = await apiDelete(`/api/family-tree/person/${datum.id}`)
        chartInstance.updateData(freshNodes).updateTree({ tree_position: 'inherit' })
        postSubmit({})
      } catch (err) {
        if (err.message === '403') alert('רק מנהל יכול למחוק')
        else alert('שגיאה במחיקה')
      }
    })

  // ── הגדרות תצוגה ─────────────────────────────────────────────
  if (props.defaultMainPersonId) {
    chartInstance.updateMainId(props.defaultMainPersonId)
  }

  chartInstance
    .setTransitionTime(500)
    .setShowSiblingsOfMain(showSiblings.value)
    .setAncestryDepth(depth.value)
    .setProgenyDepth(depth.value)
    .updateTree({ initial: true })
}

function centerTree() {
  if (chartInstance) chartInstance.updateTree({ initial: true })
}

function goToRoot() {
  if (chartInstance && props.rootPersonId) {
    chartInstance.updateMainId(props.rootPersonId)
    selectedPerson.value = null
  }
}

function toggleSiblings() {
  showSiblings.value = !showSiblings.value
  if (chartInstance) {
    chartInstance.setShowSiblingsOfMain(showSiblings.value).updateTree({})
  }
}

function changeDepth(delta) {
  const newDepth = Math.min(7, Math.max(1, depth.value + delta))
  if (newDepth === depth.value) return
  depth.value = newDepth
  if (chartInstance) {
    chartInstance
      .setAncestryDepth(newDepth)
      .setProgenyDepth(newDepth)
      .updateTree({})
  }
}

async function setAsDefault(personId) {
  try {
    await apiPost(`/api/family-tree/set-main/${personId}`, null)
    currentDefaultId.value = String(personId)
  } catch {
    alert('שגיאה בהגדרת ברירת מחדל')
  }
}

// helpers
function fullName(d) { return `${d['first name'] || ''} ${d['last name'] || ''}`.trim() }
function initials(name) { return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2) }
function formatDate(d) {
  if (!d) return ''
  try { const dt = new Date(d); return `${dt.getDate()}/${dt.getMonth()+1}/${dt.getFullYear()}` }
  catch { return d }
}
</script>

<style>
/* ── Override family-chart CSS variables — light theme + Rubik ── */
#FamilyChart.f3 {
  --male-color:       #93c5fd;
  --female-color:     #c4b5fd;
  --background-color: transparent;
  --text-color:       #1a3a6b;
  font-family: 'Rubik', sans-serif !important;
}
#FamilyChart .f3 { font-family: 'Rubik', sans-serif !important; }

/* Card label RTL */
#FamilyChart .card-label {
  direction: rtl; text-align: right;
  font-family: 'Rubik', sans-serif; font-size: 0.78rem; line-height: 1.35; overflow: hidden;
}
#FamilyChart .card-label div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

/* Transparent SVG background — allow edge-card button overflow */
#FamilyChart svg.main_svg { background: transparent !important; overflow: visible !important; }

/* ── Add-relative "+" buttons — blue on light bg (default=white, invisible) ── */
#FamilyChart .card_add_relative             { color: #2d6be4 !important; }
#FamilyChart .card_add_relative circle      { fill: rgba(45,107,228,.08) !important; stroke: #2d6be4 !important; stroke-width: 1.5 !important; }
#FamilyChart .card_add_relative:hover       { color: #1a3a6b !important; }
#FamilyChart .card_add_relative:hover circle{ fill: rgba(45,107,228,.18) !important; }

/* ── Edit pencil ── */
#FamilyChart .card_edit        { color: #2d6be4 !important; }
#FamilyChart .card_edit circle { fill: rgba(45,107,228,.08) !important; }

/* ── New-person placeholder card ── */
#FamilyChart .card_add .card-body-rect { fill: #dbeafe !important; stroke: #2d6be4 !important; stroke-width: 2 !important; }
#FamilyChart g.card_add text            { fill: #2d6be4 !important; }

/* Sizing */
#FamilyChart       { width: 100%; height: 100%; }
#FamilyChart .f3-cont { width: 100%; height: 100%; }
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.tree-page {
  display: flex; flex-direction: column;
  height: calc(100vh - 60px);
  font-family: 'Rubik', sans-serif;
  position: relative; overflow: hidden;
  background: #f0f6ff;
}

/* ── Header ── */
.tree-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.6rem 1.2rem;
  background: white; border-bottom: 1px solid #e0eaf8;
  flex-shrink: 0; gap: 0.75rem; flex-wrap: wrap; z-index: 10;
}
.tree-title { display: flex; align-items: center; gap: 0.6rem; }
h1 { font-size: 1.1rem; color: #1a3a6b; margin: 0; }
.people-count { background: #e8f0fe; color: #2d6be4; font-size: 0.78rem; padding: 0.18rem 0.55rem; border-radius: 12px; font-weight: 600; }

.tree-controls { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }

.ctrl-btn {
  background: white; border: 1.5px solid #d1dce8; color: #4a5568;
  padding: 0.35rem 0.8rem; border-radius: 8px; font-size: 0.82rem;
  cursor: pointer; font-family: 'Rubik', sans-serif; transition: all 0.2s; white-space: nowrap;
}
.ctrl-btn:hover:not(:disabled) { border-color: #2d6be4; color: #2d6be4; }
.ctrl-btn:disabled { opacity: 0.4; cursor: default; }
.ctrl-btn.active { border-color: #2d6be4; color: #2d6be4; background: #eef3fd; }

.icon-btn { padding: 0.35rem 0.65rem; font-size: 1rem; }

.depth-ctrl { display: flex; align-items: center; gap: 0.25rem; }
.depth-label { font-size: 0.8rem; color: #4a5568; min-width: 52px; text-align: center; }

.ctrl-btn-primary {
  background: #2d6be4; color: white; border: none;
  padding: 0.38rem 0.9rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  text-decoration: none; transition: background 0.2s; white-space: nowrap;
}
.ctrl-btn-primary:hover { background: #1a55c8; }

/* ── Tree wrap ── */
.tree-wrap { flex: 1; position: relative; overflow: hidden; }

/* ── Empty state ── */
.empty-tree {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.75rem; color: #8a9ab5; padding: 3rem;
}
.empty-icon { font-size: 3.5rem; }
.empty-tree h2 { font-size: 1.3rem; color: #2d4a7a; margin: 0; }
.empty-tree p { margin: 0; font-size: 0.95rem; }
.btn-start { background: #2d6be4; color: white; padding: 0.65rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 0.5rem; }

/* ── Side panel ── */
.side-panel {
  position: absolute; top: 0; right: 0; width: 285px; height: 100%;
  background: white; box-shadow: -4px 0 24px rgba(0,50,150,.12);
  padding: 1.3rem; display: flex; flex-direction: column; gap: 0.9rem;
  z-index: 50; overflow-y: auto;
}
.panel-close {
  position: absolute; top: 0.6rem; left: 0.6rem;
  background: none; border: none; font-size: 1.4rem; color: #8a9ab5; cursor: pointer;
}
.panel-close:hover { color: #2d4a7a; }

.panel-avatar {
  width: 85px; height: 85px; border-radius: 50%; overflow: hidden;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  margin: 0 auto; flex-shrink: 0;
  border: 3px solid #dbeafe;
}
.panel-avatar img { width: 100%; height: 100%; object-fit: cover; }
.panel-initials { font-size: 1.7rem; font-weight: 700; color: #2d6be4; }

.panel-name { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.3rem; }
.panel-name h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0; }
.badge-deceased { background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; padding: 0.12rem 0.45rem; border-radius: 5px; font-size: 0.78rem; }
.badge-main { background: #fefce8; border: 1px solid #fde047; color: #854d0e; padding: 0.12rem 0.45rem; border-radius: 5px; font-size: 0.78rem; }

.panel-details { display: flex; flex-direction: column; gap: 0.45rem; }
.detail-row { display: flex; gap: 0.55rem; align-items: flex-start; font-size: 0.85rem; color: #4a5568; }
.detail-icon { font-size: 0.9rem; flex-shrink: 0; }
.detail-sub { opacity: 0.75; }

.panel-actions { display: flex; flex-direction: column; gap: 0.45rem; margin-top: auto; }
.panel-btn-primary { background: #2d6be4; color: white; text-decoration: none; padding: 0.55rem; border-radius: 9px; font-size: 0.88rem; font-weight: 600; text-align: center; display: block; }
.panel-btn-primary:hover { background: #1a55c8; }
.panel-btn-secondary { background: #f0f7ff; color: #2d6be4; text-decoration: none; padding: 0.55rem; border-radius: 9px; font-size: 0.88rem; text-align: center; display: block; }
.panel-btn-secondary:hover { background: #dbeafe; }
.panel-btn-star {
  background: none; border: 1.5px solid #d1dce8; color: #8a9ab5;
  padding: 0.5rem; border-radius: 9px; font-size: 0.82rem; cursor: pointer;
  font-family: 'Rubik', sans-serif; transition: all 0.2s;
}
.panel-btn-star:hover { border-color: #f59e0b; color: #d97706; }
.panel-btn-star.active { border-color: #f59e0b; color: #d97706; background: #fefce8; font-weight: 600; }

/* ─── Add relative ─── */
.add-relative-section {
  border: 1.5px solid #e4eefb; border-radius: 12px; padding: 0.75rem;
  background: #f8faff;
}
.add-rel-title { font-size: 0.78rem; font-weight: 600; color: #4a5568; margin-bottom: 0.5rem; }
.add-rel-buttons {
  display: flex; flex-wrap: wrap; gap: 0.3rem;
}
.add-rel-btn {
  padding: 0.28rem 0.55rem; border: 1.5px solid #d1dce8; border-radius: 6px;
  background: white; color: #2d6be4; font-size: 0.75rem; font-family: 'Rubik', sans-serif;
  cursor: pointer; transition: all 0.15s; white-space: nowrap;
}
.add-rel-btn:hover { border-color: #2d6be4; background: #edf3ff; }
.add-rel-btn.active { background: #2d6be4; color: white; border-color: #2d6be4; }

.add-rel-form { margin-top: 0.65rem; display: flex; flex-direction: column; gap: 0.35rem; }
.rel-input {
  width: 100%; padding: 0.4rem 0.6rem; border: 1.5px solid #d1dce8; border-radius: 7px;
  font-size: 0.85rem; font-family: 'Rubik', sans-serif; direction: rtl;
  box-sizing: border-box; background: white;
}
.rel-input:focus { outline: none; border-color: #2d6be4; }
.rel-gender { display: flex; border: 1.5px solid #d1dce8; border-radius: 7px; overflow: hidden; }
.rel-gender button {
  flex: 1; padding: 0.35rem; border: none; background: white; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.82rem; color: #6b7a99; transition: all 0.15s;
}
.rel-gender button.active { background: #2d6be4; color: white; }
.add-rel-form-actions { display: flex; gap: 0.4rem; }
.rel-cancel {
  flex: 1; padding: 0.38rem; background: white; border: 1.5px solid #d1dce8; color: #4a5568;
  border-radius: 7px; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.82rem;
}
.rel-submit {
  flex: 2; padding: 0.38rem; background: #2d6be4; color: white; border: none;
  border-radius: 7px; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.82rem; font-weight: 600;
}
.rel-submit:disabled { opacity: 0.55; cursor: not-allowed; }

/* ── Panel transition ── */
.panel-slide-enter-active, .panel-slide-leave-active { transition: transform 0.28s ease; }
.panel-slide-enter-from, .panel-slide-leave-to { transform: translateX(100%); }
</style>
