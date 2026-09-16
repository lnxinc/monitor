<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Props {
    device: {
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
    };
    organizations: Array<{
        id: number;
        name: string;
        description: string | null;
    }>;
    deviceTypes: Array<{
        id: number;
        name: string;
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
    {
        title: props.device.name,
        href: `/devices/${props.device.id}`,
    },
    {
        title: 'Edit',
        href: `/devices/${props.device.id}/edit`,
    },
];

const form = useForm({
    organization_id: props.device.organization.id.toString(),
    device_type_id: (props as any).device.device_type_id ? String((props as any).device.device_type_id) : '',
    name: props.device.name,
    ip_address: props.device.ip_address,
    status: props.device.status,
});
</script>

<template>
    <Head :title="`Edit ${device.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Device</h1>
                    <p class="text-muted-foreground">Update device information and settings</p>
                </div>
                <Link :href="`/devices/${props.device.id}`">
                    <Button variant="outline"> Cancel </Button>
                </Link>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Device Details</CardTitle>
                    <CardDescription> Update the device information </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="form.put(`/devices/${props.device.id}`)">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="organization_id">Organization *</Label>
                                <select
                                    id="organization_id"
                                    v-model="form.organization_id"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option v-for="organization in props.organizations" :key="organization.id" :value="organization.id.toString()">
                                        {{ organization.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.organization_id" class="text-sm text-destructive">
                                    {{ form.errors.organization_id }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="device_type_id">Device Type</Label>
                                <select
                                    id="device_type_id"
                                    v-model="form.device_type_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">No type</option>
                                    <option v-for="type in props.deviceTypes" :key="type.id" :value="type.id.toString()">
                                        {{ type.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="name">Device Name *</Label>
                                <Input id="name" v-model="form.name" type="text" placeholder="Enter device name" required />
                                <div v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="ip_address">IP Address *</Label>
                                <Input id="ip_address" v-model="form.ip_address" type="text" placeholder="192.168.1.100" required />
                                <div v-if="form.errors.ip_address" class="text-sm text-destructive">
                                    {{ form.errors.ip_address }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                    <option value="warning">Warning</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <div v-if="form.errors.status" class="text-sm text-destructive">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <div class="rounded-lg bg-muted p-4">
                                <h4 class="mb-2 font-medium">Device Secret</h4>
                                <p class="mb-2 text-sm text-muted-foreground">
                                    Current secret: <code class="rounded bg-background px-2 py-1">{{ props.device.secret }}</code>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    The device secret cannot be changed through this form. Contact your administrator if you need to regenerate the
                                    secret.
                                </p>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link :href="`/devices/${props.device.id}`">
                                    <Button variant="outline" type="button"> Cancel </Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Updating...' : 'Update Device' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
