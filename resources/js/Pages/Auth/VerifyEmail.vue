<script setup>
import { computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  status: { type: String },
})

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <GuestLayout title="אימות אימייל">
    <Head title="אימות אימייל" />

    <p class="auth-note">
      תודה שנרשמתם! לפני שמתחילים, נשמח שתאמתו את כתובת האימייל שלכם
      בלחיצה על הקישור ששלחנו אליכם. אם לא קיבלתם — נשלח שוב בשמחה.
    </p>

    <div v-if="verificationLinkSent" class="auth-status">
      קישור אימות חדש נשלח לכתובת האימייל שלכם.
    </div>

    <form @submit.prevent="submit">
      <button type="submit" class="auth-btn" :disabled="form.processing">
        {{ form.processing ? 'שולח...' : 'שלח שוב את מייל האימות' }}
      </button>
    </form>

    <div class="auth-foot">
      <Link :href="route('logout')" method="post" as="button" class="auth-link">יציאה</Link>
    </div>
  </GuestLayout>
</template>
