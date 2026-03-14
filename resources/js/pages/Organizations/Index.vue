<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Building2, Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    organizations: Array<{
        id: number;
        name: string;
        description: string | null;
        devices_count: number;
        online_count: number;
        offline_count: number;
        warning_count: number;
        critical_count: number;
        created_at: string;
        updated_at: string;
    }>;
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
];

const deleteOrganization = (organizationId: number) => {
    if (confirm('Are you sure you want to delete this organization? This will also delete all associated devices and incidents.')) {
        router.delete(`/organizations/${organizationId}`);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Organizations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Organizations</h1>
                    <p class="text-muted-foreground">
                        Manage organizations and their devices
                    </p>
                </div>
                <Link href="/organizations/create">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        Add Organization
                    </Button>
                </Link>
            </div>

            <div v-if="props.organizations.length === 0" class="text-center py-12">
                <Building2 class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">No organizations</h3>
                <p class="mt-2 text-muted-foreground">
                    Get started by creating your first organization.
                </p>
                <div class="mt-6">
                    <Link href="/organizations/create">
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Organization
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="organization in props.organizations" :key="organization.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ organization.name }}</CardTitle>
                                <CardDescription v-if="organization.description" class="mt-1">
                                    {{ organization.description }}
                                </CardDescription>
                            </div>
                            <Badge variant="secondary">
                                {{ organization.devices_count }} {{ organization.devices_count === 1 ? 'device' : 'devices' }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm text-muted-foreground mb-3">
                            Created {{ formatDate(organization.created_at) }}
                        </div>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <Badge variant="secondary" class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Online: {{ organization.online_count }}
                            </Badge>
                            <Badge variant="secondary" class="bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300">
                                Offline: {{ organization.offline_count }}
                            </Badge>
                            <Badge variant="secondary" class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Warning: {{ organization.warning_count }}
                            </Badge>
                            <Badge variant="secondary" class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                Critical: {{ organization.critical_count }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between space-x-2">
                            <div class="flex space-x-2">
                                <Link :href="`/organizations/${organization.id}`">
                                    <Button variant="outline" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Link :href="`/organizations/${organization.id}/edit`">
                                    <Button variant="outline" size="sm">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="deleteOrganization(organization.id)"
                                class="text-destructive hover:text-destructive"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
