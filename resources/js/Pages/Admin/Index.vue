<template>
  <AppLayout title="ניהול">
    <div class="admin-page" dir="rtl">
      <div class="page-header">
        <h1>⚙️ פאנל ניהול</h1>
        <p class="subtitle">ניהול משתמשים, הזמנות, מסמכים והורדות מידע</p>
      </div>

      <!-- כרטיסי סיכום -->
      <div class="summary-cards">
        <div class="sum-card"><b>{{ summary.people_total }}</b><span>דמויות בעץ</span></div>
        <div class="sum-card"><b>{{ summary.users_total }}</b><span>משתמשי אתר</span></div>
        <div class="sum-card" :class="{ warn: summary.invites_expired }"><b>{{ summary.invites_open }}</b><span>הזמנות פתוחות</span></div>
        <div class="sum-card" :class="{ warn: summary.missing_bday }"><b>{{ summary.missing_bday }}</b><span>חסרי יום הולדת</span></div>
        <div class="sum-card" :class="{ warn: summary.missing_photo }"><b>{{ summary.missing_photo }}</b><span>חסרי תמונה</span></div>
      </div>

      <!-- הורדות -->
      <section class="panel">
        <h2>📥 הורדת מידע מרוכז</h2>
        <p class="hint">קבצי CSV נפתחים באקסל / גוגל שיטס (תומך עברית).</p>
        <div class="download-row">
          <a href="/admin/export/family" class="dl-btn">👨‍👩‍👧 רשימת כל המשפחה</a>
          <a href="/admin/export/birthdays" class="dl-btn">🎂 ימי הולדת — כל השנה</a>
          <a href="/admin/export/users" class="dl-btn">👥 רשימת משתמשים</a>
          <Link href="/print/tree" class="dl-btn print">🖨️ הדפסת עץ ל-PDF</Link>
        </div>
      </section>

      <!-- שליחת עדכון חודשי -->
      <section class="panel">
        <h2>📬 עדכון חודשי במייל</h2>
        <p class="hint">שלחו את הדיגסט החודשי לכולם, או קבלו תצוגה מקדימה לעצמכם.</p>
        <div v-if="digestMessage" class="digest-msg">✓ {{ digestMessage }}</div>
        <div class="download-row">
          <button class="dl-btn" :disabled="digestSending" @click="sendDigestPreview">
            {{ digestSending ? '...שולח' : '👁️ שלח לי תצוגה מקדימה' }}
          </button>
          <button class="dl-btn dl-btn-send" :disabled="digestSending" @click="sendDigestAll">
            {{ digestSending ? '...שולח' : '📤 שלח לכולם עכשיו' }}
          </button>
        </div>
      </section>

      <!-- שליחת הודעה מותאמת -->
      <section class="panel">
        <div class="custom-header" @click="customOpen = !customOpen">
          <h2 style="margin:0;">✉️ שליחת הודעה מותאמת</h2>
          <span class="custom-toggle">{{ customOpen ? '▲ סגור' : '▼ פתח' }}</span>
        </div>

        <Transition name="slide">
          <div v-if="customOpen" class="custom-form">
            <div v-if="digestMessage" class="digest-msg">✓ {{ digestMessage }}</div>

            <div class="custom-field">
              <label class="custom-label">נושא המייל</label>
              <input v-model="customForm.subject" type="text" class="custom-input"
                placeholder="לדוג׳: אירוע משפחתי חשוב 🌟" maxlength="200" />
            </div>

            <div class="custom-field">
              <label class="custom-label">תוכן ההודעה</label>
              <textarea v-model="customForm.body" class="custom-textarea" rows="6"
                placeholder="כתבו כאן את תוכן ההודעה החופשית...&#10;&#10;ניתן לכתוב בפסקאות — ירדפות שורה ישמרו."></textarea>
              <div class="custom-count">{{ customForm.body.length }} / 6000</div>
            </div>

            <div class="custom-field">
              <label class="custom-label">שלח אל</label>
              <select v-model="customForm.branch_person_id" class="custom-input">
                <option :value="null">👥 כל המשתמשים הפעילים</option>
                <optgroup label="לפי ענף — צאצאי:">
                  <option v-for="p in people" :key="p.id" :value="p.id">🌿 ענף {{ p.name }}</option>
                </optgroup>
              </select>
            </div>

            <div class="custom-actions">
              <button class="pf-btn"
                :disabled="customSending || !customForm.subject.trim() || !customForm.body.trim()"
                @click="sendCustom">
                {{ customSending ? '...שולח' : '📤 שלח עכשיו' }}
              </button>
              <span class="custom-hint">
                {{ customForm.branch_person_id ? 'לצאצאי הדמות הנבחרת בלבד' : 'לכל המשתמשים הפעילים' }}
              </span>
            </div>
          </div>
        </Transition>
      </section>

      <!-- מסמכים -->
      <section class="panel">
        <h2>📄 מסמכים משותפים</h2>
        <form @submit.prevent="uploadDoc" class="doc-form">
          <input v-model="docForm.title" type="text" placeholder="כותרת המסמך" required />
          <input ref="fileInput" type="file" @change="onFile" required />
          <button type="submit" :disabled="docForm.processing">העלה</button>
        </form>
        <p v-if="docForm.errors.file" class="err">{{ docForm.errors.file }}</p>
        <ul class="doc-list" v-if="documents.length">
          <li v-for="d in documents" :key="d.id">
            <a :href="d.url" target="_blank" class="doc-link">📎 {{ d.title }}</a>
            <span class="doc-meta">{{ d.uploaded }}</span>
            <button class="link-del" @click="deleteDoc(d)">מחק</button>
          </li>
        </ul>
        <p v-else class="empty">אין מסמכים עדיין</p>
      </section>

      <!-- חסרי יום הולדת -->
      <section class="panel" v-if="missingBirthday.length">
        <h2>🎂 חסרי תאריך לידה ({{ missingBirthday.length }})</h2>
        <div class="chip-grid">
          <Link v-for="p in missingBirthday" :key="p.id" :href="`/people/${p.id}/edit`" class="chip" :class="{ deceased: p.is_deceased }">
            {{ p.full_name }}<span v-if="p.is_deceased"> ז"ל</span> ✏️
          </Link>
        </div>
      </section>

      <!-- חסרי תמונה -->
      <section class="panel" v-if="missingPhoto.length">
        <h2>🖼️ חסרי תמונת פרופיל ({{ missingPhoto.length }})</h2>
        <div class="chip-grid">
          <Link v-for="p in missingPhoto" :key="p.id" :href="`/people/${p.id}/edit`" class="chip">
            {{ p.full_name }} ✏️
          </Link>
        </div>
      </section>

      <!-- הזמנות -->
      <section class="panel" v-if="invitations.length">
        <h2>✉️ הזמנות פתוחות</h2>
        <table class="data-table">
          <thead><tr><th>מייל</th><th>דמות</th><th>הזמין</th><th>תוקף עד</th><th></th></tr></thead>
          <tbody>
            <tr v-for="inv in invitations" :key="inv.id" :class="{ expired: inv.expired }">
              <td>{{ inv.email }}</td>
              <td>{{ inv.person || '—' }}</td>
              <td>{{ inv.invited_by || '—' }}</td>
              <td>
                {{ inv.expires_at }}
                <span v-if="inv.expired" class="badge-expired">פג תוקף</span>
              </td>
              <td class="actions">
                <button class="mini-btn" @click="extendInvite(inv)">הארך 30 יום</button>
                <button class="mini-btn del" @click="deleteInvite(inv)">מחק</button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- משתמשים -->
      <section class="panel">
        <h2>👥 משתמשי האתר ({{ users.length }})</h2>
        <table class="data-table">
          <thead><tr><th>שם</th><th>מייל</th><th>תפקיד</th><th>דמות בעץ</th><th>הצטרף</th><th></th></tr></thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>{{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td><span class="role" :class="u.role">{{ u.role === 'admin' ? 'מנהל' : 'חבר' }}</span></td>
              <td>{{ u.person || '—' }}</td>
              <td>{{ u.joined }}</td>
              <td class="actions">
                <button class="mini-btn" @click="toggleRole(u)">{{ u.role === 'admin' ? 'הפוך לחבר' : 'הפוך למנהל' }}</button>
                <button class="mini-btn del" @click="deleteUser(u)">מחק</button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  summary:         { type: Object, default: () => ({}) },
  users:           { type: Array, default: () => [] },
  invitations:     { type: Array, default: () => [] },
  missingBirthday: { type: Array, default: () => [] },
  missingPhoto:    { type: Array, default: () => [] },
  documents:       { type: Array, default: () => [] },
  people:          { type: Array, default: () => [] },
})

const fileInput = ref(null)
const docForm = useForm({ title: '', file: null })

// דיגסט מייל
const digestSending = ref(false)
const digestMessage = ref(usePage().props.flash?.digest_success ?? null)

// הודעה מותאמת
const customOpen    = ref(false)
const customSending = ref(false)
const customForm    = ref({ subject: '', body: '', branch_person_id: null })

function sendCustom() {
  if (! customForm.value.subject.trim() || ! customForm.value.body.trim()) return
  const target = customForm.value.branch_person_id
    ? 'לצאצאי הדמות הנבחרת'
    : 'לכל המשתמשים הפעילים'
  if (! confirm(`לשלוח את ההודעה "${customForm.value.subject}" ${target}?`)) return
  customSending.value = true
  router.post('/admin/digest/custom', customForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      digestMessage.value = usePage().props.flash?.digest_success ?? 'נשלח!'
      customSending.value = false
      customOpen.value    = false
      customForm.value    = { subject: '', body: '', branch_person_id: null }
    },
    onError: () => { customSending.value = false },
  })
}

function sendDigestPreview() {
  if (!confirm('לשלוח תצוגה מקדימה לכתובת המייל שלך?')) return
  digestSending.value = true
  router.post('/admin/digest/preview', {}, {
    preserveScroll: true,
    onSuccess: () => {
      digestMessage.value = usePage().props.flash?.digest_success ?? 'נשלח!'
      digestSending.value = false
    },
    onError: () => { digestSending.value = false },
  })
}

function sendDigestAll() {
  const recipients = usePage().props.summary?.users_total ?? '?'
  if (!confirm(`לשלוח עדכון חודשי לכל המנויים (עד ${recipients} משתמשים)?`)) return
  digestSending.value = true
  router.post('/admin/digest/send-all', {}, {
    preserveScroll: true,
    onSuccess: () => {
      digestMessage.value = usePage().props.flash?.digest_success ?? 'נשלח לכולם!'
      digestSending.value = false
    },
    onError: () => { digestSending.value = false },
  })
}

function onFile(e) {
  docForm.file = e.target.files[0]
}

function uploadDoc() {
  docForm.post('/admin/documents', {
    preserveScroll: true,
    onSuccess: () => { docForm.reset(); if (fileInput.value) fileInput.value.value = '' },
  })
}

function deleteDoc(d) {
  if (!confirm(`למחוק את "${d.title}"?`)) return
  router.delete(`/admin/documents/${d.id}`, { preserveScroll: true })
}

function extendInvite(inv) {
  router.post(`/admin/invitations/${inv.id}/extend`, {}, { preserveScroll: true })
}

function deleteInvite(inv) {
  if (!confirm(`למחוק את ההזמנה ל-${inv.email}?`)) return
  router.delete(`/admin/invitations/${inv.id}`, { preserveScroll: true })
}

function toggleRole(u) {
  router.post(`/admin/users/${u.id}/toggle-role`, {}, { preserveScroll: true })
}

function deleteUser(u) {
  if (!confirm(`למחוק את המשתמש ${u.name}? (הדמות בעץ תישאר)`)) return
  router.delete(`/admin/users/${u.id}`, { preserveScroll: true })
}
</script>

<style scoped>
.admin-page {
  max-width: 1050px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}

.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.6rem; color: #1a3a6b; margin: 0; }
.subtitle { color: #6b7a99; margin: 0.25rem 0 0; }

.summary-cards {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}
.sum-card {
  background: white; border: 1px solid #e6eefb; border-radius: 12px;
  padding: 1rem 0.75rem; text-align: center;
  box-shadow: 0 2px 8px rgba(0,50,150,0.05);
}
.sum-card b { display: block; font-size: 1.6rem; color: #2d6be4; }
.sum-card span { font-size: 0.78rem; color: #6b7a99; }
.sum-card.warn b { color: #e67e22; }

.panel {
  background: white; border: 1px solid #e6eefb; border-radius: 14px;
  padding: 1.25rem 1.5rem; margin-bottom: 1.25rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.05);
}
.panel h2 { font-size: 1.1rem; color: #1a3a6b; margin: 0 0 0.85rem; }
.hint { font-size: 0.82rem; color: #9aa7c0; margin: 0 0 0.85rem; }
.empty { color: #9aa7c0; font-size: 0.9rem; }
.err { color: #e74c3c; font-size: 0.85rem; margin: 0.5rem 0 0; }

/* הורדות */
.download-row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.dl-btn {
  background: #edf3ff; color: #2d6be4; text-decoration: none;
  padding: 0.6rem 1rem; border-radius: 9px; font-size: 0.9rem; font-weight: 500;
  transition: background 0.2s;
}
.dl-btn:hover:not(:disabled) { background: #dde9ff; }
.dl-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.dl-btn.print { background: #fff4e6; color: #e67e22; }
.dl-btn.print:hover { background: #ffe9cc; }
.dl-btn-send { background: #e8f5e9; color: #166534; }
.dl-btn-send:hover:not(:disabled) { background: #d1f0d8; }
.digest-msg { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 8px; padding: 0.5rem 0.85rem; margin-bottom: 0.75rem; font-size: 0.88rem; }

/* הודעה מותאמת */
.custom-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
.custom-toggle { font-size: 0.8rem; color: #6b7a99; }
.custom-form { margin-top: 1rem; border-top: 1px solid #e6eefb; padding-top: 1rem; }
.custom-field { margin-bottom: 0.85rem; }
.custom-label { display: block; font-size: 0.83rem; color: #4a5568; font-weight: 600; margin-bottom: 0.3rem; }
.custom-input { width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #d1dce8; border-radius: 9px; font-size: 0.92rem; font-family: 'Rubik', sans-serif; color: #1a3a6b; background: #fff; direction: rtl; box-sizing: border-box; }
.custom-input:focus { outline: none; border-color: #2d6be4; box-shadow: 0 0 0 3px rgba(45,107,228,0.1); }
.custom-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1.5px solid #d1dce8; border-radius: 9px; font-size: 0.92rem; font-family: 'Rubik', sans-serif; color: #1a3a6b; background: #fff; direction: rtl; resize: vertical; box-sizing: border-box; line-height: 1.6; }
.custom-textarea:focus { outline: none; border-color: #2d6be4; box-shadow: 0 0 0 3px rgba(45,107,228,0.1); }
.custom-count { font-size: 0.75rem; color: #9ca3af; text-align: left; margin-top: 0.2rem; }
.custom-actions { display: flex; align-items: center; gap: 0.9rem; margin-top: 0.5rem; }
.custom-hint { font-size: 0.8rem; color: #6b7a99; }
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; overflow: hidden; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; }
.slide-enter-to, .slide-leave-from { opacity: 1; max-height: 600px; }

/* מסמכים */
.doc-form { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1rem; }
.doc-form input[type=text] {
  flex: 1; min-width: 180px; padding: 0.5rem 0.75rem;
  border: 1px solid #d7e2f5; border-radius: 8px; font-family: inherit;
}
.doc-form input[type=file] { font-size: 0.85rem; align-self: center; }
.doc-form button {
  background: #2d6be4; color: white; border: none; border-radius: 8px;
  padding: 0.5rem 1.25rem; cursor: pointer; font-family: inherit; font-weight: 600;
}
.doc-form button:disabled { opacity: 0.5; }
.doc-list { list-style: none; padding: 0; margin: 0; }
.doc-list li { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f0f4fb; }
.doc-link { color: #2d6be4; text-decoration: none; flex: 1; }
.doc-meta { font-size: 0.78rem; color: #9aa7c0; }

/* chips */
.chip-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip {
  background: #edf3ff; color: #2d4a7a; text-decoration: none;
  padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem;
  transition: background 0.2s;
}
.chip:hover { background: #dde9ff; }
.chip.deceased { background: #f0f0f3; color: #6b7280; }

/* טבלאות */
.data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.data-table th { text-align: right; color: #6b7a99; font-weight: 600; padding: 0.5rem 0.6rem; border-bottom: 2px solid #e6eefb; }
.data-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid #f0f4fb; color: #2d4a7a; }
.data-table tr.expired td { background: #fff7f5; }
.badge-expired { background: #fde2dd; color: #c0392b; border-radius: 6px; padding: 0.1rem 0.45rem; font-size: 0.72rem; margin-right: 0.4rem; }
.role { border-radius: 6px; padding: 0.1rem 0.5rem; font-size: 0.78rem; }
.role.admin { background: #e3f0ff; color: #2d6be4; }
.role.member { background: #f0f4fb; color: #6b7a99; }

.actions { display: flex; gap: 0.4rem; white-space: nowrap; }
.mini-btn {
  background: #edf3ff; color: #2d6be4; border: none; border-radius: 7px;
  padding: 0.3rem 0.65rem; font-size: 0.78rem; cursor: pointer; font-family: inherit;
}
.mini-btn:hover { background: #dde9ff; }
.mini-btn.del { background: #fdeeec; color: #c0392b; }
.mini-btn.del:hover { background: #fbddd8; }
.link-del { background: none; border: none; color: #c0392b; cursor: pointer; font-size: 0.8rem; font-family: inherit; }

@media (max-width: 760px) {
  .summary-cards { grid-template-columns: repeat(2, 1fr); }
  .data-table { font-size: 0.8rem; }
  .actions { flex-direction: column; }
}
</style>
