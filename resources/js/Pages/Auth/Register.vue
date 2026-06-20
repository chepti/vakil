<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout title="הצטרפות למשפחה" subtitle="פתחו חשבון כדי להיות חלק מעץ המשפחה">
    <Head title="הרשמה" />

    <form @submit.prevent="submit">
      <div class="auth-field">
        <label class="auth-label" for="name">שם מלא</label>
        <input id="name" type="text" class="auth-input" v-model="form.name"
          required autofocus autocomplete="name" placeholder="שם פרטי ומשפחה" />
        <div v-if="form.errors.name" class="auth-error">{{ form.errors.name }}</div>
      </div>

      <div class="auth-field">
        <label class="auth-label" for="email">אימייל</label>
        <input id="email" type="email" class="auth-input" v-model="form.email"
          required autocomplete="username" dir="ltr" placeholder="name@example.com" />
        <div v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</div>
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password">סיסמה</label>
        <input id="password" type="password" class="auth-input" v-model="form.password"
          required autocomplete="new-password" dir="ltr" placeholder="••••••••" />
        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password_confirmation">אימות סיסמה</label>
        <input id="password_confirmation" type="password" class="auth-input" v-model="form.password_confirmation"
          required autocomplete="new-password" dir="ltr" placeholder="••••••••" />
        <div v-if="form.errors.password_confirmation" class="auth-error">{{ form.errors.password_confirmation }}</div>
      </div>

      <button type="submit" class="auth-btn" :disabled="form.processing" style="margin-top:0.4rem">
        {{ form.processing ? 'נרשם...' : 'הרשמה' }}
      </button>
    </form>

    <div class="auth-foot">
      כבר יש לך חשבון?
      <Link :href="route('login')" class="auth-link">התחברות</Link>
    </div>
  </GuestLayout>
</template>
