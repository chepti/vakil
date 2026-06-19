<template>
  <svg :viewBox="`0 0 ${size} ${size}`" :width="size" :height="size" class="radial-svg">
    <!-- טבעות עזר -->
    <circle v-for="r in rings" :key="'ring'+r" :cx="cx" :cy="cy" :r="r" class="ring" />

    <!-- קווי חיבור -->
    <line v-for="(e, i) in edges" :key="'e'+i" :x1="e.x1" :y1="e.y1" :x2="e.x2" :y2="e.y2" class="edge" />

    <!-- צמתים -->
    <g v-for="n in placedNodes" :key="n.id" :transform="`translate(${n.x},${n.y})`">
      <clipPath :id="`clip-${uid}-${n.id}`"><circle :r="nodeR" /></clipPath>
      <circle :r="nodeR + 2" :class="['node-bg', n.gender === 'M' ? 'male' : 'female']" />
      <image v-if="n.avatar" :href="n.avatar" :x="-nodeR" :y="-nodeR" :width="nodeR*2" :height="nodeR*2"
             :clip-path="`url(#clip-${uid}-${n.id})`" preserveAspectRatio="xMidYMid slice" />
      <text v-else class="node-initial" dy="0.35em">{{ (n.name || '?').charAt(0) }}</text>
      <text class="node-label" :y="nodeR + 13">{{ n.name }}</text>
    </g>
  </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tree:  { type: Object, default: null },
  depth: { type: Number, default: 4 },
})

const uid = Math.floor(performance.now ? performance.now() % 100000 : 1)
const ringGap = 120
const nodeR = 22
const margin = 60

const maxDepth = computed(() => props.depth)
const radius = computed(() => maxDepth.value * ringGap)
const size = computed(() => (radius.value + margin) * 2)
const cx = computed(() => size.value / 2)
const cy = computed(() => size.value / 2)
const rings = computed(() => Array.from({ length: maxDepth.value }, (_, i) => (i + 1) * ringGap))

// ── חישוב משקל (מספר עלים) לכל תת-עץ ──
function countLeaves(node) {
  if (!node.children || !node.children.length) { node._leaves = 1; return 1 }
  node._leaves = node.children.reduce((s, c) => s + countLeaves(c), 0)
  return node._leaves
}

// ── הקצאת זוויות ומיקומים ──
const layout = computed(() => {
  if (!props.tree) return { nodes: [], edges: [] }
  const root = props.tree
  countLeaves(root)

  const nodes = []
  const edges = []

  function place(node, depth, a0, a1, parent) {
    const angle = (a0 + a1) / 2
    const r = depth * ringGap
    const x = cx.value + r * Math.cos(angle)
    const y = cy.value + r * Math.sin(angle)
    const placed = {
      id: node.id,
      name: node.person['first name'],
      gender: node.person.gender,
      avatar: node.person.avatar,
      x, y,
    }
    nodes.push(placed)
    if (parent) edges.push({ x1: parent.x, y1: parent.y, x2: x, y2: y })

    if (node.children && node.children.length) {
      let cursor = a0
      const span = a1 - a0
      for (const child of node.children) {
        const cSpan = span * (child._leaves / node._leaves)
        place(child, depth + 1, cursor, cursor + cSpan, placed)
        cursor += cSpan
      }
    }
  }

  // מתחילים מלמעלה (-90°) ופורשים 360°
  place(root, 0, -Math.PI / 2, -Math.PI / 2 + 2 * Math.PI, null)
  return { nodes, edges }
})

const placedNodes = computed(() => layout.value.nodes)
const edges = computed(() => layout.value.edges)
</script>

<style scoped>
.radial-svg { max-width: 100%; height: auto; display: block; margin: 0 auto; font-family: 'Rubik', sans-serif; }
.ring { fill: none; stroke: #eef3fb; stroke-width: 1; }
.edge { stroke: #cdddf5; stroke-width: 1.5; }
.node-bg { stroke-width: 2; }
.node-bg.male { fill: #f3f8ff; stroke: #9cc0f0; }
.node-bg.female { fill: #faf3ff; stroke: #d9b8ec; }
.node-initial { text-anchor: middle; fill: #2d6be4; font-weight: 700; font-size: 16px; }
.node-label { text-anchor: middle; fill: #1a3a6b; font-size: 11px; font-weight: 600; }
</style>
