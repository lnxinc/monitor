<script setup lang="ts">
import { computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Monitors', href: '/monitors' },
  { title: 'Create', href: '/monitors/create' },
];

const typeOptions = [
  { value: 'http', label: 'HTTP/HTTPS' },
  { value: 'ping', label: 'Ping (ICMP)' },
  { value: 'tcp', label: 'TCP Port' },
  { value: 'ssl', label: 'SSL Certificate' },
  { value: 'sip', label: 'SIP Phone' },
  { value: 'webhook', label: 'Webhook' },
];

const form = useForm({
  name: '',
  type: 'http',
  url: '',
  frequency_minutes: 5,
  config: { method: 'HEAD', timeout: 15 },
});

const targetLabel = computed(() => {
  switch (form.type) {
    case 'ping':
      return 'Host or IP *';
    case 'tcp':
      return 'Host *';
    case 'ssl':
      return 'Host *';
    case 'sip':
      return 'SIP Server Host *';
    case 'webhook':
      return 'Identifier *';
    default:
      return 'URL *';
  }
});

const targetPlaceholder = computed(() => {
  switch (form.type) {
    case 'ping':
      return '10.0.0.5 or server.example.com';
    case 'tcp':
      return 'server.example.com';
    case 'ssl':
      return 'secure.example.com';
    case 'sip':
      return 'sip.example.com';
    case 'webhook':
      return 'spectrum-edge-west';
    default:
      return 'https://example.com';
  }
});

const showFrequency = computed(() => form.type !== 'webhook');

watch(
  () => form.type,
  (value) => {
    if (value === 'http' && !('method' in form.config)) {
      form.config = { method: 'HEAD', timeout: 15 };
    }
    if (value === 'ping') {
      form.config = { ...form.config, timeout: form.config.timeout ?? 5, count: form.config.count ?? 4 };
    }
    if (value === 'tcp') {
      form.config = { ...form.config, timeout: form.config.timeout ?? 10, port: form.config.port ?? 443 };
    }
    if (value === 'ssl') {
      form.config = { ...form.config, port: form.config.port ?? 443 };
    }
    if (value === 'sip') {
      form.config = { ...form.config, timeout: form.config.timeout ?? 10, port: form.config.port ?? 5060, phone_number: form.config.phone_number ?? '' };
    }
    if (value === 'webhook') {
      form.config = {};
    }
  },
  { immediate: true }
);
</script>

<template>
  <Head title="Create Monitor" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Monitor</h1>
          <p class="text-muted-foreground">Add a URL to monitor and a check frequency</p>
        </div>
        <Link href="/monitors"><Button variant="outline">Cancel</Button></Link>
      </div>

      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>Monitor Details</CardTitle>
          <CardDescription>Choose the type of probe and sampling interval</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="form.post('/monitors')" class="space-y-6">
            <div class="space-y-2">
              <Label for="name">Name *</Label>
              <input id="name" v-model="form.name" required type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
              <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
            </div>
            <div class="space-y-2">
              <Label for="type">Type *</Label>
              <select id="type" v-model="form.type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
              <div v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</div>
            </div>
            <div class="space-y-2">
              <Label for="target">{{ targetLabel }}</Label>
              <input
                id="target"
                v-model="form.url"
                :required="true"
                :placeholder="targetPlaceholder"
                :type="form.type === 'http' ? 'url' : 'text'"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              />
              <div v-if="form.errors.url" class="text-sm text-destructive">{{ form.errors.url }}</div>
            </div>
            <div v-if="showFrequency" class="space-y-2">
              <Label for="freq">Frequency (minutes) *</Label>
              <input id="freq" v-model.number="form.frequency_minutes" min="1" max="1440" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
              <div v-if="form.errors.frequency_minutes" class="text-sm text-destructive">{{ form.errors.frequency_minutes }}</div>
            </div>

            <div v-if="form.type === 'http'" class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="method">Method</Label>
                <select id="method" v-model="form.config.method" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                  <option value="HEAD">HEAD</option>
                  <option value="GET">GET</option>
                </select>
                <div v-if="form.errors['config.method']" class="text-sm text-destructive">{{ form.errors['config.method'] }}</div>
              </div>
              <div class="space-y-2">
                <Label for="expected">Expected status</Label>
                <input id="expected" v-model.number="form.config.expected_status" min="100" max="599" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.expected_status']" class="text-sm text-destructive">{{ form.errors['config.expected_status'] }}</div>
              </div>
              <div class="space-y-2">
                <Label for="timeout">Timeout (seconds)</Label>
                <input id="timeout" v-model.number="form.config.timeout" min="1" max="120" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.timeout']" class="text-sm text-destructive">{{ form.errors['config.timeout'] }}</div>
              </div>
            </div>

            <div v-if="form.type === 'ping'" class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="ping-count">Packet count</Label>
                <input id="ping-count" v-model.number="form.config.count" min="1" max="10" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.count']" class="text-sm text-destructive">{{ form.errors['config.count'] }}</div>
              </div>
              <div class="space-y-2">
                <Label for="ping-timeout">Timeout (seconds)</Label>
                <input id="ping-timeout" v-model.number="form.config.timeout" min="1" max="60" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.timeout']" class="text-sm text-destructive">{{ form.errors['config.timeout'] }}</div>
              </div>
            </div>

            <div v-if="form.type === 'tcp'" class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="tcp-port">Port *</Label>
                <input id="tcp-port" v-model.number="form.config.port" min="1" max="65535" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.port']" class="text-sm text-destructive">{{ form.errors['config.port'] }}</div>
              </div>
              <div class="space-y-2">
                <Label for="tcp-timeout">Timeout (seconds)</Label>
                <input id="tcp-timeout" v-model.number="form.config.timeout" min="1" max="120" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.timeout']" class="text-sm text-destructive">{{ form.errors['config.timeout'] }}</div>
              </div>
            </div>

            <div v-if="form.type === 'ssl'" class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="ssl-port">Port</Label>
                <input id="ssl-port" v-model.number="form.config.port" min="1" max="65535" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.port']" class="text-sm text-destructive">{{ form.errors['config.port'] }}</div>
              </div>
            </div>

            <div v-if="form.type === 'sip'" class="space-y-4">
              <div class="space-y-2">
                <Label for="sip-phone">Phone Number *</Label>
                <input id="sip-phone" v-model="form.config.phone_number" required type="text" placeholder="+1234567890 or 1000" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                <div v-if="form.errors['config.phone_number']" class="text-sm text-destructive">{{ form.errors['config.phone_number'] }}</div>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="sip-port">Port</Label>
                  <input id="sip-port" v-model.number="form.config.port" min="1" max="65535" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                  <div v-if="form.errors['config.port']" class="text-sm text-destructive">{{ form.errors['config.port'] }}</div>
                </div>
                <div class="space-y-2">
                  <Label for="sip-timeout">Timeout (seconds)</Label>
                  <input id="sip-timeout" v-model.number="form.config.timeout" min="1" max="120" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                  <div v-if="form.errors['config.timeout']" class="text-sm text-destructive">{{ form.errors['config.timeout'] }}</div>
                </div>
              </div>
            </div>

            <div v-if="form.type === 'webhook'" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
              The webhook token will be generated after saving. Use this monitor when an external service pushes health events to the platform.
            </div>

            <div class="flex justify-end gap-2">
              <Button type="submit" :disabled="form.processing">Create</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
