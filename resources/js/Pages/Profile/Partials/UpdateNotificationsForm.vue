<script setup>
import { useForm, usePage } from '@inertiajs/vue3'

const user = usePage().props.auth.user

const form = useForm({
  notify_monthly_digest: !!user.notify_monthly_digest,
  notify_new_person: !!user.notify_new_person,
})

const submit = () => form.patch(route('profile.notifications'), { preserveScroll: true })
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

      <div class="pf-actions" style="margin-top:1rem;">
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
</style>
