<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Props {
  monitors: Array<{
    id: number;
    name: string;
    url: string;
    status: 'up' | 'down' | 'unknown';
    last_response_time_ms: number | null;
    last_checked_at: string | null;
    uptime_24h: number | null;
  }>;
  since: string;
}

const props = defineProps<Props>();

const statusColor = (s: string) => s === 'up' ? 'text-green-600' : s === 'down' ? 'text-red-600' : 'text-gray-500';
const formatRelativeTime = (dateString: string | null) => {
  if (!dateString) return 'Never';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '—';
  const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
  const seconds = Math.round((Date.now() - date.getTime()) / 1000);
  const divisions: Array<[number, Intl.RelativeTimeFormatUnit]> = [
    [60, 'second'], [60, 'minute'], [24, 'hour'], [7, 'day'], [4.34524, 'week'], [12, 'month'], [Infinity, 'year'],
  ];
  let duration = seconds; let unit: Intl.RelativeTimeFormatUnit = 'second';
  for (const [amount, nextUnit] of divisions) { if (Math.abs(duration) < amount) { unit = nextUnit; break; } duration = Math.round(duration / amount); }
  return rtf.format(-duration, unit);
}
</script>

<template>
  <Head title="Status" />
  <div class="min-h-screen bg-background text-foreground">
    <div class="max-w-5xl mx-auto p-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold">LNX-360 Service Status</h1>
        <p class="text-muted-foreground">Uptime over the last 24 hours</p>
      </div>

      <div class="space-y-3">
        <div v-for="m in props.monitors" :key="m.id" class="flex items-center justify-between p-4 border rounded-lg">
          <div class="min-w-0">
            <div class="flex items-center gap-3">
              <div :class="['w-3 h-3 rounded-full', m.status==='up' ? 'bg-green-500' : m.status==='down' ? 'bg-red-500' : 'bg-gray-400']"></div>
              <div class="font-medium truncate">{{ m.name }}</div>
            </div>
            <div class="text-xs text-muted-foreground truncate">{{ m.url }}</div>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-right">
              <div class="text-sm">Uptime (24h)</div>
              <div class="font-semibold">{{ m.uptime_24h != null ? `${m.uptime_24h}%` : '—' }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm">Last check</div>
              <div class="text-muted-foreground text-sm">{{ formatRelativeTime(m.last_checked_at) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm">Response</div>
              <div class="text-sm">{{ m.last_response_time_ms != null ? `${m.last_response_time_ms} ms` : '—' }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 text-xs text-muted-foreground">Since: {{ new Date(props.since).toLocaleString() }}</div>
    </div>
  </div>
</template>

