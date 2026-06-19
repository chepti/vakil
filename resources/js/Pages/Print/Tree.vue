<template>
  <div class="print-page" dir="rtl">
    <!-- בקרה (לא מודפס) -->
    <div class="controls no-print">
      <Link href="/family-tree" class="back">→ חזרה לעץ</Link>

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

      <div class="mode-toggle">
        <button :class="{ active: mode === 'tree' }" @click="mode = 'tree'">🌳 עץ</button>
        <button :class="{ active: mode === 'list' }" @click="mode = 'list'">☰ רשימה</button>
        <button :class="{ active: mode === 'radial' }" @click="mode = 'radial'">◎ מעוגל</button>
      </div>

      <button class="print-btn" @click="print">🖨️ הדפס / שמור כ-PDF</button>
    </div>

    <!-- אזור ההדפסה -->
    <div class="printable">
      <h1 class="print-title">משפחת ואקיל — {{ rootName }}</h1>
      <p class="print-sub">{{ generations }} דורות · {{ countShown }} בני משפחה</p>

      <div class="tree-scroll">
        <!-- עץ מלמעלה למטה -->
        <ul v-if="mode === 'tree' && tree" class="tree-root vertical">
          <TreeNodeVertical :node="tree" />
        </ul>

        <!-- רשימה צידית -->
        <ul v-else-if="mode === 'list' && tree" class="tree-root">
          <TreeNode :node="tree" />
        </ul>

        <!-- מעוגל -->
        <RadialTree v-else-if="mode === 'radial' && tree" :tree="tree" :depth="generations" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TreeNode from './TreeNode.vue'
import TreeNodeVertical from './TreeNodeVertical.vue'
import RadialTree from './RadialTree.vue'

const props = defineProps({
  nodes:         { type: Array, default: () => [] },
  people:        { type: Array, default: () => [] },
  defaultRootId: { type: String, default: null },
})

const rootId = ref(props.defaultRootId)
const generations = ref(4)
const mode = ref('tree')

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
  return buildSubtree(rootId.value, 0, new Set())
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

.mode-toggle { display: flex; gap: 0.25rem; background: #f0f4fb; padding: 0.2rem; border-radius: 9px; }
.mode-toggle button {
  border: none; background: none; padding: 0.4rem 0.75rem; border-radius: 7px;
  cursor: pointer; font-family: inherit; font-size: 0.85rem; color: #6b7a99;
}
.mode-toggle button.active { background: white; color: #2d6be4; font-weight: 600; box-shadow: 0 1px 4px rgba(0,50,150,0.1); }

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

.tree-scroll { overflow-x: auto; }
.tree-root { list-style: none; margin: 0; padding: 0; }
.tree-root.vertical { display: flex; justify-content: center; min-width: max-content; }

/* הדפסה */
@media print {
  .no-print { display: none !important; }
  .print-page { background: white; padding: 0; }
  .printable { box-shadow: none; border-radius: 0; padding: 0; }
  .tree-scroll { overflow: visible; }
  @page { margin: 1cm; }
}
</style>
