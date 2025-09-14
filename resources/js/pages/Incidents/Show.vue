<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { AlertTriangle, CheckCircle, Activity, Clock, ArrowLeft } from 'lucide-vue-next';

interface Props {
    incident: {
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
            organization: { id: number; name: string };
        };
        metadata?: Record<string, unknown> | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Incidents', href: '/incidents' },
    { title: props.incident.title, href: `/incidents/${props.incident.id}` },
];

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

const formatDateTime = (dateString: string) => new Date(dateString).toLocaleString();
</script>

<template>
    <Head :title="props.incident.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/incidents" class="text-muted-foreground hover:text-foreground inline-flex items-center">
                        <ArrowLeft class="h-4 w-4 mr-1" /> Back
                    </Link>
                    <h1 class="text-3xl font-bold tracking-tight">{{ props.incident.title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :class="getSeverityColor(props.incident.severity)" class="capitalize">
                        {{ props.incident.severity }}
                    </Badge>
                    <Badge class="capitalize">
                        {{ props.incident.status }}
                    </Badge>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <component :is="getStatusIcon(props.incident.status)" class="h-5 w-5" />
                        Details
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="text-sm text-muted-foreground">
                        <div><span class="font-medium">Organization:</span> {{ props.incident.device.organization.name }}</div>
                        <div><span class="font-medium">Device:</span> {{ props.incident.device.name }}</div>
                        <div><span class="font-medium">Occurred:</span> {{ formatDateTime(props.incident.occurred_at) }}</div>
                        <div v-if="props.incident.acknowledged_at"><span class="font-medium">Acknowledged:</span> {{ formatDateTime(props.incident.acknowledged_at) }}</div>
                        <div v-if="props.incident.resolved_at"><span class="font-medium">Resolved:</span> {{ formatDateTime(props.incident.resolved_at) }}</div>
                        <div v-if="(props as any).incident.resolution_comment">
                            <span class="font-medium">Resolution Comment:</span>
                            <span class="whitespace-pre-wrap text-foreground">{{ (props as any).incident.resolution_comment }}</span>
                        </div>
                    </div>

                    <div v-if="props.incident.description">
                        <h3 class="font-medium mb-1">Description</h3>
                        <p class="text-sm text-foreground">{{ props.incident.description }}</p>
                    </div>

                    <div v-if="props.incident.metadata">
                        <h3 class="font-medium mb-1">Metadata</h3>
                        <pre class="text-xs bg-muted rounded p-3 overflow-auto">{{ JSON.stringify(props.incident.metadata, null, 2) }}</pre>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
