<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Server, Edit, Eye, Plus, Trash2, CheckCircle, XCircle, AlertTriangle, Link as LinkIcon } from 'lucide-vue-next';

interface Props {
    devices: Array<{
        id: number;
        name: string;
        ip_address: string;
        secret: string;
        status: 'online' | 'offline' | 'warning' | 'critical';
        last_seen_at: string | null;
        external_source?: string | null;
        unifi_console_id?: string | null;
        created_at: string;
        updated_at: string;
        organization: {
            id: number;
            name: string;
        };
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Devices',
        href: '/devices',
    },
];

const deleteDevice = (deviceId: number) => {
    if (confirm('Are you sure you want to delete this device? This will also delete all associated incidents.')) {
        router.delete(`/devices/${deviceId}`);
    }
};

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

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'online':
            return 'default';
        case 'offline':
            return 'secondary';
        case 'warning':
            return 'outline';
        case 'critical':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const formatDateTime = (dateString: string | null) => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '—';

    const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const seconds = Math.round((Date.now() - date.getTime()) / 1000);

    const divisions: Array<[number, Intl.RelativeTimeFormatUnit]> = [
        [60, 'second'],          // up to 60 seconds -> seconds ago
        [60, 'minute'],          // up to 60 minutes -> minutes ago
        [24, 'hour'],            // up to 24 hours -> hours ago
        [7, 'day'],              // up to 7 days -> days ago
        [4.34524, 'week'],       // approx weeks in a month
        [12, 'month'],           // months in a year
        [Number.POSITIVE_INFINITY, 'year'],
    ];

    let duration = seconds;
    let unit: Intl.RelativeTimeFormatUnit = 'second';
    for (const [amount, nextUnit] of divisions) {
        if (Math.abs(duration) < amount) {
            unit = nextUnit;
            break;
        }
        duration = Math.round(duration / amount);
    }
    return rtf.format(-duration, unit);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const unifiUrl = (device: any) => {
    if (device?.external_source === 'unifi_site_manager' && device?.unifi_console_id) {
        return `https://unifi.ui.com/consoles/${device.unifi_console_id}/network/default`;
    }
    return null;
};
</script>

<template>
    <Head title="Devices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Devices</h1>
                    <p class="text-muted-foreground">
                        Monitor and manage all devices
                    </p>
                </div>
                <Link href="/devices/create">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        Add Device
                    </Button>
                </Link>
            </div>

            <div v-if="props.devices.length === 0" class="text-center py-12">
                <Server class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No devices</h3>
                <p class="mt-2 text-muted-foreground">
                    Get started by creating your first device.
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

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="device in props.devices" :key="device.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3 flex-1">
                                <component :is="getStatusIcon(device.status)"
                                          :class="['h-5 w-5 mt-0.5', getStatusColor(device.status)]" />
                                <div class="flex-1 min-w-0">
                                    <CardTitle class="text-lg truncate">{{ device.name }}</CardTitle>
                                    <CardDescription class="space-y-1">
                                        <div class="font-mono text-sm">{{ device.ip_address }}</div>
                                        <div class="text-xs">{{ device.organization.name }}</div>
                                    </CardDescription>
                                </div>
                            </div>
                            <Badge :variant="getStatusBadgeVariant(device.status)" class="capitalize">
                                {{ device.status }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 gap-y-6">
                            <div class="text-sm text-muted-foreground">
                                <div>Last seen: {{ formatDateTime(device.last_seen_at) }}</div>
                                <div>Created: {{ formatDate(device.created_at) }}</div>
                            </div>

                            <div class="flex items-center justify-between space-x-2 pt-2">
                                <div class="flex space-x-2">
                                    <Link :href="`/devices/${device.id}`">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/devices/${device.id}/edit`">
                                        <Button variant="outline" size="sm">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <a v-if="unifiUrl(device)" :href="unifiUrl(device)" target="_blank" rel="noopener noreferrer">
                                        <Button variant="outline" size="sm" title="Open UniFi Network">
                                            <LinkIcon class="h-4 w-4" />
                                        </Button>
                                    </a>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="deleteDevice(device.id)"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
