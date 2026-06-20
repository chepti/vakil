<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation')
        passwordInput.value.focus()
      }
      if (form.errors.current_password) {
        form.reset('current_password')
        currentPasswordInput.value.focus()
      }
    },
  })
}
</script>

<template>
  <section>
    <h2 class="pf-section-title">שינוי סיסמה</h2>
    <p class="pf-section-desc">מומלץ להשתמש בסיסמה ארוכה וייחודית כדי לשמור על החשבון מאובטח.</p>

    <form @submit.prevent="updatePassword">
      <div class="pf-field">
        <label class="pf-label" for="current_password">סיסמה נוכחית</label>
        <input id="current_password" ref="currentPasswordInput" type="password" class="pf-input"
          v-model="form.current_password" autocomplete="current-password" dir="ltr" />
        <div v-if="form.errors.current_password" class="pf-error">{{ form.errors.current_password }}</div>
      </div>

      <div class="pf-field">
        <label class="pf-label" for="password">סיסמה חדשה</label>
        <input id="password" ref="passwordInput" type="password" class="pf-input"
          v-model="form.password" autocomplete="new-password" dir="ltr" />
        <div v-if="form.errors.password" class="pf-error">{{ form.errors.password }}</div>
      </div>

      <div class="pf-field">
        <label class="pf-label" for="password_confirmation">אימות סיסמה</label>
        <input id="password_confirmation" type="password" class="pf-input"
          v-model="form.password_confirmation" autocomplete="new-password" dir="ltr" />
        <div v-if="form.errors.password_confirmation" class="pf-error">{{ form.errors.password_confirmation }}</div>
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
