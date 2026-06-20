<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
  canResetPassword: { type: Boolean },
  status: { type: String },
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <GuestLayout title="ברוכים הבאים" subtitle="התחברו כדי להיכנס למעגל המשפחה">
    <Head title="התחברות" />

    <div v-if="status" class="auth-status">{{ status }}</div>

    <form @submit.prevent="submit">
      <div class="auth-field">
        <label class="auth-label" for="email">אימייל</label>
        <input id="email" type="email" class="auth-input" v-model="form.email"
          required autofocus autocomplete="username" dir="ltr" placeholder="name@example.com" />
        <div v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</div>
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password">סיסמה</label>
        <input id="password" type="password" class="auth-input" v-model="form.password"
          required autocomplete="current-password" dir="ltr" placeholder="••••••••" />
        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
      </div>

      <div class="auth-row">
        <label class="auth-checkbox">
          <input type="checkbox" v-model="form.remember" />
          <span>זכור אותי</span>
        </label>
        <Link v-if="canResetPassword" :href="route('password.request')" class="auth-link">
          שכחת סיסמה?
        </Link>
      </div>

      <button type="submit" class="auth-btn" :disabled="form.processing">
        {{ form.processing ? 'מתחבר...' : 'התחברות' }}
      </button>
    </form>

    <div class="auth-foot">
      אין לך חשבון?
      <Link :href="route('register')" class="auth-link">הרשמה</Link>
    </div>
  </GuestLayout>
</template>
