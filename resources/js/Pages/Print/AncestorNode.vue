<template>
  <li class="anode">
    <!-- הורים מעל (רקורסיבי) — קו ישיר בלבד, בלי אחים -->
    <ul v-if="node.parents && node.parents.length" class="aparents">
      <AncestorNode v-for="p in node.parents" :key="p.id" :node="p" />
    </ul>

    <div class="abox" :class="node.person.gender === 'M' ? 'male' : 'female'">
      <img v-if="node.person.avatar" :src="node.person.avatar" class="aphoto" />
      <div v-else class="aphoto ph">{{ initial }}</div>
      <div class="aname">
        {{ node.person['first name'] }} {{ node.person['last name'] }}
        <span v-if="node.person.is_deceased" class="zl">ז"ל</span>
        <span v-if="years" class="ayears">{{ years }}</span>
      </div>
    </div>
  </li>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ node: { type: Object, required: true } })

const initial = computed(() => (props.node.person['first name'] || '?').charAt(0))

const years = computed(() => {
  const b = props.node.person.birthday
  const by = b ? new Date(b).getFullYear() : null
  const d = props.node.person.death_date
  const dy = d ? new Date(d).getFullYear() : null
  if (by && dy) return `${by}–${dy}`
  return by ? `${by}` : ''
})
</script>

<style scoped>
/* פדיגרי הפוך: התיבה למטה, ההורים מעליה. מראה-ראי של ה-org-chart היורד. */
.anode {
  list-style: none;
  text-align: center;
  position: relative;
  padding-bottom: 1.3rem;
}

/* קווי חיבור מתחת לתיבה — מחברים כלפי מטה אל הצאצא שמתחת */
.anode::before,
.anode::after {
  content: '';
  position: absolute;
  bottom: 0;
  right: 50%;
  width: 50%;
  height: 1.3rem;
  border-bottom: 2px solid #cdddf5;
}
.anode::after { right: auto; left: 50%; border-left: 2px solid #cdddf5; }
.anode::before { border-right: 2px solid #cdddf5; }

.anode:only-child::before,
.anode:only-child::after { display: none; }
.anode:first-child::after { border: 0 none; }
.anode:last-child::before { border: 0 none; }

/* שורת ההורים + קו אנכי שעולה מהתיבה אל המוט האופקי */
.aparents {
  display: flex;
  justify-content: center;
  list-style: none;
  margin: 0;
  padding: 0;
  position: relative;
}
.aparents::after {
  content: '';
  position: absolute;
  bottom: 0;
  right: 50%;
  transform: translateX(50%);
  width: 2px;
  height: 1.3rem;
  background: #cdddf5;
}

.abox {
  display: inline-flex; flex-direction: column; align-items: center;
  background: white; border: 1.5px solid #cdddf5; border-radius: 10px;
  padding: 0.45rem 0.6rem; min-width: 84px; max-width: 120px;
}
.abox.male { border-color: #9cc0f0; background: #f3f8ff; }
.abox.female { border-color: #d9b8ec; background: #faf3ff; }
.aphoto { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.aphoto.ph { display: flex; align-items: center; justify-content: center; background: #e6eefb; color: #2d6be4; font-weight: 700; }
.aname { font-size: 0.78rem; font-weight: 600; color: #1a3a6b; text-align: center; margin-top: 0.25rem; line-height: 1.25; }
.ayears { display: block; font-size: 0.68rem; color: #6b7a99; font-weight: 400; }
.zl { font-size: 0.65rem; color: #888; }
</style>
