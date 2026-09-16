<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RefreshCw, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    monitor: {
        id: number;
        name: string;
        type: 'http' | 'ping' | 'tcp' | 'ssl' | 'webhook';
        display_target: string | null;
        url: string;
        frequency_minutes: number;
        status: 'up' | 'down' | 'unknown';
        last_checked_at: string | null;
        last_status_code: number | null;
        last_response_time_ms: number | null;
        failure_reason?: string | null;
        ssl_valid_from?: string | null;
        ssl_expires_at?: string | null;
        ssl_issuer?: string | null;
        webhook_token?: string | null;
        consecutive_failures?: number | null;
        checks: Array<{
            id: number;
            status: 'up' | 'down';
            status_code: number | null;
            response_time_ms: number | null;
            error: string | null;
            checked_at: string;
            meta?: Record<string, any> | null;
            payload?: Record<string, any> | null;
        }>;
    };
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Monitors', href: '/monitors' },
    { title: props.monitor.name, href: `/monitors/${props.monitor.id}` },
];

const typeLabel = computed(
    () =>
        ({
            http: 'HTTP',
            ping: 'Ping',
            tcp: 'TCP',
            ssl: 'SSL',
            webhook: 'Webhook',
        })[props.monitor.type] ?? props.monitor.type,
);

const isWebhook = computed(() => props.monitor.type === 'webhook');
const displayTarget = computed(() => props.monitor.display_target ?? props.monitor.url);

const runNow = () => {
    if (isWebhook.value) return;
    router.post(`/monitors/${props.monitor.id}/run`, {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['monitor'] }) });
};

const destroyMonitor = () => {
    if (confirm('Delete this monitor? Its history will also be removed.')) {
        router.delete(`/monitors/${props.monitor.id}`);
    }
};

const formatJson = (value: Record<string, any> | null | undefined) => {
    if (!value) return '';
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};
</script>

<template>
    <Head :title="props.monitor.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold tracking-tight">{{ props.monitor.name }}</h1>
                        <Badge variant="outline" class="text-xs uppercase">{{ typeLabel }}</Badge>
                    </div>
                    <p class="text-muted-foreground">{{ displayTarget }}</p>
                    <p v-if="isWebhook" class="mt-1 text-xs text-muted-foreground">
                        Webhook token: {{ props.monitor.webhook_token ?? 'generated after save' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/monitors/${props.monitor.id}/edit`"
                        ><Button variant="outline" size="sm"><SquarePen class="h-4 w-4" /> Edit</Button></Link
                    >
                    <Button variant="outline" size="sm" class="text-destructive" @click="destroyMonitor"><Trash2 class="h-4 w-4" /> Delete</Button>
                    <Button v-if="!isWebhook" variant="outline" size="sm" @click="runNow"><RefreshCw class="h-4 w-4" /> Run Check</Button>
                    <Link href="/monitors"><Button variant="outline" size="sm">Back</Button></Link>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Status</CardTitle>
                    <CardDescription>Current monitor summary</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <div class="text-sm text-muted-foreground">State</div>
                            <div
                                :class="[
                                    'text-xl font-semibold capitalize',
                                    props.monitor.status === 'up'
                                        ? 'text-green-600'
                                        : props.monitor.status === 'down'
                                          ? 'text-red-600'
                                          : 'text-gray-500',
                                ]"
                            >
                                {{ props.monitor.status }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-muted-foreground">Last Check</div>
                            <div class="text-xl font-semibold">
                                {{ props.monitor.last_checked_at ? new Date(props.monitor.last_checked_at).toLocaleString() : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-muted-foreground">Last Response</div>
                            <div class="text-xl font-semibold">
                                {{ props.monitor.last_status_code ?? '—' }} •
                                {{ props.monitor.last_response_time_ms ? props.monitor.last_response_time_ms + 'ms' : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-muted-foreground">Cadence</div>
                            <div class="text-xl font-semibold">{{ isWebhook ? 'Webhook' : `Every ${props.monitor.frequency_minutes} min` }}</div>
                        </div>
                    </div>
                    <div v-if="props.monitor.failure_reason" class="mt-3 text-sm text-destructive">{{ props.monitor.failure_reason }}</div>
                    <div v-if="props.monitor.consecutive_failures" class="mt-2 text-xs text-muted-foreground">
                        Consecutive failures: {{ props.monitor.consecutive_failures }}
                    </div>
                </CardContent>
            </Card>

            <Card v-if="props.monitor.ssl_expires_at">
                <CardHeader>
                    <CardTitle>SSL Certificate</CardTitle>
                    <CardDescription>Metadata captured from the last successful probe</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-muted-foreground">Valid From</div>
                        <div class="text-sm font-medium">
                            {{ props.monitor.ssl_valid_from ? new Date(props.monitor.ssl_valid_from).toLocaleString() : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Expires</div>
                        <div class="text-sm font-medium">
                            {{ props.monitor.ssl_expires_at ? new Date(props.monitor.ssl_expires_at).toLocaleString() : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Issuer</div>
                        <div class="text-sm font-medium break-words">{{ props.monitor.ssl_issuer ?? '—' }}</div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Checks</CardTitle>
                    <CardDescription>Last 50 results</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-[400px] space-y-2 overflow-y-auto pr-2">
                        <div v-for="c in props.monitor.checks" :key="c.id" class="flex flex-col gap-2 border-b pb-3">
                            <div class="flex items-center justify-between">
                                <div class="text-sm">{{ new Date(c.checked_at).toLocaleString() }}</div>
                                <div class="text-sm">{{ c.status_code ?? '—' }} • {{ c.response_time_ms ? c.response_time_ms + 'ms' : '—' }}</div>
                                <div :class="['text-sm capitalize', c.status === 'up' ? 'text-green-600' : 'text-red-600']">{{ c.status }}</div>
                            </div>
                            <div v-if="c.error" class="text-xs text-destructive">{{ c.error }}</div>
                            <pre v-if="c.meta" class="rounded bg-muted/40 p-2 font-mono text-[11px] break-all whitespace-pre-wrap">{{
                                formatJson(c.meta)
                            }}</pre>
                            <pre v-if="c.payload" class="rounded bg-muted/20 p-2 font-mono text-[11px] break-all whitespace-pre-wrap">{{
                                formatJson(c.payload)
                            }}</pre>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
