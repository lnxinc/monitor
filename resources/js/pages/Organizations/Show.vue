<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, Plus, Server, Activity, CheckCircle, XCircle, AlertTriangle } from 'lucide-vue-next';

interface Props {
    organization: {
        id: number;
        name: string;
        description: string | null;
        created_at: string;
        updated_at: string;
        devices: Array<{
            id: number;
            name: string;
            ip_address: string;
            status: 'online' | 'offline' | 'warning' | 'critical';
            last_seen_at: string | null;
            incidents: Array<{
                id: number;
                title: string;
                severity: 'low' | 'medium' | 'high' | 'critical';
                status: 'open' | 'acknowledged' | 'resolved';
                occurred_at: string;
            }>;
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Organizations',
        href: '/organizations',
    },
    {
        title: props.organization.name,
        href: `/organizations/${props.organization.id}`,
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

const formatDateTime = (dateString: string | null) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleString();
};

const formatRelativeTime = (dateString: string | null) => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '—';
    const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const seconds = Math.round((Date.now() - date.getTime()) / 1000);
    const divisions: Array<[number, Intl.RelativeTimeFormatUnit]> = [
        [60, 'second'],
        [60, 'minute'],
        [24, 'hour'],
        [7, 'day'],
        [4.34524, 'week'],
        [12, 'month'],
        [Number.POSITIVE_INFINITY, 'year'],
    ];
    let duration = seconds;
    let unit: Intl.RelativeTimeFormatUnit = 'second';
    for (const [amount, nextUnit] of divisions) {
        if (Math.abs(duration) < amount) { unit = nextUnit; break; }
        duration = Math.round(duration / amount);
    }
    return rtf.format(-duration, unit);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const deviceStats = {
    total: props.organization.devices.length,
    online: props.organization.devices.filter(d => d.status === 'online').length,
    offline: props.organization.devices.filter(d => d.status === 'offline').length,
    warning: props.organization.devices.filter(d => d.status === 'warning').length,
    critical: props.organization.devices.filter(d => d.status === 'critical').length,
};

const openIncidents = props.organization.devices.flatMap(d =>
    d.incidents.filter(i => i.status === 'open')
).length;
</script>

<template>
    <Head :title="organization.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ props.organization.name }}</h1>
                    <p v-if="props.organization.description" class="text-muted-foreground">
                        {{ props.organization.description }}
                    </p>
                    <p class="text-sm text-muted-foreground mt-1">
                        Created {{ formatDate(props.organization.created_at) }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <Link :href="`/organizations/${props.organization.id}/edit`">
                        <Button variant="outline">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </Link>
                    <Link href="/devices/create">
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Device
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Devices</CardTitle>
                        <Server class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ deviceStats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Online</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ deviceStats.online }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Issues</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">
                            {{ deviceStats.warning + deviceStats.critical }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Open Incidents</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ openIncidents }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Devices List -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Devices</CardTitle>
                            <CardDescription>
                                Devices managed by this organization
                            </CardDescription>
                        </div>
                        <Link href="/devices/create">
                            <Button variant="outline" size="sm">
                                <Plus class="h-4 w-4 mr-2" />
                                Add Device
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="props.organization.devices.length === 0" class="text-center py-8">
                        <Server class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No devices</h3>
                        <p class="mt-2 text-muted-foreground">
                            Get started by adding your first device.
                        </p>
                        <div class="mt-6">
                            <Link href="/devices/create">
                                <Button>
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Device
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="device in props.organization.devices" :key="device.id"
                             class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex items-center space-x-4">
                                <component :is="getStatusIcon(device.status)"
                                          :class="['h-5 w-5', getStatusColor(device.status)]" />
                                <div>
                                    <h3 class="font-medium">{{ device.name }}</h3>
                                    <p class="text-sm text-muted-foreground">{{ device.ip_address }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Last seen: {{ formatRelativeTime(device.last_seen_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <Badge
                                        :variant="device.status === 'online' ? 'default' :
                                                device.status === 'offline' ? 'secondary' : 'destructive'"
                                        class="capitalize"
                                    >
                                        {{ device.status }}
                                    </Badge>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        {{ device.incidents.filter(i => i.status === 'open').length }} open incidents
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <Link :href="`/devices/${device.id}`">
                                        <Button variant="outline" size="sm">
                                            View
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
