<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Monitor {
    id: number;
    name: string;
}
interface Channel {
    id: number;
    name: string;
    type: string;
}
interface Props {
    monitors: Monitor[];
    channels: Channel[];
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Policies', href: '/policies' },
    { title: 'Create', href: '/policies/create' },
];

const form = useForm({
    monitor_id: '',
    down_threshold: 1,
    notify_after_seconds: 0,
    repeat_interval_minutes: '',
    notify_on_recovery: true,
    enabled: true,
    channel_ids: [] as number[],
});
const toggleChannel = (id: number, checked: boolean) => {
    if (checked) form.channel_ids.push(id);
    else form.channel_ids = form.channel_ids.filter((i) => i !== id);
};
const submit = () => form.post('/policies');
</script>

<template>
    <Head title="Create Policy" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-6 p-6">
            <Card>
                <CardHeader><CardTitle>New Policy</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label>Monitor</Label>
                        <select v-model="form.monitor_id" class="flex h-10 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="" disabled>Select monitor</option>
                            <option v-for="m in props.monitors" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Down threshold</Label>
                            <input
                                v-model.number="form.down_threshold"
                                type="number"
                                min="1"
                                max="10"
                                class="flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Notify after (s)</Label>
                            <input
                                v-model.number="form.notify_after_seconds"
                                type="number"
                                min="0"
                                max="3600"
                                class="flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Repeat every (min)</Label>
                            <input
                                v-model.number="form.repeat_interval_minutes"
                                type="number"
                                min="1"
                                max="1440"
                                class="flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2"><input type="checkbox" v-model="form.notify_on_recovery" /> Notify on recovery</label>
                        <label class="flex items-center gap-2"><input type="checkbox" v-model="form.enabled" /> Enabled</label>
                    </div>
                    <div class="space-y-2">
                        <Label>Channels</Label>
                        <div class="grid gap-2">
                            <label v-for="c in props.channels" :key="c.id" class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    :value="c.id"
                                    :checked="form.channel_ids.includes(c.id)"
                                    @change="(e: any) => toggleChannel(c.id, e.target.checked)"
                                />
                                {{ c.name }} ({{ c.type }})
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Link href="/policies"><Button variant="outline">Cancel</Button></Link>
                        <Button @click="submit">Create</Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
