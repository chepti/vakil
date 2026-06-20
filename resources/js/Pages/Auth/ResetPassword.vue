<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
  email: { type: String, required: true },
  token: { type: String, required: true },
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout title="בחירת סיסמה חדשה">
    <Head title="איפוס סיסמה" />

    <form @submit.prevent="submit">
      <div class="auth-field">
        <label class="auth-label" for="email">אימייל</label>
        <input id="email" type="email" class="auth-input" v-model="form.email"
          required autofocus autocomplete="username" dir="ltr" />
        <div v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</div>
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password">סיסמה חדשה</label>
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

      <button type="submit" class="auth-btn" :disabled="form.processing">
        {{ form.processing ? 'שומר...' : 'איפוס סיסמה' }}
      </button>
    </form>
  </GuestLayout>
</template>
