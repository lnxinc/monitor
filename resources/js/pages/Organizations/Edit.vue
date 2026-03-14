<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

interface Props {
    organization: {
        id: number;
        name: string;
        description: string | null;
        created_at: string;
        updated_at: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Organizations',
        href: '/organizations',
    },
    {
        title: props.organization.name,
        href: `/organizations/${props.organization.id}`,
    },
    {
        title: 'Edit',
        href: `/organizations/${props.organization.id}/edit`,
    },
];

const form = useForm({
    name: props.organization.name,
    description: props.organization.description || '',
});
</script>

<template>
    <Head :title="`Edit ${organization.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Organization</h1>
                    <p class="text-muted-foreground">
                        Update organization details
                    </p>
                </div>
                <Link :href="`/organizations/${props.organization.id}`">
                    <Button variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Organization Details</CardTitle>
                    <CardDescription>
                        Update the organization information
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="form.put(`/organizations/${props.organization.id}`)">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="name">Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter organization name"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Enter organization description (optional)"
                                    rows="3"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <div v-if="form.errors.description" class="text-sm text-destructive">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link :href="`/organizations/${props.organization.id}`">
                                    <Button variant="outline" type="button">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Updating...' : 'Update Organization' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>