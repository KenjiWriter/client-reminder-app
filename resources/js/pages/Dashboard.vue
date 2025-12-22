<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { route } from 'ziggy-js';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const { t } = useTranslation();
</script>

<template>
    <Head :title="t('nav.dashboard')" />

    <AppShell>
        <template #header-title>
            <h1 class="text-2xl font-semibold">{{ t('nav.dashboard') }}</h1>
        </template>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-6">
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
