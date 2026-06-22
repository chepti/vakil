<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  people: { type: Array, default: () => [] },
})

const user = usePage().props.auth.user

const form = useForm({
  notify_monthly_digest:    !!user.notify_monthly_digest,
  notify_new_person:        !!user.notify_new_person,
  notify_new_event:         !!user.notify_new_event,
  digest_branch_person_id:  user.digest_branch_person_id ?? null,
})

const submit = () => form.patch(route('profile.notifications'), { preserveScroll: true })

// חיפוש בתוך רשימת האנשים לבחירת ענף
const branchSearch = ref(
  user.digest_branch_person_id
    ? (props.people.find(p => p.id === user.digest_branch_person_id)?.name ?? '')
    : ''
)
const showDropdown = ref(false)

const filteredPeople = computed(() =>
  branchSearch.value.length < 1
    ? props.people.slice(0, 50)
    : props.people.filter(p =>
        p.name.includes(branchSearch.value) ||
        p.name.toLowerCase().includes(branchSearch.value.toLowerCase())
      ).slice(0, 40)
)

function selectBranch(person) {
  form.digest_branch_person_id = person ? person.id : null
  branchSearch.value = person ? person.name : ''
  showDropdown.value = false
}

function clearBranch() {
  form.digest_branch_person_id = null
  branchSearch.value = ''
  showDropdown.value = false
}
</script>

<template>
  <section>
    <h2 class="pf-section-title">התראות במייל</h2>
    <p class="pf-section-desc">בחרו אילו עדכונים תרצו לקבל לתיבת המייל.</p>

    <form @submit.prevent="submit">
      <label class="nf-row">
        <input type="checkbox" v-model="form.notify_monthly_digest" />
        <span class="nf-text">
          <span class="nf-title">📬 עדכון חודשי (ראש חודש)</span>
          <span class="nf-desc">סיכום חודשי: מי נולד, אירועים קרובים וימי הולדת/נישואין עגולים.</span>
        </span>
      </label>

      <label class="nf-row">
        <input type="checkbox" v-model="form.notify_new_person" />
        <span class="nf-text">
          <span class="nf-title">🌳 דמות חדשה בעץ</span>
          <span class="nf-desc">מייל מיידי בכל פעם שמתווספת דמות חדשה לעץ המשפחה.</span>
        </span>
      </label>

      <label class="nf-row">
        <input type="checkbox" v-model="form.notify_new_event" />
        <span class="nf-text">
          <span class="nf-title">📅 אירוע חדש בעץ</span>
          <span class="nf-desc">מייל מיידי בכל פעם שנוסף אירוע חדש ללוח האירועים של המשפחה.</span>
        </span>
      </label>

      <!-- בחירת ענף ברזולוציה גבוהה -->
      <div class="nf-branch-box">
        <div class="nf-title" style="margin-bottom:0.4rem;">🌿 ענף ברזולוציה גבוהה</div>
        <div class="nf-desc" style="margin-bottom:0.7rem;">
          בחרו דמות — כל ימי ההולדת והנישואין של צאצאיה יופיעו בעדכון החודשי (לא רק עשורים).
        </div>

        <div class="nf-branch-picker" @click.stop>
          <div style="display:flex;gap:0.4rem;align-items:center;">
            <input
              v-model="branchSearch"
              type="text"
              placeholder="חיפוש דמות..."
              class="pf-input"
              style="flex:1;"
              @focus="showDropdown = true"
              @input="showDropdown = true"
            />
            <button
              v-if="form.digest_branch_person_id"
              type="button"
              class="pf-btn-ghost"
              style="padding:0.45rem 0.7rem;font-size:0.85rem;"
              @click="clearBranch"
            >✕ נקה</button>
          </div>

          <div v-if="showDropdown && filteredPeople.length" class="nf-dropdown">
            <div
              v-for="p in filteredPeople"
              :key="p.id"
              class="nf-dropdown-item"
              :class="{ 'nf-dropdown-item--selected': form.digest_branch_person_id === p.id }"
              @mousedown.prevent="selectBranch(p)"
            >{{ p.name }}</div>
          </div>
        </div>

        <div v-if="form.digest_branch_person_id" class="nf-branch-selected">
          ✓ נבחר: <strong>{{ branchSearch }}</strong>
        </div>
      </div>

      <div class="pf-actions" style="margin-top:1.2rem;">
        <button type="submit" class="pf-btn" :disabled="form.processing">שמירה</button>
        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
          leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
          <span v-if="form.recentlySuccessful" class="pf-saved">✓ נשמר</span>
        </Transition>
      </div>
    </form>
  </section>
</template>

<style scoped>
.nf-row {
  display: flex; align-items: flex-start; gap: 0.7rem;
  padding: 0.8rem; border: 1.5px solid #e7eefb; border-radius: 11px;
  margin-bottom: 0.7rem; cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.nf-row:hover { border-color: #c7d8f5; background: #f8fbff; }
.nf-row input { margin-top: 0.25rem; width: 1.1rem; height: 1.1rem; cursor: pointer; accent-color: #2d6be4; }
.nf-text { display: flex; flex-direction: column; gap: 0.15rem; }
.nf-title { font-size: 0.94rem; font-weight: 600; color: #1a3a6b; }
.nf-desc { font-size: 0.82rem; color: #6b7a99; line-height: 1.45; }

.nf-branch-box {
  border: 1.5px solid #e7eefb; border-radius: 11px;
  padding: 0.85rem; margin-bottom: 0.7rem; background: #fafcff;
}
.nf-branch-picker { position: relative; }
.nf-dropdown {
  position: absolute; top: 100%; right: 0; left: 0; z-index: 100;
  background: #fff; border: 1.5px solid #c7d8f5; border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,50,150,0.12); max-height: 220px; overflow-y: auto;
  margin-top: 3px;
}
.nf-dropdown-item {
  padding: 0.55rem 0.9rem; font-size: 0.9rem; color: #1a3a6b; cursor: pointer;
  transition: background 0.1s;
}
.nf-dropdown-item:hover { background: #eff6ff; }
.nf-dropdown-item--selected { background: #dbeafe; font-weight: 600; }
.nf-branch-selected {
  margin-top: 0.5rem; font-size: 0.85rem; color: #16a34a;
}
</style>
