<template>
  <AppLayout :title="event.title">
    <div class="event-show" dir="rtl">
      <div class="top-bar">
        <Link href="/events" class="btn-back">← חזרה ללוח</Link>
        <div v-if="event.can_edit" class="top-actions">
          <Link :href="`/events/${event.id}/edit`" class="btn-edit">עריכה</Link>
          <button class="btn-delete" @click="destroy">מחיקה</button>
        </div>
      </div>

      <div class="event-card">
        <!-- תמונת הזמנה -->
        <div v-if="event.invitation_image" class="invite-image">
          <img :src="event.invitation_image" :alt="event.title" />
        </div>

        <div class="event-body">
          <span class="type-badge">{{ typeLabel }}</span>
          <h1>{{ event.title }}</h1>

          <Link v-if="event.person_url" :href="event.person_url" class="person-link">
            👤 {{ event.person_name }}
          </Link>

          <!-- תאריך ושעה -->
          <div class="detail-rows">
            <div class="detail" v-if="event.hebrew_date || event.event_date">
              <span class="detail-icon">📅</span>
              <span>
                <strong v-if="event.hebrew_date">{{ event.hebrew_date }}</strong>
                <span v-if="gregLabel" class="greg">{{ gregLabel }}</span>
              </span>
            </div>
            <div class="detail" v-if="event.event_time">
              <span class="detail-icon">🕐</span>
              <span>{{ event.event_time }}</span>
            </div>
            <div class="detail" v-if="event.location">
              <span class="detail-icon">📍</span>
              <span>{{ event.location }}</span>
            </div>
          </div>

          <!-- קהל יעד -->
          <div v-if="event.audience.length || event.audience_branch" class="audience-box">
            <div class="audience-title">למי מיועד</div>
            <div class="chips">
              <span v-for="(a, i) in event.audience" :key="i" class="chip">{{ a }}</span>
              <span v-if="event.audience_branch" class="chip chip-branch">
                צאצאי {{ event.audience_branch.person_name }} ({{ event.audience_branch.count }})
              </span>
            </div>
            <div v-if="event.audience_branch && event.audience_branch.names.length" class="branch-names">
              {{ event.audience_branch.names.join('، ') }}
            </div>
          </div>

          <!-- תיאור -->
          <p v-if="event.description" class="description">{{ event.description }}</p>

          <!-- קישור לתמונות -->
          <a v-if="event.photos_link" :href="event.photos_link" target="_blank" rel="noopener" class="btn-photos">
            🖼️ לתמונות ב-Google Photos
          </a>
        </div>
      </div>

      <!-- תגובות פרטיות -->
      <div class="reactions-card">
        <div class="reactions-header">
          <h2>תגובות וברכות</h2>
          <span class="privacy-note">🔒 נראה רק כאן, למי שפותח את האירוע</span>
        </div>

        <!-- אימוג'ים -->
        <div class="emoji-bar">
          <button
            v-for="emo in palette"
            :key="emo"
            type="button"
            class="emoji-btn"
            :class="{ mine: isMine(emo) }"
            @click="toggleReaction(emo)"
          >
            <span class="emo">{{ emo }}</span>
            <span v-if="countFor(emo)" class="emo-count">{{ countFor(emo) }}</span>
          </button>
        </div>

        <!-- ברכות -->
        <div class="blessings">
          <div v-for="b in blessings" :key="b.id" class="blessing">
            <div class="blessing-head">
              <span class="blessing-user">{{ b.user }}</span>
              <span class="blessing-date">{{ b.date }}</span>
            </div>
            <p class="blessing-msg">{{ b.message }}</p>
          </div>
          <p v-if="!blessings.length" class="no-blessings">עדיין אין ברכות — הוסיפו ברכה חמודה 💛</p>
        </div>

        <!-- הוספת ברכה -->
        <form @submit.prevent="submitBlessing" class="add-blessing">
          <input v-model="blessingForm.message" type="text" placeholder="כתבו ברכה..." maxlength="1000" />
          <button type="submit" :disabled="blessingForm.processing || !blessingForm.message.trim()">שלח</button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  event: { type: Object, required: true },
  blessings: { type: Array, default: () => [] },
  reactions: { type: Array, default: () => [] },
})

const TYPE_LABELS = {
  bar_mitzvah: 'בר מצווה', bat_mitzvah: 'בת מצווה', wedding: 'חתונה',
  birth: 'לידה', death: 'אזכרה', other: 'אירוע',
}
const typeLabel = computed(() => TYPE_LABELS[props.event.type] || 'אירוע')

const palette = ['❤️', '🎉', '😍', '👏', '🥳', '🙏', '🌸', '✨']

const gregLabel = computed(() => {
  if (!props.event.event_date) return ''
  const [y, m, d] = props.event.event_date.split('-')
  return `${d}.${m}.${y}`
})

function reactionFor(emoji) {
  return props.reactions.find(r => r.emoji === emoji)
}
function countFor(emoji) {
  return reactionFor(emoji)?.count || 0
}
function isMine(emoji) {
  return reactionFor(emoji)?.mine || false
}

function toggleReaction(emoji) {
  router.post(`/events/${props.event.id}/reactions`, { emoji }, {
    preserveScroll: true,
  })
}

const blessingForm = useForm({ message: '' })
function submitBlessing() {
  blessingForm.post(`/events/${props.event.id}/blessings`, {
    preserveScroll: true,
    onSuccess: () => blessingForm.reset('message'),
  })
}

function destroy() {
  if (confirm('למחוק את האירוע?')) {
    router.delete(`/events/${props.event.id}`)
  }
}
</script>

<style scoped>
.event-show {
  max-width: 720px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
  font-family: 'Rubik', sans-serif;
}

.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.btn-back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; }
.top-actions { display: flex; gap: 0.5rem; }
.btn-edit {
  color: #2d6be4; text-decoration: none; font-size: 0.85rem;
  border: 1.5px solid #d1dce8; padding: 0.35rem 0.9rem; border-radius: 8px;
}
.btn-edit:hover { background: #edf3ff; }
.btn-delete {
  color: #e74c3c; background: none; border: 1.5px solid #f3c0bb;
  padding: 0.35rem 0.9rem; border-radius: 8px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.85rem;
}
.btn-delete:hover { background: #fdecea; }

.event-card {
  background: white;
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(0,50,150,0.08);
  overflow: hidden;
}

.invite-image { background: #faf6ef; text-align: center; }
.invite-image img { max-width: 100%; max-height: 520px; display: block; margin: 0 auto; }

.event-body { padding: 1.75rem; }

.type-badge {
  display: inline-block;
  background: #fdf1e0; color: #8a5a2b;
  font-size: 0.78rem; font-weight: 600;
  padding: 0.2rem 0.7rem; border-radius: 20px;
}

h1 { font-size: 1.6rem; color: #1a3a6b; margin: 0.6rem 0 0.5rem; }

.person-link {
  display: inline-block; color: #2d6be4; text-decoration: none;
  font-size: 0.95rem; margin-bottom: 1rem;
}
.person-link:hover { text-decoration: underline; }

.detail-rows { display: flex; flex-direction: column; gap: 0.5rem; margin: 1rem 0; }
.detail { display: flex; gap: 0.6rem; align-items: center; color: #3a4a63; font-size: 0.98rem; }
.detail-icon { font-size: 1.1rem; }
.detail .greg { color: #8a9ab5; font-size: 0.88rem; margin-right: 0.5rem; }

.audience-box {
  background: #f8faff; border: 1px solid #e0eaf8;
  border-radius: 12px; padding: 1rem; margin: 1rem 0;
}
.audience-title { font-size: 0.8rem; color: #6b7a99; font-weight: 600; margin-bottom: 0.5rem; }
.chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip {
  background: #e8f0fe; color: #1a3a6b;
  border-radius: 20px; padding: 0.3rem 0.8rem; font-size: 0.85rem;
}
.chip-branch { background: #fdf1e0; color: #8a5a2b; }
.branch-names { font-size: 0.82rem; color: #8a9ab5; margin-top: 0.5rem; }

.description { color: #3a4a63; line-height: 1.6; white-space: pre-line; margin: 1rem 0; }

.btn-photos {
  display: inline-block;
  background: #2d6be4; color: white; text-decoration: none;
  padding: 0.6rem 1.4rem; border-radius: 10px; font-weight: 600;
  font-size: 0.95rem; margin-top: 0.5rem;
}
.btn-photos:hover { background: #1a55c8; }

/* תגובות */
.reactions-card {
  background: white;
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(0,50,150,0.08);
  padding: 1.5rem;
  margin-top: 1.5rem;
}
.reactions-header { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; }
.reactions-header h2 { font-size: 1.15rem; color: #1a3a6b; margin: 0; }
.privacy-note { font-size: 0.78rem; color: #8a9ab5; }

.emoji-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0 1.5rem; }
.emoji-btn {
  display: flex; align-items: center; gap: 0.3rem;
  background: #f4f8ff; border: 1.5px solid transparent;
  border-radius: 22px; padding: 0.35rem 0.7rem; cursor: pointer;
  font-size: 1.1rem; transition: all 0.15s;
}
.emoji-btn:hover { background: #e8f0fe; }
.emoji-btn.mine { background: #e8f0fe; border-color: #2d6be4; }
.emo-count { font-size: 0.85rem; color: #2d6be4; font-weight: 600; }

.blessings { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
.blessing { background: #faf6ef; border-radius: 12px; padding: 0.8rem 1rem; }
.blessing-head { display: flex; justify-content: space-between; margin-bottom: 0.25rem; }
.blessing-user { font-weight: 600; color: #1a3a6b; font-size: 0.9rem; }
.blessing-date { font-size: 0.78rem; color: #b0a890; }
.blessing-msg { margin: 0; color: #3a4a63; font-size: 0.95rem; }
.no-blessings { text-align: center; color: #8a9ab5; font-size: 0.9rem; padding: 1rem; }

.add-blessing { display: flex; gap: 0.5rem; }
.add-blessing input {
  flex: 1; padding: 0.6rem 0.85rem;
  border: 1.5px solid #d1dce8; border-radius: 10px;
  font-family: 'Rubik', sans-serif; font-size: 0.95rem; direction: rtl;
}
.add-blessing input:focus { outline: none; border-color: #2d6be4; }
.add-blessing button {
  background: #2d6be4; color: white; border: none;
  padding: 0.6rem 1.4rem; border-radius: 10px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-weight: 600;
}
.add-blessing button:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
