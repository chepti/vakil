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
          <!-- Search -->
          <div class="search-wrap">
            <div class="search-box">
              <input
                v-model="searchQuery"
                @focus="searchOpen = true"
                @blur="searchOpen = false"
                type="text"
                placeholder="חיפוש דמות..."
                class="search-input"
                dir="rtl"
                autocomplete="off"
              />
              <span class="search-icon">🔍</span>
            </div>
            <div v-if="searchOpen && searchResults.length" class="search-dropdown">
              <div
                v-for="r in searchResults" :key="r.id"
                class="search-result"
                @mousedown.prevent="goToPerson(r.id)"
              >
                <div class="search-result-avatar">
                  <img v-if="r.avatar" :src="r.avatar" />
                  <span v-else>{{ initials(r.name) }}</span>
                </div>
                <span>{{ r.name }}</span>
              </div>
            </div>
          </div>

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
          <button class="ctrl-btn" :class="{ active: compactMode }" @click="toggleCompactMode" title="פסים אנכיים — מכווץ את הרוחב, רחף להרחיב">
            {{ compactMode ? '⊠ מצב רגיל' : '⊟ קומפקטי' }}
          </button>
          <button class="ctrl-btn" :class="{ active: radialMode }" @click="toggleRadialMode" title="תצוגה עגולה/ספיראלית">
            {{ radialMode ? '⊞ עץ רגיל' : '◎ עגול' }}
          </button>
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

      <!-- Tree container — v-show keeps the chart instance alive when toggling radial -->
      <div v-show="nodes.length > 0 && !radialMode" class="tree-wrap">
        <div ref="chartContainer" id="FamilyChart" class="f3"></div>
      </div>

      <!-- Radial view -->
      <div v-show="nodes.length > 0 && radialMode" class="radial-wrap">
        <!-- breadcrumb: who is at center -->
        <div class="radial-center-label" v-if="radialCenterId && radialCenterId !== String(props.rootPersonId || props.defaultMainPersonId || props.nodes[0]?.id)">
          <button class="radial-back-btn" @click="resetRadialToRoot()">← חזור לשורש</button>
        </div>
        <svg
          class="radial-svg"
          :viewBox="`${radialVB.x} ${radialVB.y} ${radialVB.w} ${radialVB.h}`"
          @wheel.prevent="onRadialWheel"
          @pointerdown="onRadialBgPointerDown"
          @pointermove="onRadialPointerMove"
          @pointerup="onRadialPointerUp"
          @pointercancel="onRadialPointerUp"
        >
          <!-- Links — child=solid, spouse=dashed -->
          <line
            v-for="link in radialData.links" :key="link.key"
            :x1="link.x1" :y1="link.y1" :x2="link.x2" :y2="link.y2"
            :stroke="link.type === 'spouse' ? '#f0abfc' : link.type === 'parent' ? '#fcd34d' : '#b0c8e4'"
            :stroke-width="link.type === 'spouse' || link.type === 'parent' ? 1.5 : 1.2"
            :stroke-dasharray="link.type === 'spouse' ? '4 3' : link.type === 'parent' ? '5 3' : 'none'"
            stroke-linecap="round"
          />
          <!-- Nodes -->
          <g
            v-for="node in radialData.nodes" :key="node.id"
            :transform="`translate(${node.x},${node.y})`"
            class="radial-node"
            :class="{ 'radial-spouse': node.relType === 'spouse' }"
            @pointerdown.stop
            @click.stop="onRadialNodeClick(node.id)"
          >
            <!-- Relationship ring indicators -->
            <circle
              v-if="node.relType === 'spouse' && !node.isRoot"
              :r="node.nodeR + 4" fill="none" stroke="#e879f9" stroke-width="1.5" stroke-dasharray="3 2" opacity="0.7"
            />
            <circle
              v-if="node.relType === 'parent' && !node.isRoot"
              :r="node.nodeR + 4" fill="none" stroke="#f59e0b" stroke-width="2" opacity="0.85"
            />
            <!-- Avatar clip -->
            <clipPath :id="`rclip-${node.id}`">
              <circle :r="node.nodeR" cx="0" cy="0"/>
            </clipPath>
            <circle
              :r="node.nodeR"
              :fill="radialNodeColor(node.gender)"
              :stroke="radialNodeStroke(node.gender)"
              :stroke-width="node.isRoot ? 3 : 1.5"
            />
            <image
              v-if="node.avatar"
              :href="node.avatar"
              :x="-node.nodeR" :y="-node.nodeR"
              :width="node.nodeR * 2" :height="node.nodeR * 2"
              :clip-path="`url(#rclip-${node.id})`"
              preserveAspectRatio="xMidYMid slice"
            />
            <text
              v-else
              text-anchor="middle" dominant-baseline="central"
              :font-size="node.isRoot ? 11 : 9"
              fill="#1a3a6b" font-family="Rubik, sans-serif" font-weight="500"
            >{{ node.firstName.slice(0, 4) }}</text>
            <!-- Name label placed radially outward — white stroke for readability -->
            <text
              :x="node.labelX" :y="node.labelY"
              text-anchor="middle"
              :dominant-baseline="node.labelBaseline"
              font-family="Rubik, sans-serif"
              :font-size="node.isRoot ? 12 : 8.5"
              :font-weight="node.isRoot ? '700' : '500'"
              fill="#1a3a6b"
              stroke="rgba(240,246,255,0.85)" stroke-width="3" paint-order="stroke"
            >{{ node.isRoot ? node.fullName : node.firstName }}</text>
            <title>{{ node.fullName }}{{ node.relType === 'spouse' ? ' (בן/בת זוג)' : node.relType === 'parent' ? ' (הורה)' : '' }}</title>
          </g>
        </svg>
        <div class="radial-hint">גלגל לזום · גרירה להזזה · לחיצה להצגת מעגלי הקשר</div>
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
            <template v-if="selectedPerson.marriages">
              <template v-for="(m, sid) in selectedPerson.marriages" :key="sid">
                <div v-if="m && (m.date || m.date_he)" class="detail-row">
                  <span class="detail-icon">💍</span>
                  <span>{{ m.date ? formatDate(m.date) : '' }}<span v-if="m.date_he" class="detail-sub"> / {{ m.date_he }}</span></span>
                </div>
              </template>
            </template>
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
              <input v-model="addRelForm.birth_date_gregorian" type="date" class="rel-input" />
              <input v-model="addRelForm.birth_date_hebrew" type="text" placeholder='תאריך לידה עברי (כ"ה תשרי תשפ"ה)' class="rel-input" />
              <template v-if="addRelType === 'spouse'">
                <input v-model="addRelForm.marriage_date_gregorian" type="date" class="rel-input" />
                <input v-model="addRelForm.marriage_date_hebrew" type="text" placeholder='תאריך נישואין עברי (כ"ב אייר תש"פ)' class="rel-input" />
              </template>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
const compactMode      = ref(false)
let chartInstance      = null
let cardHtml           = null

// ─── Search ────────────────────────────────────────────────────
const searchQuery = ref('')
const searchOpen  = ref(false)

const searchResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return []
  return props.nodes
    .filter(n => {
      const name = `${n.data?.['first name'] || ''} ${n.data?.['last name'] || ''}`.toLowerCase()
      return name.includes(q)
    })
    .slice(0, 10)
    .map(n => ({
      id: n.id,
      name: `${n.data?.['first name'] || ''} ${n.data?.['last name'] || ''}`.trim(),
      avatar: n.data?.avatar || null,
    }))
})

function goToPerson(id) {
  searchQuery.value = ''
  searchOpen.value  = false
  if (!chartInstance) return
  chartInstance.updateMainId(id)
  const node = props.nodes.find(n => n.id === id)
  if (node) selectedPerson.value = { id: node.id, ...node.data }
}

// ─── Add relative ─────────────────────────────────────────────
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
const addRelForm    = ref({ first_name: '', last_name: '', birth_date_gregorian: '', birth_date_hebrew: '', marriage_date_gregorian: '', marriage_date_hebrew: '', gender: '' })

function openAddRel(rel) {
  if (addRelType.value === rel.key) { addRelType.value = null; return }
  addRelType.value = rel.key

  let gender = rel.gender ?? ''
  if (rel.key === 'spouse' && selectedPerson.value) {
    const sg = selectedPerson.value.gender
    gender = sg === 'M' ? 'F' : sg === 'F' ? 'M' : ''
  }

  addRelForm.value = {
    first_name:              '',
    last_name:               selectedPerson.value?.['last name'] ?? '',
    birth_date_gregorian:    '',
    birth_date_hebrew:       '',
    marriage_date_gregorian: '',
    marriage_date_hebrew:    '',
    gender,
  }
}

async function submitAddRel() {
  if (!selectedPerson.value || !addRelForm.value.first_name || !addRelForm.value.gender) return
  addRelSaving.value = true

  const selfId  = String(selectedPerson.value.id)
  const relMeta = relTypes.find(r => r.key === addRelType.value)

  let datum
  const baseData = {
    'first name': addRelForm.value.first_name,
    'last name':  addRelForm.value.last_name,
    gender:       addRelForm.value.gender,
    birthday:         addRelForm.value.birth_date_gregorian    || '',
    birthday_he:      addRelForm.value.birth_date_hebrew       || '',
    marriage_date:    addRelForm.value.marriage_date_gregorian || '',
    marriage_date_he: addRelForm.value.marriage_date_hebrew    || '',
    occupation:       '',
    city:             '',
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

onUnmounted(() => { chartInstance = null; cardHtml = null })

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

  // setCardHtml() returns a CardHtml instance (not chartInstance) — store it for reuse
  cardHtml = chartInstance
    .setCardHtml()
    .setCardDisplay([
      d => `${d.data['first name'] || ''} ${d.data['last name'] || ''}`.trim(),
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

// ── Compact mode ─────────────────────────────────────────────
function compactInnerHtml(d) {
  const raw    = d.data.data || {}
  const first  = (raw['first name'] || '').trim()
  const last   = (raw['last name']  || '').trim()
  const name   = [first, last].filter(Boolean).join(' ')
  const avatar = raw.avatar || null
  const gender = raw.gender
  const depth  = d.depth

  const base   = gender === 'M' ? '#dbeafe' : gender === 'F' ? '#ede9fe' : '#e2e8f0'
  const accent = gender === 'M' ? '#3b82f6' : gender === 'F' ? '#8b5cf6' : '#94a3b8'

  if (depth <= 1) {
    // Main (0) or children (1): circle photo + first name, fits in 32px slot
    const photo = avatar
      ? `<img src="${avatar}" style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:1.5px solid ${accent};display:block;flex-shrink:0">`
      : `<div style="width:26px;height:26px;border-radius:50%;background:${base};border:1.5px solid ${accent};display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;color:${accent};flex-shrink:0">${first[0] || '?'}</div>`
    return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:88px;gap:2px;overflow:hidden">${photo}<div style="font-size:0.44rem;text-align:center;color:#1e3a5f;width:30px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;line-height:1">${first}</div></div>`
  } else if (depth === 2) {
    // Grandchildren: colored strip + rotated first name
    return `<div style="position:relative;height:88px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:${base};border-radius:5px"><span style="position:absolute;transform:rotate(-90deg);white-space:nowrap;font-size:0.54rem;font-weight:600;color:${accent};max-width:80px;overflow:hidden;text-overflow:ellipsis">${first || name}</span></div>`
  } else {
    // Great-grandchildren: lighter strip + first name
    return `<div style="position:relative;height:88px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:${accent}22;border-radius:5px"><span style="position:absolute;transform:rotate(-90deg);white-space:nowrap;font-size:0.48rem;color:${accent}bb;max-width:80px;overflow:hidden;text-overflow:ellipsis">${first}</span></div>`
  }
}

function toggleCompactMode() {
  compactMode.value = !compactMode.value
  if (!chartInstance || !cardHtml) return
  const container = chartContainer.value
  if (compactMode.value) {
    // node_separation = center-to-center distance = card_width + gap (not just gap)
    cardHtml
      .setCardDim({ width: 32, height: 90 })
      .setStyle('rect')
      .setCardInnerHtmlCreator(compactInnerHtml)
    chartInstance.setCardXSpacing(36).updateTree({ initial: true })  // 32px card + 4px gap
    container?.classList.add('compact-mode')
  } else {
    cardHtml
      .setCardDim({ width: 210, height: 90, text_x: 80, text_y: 20, img_x: 6, img_y: 6, img_w: 62, img_h: 62 })
      .setStyle('imageCircleRect')
      .setCardInnerHtmlCreator(undefined)
    chartInstance.setCardXSpacing(250).updateTree({ initial: true })  // restore library default
    container?.classList.remove('compact-mode')
  }
}

// ── Radial view ──────────────────────────────────────────────
const radialMode     = ref(false)
const radialCenterId = ref(null)   // null = use root
const radialVB       = ref({ x: -550, y: -550, w: 1100, h: 1100 })
let   radialDrag     = null

const radialData = computed(() => {
  if (!radialMode.value || !props.nodes.length) return { nodes: [], links: [] }

  const centerId = String(radialCenterId.value || props.rootPersonId || props.defaultMainPersonId || props.nodes[0]?.id)
  const nodeMap = {}
  props.nodes.forEach(n => { nodeMap[String(n.id)] = n })

  function subtreeSize(id, seen = new Set()) {
    if (seen.has(id)) return 0
    seen.add(id)
    const children = (nodeMap[id]?.rels?.children || []).map(c => String(c))
    return 1 + children.reduce((s, c) => s + subtreeSize(c, seen), 0)
  }

  const positions = {}
  const links = []
  const visited = new Set()

  function layout(id, level, a0, a1, relType = 'child') {
    if (visited.has(id)) return
    visited.add(id)
    const angle = (a0 + a1) / 2
    // Dynamic radius: ensures at least 52px arc-length per node so rings spread out
    let r
    if (level === 0) r = 0
    else if (relType === 'spouse') r = 90
    else if (relType === 'parent') r = 130
    else {
      const arcSpan = Math.max(a1 - a0, 0.01)
      const minR = Math.ceil(52 / arcSpan)  // push ring out until each node has 52px of arc
      r = Math.max(level * 145, minR, 130)
    }
    positions[id] = { x: r * Math.cos(angle - Math.PI / 2), y: r * Math.sin(angle - Math.PI / 2), level, relType, angle }

    if (level === 0) {
      // Center node: show parents, spouses, and children in first ring
      const parents  = (nodeMap[id]?.rels?.parents  || []).map(p => String(p)).filter(p => !visited.has(p))
      const spouses  = (nodeMap[id]?.rels?.spouses  || []).map(s => String(s)).filter(s => !visited.has(s))
      const children = (nodeMap[id]?.rels?.children || []).map(c => String(c)).filter(c => !visited.has(c))
      const firstRing = [
        ...parents.map(p => ({ id: p, type: 'parent', size: 1 })),
        ...spouses.map(s => ({ id: s, type: 'spouse', size: 1 })),
        ...children.map(c => ({ id: c, type: 'child', size: subtreeSize(c, new Set(visited)) })),
      ]
      const total = firstRing.reduce((s, x) => s + x.size, 0) || 1
      let cur = a0
      firstRing.forEach(({ id: nid, type, size }) => {
        const arc = (a1 - a0) * size / total
        links.push({ key: `${id}-${nid}`, from: id, to: nid, type })
        layout(nid, 1, cur, cur + arc, type)
        cur += arc
      })
    } else if (relType !== 'spouse' && relType !== 'parent') {
      // Non-spouse nodes: recurse into their children only
      const children = (nodeMap[id]?.rels?.children || []).map(c => String(c)).filter(c => !visited.has(c))
      if (!children.length) return
      const sizes = children.map(c => subtreeSize(c, new Set(visited)))
      const total  = sizes.reduce((a, b) => a + b, 0) || 1
      let cur = a0
      children.forEach((childId, i) => {
        const arc = (a1 - a0) * sizes[i] / total
        links.push({ key: `${id}-${childId}`, from: id, to: childId, type: 'child' })
        layout(childId, level + 1, cur, cur + arc, 'child')
        cur += arc
      })
    }
  }

  layout(centerId, 0, 0, 2 * Math.PI)

  const nodes = Object.keys(positions).map(id => {
    const n   = nodeMap[id]
    const pos = positions[id]
    const isRoot = id === centerId
    const nodeR  = isRoot ? 28 : 20

    // Label placed radially outward from center so it doesn't overlap neighbours
    const angle  = pos.angle || 0
    const lDist  = nodeR + 11
    const lx = isRoot ? 0 : Math.cos(angle - Math.PI / 2) * lDist
    const ly = isRoot ? nodeR + 9 : Math.sin(angle - Math.PI / 2) * lDist
    const labelBaseline = ly > 3 ? 'hanging' : ly < -3 ? 'auto' : 'central'

    return {
      id,
      x: pos.x, y: pos.y, level: pos.level, relType: pos.relType,
      nodeR, labelX: lx, labelY: ly, labelBaseline,
      gender:    n?.data?.gender,
      avatar:    n?.data?.avatar || null,
      firstName: n?.data?.['first name'] || '',
      lastName:  n?.data?.['last name']  || '',
      fullName:  `${n?.data?.['first name'] || ''} ${n?.data?.['last name'] || ''}`.trim(),
      isRoot,
    }
  })

  const linkLines = links
    .filter(l => positions[l.from] && positions[l.to])
    .map(l => ({
      key:  l.key,  type: l.type,
      x1: positions[l.from].x, y1: positions[l.from].y,
      x2: positions[l.to].x,   y2: positions[l.to].y,
    }))

  return { nodes, links: linkLines }
})

function radialNodeColor(gender) {
  return gender === 'M' ? '#93c5fd' : gender === 'F' ? '#c4b5fd' : '#e2e8f0'
}
function radialNodeStroke(gender) {
  return gender === 'M' ? '#3b82f6' : gender === 'F' ? '#8b5cf6' : '#94a3b8'
}

function onRadialWheel(e) {
  e.preventDefault()
  const factor = e.deltaY > 0 ? 1.12 : 0.89
  const vb = radialVB.value
  const newW = Math.min(3000, Math.max(300, vb.w * factor))
  const newH = Math.min(3000, Math.max(300, vb.h * factor))
  radialVB.value = { x: vb.x - (newW - vb.w) / 2, y: vb.y - (newH - vb.h) / 2, w: newW, h: newH }
}

// Drag only starts from the SVG background — nodes use @pointerdown.stop to block this
function onRadialBgPointerDown(e) {
  radialDrag = { px: e.clientX, py: e.clientY, vb: { ...radialVB.value }, moved: false }
}
function onRadialPointerMove(e) {
  if (!radialDrag) return
  const dx = e.clientX - radialDrag.px
  const dy = e.clientY - radialDrag.py
  if (!radialDrag.moved && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) radialDrag.moved = true
  const vb  = radialDrag.vb
  const rect = e.currentTarget.getBoundingClientRect()
  radialVB.value = {
    x: vb.x - dx * vb.w / rect.width,
    y: vb.y - dy * vb.h / rect.height,
    w: vb.w, h: vb.h,
  }
}
function onRadialPointerUp() { radialDrag = null }

function onRadialNodeClick(id) {
  // Ignore if this ended a drag
  if (radialDrag?.moved) return
  // Single click: make this person the center + open side panel
  radialCenterId.value = String(id)
  radialVB.value = { x: -550, y: -550, w: 1100, h: 1100 }
  const node = props.nodes.find(n => String(n.id) === String(id))
  if (!node) return
  selectedPerson.value = { id: node.id, ...node.data }
  addRelType.value = null
}

function resetRadialToRoot() {
  radialCenterId.value = null
  radialVB.value = { x: -550, y: -550, w: 1100, h: 1100 }
}

function toggleRadialMode() {
  radialMode.value = !radialMode.value
  if (!radialMode.value) {
    // Returning to tree view — reset compact state if needed and refresh chart
    if (compactMode.value) {
      compactMode.value = false
      if (cardHtml && chartInstance) {
        cardHtml
          .setCardDim({ width: 210, height: 90, text_x: 80, text_y: 20, img_x: 6, img_y: 6, img_w: 62, img_h: 62 })
          .setStyle('imageCircleRect')
          .setCardInnerHtmlCreator(undefined)
        chartInstance.setCardXSpacing(250)
        chartContainer.value?.classList.remove('compact-mode')
      }
    }
    // Chart was kept alive via v-show — just re-trigger layout
    if (chartInstance) chartInstance.updateTree({ initial: true })
  }
  radialCenterId.value = null
  radialVB.value = { x: -550, y: -550, w: 1100, h: 1100 }
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

/* ── Connector lines — visible gray ── */
#FamilyChart .link {
  stroke: #9eb8d4 !important;
  stroke-width: 1.8px !important;
  opacity: 1 !important;
}
#FamilyChart .link.f3-path-to-main {
  stroke: #2d6be4 !important;
  stroke-width: 3px !important;
}

/* ── Add-relative "+" buttons — blue on light bg ── */
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

/* ── Card hover shadow — subtle blue ── */
#FamilyChart .card-main .card-inner,
#FamilyChart .card:hover .card-inner {
  box-shadow: 0 4px 18px 0 rgba(0, 50, 150, 0.18) !important;
}

/* ── Photo cards: flex row — image 33% left, name 67% right ── */
#FamilyChart .card-image-circle {
  border-radius: 10px !important;
  overflow: hidden !important;
  display: flex !important;
  align-items: stretch !important;
  padding: 0 !important;
}
#FamilyChart .card-image-circle img {
  /* override inline width/height from getCardImageStyle */
  width: 33% !important;
  height: 100% !important;
  min-width: 33% !important;
  flex-shrink: 0 !important;
  border-radius: 0 !important;
  object-fit: cover !important;
  /* clear the library's absolute-style left/top */
  position: static !important;
  left: unset !important;
  top: unset !important;
  border: none !important;
  box-shadow: none !important;
}
#FamilyChart .card-image-circle .card-label {
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  align-items: center !important;
  text-align: center !important;
  background: none !important;
  color: inherit !important;
  padding: 0 8px !important;
  position: static !important;
  transform: none !important;
  bottom: auto !important;
  left: auto !important;
  min-height: unset !important;
  white-space: normal !important;
  font-size: 0.78rem !important;
  line-height: 1.35 !important;
  direction: rtl !important;
}
#FamilyChart .card-image-circle .card-label div {
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  max-width: 100% !important;
}

/* ── Compact mode ── */
#FamilyChart.compact-mode .card-inner {
  border-radius: 8px !important;
  overflow: hidden !important;
  padding: 0 !important;
}
#FamilyChart.compact-mode .card:hover .card-inner {
  box-shadow: 0 4px 20px rgba(0,50,150,0.22) !important;
  position: relative !important;
  z-index: 10 !important;
}
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

/* ── Search ── */
.search-wrap { position: relative; }
.search-box { display: flex; align-items: center; position: relative; }
.search-input {
  padding: 0.35rem 2rem 0.35rem 0.75rem;
  border: 1.5px solid #d1dce8; border-radius: 8px;
  font-size: 0.82rem; font-family: 'Rubik', sans-serif;
  width: 160px; background: white; color: #1a3a6b;
  transition: border-color 0.2s, width 0.2s;
}
.search-input:focus { outline: none; border-color: #2d6be4; width: 210px; }
.search-icon { position: absolute; right: 0.55rem; color: #94a3b8; font-size: 0.88rem; pointer-events: none; }
.search-dropdown {
  position: absolute; top: calc(100% + 5px); right: 0;
  background: white; border: 1px solid #e0eaf8; border-radius: 10px;
  box-shadow: 0 6px 20px rgba(0,50,150,0.13); min-width: 210px;
  z-index: 200; overflow: hidden;
}
.search-result {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.48rem 0.75rem; cursor: pointer; transition: background 0.12s;
  font-size: 0.84rem; color: #1a3a6b; direction: rtl; text-align: right;
}
.search-result:hover { background: #f0f6ff; }
.search-result-avatar {
  width: 28px; height: 28px; border-radius: 50%; background: #e8f0fe;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  font-size: 0.7rem; font-weight: 700; color: #2d6be4; overflow: hidden;
}
.search-result-avatar img { width: 100%; height: 100%; object-fit: cover; }

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

@media (max-width: 640px) {
  .panel-slide-enter-from, .panel-slide-leave-to { transform: translateY(100%); }
  .panel-slide-enter-active, .panel-slide-leave-active { transition: transform 0.28s ease; }
}

/* ── Radial view ── */
.radial-wrap {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: #f0f6ff;
  cursor: grab;
}
.radial-wrap:active { cursor: grabbing; }
.radial-svg {
  width: 100%;
  height: 100%;
  user-select: none;
  touch-action: none;
}
.radial-center-label {
  position: absolute;
  top: 0.6rem;
  right: 0.75rem;
  z-index: 5;
}
.radial-back-btn {
  background: rgba(255,255,255,0.9);
  border: 1px solid #b0c8e4;
  border-radius: 20px;
  padding: 0.25rem 0.9rem;
  font-size: 0.8rem;
  color: #1a3a6b;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
}
.radial-back-btn:hover { background: #e8f0ff; }
.radial-node { cursor: pointer; }
.radial-node circle { transition: r 0.15s, stroke-width 0.15s; }
.radial-node:hover circle { stroke-width: 3 !important; filter: brightness(1.08); }
.radial-node:hover text { font-weight: 600; }
.radial-hint {
  position: absolute;
  bottom: 0.75rem;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.78rem;
  color: #8a9ab5;
  background: rgba(255,255,255,0.85);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  pointer-events: none;
  white-space: nowrap;
}

/* ── Mobile ── */
@media (max-width: 640px) {
  .tree-header {
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
  }
  h1 { font-size: 0.95rem; }
  .tree-controls {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 2px;
    scrollbar-width: none;
  }
  .tree-controls::-webkit-scrollbar { display: none; }
  .ctrl-btn { padding: 0.3rem 0.6rem; font-size: 0.78rem; }
  .ctrl-btn-primary { padding: 0.3rem 0.65rem; font-size: 0.78rem; }
  .search-input { width: 120px; }
  .search-input:focus { width: 150px; }
  .side-panel {
    width: 100% !important;
    height: 60vh !important;
    top: auto !important;
    bottom: 0 !important;
    right: 0 !important;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 24px rgba(0,50,150,.15);
  }
  .radial-hint { font-size: 0.72rem; }
}
</style>
