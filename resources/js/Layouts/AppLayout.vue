<template>
  <div class="app-layout">
    <!-- Navbar -->
    <nav class="app-nav" dir="rtl">
      <div class="nav-inner">
        <!-- לוגו -->
        <Link href="/people" class="nav-logo">
          <span class="logo-icon">🌳</span>
          <span class="logo-text">משפחת ואקיל</span>
        </Link>

        <!-- ניווט ראשי -->
        <div class="nav-links">
          <Link href="/family-tree" :class="['nav-link', { active: $page.url === '/family-tree' }]">🌳 עץ משפחה</Link>
          <Link href="/people" :class="['nav-link', { active: $page.url.startsWith('/people') && !$page.url.startsWith('/people/create') }]">בני המשפחה</Link>
          <Link href="/family-photos" :class="['nav-link', { active: $page.url.startsWith('/family-photos') }]">📸 תמונות</Link>
          <Link href="/events" :class="['nav-link', { active: $page.url.startsWith('/events') }]">📅 אירועים</Link>
          <Link href="/game" :class="['nav-link', { active: $page.url.startsWith('/game') }]">🎮 משחק</Link>
          <Link href="/stats" :class="['nav-link', { active: $page.url.startsWith('/stats') }]">📊 סטטיסטיקות</Link>
          <Link href="/print/tree" :class="['nav-link', { active: $page.url.startsWith('/print') }]">🖨️ הדפסה</Link>
          <Link v-if="$page.props.auth.user.role === 'admin'" href="/admin" :class="['nav-link', { active: $page.url.startsWith('/admin') }]">⚙️ ניהול</Link>
          <Link href="/people/create" class="nav-link-btn">+ הוסף דמות</Link>
        </div>

        <!-- משתמש -->
        <div class="nav-user">
          <span class="user-name">{{ $page.props.auth.user.name }}</span>
          <div class="user-menu">
            <Link href="/profile" class="user-menu-item">פרופיל</Link>
            <Link href="/logout" method="post" as="button" class="user-menu-item logout">יציאה</Link>
          </div>
        </div>

        <!-- המבורגר מובייל -->
        <button class="hamburger" @click="mobileOpen = !mobileOpen">
          <span></span><span></span><span></span>
        </button>
      </div>

      <!-- תפריט מובייל -->
      <div v-if="mobileOpen" class="mobile-menu" dir="rtl">
        <Link href="/family-tree" class="mobile-link" @click="mobileOpen = false">🌳 עץ משפחה</Link>
        <Link href="/people" class="mobile-link" @click="mobileOpen = false">בני המשפחה</Link>
        <Link href="/family-photos" class="mobile-link" @click="mobileOpen = false">📸 תמונות</Link>
        <Link href="/events" class="mobile-link" @click="mobileOpen = false">📅 אירועים</Link>
        <Link href="/game" class="mobile-link" @click="mobileOpen = false">🎮 משחק</Link>
        <Link href="/stats" class="mobile-link" @click="mobileOpen = false">📊 סטטיסטיקות</Link>
        <Link href="/print/tree" class="mobile-link" @click="mobileOpen = false">🖨️ הדפסה</Link>
        <Link v-if="$page.props.auth.user.role === 'admin'" href="/admin" class="mobile-link" @click="mobileOpen = false">⚙️ ניהול</Link>
        <Link href="/people/create" class="mobile-link" @click="mobileOpen = false">הוסף דמות</Link>
        <Link href="/profile" class="mobile-link" @click="mobileOpen = false">פרופיל</Link>
        <Link href="/logout" method="post" as="button" class="mobile-link mobile-logout" @click="mobileOpen = false">יציאה</Link>
      </div>
    </nav>

    <!-- Flash Message -->
    <div v-if="$page.props.flash?.success" class="flash-success" dir="rtl">
      ✓ {{ $page.props.flash.success }}
    </div>

    <!-- תוכן הדף -->
    <main class="app-main">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  title: { type: String, default: '' },
})

const mobileOpen = ref(false)
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

.app-layout {
  min-height: 100vh;
  background: #f4f8ff;
  font-family: 'Rubik', sans-serif;
}

/* Navbar */
.app-nav {
  background: white;
  border-bottom: 1px solid #e0eaf8;
  box-shadow: 0 2px 8px rgba(0,50,150,0.05);
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 60px;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  flex-shrink: 0;
}

.logo-icon { font-size: 1.4rem; }

.logo-text {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a3a6b;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.nav-link {
  color: #4a5568;
  text-decoration: none;
  font-size: 0.93rem;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.nav-link:hover, .nav-link.active {
  color: #2d6be4;
  background: #edf3ff;
}

.nav-link-btn {
  background: #2d6be4;
  color: white !important;
  text-decoration: none;
  font-size: 0.88rem;
  font-weight: 600;
  padding: 0.35rem 0.9rem;
  border-radius: 7px;
  transition: background 0.2s;
}

.nav-link-btn:hover { background: #1a55c8; }

.nav-user {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-name {
  font-size: 0.88rem;
  color: #4a5568;
  cursor: pointer;
}

.user-menu {
  display: flex;
  gap: 0.5rem;
}

.user-menu-item {
  font-size: 0.85rem;
  color: #6b7a99;
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 5px;
  transition: color 0.2s;
  border: none;
  background: none;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
}

.user-menu-item:hover { color: #2d6be4; }
.user-menu-item.logout:hover { color: #e74c3c; }

.hamburger {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.3rem;
  margin-right: auto;
}

.hamburger span {
  display: block;
  width: 22px;
  height: 2px;
  background: #4a5568;
  border-radius: 2px;
}

.mobile-menu {
  border-top: 1px solid #e0eaf8;
  padding: 0.75rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.mobile-link {
  padding: 0.65rem 0.75rem;
  color: #2d4a7a;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.95rem;
  background: none;
  border: none;
  cursor: pointer;
  font-family: 'Rubik', sans-serif;
  text-align: right;
}

.mobile-link:hover { background: #edf3ff; }
.mobile-logout { color: #e74c3c; }

/* Flash */
.flash-success {
  max-width: 1100px;
  margin: 1rem auto 0;
  padding: 0.75rem 1.5rem;
  background: #d1fae5;
  border: 1px solid #6ee7b7;
  border-radius: 10px;
  color: #065f46;
  font-size: 0.9rem;
}

/* Main */
.app-main {
  max-width: 100%;
}

/* Mobile */
@media (max-width: 640px) {
  .nav-links, .nav-user { display: none; }
  .hamburger { display: flex; }
}
</style>
