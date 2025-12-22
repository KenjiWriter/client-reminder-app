<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DataPoint {
    date: string;
    count: number;
}

interface Props {
    title: string;
    data: DataPoint[];
    type?: 'line' | 'bar' | 'area';
    color?: string;
    height?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'line',
    color: '#0ea5e9',
    height: 300,
});

const series = computed(() => [{
    name: props.title,
    data: props.data.map(d => d.count),
}]);

const chartOptions = computed(() => ({
    chart: {
        type: props.type,
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
    },
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth' as const,
        width: props.type === 'line' ? 3 : 0,
    },
    colors: [props.color],
    fill: {
        type: props.type === 'area' ? 'gradient' : 'solid',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100, 100, 100]
        }
    },
    xaxis: {
        categories: props.data.map(d => d.date),
        labels: {
            style: { colors: '#94a3b8' },
            rotate: -45,
            hideOverlappingLabels: true,
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8' },
        },
    },
    grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
        padding: { left: 20, right: 20 },
    },
    tooltip: {
        theme: 'light',
        x: { show: true },
        y: { 
            formatter: (val: number) => Math.floor(val).toString()
        }
    },
}));
</script>

<template>
    <Card class="border-sidebar-border/70 shadow-sm">
        <CardHeader class="pb-2">
            <CardTitle class="text-base font-semibold">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="p-0 sm:p-4">
            <VueApexCharts
                :type="type"
                :height="height"
                :options="chartOptions"
                :series="series"
            />
        </CardContent>
    </Card>
</template>
