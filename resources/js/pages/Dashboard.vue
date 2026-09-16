<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Activity, AlertTriangle, Building2, CheckCircle, Maximize, Minimize2, Phone, Server, XCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    stats: {
        total_organizations: number;
        total_devices: number;
        online_devices: number;
        offline_devices: number;
        warning_devices: number;
        critical_devices: number;
        open_incidents: number;
        critical_incidents: number;
        total_phone_monitors: number;
        phone_monitors_up: number;
        phone_monitors_down: number;
    };
    recentIncidents: Array<{
        id: number;
        title: string;
        severity: 'low' | 'medium' | 'high' | 'critical';
        status: 'open' | 'acknowledged' | 'resolved';
        occurred_at: string;
        device: {
            id: number;
            name: string;
            organization: {
                id: number;
                name: string;
            };
        };
    }>;
    devicesByStatus: Record<string, number>;
    incidentsBySeverity: Record<string, number>;
    phoneMonitors: Array<{
        id: number;
        name: string;
        phone_number: string;
        status: 'up' | 'down' | 'unknown';
        last_checked_at: string | null;
        last_response_time_ms: number | null;
        failure_reason: string | null;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'online':
            return CheckCircle;
        case 'offline':
            return XCircle;
        case 'warning':
            return AlertTriangle;
        case 'critical':
            return AlertTriangle;
        default:
            return Server;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'online':
            return 'text-green-600';
        case 'offline':
            return 'text-gray-500';
        case 'warning':
            return 'text-yellow-600';
        case 'critical':
            return 'text-red-600';
        default:
            return 'text-gray-500';
    }
};

const getSeverityColor = (severity: string) => {
    switch (severity) {
        case 'low':
            return 'bg-blue-100 text-blue-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'high':
            return 'bg-orange-100 text-orange-800';
        case 'critical':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

// TV/NOC: clock and periodic auto-refresh
const now = ref(new Date());
let clockTimer: number | undefined;
let refreshTimer: number | undefined;

const refresh = () => router.reload({ only: ['stats', 'recentIncidents', 'devicesByStatus', 'incidentsBySeverity', 'phoneMonitors'] });

onMounted(() => {
    clockTimer = window.setInterval(() => (now.value = new Date()), 1000);
    refreshTimer = window.setInterval(refresh, 10000); // 10s refresh for dashboard data
});

onUnmounted(() => {
    if (clockTimer) window.clearInterval(clockTimer);
    if (refreshTimer) window.clearInterval(refreshTimer);
});

// Fullscreen toggle
const isFullscreen = ref<boolean>(!!document.fullscreenElement);
const onFsChange = () => (isFullscreen.value = !!document.fullscreenElement);
onMounted(() => document.addEventListener('fullscreenchange', onFsChange));
onUnmounted(() => document.removeEventListener('fullscreenchange', onFsChange));
const enterFullscreen = async () => {
    try {
        await document.documentElement.requestFullscreen();
    } catch {}
};
const exitFullscreen = async () => {
    try {
        await document.exitFullscreen();
    } catch {}
};
const toggleFullscreen = () => (isFullscreen.value ? exitFullscreen() : enterFullscreen());

// Critical incidents ticker (auto-scroll)
const criticalItems = computed(() => props.recentIncidents.filter((i) => i.severity === 'critical' && i.status !== 'resolved'));
const tickerText = (i: any) => `${new Date(i.occurred_at).toLocaleTimeString()} • ${i.device.organization.name} / ${i.device.name} • ${i.title}`;

// Realtime incidents series (open incidents count over time)
type Point = { t: number; v: number };
const series = ref<Point[]>([]);
const maxPoints = 60; // keep last 60 points
let timer: number | undefined;

const pushPoint = (v: number) => {
    series.value.push({ t: Date.now(), v });
    if (series.value.length > maxPoints) series.value.shift();
};

const fetchOpenCount = async () => {
    try {
        const res = await fetch('/incidents/metrics/open-count', { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        pushPoint(Number(data.open_count ?? 0));
    } catch {
        // ignore transient errors
    }
};

onMounted(() => {
    // seed with current count from props
    pushPoint(props.stats.open_incidents);
    timer = window.setInterval(fetchOpenCount, 5000);
});

onUnmounted(() => {
    if (timer) window.clearInterval(timer);
});

// Build SVG path for the line chart
const chartWidth = 640;
const chartHeight = 200;
const padding = 24;
const innerW = chartWidth - padding * 2;
const innerH = chartHeight - padding * 2;

const pathD = computed(() => {
    const pts = series.value;
    if (pts.length === 0) return '';
    const maxV = Math.max(1, ...pts.map((p) => p.v));
    const stepX = pts.length > 1 ? innerW / (pts.length - 1) : 0;
    const toX = (i: number) => padding + i * stepX;
    const toY = (v: number) => padding + innerH - (v / maxV) * innerH;
    let d = `M ${toX(0)} ${toY(pts[0].v)}`;
    for (let i = 1; i < pts.length; i++) {
        d += ` L ${toX(i)} ${toY(pts[i].v)}`;
    }
    return d;
});

// Precompute points for tooltip and grids
const points = computed(() => {
    const pts = series.value;
    if (pts.length === 0) return [] as Array<{ x: number; y: number; v: number; t: number }>;
    const maxV = Math.max(1, ...pts.map((p) => p.v));
    const stepX = pts.length > 1 ? innerW / (pts.length - 1) : 0;
    const toX = (i: number) => padding + i * stepX;
    const toY = (v: number) => padding + innerH - (v / maxV) * innerH;
    return pts.map((p, i) => ({ x: toX(i), y: toY(p.v), v: p.v, t: p.t }));
});

const yTicks = computed(() => {
    const pts = series.value;
    const maxV = Math.max(1, ...pts.map((p) => p.v));
    const ticks = [0, 0.25, 0.5, 0.75, 1].map((frac) => {
        const v = Math.round(maxV * frac);
        const y = padding + innerH - frac * innerH;
        return { v, y };
    });
    return ticks;
});

// Tooltip interaction
const svgRef = ref<SVGSVGElement | null>(null);
const hoverIndex = ref<number | null>(null);
const currentPoint = computed(() => (hoverIndex.value != null ? points.value[hoverIndex.value] : null));
const stepXComputed = computed(() => (series.value.length > 1 ? innerW / (series.value.length - 1) : 0));
const onMove = (e: MouseEvent) => {
    if (!svgRef.value || series.value.length === 0) return;
    const rect = svgRef.value.getBoundingClientRect();
    const relX = Math.min(Math.max(e.clientX - rect.left - padding, 0), innerW);
    const idx = Math.round(relX / (stepXComputed.value || 1));
    hoverIndex.value = Math.min(Math.max(idx, 0), series.value.length - 1);
};
const onLeave = () => {
    hoverIndex.value = null;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">LNX Monitor Operations Dashboard</h1>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-2xl font-semibold">{{ now.toLocaleTimeString() }}</div>
                        <div class="text-xs text-muted-foreground">{{ now.toLocaleDateString() }}</div>
                    </div>
                    <button
                        @click="toggleFullscreen"
                        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                    >
                        <component :is="isFullscreen ? Minimize2 : Maximize" class="h-4 w-4" />
                        <span>{{ isFullscreen ? 'Exit Full Screen' : 'Full Screen' }}</span>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Organizations</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-4xl font-extrabold">{{ props.stats.total_organizations }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Devices</CardTitle>
                        <Server class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-4xl font-extrabold">{{ props.stats.total_devices }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ props.stats.online_devices }} online, {{ props.stats.offline_devices }} offline
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Incidents</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-4xl font-extrabold text-red-600">{{ props.stats.open_incidents }}</div>
                        <p class="text-xs text-muted-foreground">{{ props.stats.critical_incidents }} critical</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Device Health</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-4xl font-extrabold text-green-600">
                            {{ Math.round((props.stats.online_devices / props.stats.total_devices) * 100) || 0 }}%
                        </div>
                        <p class="text-xs text-muted-foreground">Devices online</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Realtime Incidents Line Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle>Realtime Open Incidents</CardTitle>
                        <CardDescription>Updates every 5 seconds</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="relative w-full overflow-hidden">
                            <svg
                                ref="svgRef"
                                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                                class="h-[220px] w-full"
                                @mousemove="onMove"
                                @mouseleave="onLeave"
                            >
                                <!-- axes/background -->
                                <rect :x="padding" :y="padding" :width="innerW" :height="innerH" class="fill-background stroke-muted" />
                                <!-- y gridlines and labels -->
                                <g v-for="(tick, i) in yTicks" :key="i">
                                    <line
                                        :x1="padding"
                                        :x2="padding + innerW"
                                        :y1="tick.y"
                                        :y2="tick.y"
                                        class="stroke-muted"
                                        stroke-dasharray="4 4"
                                    />
                                    <text :x="padding - 6" :y="tick.y + 4" class="fill-muted-foreground text-[10px]" text-anchor="end">
                                        {{ tick.v }}
                                    </text>
                                </g>
                                <!-- series path -->
                                <path :d="pathD" class="fill-none stroke-blue-600" stroke-width="2" />
                                <!-- hover marker -->
                                <template v-if="currentPoint">
                                    <line
                                        :x1="currentPoint.x"
                                        :x2="currentPoint.x"
                                        :y1="padding"
                                        :y2="padding + innerH"
                                        class="stroke-blue-200"
                                        stroke-dasharray="4 4"
                                    />
                                    <circle :cx="currentPoint.x" :cy="currentPoint.y" r="3" class="fill-blue-600" />
                                </template>
                            </svg>
                            <div
                                v-if="currentPoint"
                                class="absolute rounded border bg-background px-2 py-1 text-xs shadow-sm"
                                :style="{
                                    left: `calc(${(currentPoint.x / chartWidth) * 100}% + 8px)`,
                                    top: `${Math.max(0, (currentPoint.y / chartHeight) * 100 - 10)}%`,
                                }"
                            >
                                <div class="font-medium">{{ currentPoint.v }} open</div>
                                <div class="text-muted-foreground">{{ new Date(currentPoint.t).toLocaleTimeString() }}</div>
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">
                                Last value: {{ series.length ? series[series.length - 1].v : 0 }} open incidents
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <!-- Active Incidents (auto-refresh) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between"
                            >Active Incidents <span class="text-xs font-normal text-muted-foreground">auto-refreshing</span></CardTitle
                        >
                        <CardDescription> Latest incidents across all devices </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-h-[380px] space-y-4 overflow-y-auto pr-2">
                            <div v-if="props.recentIncidents.length === 0" class="py-4 text-center text-muted-foreground">No recent incidents</div>
                            <div
                                v-else
                                v-for="incident in props.recentIncidents"
                                :key="incident.id"
                                class="flex items-center justify-between space-x-4 border-b pb-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-base font-semibold">
                                        {{ incident.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ incident.device.organization.name }} - {{ incident.device.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDateTime(incident.occurred_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Badge :class="getSeverityColor(incident.severity)">
                                        {{ incident.severity }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Device Status Overview -->
                <Card>
                    <CardHeader>
                        <CardTitle>Device Status Overview</CardTitle>
                        <CardDescription> Current status of all monitored devices </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="(count, status) in props.devicesByStatus" :key="status" class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <component :is="getStatusIcon(status)" :class="['h-4 w-4', getStatusColor(status)]" />
                                    <span class="text-sm font-medium capitalize">{{ status }}</span>
                                </div>
                                <span class="text-sm font-medium">{{ count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Phone Monitoring Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Phone class="h-5 w-5" />
                            Phone Monitoring
                        </CardTitle>
                        <CardDescription> SIP server health and phone number availability </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="grid grid-cols-3 gap-4 border-b pb-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ props.stats.total_phone_monitors }}</div>
                                    <div class="text-xs text-muted-foreground">Total</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">{{ props.stats.phone_monitors_up }}</div>
                                    <div class="text-xs text-muted-foreground">Up</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-600">{{ props.stats.phone_monitors_down }}</div>
                                    <div class="text-xs text-muted-foreground">Down</div>
                                </div>
                            </div>
                            <div class="max-h-[280px] space-y-3 overflow-y-auto">
                                <div v-if="props.phoneMonitors.length === 0" class="py-4 text-center text-muted-foreground">
                                    No phone monitors configured
                                </div>
                                <div
                                    v-else
                                    v-for="monitor in props.phoneMonitors"
                                    :key="monitor.id"
                                    class="flex items-center justify-between space-x-4 border-b pb-3 last:border-b-0"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold">{{ monitor.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ monitor.phone_number }}</p>
                                        <p v-if="monitor.last_checked_at" class="text-xs text-muted-foreground">
                                            {{ formatDateTime(monitor.last_checked_at) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="monitor.status === 'up'" class="bg-green-100 text-green-800"> Up </Badge>
                                        <Badge v-else-if="monitor.status === 'down'" class="bg-red-100 text-red-800"> Down </Badge>
                                        <Badge v-else class="bg-gray-100 text-gray-800"> Unknown </Badge>
                                        <span v-if="monitor.last_response_time_ms" class="text-xs text-muted-foreground">
                                            {{ monitor.last_response_time_ms }}ms
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Critical incidents ticker -->
            <div v-if="criticalItems.length > 0" class="ticker mt-4">
                <div class="border-t border-red-200 bg-red-50 px-3 py-2 dark:border-red-900/30 dark:bg-red-900/20">
                    <div class="mb-1 flex items-center gap-2 text-red-700 dark:text-red-300">
                        <AlertTriangle class="h-4 w-4" />
                        <span class="text-xs font-semibold tracking-wider uppercase">Critical Incidents</span>
                    </div>
                    <div class="ticker__inner text-red-700 dark:text-red-200">
                        <div class="ticker__track">
                            <span v-for="i in criticalItems" :key="'a' + i.id" class="mx-6 text-sm">{{ tickerText(i) }}</span>
                        </div>
                        <div class="ticker__track" aria-hidden="true">
                            <span v-for="i in criticalItems" :key="'b' + i.id" class="mx-6 text-sm">{{ tickerText(i) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.ticker {
    position: relative;
    width: 100%;
    overflow: hidden;
}
.ticker__inner {
    white-space: nowrap;
}
.ticker__track {
    display: inline-block;
    padding-left: 100%;
    animation: ticker-move var(--ticker-duration, 30s) linear infinite;
}
@keyframes ticker-move {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-100%);
    }
}
</style>
