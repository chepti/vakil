// המרת תאריכים עברי ↔ לועזי, עם תצוגת גימטריה (כ"ז בתשרי תשע"ח).
// משתמש ב-@hebcal/core לחישוב הלוח, ובפורמט גימטריה ידני לתצוגה עקבית.
import { HDate } from '@hebcal/core'

// ─── גימטריה ──────────────────────────────────────────────────
const ONES     = ['', 'א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ז', 'ח', 'ט']
const TENS     = ['', 'י', 'כ', 'ל', 'מ', 'נ', 'ס', 'ע', 'פ', 'צ']
const HUNDREDS = ['', 'ק', 'ר', 'ש', 'ת', 'תק', 'תר', 'תש', 'תת', 'תתק']

// ערכי אותיות (כולל סופיות) לפירוק מחרוזת → מספר
const LETTER_VALUES = {
  'א': 1, 'ב': 2, 'ג': 3, 'ד': 4, 'ה': 5, 'ו': 6, 'ז': 7, 'ח': 8, 'ט': 9,
  'י': 10, 'כ': 20, 'ך': 20, 'ל': 30, 'מ': 40, 'ם': 40, 'נ': 50, 'ן': 50,
  'ס': 60, 'ע': 70, 'פ': 80, 'ף': 80, 'צ': 90, 'ץ': 90,
  'ק': 100, 'ר': 200, 'ש': 300, 'ת': 400,
}

// מספר → אותיות גימטריה (לשנים מורידים אלפים: 5778 → תשע"ח)
export function toGematria(num) {
  let n = num % 1000
  let s = HUNDREDS[Math.floor(n / 100)]
  n %= 100
  if (n === 15)      s += 'טו'   // לא יה
  else if (n === 16) s += 'טז'   // לא יו
  else               s += TENS[Math.floor(n / 10)] + ONES[n % 10]
  if (s.length <= 1) return s + '׳'                 // גרש לאות בודדת
  return s.slice(0, -1) + '"' + s.slice(-1)         // גרשיים לפני האות האחרונה
}

// אותיות גימטריה → מספר (סכום ערכי האותיות, מתעלם מגרשיים)
function fromGematria(str) {
  let n = 0
  for (const ch of str) n += LETTER_VALUES[ch] || 0
  return n
}

// ─── שמות חודשים ──────────────────────────────────────────────
// hebcal getMonthName() מחזיר תעתיק אנגלי — ממפים לעברי לתצוגה
const HEB_MONTH_FROM_EN = {
  'Nisan': 'ניסן', 'Iyyar': 'אייר', 'Sivan': 'סיוון', 'Tamuz': 'תמוז',
  'Av': 'אב', 'Elul': 'אלול', 'Tishrei': 'תשרי', "Cheshvan": 'חשוון',
  'Kislev': 'כסלו', 'Tevet': 'טבת', "Sh'vat": 'שבט', 'Shvat': 'שבט',
  'Adar': 'אדר', 'Adar I': 'אדר א׳', 'Adar II': 'אדר ב׳',
  'Adar 1': 'אדר א׳', 'Adar 2': 'אדר ב׳',
}

// שם חודש עברי (מנורמל) → שם אנגלי עבור בניית HDate
const EN_MONTH_FROM_HEB = {
  'ניסן': 'Nisan', 'אייר': 'Iyyar', 'אִיָּר': 'Iyyar', 'סיון': 'Sivan', 'סיוון': 'Sivan',
  'תמוז': 'Tamuz', 'אב': 'Av', 'מנחםאב': 'Av', 'אלול': 'Elul',
  'תשרי': 'Tishrei', 'חשון': 'Cheshvan', 'חשוון': 'Cheshvan', 'מרחשון': 'Cheshvan', 'מרחשוון': 'Cheshvan',
  'כסלו': 'Kislev', 'כסליו': 'Kislev', 'טבת': 'Tevet', 'שבט': 'Shvat',
  'אדר': 'Adar', 'אדרא': 'Adar I', 'אדרב': 'Adar II', 'אדרראשון': 'Adar I', 'אדרשני': 'Adar II',
}

// ─── המרה לועזי → עברי (מחזיר "כ"ז בתשרי תשע"ח") ────────────────
export function gregorianToHebrew(dateStr) {
  if (!dateStr) return ''
  try {
    const d  = new Date(dateStr + 'T12:00:00')
    if (isNaN(d)) return ''
    const hd = new HDate(d)
    const day   = toGematria(hd.getDate())
    const month = HEB_MONTH_FROM_EN[hd.getMonthName()] || hd.getMonthName()
    const year  = toGematria(hd.getFullYear())
    return `${day} ב${month} ${year}`
  } catch { return '' }
}

// ─── המרה עברי → לועזי (מחזיר "YYYY-MM-DD" או '') ──────────────
export function hebrewToGregorian(hebrewStr) {
  if (!hebrewStr) return ''
  try {
    // ניקוי גרשיים/גרשים והפיכה לטוקנים
    const clean  = hebrewStr.replace(/["'״׳`]/g, '').trim()
    const tokens = clean.split(/\s+/).filter(Boolean)
    if (tokens.length < 2) return ''

    // איתור טוקן החודש (אולי עם תחילית ב')
    let monthIdx = -1, enMonth = null
    for (let i = 0; i < tokens.length; i++) {
      let t = tokens[i]
      if (EN_MONTH_FROM_HEB[t]) { monthIdx = i; enMonth = EN_MONTH_FROM_HEB[t]; break }
      if (t.startsWith('ב') && EN_MONTH_FROM_HEB[t.slice(1)]) { monthIdx = i; enMonth = EN_MONTH_FROM_HEB[t.slice(1)]; break }
    }
    if (monthIdx < 1 || monthIdx >= tokens.length - 1) return ''

    const day = fromGematria(tokens[monthIdx - 1])
    let year  = fromGematria(tokens[monthIdx + 1])
    if (year < 1000) year += 5000     // תשע"ח → 778 → 5778
    if (!day || !year) return ''

    let hd
    try { hd = new HDate(day, enMonth, year) }
    catch { hd = new HDate(day, 'Adar I', year) }   // נפילה לאחור לאדר א' בשנה מעוברת
    const g = hd.greg()
    const mm = String(g.getMonth() + 1).padStart(2, '0')
    const dd = String(g.getDate()).padStart(2, '0')
    return `${g.getFullYear()}-${mm}-${dd}`
  } catch { return '' }
}
