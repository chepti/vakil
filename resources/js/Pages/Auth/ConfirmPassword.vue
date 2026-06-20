<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({
  password: '',
})

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <GuestLayout title="אישור סיסמה">
    <Head title="אישור סיסמה" />

    <p class="auth-note">
      זהו אזור מאובטח באתר. אנא אשרו את הסיסמה שלכם כדי להמשיך.
    </p>

    <form @submit.prevent="submit">
      <div class="auth-field">
        <label class="auth-label" for="password">סיסמה</label>
        <input id="password" type="password" class="auth-input" v-model="form.password"
          required autocomplete="current-password" autofocus dir="ltr" placeholder="••••••••" />
        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
      </div>

      <button type="submit" class="auth-btn" :disabled="form.processing">
        {{ form.processing ? 'מאשר...' : 'אישור' }}
      </button>
    </form>
  </GuestLayout>
</template>
