<template>
  <AppLayout title="משחק המשפחה">
    <div class="game-page" dir="rtl">

      <div class="game-header">
        <h1>🎮 הדרך אל {{ mainPerson ? mainPerson.full_name : 'השורש' }}</h1>
        <div class="score-box" :class="{ bump: scoreBump }">
          <span class="score-label">ניקוד</span>
          <span class="score-value">{{ score }}</span>
          <span v-if="scoreDelta" class="score-delta">{{ scoreDelta }}</span>
        </div>
      </div>

      <div v-if="!mainPerson" class="notice">
        כדי לשחק צריך להגדיר דמות מרכזית בעץ המשפחה.
      </div>

      <div v-else-if="loading" class="notice">טוען סבב חדש…</div>

      <div v-else-if="loadError" class="notice">
        {{ loadError }}
        <button class="btn-primary" @click="newRound">נסה שוב</button>
      </div>

      <template v-else>
        <p class="game-intro">
          מי הדמות בתמונה? 🤔 בנו את שרשרת המשפחה כלפי מעלה — מי ההורה? מי הסבא/סבתא? — עד שתגיעו אל
          <strong>{{ mainPerson.full_name }}</strong>. לא יודעים מי בתמונה? בקשו רמז זהות (זה עולה בנקודות).
        </p>

        <div class="game-board">

          <!-- ── הסולם ── -->
          <div class="ladder">

            <div class="rung goal" :class="{ reached: finished, 'pop-in': finished }">
              <div class="avatar avatar-md grandma">
                <img v-if="mainPerson.photo_url" :src="mainPerson.photo_url" :alt="mainPerson.full_name" />
                <span v-else class="initials">{{ initials(mainPerson.full_name) }}</span>
              </div>
              <div class="rung-info">
                <span class="goal-crown">👑 {{ mainPerson.full_name }}</span>
                <span class="rung-name">{{ mainPerson.full_name }}</span>
              </div>
            </div>

            <div class="connector" :class="{ active: finished }"></div>

            <template v-for="step in reversedSteps" :key="'step-' + step.idx">
              <div
                class="rung slot"
                :class="{
                  solved: placed[step.idx] !== null,
                  'pop-in': placed[step.idx] !== null,
                  active: step.idx === currentIndex && !finished,
                  locked: step.idx > currentIndex,
                  shake: shakeIdx === step.idx,
                  pulse: step.idx === currentIndex && !finished,
                }"
                @dragover.prevent="step.idx === currentIndex && (dragOverSlot = true)"
                @dragleave="dragOverSlot = false"
                @drop.prevent="onDropSlot(step.idx)"
              >
                <template v-if="placed[step.idx] !== null">
                  <div class="avatar avatar-md" :class="genderClass(placed[step.idx])">
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
                  <div class="avatar avatar-md drop" :class="{ hover: dragOverSlot }">?</div>
                  <div class="rung-info">
                    <span class="rung-label">מי {{ step.label }}?</span>
                    <span v-if="marriedIn[step.idx]" class="married-badge">
                      💍 {{ name(marriedIn[step.idx]) }} (נכנס/ה בנישואין) — חסר ההורה מצד המשפחה
                    </span>
                    <span v-else class="rung-hint-text">גררו לכאן דמות, או הקלידו למטה</span>
                  </div>
                </template>

                <template v-else>
                  <div class="avatar avatar-md locked">?</div>
                  <div class="rung-info">
                    <span class="rung-label">{{ step.label }}</span>
                  </div>
                </template>
              </div>
              <div class="connector" :class="{ active: placed[step.idx] !== null }"></div>
            </template>

            <!-- דמות המוצא — תמונה גדולה -->
            <div class="rung target hero" :class="{ revealed: targetRevealed, float: !targetRevealed }">
              <div class="avatar avatar-hero mystery" :class="genderClass(round.target_id)">
                <img v-if="photo(round.target_id)" :src="photo(round.target_id)" alt="?" />
                <span v-else class="initials">?</span>
              </div>
              <div class="rung-info">
                <span class="rung-label">נקודת ההתחלה</span>
                <span
                  class="rung-name mystery-name"
                  :class="{ 'name-reveal': targetRevealed }"
                >{{ targetDisplayName }}</span>
              </div>
            </div>
          </div>

          <!-- ── לוח שליטה ── -->
          <div class="controls" v-if="!finished">

            <!-- זהות הדמות המסתורית -->
            <div class="identity-box" :class="{ solved: targetIdentitySolved }">
              <div class="identity-head">מי הדמות בתמונה? 🤔</div>

              <div v-if="!targetIdentitySolved && !finished" class="identity-guess">
                <input
                  v-model="targetGuess"
                  type="text"
                  class="search-input identity-input"
                  placeholder="נחשו את השם המלא…"
                  @keydown.enter.prevent="attemptTargetGuess"
                />
                <button class="btn-guess" @click="attemptTargetGuess" :disabled="!targetGuess.trim()">
                  🎯 זהו!
                </button>
                <p class="identity-bonus-hint">
                  ניחוש נכון בלי רמזים: <strong>+{{ TARGET_IDENTITY_BONUS[0] }}</strong> נקודות!
                </p>
              </div>

              <div v-else-if="targetIdentitySolved" class="identity-solved">
                <span class="identity-solved-emoji">✨</span>
                <span class="identity-solved-name">{{ name(round.target_id) }}</span>
                <span v-if="targetIdentityBonus > 0" class="identity-solved-pts">+{{ targetIdentityBonus }}</span>
              </div>

              <div v-if="targetHintLines.length" class="hint-box">
                <p v-for="(line, i) in targetHintLines" :key="i" class="hint-line">💡 {{ line }}</p>
              </div>
              <button
                v-if="!targetIdentitySolved"
                class="btn-hint"
                @click="useTargetHint"
                :disabled="targetHintMaxed"
              >
                {{ targetHintLabel }}
              </button>
            </div>

            <div class="current-question">
              מי <strong>{{ currentStep?.label }}</strong> של {{ subjectName }}?
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
            <div class="win-emoji bounce">🎉</div>
            <h2>הגעתם אל {{ mainPerson.full_name }}!</h2>

            <div class="win-reveal pop-in">
              <div class="win-reveal-photo">
                <img v-if="photo(round.target_id)" :src="photo(round.target_id)" :alt="name(round.target_id)" />
                <span v-else class="initials">{{ initials(name(round.target_id)) }}</span>
              </div>
              <p class="win-reveal-label">זוהי הייתה</p>
              <p class="win-reveal-name name-reveal">{{ name(round.target_id) }}</p>
            </div>

            <p class="win-score">צברתם <strong>{{ lastRoundScore }}</strong> נקודות בסבב הזה</p>
            <button class="btn-primary pulse-btn" @click="newRound">סבב חדש 🔄</button>
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
const OPTIONS_COST = 60
const TARGET_HINT_COST = { 1: 40, 2: 30, 3: 20 }
const TARGET_IDENTITY_BONUS = { 0: 300, 1: 120, 2: 50, 3: 0 }

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
const targetHintLevel = ref(0)
const targetIdentitySolved = ref(false)
const targetIdentityBonus = ref(0)
const targetGuess = ref('')
const showOptions = ref([])
const wrongAttempts = ref([])
const score     = ref(0)
const lastRoundScore = ref(0)
const scoreBump = ref(false)
const scoreDelta = ref('')
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

// שם הדמות שבתמונה — נחשף אחרי ניחוש, רמז, או סיום
const targetRevealed = computed(() => targetIdentitySolved.value || finished.value)

const targetDisplayName = computed(() => {
  if (targetRevealed.value) return name(round.value?.target_id)
  if (targetHintLevel.value >= 2) return name(round.value?.target_id)
  if (targetHintLevel.value >= 1) return round.value?.target_first_name || 'מי זה? 🤔'
  return 'מי זה? 🤔'
})

function name(id)   { return peopleById.value[id]?.full_name ?? '—' }
function photo(id)  { return peopleById.value[id]?.photo_url ?? null }
function genderClass(id) { return peopleById.value[id]?.gender === 'female' ? 'female' : 'male' }
function initials(n) { return (n || '').split(' ').map(w => w[0]).join('').slice(0, 2) }

// כל השאלות מתייחסות לדמות ההתחלה (היעד), שכל הדרגות נמדדות ממנה.
const subjectName = computed(() =>
  targetHintLevel.value >= 1 ? targetDisplayName.value : 'הדמות בתמונה'
)

function normalizeName(s) {
  return (s || '').trim().toLowerCase().replace(/\s+/g, ' ')
}

function isTargetGuessCorrect(guess) {
  const full = normalizeName(name(round.value?.target_id))
  const g = normalizeName(guess)
  if (!g || !full) return false
  if (g === full) return true
  const parts = full.split(' ')
  return parts.length >= 2 && parts.every(p => g.includes(p))
}

function awardPoints(n, label) {
  score.value += n
  lastRoundScore.value += n
  scoreDelta.value = `+${n}`
  scoreBump.value = true
  setTimeout(() => { scoreBump.value = false; scoreDelta.value = '' }, 900)
  if (label) feedback.value = { type: 'ok', text: label }
}

function attemptTargetGuess() {
  if (finished.value || targetIdentitySolved.value || !round.value) return
  const g = targetGuess.value.trim()
  if (!g) return

  if (isTargetGuessCorrect(g)) {
    targetIdentitySolved.value = true
    const bonus = TARGET_IDENTITY_BONUS[targetHintLevel.value] ?? 0
    targetIdentityBonus.value = bonus
    if (bonus > 0) awardPoints(bonus, `מדהים! זיהיתם את ${name(round.value.target_id)}! +${bonus} 🌟`)
    else feedback.value = { type: 'ok', text: `נכון! זוהי ${name(round.value.target_id)}` }
    targetGuess.value = ''
    fireConfetti(100, window.innerWidth / 2, window.innerHeight * 0.55)
  } else {
    feedback.value = { type: 'err', text: 'לא בדיוק… נסו שוב או בקשו רמז 💡' }
  }
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
    targetIdentitySolved.value = false
    targetIdentityBonus.value = 0
    targetGuess.value = ''
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
    awardPoints(award, `נכון! +${award}`)
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
  display: flex; flex-direction: column; align-items: center; position: relative;
  background: #1a3a6b; color: white; border-radius: 12px; padding: 0.4rem 1rem; min-width: 80px;
  transition: transform 0.25s;
}
.score-box.bump { animation: score-bump 0.5s ease; }
.score-delta {
  position: absolute; top: -1.4rem; left: 50%; transform: translateX(-50%);
  font-size: 1rem; font-weight: 700; color: #22c55e;
  animation: float-up 0.9s ease forwards; pointer-events: none;
}
@keyframes score-bump {
  0%,100% { transform: scale(1); }
  40% { transform: scale(1.15); }
}
@keyframes float-up {
  0% { opacity: 1; transform: translateX(-50%) translateY(0); }
  100% { opacity: 0; transform: translateX(-50%) translateY(-28px); }
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
  display: flex; align-items: center; gap: 1.25rem; width: 100%; max-width: 420px;
  background: white; border-radius: 16px; padding: 1rem 1.25rem;
  box-shadow: 0 2px 10px rgba(0,50,150,.07); border: 2px solid transparent;
  transition: border-color 0.3s, box-shadow 0.3s, transform 0.3s;
}
.rung.hero {
  flex-direction: column; text-align: center; padding: 1.5rem;
  max-width: 280px; align-self: center;
}
.rung.hero .rung-info { align-items: center; }
.rung.hero.revealed { border-color: #22c55e; background: linear-gradient(135deg, #f0fdf4, #fff); }
.rung.target { border-color: #2d6be4; background: linear-gradient(135deg, #f0f7ff, #fff); }
.rung.target.float { animation: gentle-float 3s ease-in-out infinite; }
.rung.active { border-color: #2d6be4; box-shadow: 0 0 0 4px #dbeafe; }
.rung.pulse { animation: slot-pulse 1.8s ease-in-out infinite; }
.rung.solved { border-color: #22c55e; }
.rung.locked { opacity: 0.55; }
.rung.shake { animation: shake 0.45s; }
.rung.pop-in { animation: pop-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1); }

.rung.goal { border-color: #f59e0b; background: linear-gradient(135deg, #fffaf0, #fff); }
.rung.goal.reached { box-shadow: 0 0 0 4px #fde68a, 0 6px 20px rgba(245,158,11,.3); }

@keyframes gentle-float {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
@keyframes slot-pulse {
  0%,100% { box-shadow: 0 0 0 4px #dbeafe; }
  50% { box-shadow: 0 0 0 8px rgba(45,107,228,.25); }
}
@keyframes pop-in {
  0% { transform: scale(0.85); opacity: 0.6; }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes shake {
  0%,100% { transform: translateX(0); }
  20%,60% { transform: translateX(-8px); }
  40%,80% { transform: translateX(8px); }
}

.avatar {
  width: 72px; height: 72px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: #e8f0fe; color: #2d6be4; font-weight: 700; font-size: 1.4rem;
  border: 3px solid #dbeafe;
  transition: transform 0.25s;
}
.avatar.avatar-md { width: 96px; height: 96px; font-size: 1.6rem; border-width: 4px; }
.avatar.avatar-hero {
  width: 160px; height: 160px; font-size: 2.2rem; border-width: 5px;
  box-shadow: 0 8px 28px rgba(45,107,228,.2);
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
.rung-name { font-size: 1.05rem; color: #1a3a6b; font-weight: 600; }
.mystery-name { color: #b45309; font-size: 1.1rem; }
.mystery-name.name-reveal {
  font-size: 1.8rem; font-weight: 800; color: #166534;
  animation: name-reveal 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes name-reveal {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
.rung-hint-text { font-size: 0.72rem; color: #aab; }
.married-badge { font-size: 0.72rem; color: #b45309; background: #fff7ed; border-radius: 6px; padding: 0.1rem 0.4rem; margin-top: 0.15rem; }

.connector { width: 4px; height: 28px; background: #d1dce8; transition: background 0.4s, height 0.3s; }
.connector.active { background: linear-gradient(#22c55e, #16a34a); animation: grow-connector 0.35s ease; }
@keyframes grow-connector {
  0% { height: 0; opacity: 0; }
  100% { height: 28px; opacity: 1; }
}

.controls {
  flex: 1 1 280px; min-width: 260px; background: white; border-radius: 16px;
  padding: 1.25rem; box-shadow: 0 2px 12px rgba(0,50,150,.08);
  position: sticky; top: 75px;
}

.identity-box {
  background: #fffaf0; border: 1px solid #fde68a; border-radius: 12px;
  padding: 0.85rem; margin-bottom: 1rem;
  transition: background 0.3s, border-color 0.3s;
}
.identity-box.solved { background: #f0fdf4; border-color: #86efac; }
.identity-head { font-size: 0.95rem; color: #92400e; font-weight: 600; margin-bottom: 0.6rem; }
.identity-guess { display: flex; flex-direction: column; gap: 0.45rem; margin-bottom: 0.5rem; }
.identity-input { margin: 0; }
.identity-bonus-hint { font-size: 0.78rem; color: #b45309; margin: 0; text-align: center; }
.identity-solved {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 0.5rem; margin-bottom: 0.5rem;
}
.identity-solved-emoji { font-size: 1.4rem; }
.identity-solved-name { font-size: 1.3rem; font-weight: 800; color: #166534; }
.identity-solved-pts { font-size: 0.9rem; font-weight: 700; color: #22c55e; }

.btn-guess {
  width: 100%; padding: 0.55rem; border: none; border-radius: 9px;
  background: linear-gradient(135deg, #2d6be4, #1a55c8); color: white;
  font-family: 'Rubik', sans-serif; font-size: 0.95rem; font-weight: 700;
  cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
}
.btn-guess:hover:not(:disabled) { transform: scale(1.02); box-shadow: 0 4px 14px rgba(45,107,228,.35); }
.btn-guess:disabled { opacity: 0.45; cursor: default; }

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
  width: 44px; height: 44px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; font-weight: 700; color: #2d6be4;
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
.win-emoji { font-size: 3.5rem; }
.win-emoji.bounce { animation: bounce 0.8s ease; }
@keyframes bounce {
  0%,100% { transform: translateY(0); }
  30% { transform: translateY(-18px); }
  50% { transform: translateY(-8px); }
  70% { transform: translateY(-12px); }
}
.controls.win h2 { color: #1a3a6b; font-size: 1.4rem; margin: 0.5rem 0; }

.win-reveal {
  background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
  border: 2px solid #86efac; border-radius: 20px;
  padding: 1.25rem; margin: 1rem 0;
}
.win-reveal-photo {
  width: 130px; height: 130px; border-radius: 50%; overflow: hidden;
  margin: 0 auto 0.75rem; border: 5px solid #22c55e;
  box-shadow: 0 8px 24px rgba(34,197,94,.25);
  display: flex; align-items: center; justify-content: center;
  background: #e8f0fe; font-size: 2rem; font-weight: 700; color: #2d6be4;
}
.win-reveal-photo img { width: 100%; height: 100%; object-fit: cover; }
.win-reveal-label { font-size: 0.9rem; color: #6b7280; margin: 0 0 0.25rem; }
.win-reveal-name {
  font-size: 2rem; font-weight: 800; color: #166534; margin: 0;
  line-height: 1.2;
}

.win-score { color: #4a5568; margin-bottom: 1.25rem; }
.pulse-btn { animation: pulse-btn 2s ease-in-out infinite; }
@keyframes pulse-btn {
  0%,100% { box-shadow: 0 0 0 0 rgba(45,107,228,.4); }
  50% { box-shadow: 0 0 0 8px rgba(45,107,228,0); }
}

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
