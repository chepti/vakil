<template>
  <AppLayout>
    <div class="stories-page" dir="rtl">

      <!-- Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title">
            <span class="header-icon">📜</span>
            <div>
              <h1>למה קראו לי בשמי</h1>
              <p class="header-sub">{{ stories.length }} סיפורי שמות במשפחה</p>
            </div>
          </div>
          <Link href="/name-stories/create" class="btn-add">
            <span>+</span> סיפור חדש
          </Link>
        </div>
        <p class="page-intro">
          לכל שם יש סיפור — על שם מי נקראנו, מה המשמעות, ומה עמד מאחורי הבחירה.
          כאן אוספים את הסיפורים הקטנים שמאחורי השמות שלנו.
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="stories.length === 0" class="empty-state">
        <div class="empty-icon">✍️</div>
        <h3>אין עדיין סיפורי שמות</h3>
        <p>היו הראשונים לספר על שם במשפחה!</p>
        <Link href="/name-stories/create" class="btn-add">+ הוסף סיפור</Link>
      </div>

      <!-- Stories grid -->
      <div v-else class="stories-grid">
        <div
          v-for="story in stories"
          :key="story.id"
          class="story-card"
        >
          <div class="card-top">
            <Link :href="`/people/${story.person_id}`" class="person-link">
              <div class="person-avatar" :class="story.person_gender">
                <img v-if="story.person_photo" :src="story.person_photo" :alt="story.person_name" />
                <span v-else class="avatar-initials">{{ initials(story.person_name) }}</span>
              </div>
              <div class="person-meta">
                <span class="person-name">{{ story.person_name }}</span>
                <span v-if="story.person_context" class="person-context">{{ story.person_context }}</span>
              </div>
            </Link>
            <Link
              v-if="story.can_edit"
              :href="`/name-stories/${story.id}/edit`"
              class="edit-btn"
              title="עריכה"
            >✏️</Link>
          </div>

          <div class="card-quote">
            <span class="quote-mark">”</span>
            <div class="story-text" v-html="renderRichText(story.content)"></div>
          </div>

          <div class="card-footer">
            <span class="added-by">נכתב ע״י {{ story.created_by_name }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { renderRichText } from '@/utils/richText'

defineProps({
  stories: { type: Array, default: () => [] },
})

function initials(name) {
  if (!name) return '?'
  return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('')
}
</script>

<style scoped>
.stories-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

/* Header */
.page-header { margin-bottom: 2rem; }

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  gap: 1rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon { font-size: 2.5rem; line-height: 1; }

h1 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #4a2e83;
  margin: 0;
}

.header-sub {
  color: #a08ac9;
  font-size: 0.9rem;
  margin: 0.15rem 0 0;
}

.page-intro {
  color: #6b6382;
  font-size: 0.95rem;
  line-height: 1.6;
  max-width: 640px;
  margin: 0;
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #8b5cf6, #b794f6);
  color: white;
  text-decoration: none;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(139,92,246,0.3);
}

.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(139,92,246,0.4);
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  border: 2px dashed #e5ddf5;
}

.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state h3 { color: #4a2e83; margin: 0 0 0.5rem; }
.empty-state p { color: #a08ac9; margin: 0 0 1.5rem; }

/* Grid */
.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

/* Story card */
.story-card {
  background: white;
  border-radius: 18px;
  padding: 1.25rem;
  box-shadow: 0 2px 14px rgba(74,46,131,0.07);
  border: 1px solid #f0ebfa;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  transition: all 0.25s;
}

.story-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(74,46,131,0.13);
  border-color: #e0d4f7;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.person-link {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  text-decoration: none;
  min-width: 0;
}

.person-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #6d28d9;
  font-weight: 700;
  font-size: 1rem;
}

.person-avatar.female { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #be185d; }
.person-avatar.male   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }

.person-avatar img { width: 100%; height: 100%; object-fit: cover; }

.person-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.person-name {
  font-weight: 700;
  color: #3b2b63;
  font-size: 1.02rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.person-context {
  color: #a08ac9;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.edit-btn {
  flex-shrink: 0;
  text-decoration: none;
  font-size: 1rem;
  padding: 0.35rem 0.5rem;
  border-radius: 8px;
  transition: background 0.2s;
  opacity: 0.6;
}
.edit-btn:hover { background: #f3eefe; opacity: 1; }

/* Quote body */
.card-quote {
  position: relative;
  background: linear-gradient(135deg, #faf8ff, #f5f0ff);
  border-radius: 14px;
  padding: 1rem 1.1rem 1rem 1.1rem;
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

.story-text {
  color: #4a4362;
  font-size: 0.96rem;
  line-height: 1.75;
  white-space: normal;
  padding-top: 0.35rem;
}

.story-text :deep(strong) { color: #5b21b6; font-weight: 700; }

.card-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.added-by {
  font-size: 0.78rem;
  color: #b0a4cc;
}

@media (max-width: 640px) {
  .stories-grid { grid-template-columns: 1fr; }
  h1 { font-size: 1.4rem; }
  .header-content { flex-wrap: wrap; }
}
</style>
