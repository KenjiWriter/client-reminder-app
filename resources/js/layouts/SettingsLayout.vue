<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue'; // Direct import from Layouts
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { User, MessageSquare, Monitor, LogOut, Settings, Calendar } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import Heading from '@/components/Heading.vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();
const page = usePage();
const currentPath = computed(() => page.url);

const navItems = computed(() => [
    {
        name: 'settings.nav.general',
        href: '/settings/general',
        icon: Settings,
    },
    {
        name: 'settings.nav.account',
        href: '/settings/account',
        icon: User,
    },
    {
        name: 'settings.nav.sms',
        href: '/settings/sms',
        icon: MessageSquare,
    },
    {
        name: 'settings.nav.medical',
        href: '/settings/medical',
        icon: Monitor,
    },
    {
        name: 'settings.nav.services',
        href: '/settings/services',
        icon: Monitor,
    },
    {
        name: 'settings.nav.appearance',
        href: '/settings/appearance',
        icon: Monitor,
    },
    {
        name: 'settings.nav.integrations',
        href: '/settings/integrations',
        icon: Calendar,
    },
]);

const isActive = (href: string) => {
    return currentPath.value.startsWith(href);
};
</script>

<template>
    <AppShell>
         <template #header-title>
            <h1 class="text-xl lg:text-2xl font-semibold">{{ t('settings.title') }}</h1>
        </template>

        <div class="px-4 py-6 md:px-8">
            <Heading
                :title="t('settings.title')"
                :description="t('settings.description')"
                class="mb-8"
            />

            <div class="flex flex-col lg:flex-row lg:space-x-12">
                <!-- Sidebar -->
                <aside class="w-full lg:w-64 shrink-0 mb-8 lg:mb-0">
                    <nav class="flex flex-row flex-wrap gap-2 lg:flex-col lg:space-y-1 lg:gap-0 pb-4 lg:pb-0">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap',
                                isActive(item.href)
                                    ? 'bg-secondary text-secondary-foreground'
                                    : 'text-muted-foreground hover:bg-secondary/50 hover:text-secondary-foreground',
                            ]"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ t(item.name) }}
                        </Link>
                    </nav>

                    <Separator class="my-6" />

                    <div class="px-3">
                         <Link
                            method="post" 
                            as="button" 
                            href="/logout" 
                            class="flex w-full items-center gap-3 rounded-lg px-0 py-2 text-sm font-medium text-red-500 hover:text-red-600 transition-colors"
                        >
                            <LogOut class="h-4 w-4" />
                            {{ t('settings.nav.logout') }}
                        </Link>
                    </div>
                </aside>

                <!-- Content -->
                <div class="flex-1 max-w-2xl">
                    <slot />
                </div>
            </div>
        </div>
    </AppShell>
</template>

