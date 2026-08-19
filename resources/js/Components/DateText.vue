<script setup>
/**
 * תאריך לתצוגה, עם קיפול אופציונלי של השנה.
 *
 * במקומות שבהם השנה מוסתרת (נשים נשואות, באתרים שהדגל דלוק בהם) מוצג
 * היום והחודש בלבד, ולצידם כפתור קטן שחושף את השנה בלחיצה — כדי שהמידע
 * יישאר נגיש למי שצריך אותו, בלי להופיע מעצמו על המסך.
 */
import { computed, ref } from 'vue'
import { useDateDisplay } from '@/utils/dateDisplay'

const props = defineProps({
  gregorian: { type: String, default: '' },
  hebrew:    { type: String, default: '' },
  personId:  { type: [Number, String], default: null },
  isBirth:   { type: Boolean, default: false },
})

const { dateParts } = useDateDisplay()
const open  = ref(false)
const parts = computed(() => dateParts({ ...props }))
</script>

<template>
  <span v-if="parts.text || parts.folded" class="date-text">
    {{ parts.text }}
    <template v-if="parts.folded">
      <button
        v-if="!open"
        type="button"
        class="year-toggle"
        title="הצג שנה"
        @click.stop.prevent="open = true"
      >···</button>
      <span v-else class="year-revealed">{{ parts.folded }}</span>
    </template>
  </span>
</template>

<style scoped>
.date-text { white-space: nowrap; }

.year-toggle {
  border: none;
  background: transparent;
  color: inherit;
  opacity: 0.55;
  cursor: pointer;
  padding: 0 0.15rem;
  font: inherit;
  line-height: 1;
}
.year-toggle:hover { opacity: 1; }

.year-revealed { opacity: 0.75; }
</style>
