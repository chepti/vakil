<template>
  <AppLayout title="עץ המשפחה">
    <div class="tree-page" dir="rtl">

      <!-- Header -->
      <div class="tree-header">
        <div class="tree-title">
          <h1>🌳 עץ משפחת ואקיל</h1>
          <span class="people-count">{{ localNodes.length }} דמויות</span>
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

          <!-- Tree-only controls (hidden in radial view, where they have no effect) -->
          <template v-if="!radialMode">
            <button class="ctrl-btn" @click="centerTree" title="התאם את העץ לגודל המסך">⊕ מרכז</button>
            <button class="ctrl-btn" @click="goToRoot" title="הצג מהאב הקדמון">🌳 שורש</button>
            <button class="ctrl-btn" :class="{ active: hideMode }" @click="toggleHideMode"
              :title="hideMode ? 'סיום — חזרה למצב רגיל' : 'קיפול ענף — לחצו ואז בחרו דמות כדי לקפל את צאצאיה'">
              {{ hideMode ? '✓ סיום קיפול' : '👁 קיפול ענף' }}
            </button>
            <button v-if="collapsedIds.size" class="ctrl-btn" @click="expandAll" title="פרוס מחדש את כל הענפים המקופלים">
              ⊕ פרוס הכל ({{ collapsedIds.size }})
            </button>
            <div class="depth-ctrl">
              <button class="ctrl-btn icon-btn" @click="changeDepth(-1)" title="פחות דורות" :disabled="depth <= 1">−</button>
              <span class="depth-label">{{ depth }} דורות</span>
              <button class="ctrl-btn icon-btn" @click="changeDepth(1)" title="יותר דורות" :disabled="depth >= 7">+</button>
            </div>
            <button class="ctrl-btn" :class="{ active: compactMode }" @click="toggleCompactMode" title="פסים אנכיים — מכווץ את הרוחב, רחף להרחיב">
              {{ compactMode ? '⊠ מצב רגיל' : '⊟ קומפקטי' }}
            </button>
          </template>
          <button class="ctrl-btn" :class="{ active: !radialMode }" @click="toggleRadialMode" title="תצוגה עגולה / עץ רגיל">
            {{ radialMode ? '🌳 עץ רגיל' : '◎ עגול' }}
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="localNodes.length === 0" class="empty-tree">
        <div class="empty-icon">🌱</div>
        <h2>העץ ריק עדיין</h2>
        <p>התחל בהוספת הדמות הראשונה</p>
        <Link href="/onboarding" class="btn-start">התחל עכשיו</Link>
      </div>

      <!-- Tree container — v-show keeps the chart instance alive when toggling radial -->
      <div v-show="localNodes.length > 0 && !radialMode" class="tree-wrap">
        <div v-if="hideMode" class="fold-hint">👁 לחצו על דמות כדי לקפל / לפתוח את ענף הצאצאים שלה</div>
        <div ref="chartContainer" id="FamilyChart" class="f3"></div>
      </div>

      <!-- Radial view -->
      <div v-show="localNodes.length > 0 && radialMode" class="radial-wrap" :class="{ 'panel-open': selectedPerson }">
        <!-- breadcrumb: who is at center -->
        <div class="radial-center-label" v-if="radialCenterId && radialCenterId !== String(props.defaultMainPersonId || props.rootPersonId || props.nodes[0]?.id)">
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
          <!-- Parents/spouse sector — soft amber wedge behind the relatives -->
          <path
            v-if="radialData.sectorPath"
            :d="radialData.sectorPath"
            fill="rgba(251,191,36,0.12)"
            stroke="rgba(245,158,11,0.35)"
            stroke-width="1"
          />
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
            @mouseenter="node.spouse && (hoveredNodeId = node.id)"
            @mouseleave="hoveredNodeId === node.id && (hoveredNodeId = null)"
          >
            <!-- Married-in spouse: drawn BEHIND the person as a semi-transparent photo;
                 on hover it slides out beside them at full opacity with a name -->
            <g
              v-if="node.spouse"
              class="radial-mate" :class="{ revealed: hoveredNodeId === node.id }"
            >
              <clipPath :id="`mclip-${node.id}`"><circle :r="node.nodeR" cx="0" cy="0"/></clipPath>
              <circle :r="node.nodeR" :fill="radialNodeColor(node.spouse.gender)"
                      :stroke="radialNodeStroke(node.spouse.gender)" stroke-width="1.5"/>
              <image v-if="node.spouse.avatar" :href="node.spouse.avatar"
                     :x="-node.nodeR" :y="-node.nodeR" :width="node.nodeR * 2" :height="node.nodeR * 2"
                     :clip-path="`url(#mclip-${node.id})`" preserveAspectRatio="xMidYMid slice"/>
              <text v-else text-anchor="middle" dominant-baseline="central" font-size="9"
                    fill="#1a3a6b" font-family="Rubik, sans-serif">{{ node.spouse.firstName.slice(0, 4) }}</text>
              <text class="mate-name" :y="node.nodeR + 9" text-anchor="middle" dominant-baseline="hanging"
                    font-family="Rubik, sans-serif" font-size="8" fill="#1a3a6b"
                    stroke="rgba(240,246,255,0.85)" stroke-width="3" paint-order="stroke"
              >{{ node.spouse.firstName }}</text>
            </g>
            <!-- Relationship ring indicators (not for the center spouse — it sits flush with the center) -->
            <circle
              v-if="node.relType === 'spouse' && !node.isRoot && !node.centerSpouse"
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
              :stroke-width="node.isRoot || node.centerSpouse ? 3 : 1.5"
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
              :font-size="node.isRoot || node.centerSpouse ? 11 : 9"
              fill="#1a3a6b" font-family="Rubik, sans-serif" font-weight="500"
            >{{ node.firstName.slice(0, 4) }}</text>
            <!-- Name on a bottom half-circle hugging the node — white halo for readability -->
            <path :id="`lpath-${node.id}`" :d="node.labelArc" fill="none" />
            <text
              font-family="Rubik, sans-serif"
              dominant-baseline="hanging"
              :font-size="node.isRoot || node.centerSpouse ? 11 : 8.5"
              :font-weight="node.isRoot || node.centerSpouse ? '700' : '500'"
              fill="#1a3a6b"
              stroke="rgba(240,246,255,0.9)" stroke-width="3" paint-order="stroke"
            >
              <textPath :href="`#lpath-${node.id}`" startOffset="50%" text-anchor="middle">{{ node.isRoot ? node.fullName : node.firstName }}</textPath>
            </text>
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
              <input v-model="addRelForm.birth_date_gregorian" type="date" class="rel-input" @change="addRelForm.birth_date_hebrew = gregorianToHebrew(addRelForm.birth_date_gregorian) || addRelForm.birth_date_hebrew" />
              <input v-model="addRelForm.birth_date_hebrew" type="text" placeholder='תאריך לידה עברי (כ"ה תשרי תשפ"ה)' class="rel-input" @change="addRelForm.birth_date_gregorian = hebrewToGregorian(addRelForm.birth_date_hebrew) || addRelForm.birth_date_gregorian" />
              <template v-if="addRelType === 'spouse'">
                <input v-model="addRelForm.marriage_date_gregorian" type="date" class="rel-input" @change="addRelForm.marriage_date_hebrew = gregorianToHebrew(addRelForm.marriage_date_gregorian) || addRelForm.marriage_date_hebrew" />
                <input v-model="addRelForm.marriage_date_hebrew" type="text" placeholder='תאריך נישואין עברי (כ"ב אייר תש"פ)' class="rel-input" @change="addRelForm.marriage_date_gregorian = hebrewToGregorian(addRelForm.marriage_date_hebrew) || addRelForm.marriage_date_gregorian" />
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

          <!-- ─── Edit details — compact, placeholder-based, at bottom ─── -->
          <div class="ef-form">
            <div class="ef-pair">
              <input type="date" v-model="ef.birth_date_gregorian" class="ef-input" title="תאריך לידה (לועזי)" @change="autoFillHebrew('birth')" />
              <input type="text" v-model="ef.birth_date_hebrew" class="ef-input" placeholder='🎂 לידה עברי' @change="autoFillGregorian('birth')" />
            </div>
            <input v-if="selectedPerson.gender === 'F'" type="text" v-model="ef.maiden_name" class="ef-input" placeholder="👰 שם נעורים" />
            <input type="text" v-model="ef.occupation" class="ef-input" placeholder="💼 עיסוק" />
            <input type="text" v-model="ef.city" class="ef-input" placeholder="📍 כתובת" />
            <input type="email" v-model="ef.email" class="ef-input" dir="ltr" placeholder="✉️ מייל" />
            <input type="tel" v-model="ef.phone" class="ef-input" dir="ltr" placeholder="📞 טלפון" />
            <textarea v-model="ef.bio" class="ef-input ef-textarea" rows="2" placeholder="📝 מידע נוסף"></textarea>

            <template v-if="Object.keys(ef.marriages).length">
              <div v-for="(m, spouseId) in ef.marriages" :key="spouseId" class="ef-marriage" :class="{ 'ef-marriage-former': m.is_former }">
                <div class="ef-marriage-label">
                  {{ m.is_former ? '💔' : '💍' }} {{ m.is_former ? 'לשעבר: ' : '' }}{{ getSpouseName(spouseId) }}
                </div>
                <div class="ef-pair">
                  <input type="date" v-model="m.date" class="ef-input" title="תאריך נישואין (לועזי)" @change="autoFillHebrew('marriage', spouseId)" />
                  <input type="text" v-model="m.date_he" class="ef-input" placeholder='נישואין עברי' @change="autoFillGregorian('marriage', spouseId)" />
                </div>
                <label class="ef-former-toggle">
                  <input type="checkbox" v-model="m.is_former" />
                  <span>בן/בת זוג לשעבר</span>
                </label>
              </div>
            </template>

            <button class="ef-save-btn" @click="savePerson" :disabled="efSaving">
              {{ efSaving ? 'שומר...' : 'שמור שינויים' }}
            </button>

            <!-- נפטר — ממש בתחתית, פחות בולט -->
            <div class="ef-deceased-area">
              <label class="ef-checkbox-label">
                <input type="checkbox" v-model="ef.is_deceased" />
                <span>נפטר/ה</span>
              </label>
              <template v-if="ef.is_deceased">
                <div class="ef-pair">
                  <input type="date" v-model="ef.death_date_gregorian" class="ef-input" title="תאריך פטירה (לועזי)" @change="autoFillHebrew('death')" />
                  <input type="text" v-model="ef.death_date_hebrew" class="ef-input" placeholder='🕯 פטירה עברי' @change="autoFillGregorian('death')" />
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { createChart } from 'family-chart'
import 'family-chart/styles/family-chart.css'
import { gregorianToHebrew, hebrewToGregorian } from '@/utils/hebrewDate'

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
const localNodes       = ref(props.nodes)  // mutable copy — updated when chart saves/deletes
const depth            = ref(4)
const showSiblings     = ref(true)
const compactMode      = ref(false)
const hideMode         = ref(false)         // "fold branch" mode — click a card to collapse its descendants
const collapsedIds     = ref(new Set())     // ids whose descendant branch is hidden
let chartInstance      = null
let cardHtml           = null

// ─── Search ────────────────────────────────────────────────────
const searchQuery = ref('')
const searchOpen  = ref(false)

const searchResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return []
  return localNodes.value
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
  const node = localNodes.value.find(n => n.id === id)
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

// ─── Edit-details form ────────────────────────────────────────
const ef       = ref({ maiden_name: '', birth_date_gregorian: '', birth_date_hebrew: '', is_deceased: false, death_date_gregorian: '', death_date_hebrew: '', occupation: '', city: '', email: '', phone: '', bio: '', marriages: {} })
const efSaving = ref(false)

watch(selectedPerson, (person) => {
  if (!person) return
  const marriages = {}
  for (const [sid, mData] of Object.entries(person.marriages || {})) {
    marriages[sid] = { date: mData?.date ?? '', date_he: mData?.date_he ?? '', is_former: !!mData?.is_former }
  }
  ef.value = {
    maiden_name:          person.maiden_name   || '',
    birth_date_gregorian: person.birthday      || '',
    birth_date_hebrew:    person.birthday_he   || '',
    is_deceased:          !!person.is_deceased,
    death_date_gregorian: person.death_date    || '',
    death_date_hebrew:    person.death_date_he || '',
    occupation:           person.occupation    || '',
    city:                 person.city          || '',
    email:                person.email         || '',
    phone:                person.phone         || '',
    bio:                  person.bio           || '',
    marriages,
  }
}, { immediate: true })

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
    const selfNode  = localNodes.value.find(n => n.id === selfId)
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

  let freshNodes
  try {
    freshNodes = await apiPost('/api/family-tree/person', datum)
  } catch (err) {
    console.error('add-relative save failed:', err)
    alert('שגיאה בהוספה')
    addRelSaving.value = false
    return
  }
  // השמירה הצליחה — מכאן שגיאת רינדור לא נחשבת ככשל שמירה
  localNodes.value = freshNodes
  refreshChart(freshNodes)
  addRelType.value = null
  addRelSaving.value = false
}

// עדכון הגרף בנפרד מהשמירה — כשל ברינדור לא אמור להיראות ככשל שמירה
function refreshChart(freshNodes) {
  try {
    chartInstance?.updateData(chartFeed(freshNodes)).updateTree({ tree_position: 'inherit' })
  } catch (err) {
    console.error('chart refresh failed (data already saved):', err)
  }
}

onMounted(() => {
  if (radialMode.value) fitRadialView()   // radial is the default view — fit on entry
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

// Fresh copies of every rels array — family-chart mutates these in place,
// so sharing references would corrupt localNodes/props.nodes across updates.
// Ordering is handled by setSortChildrenFunction inside initChart().
function chartFeed(nodes) {
  return nodes.map(n => ({
    ...n,
    rels: {
      parents:  [...(n.rels?.parents  || [])],
      spouses:  [...(n.rels?.spouses  || [])],
      children: [...(n.rels?.children || [])],
    },
  }))
}

// Sort children by birthday descending → youngest leftmost, oldest rightmost.
// In RTL display, rightmost = "first read" = oldest. Nulls go to the left (last).
function sortByBirthday(a, b) {
  const aDate = a.data?.birthday || ''
  const bDate = b.data?.birthday || ''
  if (!aDate && !bDate) return 0
  if (!aDate) return 1
  if (!bDate) return -1
  return aDate > bDate ? -1 : aDate < bDate ? 1 : 0
}

function initChart() {
  const cont = chartContainer.value
  chartInstance = createChart(cont, chartFeed(props.nodes))
  chartInstance.setSortChildrenFunction(sortByBirthday)

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
      const id = String(d.data.id)
      // Fold mode: clicking a card collapses/expands its descendant branch
      if (hideMode.value) {
        const s = new Set(collapsedIds.value)
        if (s.has(id)) s.delete(id)
        else s.add(id)
        collapsedIds.value = s
        chartInstance.updateTree({})
        return
      }
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
        localNodes.value = freshNodes
        chartInstance.updateData(chartFeed(freshNodes)).updateTree({ tree_position: 'inherit' })
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
        localNodes.value = freshNodes
        chartInstance.updateData(chartFeed(freshNodes)).updateTree({ tree_position: 'inherit' })
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
    // Disable empty placeholder spouse for single-parent children. That feature
    // (createRelsToAdd) mutates the data in place — pushing generated spouse ids
    // into each child's rels.parents — which corrupts our source nodes and then
    // throws "child has more than 1 parent" on a later updateTree (e.g. radial→tree).
    .setSingleParentEmptyCard(false)
    // Fold branches: prune the children of any collapsed node (progeny side only)
    .setModifyTreeHierarchy((root, is_ancestry) => {
      if (is_ancestry || collapsedIds.value.size === 0) return
      root.descendants().forEach(n => {
        if (n.children && collapsedIds.value.has(String(n.data.id))) n.children = null
      })
    })
    .updateTree({ initial: true })
}

// Re-fit the tree to the container. MUST run only when the container is visible —
// family-chart's treeFit divides by getBoundingClientRect(), so a display:none
// container yields scale(0)/NaN and kills pan+zoom. nextTick + rAF guarantees the
// v-show has applied display:block and layout has flushed before we measure.
function fitTreeView() {
  if (!chartInstance) return
  nextTick(() => requestAnimationFrame(() => chartInstance.updateTree({ initial: true })))
}

function centerTree() {
  fitTreeView()
}

function goToRoot() {
  if (chartInstance && props.rootPersonId) {
    chartInstance.updateMainId(props.rootPersonId)
    selectedPerson.value = null
    fitTreeView()
  }
}

// Fold-branch mode: toggle the eye cursor; clicking a card then collapses its branch
function toggleHideMode() {
  hideMode.value = !hideMode.value
  chartContainer.value?.classList.toggle('hide-mode', hideMode.value)
}

function expandAll() {
  collapsedIds.value = new Set()
  if (chartInstance) chartInstance.updateTree({})
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
    return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:32px;height:90px;box-sizing:border-box;gap:2px;overflow:hidden">${photo}<div style="font-size:0.44rem;text-align:center;color:#1e3a5f;width:32px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;line-height:1">${first}</div></div>`
  } else if (depth === 2) {
    // Grandchildren: colored strip + rotated first name
    return `<div style="position:relative;width:32px;height:90px;box-sizing:border-box;overflow:hidden;display:flex;align-items:center;justify-content:center;background:${base};border:1px solid ${accent}55;border-radius:5px"><span style="position:absolute;transform:rotate(-90deg);white-space:nowrap;font-size:0.54rem;font-weight:600;color:${accent};max-width:84px;overflow:hidden;text-overflow:ellipsis">${first || name}</span></div>`
  } else {
    // Great-grandchildren: lighter strip + first name
    return `<div style="position:relative;width:32px;height:90px;box-sizing:border-box;overflow:hidden;display:flex;align-items:center;justify-content:center;background:${accent}22;border-radius:5px"><span style="position:absolute;transform:rotate(-90deg);white-space:nowrap;font-size:0.48rem;font-weight:500;color:${accent};max-width:84px;overflow:hidden;text-overflow:ellipsis">${first}</span></div>`
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
const radialMode     = ref(true)   // default: radial on entry
const radialCenterId = ref(null)   // null = use defaultMainPersonId
const radialVB       = ref({ x: -550, y: -550, w: 1100, h: 1100 })
const hoveredNodeId  = ref(null)   // node whose married-in spouse is revealed on hover

// Pointer tracking — supports both single-finger drag and two-finger pinch zoom
const radialActivePtrs = new Map()   // pointerId → {x, y}
let   radialDrag       = null        // single-pointer drag state
let   radialPinch      = null        // two-pointer pinch state

function _pinchDist() {
  const pts = [...radialActivePtrs.values()]
  return Math.hypot(pts[1].x - pts[0].x, pts[1].y - pts[0].y)
}

const radialData = computed(() => {
  if (!radialMode.value || !localNodes.value.length) return { nodes: [], links: [] }

  const centerId = String(radialCenterId.value || props.defaultMainPersonId || props.rootPersonId || localNodes.value[0]?.id)
  const nodeMap = {}
  localNodes.value.forEach(n => { nodeMap[String(n.id)] = n })

  // Number of leaf (childless) descendants — drives how much angular room a subtree needs
  function leafCount(id, seen) {
    if (seen.has(id)) return 0
    seen.add(id)
    const kids = (nodeMap[id]?.rels?.children || []).map(c => String(c)).filter(c => !seen.has(c))
    if (!kids.length) return 1
    return kids.reduce((s, c) => s + leafCount(c, seen), 0)
  }

  // Sort children: oldest first → placed on the RIGHT in the radial fan (RTL-friendly).
  // Nodes without a birthday go to the end (leftmost).
  const sortedChildren = (id) => {
    const kids = (nodeMap[id]?.rels?.children || []).map(c => String(c)).filter(c => !visited.has(c))
    return kids.sort((aId, bId) => {
      const a = nodeMap[aId]?.data?.birthday || ''
      const b = nodeMap[bId]?.data?.birthday || ''
      if (!a && !b) return 0
      if (!a) return 1
      if (!b) return -1
      return a < b ? -1 : a > b ? 1 : 0   // ascending: oldest (smaller date) → index 0 → rightmost
    })
  }

  const positions = {}
  const links = []
  const visited = new Set()
  const NODE_D  = 44        // effective node diameter + gap
  let   relSector = 0       // angular width of the parents wedge (filled at level 0)
  const REL_R_PAR = 145     // parents radius
  const R_LVL     = 130     // nominal radius per generation
  const LEAF_ARC  = NODE_D / 620   // angular budget per descendant leaf

  // Minimum angular spacing for a node at the given generation (so siblings never touch)
  const minStepAt = (lvl) => NODE_D / Math.max(R_LVL, lvl * R_LVL)

  // Lay out a parent's children: equal fair minimum per child + extra for big subtrees,
  // centered on `arcMid`, using only as much of `arcAvail` as needed (compact when sparse).
  function fanChildren(parentId, level, arcMid, arcAvail) {
    const kids = sortedChildren(parentId)
    if (!kids.length) return
    const needs = kids.map(c => Math.max(minStepAt(level + 1), leafCount(c, new Set(visited)) * LEAF_ARC))
    const total = needs.reduce((a, b) => a + b, 0)
    const used  = Math.min(total, arcAvail)
    const scale = total > 0 ? used / total : 1
    let cur = arcMid - used / 2
    kids.forEach((c, i) => {
      const arc = needs[i] * scale
      links.push({ key: `${parentId}-${c}`, from: parentId, to: c, type: 'child' })
      layout(c, level + 1, cur, cur + arc, 'child')
      cur += arc
    })
  }

  function layout(id, level, a0, a1, relType = 'child') {
    if (visited.has(id)) return
    visited.add(id)
    const angle = (a0 + a1) / 2
    // First-pass radius — child nodes will be corrected in second pass
    const r = level === 0 ? 0 : relType === 'spouse' ? 90 : relType === 'parent' ? 130 : level * 160
    positions[id] = { x: r * Math.cos(angle - Math.PI / 2), y: r * Math.sin(angle - Math.PI / 2), level, relType, angle, arcSpan: a1 - a0 }

    if (level === 0) {
      const marriages = nodeMap[id]?.data?.marriages || {}
      const parents  = (nodeMap[id]?.rels?.parents  || []).map(p => String(p)).filter(p => !visited.has(p))
      // Former spouses are not shown beside the center — only current partners read as "together"
      const spouses  = (nodeMap[id]?.rels?.spouses  || []).map(s => String(s))
        .filter(s => !visited.has(s) && !marriages[s]?.is_former)

      // ── Reserved top wedge for PARENTS only (angle 0 = straight up) ──
      if (parents.length) {
        relSector = Math.min(Math.max(parents.length * (NODE_D + 14) / REL_R_PAR, 0.55), Math.PI * 0.85)
      }
      {
        const n = parents.length
        const step  = n > 1 ? relSector / n : 0
        const start = -((n - 1) / 2) * step
        parents.forEach((pid, i) => {
          const a = start + i * step
          visited.add(pid)
          positions[pid] = {
            x: REL_R_PAR * Math.cos(a - Math.PI / 2), y: REL_R_PAR * Math.sin(a - Math.PI / 2),
            level: 1, relType: 'parent', angle: a, arcSpan: step || relSector,
          }
          links.push({ key: `${id}-${pid}`, from: id, to: pid, type: 'parent' })
        })
      }

      // ── Center spouse(s): right beside the center, slight overlap (children come from both) ──
      spouses.forEach((sid, i) => {
        visited.add(sid)
        const dir = i % 2 === 0 ? 1 : -1
        const k   = Math.floor(i / 2) + 1
        positions[sid] = {
          x: dir * 46 * k, y: 0, level: 0, relType: 'spouse',
          angle: dir > 0 ? Math.PI / 2 : -Math.PI / 2, arcSpan: 0, centerSpouse: true,
        }
        links.push({ key: `${id}-${sid}`, from: id, to: sid, type: 'spouse' })
      })

      // ── Children: fan out centered on the BOTTOM (away from the parents wedge) ──
      // Uses only the arc it needs, so small families stay compact instead of wrapping around.
      fanChildren(id, 0, Math.PI, 2 * Math.PI - relSector)
    } else if (relType !== 'spouse' && relType !== 'parent') {
      // Grandchildren and deeper: centered on this node's own direction, within its allotted arc.
      fanChildren(id, level, angle, a1 - a0)
    }
  }

  layout(centerId, 0, 0, 2 * Math.PI)

  // ── Second pass: multi-row band layout per generation ──────────────────
  // Group child nodes by level
  const childByLevel = {}
  Object.keys(positions).forEach(id => {
    const pos = positions[id]
    if (pos.level === 0 || pos.relType === 'spouse' || pos.relType === 'parent') return
    if (!childByLevel[pos.level]) childByLevel[pos.level] = []
    childByLevel[pos.level].push(id)
  })

  // Canonical base radius per level — using 2-row capacity estimate (halves needed radius)
  const levelR = {}
  Object.keys(childByLevel).forEach(lvl => {
    const l = parseInt(lvl)
    const n = childByLevel[lvl].length
    const needed = Math.ceil(n * NODE_D / (4 * Math.PI))  // 2-row capacity
    levelR[l] = Math.max(l * 130, needed, 120)
  })

  // Enforce monotonic increase — gap between level base radii leaves room for stacked rows
  const sortedLevels = Object.keys(levelR).map(Number).sort((a, b) => a - b)
  for (let i = 1; i < sortedLevels.length; i++) {
    const l = sortedLevels[i], lPrev = sortedLevels[i - 1]
    levelR[l] = Math.max(levelR[l], levelR[lPrev] + 170)
  }

  // Assign final positions with a GLOBAL greedy multi-row per generation: walking all
  // nodes of a level in angular order, each node takes the innermost row whose previous
  // node is far enough away. This guarantees no overlap even across sibling-family borders.
  const ROW_OFFSET = 42  // radial gap between rows (>= node diameter so stacked rows clear)
  Object.keys(childByLevel).forEach(lvl => {
    const l       = parseInt(lvl)
    const base    = levelR[l]
    const minAng  = NODE_D / base   // min angular gap to avoid overlap within one row
    const ids     = childByLevel[lvl].slice().sort((a, b) => positions[a].angle - positions[b].angle)
    const rowLast = []              // last angle placed in each row
    ids.forEach(id => {
      const a = positions[id].angle
      let row = rowLast.findIndex(last => a - last >= minAng)
      if (row === -1) { row = rowLast.length; rowLast.push(a) }
      else rowLast[row] = a
      const r = base + row * ROW_OFFSET
      positions[id].x = r * Math.cos(a - Math.PI / 2)
      positions[id].y = r * Math.sin(a - Math.PI / 2)
    })
  })

  const fullNameOf = (nd) => `${nd?.data?.['first name'] || ''} ${nd?.data?.['last name'] || ''}`.trim()

  const nodes = Object.keys(positions).map(id => {
    const n   = nodeMap[id]
    const pos = positions[id]
    const isRoot = id === centerId
    // Center spouse shares the center's size — they read as one couple
    const nodeR  = isRoot || pos.centerSpouse ? 28 : 20

    // Name label hugs the node on a bottom half-circle arc that sits JUST OUTSIDE the circle
    // (glued, but never over the face). Glyphs extend inward from the baseline, so the arc
    // radius must clear the node by at least the font height. Path left→bottom→right keeps
    // the text upright; Hebrew bidi places the letters right-to-left for us.
    const Rl = nodeR + 4
    const aL = 155 * Math.PI / 180, aR = 25 * Math.PI / 180
    const pLx = (Rl * Math.cos(aL)).toFixed(1), pLy = (Rl * Math.sin(aL)).toFixed(1)
    const pRx = (Rl * Math.cos(aR)).toFixed(1), pRy = (Rl * Math.sin(aR)).toFixed(1)
    const labelArc = `M ${pLx} ${pLy} A ${Rl} ${Rl} 0 0 1 ${pRx} ${pRy}`

    // Married-in spouse: a current spouse not placed elsewhere (peripheral hint + hover reveal)
    let spouse = null
    if (!isRoot && !pos.centerSpouse) {
      const mar = n?.data?.marriages || {}
      const sid = (n?.rels?.spouses || []).map(String).find(s => !positions[s] && nodeMap[s] && !mar[s]?.is_former)
      if (sid) {
        const sp = nodeMap[sid]
        spouse = {
          id: sid, gender: sp?.data?.gender,
          avatar: sp?.data?.avatar || null,
          firstName: sp?.data?.['first name'] || '',
          fullName: fullNameOf(sp),
        }
      }
    }

    return {
      id,
      x: pos.x, y: pos.y, level: pos.level, relType: pos.relType,
      nodeR, labelArc,
      gender:    n?.data?.gender,
      avatar:    n?.data?.avatar || null,
      firstName: n?.data?.['first name'] || '',
      lastName:  n?.data?.['last name']  || '',
      fullName:  fullNameOf(n),
      isRoot, centerSpouse: !!pos.centerSpouse, spouse,
    }
  })

  const linkLines = links
    .filter(l => positions[l.from] && positions[l.to])
    .map(l => ({
      key:  l.key,  type: l.type,
      x1: positions[l.from].x, y1: positions[l.from].y,
      x2: positions[l.to].x,   y2: positions[l.to].y,
    }))

  // Background wedge for the parents/spouse sector (SVG pie slice from center)
  let sectorPath = null
  if (relSector > 0) {
    const R   = REL_R_PAR + 40
    const aL  = -relSector / 2, aR = relSector / 2
    const p1x = R * Math.cos(aL - Math.PI / 2), p1y = R * Math.sin(aL - Math.PI / 2)
    const p2x = R * Math.cos(aR - Math.PI / 2), p2y = R * Math.sin(aR - Math.PI / 2)
    sectorPath = `M 0 0 L ${p1x.toFixed(1)} ${p1y.toFixed(1)} A ${R} ${R} 0 0 1 ${p2x.toFixed(1)} ${p2y.toFixed(1)} Z`
  }

  // Furthest node distance from center (+ node radius) — used to auto-fit the initial zoom
  let maxR = 0
  nodes.forEach(nd => {
    const d = Math.hypot(nd.x, nd.y) + nd.nodeR
    if (d > maxR) maxR = d
  })

  return { nodes, links: linkLines, sectorPath, maxR }
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

// Background pointer events — nodes use @pointerdown.stop so they don't start drag/pinch
function onRadialBgPointerDown(e) {
  radialActivePtrs.set(e.pointerId, { x: e.clientX, y: e.clientY })
  if (radialActivePtrs.size === 1) {
    radialDrag  = { px: e.clientX, py: e.clientY, vb: { ...radialVB.value }, moved: false }
    radialPinch = null
  } else if (radialActivePtrs.size === 2) {
    radialDrag  = null
    radialPinch = { startDist: _pinchDist(), startVB: { ...radialVB.value } }
  }
}
function onRadialPointerMove(e) {
  if (!radialActivePtrs.has(e.pointerId)) return
  radialActivePtrs.set(e.pointerId, { x: e.clientX, y: e.clientY })
  if (radialActivePtrs.size >= 2 && radialPinch) {
    // Pinch zoom
    const scale = radialPinch.startDist / _pinchDist()
    const svb   = radialPinch.startVB
    const newW  = Math.min(3000, Math.max(300, svb.w * scale))
    const newH  = Math.min(3000, Math.max(300, svb.h * scale))
    radialVB.value = { x: svb.x - (newW - svb.w) / 2, y: svb.y - (newH - svb.h) / 2, w: newW, h: newH }
  } else if (radialDrag) {
    // Single-finger pan
    const dx = e.clientX - radialDrag.px
    const dy = e.clientY - radialDrag.py
    if (!radialDrag.moved && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) radialDrag.moved = true
    const vb   = radialDrag.vb
    const rect = e.currentTarget.getBoundingClientRect()
    radialVB.value = { x: vb.x - dx * vb.w / rect.width, y: vb.y - dy * vb.h / rect.height, w: vb.w, h: vb.h }
  }
}
function onRadialPointerUp(e) {
  radialActivePtrs.delete(e.pointerId)
  if (radialActivePtrs.size < 2) radialPinch = null
  if (radialActivePtrs.size === 0) radialDrag = null
}

// Auto-fit the viewBox to the current layout: small families fill the view (appear big),
// large families start zoomed-in (capped) and the user zooms out from there.
function fitRadialView() {
  nextTick(() => {
    const maxR = radialData.value.maxR || 300
    const size = Math.min(Math.max(maxR * 2.2, 380), 820)
    radialVB.value = { x: -size / 2, y: -size / 2, w: size, h: size }
  })
}

function onRadialNodeClick(id) {
  // Ignore if this ended a drag
  if (radialDrag?.moved) return
  // Single click: make this person the center + open side panel
  radialCenterId.value = String(id)
  const node = localNodes.value.find(n => String(n.id) === String(id))
  if (!node) return
  selectedPerson.value = { id: node.id, ...node.data }
  addRelType.value = null
  fitRadialView()
}

function resetRadialToRoot() {
  radialCenterId.value = null
  fitRadialView()
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
    // Chart was kept alive via v-show — re-fit AFTER the container is visible
    // (nextTick + rAF). Fitting while still display:none gives scale(0)/NaN and
    // breaks pan+zoom.
    fitTreeView()
  } else {
    radialCenterId.value = null
    fitRadialView()
  }
}

// ─── Edit-details helpers ─────────────────────────────────────

// לועזי → עברי
function autoFillHebrew(field, spouseId) {
  if (field === 'birth')         ef.value.birth_date_hebrew = gregorianToHebrew(ef.value.birth_date_gregorian)
  else if (field === 'death')    ef.value.death_date_hebrew = gregorianToHebrew(ef.value.death_date_gregorian)
  else if (field === 'marriage') { const m = ef.value.marriages[spouseId]; if (m) m.date_he = gregorianToHebrew(m.date) }
}

// עברי → לועזי (רק אם ההמרה הצליחה — לא מוחק קלט קיים)
function autoFillGregorian(field, spouseId) {
  if (field === 'birth')      { const g = hebrewToGregorian(ef.value.birth_date_hebrew); if (g) ef.value.birth_date_gregorian = g }
  else if (field === 'death') { const g = hebrewToGregorian(ef.value.death_date_hebrew); if (g) ef.value.death_date_gregorian = g }
  else if (field === 'marriage') { const m = ef.value.marriages[spouseId]; if (m) { const g = hebrewToGregorian(m.date_he); if (g) m.date = g } }
}

function getSpouseName(spouseId) {
  const node = localNodes.value.find(n => n.id === String(spouseId))
  return node ? (`${node.data['first name'] || ''} ${node.data['last name'] || ''}`.trim() || `#${spouseId}`) : `#${spouseId}`
}

async function apiPut(url, body) {
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    body: JSON.stringify(body),
  })
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json()
}

async function savePerson() {
  if (!selectedPerson.value) return
  const personId = String(selectedPerson.value.id)
  efSaving.value = true

  let freshNodes
  try {
    freshNodes = await apiPut(`/api/family-tree/person/${personId}/details`, {
      maiden_name:          ef.value.maiden_name || null,
      birth_date_gregorian: ef.value.birth_date_gregorian || null,
      birth_date_hebrew:    ef.value.birth_date_hebrew    || null,
      is_deceased:          ef.value.is_deceased,
      death_date_gregorian: ef.value.death_date_gregorian || null,
      death_date_hebrew:    ef.value.death_date_hebrew    || null,
      current_occupation:   ef.value.occupation           || null,
      city:                 ef.value.city                 || null,
      email:                ef.value.email                || null,
      phone:                ef.value.phone                || null,
      bio:                  ef.value.bio                  || null,
      spouse_marriages:     ef.value.marriages,
    })
  } catch (err) {
    console.error('save-person failed:', err)
    alert('שגיאה בשמירה')
    efSaving.value = false
    return
  }
  // השמירה הצליחה — מכאן שגיאת רינדור לא נחשבת ככשל שמירה
  localNodes.value = freshNodes
  refreshChart(freshNodes)
  const freshNode = freshNodes.find(n => n.id === personId)
  if (freshNode) selectedPerson.value = { id: freshNode.id, ...freshNode.data }
  efSaving.value = false
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

/* ── Fold-branch mode: eye cursor over the whole chart ── */
#FamilyChart.hide-mode,
#FamilyChart.hide-mode * {
  cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white" stroke="%231e3a5f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>') 14 14, pointer !important;
}
#FamilyChart.hide-mode .card:hover .card-inner {
  outline: 3px solid #ef4444 !important;
  outline-offset: 1px !important;
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
  position: absolute; top: 0; right: 0; width: 310px; height: 100%;
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

/* ─── Edit-details form ─── */
.ef-form {
  display: flex; flex-direction: column; gap: 0.3rem;
  border-top: 1.5px solid #e4eefb; padding-top: 0.6rem; margin-top: 0.1rem;
}
.ef-input {
  width: 100%; padding: 0.3rem 0.45rem;
  border: 1.5px solid #d1dce8; border-radius: 7px;
  font-size: 0.82rem; font-family: 'Rubik', sans-serif;
  direction: rtl; box-sizing: border-box; background: white; color: #1a3a6b;
}
.ef-input:focus { outline: none; border-color: #2d6be4; }
.ef-input::placeholder { color: #9bacc8; }
.ef-textarea { resize: vertical; min-height: 52px; direction: rtl; }
.ef-pair { display: flex; gap: 0.3rem; }
.ef-pair .ef-input { flex: 1; min-width: 0; }
.ef-marriage { display: flex; flex-direction: column; gap: 0.3rem; margin-top: 0.25rem; }
.ef-marriage-label { font-size: 0.78rem; color: #2d6be4; font-weight: 500; }
.ef-former-toggle {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.74rem; color: #8a9ab5; cursor: pointer;
}
.ef-former-toggle input { cursor: pointer; }
/* בן/בת זוג לשעבר — אפור ועמום */
.ef-marriage-former { opacity: 0.62; }
.ef-marriage-former .ef-marriage-label { color: #94a3b8; }
.ef-marriage-former .ef-input { background: #f7f8fa; color: #64748b; }
.ef-save-btn {
  margin-top: 0.2rem; padding: 0.45rem;
  background: #2d6be4; color: white; border: none;
  border-radius: 8px; font-size: 0.86rem; font-weight: 600;
  font-family: 'Rubik', sans-serif; cursor: pointer; transition: background 0.2s;
}
.ef-save-btn:hover:not(:disabled) { background: #1a55c8; }
.ef-save-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.ef-deceased-area {
  border-top: 1px dashed #e4eefb; margin-top: 0.3rem; padding-top: 0.35rem;
  display: flex; flex-direction: column; gap: 0.3rem;
}
.ef-checkbox-label {
  display: flex; align-items: center; gap: 0.45rem;
  font-size: 0.8rem; color: #8a9ab5; cursor: pointer;
}
.ef-checkbox-label input { cursor: pointer; }

.panel-actions { display: flex; flex-direction: column; gap: 0.45rem; }
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
/* Married-in spouse: semi-transparent photo peeking behind at rest; slides out + fades to
   full on hover. Drawn before the main node so the person stays on top. */
.radial-mate {
  opacity: 0.4;
  transform: translate(11px, 11px);
  transition: opacity 0.2s ease, transform 0.2s ease;
  pointer-events: none;
}
.radial-mate.revealed { opacity: 1; transform: translate(30px, 30px); }
.radial-mate .mate-name { opacity: 0; transition: opacity 0.2s ease; }
.radial-mate.revealed .mate-name { opacity: 1; }
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

/* Fold-branch hint banner — top center of the tree view */
.fold-hint {
  position: absolute;
  top: 0.75rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e3a5f;
  background: #fef3c7;
  border: 1px solid #fcd34d;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.12);
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
    height: 58vh !important;
    top: auto !important;
    bottom: 0 !important;
    right: 0 !important;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 24px rgba(0,50,150,.15);
  }
  /* When the bottom sheet is open, pin the radial view to the visible area above it
     so the selected family stays in view instead of being hidden behind the panel. */
  .radial-wrap.panel-open { flex: none; height: 42vh; }
  .radial-hint { font-size: 0.72rem; }
}
</style>
