<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Org {
    id: number;
    name: string;
}
interface Channel {
    id: number;
    name: string;
    type: 'email' | 'slack_webhook';
    is_active: boolean;
    organization_id?: number | null;
    config?: any;
}
interface Props {
    organizations: Org[];
    channel: Channel;
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alert Channels', href: '/channels' },
    { title: 'Edit', href: `/channels/${props.channel.id}/edit` },
];

const form = useForm({
    organization_id: String(props.channel.organization_id || ''),
    name: props.channel.name,
    type: props.channel.type,
    config_email: props.channel.config?.email || '',
    config_webhook_url: props.channel.config?.webhook_url || '',
    is_active: props.channel.is_active,
});
const submit = () => {
    const payload: any = { name: form.name, type: form.type, is_active: form.is_active };
    payload.organization_id = form.organization_id ? Number(form.organization_id) : null;
    payload.config = form.type === 'email' ? { email: form.config_email } : { webhook_url: form.config_webhook_url };
    form.put(`/channels/${props.channel.id}`, { preserveScroll: true, data: payload });
};
</script>

<template>
    <Head title="Edit Channel" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-6 p-6">
            <Card>
                <CardHeader><CardTitle>Edit Alert Channel</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label>Organization (optional)</Label>
                        <select v-model="form.organization_id" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="">All organizations</option>
                            <option v-for="o in props.organizations" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label>Name</Label>
                        <input v-model="form.name" type="text" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" required />
                    </div>
                    <div class="space-y-2">
                        <Label>Type</Label>
                        <select v-model="form.type" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="email">Email</option>
                            <option value="slack_webhook">Slack Webhook</option>
                        </select>
                    </div>
                    <div v-if="form.type === 'email'" class="space-y-2">
                        <Label>Email</Label>
                        <input v-model="form.config_email" type="email" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div v-else class="space-y-2">
                        <Label>Slack Webhook URL</Label>
                        <input v-model="form.config_webhook_url" type="url" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="active" type="checkbox" v-model="form.is_active" />
                        <Label for="active">Active</Label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Link href="/channels"><Button variant="outline">Cancel</Button></Link>
                        <Button @click="submit">Save</Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
