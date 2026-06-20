<script setup>
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)

const form = useForm({
  password: '',
})

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true
  nextTick(() => passwordInput.value.focus())
}

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value.focus(),
    onFinish: () => form.reset(),
  })
}

const closeModal = () => {
  confirmingUserDeletion.value = false
  form.clearErrors()
  form.reset()
}
</script>

<template>
  <section>
    <h2 class="pf-section-title" style="color:#b91c1c">מחיקת חשבון</h2>
    <p class="pf-section-desc">
      לאחר מחיקת החשבון, כל הנתונים שלו יימחקו לצמיתות. לפני המחיקה,
      ודאו ששמרתם כל מידע שתרצו לשמור.
    </p>

    <button class="pf-btn pf-btn-danger" @click="confirmUserDeletion">מחיקת החשבון</button>

    <Teleport to="body">
      <div v-if="confirmingUserDeletion" class="pf-modal-overlay" @click.self="closeModal">
        <div class="pf-modal" dir="rtl">
          <h2 class="pf-section-title" style="color:#b91c1c">האם למחוק את החשבון?</h2>
          <p class="pf-section-desc">
            לאחר המחיקה, כל הנתונים יימחקו לצמיתות. הזינו את הסיסמה
            שלכם כדי לאשר את מחיקת החשבון.
          </p>

          <div class="pf-field">
            <input id="password" ref="passwordInput" type="password" class="pf-input"
              v-model="form.password" placeholder="סיסמה" dir="ltr" @keyup.enter="deleteUser" />
            <div v-if="form.errors.password" class="pf-error">{{ form.errors.password }}</div>
          </div>

          <div class="pf-actions" style="justify-content:flex-start; margin-top:1.2rem">
            <button class="pf-btn-ghost" @click="closeModal">ביטול</button>
            <button class="pf-btn pf-btn-danger" :disabled="form.processing" @click="deleteUser">
              מחיקת החשבון
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
