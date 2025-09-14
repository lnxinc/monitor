<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Server, Edit, CheckCircle, XCircle, AlertTriangle, Activity, Copy, Eye, Link as LinkIcon } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

interface Props {
    device: {
        id: number;
        name: string;
        ip_address: string;
        secret: string;
        device_type_id?: number | null;
        status: 'online' | 'offline' | 'warning' | 'critical';
        last_seen_at: string | null;
        created_at: string;
        updated_at: string;
        organization: {
            id: number;
            name: string;
        };
        device_type?: { id: number; name: string } | null;
        incidents: Array<{
            id: number;
            title: string;
            severity: 'low' | 'medium' | 'high' | 'critical';
            status: 'open' | 'acknowledged' | 'resolved';
            occurred_at: string;
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
        title: 'Devices',
        href: '/devices',
    },
    {
        title: props.device.name,
        href: `/devices/${props.device.id}`,
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

const payload = reactive({
    severity: 'high',
    title: `Alert on ${props.device.name}`,
    description: 'Describe the issue... (e.g., Disk space low)',
    device_status: 'warning',
});

const exampleJson = computed(() => JSON.stringify({
    severity: payload.severity,
    title: payload.title,
    description: payload.description,
    device_status: payload.device_status,
    metadata: { source: 'manual' },
}, null, 2));

const copyExamplePayload = async () => {
    try {
        await navigator.clipboard.writeText(exampleJson.value);
        console.log('Example payload copied');
    } catch (err) {
        console.error('Failed to copy example payload:', err);
    }
};

const openIncidents = props.device.incidents.filter(i => i.status === 'open');
const recentIncidents = props.device.incidents.slice(0, 5);
</script>

<template>
    <Head :title="device.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <component :is="getStatusIcon(device.status)"
                              :class="['h-8 w-8', getStatusColor(device.status)]" />
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ props.device.name }}</h1>
                        <p class="text-muted-foreground">
                            {{ props.device.organization.name }} • {{ props.device.ip_address }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Last seen: {{ formatDateTime(props.device.last_seen_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Badge :variant="getStatusBadgeVariant(device.status)" class="capitalize">
                        {{ device.status }}
                    </Badge>
                    <Link :href="`/devices/${props.device.id}/edit`">
                        <Button variant="outline">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Device Details -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Device Information</CardTitle>
                        <CardDescription>
                            Basic device details and configuration
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Name</label>
                            <p class="text-sm">{{ device.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">IP Address</label>
                            <p class="text-sm font-mono">{{ device.ip_address }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Organization</label>
                            <p class="text-sm">
                                <Link :href="`/organizations/${device.organization.id}`" class="text-blue-600 hover:text-blue-800">
                                    {{ device.organization.name }}
                                </Link>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Device Type</label>
                            <p class="text-sm">
                                {{ device.device_type?.name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Status</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <component :is="getStatusIcon(device.status)"
                                          :class="['h-4 w-4', getStatusColor(device.status)]" />
                                <span class="text-sm capitalize">{{ device.status }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created</label>
                            <p class="text-sm">{{ formatDate(device.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Device Secret</CardTitle>
                        <CardDescription>
                            Authentication secret for this device
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <p class="text-sm text-muted-foreground">
                                This secret is used for device authentication and monitoring integration.
                            </p>
                            <div class="flex items-center space-x-2">
                                <code class="flex-1 text-xs bg-muted p-3 rounded font-mono break-all">
                                    {{ device.secret }}
                                </code>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="copySecret(device.secret)"
                                >
                                    <Copy class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground">Webhook URL</label>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 text-xs bg-muted p-3 rounded font-mono break-all">
                                        {{ webhookUrl(device.secret) }}
                                    </code>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="copyWebhook(device.secret)"
                                    >
                                        <LinkIcon class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">POST JSON to this URL to report incidents.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground">Example JSON Payload</label>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="space-y-1">
                                        <label class="text-xs text-muted-foreground">Severity</label>
                                        <select v-model="payload.severity" class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-muted-foreground">Device Status</label>
                                        <select v-model="payload.device_status" class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <option value="online">Online</option>
                                            <option value="offline">Offline</option>
                                            <option value="warning">Warning</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1 md:col-span-2">
                                        <label class="text-xs text-muted-foreground">Title</label>
                                        <input v-model="payload.title" type="text" class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                    </div>
                                    <div class="space-y-1 md:col-span-2">
                                        <label class="text-xs text-muted-foreground">Description</label>
                                        <textarea v-model="payload.description" rows="3" class="w-full rounded-md border border-input bg-background px-2 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <pre class="flex-1 text-xs bg-muted p-3 rounded font-mono overflow-auto"><code>{{ exampleJson }}</code></pre>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="copyExamplePayload"
                                        title="Copy Example JSON"
                                    >
                                        <Copy class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">Adjust fields above, then copy and POST to the webhook.</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Incidents -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Incidents</CardTitle>
                            <CardDescription>
                                Latest incidents for this device
                            </CardDescription>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Badge variant="destructive" v-if="openIncidents.length > 0">
                                {{ openIncidents.length }} open
                            </Badge>
                            <Link href="/incidents" class="text-sm text-blue-600 hover:text-blue-800">
                                View all incidents
                            </Link>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="recentIncidents.length === 0" class="text-center py-8">
                        <Activity class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No incidents</h3>
                        <p class="mt-2 text-muted-foreground">
                            This device has no recorded incidents.
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="incident in recentIncidents" :key="incident.id"
                             class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex-1">
                                <h3 class="font-medium">{{ incident.title }}</h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ formatDateTime(incident.occurred_at) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Badge :class="getSeverityColor(incident.severity)" class="capitalize">
                                    {{ incident.severity }}
                                </Badge>
                                <Badge
                                    :variant="incident.status === 'resolved' ? 'default' :
                                            incident.status === 'acknowledged' ? 'secondary' : 'destructive'"
                                    class="capitalize"
                                >
                                    {{ incident.status }}
                                </Badge>
                                <Link :href="`/incidents/${incident.id}`">
                                    <Button variant="outline" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
