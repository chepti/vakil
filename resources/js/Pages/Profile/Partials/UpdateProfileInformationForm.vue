<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
  mustVerifyEmail: { type: Boolean },
  status: { type: String },
})

const user = usePage().props.auth.user

const form = useForm({
  name: user.name,
  email: user.email,
})
</script>

<template>
  <section>
    <h2 class="pf-section-title">פרטים אישיים</h2>
    <p class="pf-section-desc">עדכנו את השם וכתובת האימייל של החשבון שלכם.</p>

    <form @submit.prevent="form.patch(route('profile.update'))">
      <div class="pf-field">
        <label class="pf-label" for="name">שם</label>
        <input id="name" type="text" class="pf-input" v-model="form.name" required autocomplete="name" />
        <div v-if="form.errors.name" class="pf-error">{{ form.errors.name }}</div>
      </div>

      <div class="pf-field">
        <label class="pf-label" for="email">אימייל</label>
        <input id="email" type="email" class="pf-input" v-model="form.email" required autocomplete="username" dir="ltr" />
        <div v-if="form.errors.email" class="pf-error">{{ form.errors.email }}</div>
      </div>

      <div v-if="mustVerifyEmail && user.email_verified_at === null" class="pf-verify-note">
        כתובת האימייל שלכם אינה מאומתת.
        <Link :href="route('verification.send')" method="post" as="button" class="pf-link">
          שלחו שוב את מייל האימות
        </Link>
        <div v-show="status === 'verification-link-sent'" style="margin-top:0.4rem; color:#16a34a;">
          קישור אימות חדש נשלח לאימייל שלכם.
        </div>
      </div>

      <div class="pf-actions">
        <button type="submit" class="pf-btn" :disabled="form.processing">שמירה</button>
        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
          leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
          <span v-if="form.recentlySuccessful" class="pf-saved">✓ נשמר</span>
        </Transition>
      </div>
    </form>
  </section>
</template>
