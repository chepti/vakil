<template>
  <AppLayout :title="photo.title || 'תמונה משפחתית'">
    <div class="photo-show-page" dir="rtl">

      <div class="page-header">
        <Link href="/family-photos" class="btn-back">← כל התמונות</Link>
        <h1>{{ photo.title || 'תמונה משפחתית' }}</h1>
      </div>

      <div class="photo-layout">

        <!-- Left: photo with tag markers -->
        <div class="photo-side">
          <p class="photo-hint">לחץ/י על התמונה לסימון פנים</p>
          <div class="photo-container" ref="photoContainer" @click="handlePhotoClick">
            <img :src="photo.url" ref="photoImg" class="main-photo" @dragstart.prevent />

            <!-- Pending tag (waiting for person selection) -->
            <div v-if="pendingTag" class="tag-marker pending" :style="markerStyle(pendingTag)">
              <div class="marker-dot pending-dot">?</div>
              <div class="tag-label pending-label">בחר/י דמות</div>
            </div>

            <!-- Saved tags -->
            <div v-for="tag in localTags" :key="tag.id" class="tag-marker" :style="markerStyle(tag)">
              <a :href="tag.person_url" class="marker-dot saved-dot">{{ initials(tag.person_name) }}</a>
              <div class="tag-label">
                <a :href="tag.person_url">{{ tag.person_name }}</a>
                <button class="tag-remove" @click.stop="removeTag(tag.id)">×</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: tag panel -->
        <div class="tag-side">

          <!-- Person search (shown when pending tag exists) -->
          <div v-if="pendingTag" class="search-panel">
            <h3>מי בתמונה?</h3>
            <input
              v-model="personSearch"
              type="text"
              placeholder="הקלד שם לחיפוש..."
              autofocus
              class="search-input"
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
              <div v-if="personSearch && searchResults.length === 0" class="no-results">לא נמצאו תוצאות</div>
            </div>
            <button class="btn-cancel-tag" @click="pendingTag = null; personSearch = ''">ביטול</button>
          </div>

          <!-- Tag list -->
          <div class="tag-list">
            <h3>מתויגים בתמונה ({{ localTags.length }})</h3>
            <div v-if="localTags.length === 0" class="no-tags">
              לחץ/י על התמונה לתיוג דמויות
            </div>
            <div v-for="tag in localTags" :key="tag.id" class="tag-list-item">
              <a :href="tag.person_url" class="tag-person-name">{{ tag.person_name }}</a>
              <button class="tag-remove-list" @click="removeTag(tag.id)">×</button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  photo:     { type: Object, required: true },
  allPeople: { type: Array,  default: () => [] },
})

const photoImg       = ref(null)
const photoContainer = ref(null)
const pendingTag     = ref(null)
const personSearch   = ref('')
const savingTag      = ref(false)
const localTags      = ref([...props.photo.tags])

function csrfToken() {
  return document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

function handlePhotoClick(e) {
  if (!photoImg.value) return
  const rect = photoImg.value.getBoundingClientRect()
  const x = ((e.clientX - rect.left) / rect.width) * 100
  const y = ((e.clientY - rect.top) / rect.height) * 100
  if (x < 0 || x > 100 || y < 0 || y > 100) return
  pendingTag.value  = { x_percent: x, y_percent: y }
  personSearch.value = ''
}

function markerStyle(tag) {
  return {
    position: 'absolute',
    left: `${tag.x_percent}%`,
    top:  `${tag.y_percent}%`,
    transform: 'translate(-50%, -50%)',
  }
}

const searchResults = computed(() => {
  const q = personSearch.value.trim().toLowerCase()
  if (!q) return []
  const taggedIds = new Set(localTags.value.map(t => t.person_id))
  return props.allPeople
    .filter(p => !taggedIds.has(p.id) && p.label.toLowerCase().includes(q))
    .slice(0, 8)
})

async function saveTag(person) {
  if (!pendingTag.value || savingTag.value) return
  savingTag.value = true
  try {
    const res = await fetch(`/family-photos/${props.photo.id}/tags`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        person_id: person.id,
        x_percent: pendingTag.value.x_percent,
        y_percent: pendingTag.value.y_percent,
      }),
    })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const tag = await res.json()
    localTags.value.push(tag)
    pendingTag.value  = null
    personSearch.value = ''
  } catch (err) {
    alert('שגיאה בשמירת התיוג')
  } finally {
    savingTag.value = false
  }
}

async function removeTag(tagId) {
  if (!confirm('להסיר את התיוג?')) return
  try {
    const res = await fetch(`/family-photos/${props.photo.id}/tags/${tagId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken() },
    })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    localTags.value = localTags.value.filter(t => t.id !== tagId)
  } catch {
    alert('שגיאה בהסרת התיוג')
  }
}

function initials(name) {
  return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2)
}
</script>

<style scoped>
.photo-show-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem;
}
h1 { font-size: 1.4rem; color: #1a3a6b; margin: 0; }
.btn-back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; white-space: nowrap; }

.photo-layout {
  display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;
}

.photo-side {
  flex: 2; min-width: 300px;
}
.photo-hint { font-size: 0.82rem; color: #8a9ab5; margin: 0 0 0.5rem; }

.photo-container {
  position: relative; display: inline-block; cursor: crosshair;
  border-radius: 12px; overflow: visible;
  box-shadow: 0 4px 20px rgba(0,50,150,.1);
}
.main-photo {
  display: block; max-width: 100%; border-radius: 12px;
  user-select: none; pointer-events: none;
}

.tag-marker { position: absolute; display: flex; flex-direction: column; align-items: center; }
.marker-dot {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700; cursor: pointer;
  border: 2.5px solid white; box-shadow: 0 2px 8px rgba(0,0,0,.3);
  text-decoration: none; transition: transform 0.15s;
}
.marker-dot:hover { transform: scale(1.15); }
.pending-dot { background: #f59e0b; color: white; font-size: 1rem; }
.saved-dot { background: #2d6be4; color: white; }

.tag-label {
  background: rgba(0,0,0,.75); color: white;
  font-size: 0.72rem; padding: 0.2rem 0.45rem; border-radius: 5px;
  margin-top: 0.2rem; white-space: nowrap; display: flex; align-items: center; gap: 0.25rem;
}
.tag-label a { color: white; text-decoration: none; }
.pending-label { background: rgba(245,158,11,.9); }
.tag-remove {
  background: none; border: none; color: rgba(255,255,255,.8);
  cursor: pointer; font-size: 0.9rem; padding: 0; line-height: 1;
}
.tag-remove:hover { color: white; }

/* ── Tag side panel ── */
.tag-side {
  flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 1rem;
}

.search-panel {
  background: white; border-radius: 14px; padding: 1.25rem;
  box-shadow: 0 2px 12px rgba(0,50,150,.08);
}
.search-panel h3 { font-size: 1rem; color: #1a3a6b; margin: 0 0 0.85rem; }
.search-input {
  width: 100%; padding: 0.55rem 0.75rem;
  border: 1.5px solid #d1dce8; border-radius: 8px;
  font-size: 0.95rem; font-family: 'Rubik', sans-serif;
  direction: rtl; box-sizing: border-box;
}
.search-input:focus { outline: none; border-color: #2d6be4; }

.search-results {
  margin-top: 0.65rem; display: flex; flex-direction: column; gap: 0.3rem;
  max-height: 220px; overflow-y: auto;
}
.search-result-item {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.5rem 0.65rem; border: 1.5px solid #e4eefb; border-radius: 8px;
  background: white; cursor: pointer; font-family: 'Rubik', sans-serif;
  font-size: 0.88rem; color: #2d4a7a; text-align: right; transition: all 0.15s;
  width: 100%;
}
.search-result-item:hover { border-color: #2d6be4; background: #edf3ff; }
.search-result-item:disabled { opacity: 0.6; cursor: not-allowed; }
.result-initials {
  width: 28px; height: 28px; border-radius: 50%; background: #e8f0fe;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700; color: #2d6be4; flex-shrink: 0;
}
.no-results { font-size: 0.85rem; color: #8a9ab5; padding: 0.5rem 0; text-align: center; }
.btn-cancel-tag {
  margin-top: 0.75rem; background: none; border: 1.5px solid #d1dce8;
  color: #4a5568; padding: 0.4rem 1rem; border-radius: 7px;
  cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.85rem; width: 100%;
}

.tag-list {
  background: white; border-radius: 14px; padding: 1.25rem;
  box-shadow: 0 2px 12px rgba(0,50,150,.08);
}
.tag-list h3 { font-size: 1rem; color: #1a3a6b; margin: 0 0 0.75rem; }
.no-tags { font-size: 0.85rem; color: #8a9ab5; text-align: center; padding: 0.5rem 0; }
.tag-list-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.45rem 0; border-bottom: 1px solid #f0f4f8;
}
.tag-list-item:last-child { border-bottom: none; }
.tag-person-name { color: #2d4a7a; font-size: 0.9rem; text-decoration: none; font-weight: 500; }
.tag-person-name:hover { color: #2d6be4; }
.tag-remove-list {
  background: none; border: none; color: #aab; font-size: 1rem;
  cursor: pointer; padding: 0; line-height: 1;
}
.tag-remove-list:hover { color: #e74c3c; }
</style>
