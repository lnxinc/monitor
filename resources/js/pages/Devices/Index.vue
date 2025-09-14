<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Server, Edit, Eye, Plus, Trash2, CheckCircle, XCircle, AlertTriangle, Copy, Link as LinkIcon } from 'lucide-vue-next';

interface Props {
    devices: Array<{
        id: number;
        name: string;
        ip_address: string;
        secret: string;
        status: 'online' | 'offline' | 'warning' | 'critical';
        last_seen_at: string | null;
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
    return new Date(dateString).toLocaleString();
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const copySecret = async (secret: string) => {
    try {
        await navigator.clipboard.writeText(secret);
        // You could add a toast notification here
        console.log('Secret copied to clipboard');
    } catch (err) {
        console.error('Failed to copy secret:', err);
    }
};

const webhookUrl = (secret: string) => `${window.location.origin}/api/devices/${secret}/incidents`;
const copyWebhook = async (secret: string) => {
    try {
        await navigator.clipboard.writeText(webhookUrl(secret));
        console.log('Webhook URL copied');
    } catch (err) {
        console.error('Failed to copy webhook URL:', err);
    }
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

                            <div class="space-y-2">
                                <div class="text-xs text-muted-foreground">Device Secret:</div>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 text-xs bg-muted p-2 rounded font-mono truncate">
                                        {{ device.secret }}
                                    </code>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="copySecret(device.secret)"
                                        class="p-1"
                                    >
                                        <Copy class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="text-xs text-muted-foreground">Webhook URL:</div>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 text-xs bg-muted p-2 rounded font-mono truncate">
                                        {{ webhookUrl(device.secret) }}
                                    </code>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="copyWebhook(device.secret)"
                                        class="p-1"
                                        title="Copy Webhook URL"
                                    >
                                        <LinkIcon class="h-3 w-3" />
                                    </Button>
                                </div>
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
