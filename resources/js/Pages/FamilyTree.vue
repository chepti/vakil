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
          <button class="ctrl-btn" title="מרכז מחדש" @click="centerTree">⊕ מרכז</button>
          <Link href="/people/create" class="ctrl-btn-primary">+ הוסף דמות</Link>
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

      <!-- Side panel — פרטי דמות -->
      <Transition name="panel-slide">
        <div v-if="selectedPerson" class="side-panel" dir="rtl">
          <button class="panel-close" @click="selectedPerson = null">×</button>

          <div class="panel-avatar">
            <img v-if="selectedPerson.avatar" :src="selectedPerson.avatar" :alt="fullName(selectedPerson)" />
            <div v-else class="panel-initials">{{ initials(fullName(selectedPerson)) }}</div>
          </div>

          <div class="panel-name">
            <h2>{{ fullName(selectedPerson) }}</h2>
            <span v-if="selectedPerson.is_deceased" class="badge-deceased">ז"ל</span>
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
          </div>

          <div class="panel-actions">
            <Link :href="`/people/${selectedPerson.id}`" class="panel-btn-primary">כרטיס מלא</Link>
            <Link :href="`/people/${selectedPerson.id}/edit`" class="panel-btn-secondary">עריכה</Link>
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

const props = defineProps({
  nodes:       { type: Array,  default: () => [] },
  totalPeople: { type: Number, default: 0 },
})

const chartContainer = ref(null)
const selectedPerson = ref(null)
let chartInstance    = null

// ─── init chart ─────────────────────────────────────────
onMounted(() => {
  if (!chartContainer.value || props.nodes.length === 0) return
  initChart()
})

onUnmounted(() => { chartInstance = null })

function initChart() {
  const cont = chartContainer.value

  chartInstance = createChart(cont, props.nodes)

  chartInstance
    .setCardHtml()
    .setCardDisplay([
      d => `${d.data['first name']} ${d.data['last name']}`,
      d => d.data.birthday ? formatDate(d.data.birthday) : '',
      d => d.data.occupation || '',
    ])
    .setCardDim({ width: 180, height: 100, text_x: 90, text_y: 22, img_x: 8, img_y: 8, img_w: 66, img_h: 66 })
    .setStyle('imageCircleRect')
    .setCardImageField('avatar')
    .setOnCardClick((e, d) => {
      selectedPerson.value = { id: d.id, ...d.data }
      chartInstance.updateMainId(d.id)
    })

  chartInstance
    .setTransitionTime(600)
    .updateTree({ initial: true })

  applyRtlStyles()
}

function centerTree() {
  if (chartInstance) chartInstance.updateTree({ initial: false })
}

// ─── RTL & gender color overrides ───────────────────────
function applyRtlStyles() {
  const style = document.createElement('style')
  style.textContent = `
    #FamilyChart .card-inner { direction: rtl; text-align: right; }
    #FamilyChart .card-body-text { font-family: 'Rubik', sans-serif; }
    #FamilyChart .card-body-text tspan { font-family: 'Rubik', sans-serif; }
    #FamilyChart .card[data-gender="M"] .card-rect { fill: #dbeafe; stroke: #2d6be4; }
    #FamilyChart .card[data-gender="F"] .card-rect { fill: #ede9fe; stroke: #8b5cf6; }
    #FamilyChart .card[data-gender="M"] .card-main-rect { fill: #2d6be4; }
    #FamilyChart .card[data-gender="F"] .card-main-rect { fill: #8b5cf6; }
  `
  document.head.appendChild(style)
}

// ─── helpers ────────────────────────────────────────────
function fullName(d) { return `${d['first name'] || ''} ${d['last name'] || ''}`.trim() }
function initials(name) { return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2) }
function formatDate(d) {
  if (!d) return ''
  try { const dt = new Date(d); return `${dt.getDate()}/${dt.getMonth()+1}/${dt.getFullYear()}` }
  catch { return d }
}
</script>

<style>
/* global — family-chart needs unscoped styles */
#FamilyChart { width: 100%; height: 100%; }
#FamilyChart .f3-cont { width: 100%; height: 100%; }
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.tree-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
  font-family: 'Rubik', sans-serif;
  position: relative;
  overflow: hidden;
  background: #f0f6ff;
}

/* ── Header ── */
.tree-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.5rem;
  background: white;
  border-bottom: 1px solid #e0eaf8;
  flex-shrink: 0;
  gap: 1rem;
  flex-wrap: wrap;
  z-index: 10;
}

.tree-title { display: flex; align-items: center; gap: 0.75rem; }
h1 { font-size: 1.2rem; color: #1a3a6b; margin: 0; }
.people-count { background: #e8f0fe; color: #2d6be4; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 12px; font-weight: 600; }

.tree-controls { display: flex; gap: 0.6rem; align-items: center; }

.ctrl-btn {
  background: white;
  border: 1.5px solid #d1dce8;
  color: #4a5568;
  padding: 0.4rem 0.9rem;
  border-radius: 8px;
  font-size: 0.88rem;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  transition: all 0.2s;
}
.ctrl-btn:hover { border-color: #2d6be4; color: #2d6be4; }

.ctrl-btn-primary {
  background: #2d6be4;
  color: white;
  border: none;
  padding: 0.4rem 1rem;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s;
}
.ctrl-btn-primary:hover { background: #1a55c8; }

/* ── Tree wrap ── */
.tree-wrap {
  flex: 1;
  position: relative;
  overflow: hidden;
}

/* ── Empty state ── */
.empty-tree {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: #8a9ab5;
  padding: 3rem;
}
.empty-icon { font-size: 3.5rem; }
.empty-tree h2 { font-size: 1.3rem; color: #2d4a7a; margin: 0; }
.empty-tree p { margin: 0; font-size: 0.95rem; }
.btn-start { background: #2d6be4; color: white; padding: 0.65rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 0.5rem; }

/* ── Side panel ── */
.side-panel {
  position: absolute;
  top: 0;
  right: 0;
  width: 290px;
  height: 100%;
  background: white;
  box-shadow: -4px 0 24px rgba(0,50,150,0.12);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  z-index: 50;
  overflow-y: auto;
}

.panel-close {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  background: none;
  border: none;
  font-size: 1.4rem;
  color: #8a9ab5;
  cursor: pointer;
  line-height: 1;
  padding: 0.25rem;
}
.panel-close:hover { color: #2d4a7a; }

.panel-avatar {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  overflow: hidden;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  flex-shrink: 0;
}
.panel-avatar img { width: 100%; height: 100%; object-fit: cover; }
.panel-initials { font-size: 1.8rem; font-weight: 700; color: #2d6be4; }

.panel-name { text-align: center; }
.panel-name h2 { font-size: 1.15rem; color: #1a3a6b; margin: 0 0 0.3rem; }
.badge-deceased { background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; padding: 0.15rem 0.5rem; border-radius: 5px; font-size: 0.8rem; }

.panel-details { display: flex; flex-direction: column; gap: 0.5rem; }
.detail-row { display: flex; gap: 0.6rem; align-items: flex-start; font-size: 0.88rem; color: #4a5568; }
.detail-icon { font-size: 0.95rem; flex-shrink: 0; }
.detail-sub { opacity: 0.75; }

.panel-actions { display: flex; flex-direction: column; gap: 0.5rem; margin-top: auto; }
.panel-btn-primary { background: #2d6be4; color: white; text-decoration: none; padding: 0.6rem; border-radius: 9px; font-size: 0.92rem; font-weight: 600; text-align: center; }
.panel-btn-primary:hover { background: #1a55c8; }
.panel-btn-secondary { background: #f0f7ff; color: #2d6be4; text-decoration: none; padding: 0.6rem; border-radius: 9px; font-size: 0.92rem; text-align: center; }
.panel-btn-secondary:hover { background: #dbeafe; }

/* ── Panel transition ── */
.panel-slide-enter-active, .panel-slide-leave-active { transition: transform 0.28s ease; }
.panel-slide-enter-from, .panel-slide-leave-to { transform: translateX(100%); }
</style>
