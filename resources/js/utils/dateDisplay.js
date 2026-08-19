import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { gregorianToHebrew, gregorianToHebrewParts } from '@/utils/hebrewDate'

/**
 * התאמות תצוגת תאריכים פר-אתר.
 *
 * שני הדגלים מגיעים מהשרת (config/app.php → dates, נקבע ב-.env של כל אתר):
 *   hideGregorian    — מציגים תאריך עברי בלבד
 *   hideBirthYearIds — מזהי דמויות (נשים נשואות) שלהן שנת הלידה מקופלת
 *
 * כשהדגלים כבויים כל הפונקציות כאן מחזירות בדיוק את התצוגה הישנה,
 * כך שוואקיל ואתר ההדגמה לא מושפעים.
 */

/** פיצול מחרוזת תאריך עברי ל"יום וחודש" + "שנה" — 'כ"ה בתשרי תשפ"ה' → ['כ"ה בתשרי', 'תשפ"ה'] */
export function splitHebrewYear(hebrew) {
  const tokens = String(hebrew || '').trim().split(/\s+/).filter(Boolean)
  if (tokens.length < 3) return [hebrew || '', '']   // אין שנה לזהות (יום + חודש בלבד)
  return [tokens.slice(0, -1).join(' '), tokens[tokens.length - 1]]
}

/** פיצול תאריך לועזי ל"יום.חודש" + "שנה" */
export function splitGregorianYear(dateStr) {
  if (!dateStr) return ['', '']
  const d = new Date(dateStr)
  if (isNaN(d)) return [String(dateStr), '']
  return [`${d.getDate()}.${d.getMonth() + 1}`, String(d.getFullYear())]
}

/** תאריך לועזי מלא בפורמט המקובל באתר */
export function formatGregorian(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d)) return String(dateStr)
  return `${d.getDate()}.${d.getMonth() + 1}.${d.getFullYear()}`
}

export function useDateDisplay() {
  const page = usePage()

  const hideGregorian = computed(() => !!page.props.dateDisplay?.hideGregorian)

  const hiddenYearIds = computed(
    () => new Set((page.props.dateDisplay?.hideBirthYearIds || []).map(Number)),
  )

  /** האם שנת הלידה של הדמות מקופלת (אישה נשואה, באתר שהדגל דלוק בו) */
  function birthYearHidden(personId) {
    return personId != null && hiddenYearIds.value.has(Number(personId))
  }

  /**
   * הטקסט להצגה עבור תאריך אחד.
   *
   * @param {Object}  o
   * @param {string}  o.gregorian  תאריך לועזי (YYYY-MM-DD)
   * @param {string}  o.hebrew     תאריך עברי כטקסט, אם נשמר
   * @param {number}  o.personId   הדמות שהתאריך שייך לה
   * @param {boolean} o.isBirth    תאריך לידה — רק עליו חל כלל השנה המקופלת
   * @returns {{ text: string, folded: string }} folded = מה שמוצג רק בלחיצה
   */
  function dateParts({ gregorian = '', hebrew = '', personId = null, isBirth = false } = {}) {
    const foldYear = isBirth && birthYearHidden(personId)
    const he = hebrew || gregorianToHebrew(gregorian)

    if (hideGregorian.value) {
      if (!he) return { text: '', folded: '' }
      if (!foldYear) return { text: he, folded: '' }

      const [dayMonth, year] = splitHebrewYear(he)
      return { text: dayMonth, folded: year }
    }

    // אתר רגיל — לועזי (+ עברי כשקיים), עם קיפול השנה כשצריך
    if (!gregorian && !he) return { text: '', folded: '' }

    if (!foldYear) {
      const full = [formatGregorian(gregorian), he].filter(Boolean).join(' / ')
      return { text: full, folded: '' }
    }

    const [gDayMonth, gYear] = splitGregorianYear(gregorian)
    const [hDayMonth, hYear] = splitHebrewYear(he)

    return {
      text:   [gDayMonth, hDayMonth].filter(Boolean).join(' / '),
      folded: [gYear, hYear].filter(Boolean).join(' / '),
    }
  }

  /** שנת לידה (או "לידה–פטירה") לתצוגות מקוצרות כמו רשימת הדמויות וההדפסה */
  function yearParts({ gregorian = '', hebrew = '', deathGregorian = '', personId = null } = {}) {
    if (birthYearHidden(personId)) return { text: '', folded: '' }

    const birthYear = hideGregorian.value
      ? (splitHebrewYear(hebrew || gregorianToHebrew(gregorian))[1]
         || gregorianToHebrewParts(gregorian)?.yearHe
         || '')
      : (gregorian ? String(new Date(gregorian).getFullYear()) : '')

    const deathYear = hideGregorian.value
      ? (gregorianToHebrewParts(deathGregorian)?.yearHe || '')
      : (deathGregorian ? String(new Date(deathGregorian).getFullYear()) : '')

    if (birthYear && deathYear) return { text: `${birthYear}–${deathYear}`, folded: '' }
    return { text: birthYear || deathYear || '', folded: '' }
  }

  return { hideGregorian, birthYearHidden, dateParts, yearParts }
}
