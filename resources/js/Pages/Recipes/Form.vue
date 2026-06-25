<template>
  <AppLayout>
    <div class="form-page" dir="rtl">
      <div class="form-container">

        <div class="form-header">
          <Link href="/recipes" class="back-link">← חזרה למתכונים</Link>
          <h1>{{ recipe ? 'עריכת מתכון' : 'מתכון חדש 🍳' }}</h1>
        </div>

        <form @submit.prevent="submit" enctype="multipart/form-data" class="recipe-form">

          <!-- תמונה -->
          <div class="image-section">
            <div class="image-preview" @click="imageInput.click()">
              <img v-if="imagePreview" :src="imagePreview" alt="תמונה" />
              <div v-else class="image-placeholder">
                <span class="placeholder-emoji">📸</span>
                <span>לחץ להוספת תמונה</span>
                <span class="placeholder-hint">מומלץ: עד 8MB</span>
              </div>
              <div v-if="imagePreview" class="image-overlay">
                <span>החלף תמונה</span>
              </div>
            </div>
            <input ref="imageInput" type="file" accept="image/*" class="hidden-input" @change="onImageChange" />
            <button v-if="imagePreview" type="button" class="btn-remove-image" @click="removeImage">הסר תמונה</button>
          </div>

          <!-- שם -->
          <div class="form-field">
            <label>שם המתכון *</label>
            <input v-model="form.title" type="text" placeholder="למשל: שניצל של סבתא" required />
          </div>

          <!-- קטגוריה + כמות -->
          <div class="form-row">
            <div class="form-field">
              <label>קטגוריה *</label>
              <select v-model="form.category" required>
                <option value="soups">🍲 מרקים</option>
                <option value="mains">🍽️ עיקריות</option>
                <option value="salads">🥗 סלטים</option>
                <option value="pastries">🥐 מאפים</option>
                <option value="desserts">🍰 קינוחים</option>
                <option value="drinks">🥤 שתייה</option>
                <option value="other">✨ שונות</option>
              </select>
            </div>
            <div class="form-field">
              <label>כמות מנות</label>
              <input v-model="form.quantity" type="text" placeholder="למשל: 6 מנות" />
            </div>
          </div>

          <!-- של מי -->
          <div class="form-field">
            <label>המתכון של מי? (לקשר לבן/בת משפחה)</label>
            <select v-model="form.person_id">
              <option :value="null">— לא מקושר לאיש ספציפי —</option>
              <option v-for="p in people" :key="p.id" :value="p.id">
                {{ p.first_name }} {{ p.last_name }}
              </option>
            </select>
          </div>

          <!-- חומרים -->
          <div class="form-field">
            <label>חומרים *</label>
            <textarea
              v-model="form.ingredients"
              rows="6"
              placeholder="רשום כל חומר בשורה נפרדת:&#10;2 ביצים&#10;1 כוס קמח&#10;..."
              required
            ></textarea>
            <span class="field-hint">כדאי לרשום כל חומר בשורה חדשה</span>
          </div>

          <!-- הכנה -->
          <div class="form-field">
            <label>אופן הכנה *</label>
            <textarea
              v-model="form.preparation"
              rows="8"
              placeholder="תאר את שלבי ההכנה..."
              required
            ></textarea>
          </div>

          <!-- שדות נוספים -->
          <div class="form-checkboxes">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_favorite" />
              <span class="checkbox-custom">❤️ מתכון מועדף</span>
            </label>
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_gluten_free" />
              <span class="checkbox-custom">🌾 ללא גלוטן</span>
            </label>
          </div>

          <!-- כפתורים -->
          <div class="form-actions">
            <Link href="/recipes" class="btn-cancel">ביטול</Link>
            <button type="submit" class="btn-submit" :disabled="submitting">
              <span v-if="submitting">שומר...</span>
              <span v-else>{{ recipe ? '💾 שמור שינויים' : '🍽️ הוסף מתכון' }}</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  recipe: Object,
  people: Array,
})

const imageInput = ref(null)
const imagePreview = ref(props.recipe?.image_url || null)
const imageFile = ref(null)
const submitting = ref(false)

const form = reactive({
  title:          props.recipe?.title ?? '',
  category:       props.recipe?.category ?? 'mains',
  quantity:       props.recipe?.quantity ?? '',
  ingredients:    props.recipe?.ingredients ?? '',
  preparation:    props.recipe?.preparation ?? '',
  is_favorite:    props.recipe?.is_favorite ?? false,
  is_gluten_free: props.recipe?.is_gluten_free ?? false,
  person_id:      props.recipe?.person_id ?? null,
})

function onImageChange(e) {
  const file = e.target.files[0]
  if (!file) return

  // Compress via canvas before upload
  compressImage(file, 1000, 0.82).then(blob => {
    imageFile.value = blob
    imagePreview.value = URL.createObjectURL(blob)
  })
}

function compressImage(file, maxPx, quality) {
  return new Promise((resolve) => {
    const img = new Image()
    const url = URL.createObjectURL(file)
    img.onload = () => {
      URL.revokeObjectURL(url)
      let w = img.width
      let h = img.height
      if (w > maxPx || h > maxPx) {
        if (w > h) { h = Math.round(h * maxPx / w); w = maxPx }
        else       { w = Math.round(w * maxPx / h); h = maxPx }
      }
      const canvas = document.createElement('canvas')
      canvas.width = w; canvas.height = h
      canvas.getContext('2d').drawImage(img, 0, 0, w, h)
      canvas.toBlob(resolve, 'image/jpeg', quality)
    }
    img.src = url
  })
}

function removeImage() {
  imagePreview.value = null
  imageFile.value = null
  if (imageInput.value) imageInput.value.value = ''
}

function submit() {
  submitting.value = true
  const data = new FormData()
  Object.entries(form).forEach(([k, v]) => {
    if (v === null || v === undefined) return
    data.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : v)
  })
  if (imageFile.value) {
    data.append('image', imageFile.value, 'recipe.jpg')
  }

  if (props.recipe) {
    data.append('_method', 'PUT')
    router.post(`/recipes/${props.recipe.id}`, data, {
      onFinish: () => { submitting.value = false },
    })
  } else {
    router.post('/recipes', data, {
      onFinish: () => { submitting.value = false },
    })
  }
}
</script>

<style scoped>
.form-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.form-container {
  max-width: 700px;
  margin: 0 auto;
}

.form-header {
  margin-bottom: 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  color: #8aa0c2;
  text-decoration: none;
  font-size: 0.88rem;
  margin-bottom: 0.75rem;
  transition: color 0.2s;
}
.back-link:hover { color: #ff6b35; }

h1 {
  font-size: 1.7rem;
  font-weight: 700;
  color: #1a3a6b;
  margin: 0;
}

.recipe-form {
  background: white;
  border-radius: 18px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0,50,150,0.07);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Image upload */
.image-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.image-preview {
  width: 100%;
  max-width: 400px;
  height: 220px;
  border-radius: 14px;
  overflow: hidden;
  border: 2px dashed #e0eaf8;
  cursor: pointer;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafcff;
  transition: border-color 0.2s;
}

.image-preview:hover { border-color: #ff6b35; }

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  color: #8aa0c2;
  font-size: 0.9rem;
}

.placeholder-emoji { font-size: 2.5rem; }
.placeholder-hint { font-size: 0.78rem; color: #b0bec5; }

.image-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.4);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
  font-weight: 600;
}

.image-preview:hover .image-overlay { opacity: 1; }

.hidden-input { display: none; }

.btn-remove-image {
  background: none;
  border: 1px solid #e0eaf8;
  color: #e74c3c;
  font-size: 0.85rem;
  padding: 0.3rem 0.8rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  transition: all 0.2s;
}
.btn-remove-image:hover { background: #fff5f5; border-color: #e74c3c; }

/* Form fields */
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-field label {
  font-size: 0.88rem;
  font-weight: 600;
  color: #4a5568;
}

.form-field input,
.form-field select,
.form-field textarea {
  padding: 0.65rem 0.9rem;
  border: 1.5px solid #e0eaf8;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  color: #2d3748;
  transition: border-color 0.2s;
  background: white;
  direction: rtl;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
}

.form-field textarea { resize: vertical; line-height: 1.6; }

.field-hint {
  font-size: 0.78rem;
  color: #8aa0c2;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Checkboxes */
.form-checkboxes {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] { display: none; }

.checkbox-custom {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.9rem;
  border: 2px solid #e0eaf8;
  border-radius: 20px;
  font-size: 0.9rem;
  color: #4a5568;
  transition: all 0.2s;
  background: white;
  font-family: 'Rubik', sans-serif;
}

.checkbox-label input:checked + .checkbox-custom {
  border-color: #ff6b35;
  background: #fff3ee;
  color: #c05621;
  font-weight: 600;
}

/* Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 0.5rem;
  border-top: 1px solid #f0f4fb;
}

.btn-cancel {
  padding: 0.65rem 1.4rem;
  border: 1.5px solid #e0eaf8;
  border-radius: 10px;
  color: #4a5568;
  text-decoration: none;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  transition: all 0.2s;
}
.btn-cancel:hover { background: #f7f9ff; }

.btn-submit {
  padding: 0.65rem 1.8rem;
  background: linear-gradient(135deg, #ff6b35, #f7a56e);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(255,107,53,0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(255,107,53,0.4);
}

.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 640px) {
  .form-row { grid-template-columns: 1fr; }
  .recipe-form { padding: 1.25rem; }
}
</style>
