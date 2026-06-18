<template>
  <AppLayout title="תמונות משפחתיות">
    <div class="photos-page" dir="rtl">

      <div class="page-header">
        <h1>📸 תמונות משפחתיות</h1>
        <label class="btn-upload">
          + העלה תמונה
          <input type="file" accept="image/jpeg,image/png,image/webp" @change="handleFileSelect" hidden />
        </label>
      </div>

      <!-- Upload form (appears after file selected) -->
      <div v-if="uploadFile" class="upload-form">
        <img :src="uploadPreview" class="upload-preview" />
        <div class="upload-meta">
          <div class="form-group">
            <label>כותרת (אופציונלי)</label>
            <input v-model="uploadTitle" type="text" placeholder="למשל: חתונת הוריי 1985" />
          </div>
          <div class="upload-actions">
            <button class="btn-cancel" @click="cancelUpload">ביטול</button>
            <button class="btn-primary" @click="submitUpload" :disabled="uploading">
              {{ uploading ? 'מעלה...' : 'העלה תמונה' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="photos.length === 0 && !uploadFile" class="empty-state">
        <div class="empty-icon">📷</div>
        <p>אין תמונות עדיין. העלה תמונה משפחתית ותייג את הדמויות!</p>
      </div>

      <!-- Photos grid -->
      <div class="photos-grid">
        <div
          v-for="photo in photos"
          :key="photo.id"
          class="photo-card-wrapper"
        >
          <Link :href="`/family-photos/${photo.id}`" class="photo-card">
            <div class="photo-thumb">
              <img :src="photo.url" :alt="photo.title || 'תמונה משפחתית'" />
              <div class="photo-tags-count" v-if="photo.tags_count > 0">
                {{ photo.tags_count }} מתויגים
              </div>
            </div>
            <div class="photo-info">
              <span class="photo-title">{{ photo.title || 'ללא כותרת' }}</span>
            </div>
          </Link>
          <button
            v-if="canDelete(photo)"
            class="btn-delete-photo"
            @click="deletePhoto(photo)"
            title="מחק תמונה"
          >×</button>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  photos: { type: Array, default: () => [] },
})

const authUser = usePage().props.auth.user

function canDelete(photo) {
  return authUser?.role === 'admin' || photo.uploaded_by === authUser?.id
}

function deletePhoto(photo) {
  if (!confirm('למחוק את התמונה לצמיתות?')) return
  router.delete(`/family-photos/${photo.id}`)
}

const uploadFile    = ref(null)
const uploadPreview = ref(null)
const uploadTitle   = ref('')
const uploading     = ref(false)

function handleFileSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  uploadFile.value    = file
  uploadPreview.value = URL.createObjectURL(file)
  uploadTitle.value   = ''
  e.target.value = ''
}

function cancelUpload() {
  uploadFile.value    = null
  uploadPreview.value = null
  uploadTitle.value   = ''
}

function submitUpload() {
  if (!uploadFile.value) return
  uploading.value = true
  const data = new FormData()
  data.append('photo', uploadFile.value)
  if (uploadTitle.value) data.append('title', uploadTitle.value)
  router.post('/family-photos', data, {
    forceFormData: true,
    onFinish: () => { uploading.value = false },
  })
}
</script>

<style scoped>
.photos-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }

.btn-upload {
  background: #2d6be4; color: white; border: none;
  padding: 0.6rem 1.2rem; border-radius: 9px; font-size: 0.9rem;
  font-family: 'Rubik', sans-serif; font-weight: 600; cursor: pointer;
  display: inline-block;
}
.btn-upload:hover { background: #1a55c8; }

.upload-form {
  display: flex; gap: 1.5rem; align-items: flex-start;
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(0,50,150,.08); margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.upload-preview { width: 180px; height: 140px; object-fit: cover; border-radius: 10px; flex-shrink: 0; }
.upload-meta { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
label { font-size: 0.85rem; color: #4a5568; font-weight: 500; }
input[type="text"] {
  padding: 0.55rem 0.75rem; border: 1.5px solid #d1dce8; border-radius: 8px;
  font-size: 0.95rem; font-family: 'Rubik', sans-serif; direction: rtl;
}
input[type="text"]:focus { outline: none; border-color: #2d6be4; }

.upload-actions { display: flex; gap: 0.75rem; margin-top: auto; }
.btn-cancel {
  background: white; border: 1.5px solid #d1dce8; color: #4a5568;
  padding: 0.55rem 1.2rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif;
}
.btn-primary {
  background: #2d6be4; color: white; border: none;
  padding: 0.55rem 1.5rem; border-radius: 8px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-weight: 600;
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.empty-state {
  text-align: center; padding: 4rem 2rem; color: #8a9ab5;
}
.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state p { font-size: 1rem; }

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.photo-card-wrapper {
  position: relative;
}
.photo-card-wrapper:hover .btn-delete-photo {
  opacity: 1;
}

.photo-card {
  text-decoration: none; border-radius: 12px; overflow: hidden;
  background: white; box-shadow: 0 2px 10px rgba(0,50,150,.07);
  transition: transform 0.2s, box-shadow 0.2s;
  display: block;
}
.photo-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,50,150,.13); }

.btn-delete-photo {
  position: absolute;
  top: 0.4rem;
  left: 0.4rem;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(220, 38, 38, 0.85);
  color: white;
  border: none;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s, background 0.15s;
  z-index: 2;
}
.btn-delete-photo:hover { background: rgba(185, 28, 28, 1); }

.photo-thumb {
  position: relative; width: 100%; height: 160px; overflow: hidden;
  background: #f0f4f8;
}
.photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
.photo-tags-count {
  position: absolute; bottom: 0.4rem; right: 0.4rem;
  background: rgba(0,0,0,.6); color: white;
  font-size: 0.75rem; padding: 0.18rem 0.5rem; border-radius: 12px;
}

.photo-info { padding: 0.75rem; }
.photo-title { font-size: 0.88rem; color: #2d4a7a; font-weight: 500; }
</style>
