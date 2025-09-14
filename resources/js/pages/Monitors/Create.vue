<script setup lang="ts">
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

const form = useForm({ name: '', url: '', frequency_minutes: 5 });
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
          <CardDescription>HTTP/SSL checks will run at the chosen interval</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="form.post('/monitors')" class="space-y-6">
            <div class="space-y-2">
              <Label for="name">Name *</Label>
              <input id="name" v-model="form.name" required type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
              <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
            </div>
            <div class="space-y-2">
              <Label for="url">URL *</Label>
              <input id="url" v-model="form.url" required placeholder="https://example.com" type="url" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
              <div v-if="form.errors.url" class="text-sm text-destructive">{{ form.errors.url }}</div>
            </div>
            <div class="space-y-2">
              <Label for="freq">Frequency (minutes) *</Label>
              <input id="freq" v-model.number="form.frequency_minutes" min="1" max="1440" type="number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
              <div v-if="form.errors.frequency_minutes" class="text-sm text-destructive">{{ form.errors.frequency_minutes }}</div>
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
