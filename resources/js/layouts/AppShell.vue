<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Home, Calendar, Users, Settings, Mail, Globe, ClipboardCheck } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from '@/composables/useTranslation';

interface User {
    name: string;
    email: string;
}

interface PageProps {
    auth: {
        user: User;
    };
    name: string;
    quote: { message: string; author: string };
    locale: string;
    locales: string[];
    translations: Record<string, string>;
    sidebarOpen: boolean;
    pendingApprovalsCount: number;
}

interface NavItem {
    name: string;
    href: string;
    icon: any;
    badge?: number;
}

const { t } = useTranslation();

const navItems = computed<NavItem[]>(() => [
    { name: 'nav.dashboard', href: route('dashboard'), icon: Home },
    { 
        name: 'nav.review', 
        href: route('admin.appointments.review.index'), 
        icon: ClipboardCheck, 
        badge: (page.props as any).pendingApprovalsCount 
    },
    { name: 'nav.clients', href: route('clients.index'), icon: Users },
    { name: 'nav.messages', href: route('messages.index'), icon: Mail },
    { name: 'nav.calendar', href: route('calendar.index'), icon: Calendar },
    { name: 'nav.settings', href: route('settings.index'), icon: Settings },
]);

const page = usePage<PageProps>();
const currentUrl = computed(() => page.url);

const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => {
    if (!user.value?.name) return '?';
    const names = user.value.name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[names.length - 1][0]).toUpperCase();
    }
    return user.value.name.substring(0, 2).toUpperCase();
});
const firstName = computed(() => user.value?.name.split(' ')[0] || 'User');

const isActive = (href: string) => {
    if (href === '#') return false;
    return currentUrl.value.startsWith(href);
};

const switchLocale = (locale: string) => {
    router.post(route('locale.switch'), { locale }, {
        preserveState: false,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background">
        <!-- Sidebar -->
        <aside class="hidden w-64 flex-col border-r border-border bg-card lg:flex">
            <!-- Logo -->
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
                        <Calendar class="h-6 w-6 text-primary-foreground" />
                    </div>
                    <span class="text-lg font-semibold">App 👋</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                            : 'text-sidebar-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground',
                    ]"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    <span>{{ t(item.name) }}</span>
                    <span v-if="item.badge && item.badge > 0" class="ml-auto flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground font-bold">
                        {{ item.badge }}
                    </span>
                </Link>
            </nav>

            <!-- User section (optional) -->
            <div class="border-t border-border p-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="text-sm font-medium text-primary">{{ userInitials }}</span>
                    </div>
                    <div class="flex-1 text-sm">
                        <div class="font-medium">{{ user?.name || 'User' }}</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="flex h-16 items-center justify-between border-b border-border bg-card px-6">
                <div class="flex items-center gap-4">
                    <slot name="header-title">
                        <h1 class="text-2xl font-semibold">{{ $page.props.title || 'Dashboard' }}</h1>
                    </slot>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Language Switcher -->
                    <Select :model-value="page.props.locale" @update:model-value="switchLocale">
                        <SelectTrigger class="w-[80px] h-8">
                            <Globe class="mr-1 h-3.5 w-3.5" />
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pl">PL</SelectItem>
                            <SelectItem value="en">EN</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <span class="text-sm text-muted-foreground">Hi, {{ firstName }} 👋</span>
                    <div class="relative h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="text-sm font-medium text-primary">{{ userInitials }}</span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
