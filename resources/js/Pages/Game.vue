<template>
  <AppLayout title="משחק המשפחה">
    <div class="game-page" dir="rtl">

      <!-- כותרת -->
      <header class="game-header">
        <div class="header-start">
          <h1>🎮 הדרך אל {{ mainPerson ? mainPerson.full_name : 'השורש' }}</h1>
          <button class="help-toggle" @click="showHelp = !showHelp" :aria-expanded="showHelp">
            {{ showHelp ? '✕' : '❓' }}
          </button>
        </div>
        <div class="header-actions">
          <button
            v-if="mainPerson && !loading && !loadError"
            class="btn-new-round"
            @click="requestNewRound"
            :disabled="loading"
            title="סבב חדש"
          >
            🔄 סבב חדש
          </button>
          <div class="score-box" :class="{ bump: scoreBump }">
          <span class="score-label">ניקוד</span>
          <span class="score-value">{{ score }}</span>
          <span v-if="scoreDelta" class="score-delta">{{ scoreDelta }}</span>
          </div>
        </div>
      </header>

      <Transition name="slide-down">
        <div v-if="showHelp" class="help-panel">
          מי הדמות בתמונה? בנו את שרשרת המשפחה כלפי מעלה — הורה, סבא/סבתא — עד
          <strong>{{ mainPerson?.full_name }}</strong>.
          ניחוש שם מלא בלי רמזים: <strong>+{{ TARGET_IDENTITY_BONUS[0] }}</strong>.
          אפשר גם לנחש חלק — שם פרטי <strong>+{{ PART_BONUS.first }}</strong>, שם משפחה <strong>+{{ PART_BONUS.last }}</strong> — ואז להשלים לניקוד נוסף!
        </div>
      </Transition>

      <div v-if="!mainPerson" class="notice">כדי לשחק צריך להגדיר דמות מרכזית בעץ המשפחה.</div>
      <div v-else-if="loading" class="notice loading-pulse">טוען סבב חדש…</div>
      <div v-else-if="loadError" class="notice">
        {{ loadError }}
        <button class="btn-primary" @click="newRound">נסה שוב</button>
      </div>

      <template v-else-if="!finished">

        <!-- שביל התקדמות אופקי -->
        <div class="trail-wrap">
          <div class="trail" ref="trailEl">
            <template v-for="(node, i) in trailNodes" :key="node.key">
              <div
                class="trail-node"
                :class="[node.state, { shake: node.shake }]"
              >
                <div class="trail-bubble" :class="node.bubbleClass">
                  <img v-if="node.photo" :src="node.photo" :alt="node.name || ''" />
                  <span v-else-if="node.state === 'active'" class="trail-q">?</span>
                  <span v-else-if="node.state === 'goal'" class="trail-crown">👑</span>
                  <span v-else-if="node.initials" class="trail-init">{{ node.initials }}</span>
                  <span v-else class="trail-dot"></span>
                </div>
                <span class="trail-label">{{ node.shortLabel }}</span>
                <span v-if="node.state === 'done'" class="trail-check">✓</span>
              </div>
              <div v-if="i < trailNodes.length - 1" class="trail-seg" :class="{ lit: node.state === 'done' }"></div>
            </template>
          </div>
        </div>

        <!-- שלב נוכחי -->
        <div class="step-badge">
          שלב {{ currentIndex + 1 }} מתוך {{ quizSteps.length }}
          <span v-if="!targetIdentitySolved" class="step-extra">· זיהוי אופציונלי</span>
          <span v-else-if="identityPartsFound.first || identityPartsFound.last" class="step-extra partial">· זיהוי חלקי</span>
        </div>

        <!-- במה מרכזית -->
        <section class="stage" :class="{ 'stage-revealed': targetIdentitySolved, 'stage-partial': !targetIdentitySolved && (identityPartsFound.first || identityPartsFound.last) }">

          <div class="stage-photo-wrap" :class="{ float: !targetIdentitySolved }">
            <div class="stage-photo" :class="[genderClass(round.target_id), { revealed: targetIdentitySolved, partial: !targetIdentitySolved && (identityPartsFound.first || identityPartsFound.last) }]">
              <img v-if="photo(round.target_id)" :src="photo(round.target_id)" alt="?" />
              <span v-else class="initials-lg">?</span>
            </div>
            <div v-if="targetDisplayOnStage" class="stage-name-reveal name-reveal" :class="{ partial: !targetIdentitySolved }">
              {{ targetDisplayOnStage }}
            </div>
            <div v-else class="stage-mystery">מי זה? 🤔</div>
          </div>

          <!-- כרטיס פעולה -->
          <div class="action-card">

            <!-- זיהוי (אופציונלי) -->
            <div v-if="!targetIdentitySolved" class="mission mission-id" :class="{ collapsed: identityCollapsed }">
              <button class="mission-head" @click="identityCollapsed = !identityCollapsed">
                <span class="mission-icon">🎯</span>
                <span class="mission-title">נחשו מי בדמות</span>
                <span class="mission-bonus">עד +{{ TARGET_IDENTITY_BONUS[0] }}</span>
                <span class="mission-chevron">{{ identityCollapsed ? '◀' : '▼' }}</span>
              </button>

              <Transition name="expand">
                <div v-if="!identityCollapsed" class="mission-body">
                  <div v-if="identityPartsFound.first || identityPartsFound.last" class="identity-progress pop-in">
                    <span v-if="identityPartsFound.first" class="part-chip done">✓ {{ round.target_first_name }}</span>
                    <span v-else class="part-chip missing">שם פרטי?</span>
                    <span v-if="identityPartsFound.last" class="part-chip done">✓ {{ round.target_last_name }}</span>
                    <span v-else class="part-chip missing">שם משפחה?</span>
                  </div>

                  <p v-if="identityNextHint" class="identity-next-hint">{{ identityNextHint }}</p>

                  <div class="input-row">
                    <input
                      v-model="targetGuess"
                      type="text"
                      class="search-input"
                      :placeholder="identityInputPlaceholder"
                      @keydown.enter.prevent="attemptTargetGuess"
                    />
                    <button class="btn-guess" @click="attemptTargetGuess" :disabled="!targetGuess.trim()">זהו!</button>
                  </div>
                  <p class="identity-bonus-hint">
                    שם פרטי: <strong>+{{ partBonusFor('first') }}</strong> ·
                    שם משפחה: <strong>+{{ partBonusFor('last') }}</strong> ·
                    מלא בבת אחת: <strong>+{{ TARGET_IDENTITY_BONUS[0] }}</strong>
                  </p>
                  <div v-if="targetHintLines.length" class="hint-box">
                    <p v-for="(line, i) in targetHintLines" :key="i" class="hint-line">💡 {{ line }}</p>
                  </div>
                  <button class="btn-hint" @click="useTargetHint" :disabled="targetHintMaxed">
                    {{ targetHintLabel }}
                  </button>
                </div>
              </Transition>
            </div>

            <div v-else class="identity-done pop-in">
              <span>✨ {{ name(round.target_id) }}</span>
              <span v-if="targetIdentityBonus > 0" class="pts">+{{ targetIdentityBonus }}</span>
            </div>
            <div class="mission mission-climb">
              <div class="mission-head static">
                <span class="mission-icon">🧗</span>
                <span class="mission-title">
                  מי <strong>{{ currentStep?.label }}</strong> של {{ subjectName }}?
                </span>
              </div>

              <div class="mission-body">
                <input
                  v-model="query"
                  type="text"
                  class="search-input search-lg"
                  placeholder="הקלידו שם…"
                  @keydown.enter.prevent="placeFirstResult"
                  autofocus
                />

                <div v-if="searchResults.length" class="results-grid">
                  <button
                    v-for="p in searchResults"
                    :key="p.id"
                    class="pick-card"
                    :class="genderClass(p.id)"
                    @click="attemptPlace(p.id)"
                  >
                    <div class="pick-photo">
                      <img v-if="p.photo_url" :src="p.photo_url" />
                      <span v-else>{{ initials(p.full_name) }}</span>
                    </div>
                    <span class="pick-name">{{ p.full_name }}</span>
                  </button>
                </div>
                <div v-else-if="query" class="no-results">לא נמצאו תוצאות</div>

                <button
                  v-if="!showOptions[currentIndex]"
                  class="btn-hint btn-hint-options"
                  @click="revealOptions"
                >
                  💡 תקועים? הציגו אפשרויות (−60)
                </button>

                <div v-if="showOptions[currentIndex]" class="hint-options">
                  <p class="hint-title">אחת מאלה:</p>
                  <div class="results-grid">
                    <button
                      v-for="pid in currentStep.options"
                      :key="'opt-' + pid"
                      class="pick-card hint"
                      :class="genderClass(pid)"
                      @click="attemptPlace(pid)"
                    >
                      <div class="pick-photo">
                        <img v-if="photo(pid)" :src="photo(pid)" />
                        <span v-else>{{ initials(name(pid)) }}</span>
                      </div>
                      <span class="pick-name">{{ name(pid) }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <Transition name="fade">
              <p v-if="feedback" class="feedback" :class="feedback.type">{{ feedback.text }}</p>
            </Transition>
          </div>
        </section>
      </template>

      <!-- ניצחון -->
      <section v-else class="win-stage pop-in">
        <div class="win-emoji bounce">🎉</div>
        <h2>הגעתם אל {{ mainPerson.full_name }}!</h2>

        <div class="win-reveal">
          <div class="win-reveal-photo">
            <img v-if="photo(round.target_id)" :src="photo(round.target_id)" :alt="name(round.target_id)" />
            <span v-else class="initials-lg">{{ initials(name(round.target_id)) }}</span>
          </div>
          <p class="win-reveal-label">זוהי הייתה</p>
          <p class="win-reveal-name name-reveal">{{ name(round.target_id) }}</p>
        </div>

        <!-- שביל מלא -->
        <div class="win-trail">
          <div v-for="node in trailNodes" :key="'w-' + node.key" class="win-chip" :class="node.state">
            <div class="win-chip-photo">
              <img v-if="node.photo" :src="node.photo" />
              <span v-else>{{ node.initials || '👑' }}</span>
            </div>
            <span class="win-chip-name">{{ node.name || node.shortLabel }}</span>
          </div>
        </div>

        <p class="win-score">צברתם <strong>{{ lastRoundScore }}</strong> נקודות בסבב הזה</p>
        <button class="btn-primary pulse-btn" @click="newRound">סבב חדש 🔄</button>
      </section>

      <canvas ref="confettiCanvas" class="confetti-canvas"></canvas>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  mainPerson: { type: Object, default: null },
  allPeople:  { type: Array,  default: () => [] },
})

const BASE_POINTS = 150
const OPTIONS_COST = 60
const TARGET_HINT_COST = { 1: 40, 2: 30, 3: 20 }
const TARGET_IDENTITY_BONUS = { 0: 300, 1: 120, 2: 50, 3: 0 }
const PART_BONUS = { first: 120, last: 100 }
const PART_HINT_SCALE = { 0: 1, 1: 0.5, 2: 0.25, 3: 0 }

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
const identityPartsFound = ref({ first: false, last: false })
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
const showHelp  = ref(false)
const identityCollapsed = ref(false)
const trailEl   = ref(null)

const confettiCanvas = ref(null)

const currentStep = computed(() => quizSteps.value[currentIndex.value] ?? null)

const shortStepLabel = (label) => {
  if (label === 'ההורה') return 'הורה'
  if (label === 'הסבא/סבתא') return 'סבא/תא'
  return label.replace('הסבא/סבתא ', 'דור ')
}

const trailNodes = computed(() => {
  if (!round.value) return []
  const nodes = []

  nodes.push({
    key: 'start',
    state: targetRevealed.value ? 'done' : 'start',
    shortLabel: 'התחלה',
    name: targetRevealed.value ? name(round.value.target_id) : null,
    photo: photo(round.value.target_id),
    initials: initials(name(round.value.target_id)),
    bubbleClass: genderClass(round.value.target_id),
    shake: false,
  })

  quizSteps.value.forEach((step, idx) => {
    const pid = placed.value[idx]
    let state = 'locked'
    if (pid) state = 'done'
    else if (idx === currentIndex.value && !finished.value) state = 'active'

    nodes.push({
      key: 'step-' + idx,
      state,
      shortLabel: shortStepLabel(step.label),
      name: pid ? name(pid) : null,
      photo: pid ? photo(pid) : null,
      initials: pid ? initials(name(pid)) : null,
      bubbleClass: pid ? genderClass(pid) : '',
      shake: shakeIdx.value === idx,
    })
  })

  nodes.push({
    key: 'goal',
    state: finished.value ? 'done' : 'goal',
    shortLabel: 'יעד',
    name: props.mainPerson?.full_name,
    photo: props.mainPerson?.photo_url,
    initials: initials(props.mainPerson?.full_name),
    bubbleClass: 'grandma',
    shake: false,
  })

  return nodes
})

const targetRevealed = computed(() => targetIdentitySolved.value || finished.value)

const targetDisplayOnStage = computed(() => {
  if (targetIdentitySolved.value || finished.value) return name(round.value?.target_id)
  const fn = round.value?.target_first_name
  const ln = round.value?.target_last_name
  if (identityPartsFound.value.first && identityPartsFound.value.last) return name(round.value?.target_id)
  if (identityPartsFound.value.first && fn) return fn + ' …'
  if (identityPartsFound.value.last && ln) return '… ' + ln
  return null
})

const identityInputPlaceholder = computed(() => {
  if (identityPartsFound.value.first && !identityPartsFound.value.last) return 'הוסיפו שם משפחה…'
  if (identityPartsFound.value.last && !identityPartsFound.value.first) return 'הוסיפו שם פרטי…'
  return 'שם פרטי, משפחה, או מלא…'
})

const identityNextHint = computed(() => {
  if (targetIdentitySolved.value) return ''
  const remaining = remainingIdentityBonus()
  if (identityPartsFound.value.first && !identityPartsFound.value.last) {
    return `💡 יפה! הוסיפו שם משפחה לעוד +${remaining} נקודות`
  }
  if (identityPartsFound.value.last && !identityPartsFound.value.first) {
    return `💡 מצוין! הוסיפו שם פרטי לעוד +${remaining} נקודות`
  }
  return ''
})

watch(currentIndex, async () => {
  await nextTick()
  const active = trailEl.value?.querySelector('.trail-node.active')
  active?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
})

const searchResults = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return []
  const used = new Set(placed.value.filter(Boolean))
  return props.allPeople
    .filter(p => p.id !== round.value?.target_id && !used.has(p.id))
    .filter(p => p.full_name.toLowerCase().includes(q))
    .slice(0, 6)
})

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

const subjectName = computed(() => {
  if (targetIdentitySolved.value || identityPartsFound.value.first) {
    return round.value?.target_first_name || name(round.value?.target_id)
  }
  if (targetHintLevel.value >= 1) return round.value?.target_first_name || 'הדמות'
  return 'הדמות בתמונה'
})

function name(id)   { return peopleById.value[id]?.full_name ?? '—' }
function photo(id)  { return peopleById.value[id]?.photo_url ?? null }
function genderClass(id) { return peopleById.value[id]?.gender === 'female' ? 'female' : 'male' }
function initials(n) { return (n || '').split(' ').map(w => w[0]).join('').slice(0, 2) }

function partBonusFor(part) {
  const scale = PART_HINT_SCALE[targetHintLevel.value] ?? 0
  return Math.round(PART_BONUS[part] * scale)
}

function remainingIdentityBonus() {
  const full = TARGET_IDENTITY_BONUS[targetHintLevel.value] ?? 0
  return Math.max(0, full - targetIdentityBonus.value)
}

function getTargetParts() {
  const r = round.value
  return {
    first: normalizeName(r?.target_first_name || ''),
    last: normalizeName(r?.target_last_name || ''),
    full: normalizeName(name(r?.target_id)),
  }
}

function guessMatchesPart(guess, part) {
  if (!guess || !part) return false
  if (guess === part) return true
  const words = guess.split(' ').filter(Boolean)
  return words.some(w => w === part)
}

function guessIsFull(guess, parts) {
  if (!guess) return false
  if (guess === parts.full) return true
  if (!parts.first || !parts.last) return guess === parts.full
  return guessMatchesPart(guess, parts.first) && guessMatchesPart(guess, parts.last)
}

function completeIdentitySolve(message) {
  const remaining = remainingIdentityBonus()
  if (remaining > 0) {
    targetIdentityBonus.value += remaining
    awardPoints(remaining, message)
  } else if (message) {
    feedback.value = { type: 'ok', text: message }
  }
  targetIdentitySolved.value = true
  identityPartsFound.value = { first: true, last: true }
  identityCollapsed.value = true
  targetGuess.value = ''
  fireConfetti(remaining > 80 ? 100 : 50, window.innerWidth / 2, window.innerHeight * 0.45)
}

function awardPoints(n, label) {
  score.value += n
  lastRoundScore.value += n
  scoreDelta.value = `+${n}`
  scoreBump.value = true
  setTimeout(() => { scoreBump.value = false; scoreDelta.value = '' }, 900)
  if (label) feedback.value = { type: 'ok', text: label }
}

function normalizeName(s) {
  return (s || '').trim().toLowerCase().replace(/\s+/g, ' ')
}

function attemptTargetGuess() {
  if (finished.value || targetIdentitySolved.value || !round.value) return
  const g = normalizeName(targetGuess.value)
  if (!g) return

  const parts = getTargetParts()
  const hasPartial = identityPartsFound.value.first || identityPartsFound.value.last

  if (guessIsFull(g, parts)) {
    if (!hasPartial) {
      const bonus = TARGET_IDENTITY_BONUS[targetHintLevel.value] ?? 0
      targetIdentityBonus.value = bonus
      if (bonus > 0) {
        awardPoints(bonus, `מדהים! זיהיתם את ${name(round.value.target_id)}! +${bonus} 🌟`)
      } else {
        feedback.value = { type: 'ok', text: `נכון! זוהי ${name(round.value.target_id)}` }
      }
      targetIdentitySolved.value = true
      identityPartsFound.value = { first: true, last: true }
      identityCollapsed.value = true
      targetGuess.value = ''
      fireConfetti(100, window.innerWidth / 2, window.innerHeight * 0.45)
    } else {
      completeIdentitySolve(`כל הכבוד! השלמתם את ${name(round.value.target_id)}! +${remainingIdentityBonus()} 🌟`)
    }
    return
  }

  let foundNew = false
  let awarded = 0
  const newParts = []

  if (!identityPartsFound.value.first && parts.first && guessMatchesPart(g, parts.first)) {
    identityPartsFound.value.first = true
    awarded += partBonusFor('first')
    newParts.push(parts.first)
    foundNew = true
  }
  if (!identityPartsFound.value.last && parts.last && guessMatchesPart(g, parts.last)) {
    identityPartsFound.value.last = true
    awarded += partBonusFor('last')
    newParts.push(parts.last)
    foundNew = true
  }

  if (identityPartsFound.value.first && identityPartsFound.value.last) {
    if (awarded > 0) targetIdentityBonus.value += awarded
    completeIdentitySolve(`כל הכבוד! ${name(round.value.target_id)} — +${targetIdentityBonus.value} סה״כ 🌟`)
    return
  }

  if (foundNew && awarded > 0) {
    targetIdentityBonus.value += awarded
    awardPoints(awarded, `נכון! ${newParts.join(' ')} — +${awarded}. ${identityNextHint.value || ''}`)
    targetGuess.value = ''
    fireConfetti(40, window.innerWidth / 2, window.innerHeight * 0.5)
    return
  }

  feedback.value = { type: 'err', text: 'לא בדיוק… נסו שוב, חלק מהשם, או בקשו רמז 💡' }
}

function requestNewRound() {
  if (loading.value) return
  const inProgress = !finished.value && round.value && (
    currentIndex.value > 0 ||
    targetIdentityBonus.value > 0 ||
    identityPartsFound.value.first ||
    identityPartsFound.value.last ||
    lastRoundScore.value > 0
  )
  if (inProgress && !confirm('לוותר על הסבב הנוכחי ולהתחיל סבב חדש?')) return
  newRound()
}

async function newRound() {
  loading.value = true
  loadError.value = null
  finished.value = false
  feedback.value = null
  identityCollapsed.value = false
  try {
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
    identityPartsFound.value = { first: false, last: false }
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
  max-width: 720px;
  margin: 0 auto;
  padding: 1.25rem 1rem 3rem;
  font-family: 'Rubik', sans-serif;
  background: linear-gradient(180deg, #f0f6ff 0%, #fff 35%, #fafbff 100%);
  min-height: calc(100vh - 64px);
}

/* ── כותרת ── */
.game-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 0.75rem; gap: 0.75rem;
}
.header-start { display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 0; }
.game-header h1 { font-size: 1.2rem; color: #1a3a6b; margin: 0; line-height: 1.3; }
.help-toggle {
  flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%;
  border: 1.5px solid #c7d8f5; background: white; cursor: pointer;
  font-size: 0.85rem; color: #2d6be4; transition: all 0.2s;
}
.help-toggle:hover { background: #edf3ff; transform: scale(1.08); }

.header-actions { display: flex; align-items: flex-start; gap: 0.5rem; flex-shrink: 0; }
.btn-new-round {
  padding: 0.4rem 0.7rem; border: 1.5px solid #c7d8f5; border-radius: 10px;
  background: white; color: #2d6be4; font-family: 'Rubik', sans-serif;
  font-size: 0.78rem; font-weight: 600; cursor: pointer; white-space: nowrap;
  transition: all 0.15s;
}
.btn-new-round:hover:not(:disabled) { background: #edf3ff; border-color: #2d6be4; }
.btn-new-round:disabled { opacity: 0.5; cursor: default; }

.score-box {
  display: flex; flex-direction: column; align-items: center; position: relative; flex-shrink: 0;
  background: linear-gradient(135deg, #1a3a6b, #2d5aa0); color: white;
  border-radius: 14px; padding: 0.35rem 0.9rem; min-width: 72px;
}
.score-box.bump { animation: score-bump 0.5s ease; }
.score-delta {
  position: absolute; top: -1.3rem; left: 50%; transform: translateX(-50%);
  font-size: 0.95rem; font-weight: 700; color: #22c55e;
  animation: float-up 0.9s ease forwards; pointer-events: none;
}
.score-label { font-size: 0.65rem; opacity: 0.85; }
.score-value { font-size: 1.35rem; font-weight: 800; }

.help-panel {
  background: white; border: 1px solid #dbeafe; border-radius: 12px;
  padding: 0.75rem 1rem; font-size: 0.88rem; color: #4a5568; line-height: 1.55;
  margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(45,107,228,.08);
}

.notice {
  background: white; border-radius: 14px; padding: 2rem; text-align: center;
  color: #4a5568; box-shadow: 0 2px 12px rgba(0,50,150,.07);
}
.loading-pulse { animation: pulse-text 1.2s ease-in-out infinite; }

/* ── שביל אופקי ── */
.trail-wrap {
  background: white; border-radius: 16px; padding: 0.85rem 0.5rem;
  box-shadow: 0 2px 14px rgba(0,50,150,.07); margin-bottom: 0.65rem;
  overflow: hidden;
}
.trail {
  display: flex; align-items: flex-start; justify-content: flex-start;
  overflow-x: auto; scroll-behavior: smooth; padding: 0.25rem 0.5rem;
  gap: 0; scrollbar-width: thin;
}
.trail-node {
  display: flex; flex-direction: column; align-items: center; flex-shrink: 0;
  width: 64px; position: relative;
}
.trail-node.shake { animation: shake 0.45s; }
.trail-bubble {
  width: 48px; height: 48px; border-radius: 50%; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  border: 3px solid #d1dce8; background: #f1f5f9;
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.trail-bubble img { width: 100%; height: 100%; object-fit: cover; }
.trail-bubble.female { border-color: #e9d5ff; background: #fdf4ff; }
.trail-bubble.grandma { border-color: #fbbf24; background: #fef3c7; }
.trail-node.start .trail-bubble { border-color: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
.trail-node.active .trail-bubble {
  border-color: #2d6be4; background: #eef4ff;
  box-shadow: 0 0 0 4px #dbeafe;
  animation: pulse-ring 1.6s ease-in-out infinite;
}
.trail-node.done .trail-bubble { border-color: #22c55e; }
.trail-node.goal .trail-bubble { border-color: #f59e0b; }
.trail-node.locked .trail-bubble { opacity: 0.45; }
.trail-q { font-size: 1.3rem; font-weight: 700; color: #2d6be4; }
.trail-crown { font-size: 1.3rem; }
.trail-init { font-size: 0.75rem; font-weight: 700; color: #2d6be4; }
.trail-dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
.trail-label {
  font-size: 0.62rem; color: #8a9ab5; margin-top: 0.3rem;
  text-align: center; line-height: 1.2; max-width: 60px;
}
.trail-node.active .trail-label { color: #2d6be4; font-weight: 700; }
.trail-node.done .trail-label { color: #166534; font-weight: 600; }
.trail-check {
  position: absolute; top: -2px; left: 2px;
  width: 16px; height: 16px; border-radius: 50%;
  background: #22c55e; color: white; font-size: 0.55rem;
  display: flex; align-items: center; justify-content: center; font-weight: 700;
}
.trail-seg {
  flex: 1; min-width: 20px; max-width: 48px; height: 3px;
  background: #e2e8f0; border-radius: 2px; margin-top: 22px; flex-shrink: 0;
  transition: background 0.4s;
}
.trail-seg.lit { background: linear-gradient(90deg, #22c55e, #4ade80); }

.step-badge {
  text-align: center; font-size: 0.78rem; color: #6b7a99;
  margin-bottom: 0.85rem;
}
.step-extra { color: #b45309; }
.step-extra.partial { color: #2d6be4; }

/* ── במה ── */
.stage {
  background: white; border-radius: 20px;
  box-shadow: 0 4px 24px rgba(0,50,150,.09);
  overflow: hidden; border: 2px solid transparent;
  transition: border-color 0.4s;
}
.stage-revealed { border-color: #86efac; }
.stage-partial { border-color: #93c5fd; }

.stage-photo-wrap {
  text-align: center; padding: 1.5rem 1rem 1rem;
  background: linear-gradient(180deg, #eef4ff 0%, white 100%);
}
.stage-photo-wrap.float { animation: gentle-float 3.5s ease-in-out infinite; }

.stage-photo {
  width: 180px; height: 180px; border-radius: 50%; overflow: hidden;
  margin: 0 auto; border: 5px solid #f59e0b;
  box-shadow: 0 10px 32px rgba(45,107,228,.18);
  display: flex; align-items: center; justify-content: center;
  background: #e8f0fe; transition: border-color 0.4s, box-shadow 0.4s;
}
.stage-photo.female { border-color: #c084fc; background: #fdf4ff; }
.stage-photo.revealed { border-color: #22c55e; box-shadow: 0 10px 32px rgba(34,197,94,.22); }
.stage-photo.partial { border-color: #60a5fa; box-shadow: 0 10px 32px rgba(96,165,250,.2); }
.stage-photo img { width: 100%; height: 100%; object-fit: cover; }
.initials-lg { font-size: 3rem; font-weight: 800; color: #2d6be4; }

.stage-mystery { font-size: 1.1rem; color: #b45309; font-weight: 700; margin-top: 0.65rem; }
.stage-name-reveal {
  font-size: 1.75rem; font-weight: 800; color: #166534; margin-top: 0.5rem;
}

/* ── כרטיס פעולה ── */
.action-card { padding: 0 1rem 1.25rem; }

.mission { border-radius: 14px; margin-bottom: 0.75rem; overflow: hidden; }
.mission-id { background: #fffbeb; border: 1px solid #fde68a; }
.mission-id.collapsed .mission-head { border-bottom: none; }
.mission-climb { background: #f8faff; border: 1px solid #dbeafe; }

.mission-head {
  display: flex; align-items: center; gap: 0.5rem; width: 100%;
  padding: 0.7rem 0.85rem; background: transparent; border: none;
  cursor: pointer; font-family: 'Rubik', sans-serif; text-align: right;
}
.mission-head.static { cursor: default; }
.mission-icon { font-size: 1.2rem; }
.mission-title { flex: 1; font-size: 0.92rem; color: #1a3a6b; font-weight: 600; }
.mission-bonus {
  font-size: 0.75rem; font-weight: 800; color: #b45309;
  background: #fef3c7; padding: 0.15rem 0.45rem; border-radius: 20px;
}
.mission-chevron { font-size: 0.65rem; color: #aab; }

.mission-body { padding: 0 0.85rem 0.85rem; }

.identity-done {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 0.6rem; margin-bottom: 0.75rem;
  background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px;
  font-weight: 700; color: #166534; font-size: 1rem;
}
.stage-name-reveal.partial { font-size: 1.45rem; color: #1d4ed8; }

.identity-progress {
  display: flex; flex-wrap: wrap; gap: 0.4rem; justify-content: center;
  margin-bottom: 0.6rem;
}
.part-chip {
  font-size: 0.8rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 20px;
}
.part-chip.done { background: #dcfce7; color: #166534; }
.part-chip.missing { background: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1; }

.identity-next-hint {
  font-size: 0.85rem; color: #1d4ed8; font-weight: 600; text-align: center;
  margin: 0 0 0.5rem; padding: 0.45rem 0.6rem;
  background: #eff6ff; border-radius: 8px;
}
.identity-bonus-hint {
  font-size: 0.72rem; color: #92400e; margin: 0.45rem 0 0; text-align: center; line-height: 1.4;
}

.input-row { display: flex; gap: 0.5rem; }
.input-row .search-input { flex: 1; }

.search-input {
  width: 100%; padding: 0.65rem 0.85rem; border: 1.5px solid #d1dce8; border-radius: 10px;
  font-size: 0.95rem; font-family: 'Rubik', sans-serif; direction: rtl; box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus { outline: none; border-color: #2d6be4; box-shadow: 0 0 0 3px #dbeafe; }
.search-lg { font-size: 1.05rem; padding: 0.75rem 1rem; }

.btn-guess {
  flex-shrink: 0; padding: 0.65rem 1.1rem; border: none; border-radius: 10px;
  background: linear-gradient(135deg, #2d6be4, #1a55c8); color: white;
  font-family: 'Rubik', sans-serif; font-size: 0.95rem; font-weight: 700;
  cursor: pointer; transition: transform 0.15s;
}
.btn-guess:hover:not(:disabled) { transform: scale(1.04); }
.btn-guess:disabled { opacity: 0.4; cursor: default; }

.results-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 0.5rem; margin-top: 0.65rem;
}
.pick-card {
  display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
  padding: 0.55rem; border: 1.5px solid #e4eefb; border-radius: 12px;
  background: white; cursor: pointer; transition: all 0.18s;
  font-family: 'Rubik', sans-serif;
}
.pick-card:hover { border-color: #2d6be4; background: #edf3ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45,107,228,.12); }
.pick-card.female:hover { border-color: #8b5cf6; background: #faf5ff; }
.pick-card.hint { border-color: #f59e0b; background: #fffaf0; }
.pick-photo {
  width: 52px; height: 52px; border-radius: 50%; overflow: hidden;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; font-weight: 700; color: #2d6be4;
}
.pick-photo img { width: 100%; height: 100%; object-fit: cover; }
.pick-name { font-size: 0.78rem; color: #2d4a7a; font-weight: 600; text-align: center; line-height: 1.2; }

.no-results { font-size: 0.85rem; color: #aab; text-align: center; padding: 0.5rem; }

.hint-box { background: white; border: 1px solid #fde68a; border-radius: 9px; padding: 0.4rem 0.6rem; margin: 0.5rem 0; }
.hint-line { font-size: 0.82rem; color: #92400e; margin: 0.1rem 0; }

.btn-hint {
  margin-top: 0.5rem; width: 100%; padding: 0.5rem; border: 1.5px solid #f59e0b;
  background: #fffaf0; color: #b45309; border-radius: 9px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-size: 0.82rem; font-weight: 600;
}
.btn-hint:disabled { opacity: 0.5; cursor: default; }
.btn-hint-options { border-style: dashed; }
.hint-options { margin-top: 0.65rem; }
.hint-title { font-size: 0.8rem; color: #b45309; margin: 0 0 0.35rem; }

.feedback {
  margin-top: 0.75rem; font-size: 0.88rem; padding: 0.55rem 0.75rem;
  border-radius: 10px; text-align: center;
}
.feedback.ok   { background: #f0fdf4; color: #166534; }
.feedback.err  { background: #fef2f2; color: #991b1b; }
.feedback.info { background: #eff6ff; color: #1e40af; }

/* ── ניצחון ── */
.win-stage {
  background: white; border-radius: 20px; padding: 1.5rem 1rem 2rem;
  text-align: center; box-shadow: 0 4px 24px rgba(0,50,150,.09);
}
.win-emoji { font-size: 3.5rem; }
.win-stage h2 { color: #1a3a6b; font-size: 1.35rem; margin: 0.4rem 0 1rem; }

.win-reveal {
  background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
  border: 2px solid #86efac; border-radius: 18px; padding: 1.25rem; margin-bottom: 1rem;
}
.win-reveal-photo {
  width: 120px; height: 120px; border-radius: 50%; overflow: hidden;
  margin: 0 auto 0.6rem; border: 4px solid #22c55e;
  display: flex; align-items: center; justify-content: center;
  background: #e8f0fe; font-size: 2rem; font-weight: 700; color: #2d6be4;
}
.win-reveal-photo img { width: 100%; height: 100%; object-fit: cover; }
.win-reveal-label { font-size: 0.85rem; color: #6b7280; margin: 0; }
.win-reveal-name { font-size: 1.85rem; font-weight: 800; color: #166534; margin: 0.2rem 0 0; }

.win-trail {
  display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;
  margin-bottom: 1rem; padding: 0.75rem; background: #f8faff; border-radius: 14px;
}
.win-chip {
  display: flex; flex-direction: column; align-items: center; gap: 0.2rem;
  padding: 0.4rem; border-radius: 10px; min-width: 64px;
}
.win-chip.done { background: #f0fdf4; }
.win-chip-photo {
  width: 40px; height: 40px; border-radius: 50%; overflow: hidden;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 700; color: #2d6be4;
}
.win-chip-photo img { width: 100%; height: 100%; object-fit: cover; }
.win-chip-name { font-size: 0.62rem; color: #4a5568; font-weight: 600; max-width: 70px; text-align: center; }

.win-score { color: #4a5568; margin-bottom: 1rem; }

.btn-primary {
  background: linear-gradient(135deg, #2d6be4, #1a55c8); color: white; border: none;
  padding: 0.7rem 1.75rem; border-radius: 12px; cursor: pointer;
  font-family: 'Rubik', sans-serif; font-weight: 700; font-size: 1rem;
}
.btn-primary:hover { filter: brightness(1.08); }
.pulse-btn { animation: pulse-btn 2s ease-in-out infinite; }

.confetti-canvas { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }

/* ── אנימציות ── */
@keyframes score-bump { 0%,100%{transform:scale(1)} 40%{transform:scale(1.15)} }
@keyframes float-up { 0%{opacity:1;transform:translateX(-50%) translateY(0)} 100%{opacity:0;transform:translateX(-50%) translateY(-24px)} }
@keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-6px)} 40%,80%{transform:translateX(6px)} }
@keyframes gentle-float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }
@keyframes pulse-ring { 0%,100%{box-shadow:0 0 0 4px #dbeafe} 50%{box-shadow:0 0 0 8px rgba(45,107,228,.2)} }
@keyframes pop-in { 0%{transform:scale(0.88);opacity:0.5} 100%{transform:scale(1);opacity:1} }
@keyframes name-reveal { 0%{transform:scale(0.5);opacity:0} 100%{transform:scale(1);opacity:1} }
@keyframes bounce { 0%,100%{transform:translateY(0)} 30%{transform:translateY(-16px)} 50%{transform:translateY(-6px)} }
@keyframes pulse-btn { 0%,100%{box-shadow:0 0 0 0 rgba(45,107,228,.4)} 50%{box-shadow:0 0 0 8px rgba(45,107,228,0)} }
@keyframes pulse-text { 0%,100%{opacity:1} 50%{opacity:0.5} }
.pop-in { animation: pop-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1); }
.name-reveal { animation: name-reveal 0.55s cubic-bezier(0.34, 1.56, 0.64, 1); }
.bounce { animation: bounce 0.8s ease; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; padding: 0; margin: 0; }
.slide-down-enter-to, .slide-down-leave-from { max-height: 120px; }

.expand-enter-active, .expand-leave-active { transition: all 0.25s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; }
.expand-enter-to, .expand-leave-from { max-height: 200px; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.identity-done .pts { color: #22c55e; font-size: 0.9rem; }

@media (max-width: 480px) {
  .btn-new-round { font-size: 0.72rem; padding: 0.35rem 0.5rem; }
  .stage-photo { width: 150px; height: 150px; }
  .trail-node { width: 56px; }
  .trail-bubble { width: 42px; height: 42px; }
  .results-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
