<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { RefreshCw, SquarePen, Trash2 } from 'lucide-vue-next';

interface Props {
  monitor: {
    id:number; name:string; url:string; frequency_minutes:number;
    status:'up'|'down'|'unknown'; last_checked_at:string|null; last_status_code:number|null; last_response_time_ms:number|null;
    failure_reason?: string | null;
    checks: Array<{ id:number; status:'up'|'down'; status_code:number|null; response_time_ms:number|null; error:string|null; checked_at:string }>;
  }
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Monitors', href: '/monitors' },
  { title: props.monitor.name, href: `/monitors/${props.monitor.id}` },
];

const runNow = () => router.post(`/monitors/${props.monitor.id}/run`, {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['monitor'] }) });
const destroyMonitor = () => {
  if (confirm('Delete this monitor? Its history will also be removed.')) {
    router.delete(`/monitors/${props.monitor.id}`);
  }
};
</script>

<template>
  <Head :title="props.monitor.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">{{ props.monitor.name }}</h1>
          <p class="text-muted-foreground">{{ props.monitor.url }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="`/monitors/${props.monitor.id}/edit`"><Button variant="outline" size="sm"><SquarePen class="h-4 w-4" /> Edit</Button></Link>
          <Button variant="outline" size="sm" class="text-destructive" @click="destroyMonitor"><Trash2 class="h-4 w-4" /> Delete</Button>
          <Button variant="outline" size="sm" @click="runNow"><RefreshCw class="h-4 w-4" /> Run Check</Button>
          <Link href="/monitors"><Button variant="outline" size="sm">Back</Button></Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Status</CardTitle>
          <CardDescription>Current monitor summary</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <div class="text-sm text-muted-foreground">State</div>
              <div :class="['text-xl font-semibold capitalize', props.monitor.status==='up'?'text-green-600':(props.monitor.status==='down'?'text-red-600':'text-gray-500') ]">{{ props.monitor.status }}</div>
            </div>
            <div>
              <div class="text-sm text-muted-foreground">Last Check</div>
              <div class="text-xl font-semibold">{{ props.monitor.last_checked_at ? new Date(props.monitor.last_checked_at).toLocaleString() : '—' }}</div>
            </div>
            <div>
              <div class="text-sm text-muted-foreground">Last Response</div>
              <div class="text-xl font-semibold">{{ props.monitor.last_status_code ?? '—' }} • {{ props.monitor.last_response_time_ms ? props.monitor.last_response_time_ms+'ms' : '—' }}</div>
            </div>
          </div>
          <div v-if="props.monitor.failure_reason" class="text-sm text-destructive mt-3">{{ props.monitor.failure_reason }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Recent Checks</CardTitle>
          <CardDescription>Last 50 results</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2">
            <div v-for="c in props.monitor.checks" :key="c.id" class="flex items-center justify-between border-b pb-2">
              <div class="text-sm">{{ new Date(c.checked_at).toLocaleString() }}</div>
              <div class="text-sm">{{ c.status_code ?? '—' }} • {{ c.response_time_ms ? c.response_time_ms+'ms' : '—' }}</div>
              <div :class="['text-sm capitalize', c.status==='up'?'text-green-600':'text-red-600']">{{ c.status }}</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
