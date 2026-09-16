<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2, Wrench } from 'lucide-vue-next';

interface Window {
    id: number;
    scope: 'global' | 'organization' | 'device' | 'monitor';
    ref_id: number | null;
    start_at: string;
    end_at: string;
    reason: string | null;
}
interface Props {
    windows: Window[];
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Maintenance', href: '/maintenance' },
];
const destroyWindow = (id: number) => {
    if (confirm('Delete this maintenance window?')) router.delete(`/maintenance/${id}`);
};
</script>

<template>
    <Head title="Maintenance Windows" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Maintenance</h1>
                    <p class="text-muted-foreground">Suppress alerts during planned maintenance</p>
                </div>
                <Link href="/maintenance/create"
                    ><Button><Plus class="mr-2 h-4 w-4" /> Add Window</Button></Link
                >
            </div>

            <div v-if="props.windows.length === 0" class="py-12 text-center">
                <Wrench class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No maintenance windows</h3>
                <p class="mt-2 text-muted-foreground">Create your first window.</p>
            </div>

            <div v-else class="space-y-3">
                <Card v-for="w in props.windows" :key="w.id">
                    <CardHeader class="flex flex-row items-center justify-between gap-3">
                        <div>
                            <CardTitle class="text-lg">{{ w.scope }} {{ w.ref_id ? `#${w.ref_id}` : '' }}</CardTitle>
                            <div class="text-xs text-muted-foreground">
                                {{ new Date(w.start_at).toLocaleString() }} → {{ new Date(w.end_at).toLocaleString() }}
                            </div>
                            <div class="text-xs text-muted-foreground" v-if="w.reason">{{ w.reason }}</div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <Link :href="`/maintenance/${w.id}/edit`"
                                ><Button variant="outline" size="sm"><Edit class="h-4 w-4" /></Button
                            ></Link>
                            <Button variant="outline" size="sm" class="text-destructive" @click="destroyWindow(w.id)"
                                ><Trash2 class="h-4 w-4"
                            /></Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
