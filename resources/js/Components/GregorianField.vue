<script setup>
/**
 * עוטף שדה של תאריך לועזי בטופס.
 *
 * באתרים שבהם התצוגה עברית בלבד (config app.dates.hide_gregorian) השדה
 * לא נעלם — הוא נכנס לאזור מקופל, כך שאפשר עדיין להזין תאריך לועזי
 * מבלי שהוא יתפוס את מרכז הטופס. בשאר האתרים אין שינוי כלל.
 */
import { useDateDisplay } from '@/utils/dateDisplay'

defineProps({
  label: { type: String, default: 'תאריך לועזי' },
})

const { hideGregorian } = useDateDisplay()
</script>

<template>
  <details v-if="hideGregorian" class="greg-fold">
    <summary>{{ label }}</summary>
    <slot />
  </details>
  <slot v-else />
</template>

<style scoped>
.greg-fold {
  flex: 1 1 100%;
  border: 1px dashed #cdddf5;
  border-radius: 8px;
  padding: 0.35rem 0.6rem;
  background: #fbfdff;
}
.greg-fold > summary {
  cursor: pointer;
  font-size: 0.8rem;
  color: #6b7a99;
  user-select: none;
}
.greg-fold[open] > summary { margin-bottom: 0.4rem; }
</style>
