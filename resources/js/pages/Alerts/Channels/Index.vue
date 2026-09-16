<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, Edit, Plus, Trash2 } from 'lucide-vue-next';

interface Channel {
    id: number;
    name: string;
    type: 'email' | 'slack_webhook';
    is_active: boolean;
    organization?: { id: number; name: string } | null;
}
interface Props {
    channels: Channel[];
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alert Channels', href: '/channels' },
];
const destroyChannel = (id: number) => {
    if (confirm('Delete this channel?')) router.delete(`/channels/${id}`);
};
const typeLabel = (t: string) => (t === 'email' ? 'Email' : 'Slack Webhook');
</script>

<template>
    <Head title="Alert Channels" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Alert Channels</h1>
                    <p class="text-muted-foreground">Destinations for alert notifications</p>
                </div>
                <Link href="/channels/create"
                    ><Button><Plus class="mr-2 h-4 w-4" /> Add Channel</Button></Link
                >
            </div>

            <div v-if="props.channels.length === 0" class="py-12 text-center">
                <Bell class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No channels</h3>
                <p class="mt-2 text-muted-foreground">Create your first alert channel.</p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="c in props.channels" :key="c.id">
                    <CardHeader class="flex flex-row items-center justify-between gap-3">
                        <div>
                            <CardTitle class="text-lg">{{ c.name }}</CardTitle>
                            <div class="text-xs text-muted-foreground">{{ typeLabel(c.type) }} • {{ c.organization?.name || 'All orgs' }}</div>
                        </div>
                        <span :class="['text-xs', c.is_active ? 'text-green-600' : 'text-gray-500']">{{ c.is_active ? 'Active' : 'Disabled' }}</span>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <Link :href="`/channels/${c.id}/edit`"
                                ><Button variant="outline" size="sm"><Edit class="h-4 w-4" /></Button
                            ></Link>
                            <Button variant="outline" size="sm" class="text-destructive" @click="destroyChannel(c.id)"
                                ><Trash2 class="h-4 w-4"
                            /></Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
