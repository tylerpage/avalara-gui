<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    passcode: '',
});

function submit() {
    form.post('/passcode');
}
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center px-4 bg-slate-950 text-slate-100">
        <div class="w-full max-w-sm space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-semibold text-sky-400">Order Transactions Debug</h1>
                <p class="mt-2 text-sm text-slate-400">Enter the passcode to continue.</p>
            </div>

            <form class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-4" @submit.prevent="submit">
                <label class="block space-y-1">
                    <span class="text-sm text-slate-300">Passcode</span>
                    <input
                        v-model="form.passcode"
                        type="password"
                        required
                        autofocus
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                    />
                    <p v-if="form.errors.passcode" class="text-sm text-red-400">
                        {{ form.errors.passcode }}
                    </p>
                </label>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-sky-600 px-4 py-2 font-medium hover:bg-sky-500 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Unlock
                </button>
            </form>
        </div>
    </div>
</template>
