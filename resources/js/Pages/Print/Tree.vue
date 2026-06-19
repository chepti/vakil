<template>
  <div class="print-page" dir="rtl">
    <!-- בקרה (לא מודפס) -->
    <div class="controls no-print">
      <Link href="/admin" class="back">→ חזרה לניהול</Link>
      <div class="control-group">
        <label>אדם שורש:</label>
        <select v-model="rootId">
          <option v-for="p in people" :key="p.id" :value="p.id">{{ p.label }}</option>
        </select>
      </div>
      <div class="control-group">
        <label>דורות:</label>
        <select v-model.number="generations">
          <option v-for="g in 6" :key="g" :value="g">{{ g }}</option>
        </select>
      </div>
      <button class="print-btn" @click="print">🖨️ הדפס / שמור כ-PDF</button>
    </div>

    <!-- אזור ההדפסה -->
    <div class="printable">
      <h1 class="print-title">משפחת ואקיל — {{ rootName }}</h1>
      <p class="print-sub">{{ generations }} דורות · {{ countShown }} בני משפחה</p>
      <ul class="tree-root">
        <TreeNode v-if="tree" :node="tree" />
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TreeNode from './TreeNode.vue'

const props = defineProps({
  nodes:         { type: Array, default: () => [] },
  people:        { type: Array, default: () => [] },
  defaultRootId: { type: String, default: null },
})

const rootId = ref(props.defaultRootId)
const generations = ref(4)

const byId = computed(() => {
  const m = new Map()
  for (const n of props.nodes) m.set(String(n.id), n)
  return m
})

let shownCount = 0

function buildSubtree(id, depth, visited) {
  const key = String(id)
  if (visited.has(key)) return null
  visited.add(key)
  const node = byId.value.get(key)
  if (!node) return null
  shownCount++

  // בני זוג (מסומנים מ-byId, ללא ספירה כצאצא)
  const spouses = (node.rels.spouses || [])
    .map(sid => byId.value.get(String(sid)))
    .filter(Boolean)
    .map(s => ({
      id: s.id,
      name: `${s.data['first name']} ${s.data['last name']}`,
      gender: s.data.gender,
      avatar: s.data.avatar,
      is_deceased: s.data.is_deceased,
    }))

  // ילדים — רק עד מספר הדורות שנבחר
  const children = depth < generations.value
    ? (node.rels.children || [])
        .map(cid => buildSubtree(cid, depth + 1, visited))
        .filter(Boolean)
    : []

  return { id: node.id, person: node.data, spouses, children }
}

const tree = computed(() => {
  shownCount = 0
  if (!rootId.value) return null
  const t = buildSubtree(rootId.value, 0, new Set())
  return t
})

const countShown = computed(() => { void tree.value; return shownCount })

const rootName = computed(() => {
  const p = props.people.find(p => p.id === rootId.value)
  return p ? p.label : ''
})

function print() {
  window.print()
}
</script>

<style scoped>
.print-page {
  font-family: 'Rubik', sans-serif;
  background: #f4f8ff;
  min-height: 100vh;
  padding: 1.5rem;
}

.controls {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
  background: white;
  border: 1px solid #e6eefb;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.05);
}
.back { color: #2d6be4; text-decoration: none; font-size: 0.9rem; }
.control-group { display: flex; align-items: center; gap: 0.5rem; }
.control-group label { font-size: 0.9rem; color: #4a5568; }
.control-group select {
  padding: 0.4rem 0.6rem; border: 1px solid #d7e2f5; border-radius: 8px;
  font-family: inherit; font-size: 0.9rem;
}
.print-btn {
  margin-right: auto;
  background: #2d6be4; color: white; border: none; border-radius: 9px;
  padding: 0.55rem 1.4rem; font-size: 0.95rem; font-weight: 600;
  cursor: pointer; font-family: inherit;
}
.print-btn:hover { background: #1a55c8; }

.printable {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 10px rgba(0,50,150,0.05);
}
.print-title { font-size: 1.5rem; color: #1a3a6b; margin: 0 0 0.25rem; text-align: center; }
.print-sub { text-align: center; color: #6b7a99; margin: 0 0 2rem; font-size: 0.9rem; }

.tree-root { list-style: none; margin: 0; padding: 0; }

/* הדפסה */
@media print {
  .no-print { display: none !important; }
  .print-page { background: white; padding: 0; }
  .printable { box-shadow: none; border-radius: 0; padding: 0; }
  @page { margin: 1cm; }
}
</style>
