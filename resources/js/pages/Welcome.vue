<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="flex items-center justify-between px-6 py-4 lg:px-10">
            <div class="flex items-center gap-3">
                <AppLogoIcon class="size-8 text-foreground" />
                <span class="text-xl font-bold tracking-tight">LNX Monitor</span>
            </div>
            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex items-center rounded-md border border-border px-4 py-2 text-sm font-medium hover:bg-accent"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="login()" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium hover:bg-accent"> Log in </Link>
                    <Link
                        :href="register()"
                        class="inline-flex items-center rounded-md border border-border bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main class="flex flex-1 flex-col items-center justify-center px-6">
            <div class="max-w-2xl text-center">
                <AppLogoIcon class="mx-auto mb-8 size-20 text-foreground" />
                <h1 class="mb-4 text-5xl font-extrabold tracking-tight">LNX Monitor</h1>
                <p class="mb-2 text-xl text-muted-foreground">Network Operations & Monitoring Platform</p>
                <p class="mb-10 text-muted-foreground">
                    Monitor devices, track incidents, manage alerts, and maintain full visibility across your infrastructure.
                </p>
                <div class="flex items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Go to Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center rounded-md border border-border px-6 py-3 text-sm font-medium hover:bg-accent"
                        >
                            Create Account
                        </Link>
                    </template>
                </div>
            </div>
        </main>

        <footer class="px-6 py-6 text-center text-sm text-muted-foreground">LNX Monitor Network Operations Platform</footer>
    </div>
</template>
