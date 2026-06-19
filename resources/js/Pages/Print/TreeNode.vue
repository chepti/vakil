<template>
  <li class="tree-node">
    <div class="couple">
      <!-- האדם עצמו -->
      <div class="person-box" :class="node.person.gender === 'M' ? 'male' : 'female'">
        <img v-if="node.person.avatar" :src="node.person.avatar" class="pb-photo" />
        <div v-else class="pb-photo placeholder">{{ initial }}</div>
        <div class="pb-text">
          <div class="pb-name">
            {{ node.person['first name'] }} {{ node.person['last name'] }}
            <span v-if="node.person.is_deceased" class="zl">ז"ל</span>
          </div>
          <div class="pb-meta" v-if="years">{{ years }}</div>
        </div>
      </div>

      <!-- בני זוג -->
      <template v-for="sp in node.spouses" :key="sp.id">
        <span class="heart">♥</span>
        <div class="person-box spouse" :class="sp.gender === 'M' ? 'male' : 'female'">
          <img v-if="sp.avatar" :src="sp.avatar" class="pb-photo" />
          <div v-else class="pb-photo placeholder">{{ spInitial(sp) }}</div>
          <div class="pb-text">
            <div class="pb-name">{{ sp.name }}<span v-if="sp.is_deceased" class="zl">ז"ל</span></div>
          </div>
        </div>
      </template>
    </div>

    <!-- ילדים -->
    <ul v-if="node.children.length" class="children">
      <TreeNode v-for="child in node.children" :key="child.id" :node="child" />
    </ul>
  </li>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  node: { type: Object, required: true },
})

const initial = computed(() => (props.node.person['first name'] || '?').charAt(0))

const years = computed(() => {
  const b = props.node.person.birthday
  const d = props.node.person.death_date
  const by = b ? new Date(b).getFullYear() : null
  const dy = d ? new Date(d).getFullYear() : null
  if (by && dy) return `${by}–${dy}`
  if (by) return `${by}`
  return ''
})

function spInitial(sp) {
  return (sp.name || '?').charAt(0)
}
</script>

<style scoped>
.tree-node { position: relative; }

.couple {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.35rem;
}

.person-box {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: white;
  border: 1.5px solid #cdddf5;
  border-radius: 10px;
  padding: 0.3rem 0.6rem 0.3rem 0.3rem;
}
.person-box.male { border-color: #9cc0f0; background: #f3f8ff; }
.person-box.female { border-color: #d9b8ec; background: #faf3ff; }

.pb-photo {
  width: 34px; height: 34px; border-radius: 50%;
  object-fit: cover; flex-shrink: 0;
}
.pb-photo.placeholder {
  display: flex; align-items: center; justify-content: center;
  background: #e6eefb; color: #2d6be4; font-weight: 700; font-size: 0.95rem;
}
.pb-name { font-weight: 600; color: #1a3a6b; font-size: 0.9rem; }
.pb-meta { font-size: 0.72rem; color: #6b7a99; }
.zl { font-size: 0.7rem; color: #888; margin-right: 0.25rem; }
.heart { color: #e08aa8; font-size: 0.85rem; }

/* קווי חיבור */
.children {
  list-style: none;
  margin: 0;
  padding-right: 1.5rem;
  border-right: 2px solid #cdddf5;
  margin-right: 1.1rem;
}
.children > .tree-node { padding-top: 0.35rem; position: relative; }
.children > .tree-node::before {
  content: '';
  position: absolute;
  top: 1.1rem;
  right: -1.5rem;
  width: 1.5rem;
  height: 2px;
  background: #cdddf5;
}
</style>
