<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Bell, Plus, Edit, Trash2 } from 'lucide-vue-next';

interface Policy { id: number; down_threshold: number; notify_on_recovery: boolean; enabled: boolean; monitor: { id:number; name:string }; channels: Array<{ id:number; name:string; type:string }> }
interface Props { policies: Policy[] }
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [ { title:'Dashboard', href:'/dashboard' }, { title:'Policies', href:'/policies' } ];
const destroyPolicy = (id: number) => { if (confirm('Delete this policy?')) router.delete(`/policies/${id}`) };
</script>

<template>
  <Head title="Policies" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Policies</h1>
          <p class="text-muted-foreground">Alert policies attached to monitors</p>
        </div>
        <Link href="/policies/create"><Button><Plus class="h-4 w-4 mr-2" /> Add Policy</Button></Link>
      </div>

      <div v-if="props.policies.length === 0" class="text-center py-12">
        <Bell class="mx-auto h-12 w-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-medium">No policies</h3>
        <p class="mt-2 text-muted-foreground">Create your first policy.</p>
      </div>

      <div v-else class="space-y-3">
        <Card v-for="p in props.policies" :key="p.id">
          <CardHeader class="flex items-center justify-between flex-row gap-3">
            <div>
              <CardTitle class="text-lg">{{ p.monitor.name }}</CardTitle>
              <div class="text-xs text-muted-foreground">Threshold: {{ p.down_threshold }} • {{ p.notify_on_recovery ? 'Notify on recovery' : 'No recovery notification' }}</div>
              <div class="text-xs text-muted-foreground">Channels: {{ p.channels.map(c=>c.name).join(', ') || '—' }}</div>
            </div>
            <span :class="['text-xs', p.enabled ? 'text-green-600' : 'text-gray-500']">{{ p.enabled ? 'Enabled' : 'Disabled' }}</span>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <Link :href="`/policies/${p.id}/edit`"><Button variant="outline" size="sm"><Edit class="h-4 w-4" /></Button></Link>
              <Button variant="outline" size="sm" class="text-destructive" @click="destroyPolicy(p.id)"><Trash2 class="h-4 w-4" /></Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
  </template>

