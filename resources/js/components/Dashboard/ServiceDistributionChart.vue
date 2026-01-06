<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ServiceData {
    name: string;
    count: number;
}

interface Props {
    title: string;
    data: ServiceData[];
    height?: number;
}

const props = withDefaults(defineProps<Props>(), {
    height: 350,
});

const series = computed(() => props.data.map(d => d.count));
const labels = computed(() => props.data.map(d => d.name));

const chartOptions: any = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
        toolbar: { show: false },
    },
    labels: labels.value,
    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '75%',
                labels: {
                    show: true,
                    name: {
                        offsetY: -10,
                    },
                    value: {
                        offsetY: 5,
                        formatter: (val: string) => val,
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: (w: any) => {
                            return w.globals.seriesTotals.reduce((a: any, b: any) => a + b, 0);
                        },
                    },
                },
            },
        },
    },
    legend: {
        position: 'bottom',
        fontFamily: 'inherit',
    },
    stroke: {
        show: false,
    },
    colors: ['#0ea5e9', '#22c55e', '#eab308', '#f97316', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'],
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val: number) => val + ' appointments'
        }
    },
}));
</script>

<template>
    <Card class="border-sidebar-border/70 shadow-sm flex flex-col">
        <CardHeader class="pb-2">
            <CardTitle class="text-base font-semibold">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="flex-1 min-h-0 p-4">
            <div class="h-full w-full flex items-center justify-center">
                <VueApexCharts
                    type="donut"
                    :height="height"
                    :options="chartOptions"
                    :series="series"
                />
            </div>
        </CardContent>
    </Card>
</template>
