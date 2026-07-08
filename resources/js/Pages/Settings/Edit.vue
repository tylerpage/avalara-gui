<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    shopware_url: props.settings.shopware_url,
    shopware_client_id: props.settings.shopware_client_id,
    shopware_client_secret: '',
    avalara_account_number: props.settings.avalara_account_number,
    avalara_license_key: '',
    avalara_company_code: props.settings.avalara_company_code,
    avalara_is_live: props.settings.avalara_is_live,
    authnet_api_login_id: props.settings.authnet_api_login_id,
    authnet_transaction_key: '',
    authnet_is_live: props.settings.authnet_is_live,
});

function save() {
    form.put('/settings');
}

function testShopware() {
    form.post('/settings/test-shopware', { preserveScroll: true });
}

function testAvalara() {
    form.post('/settings/test-avalara', { preserveScroll: true });
}

function testAuthnet() {
    form.post('/settings/test-authnet', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-semibold">Connection Settings</h1>
                <p class="mt-2 text-slate-400 text-sm">
                    Shopware credentials connect to the Admin API and ReturnsAvalara/ReturnsAuthnet endpoints in returns-poc.
                    Avalara and Authorize.net credentials enable live transaction lookups.
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="save">
                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <h2 class="text-lg font-medium text-sky-300">Shopware Admin API</h2>
                    <p class="text-sm text-slate-400">
                        Create an integration in Shopware Admin (Settings → System → Integrations) with order and
                        order_return permissions.
                    </p>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Shop URL</span>
                        <input
                            v-model="form.shopware_url"
                            type="url"
                            required
                            placeholder="https://your-shop.example.com"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Client ID</span>
                        <input
                            v-model="form.shopware_client_id"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Client Secret</span>
                        <input
                            v-model="form.shopware_client_secret"
                            type="password"
                            :placeholder="settings.has_shopware_client_secret ? 'Leave blank to keep current secret' : 'Required'"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                        <p v-if="form.errors.shopware_client_secret" class="text-sm text-red-400">
                            {{ form.errors.shopware_client_secret }}
                        </p>
                    </label>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800"
                        @click="testShopware"
                    >
                        Test Shopware Connection
                    </button>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <h2 class="text-lg font-medium text-sky-300">Avalara REST API</h2>
                    <p class="text-sm text-slate-400">
                        Optional for live Avalara lookups: the original order invoice, plus per-return refund and
                        verify transactions by document code. Returns reconcile snapshots still come from Shopware via
                        <code class="text-sky-300">/api/_action/returns-avalara/*</code>.
                    </p>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Account Number</span>
                        <input
                            v-model="form.avalara_account_number"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">License Key</span>
                        <input
                            v-model="form.avalara_license_key"
                            type="password"
                            :placeholder="settings.has_avalara_license_key ? 'Leave blank to keep current key' : ''"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Company Code</span>
                        <input
                            v-model="form.avalara_company_code"
                            type="text"
                            placeholder="e.g. ELLEVETSCIENCESLLC"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                        <p class="text-xs text-slate-500">The string company code from Avalara Admin, not the numeric company ID.</p>
                    </label>

                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input v-model="form.avalara_is_live" type="checkbox" class="rounded border-slate-600" />
                        Use production environment (rest.avatax.com)
                    </label>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800"
                        @click="testAvalara"
                    >
                        Test Avalara Connection
                    </button>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <h2 class="text-lg font-medium text-sky-300">Authorize.net API</h2>
                    <p class="text-sm text-slate-400">
                        Optional for fetching live payment transaction details. Returns reconcile data still comes from
                        Shopware via <code class="text-sky-300">/api/_action/returns-authnet/*</code>.
                    </p>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">API Login ID</span>
                        <input
                            v-model="form.authnet_api_login_id"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm text-slate-300">Transaction Key</span>
                        <input
                            v-model="form.authnet_transaction_key"
                            type="password"
                            :placeholder="settings.has_authnet_transaction_key ? 'Leave blank to keep current key' : ''"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                        />
                    </label>

                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input v-model="form.authnet_is_live" type="checkbox" class="rounded border-slate-600" />
                        Use production environment (api.authorize.net)
                    </label>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800"
                        @click="testAuthnet"
                    >
                        Test Authorize.net Connection
                    </button>
                </section>

                <button
                    type="submit"
                    class="rounded-lg bg-sky-600 px-4 py-2 font-medium hover:bg-sky-500 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Save Settings
                </button>
            </form>
        </div>
    </AppLayout>
</template>
