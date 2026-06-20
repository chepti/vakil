<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
  status: { type: String },
})

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <GuestLayout title="שחזור סיסמה">
    <Head title="שחזור סיסמה" />

    <p class="auth-note">
      שכחת את הסיסמה? אין בעיה. הזינו את כתובת האימייל שלכם
      ונשלח אליכם קישור לבחירת סיסמה חדשה.
    </p>

    <div v-if="status" class="auth-status">{{ status }}</div>

    <form @submit.prevent="submit">
      <div class="auth-field">
        <label class="auth-label" for="email">אימייל</label>
        <input id="email" type="email" class="auth-input" v-model="form.email"
          required autofocus autocomplete="username" dir="ltr" placeholder="name@example.com" />
        <div v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</div>
      </div>

      <button type="submit" class="auth-btn" :disabled="form.processing">
        {{ form.processing ? 'שולח...' : 'שלח קישור לשחזור' }}
      </button>
    </form>

    <div class="auth-foot">
      <Link :href="route('login')" class="auth-link">← חזרה להתחברות</Link>
    </div>
  </GuestLayout>
</template>
