<template>
  <AppLayout>
    <div class="show-page" dir="rtl">

      <!-- Hero section -->
      <div class="recipe-hero" :class="getCategoryBgClass(recipe.category)">
        <div class="hero-inner">
          <Link href="/recipes" class="back-link">← חזרה למתכונים</Link>

          <div class="hero-content">
            <div class="hero-text">
              <div class="hero-badges">
                <span class="cat-badge">{{ getCategoryEmoji(recipe.category) }} {{ recipe.category }}</span>
                <span v-if="recipe.is_favorite" class="badge-special">❤️ מועדף</span>
                <span v-if="recipe.is_gluten_free" class="badge-special">🌾 ללא גלוטן</span>
                <span v-if="recipe.quantity" class="badge-special">🍴 {{ recipe.quantity }}</span>
              </div>
              <h1>{{ recipe.title }}</h1>

              <!-- של מי -->
              <div v-if="recipe.person" class="recipe-owner">
                <Link :href="`/people/${recipe.person.id}`" class="owner-link">
                  <span class="owner-icon">🧑‍🍳</span>
                  <div>
                    <span class="owner-name">{{ recipe.person.name }}</span>
                    <span v-if="recipe.person.context" class="owner-context">{{ recipe.person.context }}</span>
                  </div>
                </Link>
              </div>
              <div v-else-if="recipe.owner_text" class="recipe-owner-plain">
                🧑‍🍳 {{ recipe.owner_text }}
              </div>
            </div>

            <div class="hero-image" v-if="recipe.image_url">
              <img :src="recipe.image_url" :alt="recipe.title" />
            </div>
            <div class="hero-emoji" v-else>
              {{ getCategoryEmoji(recipe.category) }}
            </div>
          </div>

          <!-- Actions -->
          <div class="hero-actions">
            <button class="btn-copy" @click="copyRecipe">
              {{ copied ? '✓ הועתק!' : '📋 העתק מתכון' }}
            </button>
            <button
              class="btn-wakelock"
              :class="{ active: screenAwake }"
              @click="toggleScreenLock"
              :title="screenAwake ? 'שחרר נעילת מסך' : 'שמור מסך דולק בזמן בישול'"
            >{{ screenAwake ? '🔆 מסך נעול' : '🔅 שמור מסך' }}</button>
            <template v-if="recipe.can_edit">
              <Link :href="`/recipes/${recipe.id}/edit`" class="btn-edit">✏️ עריכה</Link>
              <button class="btn-delete" @click="confirmDelete">🗑️ מחק</button>
            </template>
          </div>
        </div>
      </div>

      <div class="recipe-body">

        <!-- חומרים + הכנה -->
        <div class="recipe-main">
          <div class="recipe-section ingredients-section">
            <div class="section-title-row">
              <h2>🥕 חומרים</h2>
              <button v-if="checkedIngredients.size > 0" class="btn-reset-checks" @click="checkedIngredients = new Set()">נקה סימונים</button>
            </div>
            <ul class="ingredients-list">
              <li
                v-for="(line, i) in ingredientLines"
                :key="i"
                :class="{ 'item-checked': checkedIngredients.has(i) }"
                @click="toggleIngredient(i)"
              >
                <span class="check-box" :class="{ done: checkedIngredients.has(i) }">
                  <svg v-if="checkedIngredients.has(i)" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>{{ line }}</span>
              </li>
            </ul>
          </div>

          <div class="recipe-section preparation-section">
            <div class="section-title-row">
              <h2>👩‍🍳 אופן הכנה</h2>
              <button v-if="checkedSteps.size > 0" class="btn-reset-checks" @click="checkedSteps = new Set()">נקה סימונים</button>
            </div>
            <ol class="steps-list">
              <li
                v-for="(step, i) in preparationSteps"
                :key="i"
                :class="{ 'item-checked': checkedSteps.has(i) }"
                @click="toggleStep(i)"
              >{{ step }}</li>
            </ol>
          </div>
        </div>

        <!-- התאמות אישיות -->
        <div class="recipe-section adaptations-section">
          <h2>✏️ התאמות אישיות</h2>
          <p class="section-hint">כל אחד יכול לרשום איך המתכון עובד אצלו, מה שינה, מה הוסיף...</p>

          <!-- My adaptation form -->
          <div class="my-adaptation">
            <h3>ההתאמה שלי</h3>
            <textarea
              v-model="myAdaptationText"
              rows="3"
              placeholder="מה שיניתי? מה הוסיף טעם? כמות שונה?"
              class="adaptation-input"
              dir="rtl"
            ></textarea>
            <button class="btn-save-adaptation" @click="saveAdaptation" :disabled="savingAdaptation">
              {{ savingAdaptation ? 'שומר...' : '💾 שמור התאמה' }}
            </button>
          </div>

          <!-- Others' adaptations -->
          <div v-if="recipe.adaptations.length" class="adaptations-list">
            <div v-for="a in recipe.adaptations" :key="a.id" class="adaptation-item" :class="{ mine: a.is_mine }">
              <div class="adaptation-header">
                <span class="adaptation-user">{{ a.is_mine ? 'ההתאמה שלי' : a.user_name }}</span>
              </div>
              <p class="adaptation-text">{{ a.content }}</p>
            </div>
          </div>
        </div>

        <!-- תגובות -->
        <div class="recipe-section comments-section">
          <h2>💬 תגובות ({{ totalCommentCount }})</h2>

          <!-- Add comment form -->
          <div class="comment-form">
            <textarea
              v-model="newComment"
              rows="2"
              placeholder="הוסף תגובה, שאלה, או תיקון..."
              class="comment-input"
              dir="rtl"
            ></textarea>
            <button class="btn-comment" @click="submitComment(null)" :disabled="!newComment.trim()">
              שלח
            </button>
          </div>

          <!-- Comments list -->
          <div class="comments-list">
            <div v-for="comment in recipe.comments" :key="comment.id" class="comment-thread">
              <div class="comment">
                <div class="comment-header">
                  <span class="comment-user">{{ comment.user_name }}</span>
                  <span class="comment-time">{{ comment.created_at }}</span>
                </div>
                <p class="comment-text">{{ comment.content }}</p>
                <div class="comment-actions">
                  <button class="reply-btn" @click="replyingTo = comment.id">↩ השב</button>
                  <button v-if="comment.can_delete" class="delete-comment-btn" @click="deleteComment(comment.id)">מחק</button>
                </div>

                <!-- Reply form -->
                <div v-if="replyingTo === comment.id" class="reply-form">
                  <textarea
                    v-model="replyText"
                    rows="2"
                    placeholder="כתוב תשובה..."
                    class="comment-input"
                    dir="rtl"
                  ></textarea>
                  <div class="reply-actions">
                    <button class="btn-comment btn-sm" @click="submitComment(comment.id)" :disabled="!replyText.trim()">שלח</button>
                    <button class="btn-cancel-reply" @click="replyingTo = null; replyText = ''">ביטול</button>
                  </div>
                </div>
              </div>

              <!-- Replies -->
              <div v-if="comment.replies?.length" class="replies">
                <div v-for="reply in comment.replies" :key="reply.id" class="comment reply">
                  <div class="comment-header">
                    <span class="comment-user">{{ reply.user_name }}</span>
                    <span class="comment-time">{{ reply.created_at }}</span>
                  </div>
                  <p class="comment-text">{{ reply.content }}</p>
                  <div class="comment-actions">
                    <button v-if="reply.can_delete" class="delete-comment-btn" @click="deleteComment(reply.id)">מחק</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  recipe: Object,
  myAdaptation: String,
})

const copied = ref(false)
const newComment = ref('')
const replyText = ref('')
const replyingTo = ref(null)
const myAdaptationText = ref(props.myAdaptation || '')
const savingAdaptation = ref(false)

// Checkboxes (client-side only, not saved)
const checkedIngredients = ref(new Set())
const checkedSteps = ref(new Set())

function toggleIngredient(i) {
  const s = new Set(checkedIngredients.value)
  s.has(i) ? s.delete(i) : s.add(i)
  checkedIngredients.value = s
}

function toggleStep(i) {
  const s = new Set(checkedSteps.value)
  s.has(i) ? s.delete(i) : s.add(i)
  checkedSteps.value = s
}

// Wake Lock
let _wakeLock = null
const screenAwake = ref(false)

async function toggleScreenLock() {
  if (screenAwake.value) {
    try { await _wakeLock?.release() } catch {}
    _wakeLock = null
    screenAwake.value = false
    return
  }
  if (!('wakeLock' in navigator)) {
    alert('הדפדפן שלך לא תומך בנעילת מסך')
    return
  }
  try {
    _wakeLock = await navigator.wakeLock.request('screen')
    screenAwake.value = true
    _wakeLock.addEventListener('release', () => { screenAwake.value = false; _wakeLock = null })
  } catch {
    alert('לא ניתן לנעול את המסך — ודא שהדף פתוח ב-HTTPS')
  }
}

onUnmounted(() => { try { _wakeLock?.release() } catch {} })

const catEmojis = {
  'מרקים':'🍲', 'שבת':'✨', 'עוגות':'🎂', 'עוגיות':'🍪',
  'מושקע':'👨‍🍳', 'פודי':'🍽️', 'פחמימה':'🍞', 'לראש השנה':'🍎',
  'חלבי':'🧀', 'לפסח':'🫓', 'לחנוכה':'🕎', 'בשרי':'🥩',
  'ירקות':'🥦', 'לשבועות':'🌸', 'סלטים':'🥗', 'לפורים':'🎭',
  'ליום העצמאות':'🇮🇱', 'שתייה':'🥤', 'עיקריות':'🍽️',
}

const catBgClasses = {
  'מרקים':'cat-soups', 'שבת':'cat-shabbat', 'עוגות':'cat-desserts',
  'עוגיות':'cat-desserts', 'מושקע':'cat-mains', 'פודי':'cat-mains',
  'פחמימה':'cat-pastries', 'לראש השנה':'cat-shabbat', 'חלבי':'cat-drinks',
  'לפסח':'cat-salads', 'לחנוכה':'cat-shabbat', 'בשרי':'cat-mains',
  'ירקות':'cat-salads', 'לשבועות':'cat-shabbat', 'סלטים':'cat-salads',
}

function getCategoryLabel(c) { return c || '' }
function getCategoryEmoji(c) {
  const first = c ? c.split(',')[0].trim() : ''
  return catEmojis[first] || '🍽️'
}
function getCategoryBgClass(c) {
  const first = c ? c.split(',')[0].trim() : ''
  return catBgClasses[first] || 'cat-other'
}

const ingredientLines = computed(() =>
  props.recipe.ingredients.split('\n').map(l => l.trim()).filter(Boolean)
)

const preparationSteps = computed(() =>
  props.recipe.preparation.split('\n').map(l => l.trim()).filter(Boolean)
)

const totalCommentCount = computed(() =>
  props.recipe.comments.reduce((acc, c) => acc + 1 + (c.replies?.length || 0), 0)
)

function copyRecipe() {
  const text = [
    `🍽️ ${props.recipe.title}`,
    props.recipe.person ? `של: ${props.recipe.person.name} ${props.recipe.person.context || ''}` : '',
    props.recipe.quantity ? `כמות: ${props.recipe.quantity}` : '',
    props.recipe.is_gluten_free ? '🌾 ללא גלוטן' : '',
    '',
    '🥕 חומרים:',
    ...ingredientLines.value.map(l => `• ${l}`),
    '',
    '👩‍🍳 הכנה:',
    ...preparationSteps.value.map((s, i) => `${i + 1}. ${s}`),
  ].filter(l => l !== undefined && l !== null).join('\n')

  navigator.clipboard.writeText(text).then(() => {
    copied.value = true
    setTimeout(() => { copied.value = false }, 2500)
  })
}

function confirmDelete() {
  if (!confirm(`למחוק את המתכון "${props.recipe.title}"?`)) return
  router.delete(`/recipes/${props.recipe.id}`)
}

function submitComment(parentId) {
  const content = parentId ? replyText.value : newComment.value
  if (!content.trim()) return

  router.post(`/recipes/${props.recipe.id}/comments`, {
    content: content.trim(),
    parent_id: parentId,
  }, {
    onSuccess: () => {
      if (parentId) { replyText.value = ''; replyingTo.value = null }
      else { newComment.value = '' }
    },
  })
}

function deleteComment(id) {
  if (!confirm('למחוק תגובה זו?')) return
  router.delete(`/recipe-comments/${id}`)
}

function saveAdaptation() {
  if (!myAdaptationText.value.trim()) return
  savingAdaptation.value = true
  router.post(`/recipes/${props.recipe.id}/adaptation`, {
    content: myAdaptationText.value.trim(),
  }, {
    onFinish: () => { savingAdaptation.value = false },
  })
}
</script>

<style scoped>
.show-page {
  font-family: 'Rubik', sans-serif;
}

/* Hero */
.recipe-hero {
  background: linear-gradient(135deg, #fff8f0, #fff3e0);
  border-bottom: 1px solid #ffe0cc;
  padding: 2rem 0;
}

.recipe-hero.cat-soups    { background: linear-gradient(135deg, #fff8e1, #fff3cd); }
.recipe-hero.cat-mains    { background: linear-gradient(135deg, #fff0f0, #ffe4e4); }
.recipe-hero.cat-salads   { background: linear-gradient(135deg, #f0fff4, #dcffe4); }
.recipe-hero.cat-pastries { background: linear-gradient(135deg, #fffbf0, #fff3cd); }
.recipe-hero.cat-desserts { background: linear-gradient(135deg, #fdf0ff, #f3e5f5); }
.recipe-hero.cat-drinks   { background: linear-gradient(135deg, #f0f8ff, #e3f2fd); }
.recipe-hero.cat-shabbat  { background: linear-gradient(135deg, #fdf6ff, #f3e5f5); }
.recipe-hero.cat-other    { background: linear-gradient(135deg, #f8f9fa, #f1f3f5); }

.hero-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  color: #8aa0c2;
  text-decoration: none;
  font-size: 0.88rem;
  margin-bottom: 1rem;
  transition: color 0.2s;
}
.back-link:hover { color: #ff6b35; }

.hero-content {
  display: flex;
  align-items: flex-start;
  gap: 2rem;
  justify-content: space-between;
}

.hero-text { flex: 1; }

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.cat-badge {
  background: rgba(255,107,53,0.15);
  color: #c05621;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.25rem 0.7rem;
  border-radius: 20px;
}

.badge-special {
  background: white;
  color: #4a5568;
  font-size: 0.82rem;
  padding: 0.25rem 0.7rem;
  border-radius: 20px;
  border: 1px solid #e0eaf8;
}

h1 {
  font-size: 2.2rem;
  font-weight: 700;
  color: #1a3a6b;
  margin: 0 0 1rem;
  line-height: 1.2;
}

.recipe-owner {
  margin-bottom: 0.5rem;
}

.owner-link {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  background: white;
  padding: 0.6rem 1rem;
  border-radius: 12px;
  border: 1px solid #e0eaf8;
  transition: all 0.2s;
}
.owner-link:hover { border-color: #ff6b35; box-shadow: 0 2px 8px rgba(255,107,53,0.15); }

.owner-icon { font-size: 1.5rem; }
.owner-name { display: block; font-weight: 600; color: #1a3a6b; font-size: 0.95rem; }
.owner-context { display: block; color: #8aa0c2; font-size: 0.8rem; }

.recipe-owner-plain {
  color: #5a6d8a;
  font-size: 0.9rem;
}

.hero-image {
  width: 260px;
  height: 200px;
  border-radius: 16px;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 4px 20px rgba(0,50,150,0.1);
}

.hero-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-emoji {
  font-size: 5rem;
  line-height: 1;
  flex-shrink: 0;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1.25rem;
  flex-wrap: wrap;
}

.btn-wakelock {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: white;
  border: 1.5px solid #e0eaf8;
  color: #4a5568;
  padding: 0.5rem 1.1rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
}
.btn-wakelock:hover { border-color: #f59e0b; color: #f59e0b; }
.btn-wakelock.active {
  background: #fffbeb;
  border-color: #f59e0b;
  color: #b45309;
}

.btn-copy {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: white;
  border: 1.5px solid #e0eaf8;
  color: #4a5568;
  padding: 0.5rem 1.1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
}
.btn-copy:hover { border-color: #ff6b35; color: #ff6b35; }

.btn-edit {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #edf3ff;
  color: #2d6be4;
  border: none;
  padding: 0.5rem 1.1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-family: 'Rubik', sans-serif;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
}
.btn-edit:hover { background: #dde8ff; }

.btn-delete {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #fff5f5;
  color: #e74c3c;
  border: 1px solid #fed7d7;
  padding: 0.5rem 1.1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}
.btn-delete:hover { background: #ffe4e4; }

/* Body */
.recipe-body {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.recipe-main {
  display: grid;
  grid-template-columns: 1fr 1.6fr;
  gap: 1.5rem;
  align-items: start;
}

.recipe-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(0,50,150,0.05);
  border: 1px solid #f0f4fb;
}

.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #f7f0e8;
}

.recipe-section h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a3a6b;
  margin: 0;
}

.btn-reset-checks {
  background: none;
  border: 1px solid #e0eaf8;
  color: #8aa0c2;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-reset-checks:hover { border-color: #ff6b35; color: #ff6b35; }

/* Ingredients */
.ingredients-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ingredients-list li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.75rem;
  background: #fafcff;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #2d3748;
  border-right: 3px solid #ffd4c2;
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
}

.ingredients-list li:hover { background: #f0f6ff; }

.ingredients-list li.item-checked {
  opacity: 0.45;
  text-decoration: line-through;
  background: #f8fdf8;
  border-right-color: #68d391;
}

.check-box {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  border: 2px solid #cbd5e0;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  background: white;
}

.check-box.done {
  background: #38a169;
  border-color: #38a169;
}

.check-box svg {
  width: 12px;
  height: 12px;
}

/* Steps */
.steps-list {
  padding: 0;
  margin: 0;
  counter-reset: steps;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.steps-list li {
  padding: 0.75rem 1rem 0.75rem 3rem;
  background: #fafcff;
  border-radius: 10px;
  font-size: 0.95rem;
  color: #2d3748;
  line-height: 1.6;
  position: relative;
  counter-increment: steps;
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
}

.steps-list li:hover { background: #f0f6ff; }

.steps-list li.item-checked {
  opacity: 0.45;
  background: #f8fdf8;
}

.steps-list li.item-checked::before {
  content: '✓';
  background: #38a169;
  font-size: 0.85rem;
}

.steps-list li::before {
  content: counter(steps);
  position: absolute;
  right: 0.75rem;
  top: 0.75rem;
  width: 1.6rem;
  height: 1.6rem;
  background: #ff6b35;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

/* Adaptations */
.section-hint {
  color: #8aa0c2;
  font-size: 0.85rem;
  margin: -0.5rem 0 1rem;
}

.my-adaptation {
  background: #fff8f0;
  border: 1.5px dashed #ffd4c2;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.my-adaptation h3 {
  font-size: 0.9rem;
  color: #c05621;
  margin: 0 0 0.5rem;
  font-weight: 600;
}

.adaptation-input,
.comment-input {
  width: 100%;
  padding: 0.65rem 0.9rem;
  border: 1.5px solid #e0eaf8;
  border-radius: 10px;
  font-size: 0.9rem;
  font-family: 'Rubik', sans-serif;
  color: #2d3748;
  resize: vertical;
  transition: border-color 0.2s;
  box-sizing: border-box;
  display: block;
}

.adaptation-input:focus,
.comment-input:focus {
  outline: none;
  border-color: #ff6b35;
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
}

.btn-save-adaptation {
  margin-top: 0.6rem;
  background: #ff6b35;
  color: white;
  border: none;
  padding: 0.45rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-save-adaptation:hover:not(:disabled) { background: #e55a24; }
.btn-save-adaptation:disabled { opacity: 0.6; cursor: not-allowed; }

.adaptations-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.adaptation-item {
  background: #f8faff;
  border-radius: 12px;
  padding: 0.9rem 1rem;
  border-right: 3px solid #c8d8f0;
}

.adaptation-item.mine {
  background: #fff8f0;
  border-right-color: #ff6b35;
}

.adaptation-header { margin-bottom: 0.35rem; }
.adaptation-user { font-size: 0.82rem; font-weight: 700; color: #4a5568; }
.adaptation-text { margin: 0; font-size: 0.9rem; color: #2d3748; line-height: 1.6; }

/* Comments */
.comment-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.btn-comment {
  align-self: flex-start;
  background: #2d6be4;
  color: white;
  border: none;
  padding: 0.45rem 1.2rem;
  border-radius: 8px;
  font-size: 0.88rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-comment:hover:not(:disabled) { background: #1a55c8; }
.btn-comment:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-comment.btn-sm { padding: 0.35rem 0.9rem; font-size: 0.82rem; }

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.comment-thread {}

.comment {
  background: #f8faff;
  border-radius: 12px;
  padding: 0.9rem 1rem;
}

.comment.reply {
  background: #fafcff;
  border-right: 3px solid #e0eaf8;
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.4rem;
}

.comment-user { font-size: 0.85rem; font-weight: 700; color: #1a3a6b; }
.comment-time { font-size: 0.78rem; color: #8aa0c2; }
.comment-text { margin: 0 0 0.5rem; font-size: 0.92rem; color: #2d3748; line-height: 1.6; }

.comment-actions {
  display: flex;
  gap: 0.75rem;
}

.reply-btn, .delete-comment-btn {
  background: none;
  border: none;
  font-size: 0.8rem;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  padding: 0;
  transition: color 0.2s;
}
.reply-btn { color: #8aa0c2; }
.reply-btn:hover { color: #2d6be4; }
.delete-comment-btn { color: #c8d0db; }
.delete-comment-btn:hover { color: #e74c3c; }

.reply-form {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #edf0f7;
}

.reply-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
  align-items: center;
}

.btn-cancel-reply {
  background: none;
  border: none;
  font-size: 0.82rem;
  color: #8aa0c2;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
}

.replies {
  margin-top: 0.5rem;
  padding-right: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .recipe-main { grid-template-columns: 1fr; }
  h1 { font-size: 1.6rem; }
  .hero-image { width: 100%; max-width: 100%; height: 200px; }
  .hero-content { flex-direction: column; }
  .hero-emoji { font-size: 3.5rem; }
}
</style>
