<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token:      { type: String, required: true },
    email:      { type: String, required: true },
    personName: { type: String, default: null },
});

const form = useForm({
    name:                  '',
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('invitation.register', props.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="הצטרפות למשפחת ואקיל" />

        <div class="mb-6 text-center" dir="rtl">
            <h1 class="text-xl font-bold text-gray-800">ברוכים הבאים למשפחת ואקיל 🌳</h1>
            <p v-if="personName" class="mt-1 text-sm text-gray-600">
                הצטרף/י כ-<strong>{{ personName }}</strong>
            </p>
            <p class="mt-1 text-sm text-gray-500">{{ email }}</p>
        </div>

        <form @submit.prevent="submit" dir="rtl">
            <div>
                <InputLabel for="name" value="שם מלא" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="סיסמה" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="אימות סיסמה" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6 flex justify-center">
                <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                    הצטרף/י עכשיו
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
