<template>
  <AppLayout title="ייבוא דמויות מ-CSV">
    <div class="import-page" dir="rtl">
      <div class="page-header">
        <Link href="/people" class="btn-back">← חזור לרשימה</Link>
        <h1>ייבוא דמויות מקובץ CSV</h1>
      </div>

      <div class="form-section">
        <p class="hint">
          העלאת קובץ CSV עם דמויות וקשרי משפחה. בשלב הבא תוצג תצוגה מקדימה של כל השורות,
          עם הצעות התאמה לדמויות קיימות בעץ — ואפשרות לבחור "דמות חדשה" או להתאים לדמות קיימת, לפני שהייבוא בפועל מתבצע.
        </p>

        <a href="/people-import/template" class="btn-template">⬇ הורדת תבנית CSV למילוי</a>

        <form @submit.prevent="submit">
          <div class="form-group">
            <label>קובץ CSV</label>
            <input ref="fileInput" type="file" accept=".csv,text/csv" @change="onFile" required />
            <span class="error-msg" v-if="form.errors.csv_file">{{ form.errors.csv_file }}</span>
          </div>

          <div class="form-actions">
            <Link href="/people" class="btn-cancel">ביטול</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing || !form.csv_file">
              {{ form.processing ? 'טוען...' : 'המשך לתצוגה מקדימה' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const fileInput = ref(null)

const form = useForm({
  csv_file: null,
})

function onFile(e) {
  form.csv_file = e.target.files[0] ?? null
}

function submit() {
  form.post('/people-import/preview')
}
</script>

<style scoped>
.import-page {
  max-width: 780px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

h1 {
  font-size: 1.5rem;
  color: #1a3a6b;
  margin: 0;
}

.btn-back {
  color: #2d6be4;
  text-decoration: none;
  font-size: 0.9rem;
  white-space: nowrap;
}

.form-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,50,150,0.07);
  padding: 1.75rem;
}

.hint {
  color: #4a5568;
  font-size: 0.92rem;
  line-height: 1.6;
  margin: 0 0 1.5rem;
}

.btn-template {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #2d6be4;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border: 1.5px dashed #2d6be4;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.btn-template:hover { background: #edf3ff; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

label {
  font-size: 0.85rem;
  color: #4a5568;
  font-weight: 500;
}

input[type="file"] {
  padding: 0.55rem 0.75rem;
  border: 1.5px dashed #d1dce8;
  border-radius: 8px;
  font-family: 'Rubik', sans-serif;
  background: #f8faff;
}

.error-msg {
  color: #e74c3c;
  font-size: 0.8rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
}

.btn-primary {
  background: #2d6be4;
  color: white;
  border: none;
  padding: 0.7rem 2rem;
  border-radius: 10px;
  font-size: 1rem;
  font-family: 'Rubik', sans-serif;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) { background: #1a55c8; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-cancel {
  color: #6b7a99;
  text-decoration: none;
  padding: 0.7rem 1.5rem;
  border-radius: 10px;
  font-size: 1rem;
  border: 1.5px solid #d1dce8;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s;
}

.btn-cancel:hover { border-color: #aab; }
</style>
