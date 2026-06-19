<template>
  <AppLayout title="אירוע חדש">
    <div class="event-form-page" dir="rtl">
      <div class="page-header">
        <Link href="/events" class="btn-back">← חזרה ללוח</Link>
        <h1>אירוע חדש</h1>
      </div>

      <EventForm
        :form="form"
        :people="people"
        :errors="form.errors"
        submit-label="צור אירוע"
        @submit="submit"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EventForm from './EventForm.vue'

const props = defineProps({
  people: { type: Array, default: () => [] },
})

const form = useForm({
  person_id: null,
  type: 'other',
  title: '',
  event_date: '',
  event_time: '',
  hebrew_date: '',
  location: '',
  photos_link: '',
  audience: [],
  audience_branch_person_id: null,
  description: '',
  invitation_image: null,
})

function submit() {
  form.post('/events', { forceFormData: true })
}
</script>

<style scoped>
.event-form-page {
  max-width: 760px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: 'Rubik', sans-serif;
}
.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }
.btn-back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; white-space: nowrap; }
</style>
