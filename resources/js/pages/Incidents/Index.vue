<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
// import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Activity, AlertTriangle, CheckCircle, Eye, Filter, Clock, Check, Plus, X } from 'lucide-vue-next';
import { ref, watch, computed, reactive } from 'vue';

interface Props {
    incidents: {
        data: Array<{
            id: number;
            title: string;
            description: string | null;
            severity: 'low' | 'medium' | 'high' | 'critical';
            status: 'open' | 'acknowledged' | 'resolved';
            occurred_at: string;
            acknowledged_at: string | null;
            resolved_at: string | null;
            device: {
                id: number;
                name: string;
                organization: {
                    id: number;
                    name: string;
                };
            };
        }>;
        links: any;
        meta: any;
    };
    filters: {
        status?: string;
        severity?: string;
    };
    devices: Array<{
        id: number;
        name: string;
        organization: { id: number; name: string };
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Incidents',
        href: '/incidents',
    },
];

const currentFilters = ref({
    status: props.filters.status || '',
    severity: props.filters.severity || '',
});

const showCreate = ref(false);
const form = reactive({
    device_id: '' as string | number,
    title: '',
    severity: 'low',
    description: '',
    device_status: 'warning',
    occurred_at: '',
});

const resetForm = () => {
    form.device_id = '';
    form.title = '';
    form.severity = 'low';
    form.description = '';
    form.device_status = 'warning';
    form.occurred_at = '';
};

const submit = () => {
    router.post('/incidents', form, {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreate.value = false;
        },
    });
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

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'open':
            return AlertTriangle;
        case 'acknowledged':
            return Clock;
        case 'resolved':
            return CheckCircle;
        default:
            return Activity;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'open':
            return 'text-red-600';
        case 'acknowledged':
            return 'text-yellow-600';
        case 'resolved':
            return 'text-green-600';
        default:
            return 'text-gray-500';
    }
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const acknowledgeIncident = (incidentId: number) => {
    router.patch(`/incidents/${incidentId}/acknowledge`, {}, {
        preserveScroll: true,
    });
};

const resolveIncident = (incidentId: number) => {
    const comment = window.prompt('Add resolution comment (optional):');
    const data: Record<string, any> = {};
    if (comment !== null && comment.trim() !== '') {
        data.resolution_comment = comment.trim();
    }
    router.patch(`/incidents/${incidentId}/resolve`, data, { preserveScroll: true });
};

watch(currentFilters, (newFilters) => {
    const params = new URLSearchParams();
    if (newFilters.status) params.set('status', newFilters.status);
    if (newFilters.severity) params.set('severity', newFilters.severity);

    const url = params.toString() ? `/incidents?${params.toString()}` : '/incidents';
    router.get(url, {}, { preserveState: true, replace: true });
}, { deep: true });

// Support both Laravel paginator shapes: API Resources (meta.last_page)
// and plain paginator (last_page on the root object)
const lastPage = computed(() => {
    // @ts-ignore - runtime may differ
    const metaLast = (props.incidents as any)?.meta?.last_page;
    // @ts-ignore - runtime may differ
    const rootLast = (props.incidents as any)?.last_page;
    return metaLast ?? rootLast ?? 1;
});
</script>

<template>
    <Head title="Incidents" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Incidents</h1>
                    <p class="text-muted-foreground">
                        Monitor and manage device incidents
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="showCreate = !showCreate" variant="default">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Incident
                    </Button>
                </div>
            </div>

            <!-- Create Incident Panel -->
            <Card v-if="showCreate">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Create Incident</CardTitle>
                    <Button variant="outline" size="icon" @click="showCreate = false">
                        <X class="h-4 w-4" />
                    </Button>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium">Device</label>
                            <select v-model="form.device_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="" disabled>Select a device</option>
                                <option v-for="d in props.devices" :key="d.id" :value="d.id">
                                    {{ d.organization.name }} — {{ d.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Title</label>
                            <input v-model="form.title" required type="text"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Severity</label>
                            <select v-model="form.severity" required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium">Description</label>
                            <textarea v-model="form.description" rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Device Status</label>
                            <select v-model="form.device_status" required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                                <option value="warning">Warning</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Occurred At</label>
                            <input v-model="form.occurred_at" type="datetime-local"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                            <p class="text-xs text-muted-foreground">Leave blank to use current time.</p>
                        </div>

                        <div class="md:col-span-2 flex gap-2 justify-end">
                            <Button type="button" variant="outline" @click="resetForm">Reset</Button>
                            <Button type="submit">Create Incident</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Filter class="h-4 w-4 mr-2" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <select
                                v-model="currentFilters.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All statuses</option>
                                <option value="open">Open</option>
                                <option value="acknowledged">Acknowledged</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Severity</label>
                            <select
                                v-model="currentFilters.severity"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All severities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Incidents List -->
            <div v-if="props.incidents.data.length === 0" class="text-center py-12">
                <Activity class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No incidents found</h3>
                <p class="mt-2 text-muted-foreground">
                    No incidents match your current filters.
                </p>
            </div>

            <div v-else class="space-y-4">
                <Card v-for="incident in props.incidents.data" :key="incident.id">
                    <CardContent class="p-6">
                        <div class="flex items-start justify-between space-x-4">
                            <div class="flex items-start space-x-4 flex-1">
                                <component :is="getStatusIcon(incident.status)"
                                          :class="['h-5 w-5 mt-0.5', getStatusColor(incident.status)]" />

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="font-semibold text-lg">{{ incident.title }}</h3>
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
                                    </div>

                                    <p v-if="incident.description" class="text-muted-foreground mb-3">
                                        {{ incident.description }}
                                    </p>

                                    <div class="text-sm text-muted-foreground space-y-1">
                                        <div>
                                            <span class="font-medium">Device:</span>
                                            {{ incident.device.organization.name }} - {{ incident.device.name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Occurred:</span>
                                            {{ formatDateTime(incident.occurred_at) }}
                                        </div>
                                        <div v-if="incident.acknowledged_at">
                                            <span class="font-medium">Acknowledged:</span>
                                            {{ formatDateTime(incident.acknowledged_at) }}
                                        </div>
                                        <div v-if="incident.resolved_at">
                                            <span class="font-medium">Resolved:</span>
                                            {{ formatDateTime(incident.resolved_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Link :href="`/incidents/${incident.id}`">
                                    <Button variant="outline" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>

                                <Button
                                    v-if="incident.status === 'open'"
                                    variant="outline"
                                    size="sm"
                                    @click="acknowledgeIncident(incident.id)"
                                >
                                    <Clock class="h-4 w-4 mr-2" />
                                    Acknowledge
                                </Button>

                                <Button
                                    v-if="incident.status !== 'resolved'"
                                    variant="outline"
                                    size="sm"
                                    @click="resolveIncident(incident.id)"
                                >
                                    <Check class="h-4 w-4 mr-2" />
                                    Resolve
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="lastPage > 1" class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <component
                        v-for="link in props.incidents.links"
                        :key="link.label"
                        :is="link.url ? Link : 'span'"
                        :href="link.url || undefined"
                        :class="[
                            'px-3 py-2 text-sm rounded-md border',
                            link.active
                                ? 'bg-primary text-primary-foreground border-primary'
                                : link.url
                                    ? 'bg-background border-input hover:bg-accent hover:text-accent-foreground'
                                    : 'bg-muted text-muted-foreground border-input cursor-not-allowed'
                        ]"
                    >
                        <span v-html="link.label" />
                    </component>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
