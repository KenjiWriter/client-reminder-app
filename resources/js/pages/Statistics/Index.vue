<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '@/layouts/AppShell.vue';
import { useTranslation } from '@/composables/useTranslation';
import MetricCard from '@/components/Dashboard/MetricCard.vue';
import { Users, Eye, Clock, UserCheck, UserX } from 'lucide-vue-next';

const { t } = useTranslation();

interface Client {
    id: number;
    name: string;
    email: string;
    visit_count?: number;
    last_visit?: string;
}

interface Props {
    totalVisits: number;
    uniqueVisitors: number;
    activeClients: Client[];
    inactiveClients: Client[];
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="t('stats.title')" />

    <AppShell>
        <template #header-title>
             <h1 class="text-2xl font-semibold">{{ t('stats.title') }}</h1>
        </template>

        <div class="flex h-full flex-1 flex-col gap-6 p-6 overflow-y-auto">
            <!-- KPI Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <MetricCard
                    :title="t('stats.totalVisits')"
                    :value="totalVisits"
                    :icon="Eye"
                    variant="primary"
                />
                <MetricCard
                    :title="t('stats.uniqueVisitors')"
                    :value="uniqueVisitors"
                    :icon="Users"
                    variant="default"
                />
                 <MetricCard
                    :title="t('stats.activeClients')"
                    :value="activeClients.length"
                    :icon="UserCheck"
                    variant="success"
                />
                 <MetricCard
                    :title="t('stats.inactiveClients')"
                    :value="inactiveClients.length"
                    :icon="UserX"
                    variant="destructive"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Active Clients Table -->
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pb-2">
                        <h3 class="text-lg font-semibold leading-none tracking-tight flex items-center gap-2">
                            <UserCheck class="h-4 w-4" /> {{ t('stats.activeClients') }}
                        </h3>
                        <p class="text-sm text-muted-foreground mt-2">{{ t('stats.activeClientsDesc') }}</p>
                    </div>
                    <div class="p-0">
                         <div class="relative w-full overflow-auto">
                            <table class="w-full caption-bottom text-sm">
                                <thead class="[&_tr]:border-b">
                                    <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">{{ t('stats.name') }}</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">{{ t('stats.email') }}</th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">{{ t('stats.visits') }}</th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">{{ t('stats.lastSeen') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="[&_tr:last-child]:border-0">
                                    <tr v-for="client in activeClients" :key="client.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                        <td class="p-4 align-middle font-medium">{{ client.name }}</td>
                                        <td class="p-4 align-middle">{{ client.email }}</td>
                                        <td class="p-4 align-middle text-right">{{ client.visit_count }}</td>
                                        <td class="p-4 align-middle text-right text-muted-foreground">{{ client.last_visit }}</td>
                                    </tr>
                                    <tr v-if="activeClients.length === 0">
                                        <td colspan="4" class="p-4 text-center text-muted-foreground">{{ t('stats.noActive') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Inactive Clients List -->
                 <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pb-2">
                        <h3 class="text-lg font-semibold leading-none tracking-tight flex items-center gap-2">
                            <UserX class="h-4 w-4" /> {{ t('stats.inactiveClients') }}
                        </h3>
                        <p class="text-sm text-muted-foreground mt-2">{{ t('stats.inactiveClientsDesc') }}</p>
                    </div>
                    <div class="p-0">
                         <div class="relative w-full overflow-auto max-h-[500px]">
                            <table class="w-full caption-bottom text-sm">
                                <thead class="[&_tr]:border-b">
                                    <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">{{ t('stats.name') }}</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">{{ t('stats.email') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="[&_tr:last-child]:border-0">
                                    <tr v-for="client in inactiveClients" :key="client.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                        <td class="p-4 align-middle font-medium">{{ client.name }}</td>
                                        <td class="p-4 align-middle">{{ client.email }}</td>
                                    </tr>
                                     <tr v-if="inactiveClients.length === 0">
                                        <td colspan="2" class="p-4 text-center text-muted-foreground">{{ t('stats.allActive') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
