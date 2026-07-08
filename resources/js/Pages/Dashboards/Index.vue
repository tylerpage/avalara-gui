<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    dashboards: {
        type: Array,
        required: true,
    },
    currentDashboardId: {
        type: Number,
        required: true,
    },
});

const form = useForm({
    name: '',
});

function createDashboard() {
    form.post('/dashboards', {
        onSuccess: () => {
            form.reset('name');
        },
    });
}

function switchDashboard(id) {
    router.post(`/dashboards/${id}/switch`);
}
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-semibold">Dashboards</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Each dashboard has its own Shopware credentials, order reviews, webhooks, and passcode.
                    Switch between them from the header or below.
                </p>
            </div>

            <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h2 class="text-lg font-medium text-sky-300">Create Dashboard</h2>
                <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="createDashboard">
                    <label class="flex-1 block space-y-1">
                        <span class="text-sm text-slate-300">Name</span>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Production, Staging, Returns POC"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-400">{{ form.errors.name }}</p>
                    </label>
                    <button
                        type="submit"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium hover:bg-sky-500 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Create
                    </button>
                </form>
            </section>

            <section class="rounded-xl border border-slate-800 overflow-hidden">
                <div class="border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                    <h2 class="text-lg font-medium text-sky-300">Your Dashboards</h2>
                </div>
                <ul class="divide-y divide-slate-800">
                    <li
                        v-for="dashboard in dashboards"
                        :key="dashboard.id"
                        class="px-4 py-4 space-y-3"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ dashboard.name }}</span>
                                    <span
                                        v-if="dashboard.id === currentDashboardId"
                                        class="rounded-full bg-emerald-900/60 text-emerald-200 px-2 py-0.5 text-xs"
                                    >
                                        Active
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 font-mono">{{ dashboard.slug }}</p>
                            </div>
                            <button
                                v-if="dashboard.id !== currentDashboardId"
                                type="button"
                                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm hover:bg-slate-800"
                                @click="switchDashboard(dashboard.id)"
                            >
                                Switch
                            </button>
                        </div>
                        <div class="text-xs text-slate-500">
                            Webhook URL:
                            <code class="text-sky-300 break-all">{{ dashboard.webhookUrl }}</code>
                        </div>
                    </li>
                </ul>
            </section>

            <Link href="/" class="text-sm text-sky-400 hover:underline">← Back to home</Link>
        </div>
    </AppLayout>
</template>
