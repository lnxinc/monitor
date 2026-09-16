<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Opt {
    id: number;
    name: string;
}
interface Window {
    id: number;
    scope: 'global' | 'organization' | 'device' | 'monitor';
    ref_id: number | null;
    start_at: string;
    end_at: string;
    reason: string | null;
}
interface Props {
    organizations: Opt[];
    devices: Opt[];
    monitors: Opt[];
    maintenance: Window;
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Maintenance', href: '/maintenance' },
    { title: 'Edit', href: `/maintenance/${props.maintenance.id}/edit` },
];

const form = useForm({
    scope: props.maintenance.scope,
    ref_id: String(props.maintenance.ref_id || ''),
    start_at: props.maintenance.start_at.slice(0, 16),
    end_at: props.maintenance.end_at.slice(0, 16),
    reason: props.maintenance.reason || '',
});
const submit = () => form.put(`/maintenance/${props.maintenance.id}`);
</script>

<template>
    <Head title="Edit Maintenance" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-6 p-6">
            <Card>
                <CardHeader><CardTitle>Edit Maintenance Window</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label>Scope</Label>
                        <select v-model="form.scope" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="global">Global</option>
                            <option value="organization">Organization</option>
                            <option value="device">Device</option>
                            <option value="monitor">Monitor</option>
                        </select>
                    </div>
                    <div class="space-y-2" v-if="form.scope !== 'global'">
                        <Label>Reference</Label>
                        <select v-model="form.ref_id" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="" disabled>Select reference</option>
                            <option
                                v-for="o in form.scope === 'organization'
                                    ? props.organizations
                                    : form.scope === 'device'
                                      ? props.devices
                                      : props.monitors"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Start</Label>
                            <input v-model="form.start_at" type="datetime-local" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                        <div class="space-y-2">
                            <Label>End</Label>
                            <input v-model="form.end_at" type="datetime-local" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label>Reason (optional)</Label>
                        <input v-model="form.reason" type="text" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Link href="/maintenance"><Button variant="outline">Cancel</Button></Link>
                        <Button @click="submit">Save</Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
