<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Wrench, Plus, Edit, Trash2 } from 'lucide-vue-next';

interface Window { id:number; scope:'global'|'organization'|'device'|'monitor'; ref_id:number|null; start_at:string; end_at:string; reason:string|null }
interface Props { windows: Window[] }
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [ { title:'Dashboard', href:'/dashboard' }, { title:'Maintenance', href:'/maintenance' } ];
const destroyWindow = (id: number) => { if (confirm('Delete this maintenance window?')) router.delete(`/maintenance/${id}`) };
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
        <Link href="/maintenance/create"><Button><Plus class="h-4 w-4 mr-2" /> Add Window</Button></Link>
      </div>

      <div v-if="props.windows.length === 0" class="text-center py-12">
        <Wrench class="mx-auto h-12 w-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-medium">No maintenance windows</h3>
        <p class="mt-2 text-muted-foreground">Create your first window.</p>
      </div>

      <div v-else class="space-y-3">
        <Card v-for="w in props.windows" :key="w.id">
          <CardHeader class="flex items-center justify-between flex-row gap-3">
            <div>
              <CardTitle class="text-lg">{{ w.scope }} {{ w.ref_id ? `#${w.ref_id}` : '' }}</CardTitle>
              <div class="text-xs text-muted-foreground">{{ new Date(w.start_at).toLocaleString() }} → {{ new Date(w.end_at).toLocaleString() }}</div>
              <div class="text-xs text-muted-foreground" v-if="w.reason">{{ w.reason }}</div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <Link :href="`/maintenance/${w.id}/edit`"><Button variant="outline" size="sm"><Edit class="h-4 w-4" /></Button></Link>
              <Button variant="outline" size="sm" class="text-destructive" @click="destroyWindow(w.id)"><Trash2 class="h-4 w-4" /></Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
  </template>

