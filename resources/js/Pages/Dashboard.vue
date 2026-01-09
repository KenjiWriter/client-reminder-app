<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/layouts/AppShell.vue';
import { useTranslation } from '@/composables/useTranslation';
import MetricCard from '@/components/Dashboard/MetricCard.vue';
import DashboardChart from '@/components/Dashboard/DashboardChart.vue';
import SegmentedControl from '@/components/ui/segmented-control/SegmentedControl.vue';
import { Input } from '@/components/ui/input';
import { route } from 'ziggy-js';
import ServiceDistributionChart from '@/components/Dashboard/ServiceDistributionChart.vue';
import { Users, Calendar, UserPlus, MessageSquare, RefreshCw, Filter, XCircle, TrendingUp, Trophy, Activity } from 'lucide-vue-next';

interface DataPoint {
    date: string;
    count: number;
}

interface Props {
    filters: {
        range: string;
        from: string | null;
        to: string | null;
    };
    totals: {
        clients: number;
        appointments: number;
        canceled: number;
    };
    period: {
        new_clients: number;
        appointments: number;
        sms_sent: number;
        rescheduled_appointments: number;
        canceled: number;
    };
    timeseries: {
        clients: DataPoint[];
        appointments: DataPoint[];
        sms: DataPoint[];
        reschedules: DataPoint[];
        canceled: DataPoint[];
        unpaid: DataPoint[];
    };
    analytics: {
        projected_revenue: number;
        service_breakdown: { name: string; count: number }[];
        top_service: string;
        total_visits: number;
    };
}

const props = defineProps<Props>();
const { t } = useTranslation();

const range = ref(props.filters.range);
const fromDate = ref(props.filters.from || '');
const toDate = ref(props.filters.to || '');

const rangeOptions = [
    { value: '7d', label: t('dashboard.filters.7d') },
    { value: '30d', label: t('dashboard.filters.30d') },
    { value: '90d', label: t('dashboard.filters.90d') },
    { value: 'mtd', label: t('dashboard.filters.mtd') },
    { value: 'custom', label: t('dashboard.filters.custom') },
];

const updateFilters = () => {
    router.get(route('dashboard'), {
        range: range.value,
        from: range.value === 'custom' ? fromDate.value : null,
        to: range.value === 'custom' ? toDate.value : null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(range, (newRange) => {
    if (newRange !== 'custom') {
        updateFilters();
    }
});

const getPeriodLabel = () => {
    if (range.value === 'custom') return `${fromDate.value} - ${toDate.value}`;
    const option = rangeOptions.find(o => o.value === range.value);
    return option ? option.label : '';
};
</script>

<template>
    <Head :title="t('nav.dashboard')" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-2xl font-semibold">{{ t('nav.dashboard') }}</h1>
                <div class="flex items-center gap-4">
                    <SegmentedControl
                        v-model="range"
                        :options="rangeOptions"
                    />
                    <div v-if="range === 'custom'" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-300">
                        <Input type="date" v-model="fromDate" class="w-32 h-9" />
                        <span class="text-muted-foreground">-</span>
                        <Input type="date" v-model="toDate" class="w-32 h-9" />
                        <button 
                            @click="updateFilters"
                            class="p-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors h-9 flex items-center justify-center aspect-square"
                        >
                            <Filter class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-6 p-6 overflow-y-auto">
            <!-- Business Analytics Cards -->
            <div class="grid gap-6 md:grid-cols-3">
                <MetricCard
                    :title="t('dashboard.analytics.projectedRevenue')"
                    :value="analytics.projected_revenue"
                    :icon="TrendingUp"
                    variant="primary"
                    :formatter="(val: number) => new Intl.NumberFormat(t('common.locale'), { style: 'currency', currency: 'PLN' }).format(val)" 
                />
                <MetricCard
                    :title="t('dashboard.stats.totalAppointments')"
                    :value="totals.appointments"
                    :icon="Activity"
                    variant="default"
                />
                <MetricCard
                    :title="t('dashboard.analytics.topService')"
                    :value="analytics.top_service"
                    :icon="Trophy"
                    variant="warning"
                    :is-text-value="true"
                />
            </div>

            <!-- KPI Cards -->
            <div class="grid gap-6 md:grid-cols-3 lg:grid-cols-5">
                <MetricCard
                    :title="t('dashboard.stats.totalClients')"
                    :value="totals.clients"
                    :icon="Users"
                    variant="primary"
                />

                <MetricCard
                    :title="t('dashboard.stats.newClients')"
                    :value="period.new_clients"
                    :description="getPeriodLabel()"
                    :icon="UserPlus"
                    variant="success"
                />
                <MetricCard
                    :title="t('dashboard.stats.smsSent')"
                    :value="period.sms_sent"
                    :description="getPeriodLabel()"
                    :icon="MessageSquare"
                    variant="primary"
                />
                <MetricCard
                    :title="t('dashboard.stats.reschedules')"
                    :value="period.rescheduled_appointments"
                    :description="getPeriodLabel()"
                    :icon="RefreshCw"
                    variant="warning"
                />
                <MetricCard
                    :title="t('dashboard.stats.totalCanceled')"
                    :value="totals.canceled"
                    :icon="XCircle"
                    variant="destructive"
                />
                <MetricCard
                    :title="t('dashboard.stats.canceled')"
                    :value="period.canceled"
                    :description="getPeriodLabel()"
                    :icon="XCircle"
                    variant="destructive"
                />
            </div>

            <!-- Charts Section -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                 <!-- Appointments & SMS -->
                 <div class="md:col-span-2 grid gap-6 md:grid-cols-2">
                    <DashboardChart
                        :title="t('dashboard.charts.appointmentsPerDay')"
                        :data="timeseries.appointments"
                        type="area"
                        color="#0ea5e9"
                    />
                    <DashboardChart
                        :title="t('dashboard.charts.smsSentPerDay')"
                        :data="timeseries.sms"
                        type="line"
                        color="#10b981"
                    />
                 </div>

                 <!-- Service Breakdown -->
                 <ServiceDistributionChart
                    :title="t('dashboard.charts.serviceBreakdown')"
                    :data="analytics.service_breakdown"
                 />

                 <!-- Secondary Metrics Row -->
                 <div class="md:col-span-3 grid gap-6 md:grid-cols-3">
                    <DashboardChart
                        :title="t('dashboard.charts.newClientsPerDay')"
                        :data="timeseries.clients"
                        type="bar"
                        color="#6366f1"
                    />
                    <DashboardChart
                        :title="t('dashboard.charts.reschedulesPerDay')"
                        :data="timeseries.reschedules"
                        type="line"
                        color="#f59e0b"
                    />
                    <DashboardChart
                        :title="t('dashboard.charts.unpaidPerDay')"
                        :data="timeseries.unpaid"
                        type="bar"
                        color="#ef4444"
                    />
                 </div>
            </div>
        </div>
    </AppShell>
</template>

