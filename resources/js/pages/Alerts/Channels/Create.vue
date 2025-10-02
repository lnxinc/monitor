<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

interface Org { id: number; name: string }
interface Props { organizations: Org[] }
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [ { title:'Dashboard', href:'/dashboard' }, { title:'Alert Channels', href:'/channels' }, { title:'Create', href:'/channels/create' } ];

const form = useForm({ organization_id: '', name: '', type: 'email', config_email: '', config_webhook_url: '', is_active: true });
const submit = () => {
  const payload: any = { name: form.name, type: form.type, is_active: form.is_active };
  if (form.organization_id) payload.organization_id = Number(form.organization_id);
  payload.config = form.type === 'email' ? { email: form.config_email } : { webhook_url: form.config_webhook_url };
  form.post('/channels', { preserveScroll: true, data: payload });
};
</script>

<template>
  <Head title="Create Channel" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-2xl">
      <Card>
        <CardHeader><CardTitle>New Alert Channel</CardTitle></CardHeader>
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
          <div v-if="form.type==='email'" class="space-y-2">
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
            <Button @click="submit">Create</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
 </template>

