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
        <div v-if="loading" class="lb-spinner"></div>
        <img
          v-else
          :src="displayUrl"
          class="lb-img"
          @click.stop
          @load="loading = false"
        />
        <div class="lb-meta">
          <span v-if="current.label" class="lb-caption">{{ current.label }}</span>
          <button
            v-if="hasOriginal"
            class="lb-original-toggle"
            @click.stop="showOriginal = !showOriginal"
          >
            <ImageIcon :size="14" />
            {{ showOriginal ? 'תמונת הפרופיל החתוכה' : 'הצג בתמונה המקורית' }}
          </button>
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
import { X, ChevronLeft, ChevronRight, Image as ImageIcon } from 'lucide-vue-next'

const props = defineProps({
  photos: { type: Array, required: true },  // [{ url, originalUrl?, label? }]
  index:  { type: Number, default: 0 },
})
const emit = defineEmits(['close', 'update:index'])

const root = ref(null)
const showOriginal = ref(false)
const loading = ref(true)

const current = computed(() => props.photos[props.index] || {})
const hasOriginal = computed(() => !!current.value.originalUrl && current.value.originalUrl !== current.value.url)
const displayUrl = computed(() => (showOriginal.value && current.value.originalUrl) ? current.value.originalUrl : current.value.url)

watch(() => props.index, () => { showOriginal.value = false; loading.value = true })
watch(displayUrl, () => { loading.value = true })

function go(delta) {
  const n = props.photos.length
  if (n < 2) return
  emit('update:index', (props.index + delta + n) % n)
}
function onKey(e) {
  if (e.key === 'Escape') emit('close')
  else if (e.key === 'ArrowLeft') go(-1)
  else if (e.key === 'ArrowRight') go(1)
}
onMounted(() => {
  window.addEventListener('keydown', onKey)
  root.value?.focus()
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  window.removeEventListener('keydown', onKey)
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
  max-width: 90vw;
}
.lb-img {
  max-width: 90vw; max-height: 78vh; object-fit: contain;
  border-radius: 14px; box-shadow: 0 16px 56px rgba(0,0,0,0.5);
  cursor: default;
}
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
}
</style>
