<template>
  <Transition name="lb-fade">
    <div
      class="lb-backdrop"
      dir="rtl"
      tabindex="-1"
      ref="root"
      @click.self="$emit('close')"
    >
      <button class="lb-close" @click="$emit('close')" title="סגירה (Esc)"><X :size="20" /></button>

      <button v-if="photos.length > 1" class="lb-nav lb-prev" @click.stop="go(-1)" title="הקודם">
        <ChevronLeft :size="26" />
      </button>

      <div class="lb-stage">
        <div
          class="lb-img-wrap"
          :class="{ 'lb-can-pan': zoom > 1 }"
          @wheel.prevent="onWheel"
          @pointerdown="onPointerDown"
        >
          <div v-if="loading || !displayUrl" class="lb-spinner"></div>
          <img
            v-if="displayUrl"
            :src="displayUrl"
            class="lb-img"
            :class="{ 'lb-img-hidden': loading }"
            :style="{ transform: `translate(${panX}px, ${panY}px) scale(${zoom})` }"
            @click.stop
            @dblclick.stop="toggleQuickZoom"
            @load="loading = false"
            @error="loading = false"
            draggable="false"
          />
        </div>
        <div class="lb-meta">
          <span v-if="current.label" class="lb-caption">{{ current.label }}</span>

          <button
            v-if="isLocked"
            class="lb-original-toggle lb-locked-toggle"
            @click.stop="$emit('unlock-original', index)"
          >
            <Lock :size="14" />
            {{ current.locked.label }}
          </button>
          <button
            v-else-if="hasOriginal"
            class="lb-original-toggle"
            @click.stop="showOriginal = !showOriginal"
          >
            <ImageIcon :size="14" />
            {{ showOriginal ? 'תמונת הפרופיל החתוכה' : 'הצג בתמונה המקורית' }}
          </button>

          <div class="lb-zoom-controls">
            <button class="lb-zoom-btn" @click.stop="zoomOut" :disabled="zoom <= 1" title="הקטן"><Minus :size="15" /></button>
            <span class="lb-zoom-label">{{ Math.round(zoom * 100) }}%</span>
            <button class="lb-zoom-btn" @click.stop="zoomIn" :disabled="zoom >= MAX_ZOOM" title="הגדל"><Plus :size="15" /></button>
          </div>
        </div>
      </div>

      <button v-if="photos.length > 1" class="lb-nav lb-next" @click.stop="go(1)" title="הבא">
        <ChevronRight :size="26" />
      </button>

      <div v-if="photos.length > 1" class="lb-counter">{{ index + 1 }} / {{ photos.length }}</div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { X, ChevronLeft, ChevronRight, Image as ImageIcon, Lock, Plus, Minus } from 'lucide-vue-next'

// photos: [{ url, originalUrl?, label?, locked?: { label } }]
// locked — "התמונה המקורית" נעולה מאחורי פעולה חיצונית (למשל מחיר בנקודות במשחק); הרכיב לא
// מכיר את המחיר/הלוגיקה, רק מציג את הכיתוב שהאב מסר ומודיע לו כשלוחצים (unlock-original).
const props = defineProps({
  photos: { type: Array, required: true },
  index:  { type: Number, default: 0 },
})
const emit = defineEmits(['close', 'update:index', 'unlock-original'])

const MAX_ZOOM = 4
const ZOOM_STEP = 0.6

const root = ref(null)
const showOriginal = ref(false)
const loading = ref(true)
const zoom = ref(1)
const panX = ref(0)
const panY = ref(0)
let dragState = null

const current = computed(() => props.photos[props.index] || {})
const hasOriginal = computed(() => !!current.value.originalUrl && current.value.originalUrl !== current.value.url)
const isLocked = computed(() => !!current.value.locked && !current.value.originalUrl)
const displayUrl = computed(() => (showOriginal.value && current.value.originalUrl) ? current.value.originalUrl : current.value.url)

function resetView() { showOriginal.value = false; loading.value = true; resetZoom() }
function resetZoom() { zoom.value = 1; panX.value = 0; panY.value = 0 }

watch(() => props.index, resetView)
watch(displayUrl, () => { loading.value = true; resetZoom() })
// מי שקנה/פתח את התמונה המקורית מקבל אותה מוצגת מיד — בלי קליק נוסף
watch(() => current.value.locked, (now, before) => {
  if (before && !now && current.value.originalUrl) showOriginal.value = true
})

function go(delta) {
  const n = props.photos.length
  if (n < 2) return
  emit('update:index', (props.index + delta + n) % n)
}

function zoomIn() { zoom.value = Math.min(MAX_ZOOM, +(zoom.value + ZOOM_STEP).toFixed(2)) }
function zoomOut() {
  zoom.value = Math.max(1, +(zoom.value - ZOOM_STEP).toFixed(2))
  if (zoom.value === 1) { panX.value = 0; panY.value = 0 }
}
function toggleQuickZoom() { zoom.value > 1 ? resetZoom() : (zoom.value = 2.2) }

function onWheel(e) {
  const delta = e.deltaY > 0 ? -ZOOM_STEP : ZOOM_STEP
  const next = Math.min(MAX_ZOOM, Math.max(1, +(zoom.value + delta).toFixed(2)))
  zoom.value = next
  if (next === 1) { panX.value = 0; panY.value = 0 }
}

// גרירה להזזה כשמוגדל — עוקב אחרי המצביע גם מעבר לגבולות התמונה
function onPointerDown(e) {
  if (zoom.value <= 1) return
  dragState = { x: e.clientX, y: e.clientY, panX: panX.value, panY: panY.value }
  window.addEventListener('pointermove', onPointerMove)
  window.addEventListener('pointerup', onPointerUp)
}
function onPointerMove(e) {
  if (!dragState) return
  panX.value = dragState.panX + (e.clientX - dragState.x)
  panY.value = dragState.panY + (e.clientY - dragState.y)
}
function onPointerUp() {
  dragState = null
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
}

function onKey(e) {
  if (e.key === 'Escape') emit('close')
  else if (e.key === 'ArrowLeft') go(-1)
  else if (e.key === 'ArrowRight') go(1)
  else if (e.key === '+' || e.key === '=') zoomIn()
  else if (e.key === '-') zoomOut()
}
onMounted(() => {
  window.addEventListener('keydown', onKey)
  root.value?.focus()
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  window.removeEventListener('keydown', onKey)
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.lb-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(8, 16, 32, 0.9);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 2rem;
  font-family: 'Rubik', sans-serif;
}

.lb-close {
  position: absolute; top: 1rem; left: 1rem; z-index: 2;
  width: 40px; height: 40px; border-radius: 50%;
  background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.25);
  color: white; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.lb-close:hover { background: rgba(255,255,255,0.28); }

.lb-nav {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
  width: 46px; height: 46px; border-radius: 50%;
  background: rgba(255,255,255,0.1); border: 1.5px solid rgba(255,255,255,0.22);
  color: white; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, transform 0.15s;
}
.lb-nav:hover { background: rgba(255,255,255,0.26); transform: translateY(-50%) scale(1.06); }
.lb-prev { left: 1rem; }
.lb-next { right: 1rem; }

.lb-stage {
  display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
}
.lb-img-wrap {
  position: relative;
  width: min(92vw, 1100px);
  height: 76vh;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  border-radius: 14px;
}
.lb-can-pan { cursor: grab; }
.lb-can-pan:active { cursor: grabbing; }
.lb-img {
  width: 100%; height: 100%; object-fit: contain;
  border-radius: 14px; box-shadow: 0 16px 56px rgba(0,0,0,0.5);
  cursor: zoom-in;
  transition: transform 0.05s linear;
  touch-action: none;
  user-select: none;
}
.lb-can-pan .lb-img { cursor: inherit; }
.lb-img-hidden { position: absolute; opacity: 0; pointer-events: none; }
.lb-spinner {
  width: 42px; height: 42px; border-radius: 50%;
  border: 3px solid rgba(255,255,255,0.25); border-top-color: white;
  animation: lb-spin 0.8s linear infinite;
}

.lb-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; justify-content: center; }
.lb-caption {
  color: rgba(255,255,255,0.85); font-size: 0.88rem; font-weight: 600;
}
.lb-original-toggle {
  display: inline-flex; align-items: center; gap: 0.35rem;
  background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.25);
  color: white; border-radius: 20px; padding: 0.35rem 0.9rem;
  font-family: 'Rubik', sans-serif; font-size: 0.8rem; font-weight: 600;
  cursor: pointer; transition: background 0.2s;
}
.lb-original-toggle:hover { background: rgba(255,255,255,0.26); }
.lb-locked-toggle { background: rgba(245,158,11,0.18); border-color: rgba(245,158,11,0.5); color: #fde68a; }
.lb-locked-toggle:hover { background: rgba(245,158,11,0.3); }

.lb-zoom-controls {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: rgba(255,255,255,0.1); border: 1.5px solid rgba(255,255,255,0.2);
  border-radius: 20px; padding: 0.2rem 0.35rem;
}
.lb-zoom-btn {
  width: 24px; height: 24px; border-radius: 50%; border: none;
  background: rgba(255,255,255,0.14); color: white; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
}
.lb-zoom-btn:hover:not(:disabled) { background: rgba(255,255,255,0.3); }
.lb-zoom-btn:disabled { opacity: 0.35; cursor: default; }
.lb-zoom-label {
  color: rgba(255,255,255,0.75); font-size: 0.74rem; font-weight: 600;
  min-width: 38px; text-align: center; font-variant-numeric: tabular-nums;
}

.lb-counter {
  position: absolute; bottom: 1.1rem; left: 50%; transform: translateX(-50%);
  color: rgba(255,255,255,0.7); font-size: 0.8rem; font-weight: 600;
  background: rgba(255,255,255,0.1); padding: 0.2rem 0.7rem; border-radius: 20px;
}

@keyframes lb-spin { to { transform: rotate(360deg); } }

.lb-fade-enter-active, .lb-fade-leave-active { transition: opacity 0.2s ease; }
.lb-fade-enter-from, .lb-fade-leave-to { opacity: 0; }

@media (max-width: 640px) {
  .lb-nav { width: 38px; height: 38px; }
  .lb-close { width: 36px; height: 36px; top: 0.6rem; left: 0.6rem; }
  .lb-prev { left: 0.4rem; }
  .lb-next { right: 0.4rem; }
  .lb-img-wrap { height: 62vh; }
}
</style>
