<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { AlertTriangle, CheckCircle, Cloud } from 'lucide-vue-next';

interface Props {
  hasApiKey: boolean;
  baseUrl: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'UniFi Site Manager', href: '/integrations/unifi' },
];

const syncNow = () => {
  router.post('/integrations/unifi/sync', {}, { preserveScroll: true });
};
</script>

<template>
  <Head title="UniFi Site Manager" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="grid gap-6">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Cloud class="h-5 w-5" />
            UniFi Site Manager
          </CardTitle>
          <CardDescription>
            Sync customer sites from UniFi Site Manager into your monitoring database and raise incidents for offline or degraded sites.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <component :is="props.hasApiKey ? CheckCircle : AlertTriangle" :class="props.hasApiKey ? 'text-green-600' : 'text-red-600'" />
              <div>
                <div class="font-medium">
                  {{ props.hasApiKey ? 'API key configured' : 'Missing API key' }}
                </div>
                <div class="text-sm text-muted-foreground">
                  Base URL: {{ props.baseUrl }}
                </div>
              </div>
            </div>
            <div>
              <Button :disabled="!props.hasApiKey" @click="syncNow">Sync Now</Button>
            </div>
          </div>
          <p class="text-sm text-muted-foreground mt-4">
            This action pulls sites from UniFi and creates/updates organizations and devices. If a site is offline or has internet issues, an open incident is created when none exists.
          </p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
  
</template>

