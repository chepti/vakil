<template>
  <AppLayout title="עריכת אירוע">
    <div class="event-form-page" dir="rtl">
      <div class="page-header">
        <Link :href="`/events/${event.id}`" class="btn-back">← חזרה לאירוע</Link>
        <h1>עריכת אירוע</h1>
      </div>

      <EventForm
        :form="form"
        :people="people"
        :errors="form.errors"
        :existing-image="event.invitation_image_url"
        submit-label="שמור שינויים"
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
  event: { type: Object, required: true },
})

const form = useForm({
  _method: 'put',
  person_id: props.event.person_id,
  type: props.event.type,
  title: props.event.title,
  event_date: props.event.event_date || '',
  event_time: props.event.event_time || '',
  hebrew_date: props.event.hebrew_date || '',
  location: props.event.location || '',
  photos_link: props.event.photos_link || '',
  audience: props.event.audience || [],
  audience_branch_person_id: props.event.audience_branch_person_id,
  description: props.event.description || '',
  invitation_image: null,
})

function submit() {
  form.post(`/events/${props.event.id}`, { forceFormData: true })
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
