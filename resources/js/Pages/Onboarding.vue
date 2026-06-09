<template>
  <div class="onboarding-wrapper" dir="rtl">
    <div class="onboarding-card">
      <!-- כותרת -->
      <div class="onboarding-header">
        <div class="family-icon">👨‍👩‍👧‍👦</div>
        <h1>ברוכים הבאים לעץ משפחת ואקיל</h1>
        <p class="subtitle">בואו נתחיל לבנות את עץ המשפחה — ספר לנו קצת על עצמך</p>
      </div>

      <!-- שלבים -->
      <div class="steps-indicator">
        <div v-for="i in 3" :key="i" :class="['step-dot', { active: step >= i, done: step > i }]">
          {{ i }}
        </div>
      </div>

      <!-- שלב 1: פרטי המשתמש -->
      <form v-if="step === 1" @submit.prevent="nextStep" class="onboarding-form">
        <h2>השלב הראשון — הפרטים שלך</h2>

        <div class="form-row">
          <div class="form-group">
            <label>שם פרטי *</label>
            <input v-model="form.first_name" type="text" required placeholder="ישראל" />
            <span class="error" v-if="errors.first_name">{{ errors.first_name }}</span>
          </div>
          <div class="form-group">
            <label>שם משפחה *</label>
            <input v-model="form.last_name" type="text" required placeholder="ואקיל" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>מגדר *</label>
            <div class="gender-toggle">
              <button type="button" :class="{ active: form.gender === 'male' }" @click="form.gender = 'male'">זכר</button>
              <button type="button" :class="{ active: form.gender === 'female' }" @click="form.gender = 'female'">נקבה</button>
            </div>
          </div>
          <div class="form-group">
            <label>תאריך לידה</label>
            <input v-model="form.birth_date_gregorian" type="date" />
          </div>
        </div>

        <div class="form-group">
          <label>מה אתה עושה כיום?</label>
          <input v-model="form.current_occupation" type="text" placeholder="למשל: מהנדס, עורך דין, גמלאי..." />
        </div>

        <div class="form-group">
          <label>עיר מגורים</label>
          <input v-model="form.city" type="text" placeholder="תל אביב" />
        </div>

        <button type="submit" class="btn-primary" :disabled="!form.first_name || !form.last_name || !form.gender">
          המשך →
        </button>
      </form>

      <!-- שלב 2: הוספת בני משפחה -->
      <div v-if="step === 2" class="onboarding-form">
        <h2>השלב השני — הוסף בני משפחה</h2>
        <p class="step-hint">אפשר להוסיף עכשיו, אפשר גם אחר כך מהעץ</p>

        <!-- הורים -->
        <div class="section-box">
          <h3>הורים</h3>
          <div class="form-row" v-for="(parent, i) in parents" :key="i">
            <div class="form-group">
              <label>שם פרטי</label>
              <input v-model="parent.first_name" type="text" :placeholder="i === 0 ? 'שם האב' : 'שם האם'" />
            </div>
            <div class="form-group">
              <label>שם משפחה</label>
              <input v-model="parent.last_name" type="text" />
            </div>
            <div class="form-group form-group-sm">
              <label>מגדר</label>
              <div class="gender-toggle sm">
                <button type="button" :class="{ active: parent.gender === 'male' }" @click="parent.gender = 'male'">ז</button>
                <button type="button" :class="{ active: parent.gender === 'female' }" @click="parent.gender = 'female'">נ</button>
              </div>
            </div>
          </div>
          <button type="button" class="btn-add" v-if="parents.length < 2" @click="addParent">+ הוסף הורה</button>
        </div>

        <!-- אחים -->
        <div class="section-box">
          <h3>אחים ואחיות</h3>
          <div v-for="(sibling, i) in siblings" :key="i" class="family-card">
            <div class="family-card-header">
              <span>אח/אחות {{ i + 1 }}</span>
              <button type="button" class="btn-remove" @click="siblings.splice(i, 1)">×</button>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>שם פרטי *</label>
                <input v-model="sibling.first_name" type="text" required />
              </div>
              <div class="form-group">
                <label>שם משפחה</label>
                <input v-model="sibling.last_name" type="text" :placeholder="form.last_name" />
              </div>
              <div class="form-group form-group-sm">
                <label>מגדר</label>
                <div class="gender-toggle sm">
                  <button type="button" :class="{ active: sibling.gender === 'male' }" @click="sibling.gender = 'male'">ז</button>
                  <button type="button" :class="{ active: sibling.gender === 'female' }" @click="sibling.gender = 'female'">נ</button>
                </div>
              </div>
            </div>
          </div>
          <button type="button" class="btn-add" @click="addSibling">+ הוסף אח/אחות</button>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-secondary" @click="step = 1">← חזור</button>
          <button type="button" class="btn-skip" @click="submitOnboarding(true)">דלג, אוסיף אחר כך</button>
          <button type="button" class="btn-primary" @click="nextStep">המשך →</button>
        </div>
      </div>

      <!-- שלב 3: סיכום + שמירה -->
      <div v-if="step === 3" class="onboarding-form">
        <h2>הכל מוכן!</h2>

        <div class="summary-box">
          <div class="summary-item">
            <strong>שם:</strong> {{ form.first_name }} {{ form.last_name }}
          </div>
          <div class="summary-item" v-if="form.birth_date_gregorian">
            <strong>תאריך לידה:</strong> {{ form.birth_date_gregorian }}
          </div>
          <div class="summary-item" v-if="form.current_occupation">
            <strong>עיסוק:</strong> {{ form.current_occupation }}
          </div>
          <div class="summary-item">
            <strong>הורים:</strong> {{ parents.filter(p => p.first_name).length }}
          </div>
          <div class="summary-item">
            <strong>אחים:</strong> {{ siblings.filter(s => s.first_name).length }}
          </div>
        </div>

        <p class="final-note">לאחר השמירה תגיע ישירות לעץ המשפחה ותוכל להמשיך להוסיף דמויות</p>

        <div class="form-actions">
          <button type="button" class="btn-secondary" @click="step = 2">← חזור</button>
          <button type="button" class="btn-primary btn-big" :disabled="saving" @click="submitOnboarding(false)">
            {{ saving ? 'שומר...' : 'בנה את העץ! 🌳' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const step = ref(1)
const saving = ref(false)
const errors = ref({})

const form = reactive({
  first_name: '',
  last_name: '',
  gender: '',
  birth_date_gregorian: '',
  current_occupation: '',
  city: '',
})

const parents = ref([])
const siblings = ref([])

function addParent() {
  if (parents.value.length < 2) {
    parents.value.push({ first_name: '', last_name: '', gender: parents.value.length === 0 ? 'male' : 'female' })
  }
}

function addSibling() {
  siblings.value.push({ first_name: '', last_name: '', gender: '' })
}

function nextStep() {
  if (step.value === 1) {
    if (!form.first_name || !form.last_name || !form.gender) return
    step.value = 2
  } else if (step.value === 2) {
    step.value = 3
  }
}

function submitOnboarding(skipFamily) {
  saving.value = true

  const payload = {
    ...form,
    parents: skipFamily ? [] : parents.value.filter(p => p.first_name),
    siblings: skipFamily ? [] : siblings.value.filter(s => s.first_name),
  }

  router.post('/onboarding', payload, {
    onError: (e) => { errors.value = e; saving.value = false },
    onFinish: () => { saving.value = false },
  })
}
</script>

<style scoped>
.onboarding-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  font-family: 'Rubik', sans-serif;
}

.onboarding-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0,100,200,0.12);
  padding: 2.5rem;
  width: 100%;
  max-width: 640px;
}

.onboarding-header {
  text-align: center;
  margin-bottom: 2rem;
}

.family-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

h1 {
  font-size: 1.6rem;
  color: #1a3a6b;
  margin: 0 0 0.5rem;
}

.subtitle {
  color: #6b7a99;
  font-size: 0.95rem;
}

.steps-indicator {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.step-dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #ccd9ed;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  color: #aab;
  font-weight: bold;
  transition: all 0.3s;
}

.step-dot.active {
  border-color: #2d6be4;
  background: #2d6be4;
  color: white;
}

.step-dot.done {
  border-color: #27ae60;
  background: #27ae60;
  color: white;
}

h2 {
  font-size: 1.25rem;
  color: #1a3a6b;
  margin: 0 0 1.5rem;
}

h3 {
  font-size: 1rem;
  color: #2d4a7a;
  margin: 0 0 1rem;
}

.form-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.form-group {
  flex: 1;
  min-width: 140px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

.form-group-sm {
  flex: 0 0 90px;
  min-width: 90px;
}

label {
  font-size: 0.85rem;
  color: #4a5568;
  font-weight: 500;
}

input[type="text"],
input[type="date"] {
  padding: 0.55rem 0.75rem;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  transition: border-color 0.2s;
  direction: rtl;
}

input:focus {
  outline: none;
  border-color: #2d6be4;
}

.gender-toggle {
  display: flex;
  border: 1.5px solid #d1dce8;
  border-radius: 8px;
  overflow: hidden;
}

.gender-toggle button {
  flex: 1;
  padding: 0.5rem;
  border: none;
  background: white;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.9rem;
  color: #6b7a99;
  transition: all 0.2s;
}

.gender-toggle button.active {
  background: #2d6be4;
  color: white;
}

.gender-toggle.sm button {
  padding: 0.35rem 0.5rem;
  font-size: 0.85rem;
}

.section-box {
  background: #f8faff;
  border: 1px solid #e0eaf8;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.25rem;
}

.family-card {
  background: white;
  border: 1px solid #d1dce8;
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}

.family-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
  color: #6b7a99;
  font-weight: 500;
}

.btn-remove {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #e74c3c;
  cursor: pointer;
  line-height: 1;
  padding: 0 0.25rem;
}

.btn-add {
  background: none;
  border: 1.5px dashed #2d6be4;
  color: #2d6be4;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  font-size: 0.9rem;
  transition: all 0.2s;
  width: 100%;
}

.btn-add:hover {
  background: #edf3ff;
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
.btn-big { padding: 0.9rem 2.5rem; font-size: 1.1rem; width: 100%; margin-top: 0.5rem; }

.btn-secondary {
  background: white;
  border: 1.5px solid #ccd9ed;
  color: #4a5568;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover { border-color: #2d6be4; color: #2d6be4; }

.btn-skip {
  background: none;
  border: none;
  color: #8a9ab5;
  font-size: 0.9rem;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  text-decoration: underline;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  align-items: center;
  margin-top: 1.5rem;
  flex-wrap: wrap;
}

.summary-box {
  background: #f0f7ff;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.summary-item {
  padding: 0.35rem 0;
  color: #2d4a7a;
  font-size: 0.95rem;
}

.step-hint {
  color: #8a9ab5;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
}

.final-note {
  color: #6b7a99;
  font-size: 0.9rem;
  text-align: center;
  margin-bottom: 0.5rem;
}

.error {
  color: #e74c3c;
  font-size: 0.8rem;
}
</style>
