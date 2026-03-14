<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Device Types', href: '/device-types' },
  { title: 'Create', href: '/device-types/create' },
];

import { Server, Database, Cloud, Cpu, Shield, Globe, Camera, Printer, Monitor, HardDrive } from 'lucide-vue-next';

const iconsMap = { Server, Database, Cloud, Cpu, Shield, Globe, Camera, Printer, Monitor, HardDrive } as const;
const iconOptions = Object.keys(iconsMap).map((k) => ({ value: k, label: k }));

const form = useForm({ name: '', description: '', icon: '' as string });
</script>

<template>
  <Head title="Create Device Type" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Device Type</h1>
          <p class="text-muted-foreground">Define a category for devices</p>
        </div>
        <Link href="/device-types">
          <Button variant="outline">Cancel</Button>
        </Link>
      </div>

      <Card class="max-w-xl">
        <CardHeader>
          <CardTitle>Type Details</CardTitle>
          <CardDescription>Provide a name and optional description</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="form.post('/device-types')" class="space-y-6">
            <div class="space-y-2">
              <Label for="name">Name *</Label>
              <Input id="name" v-model="form.name" required />
              <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
            </div>
            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Input id="description" v-model="form.description" />
            </div>
            <div class="space-y-2">
              <Label for="icon">Icon</Label>
              <div class="flex items-center gap-3">
                <select id="icon" v-model="form.icon" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                  <option value="">No icon</option>
                  <option v-for="opt in iconOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <div class="w-10 h-10 border rounded flex items-center justify-center">
                  <component :is="iconsMap[form.icon as keyof typeof iconsMap] || Server" class="h-5 w-5" />
                </div>
              </div>
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
