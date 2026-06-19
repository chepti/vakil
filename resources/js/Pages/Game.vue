<template>
  <AppLayout title="משחק המשפחה">
    <div class="game-page" dir="rtl">

      <div class="game-header">
        <h1>🎮 הדרך אל סבתא ואקיל</h1>
        <div class="score-box">
          <span class="score-label">ניקוד</span>
          <span class="score-value">{{ score }}</span>
        </div>
      </div>

      <div v-if="!mainPerson" class="notice">
        כדי לשחק צריך להגדיר דמות מרכזית (סבתא ואקיל) בעץ המשפחה.
      </div>

      <div v-else-if="loading" class="notice">טוען סבב חדש…</div>

      <div v-else-if="loadError" class="notice">
        {{ loadError }}
        <button class="btn-primary" @click="newRound">נסה שוב</button>
      </div>

      <template v-else>
        <p class="game-intro">
          מי הדמות בתמונה? 🤔 בנו את שרשרת המשפחה כלפי מעלה — מי ההורה? מי הסבא/סבתא? — עד שתגיעו אל
          <strong>סבתא ואקיל</strong>. לא יודעים מי בתמונה? בקשו רמז זהות (זה עולה בנקודות).
        </p>

        <div class="game-board">

          <!-- ── הסולם ── -->
          <div class="ladder">

            <div class="rung goal" :class="{ reached: finished }">
              <div class="avatar grandma">
                <img v-if="mainPerson.photo_url" :src="mainPerson.photo_url" :alt="mainPerson.full_name" />
                <span v-else class="initials">{{ initials(mainPerson.full_name) }}</span>
              </div>
              <div class="rung-info">
                <span class="goal-crown">👑 סבתא ואקיל</span>
                <span class="rung-name">{{ mainPerson.full_name }}</span>
              </div>
            </div>

            <div class="connector" :class="{ active: finished }"></div>

            <template v-for="step in reversedSteps" :key="'step-' + step.idx">
              <div
                class="rung slot"
                :class="{
                  solved: placed[step.idx] !== null,
                  active: step.idx === currentIndex && !finished,
                  locked: step.idx > currentIndex,
                  shake: shakeIdx === step.idx,
                }"
                @dragover.prevent="step.idx === currentIndex && (dragOverSlot = true)"
                @dragleave="dragOverSlot = false"
                @drop.prevent="onDropSlot(step.idx)"
              >
                <template v-if="placed[step.idx] !== null">
                  <div class="avatar" :class="genderClass(placed[step.idx])">
                    <img v-if="photo(placed[step.idx])" :src="photo(placed[step.idx])" />
                    <span v-else class="initials">{{ initials(name(placed[step.idx])) }}</span>
                  </div>
                  <div class="rung-info">
                    <span class="rung-label">{{ step.label }}</span>
                    <span class="rung-name">{{ name(placed[step.idx]) }} ✓</span>
                    <span v-if="marriedIn[step.idx]" class="married-badge">
                      💍 גם {{ name(marriedIn[step.idx]) }}
                    </span>
                  </div>
                </template>

                <template v-else-if="step.idx === currentIndex">
                  <div class="avatar drop" :class="{ hover: dragOverSlot }">?</div>
                  <div class="rung-info">
                    <span class="rung-label">מי {{ step.label }}?</span>
                    <span v-if="marriedIn[step.idx]" class="married-badge">
                      💍 {{ name(marriedIn[step.idx]) }} (נכנס/ה בנישואין) — חסר ההורה מצד המשפחה
                    </span>
                    <span v-else class="rung-hint-text">גררו לכאן דמות, או הקלידו למטה</span>
                  </div>
                </template>

                <template v-else>
                  <div class="avatar locked">?</div>
                  <div class="rung-info">
                    <span class="rung-label">{{ step.label }}</span>
                  </div>
                </template>
              </div>
              <div class="connector" :class="{ active: placed[step.idx] !== null }"></div>
            </template>

            <!-- דמות המוצא — פנים בלבד -->
            <div class="rung target">
              <div class="avatar mystery" :class="genderClass(round.target_id)">
                <img v-if="photo(round.target_id)" :src="photo(round.target_id)" alt="?" />
                <span v-else class="initials">?</span>
              </div>
              <div class="rung-info">
                <span class="rung-label">נקודת ההתחלה</span>
                <span class="rung-name mystery-name">{{ targetDisplayName }}</span>
              </div>
            </div>
          </div>

          <!-- ── לוח שליטה ── -->
          <div class="controls" v-if="!finished">

            <!-- זהות הדמות המסתורית -->
            <div class="identity-box">
              <div class="identity-head">מי הדמות בתמונה? 🤔</div>
              <div v-if="targetHintLines.length" class="hint-box">
                <p v-for="(line, i) in targetHintLines" :key="i" class="hint-line">💡 {{ line }}</p>
              </div>
              <button class="btn-hint" @click="useTargetHint" :disabled="targetHintMaxed">
                {{ targetHintLabel }}
              </button>
            </div>

            <div class="current-question">
              מי <strong>{{ currentStep?.label }}</strong> של {{ childLabel(currentIndex) }}?
            </div>

            <input
              v-model="query"
              type="text"
              class="search-input"
              placeholder="הקלידו שם…"
              @keydown.enter.prevent="placeFirstResult"
            />

            <div class="results">
              <div
                v-for="p in searchResults"
                :key="p.id"
                class="person-chip"
                :class="genderClass(p.id)"
                draggable="true"
                @dragstart="dragPerson = p.id"
                @dragend="dragPerson = null"
                @click="attemptPlace(p.id)"
              >
                <div class="chip-avatar">
                  <img v-if="p.photo_url" :src="p.photo_url" />
                  <span v-else>{{ initials(p.full_name) }}</span>
                </div>
                <span class="chip-name">{{ p.full_name }}</span>
              </div>
              <div v-if="query && !searchResults.length" class="no-results">לא נמצאו תוצאות</div>
            </div>

            <!-- רמז לשלב הנוכחי: אפשרויות -->
            <button
              v-if="!showOptions[currentIndex]"
              class="btn-hint btn-hint-options"
              @click="revealOptions"
            >
              💡 תקועים? הציגו אפשרויות לשלב זה (−60)
            </button>

            <div v-else class="hint-options">
              <p class="hint-title">אחת מאלה היא התשובה:</p>
              <div class="results">
                <div
                  v-for="pid in currentStep.options"
                  :key="'opt-' + pid"
                  class="person-chip hint"
                  :class="genderClass(pid)"
                  draggable="true"
                  @dragstart="dragPerson = pid"
                  @dragend="dragPerson = null"
                  @click="attemptPlace(pid)"
                >
                  <div class="chip-avatar">
                    <img v-if="photo(pid)" :src="photo(pid)" />
                    <span v-else>{{ initials(name(pid)) }}</span>
                  </div>
                  <span class="chip-name">{{ name(pid) }}</span>
                </div>
              </div>
            </div>

            <p v-if="feedback" class="feedback" :class="feedback.type">{{ feedback.text }}</p>
          </div>

          <div class="controls win" v-else>
            <div class="win-emoji">🎉</div>
            <h2>הגעתם אל סבתא ואקיל!</h2>
            <p class="win-score">צברתם <strong>{{ lastRoundScore }}</strong> נקודות בסבב הזה</p>
            <button class="btn-primary" @click="newRound">סבב חדש 🔄</button>
          </div>

        </div>
      </template>

      <canvas ref="confettiCanvas" class="confetti-canvas"></canvas>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  mainPerson: { type: Object, default: null },
  allPeople:  { type: Array,  default: () => [] },
})

const BASE_POINTS = 150
const OPTIONS_COST = 60                 // עלות "הצג אפשרויות" לשלב
const TARGET_HINT_COST = { 1: 40, 2: 30, 3: 20 }   // עלות רמזי זהות

const peopleById = computed(() => {
  const m = {}
  for (const p of props.allPeople) m[p.id] = p
  return m
})

const loading   = ref(false)
const loadError = ref(null)
const round     = ref(null)
const quizSteps = ref([])
const placed    = ref([])
const marriedIn = ref([])
const currentIndex = ref(0)
const targetHintLevel = ref(0)          // 0..3 — רמזי זהות לדמות ההתחלה
const showOptions = ref([])             // האם הוצגו אפשרויות לכל שלב
const wrongAttempts = ref([])
const score     = ref(0)
const lastRoundScore = ref(0)
const finished  = ref(false)
const query     = ref('')
const feedback  = ref(null)
const shakeIdx  = ref(null)
const dragPerson = ref(null)
const dragOverSlot = ref(false)

const confettiCanvas = ref(null)

const currentStep = computed(() => quizSteps.value[currentIndex.value] ?? null)
const reversedSteps = computed(() =>
  quizSteps.value.map((s, idx) => ({ ...s, idx })).slice().reverse()
)

const searchResults = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return []
  const used = new Set(placed.value.filter(Boolean))
  return props.allPeople
    .filter(p => p.id !== round.value?.target_id && !used.has(p.id))
    .filter(p => p.full_name.toLowerCase().includes(q))
    .slice(0, 8)
})

// ── רמזי זהות לדמות ההתחלה ──
const targetHintLines = computed(() => {
  const r = round.value
  const lvl = targetHintLevel.value
  if (!r) return []
  const lines = []
  if (lvl >= 1) lines.push('שם פרטי: ' + (r.target_first_name || '—'))
  if (lvl >= 2) lines.push('שם משפחה: ' + (r.target_last_name || '—'))
  if (lvl >= 3 && r.target_hint) lines.push(r.target_hint)
  return lines
})

const targetHintMaxed = computed(() => {
  const lvl = targetHintLevel.value
  if (lvl >= 3) return true
  if (lvl >= 2 && !round.value?.target_hint) return true
  return false
})

const targetHintLabel = computed(() => {
  const lvl = targetHintLevel.value
  if (lvl === 0) return '💡 רמז: שם פרטי (−40)'
  if (lvl === 1) return '💡 רמז: שם משפחה (−30)'
  if (lvl === 2 && round.value?.target_hint) return '💡 רמז: קשר משפחתי (−20)'
  return 'אין עוד רמזי זהות'
})

// שם הדמות שבתמונה — נחשף רק אם ביקשו רמז זהות
const targetDisplayName = computed(() => {
  if (targetHintLevel.value >= 2) return name(round.value?.target_id)
  if (targetHintLevel.value >= 1) return round.value?.target_first_name || 'מי זה? 🤔'
  return 'מי זה? 🤔'
})

function name(id)   { return peopleById.value[id]?.full_name ?? '—' }
function photo(id)  { return peopleById.value[id]?.photo_url ?? null }
function genderClass(id) { return peopleById.value[id]?.gender === 'female' ? 'female' : 'male' }
function initials(n) { return (n || '').split(' ').map(w => w[0]).join('').slice(0, 2) }

function childLabel(idx) {
  if (idx === 0) return targetHintLevel.value >= 1 ? targetDisplayName.value : 'הדמות בתמונה'
  return name(placed.value[idx - 1])
}

async function newRound() {
  loading.value = true
  loadError.value = null
  finished.value = false
  feedback.value = null
  try {
    // מונע מטמון דפדפן — אחרת חוזר אותו סבב שוב ושוב
    const res = await fetch('/api/game/round?_=' + Date.now(), {
      cache: 'no-store',
      headers: { 'Accept': 'application/json' },
    })
    if (!res.ok) {
      const body = await res.json().catch(() => ({}))
      throw new Error(body.error === 'no_eligible_people'
        ? 'אין מספיק דמויות עם תמונה כדי לשחק. הוסיפו תמונות פרופיל!'
        : 'לא הצלחנו להתחיל סבב. נסו שוב.')
    }
    const data = await res.json()
    round.value = data
    quizSteps.value = data.steps.filter(s => s.correct_id !== data.main_id)
    placed.value = quizSteps.value.map(() => null)
    marriedIn.value = quizSteps.value.map(() => null)
    wrongAttempts.value = quizSteps.value.map(() => 0)
    showOptions.value = quizSteps.value.map(() => false)
    targetHintLevel.value = 0
    currentIndex.value = 0
    query.value = ''
    lastRoundScore.value = 0

    if (quizSteps.value.length === 0) {
      finished.value = true
      lastRoundScore.value = 50
      score.value += 50
      celebrate()
    }
  } catch (e) {
    loadError.value = e.message
  } finally {
    loading.value = false
  }
}

function placeFirstResult() {
  if (searchResults.value.length) attemptPlace(searchResults.value[0].id)
}

function attemptPlace(personId) {
  if (finished.value || !currentStep.value) return
  const idx = currentIndex.value
  const step = currentStep.value

  if (personId === step.correct_id) {
    placed.value[idx] = personId
    const penalty = (showOptions.value[idx] ? OPTIONS_COST : 0) + 15 * wrongAttempts.value[idx]
    const award = Math.max(20, BASE_POINTS - penalty)
    score.value += award
    lastRoundScore.value += award
    feedback.value = { type: 'ok', text: `נכון! +${award}` }
    query.value = ''
    burstFromActiveSlot()

    if (idx + 1 >= quizSteps.value.length) {
      finished.value = true
      celebrate()
    } else {
      currentIndex.value++
    }
  } else if ((step.parent_ids || []).includes(personId)) {
    marriedIn.value[idx] = personId
    feedback.value = {
      type: 'info',
      text: `נכון, ${name(personId)} הוא/היא הורה! אבל זה ההורה שהתחתן/ה לתוך המשפחה — מי ההורה השני, מצד המשפחה?`,
    }
    query.value = ''
  } else {
    wrongAttempts.value[idx]++
    feedback.value = { type: 'err', text: 'לא מדויק… נסו שוב או בקשו רמז 💡' }
    shakeIdx.value = idx
    setTimeout(() => { shakeIdx.value = null }, 500)
  }
}

function onDropSlot(idx) {
  dragOverSlot.value = false
  if (idx !== currentIndex.value || dragPerson.value == null) return
  attemptPlace(dragPerson.value)
  dragPerson.value = null
}

function applyPenalty(n) {
  score.value = Math.max(0, score.value - n)
  lastRoundScore.value = Math.max(0, lastRoundScore.value - n)
}

function useTargetHint() {
  const lvl = targetHintLevel.value
  if (lvl === 0) { targetHintLevel.value = 1; applyPenalty(TARGET_HINT_COST[1]) }
  else if (lvl === 1) { targetHintLevel.value = 2; applyPenalty(TARGET_HINT_COST[2]) }
  else if (lvl === 2 && round.value?.target_hint) { targetHintLevel.value = 3; applyPenalty(TARGET_HINT_COST[3]) }
}

function revealOptions() {
  showOptions.value[currentIndex.value] = true
}

// ── קונפטי ──
function burstFromActiveSlot() { fireConfetti(60, window.innerWidth / 2, window.innerHeight / 2) }
function celebrate() { fireConfetti(220, window.innerWidth / 2, window.innerHeight / 3) }

function fireConfetti(count, ox, oy) {
  const canvas = confettiCanvas.value
  if (!canvas) return
  canvas.width = window.innerWidth
  canvas.height = window.innerHeight
  const ctx = canvas.getContext('2d')
  const colors = ['#2d6be4', '#8b5cf6', '#f59e0b', '#22c55e', '#ec4899', '#06b6d4']
  const parts = Array.from({ length: count }, (_, i) => ({
    x: ox, y: oy,
    vx: (((i * 73) % 100) / 100 - 0.5) * 14,
    vy: -(((i * 37) % 100) / 100) * 14 - 4,
    g: 0.35,
    size: 5 + (i % 4) * 2,
    color: colors[i % colors.length],
    rot: (i % 360) * Math.PI / 180,
    vr: ((i % 10) - 5) * 0.1,
    life: 0,
  }))
  let frame = 0
  function tick() {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    let alive = false
    for (const p of parts) {
      p.vy += p.g; p.x += p.vx; p.y += p.vy; p.rot += p.vr; p.life++
      if (p.y < canvas.height + 30) alive = true
      ctx.save()
      ctx.translate(p.x, p.y)
      ctx.rotate(p.rot)
      ctx.fillStyle = p.color
      ctx.globalAlpha = Math.max(0, 1 - p.life / 110)
      ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6)
      ctx.restore()
    }
    frame++
    if (alive && frame < 130) requestAnimationFrame(tick)
    else ctx.clearRect(0, 0, canvas.width, canvas.height)
  }
  requestAnimationFrame(tick)
}

onMounted(() => { if (props.mainPerson) newRound() })
</script>

<style scoped>
.game-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem 1.5rem 4rem;
  font-family: 'Rubik', sans-serif;
}

.game-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.game-header h1 { font-size: 1.5rem; color: #1a3a6b; margin: 0; }
.score-box {
  display: flex; flex-direction: column; align-items: center;
  background: #1a3a6b; color: white; border-radius: 12px; padding: 0.4rem 1rem; min-width: 80px;
}
.score-label { font-size: 0.7rem; opacity: 0.8; }
.score-value { font-size: 1.4rem; font-weight: 700; }

.notice {
  background: white; border-radius: 14px; padding: 2rem; text-align: center;
  color: #4a5568; box-shadow: 0 2px 12px rgba(0,50,150,.07); font-size: 1rem;
}
.game-intro { color: #4a5568; font-size: 0.95rem; line-height: 1.6; margin: 0 0 1.5rem; }

.game-board { display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap; }

.ladder { flex: 1 1 340px; display: flex; flex-direction: column; align-items: center; min-width: 300px; }

.rung {
  display: flex; align-items: center; gap: 1rem; width: 100%; max-width: 360px;
  background: white; border-radius: 14px; padding: 0.75rem 1rem;
  box-shadow: 0 2px 10px rgba(0,50,150,.07); border: 2px solid transparent;
}
.rung.goal { border-color: #f59e0b; background: linear-gradient(135deg, #fffaf0, #fff); }
.rung.goal.reached { box-shadow: 0 0 0 4px #fde68a, 0 6px 20px rgba(245,158,11,.3); }
.rung.target { border-color: #2d6be4; background: linear-gradient(135deg, #f0f7ff, #fff); }
.rung.active { border-color: #2d6be4; box-shadow: 0 0 0 4px #dbeafe; }
.rung.solved { border-color: #22c55e; }
.rung.locked { opacity: 0.55; }
.rung.shake { animation: shake 0.45s; }

@keyframes shake {
  0%,100% { transform: translateX(0); }
  20%,60% { transform: translateX(-8px); }
  40%,80% { transform: translateX(8px); }
}

.avatar {
  width: 60px; height: 60px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: #e8f0fe; color: #2d6be4; font-weight: 700; font-size: 1.2rem;
  border: 3px solid #dbeafe;
}
.avatar.female { background: #fdf4ff; color: #8b5cf6; border-color: #e9d5ff; }
.avatar.grandma { border-color: #fbbf24; background: #fef3c7; color: #b45309; }
.avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar.mystery { border-color: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
.avatar.drop { background: #eef4ff; border: 2.5px dashed #2d6be4; color: #2d6be4; font-size: 1.6rem; }
.avatar.drop.hover { background: #dbeafe; transform: scale(1.06); }
.avatar.locked { background: #f1f5f9; border: 2.5px dashed #cbd5e1; color: #94a3b8; }

.rung-info { display: flex; flex-direction: column; gap: 0.15rem; }
.goal-crown { font-weight: 700; color: #b45309; font-size: 0.95rem; }
.rung-label { font-size: 0.78rem; color: #8a9ab5; }
.rung-name { font-size: 1rem; color: #1a3a6b; font-weight: 600; }
.mystery-name { color: #b45309; }
.rung-hint-text { font-size: 0.72rem; color: #aab; }
.married-badge { font-size: 0.72rem; color: #b45309; background: #fff7ed; border-radius: 6px; padding: 0.1rem 0.4rem; margin-top: 0.15rem; }

.connector { width: 3px; height: 22px; background: #d1dce8; }
.connector.active { background: #22c55e; }

.controls {
  flex: 1 1 280px; min-width: 260px; background: white; border-radius: 16px;
  padding: 1.25rem; box-shadow: 0 2px 12px rgba(0,50,150,.08);
  position: sticky; top: 75px;
}

.identity-box {
  background: #fffaf0; border: 1px solid #fde68a; border-radius: 12px;
  padding: 0.75rem; margin-bottom: 1rem;
}
.identity-head { font-size: 0.9rem; color: #92400e; font-weight: 600; margin-bottom: 0.5rem; }

.current-question { font-size: 0.95rem; color: #2d4a7a; margin-bottom: 0.75rem; }
.search-input {
  width: 100%; padding: 0.6rem 0.8rem; border: 1.5px solid #d1dce8; border-radius: 9px;
  font-size: 0.95rem; font-family: 'Rubik', sans-serif; direction: rtl; box-sizing: border-box;
}
.search-input:focus { outline: none; border-color: #2d6be4; }

.results { display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.6rem; }
.person-chip {
  display: flex; align-items: center; gap: 0.6rem; padding: 0.4rem 0.6rem;
  border: 1.5px solid #e4eefb; border-radius: 10px; background: white; cursor: pointer;
  transition: all 0.15s; user-select: none;
}
.person-chip:hover { border-color: #2d6be4; background: #edf3ff; }
.person-chip:active { cursor: grabbing; }
.person-chip.hint { border-color: #f59e0b; background: #fffaf0; }
.person-chip.female:hover { border-color: #8b5cf6; background: #faf5ff; }
.chip-avatar {
  width: 34px; height: 34px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; color: #2d6be4;
}
.chip-avatar img { width: 100%; height: 100%; object-fit: cover; }
.chip-name { font-size: 0.9rem; color: #2d4a7a; font-weight: 500; }
.no-results { font-size: 0.85rem; color: #aab; text-align: center; padding: 0.5rem; }

.hint-box { background: #fff; border: 1px solid #fde68a; border-radius: 9px; padding: 0.4rem 0.6rem; margin-bottom: 0.5rem; }
.hint-line { font-size: 0.85rem; color: #92400e; margin: 0.15rem 0; }

.btn-hint {
  margin-top: 0.5rem; width: 100%; padding: 0.5rem; border: 1.5px solid #f59e0b;
  background: #fffaf0; color: #b45309; border-radius: 9px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.85rem; font-weight: 600;
}
.btn-hint:disabled { opacity: 0.5; cursor: default; }
.btn-hint-options { margin-top: 0.85rem; border-style: dashed; }

.hint-options { margin-top: 0.85rem; }
.hint-title { font-size: 0.82rem; color: #b45309; margin: 0 0 0.4rem; }

.feedback { margin-top: 0.85rem; font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 9px; text-align: center; }
.feedback.ok   { background: #f0fdf4; color: #166534; }
.feedback.err  { background: #fef2f2; color: #991b1b; }
.feedback.info { background: #eff6ff; color: #1e40af; }

.controls.win { text-align: center; }
.win-emoji { font-size: 3rem; }
.controls.win h2 { color: #1a3a6b; font-size: 1.3rem; margin: 0.5rem 0; }
.win-score { color: #4a5568; margin-bottom: 1.25rem; }

.btn-primary {
  background: #2d6be4; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 9px;
  cursor: pointer; font-family: 'Rubik', sans-serif; font-weight: 600; font-size: 0.95rem;
  margin-top: 0.5rem;
}
.btn-primary:hover { background: #1a55c8; }

.confetti-canvas { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }

@media (max-width: 640px) {
  .controls { position: static; }
}
</style>
