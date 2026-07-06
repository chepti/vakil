// המרה בטוחה של טקסט עם הדגשות בסגנון **מודגש** ל-HTML.
// קודם בורחים מכל תו HTML (מניעת XSS), ורק אז מחילים הדגשה ומעבר שורה.
export function renderRichText(raw) {
  if (!raw) return ''

  const escaped = String(raw)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

  return escaped
    // **מודגש** → <strong>
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    // מעבר שורה → <br>
    .replace(/\n/g, '<br>')
}
