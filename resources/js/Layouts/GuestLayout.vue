<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({
  title:    { type: String, default: '' },
  subtitle: { type: String, default: '' },
})

const googleEnabled = computed(() => usePage().props.googleEnabled)
</script>

<template>
  <div class="auth-page" dir="rtl">
    <div class="auth-card">
      <Link href="/" class="auth-brand">
        <span class="auth-brand-icon">🌳</span>
        <span class="auth-brand-text">משפחת ואקיל</span>
      </Link>

      <h1 v-if="title" class="auth-title">{{ title }}</h1>
      <p v-if="subtitle" class="auth-subtitle">{{ subtitle }}</p>

      <slot />

      <!-- כפתור Google — מופיע רק כשמוגדרים credentials בשרת -->
      <template v-if="googleEnabled">
        <div class="auth-divider"><span>או</span></div>
        <a href="/auth/google" class="auth-google-btn">
          <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          <span>התחבר עם Google</span>
        </a>
      </template>
    </div>
  </div>
</template>

<style>
/* ── Shared auth form styling (global so all Auth pages reuse) ── */
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.auth-page {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
  font-family: 'Rubik', sans-serif;
  background: linear-gradient(160deg, #eaf2ff 0%, #f0f6ff 40%, #e3edff 100%);
}

.auth-card {
  width: 100%; max-width: 410px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 12px 44px rgba(0, 50, 150, 0.13);
  padding: 2.1rem 1.9rem;
  border: 1px solid #e7eefb;
}

.auth-brand {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  text-decoration: none; margin-bottom: 1.1rem;
}
.auth-brand-icon { font-size: 1.7rem; }
.auth-brand-text { font-size: 1.25rem; font-weight: 700; color: #1a3a6b; }

.auth-title    { font-size: 1.18rem; font-weight: 600; color: #1a3a6b; text-align: center; margin: 0 0 0.3rem; }
.auth-subtitle { font-size: 0.88rem; color: #6b7a99; text-align: center; margin: 0 0 1.3rem; line-height: 1.5; }

.auth-field { margin-bottom: 0.95rem; }
.auth-label { display: block; font-size: 0.84rem; color: #4a5568; font-weight: 500; margin-bottom: 0.3rem; }
.auth-input {
  width: 100%; box-sizing: border-box;
  padding: 0.6rem 0.8rem;
  border: 1.5px solid #d1dce8; border-radius: 9px;
  font-size: 0.92rem; font-family: 'Rubik', sans-serif;
  color: #1a3a6b; background: #fff; transition: border-color 0.18s, box-shadow 0.18s;
}
.auth-input:focus { outline: none; border-color: #2d6be4; box-shadow: 0 0 0 3px rgba(45,107,228,0.12); }
.auth-input::placeholder { color: #a9b6cc; }

.auth-error { color: #dc2626; font-size: 0.8rem; margin-top: 0.3rem; }

.auth-row { display: flex; align-items: center; justify-content: space-between; margin: 0.4rem 0 1.1rem; gap: 0.5rem; }
.auth-checkbox { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #6b7a99; cursor: pointer; }
.auth-checkbox input { width: 15px; height: 15px; cursor: pointer; accent-color: #2d6be4; }

.auth-btn {
  width: 100%; padding: 0.68rem;
  background: #2d6be4; color: #fff; border: none; border-radius: 10px;
  font-size: 0.96rem; font-weight: 600; font-family: 'Rubik', sans-serif;
  cursor: pointer; transition: background 0.18s;
}
.auth-btn:hover:not(:disabled) { background: #1a55c8; }
.auth-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.auth-link { color: #2d6be4; font-size: 0.86rem; text-decoration: none; cursor: pointer; background: none; border: none; font-family: 'Rubik', sans-serif; }
.auth-link:hover { text-decoration: underline; }

.auth-foot { text-align: center; margin-top: 1.2rem; font-size: 0.86rem; color: #6b7a99; }

.auth-status {
  background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46;
  border-radius: 9px; padding: 0.6rem 0.8rem; font-size: 0.85rem; margin-bottom: 1rem; text-align: center;
}
.auth-note { font-size: 0.88rem; color: #6b7a99; line-height: 1.6; margin-bottom: 1.2rem; text-align: center; }

.auth-divider { display: flex; align-items: center; text-align: center; color: #a9b6cc; font-size: 0.8rem; margin: 1.2rem 0; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; border-top: 1px solid #e4ebf6; }
.auth-divider span { padding: 0 0.7rem; }

.auth-google-btn {
  display: flex; align-items: center; justify-content: center; gap: 0.6rem;
  width: 100%; box-sizing: border-box;
  padding: 0.62rem; border: 1.5px solid #d1dce8; border-radius: 10px;
  background: #fff; color: #3c4257; font-size: 0.9rem; font-weight: 500;
  text-decoration: none; transition: background 0.18s, border-color 0.18s;
}
.auth-google-btn:hover { background: #f7faff; border-color: #b9c8de; }
</style>
