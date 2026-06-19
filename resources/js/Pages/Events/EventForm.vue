<template>
  <form @submit.prevent="$emit('submit')" class="event-form" dir="rtl">
    <div class="form-section">
      <h2>פרטי האירוע</h2>

      <div class="form-row">
        <div class="form-group">
          <label>כותרת *</label>
          <input v-model="form.title" type="text" placeholder='למשל: בר המצווה של דוד' :class="{ 'is-error': errors.title }" />
          <span class="error-msg" v-if="errors.title">{{ errors.title }}</span>
        </div>
        <div class="form-group form-group-sm">
          <label>סוג</label>
          <select v-model="form.type">
            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>קשור לדמות</label>
        <select v-model="form.person_id">
          <option :value="null">— ללא —</option>
          <option v-for="p in people" :key="p.id" :value="p.id">{{ p.label }}</option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>תאריך (לועזי)</label>
          <input v-model="form.event_date" type="date" @change="syncHeb" />
        </div>
        <div class="form-group">
          <label>תאריך עברי</label>
          <input v-model="form.hebrew_date" type="text" placeholder='כ"ז בתמוז תשפ"ו' @change="syncGreg" />
        </div>
        <div class="form-group form-group-sm">
          <label>שעה</label>
          <input v-model="form.event_time" type="time" />
        </div>
      </div>

      <div class="form-group">
        <label>מקום</label>
        <input v-model="form.location" type="text" placeholder="אולם, כתובת..." />
      </div>
    </div>

    <div class="form-section">
      <h2>הזמנה ותמונות</h2>

      <div class="form-group">
        <label>תמונת הזמנה</label>
        <input type="file" accept="image/*" @change="onFile" />
        <div v-if="previewUrl" class="invite-preview">
          <img :src="previewUrl" alt="תצוגה מקדימה" />
        </div>
        <span class="error-msg" v-if="errors.invitation_image">{{ errors.invitation_image }}</span>
      </div>

      <div class="form-group">
        <label>קישור ל-Google Photos</label>
        <input v-model="form.photos_link" type="url" placeholder="https://photos.app.goo.gl/..." dir="ltr" />
        <span class="error-msg" v-if="errors.photos_link">{{ errors.photos_link }}</span>
      </div>
    </div>

    <div class="form-section">
      <h2>למי מיועד</h2>
      <p class="section-hint">הוסף קבוצות יעד (למשל: דודות, בני דודים בלי ילדים). אפשר גם לבחור "ענף" — כל הצאצאים של דמות מסוימת.</p>

      <div class="form-group">
        <label>קבוצות יעד</label>
        <div class="chips">
          <span v-for="(a, i) in form.audience" :key="i" class="chip">
            {{ a }}
            <button type="button" @click="form.audience.splice(i, 1)">×</button>
          </span>
        </div>
        <div class="chip-add">
          <input
            v-model="newAudience"
            type="text"
            placeholder="הקלד וסיים ב-Enter"
            @keydown.enter.prevent="addAudience"
          />
          <button type="button" class="btn-chip-add" @click="addAudience">הוסף</button>
        </div>
      </div>

      <div class="form-group">
        <label>ענף: כל הצאצאים של</label>
        <select v-model="form.audience_branch_person_id">
          <option :value="null">— ללא —</option>
          <option v-for="p in people" :key="p.id" :value="p.id">{{ p.label }}</option>
        </select>
      </div>
    </div>

    <div class="form-section">
      <h2>פרטים נוספים</h2>
      <div class="form-group">
        <label>תיאור</label>
        <textarea v-model="form.description" rows="3" placeholder="פרטים נוספים, לוח זמנים, הערות..."></textarea>
      </div>
    </div>

    <div class="form-actions">
      <Link href="/events" class="btn-cancel">ביטול</Link>
      <button type="submit" class="btn-primary" :disabled="form.processing">
        {{ form.processing ? 'שומר...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { gregorianToHebrew, hebrewToGregorian } from '@/utils/hebrewDate'

const props = defineProps({
  form: { type: Object, required: true },
  people: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  submitLabel: { type: String, default: 'שמור' },
  existingImage: { type: String, default: null },
})

defineEmits(['submit'])

const types = [
  { value: 'bar_mitzvah', label: 'בר מצווה' },
  { value: 'bat_mitzvah', label: 'בת מצווה' },
  { value: 'wedding', label: 'חתונה' },
  { value: 'birth', label: 'לידה' },
  { value: 'death', label: 'אזכרה' },
  { value: 'other', label: 'אחר' },
]

const newAudience = ref('')
const previewUrl = ref(props.existingImage)

function syncHeb() {
  const v = gregorianToHebrew(props.form.event_date)
  if (v) props.form.hebrew_date = v
}
function syncGreg() {
  const v = hebrewToGregorian(props.form.hebrew_date)
  if (v) props.form.event_date = v
}

function addAudience() {
  const v = newAudience.value.trim()
  if (v && !props.form.audience.includes(v)) props.form.audience.push(v)
  newAudience.value = ''
}

function onFile(e) {
  const file = e.target.files[0]
  props.form.invitation_image = file || null
  if (file) previewUrl.value = URL.createObjectURL(file)
}
</script>

<style scoped>
.event-form { display: flex; flex-direction: column; gap: 1.5rem; }

.form-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.07);
  padding: 1.75rem;
}

h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0 0 1.25rem; }

.section-hint { color: #8a9ab5; font-size: 0.85rem; margin: -0.75rem 0 1rem; }

.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }

.form-group {
  flex: 1; min-width: 160px;
  display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem;
}
.form-group-sm { flex: 0 0 130px; min-width: 130px; }

label { font-size: 0.85rem; color: #4a5568; font-weight: 500; }

input[type="text"], input[type="date"], input[type="time"], input[type="url"], textarea, select {
  padding: 0.55rem 0.75rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  direction: rtl;
  background: white;
  transition: border-color 0.2s;
}
input[dir="ltr"] { direction: ltr; }
input:focus, textarea:focus, select:focus { outline: none; border-color: #2d6be4; }
input.is-error { border-color: #e74c3c; }
textarea { resize: vertical; }

.error-msg { color: #e74c3c; font-size: 0.8rem; }

.invite-preview { margin-top: 0.5rem; }
.invite-preview img {
  max-width: 220px; max-height: 220px;
  border-radius: 12px; border: 1px solid #e0eaf8;
}

.chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip {
  background: #fdf1e0; color: #8a5a2b;
  border-radius: 20px; padding: 0.3rem 0.75rem;
  display: flex; align-items: center; gap: 0.4rem; font-size: 0.88rem;
}
.chip button { background: none; border: none; color: #b07a40; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; }

.chip-add { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.chip-add input { flex: 1; }
.btn-chip-add {
  background: #2d6be4; color: white; border: none;
  padding: 0.45rem 1rem; border-radius: 8px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.9rem; white-space: nowrap;
}
.btn-chip-add:hover { background: #1a55c8; }

.form-actions { display: flex; justify-content: flex-end; gap: 1rem; padding: 0.5rem 0 2rem; }

.btn-primary {
  background: #2d6be4; color: white; border: none;
  padding: 0.7rem 2rem; border-radius: 10px; font-size: 1rem;
  font-family: 'Rubik', sans-serif; font-weight: 600; cursor: pointer;
  transition: background 0.2s;
}
.btn-primary:hover:not(:disabled) { background: #1a55c8; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-cancel {
  color: #6b7a99; text-decoration: none;
  padding: 0.7rem 1.5rem; border-radius: 10px; font-size: 1rem;
  border: 1.5px solid #d1dce8; display: inline-flex; align-items: center;
}
.btn-cancel:hover { border-color: #aab; }
</style>
