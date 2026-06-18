<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="הזמנה פגת תוקף" />

        <div class="text-center" dir="rtl">
            <div class="text-5xl mb-4">⏰</div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">ההזמנה פגה תוקף</h1>
            <p class="text-sm text-gray-500">
                קישור ההזמנה הזה אינו בתוקף עוד.<br>
                בקשי מהמזמין/ה לשלוח הזמנה חדשה.
            </p>
        </div>
    </GuestLayout>
</template>
