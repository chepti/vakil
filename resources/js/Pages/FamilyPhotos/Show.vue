<template>
  <AppLayout :title="localTitle || 'תמונה משפחתית'">
    <div class="photo-show-page" dir="rtl">

      <div class="page-header">
        <Link href="/family-photos" class="btn-back">← כל התמונות</Link>

        <template v-if="!editingTitle">
          <h1>{{ localTitle || 'תמונה משפחתית' }}</h1>
          <button v-if="canEdit" class="btn-edit-title" @click="startEditTitle" title="ערוך שם">✏️</button>
        </template>
        <div v-else class="title-edit">
          <input
            v-model="titleDraft"
            type="text"
            class="title-input"
            placeholder="שם התמונה…"
            ref="titleInput"
            @keydown.enter.prevent="saveTitle"
            @keydown.esc="editingTitle = false"
          />
          <button class="btn-title-save" @click="saveTitle" :disabled="savingTitle">
            {{ savingTitle ? '…' : 'שמור' }}
          </button>
          <button class="btn-title-cancel" @click="editingTitle = false">ביטול</button>
        </div>

        <button v-if="canEdit && !editingTitle" class="btn-delete" @click="deletePhoto">🗑 מחק תמונה</button>
      </div>

      <div class="photo-layout">

        <!-- Photo column (full available width) -->
        <div class="photo-col">
          <div class="photo-toolbar">
            <p class="photo-hint">גרור/י על פנים לסימון וחיתוך אוטומטי לפרופיל</p>
            <div class="zoom-controls">
              <button class="zoom-btn" @click="zoomOut" :disabled="zoom <= 1" title="הקטן">−</button>
              <span class="zoom-label">{{ Math.round(zoom * 100) }}%</span>
              <button class="zoom-btn" @click="zoomIn" :disabled="zoom >= 3" title="הגדל">+</button>
              <button v-if="zoom !== 1" class="zoom-reset" @click="zoom = 1">איפוס</button>
            </div>
          </div>

          <div class="photo-viewport">
          <div
            class="photo-container"
            ref="photoContainer"
            :style="{ width: (zoom * 100) + '%' }"
            @mousedown.prevent="onMouseDown"
            @mousemove.prevent="onMouseMove"
            @mouseup="onMouseUp"
            @mouseleave="onMouseLeave"
            @touchstart.prevent="onTouchStart"
            @touchmove.prevent="onTouchMove"
            @touchend.prevent="onTouchEnd"
          >
            <img
              :src="photo.url"
              ref="photoImg"
              class="main-photo"
              crossorigin="anonymous"
              @dragstart.prevent
              @load="imgLoaded = true"
            />

            <!-- Live drag rectangle -->
            <div v-if="dragStyle" class="drag-rect" :style="dragStyle" />

            <!-- Pending rectangle (waiting for person) -->
            <div v-if="pendingTag && !isDragging" class="pending-rect" :style="rectStyle(pendingTag)">
              <span class="rect-question">?</span>
            </div>

            <!-- Saved tag rectangles -->
            <div
              v-for="tag in localTags"
              :key="tag.id"
              class="saved-rect"
              :style="rectStyle(tag)"
            >
              <span class="rect-initials">{{ initials(tag.person_name) }}</span>
              <div class="rect-tooltip">
                <a :href="tag.person_url">{{ tag.person_name }}</a>
                <button class="tag-remove-btn" @click.stop="removeTag(tag.id)" title="הסר תיוג">×</button>
              </div>
            </div>
          </div>
          </div>
        </div>

        <!-- Side panel -->
        <div class="side-col">

          <!-- Search panel (when pending tag exists) -->
          <div v-if="pendingTag" class="search-panel">
            <h3>מי בתמונה?</h3>
            <input
              v-model="personSearch"
              type="text"
              placeholder="הקלד שם לחיפוש..."
              ref="searchInput"
              class="search-input"
              @keydown.esc="cancelPending"
            />
            <div class="search-results">
              <button
                v-for="p in searchResults"
                :key="p.id"
                class="search-result-item"
                @click="saveTag(p)"
                :disabled="savingTag"
              >
                <div class="result-initials">{{ initials(p.label) }}</div>
                {{ p.label }}
              </button>
              <div v-if="personSearch && !searchResults.length" class="no-results">לא נמצאו תוצאות</div>
            </div>
            <button class="btn-cancel" @click="cancelPending">ביטול</button>
          </div>

          <!-- Tagged people list -->
          <div class="tag-list">
            <h3>מתויגים ({{ localTags.length }})</h3>
            <div v-if="!localTags.length" class="no-tags">גרור/י על פנים לתיוג</div>
            <div v-for="tag in localTags" :key="tag.id" class="tag-list-item">
              <a :href="tag.person_url" class="tag-person-name">{{ tag.person_name }}</a>
              <button class="tag-remove-list" @click="removeTag(tag.id)" title="הסר">×</button>
            </div>
          </div>

          <!-- Upload status -->
          <div v-if="uploadStatus" class="upload-status" :class="uploadStatus.ok ? 'ok' : 'err'">
            {{ uploadStatus.msg }}
          </div>

        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  photo:     { type: Object, required: true },
  allPeople: { type: Array,  default: () => [] },
})

const authUser = usePage().props.auth.user
const canEdit = authUser?.role === 'admin' || props.photo.uploaded_by === authUser?.id

function deletePhoto() {
  if (!confirm('למחוק את התמונה לצמיתות?')) return
  router.delete(`/family-photos/${props.photo.id}`)
}

// ── עריכת שם התמונה ──
const localTitle   = ref(props.photo.title || '')
const editingTitle = ref(false)
const titleDraft   = ref('')
const savingTitle  = ref(false)
const titleInput   = ref(null)

function startEditTitle() {
  titleDraft.value = localTitle.value
  editingTitle.value = true
  nextTick(() => titleInput.value?.focus())
}

async function saveTitle() {
  if (savingTitle.value) return
  savingTitle.value = true
  try {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res = await fetch(`/family-photos/${props.photo.id}`, {
      method:  'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
      body:    JSON.stringify({ title: titleDraft.value }),
    })
    if (!res.ok) throw new Error()
    const data = await res.json()
    localTitle.value = data.title || ''
    editingTitle.value = false
  } catch {
    alert('שגיאה בשמירת השם')
  } finally {
    savingTitle.value = false
  }
}

const photoImg       = ref(null)
const photoContainer = ref(null)
const searchInput    = ref(null)
const imgLoaded      = ref(false)

// ── זום בשלב התיוג ──
const zoom = ref(1)
function zoomIn()  { zoom.value = Math.min(3, +(zoom.value + 0.25).toFixed(2)) }
function zoomOut() { zoom.value = Math.max(1, +(zoom.value - 0.25).toFixed(2)) }

// Drag state
const isDragging  = ref(false)
const dragStart   = ref(null)
const dragCurrent = ref(null)

// Tag state
const pendingTag   = ref(null)
const personSearch = ref('')
const savingTag    = ref(false)
const localTags    = ref(props.photo.tags.map(t => ({ ...t })))
const uploadStatus = ref(null)

// ── Drag helpers ─────────────────────────────────────────────

function getPct(clientX, clientY) {
  const el = photoContainer.value
  if (!el) return null
  const r = el.getBoundingClientRect()
  const x = Math.max(0, Math.min(clientX - r.left, r.width))
  const y = Math.max(0, Math.min(clientY - r.top, r.height))
  return { xPx: x, yPx: y, cw: r.width, ch: r.height }
}

const dragStyle = computed(() => {
  if (!isDragging.value || !dragStart.value || !dragCurrent.value) return null
  const x1 = Math.min(dragStart.value.xPx, dragCurrent.value.xPx)
  const y1 = Math.min(dragStart.value.yPx, dragCurrent.value.yPx)
  const w  = Math.abs(dragCurrent.value.xPx - dragStart.value.xPx)
  const h  = Math.abs(dragCurrent.value.yPx - dragStart.value.yPx)
  return { left: x1 + 'px', top: y1 + 'px', width: w + 'px', height: h + 'px' }
})

function onMouseDown(e) {
  if (e.button !== 0) return
  const p = getPct(e.clientX, e.clientY)
  if (!p) return
  isDragging.value  = true
  dragStart.value   = p
  dragCurrent.value = p
  pendingTag.value  = null
  personSearch.value = ''
  uploadStatus.value = null
}

function onMouseMove(e) {
  if (!isDragging.value) return
  dragCurrent.value = getPct(e.clientX, e.clientY)
}

function onMouseUp(e) {
  if (!isDragging.value) return
  finishDrag(e.clientX, e.clientY)
}

function onMouseLeave() {
  if (isDragging.value) {
    isDragging.value = false
    dragStart.value  = null
    dragCurrent.value = null
  }
}

// Touch
function onTouchStart(e) {
  const t = e.touches[0]
  const p = getPct(t.clientX, t.clientY)
  if (!p) return
  isDragging.value  = true
  dragStart.value   = p
  dragCurrent.value = p
  pendingTag.value  = null
  personSearch.value = ''
  uploadStatus.value = null
}
function onTouchMove(e) {
  if (!isDragging.value) return
  const t = e.touches[0]
  dragCurrent.value = getPct(t.clientX, t.clientY)
}
function onTouchEnd(e) {
  if (!isDragging.value) return
  const t = e.changedTouches[0]
  finishDrag(t.clientX, t.clientY)
}

function finishDrag(clientX, clientY) {
  isDragging.value = false
  const end = getPct(clientX, clientY)
  if (!end || !dragStart.value) return

  const x1 = Math.min(dragStart.value.xPx, end.xPx)
  const y1 = Math.min(dragStart.value.yPx, end.yPx)
  const w  = Math.abs(end.xPx - dragStart.value.xPx)
  const h  = Math.abs(end.yPx - dragStart.value.yPx)

  dragStart.value   = null
  dragCurrent.value = null

  if (w < 15 || h < 15) return  // too small — ignore

  const cw = end.cw
  const ch = end.ch
  pendingTag.value = {
    x_percent: (x1 / cw) * 100,
    y_percent: (y1 / ch) * 100,
    w_percent: (w  / cw) * 100,
    h_percent: (h  / ch) * 100,
  }
  nextTick(() => searchInput.value?.focus())
}

// ── Tag display ───────────────────────────────────────────────

function rectStyle(tag) {
  return {
    position: 'absolute',
    left:   tag.x_percent + '%',
    top:    tag.y_percent + '%',
    width:  tag.w_percent + '%',
    height: tag.h_percent + '%',
  }
}

// ── Person search ─────────────────────────────────────────────

const searchResults = computed(() => {
  const q = personSearch.value.trim().toLowerCase()
  if (!q) return []
  const tagged = new Set(localTags.value.map(t => t.person_id))
  return props.allPeople
    .filter(p => !tagged.has(p.id) && p.label.toLowerCase().includes(q))
    .slice(0, 8)
})

function cancelPending() {
  pendingTag.value  = null
  personSearch.value = ''
}

// ── Save tag + crop face ──────────────────────────────────────

async function saveTag(person) {
  if (!pendingTag.value || savingTag.value) return
  savingTag.value = true
  uploadStatus.value = null

  const rect = { ...pendingTag.value }

  try {
    const res = await fetch(`/family-photos/${props.photo.id}/tags`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body:    JSON.stringify({ person_id: person.id, ...rect }),
    })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const tag = await res.json()
    localTags.value.push(tag)
    pendingTag.value  = null
    personSearch.value = ''

    // Crop face and set as profile photo
    const blob = await cropFace(rect)
    if (blob) {
      const fd = new FormData()
      fd.append('profile_photo', blob, 'face.jpg')
      // שומרים את תמונת הגלריה כמקור — כדי לאפשר עריכת חיתוך מאוחר יותר בכרטיס
      if (props.photo.path) fd.append('source_path', props.photo.path)
      fd.append('crop_x', rect.x_percent)
      fd.append('crop_y', rect.y_percent)
      fd.append('crop_w', rect.w_percent)
      fd.append('crop_h', rect.h_percent)
      const upRes = await fetch(`/people/${person.id}/photo`, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
        body:    fd,
      })
      uploadStatus.value = upRes.ok || upRes.redirected
        ? { ok: true,  msg: `תמונת פרופיל של ${person.label} עודכנה` }
        : { ok: false, msg: 'התיוג נשמר, אך העלאת תמונת הפרופיל נכשלה' }
    }
  } catch {
    alert('שגיאה בשמירת התיוג')
  } finally {
    savingTag.value = false
  }
}

async function cropFace(rect) {
  const img = photoImg.value
  if (!img || !img.complete) return null
  try {
    const nw = img.naturalWidth
    const nh = img.naturalHeight
    const sx = (rect.x_percent / 100) * nw
    const sy = (rect.y_percent / 100) * nh
    const sw = (rect.w_percent / 100) * nw
    const sh = (rect.h_percent / 100) * nh
    const canvas = document.createElement('canvas')
    canvas.width  = Math.round(sw)
    canvas.height = Math.round(sh)
    canvas.getContext('2d').drawImage(img, sx, sy, sw, sh, 0, 0, sw, sh)
    return await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.92))
  } catch {
    return null
  }
}

// ── Remove tag ────────────────────────────────────────────────

async function removeTag(tagId) {
  if (!confirm('להסיר את התיוג?')) return
  try {
    const res = await fetch(`/family-photos/${props.photo.id}/tags/${tagId}`, {
      method:  'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken() },
    })
    if (!res.ok) throw new Error()
    localTags.value = localTags.value.filter(t => t.id !== tagId)
  } catch {
    alert('שגיאה בהסרת התיוג')
  }
}

// ── Utilities ─────────────────────────────────────────────────

function csrfToken() {
  return document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

function initials(name) {
  return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<style scoped>
.photo-show-page {
  max-width: 1300px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
}
h1 { font-size: 1.4rem; color: #1a3a6b; margin: 0; flex: 1; }
h3 { font-size: 1rem; color: #1a3a6b; margin: 0 0 0.8rem; }
.btn-back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; white-space: nowrap; }
.btn-delete {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.45rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}
.btn-delete:hover { background: #b91c1c; }

.btn-edit-title {
  background: #eef4ff; border: 1.5px solid #c7dbf7; color: #2d6be4;
  border-radius: 8px; padding: 0.35rem 0.6rem; font-size: 0.85rem; cursor: pointer;
  flex-shrink: 0;
}
.btn-edit-title:hover { background: #dbeafe; }
.title-edit { display: flex; align-items: center; gap: 0.5rem; flex: 1; flex-wrap: wrap; }
.title-input {
  flex: 1; min-width: 160px; padding: 0.45rem 0.7rem; border: 1.5px solid #2d6be4;
  border-radius: 8px; font-size: 1rem; font-family: 'Rubik', sans-serif; direction: rtl;
}
.title-input:focus { outline: none; }
.btn-title-save {
  background: #2d6be4; color: white; border: none; padding: 0.45rem 1rem;
  border-radius: 8px; font-size: 0.85rem; cursor: pointer; font-family: 'Rubik', sans-serif;
}
.btn-title-save:disabled { opacity: 0.6; cursor: default; }
.btn-title-cancel {
  background: white; border: 1.5px solid #d1dce8; color: #4a5568; padding: 0.45rem 0.9rem;
  border-radius: 8px; font-size: 0.85rem; cursor: pointer; font-family: 'Rubik', sans-serif;
}

.photo-layout {
  display: flex;
  gap: 1.5rem;
  align-items: flex-start;
}

/* ── Photo column ── */
.photo-col {
  flex: 1 1 0;
  min-width: 0;
}
.photo-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}
.photo-hint {
  font-size: 0.82rem;
  color: #8a9ab5;
  margin: 0;
}
.zoom-controls {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-shrink: 0;
}
.zoom-btn {
  width: 30px; height: 30px;
  border: 1.5px solid #d1dce8; background: white; color: #2d4a7a;
  border-radius: 7px; font-size: 1.1rem; line-height: 1; cursor: pointer;
  font-family: 'Rubik', sans-serif;
}
.zoom-btn:hover:not(:disabled) { border-color: #2d6be4; background: #edf3ff; }
.zoom-btn:disabled { opacity: 0.4; cursor: default; }
.zoom-label { font-size: 0.82rem; color: #4a5568; min-width: 42px; text-align: center; }
.zoom-reset {
  border: 1.5px solid #d1dce8; background: white; color: #4a5568;
  border-radius: 7px; padding: 0.25rem 0.6rem; font-size: 0.8rem; cursor: pointer;
  font-family: 'Rubik', sans-serif;
}
.zoom-reset:hover { border-color: #2d6be4; background: #edf3ff; }

.photo-viewport {
  overflow: auto;
  max-height: 80vh;
  border-radius: 12px;
}

.photo-container {
  position: relative;
  display: block;
  width: 100%;
  cursor: crosshair;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0, 50, 150, 0.12);
  user-select: none;
}

.main-photo {
  display: block;
  width: 100%;
  height: auto;
  pointer-events: none;
  border-radius: 12px;
}

/* ── Drag rubber-band ── */
.drag-rect {
  position: absolute;
  border: 2px dashed #f59e0b;
  background: rgba(245, 158, 11, 0.1);
  pointer-events: none;
  box-sizing: border-box;
}

/* ── Pending rectangle ── */
.pending-rect {
  position: absolute;
  border: 2.5px dashed #f59e0b;
  background: rgba(245, 158, 11, 0.08);
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
}
.rect-question {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #f59e0b;
  color: white;
  font-weight: 700;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ── Saved tag rectangle ── */
.saved-rect {
  position: absolute;
  border: 2.5px solid #2d6be4;
  background: rgba(45, 107, 228, 0.06);
  box-sizing: border-box;
  display: flex;
  align-items: flex-start;
  justify-content: flex-start;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s;
}
/* מציגים את ריבועי התיוג רק כשמרחפים מעל התמונה */
.photo-container:hover .saved-rect {
  opacity: 1;
}
.saved-rect:hover {
  border-color: #1a4fba;
  background: rgba(45, 107, 228, 0.12);
}

.rect-initials {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #2d6be4;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 3px;
  flex-shrink: 0;
}

.rect-tooltip {
  display: none;
  position: absolute;
  bottom: calc(100% + 4px);
  right: 0;
  background: rgba(0, 0, 0, 0.78);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  white-space: nowrap;
  align-items: center;
  gap: 0.4rem;
  z-index: 10;
}
.saved-rect:hover .rect-tooltip {
  display: flex;
}
.rect-tooltip a { color: white; text-decoration: none; }
.tag-remove-btn {
  background: none;
  border: none;
  color: rgba(255,255,255,.75);
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  line-height: 1;
}
.tag-remove-btn:hover { color: white; }

/* ── Side panel ── */
.side-col {
  width: 240px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-panel {
  background: white;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 50, 150, 0.09);
}
.search-input {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  box-sizing: border-box;
}
.search-input:focus { outline: none; border-color: #2d6be4; }

.search-results {
  margin-top: 0.65rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  max-height: 220px;
  overflow-y: auto;
}
.search-result-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.65rem;
  border: 1.5px solid #e4eefb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.88rem;
  color: #2d4a7a;
  text-align: right;
  transition: all 0.15s;
  width: 100%;
}
.search-result-item:hover:not(:disabled) { border-color: #2d6be4; background: #edf3ff; }
.search-result-item:disabled { opacity: 0.6; cursor: not-allowed; }
.result-initials {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: #2d6be4;
  flex-shrink: 0;
}
.no-results { font-size: 0.85rem; color: #8a9ab5; padding: 0.5rem 0; text-align: center; }
.btn-cancel {
  margin-top: 0.75rem;
  background: none;
  border: 1.5px solid #d1dce8;
  color: #4a5568;
  padding: 0.4rem 1rem;
  border-radius: 7px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.85rem;
  width: 100%;
}

.tag-list {
  background: white;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 50, 150, 0.09);
}
.no-tags { font-size: 0.85rem; color: #8a9ab5; text-align: center; padding: 0.5rem 0; }
.tag-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem 0;
  border-bottom: 1px solid #f0f4f8;
}
.tag-list-item:last-child { border-bottom: none; }
.tag-person-name { color: #2d4a7a; font-size: 0.9rem; text-decoration: none; font-weight: 500; }
.tag-person-name:hover { color: #2d6be4; }
.tag-remove-list {
  background: none;
  border: none;
  color: #aab;
  font-size: 1rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}
.tag-remove-list:hover { color: #e74c3c; }

.upload-status {
  padding: 0.65rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  text-align: center;
}
.upload-status.ok  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.upload-status.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

@media (max-width: 640px) {
  .photo-layout { flex-direction: column; }
  .side-col { width: 100%; }
}
</style>
