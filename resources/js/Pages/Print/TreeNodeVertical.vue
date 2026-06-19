<template>
  <li class="vnode">
    <div class="vcouple">
      <div class="vbox" :class="node.person.gender === 'M' ? 'male' : 'female'">
        <img v-if="node.person.avatar" :src="node.person.avatar" class="vphoto" />
        <div v-else class="vphoto ph">{{ initial }}</div>
        <div class="vname">
          {{ node.person['first name'] }} {{ node.person['last name'] }}
          <span v-if="node.person.is_deceased" class="zl">ז"ל</span>
          <span v-if="years" class="vyears">{{ years }}</span>
        </div>
      </div>
      <template v-for="sp in node.spouses" :key="sp.id">
        <span class="vheart">♥</span>
        <div class="vbox spouse" :class="sp.gender === 'M' ? 'male' : 'female'">
          <img v-if="sp.avatar" :src="sp.avatar" class="vphoto" />
          <div v-else class="vphoto ph">{{ (sp.name || '?').charAt(0) }}</div>
          <div class="vname">{{ sp.name }}<span v-if="sp.is_deceased" class="zl">ז"ל</span></div>
        </div>
      </template>
    </div>

    <ul v-if="node.children.length" class="vchildren">
      <TreeNodeVertical v-for="child in node.children" :key="child.id" :node="child" />
    </ul>
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
/* org-chart קלאסי מלמעלה למטה — תבנית CSS-tree סטנדרטית (סימטרית, תקפה ל-RTL) */
.vnode {
  list-style: none;
  text-align: center;
  position: relative;
  padding: 1.3rem 0.4rem 0;
}

/* קווי חיבור: שני חצאים אופקיים + אנכי קצר מעל כל צומת */
.vnode::before,
.vnode::after {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  width: 50%;
  height: 1.3rem;
  border-top: 2px solid #cdddf5;
}
.vnode::after {
  right: auto;
  left: 50%;
  border-left: 2px solid #cdddf5;
}
.vnode::before { border-right: 2px solid #cdddf5; }

/* צומת יחיד — בלי חיבורים אופקיים */
.vnode:only-child::before,
.vnode:only-child::after { display: none; }

/* קצוות — לחתוך את החצי החיצוני של הקו האופקי */
.vnode:first-child::before,
.vnode:last-child::after { border: 0 none; }
.vnode:last-child::before { border-right: 2px solid #cdddf5; border-radius: 0 6px 0 0; }
.vnode:first-child::after { border-left: 2px solid #cdddf5; border-radius: 6px 0 0 0; }

/* השורש (ישיר תחת .tree-root.vertical) — בלי חיבורים מעליו */
:deep(.tree-root.vertical) > .vnode { padding-top: 0; }
:deep(.tree-root.vertical) > .vnode::before,
:deep(.tree-root.vertical) > .vnode::after { display: none; }

.vcouple { display: inline-flex; align-items: center; gap: 0.3rem; }

.vbox {
  display: inline-flex; flex-direction: column; align-items: center;
  background: white; border: 1.5px solid #cdddf5; border-radius: 10px;
  padding: 0.45rem 0.6rem; min-width: 84px; max-width: 120px; vertical-align: top;
}
.vbox.male { border-color: #9cc0f0; background: #f3f8ff; }
.vbox.female { border-color: #d9b8ec; background: #faf3ff; }
.vphoto { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.vphoto.ph { display: flex; align-items: center; justify-content: center; background: #e6eefb; color: #2d6be4; font-weight: 700; }
.vname { font-size: 0.78rem; font-weight: 600; color: #1a3a6b; text-align: center; margin-top: 0.25rem; line-height: 1.25; }
.vyears { display: block; font-size: 0.68rem; color: #6b7a99; font-weight: 400; }
.zl { font-size: 0.65rem; color: #888; }
.vheart { color: #e08aa8; font-size: 0.8rem; }

/* שורת ילדים */
.vchildren {
  display: flex;
  justify-content: center;
  list-style: none;
  margin: 0;
  padding: 0;
  position: relative;
}
/* קו אנכי יורד מהאב אל שורת הילדים */
.vchildren::before {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  transform: translateX(50%);
  width: 2px;
  height: 1.3rem;
  background: #cdddf5;
}
</style>
