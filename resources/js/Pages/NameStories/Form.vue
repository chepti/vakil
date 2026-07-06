<template>
  <AppLayout>
    <div class="form-page" dir="rtl">
      <div class="form-container">

        <div class="form-header">
          <Link href="/name-stories" class="back-link">← חזרה לסיפורי השמות</Link>
          <h1>{{ story ? 'עריכת סיפור שם' : 'סיפור שם חדש 📜' }}</h1>
        </div>

        <form @submit.prevent="submit" class="story-form">

          <!-- של מי -->
          <div class="form-field">
            <label>על שם מי הסיפור? *</label>
            <select v-model="form.person_id" :disabled="!!story" required>
              <option :value="null" disabled>— בחר/י בן/בת משפחה —</option>
              <option v-for="p in people" :key="p.id" :value="p.id">
                {{ p.name }}{{ p.context ? ' — ' + p.context : '' }}
              </option>
            </select>
            <span v-if="story" class="field-hint">לא ניתן לשנות את הדמות בעריכה</span>
            <span v-else-if="people.length === 0" class="field-hint">לכל בני המשפחה כבר יש סיפור שם 🙂</span>
            <span v-if="errors.person_id" class="field-error">{{ errors.person_id }}</span>
          </div>

          <!-- על שם מי בעץ (אופציונלי) -->
          <div class="form-field">
            <label>על שם מי בעץ נקרא/ה? (אופציונלי)</label>
            <select v-model="form.named_after_person_id">
              <option :value="null">— לא נקרא/ה על שם דמות בעץ —</option>
              <option v-for="p in namedAfterOptions" :key="'na-' + p.id" :value="p.id">
                {{ p.name }}{{ p.context ? ' — ' + p.context : '' }}
              </option>
            </select>
            <span class="field-hint">כשמקושר — יופיע קו זהב בעץ בין הילד/ה למי שנקרא/ה על שמו/ה ✨</span>
            <span v-if="errors.named_after_person_id" class="field-error">{{ errors.named_after_person_id }}</span>
          </div>

          <!-- תוכן -->
          <div class="form-field">
            <label>הסיפור — למה קראו לי בשמי *</label>

            <div class="editor-toolbar">
              <button type="button" class="tool-btn" @mousedown.prevent="wrapBold" title="הדגשה (מודגש)">
                <strong>B</strong> מודגש
              </button>
              <span class="toolbar-hint">בחרו טקסט ולחצו "מודגש", או עטפו ידנית ב־ **ככה**</span>
            </div>

            <textarea
              ref="contentEl"
              v-model="form.content"
              rows="8"
              placeholder="למשל: נקראתי על שם סבתא **רבקה** ז״ל, אישה של חסד..."
              required
            ></textarea>
            <span v-if="errors.content" class="field-error">{{ errors.content }}</span>
          </div>

          <!-- תצוגה מקדימה -->
          <div class="preview-section" v-if="form.content">
            <div class="preview-label">תצוגה מקדימה</div>
            <div class="preview-box">
              <span class="quote-mark">”</span>
              <div class="preview-text" v-html="renderRichText(form.content)"></div>
            </div>
          </div>

          <!-- כפתורים -->
          <div class="form-actions">
            <Link href="/name-stories" class="btn-cancel">ביטול</Link>
            <button type="submit" class="btn-submit" :disabled="submitting || !form.person_id || !form.content">
              <span v-if="submitting">שומר...</span>
              <span v-else>{{ story ? '💾 שמור שינויים' : '📜 הוסף סיפור' }}</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { renderRichText } from '@/utils/richText'

const props = defineProps({
  story:        Object,
  people:       { type: Array, default: () => [] },
  allPeople:    { type: Array, default: () => [] },
  presetPerson: { type: [Number, null], default: null },
})

const contentEl = ref(null)
const submitting = ref(false)
const errors = reactive({ person_id: '', content: '', named_after_person_id: '' })

const form = reactive({
  person_id: props.story?.person_id ?? props.presetPerson ?? null,
  content:   props.story?.content ?? '',
  named_after_person_id: props.story?.named_after_person_id ?? null,
})

// בורר "על שם מי" — כל הדמויות חוץ מהדמות של הסיפור עצמו
const namedAfterOptions = computed(() =>
  props.allPeople.filter(p => p.id !== form.person_id)
)

function wrapBold() {
  const el = contentEl.value
  if (!el) return
  const start = el.selectionStart
  const end = el.selectionEnd
  const value = form.content
  const selected = value.slice(start, end)

  if (selected) {
    form.content = value.slice(0, start) + '**' + selected + '**' + value.slice(end)
    // מיקום הסמן אחרי הטקסט המודגש
    requestAnimationFrame(() => {
      el.focus()
      el.selectionStart = el.selectionEnd = end + 4
    })
  } else {
    form.content = value.slice(0, start) + '****' + value.slice(start)
    requestAnimationFrame(() => {
      el.focus()
      el.selectionStart = el.selectionEnd = start + 2
    })
  }
}

function submit() {
  errors.person_id = ''
  errors.content = ''
  errors.named_after_person_id = ''
  if (!form.person_id) { errors.person_id = 'יש לבחור דמות'; return }
  if (!form.content.trim()) { errors.content = 'יש לכתוב את הסיפור'; return }

  submitting.value = true
  const opts = {
    onError: (e) => { Object.assign(errors, e) },
    onFinish: () => { submitting.value = false },
  }

  if (props.story) {
    router.put(`/name-stories/${props.story.id}`, { ...form }, opts)
  } else {
    router.post('/name-stories', { ...form }, opts)
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

.form-container { max-width: 700px; margin: 0 auto; }

.form-header { margin-bottom: 1.5rem; }

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  color: #a08ac9;
  text-decoration: none;
  font-size: 0.88rem;
  margin-bottom: 0.75rem;
  transition: color 0.2s;
}
.back-link:hover { color: #8b5cf6; }

h1 {
  font-size: 1.7rem;
  font-weight: 700;
  color: #4a2e83;
  margin: 0;
}

.story-form {
  background: white;
  border-radius: 18px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(74,46,131,0.07);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-field label {
  font-size: 0.88rem;
  font-weight: 600;
  color: #4a4362;
}

.form-field select,
.form-field textarea {
  padding: 0.65rem 0.9rem;
  border: 1.5px solid #e5ddf5;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  color: #2d3748;
  transition: border-color 0.2s;
  background: white;
  direction: rtl;
}

.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139,92,246,0.1);
}

.form-field select:disabled { background: #f7f5fc; color: #8a7fa8; cursor: not-allowed; }

.form-field textarea { resize: vertical; line-height: 1.7; }

.field-hint { font-size: 0.78rem; color: #a08ac9; }
.field-error { font-size: 0.8rem; color: #e74c3c; }

/* Toolbar */
.editor-toolbar {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.tool-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border: 1.5px solid #e5ddf5;
  border-radius: 8px;
  background: #faf8ff;
  color: #5b21b6;
  font-size: 0.85rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
}
.tool-btn:hover { background: #f0e9fe; border-color: #c4b5fd; }
.tool-btn strong { font-size: 0.95rem; }

.toolbar-hint { font-size: 0.76rem; color: #b0a4cc; }

/* Preview */
.preview-section {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.preview-label { font-size: 0.82rem; font-weight: 600; color: #a08ac9; }

.preview-box {
  position: relative;
  background: linear-gradient(135deg, #faf8ff, #f5f0ff);
  border-radius: 14px;
  padding: 1rem 1.1rem;
  border-inline-start: 4px solid #c4b5fd;
}

.quote-mark {
  position: absolute;
  top: -0.35rem;
  inset-inline-start: 0.6rem;
  font-size: 2.4rem;
  color: #d6c9f7;
  line-height: 1;
  font-family: Georgia, serif;
}

.preview-text {
  color: #4a4362;
  font-size: 0.96rem;
  line-height: 1.75;
  padding-top: 0.35rem;
}
.preview-text :deep(strong) { color: #5b21b6; font-weight: 700; }

/* Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 0.5rem;
  border-top: 1px solid #f0ebfa;
}

.btn-cancel {
  padding: 0.65rem 1.4rem;
  border: 1.5px solid #e5ddf5;
  border-radius: 10px;
  color: #4a4362;
  text-decoration: none;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  transition: all 0.2s;
}
.btn-cancel:hover { background: #faf8ff; }

.btn-submit {
  padding: 0.65rem 1.8rem;
  background: linear-gradient(135deg, #8b5cf6, #b794f6);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(139,92,246,0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(139,92,246,0.4);
}

.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 640px) {
  .story-form { padding: 1.25rem; }
}
</style>
