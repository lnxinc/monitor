<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Building2, Server, Activity, Cloud, Bell, Wrench } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Organizations',
        href: '/organizations',
        icon: Building2,
    },
    {
        title: 'Devices',
        href: '/devices',
        icon: Server,
    },
    {
        title: 'Device Types',
        href: '/device-types',
        icon: Server,
    },
    {
        title: 'Incidents',
        href: '/incidents',
        icon: Activity,
    },
    {
        title: 'Monitors',
        href: '/monitors',
        icon: Activity,
    },
    {
        title: 'Alert Channels',
        href: '/channels',
        icon: Bell,
    },
    {
        title: 'Policies',
        href: '/policies',
        icon: Bell,
    },
    {
        title: 'Maintenance',
        href: '/maintenance',
        icon: Wrench,
    },
    {
        title: 'UniFi Sites',
        href: '/integrations/unifi',
        icon: Cloud,
    },
];


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
