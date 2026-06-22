<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdateNotificationsForm from './Partials/UpdateNotificationsForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  mustVerifyEmail: { type: Boolean },
  status: { type: String },
  people: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="הפרופיל שלי" />

  <AppLayout>
    <div class="pf-page" dir="rtl">
      <div class="pf-header">
        <h1>👤 הפרופיל שלי</h1>
        <p>נהלו את פרטי החשבון, הסיסמה וההגדרות שלכם</p>
      </div>

      <div class="pf-card">
        <UpdateProfileInformationForm
          :must-verify-email="mustVerifyEmail"
          :status="status"
        />
      </div>

      <div class="pf-card">
        <UpdateNotificationsForm :people="people" />
      </div>

      <div class="pf-card">
        <UpdatePasswordForm />
      </div>

      <div class="pf-card pf-card-danger">
        <DeleteUserForm />
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* shared profile styles (global so partials reuse) */
.pf-page {
  max-width: 680px; margin: 0 auto; padding: 1.6rem 1.2rem 3rem;
  font-family: 'Rubik', sans-serif;
}
.pf-header { text-align: center; margin-bottom: 1.5rem; }
.pf-header h1 { font-size: 1.4rem; color: #1a3a6b; margin: 0 0 0.3rem; }
.pf-header p { font-size: 0.9rem; color: #6b7a99; margin: 0; }

.pf-card {
  background: #fff; border: 1px solid #e7eefb; border-radius: 16px;
  box-shadow: 0 6px 22px rgba(0,50,150,0.07);
  padding: 1.6rem; margin-bottom: 1.2rem;
}
.pf-card-danger { border-color: #fad9d9; }

.pf-section-title { font-size: 1.05rem; font-weight: 600; color: #1a3a6b; margin: 0 0 0.3rem; }
.pf-section-desc  { font-size: 0.85rem; color: #6b7a99; margin: 0 0 1.2rem; line-height: 1.5; }

.pf-field { margin-bottom: 1rem; }
.pf-label { display: block; font-size: 0.85rem; color: #4a5568; font-weight: 500; margin-bottom: 0.3rem; }
.pf-input {
  width: 100%; box-sizing: border-box; padding: 0.58rem 0.8rem;
  border: 1.5px solid #d1dce8; border-radius: 9px;
  font-size: 0.92rem; font-family: 'Rubik', sans-serif; color: #1a3a6b; background: #fff;
  transition: border-color 0.18s, box-shadow 0.18s;
}
.pf-input:focus { outline: none; border-color: #2d6be4; box-shadow: 0 0 0 3px rgba(45,107,228,0.12); }
.pf-error { color: #dc2626; font-size: 0.8rem; margin-top: 0.3rem; }

.pf-btn {
  padding: 0.6rem 1.5rem; background: #2d6be4; color: #fff; border: none; border-radius: 9px;
  font-size: 0.9rem; font-weight: 600; font-family: 'Rubik', sans-serif; cursor: pointer; transition: background 0.18s;
}
.pf-btn:hover:not(:disabled) { background: #1a55c8; }
.pf-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.pf-btn-danger { background: #dc2626; }
.pf-btn-danger:hover:not(:disabled) { background: #b91c1c; }

.pf-btn-ghost {
  padding: 0.6rem 1.3rem; background: #fff; color: #4a5568; border: 1.5px solid #d1dce8;
  border-radius: 9px; font-size: 0.9rem; font-family: 'Rubik', sans-serif; cursor: pointer;
}
.pf-btn-ghost:hover { background: #f5f8ff; }

.pf-actions { display: flex; align-items: center; gap: 0.9rem; }
.pf-saved { font-size: 0.85rem; color: #16a34a; }
.pf-link { color: #2d6be4; background: none; border: none; font-size: 0.85rem; cursor: pointer; text-decoration: underline; font-family: 'Rubik', sans-serif; }

.pf-verify-note { font-size: 0.85rem; color: #92704a; background: #fef7e8; border: 1px solid #fde9c4; border-radius: 8px; padding: 0.6rem 0.8rem; margin-bottom: 1rem; }

/* delete-account modal */
.pf-modal-overlay {
  position: fixed; inset: 0; background: rgba(20,40,80,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
}
.pf-modal {
  background: #fff; border-radius: 16px; padding: 1.6rem; max-width: 440px; width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25); font-family: 'Rubik', sans-serif;
}
</style>
