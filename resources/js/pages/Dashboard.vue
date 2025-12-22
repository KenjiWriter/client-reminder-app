<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';


</script>

<template>
    <Head title="Dashboard" />

    <AppShell>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-6">
            <h1 class="text-2xl font-semibold mb-4">Dashboard</h1>
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppShell>
</template>
