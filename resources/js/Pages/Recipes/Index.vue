<template>
  <AppLayout>
    <div class="recipes-page" dir="rtl">

      <!-- Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title">
            <span class="header-icon">🍽️</span>
            <div>
              <h1>מתכוני המשפחה</h1>
              <p class="header-sub">{{ recipes.length }} מתכונים שמורים</p>
            </div>
          </div>
          <Link href="/recipes/create" class="btn-add">
            <span>+</span> מתכון חדש
          </Link>
        </div>

        <!-- Category filters -->
        <div class="category-filters">
          <button
            :class="['cat-btn', { active: activeCategories.length === 0 }]"
            @click="clearFilters"
          >🍴 הכל</button>
          <button
            v-for="cat in categoryOptions"
            :key="cat"
            :class="['cat-btn', { active: activeCategories.includes(cat) }]"
            @click="toggleCategory(cat)"
          >
            <span>{{ getCategoryEmoji(cat) }}</span> {{ cat }}
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="recipes.length === 0" class="empty-state">
        <div class="empty-icon">🥘</div>
        <h3>אין מתכונים עדיין</h3>
        <p>היו הראשונים להוסיף מתכון משפחתי!</p>
        <Link href="/recipes/create" class="btn-add">+ הוסף מתכון</Link>
      </div>

      <!-- Recipe grid -->
      <div v-else class="recipes-grid">
        <Link
          v-for="recipe in recipes"
          :key="recipe.id"
          :href="`/recipes/${recipe.id}`"
          class="recipe-card"
        >
          <!-- Image or placeholder -->
          <div class="card-image" :class="{ 'has-image': recipe.image_url }">
            <img v-if="recipe.image_url" :src="recipe.image_url" :alt="recipe.title" />
            <span v-else class="card-emoji">{{ getCategoryEmoji(recipe.category) }}</span>
            <div class="card-badges">
              <span v-if="recipe.is_favorite" class="badge badge-fav">❤️ מועדף</span>
              <span v-if="recipe.is_gluten_free" class="badge badge-gf">🌾 ללא גלוטן</span>
            </div>
          </div>

          <div class="card-body">
            <div class="card-meta">
              <span
                v-for="cat in splitCategories(recipe.category)"
                :key="cat"
                class="cat-tag"
                :style="{ background: getCategoryColor(cat) }"
              >
                {{ cat }}
              </span>
              <span v-if="recipe.quantity" class="quantity">{{ recipe.quantity }}</span>
            </div>

            <h3 class="card-title">{{ recipe.title }}</h3>

            <div class="card-author">
              <span v-if="recipe.person_name" class="author-person">
                🧑‍🍳 {{ recipe.person_name }}
                <span v-if="recipe.person_context" class="author-context">{{ recipe.person_context }}</span>
              </span>
              <span v-else-if="recipe.owner_text" class="author-user">🧑‍🍳 {{ recipe.owner_text }}</span>
            </div>

            <div class="card-footer">
              <span class="comments-count">💬 {{ recipe.comments_count }} תגובות</span>
              <span v-if="recipe.can_edit" class="edit-hint">עריכה ▸</span>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  recipes: Array,
  activeCategories: {
    type: Array,
    default: () => [],
  },
  categoryOptions: Array,
})

const catEmojis = {
  'מרקים': '🍲', 'שבת': '✨', 'עוגות': '🎂', 'עוגיות': '🍪',
  'מושקע': '👨‍🍳', 'פודי': '🍽️', 'פחמימה': '🍞', 'לראש השנה': '🍎',
  'חלבי': '🧀', 'לפסח': '🫓', 'לחנוכה': '🕎', 'בשרי': '🥩',
  'ירקות': '🥦', 'לשבועות': '🌸', 'סלטים': '🥗', 'לפורים': '🎭',
  'ליום העצמאות': '🇮🇱', 'שתייה': '🥤', 'כללי': '✨',
}

const catColors = {
  'מרקים': '#fff3e0', 'שבת': '#fdf6ff', 'עוגות': '#fce4ec',
  'עוגיות': '#fff8e1', 'מושקע': '#fce4ec', 'פודי': '#f1f8e9',
  'פחמימה': '#fff3e0', 'לראש השנה': '#fff8e1', 'חלבי': '#e3f2fd',
  'לפסח': '#f9fbe7', 'לחנוכה': '#fff8e1', 'בשרי': '#fce4ec',
  'ירקות': '#e8f5e9', 'לשבועות': '#fce4ec', 'סלטים': '#e8f5e9',
}

function splitCategories(cat) {
  return cat ? cat.split(',').map(c => c.trim()).filter(Boolean) : []
}

function getCategoryColor(cat) {
  return catColors[cat] || '#f5f5f5'
}

function getCategoryEmoji(cat) {
  return catEmojis[cat] || '🍴'
}

function clearFilters() {
  router.get('/recipes', {}, { preserveState: true })
}

function toggleCategory(cat) {
  const current = [...props.activeCategories]
  const idx = current.indexOf(cat)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    current.push(cat)
  }
  router.get('/recipes', current.length ? { categories: current } : {}, { preserveState: true })
}
</script>

<style scoped>
.recipes-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

/* Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  gap: 1rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  font-size: 2.5rem;
  line-height: 1;
}

h1 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1a3a6b;
  margin: 0;
}

.header-sub {
  color: #8aa0c2;
  font-size: 0.9rem;
  margin: 0.15rem 0 0;
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #ff6b35, #f7c59f);
  color: white;
  text-decoration: none;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(255,107,53,0.3);
}

.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(255,107,53,0.4);
}

/* Category filters */
.category-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.cat-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.9rem;
  border: 2px solid #e0eaf8;
  border-radius: 20px;
  background: white;
  color: #4a5568;
  font-size: 0.88rem;
  font-family: 'Rubik', sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
}

.cat-btn:hover {
  border-color: #ff6b35;
  color: #ff6b35;
}

.cat-btn.active {
  background: #ff6b35;
  border-color: #ff6b35;
  color: white;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  border: 2px dashed #e0eaf8;
}

.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state h3 { color: #1a3a6b; margin: 0 0 0.5rem; }
.empty-state p { color: #8aa0c2; margin: 0 0 1.5rem; }

/* Grid */
.recipes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 1rem;
}

/* Recipe card */
.recipe-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  text-decoration: none;
  box-shadow: 0 2px 12px rgba(0,50,150,0.06);
  border: 1px solid #f0f4fb;
  transition: all 0.25s;
  display: flex;
  flex-direction: column;
}

.recipe-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 28px rgba(0,50,150,0.12);
  border-color: #ffd4c2;
}

/* Card image */
.card-image {
  height: 140px;
  background: linear-gradient(135deg, #fff3e0, #fce4ec);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.card-image.has-image {
  background: #f0f0f0;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-emoji {
  font-size: 3.5rem;
  line-height: 1;
}

.card-badges {
  position: absolute;
  top: 0.6rem;
  right: 0.6rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.badge {
  font-size: 0.72rem;
  padding: 0.2rem 0.5rem;
  border-radius: 20px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-fav { background: rgba(255,255,255,0.9); color: #e53e3e; }
.badge-gf  { background: rgba(255,255,255,0.9); color: #38a169; }

/* Card body */
.card-body {
  padding: 0.7rem 0.9rem 0.9rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.cat-tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  color: #4a5568;
}

.quantity {
  font-size: 0.78rem;
  color: #8aa0c2;
}

.card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1a3a6b;
  margin: 0;
  line-height: 1.3;
}

.card-author {
  font-size: 0.83rem;
  color: #5a6d8a;
}

.author-person {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.author-context {
  color: #8aa0c2;
  font-size: 0.78rem;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 0.5rem;
  border-top: 1px solid #f0f4fb;
}

.comments-count {
  font-size: 0.8rem;
  color: #8aa0c2;
}

.edit-hint {
  font-size: 0.78rem;
  color: #ff6b35;
  font-weight: 500;
}

@media (max-width: 640px) {
  .recipes-grid { grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
  h1 { font-size: 1.4rem; }
  .header-content { flex-wrap: wrap; }
}
</style>
