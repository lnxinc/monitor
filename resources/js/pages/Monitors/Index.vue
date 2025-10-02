<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Globe, Plus, RefreshCw, SquarePen, Trash2 } from 'lucide-vue-next';

const typeLabels: Record<string, string> = {
  http: 'HTTP',
  ping: 'Ping',
  tcp: 'TCP',
  ssl: 'SSL',
  webhook: 'Webhook',
};

interface Props {
  monitors: Array<{
    id:number;
    name:string;
    type:string;
    display_target:string|null;
    url:string;
    frequency_minutes:number;
    status:'up'|'down'|'unknown';
    last_checked_at:string|null;
    last_status_code:number|null;
    last_response_time_ms:number|null;
  }>;
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Monitors', href: '/monitors' },
];

const runNow = (monitor: Props['monitors'][number]) => {
  if (monitor.type === 'webhook') return;
  router.post(`/monitors/${monitor.id}/run`, {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['monitors'] }) });
};

const destroyMonitor = (id: number) => {
  if (confirm('Delete this monitor? Its history will also be removed.')) {
    router.delete(`/monitors/${id}`, { preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Monitors" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Monitors</h1>
          <p class="text-muted-foreground">Fleet-wide probes across web, infrastructure, and webhook signals</p>
        </div>
        <Link href="/monitors/create"><Button><Plus class="h-4 w-4 mr-2" />Add Monitor</Button></Link>
      </div>

      <div v-if="props.monitors.length === 0" class="text-center py-12">
        <Globe class="mx-auto h-12 w-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-medium">No monitors</h3>
        <p class="mt-2 text-muted-foreground">Create your first HTTP/SSL monitor.</p>
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="m in props.monitors" :key="m.id">
          <CardHeader class="flex items-center justify-between flex-row">
            <div class="flex flex-col gap-1">
              <CardTitle class="text-lg">{{ m.name }}</CardTitle>
              <Badge variant="outline" class="w-fit text-xs uppercase">{{ typeLabels[m.type] ?? m.type }}</Badge>
            </div>
            <span :class="['text-xs font-medium capitalize', m.status==='up'?'text-green-600':(m.status==='down'?'text-red-600':'text-gray-500') ]">{{ m.status }}</span>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="text-sm text-muted-foreground truncate">{{ m.display_target ?? m.url }}</div>
            <div class="text-xs text-muted-foreground" v-if="m.type !== 'webhook'">Every {{ m.frequency_minutes }} min</div>
            <div class="text-xs text-muted-foreground" v-else>Inbound via webhook</div>
            <div class="text-xs text-muted-foreground">Last check: {{ m.last_checked_at ? new Date(m.last_checked_at).toLocaleString() : '—' }}</div>
            <div class="flex items-center justify-between">
              <div class="text-xs text-muted-foreground">{{ m.last_status_code ?? '—' }} • {{ m.last_response_time_ms ? m.last_response_time_ms+'ms' : '—' }}</div>
              <div class="flex gap-2">
                <Link :href="`/monitors/${m.id}`"><Button variant="outline" size="sm">View</Button></Link>
                <Link :href="`/monitors/${m.id}/edit`"><Button variant="outline" size="sm"><SquarePen class="h-4 w-4" /></Button></Link>
                <Button v-if="m.type !== 'webhook'" variant="outline" size="sm" @click="runNow(m)" title="Run check"><RefreshCw class="h-4 w-4" /></Button>
                <Button variant="outline" size="sm" class="text-destructive" @click="destroyMonitor(m.id)" title="Delete"><Trash2 class="h-4 w-4" /></Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
