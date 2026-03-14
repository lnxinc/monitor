<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { ServerCog, Edit, Plus, Trash2, Server, Database, Cloud, Cpu, Shield, Globe, Camera, Printer, Monitor, HardDrive } from 'lucide-vue-next';

interface Props {
  types: Array<{ id: number; name: string; description: string | null; devices_count: number; icon?: string | null }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Device Types', href: '/device-types' },
];

const destroyType = (id: number) => {
  if (confirm('Delete this device type? Devices will keep a null type.')) {
    router.delete(`/device-types/${id}`);
  }
};

const iconsMap = { Server, Database, Cloud, Cpu, Shield, Globe, Camera, Printer, Monitor, HardDrive } as const;
</script>

<template>
  <Head title="Device Types" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Device Types</h1>
          <p class="text-muted-foreground">Manage categories of devices</p>
        </div>
        <Link href="/device-types/create">
          <Button><Plus class="h-4 w-4 mr-2" /> Add Type</Button>
        </Link>
      </div>

      <div v-if="props.types.length === 0" class="text-center py-12">
        <ServerCog class="mx-auto h-12 w-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-medium">No device types</h3>
        <p class="mt-2 text-muted-foreground">Create your first device type.</p>
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="t in props.types" :key="t.id">
          <CardHeader class="flex items-center justify-between flex-row gap-3">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 border rounded flex items-center justify-center">
                <component :is="iconsMap[(t.icon as keyof typeof iconsMap) || 'Server']" class="h-4 w-4" />
              </div>
              <CardTitle class="text-lg">{{ t.name }}</CardTitle>
            </div>
            <span class="text-xs text-muted-foreground">{{ t.devices_count }} devices</span>
          </CardHeader>
          <CardContent>
            <p v-if="t.description" class="text-sm text-muted-foreground mb-4">{{ t.description }}</p>
            <div class="flex items-center justify-between">
              <div class="flex gap-2">
                <Link :href="`/device-types/${t.id}/edit`">
                  <Button variant="outline" size="sm"><Edit class="h-4 w-4" /></Button>
                </Link>
              </div>
              <Button variant="outline" size="sm" class="text-destructive" @click="destroyType(t.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
