<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/layouts/AppShell.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DollarSign, CheckCircle2 } from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';
import { route } from 'ziggy-js';

interface UnpaidAppointment {
    id: number;
    client_name: string;
    service_name: string;
    date: string;
    price: number | string; // Decimal comes as string often
    duration_minutes: number;
}

const props = defineProps<{
    unpaidAppointments: UnpaidAppointment[];
    totalOutstanding: number | string;
}>();

const { t } = useTranslation();

const markAsPaid = (id: number) => {
    if (!confirm(t('financial.confirmMarkPaid') || 'Mark this appointment as paid (Cash, Now)?')) return;
    
    router.patch(route('appointments.update-payment', id), {
        is_paid: true,
        payment_method: 'cash',
        payment_date: new Date().toISOString().slice(0, 19).replace('T', ' '), // simple format
        // We let backend handle the rest or minimal payload
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // refresh
        }
    });
};
</script>

<template>
    <Head title="Financial Overview" />

    <AppShell>
        <div class="space-y-6 pt-6 px-4 sm:px-6 w-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">{{ t('financial.title') }}</h2>
                    <p class="text-muted-foreground mt-1">{{ t('financial.subtitle') }}</p>
                </div>
                
                <Card class="w-full md:w-auto min-w-[250px] border-red-200 bg-red-50/50 shadow-sm shrink-0">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-red-700/80">{{ t('financial.totalOutstanding') }}</CardDescription>
                        <CardTitle class="text-2xl text-red-600 flex items-center gap-2 whitespace-nowrap">
                            <DollarSign class="h-6 w-6" />
                            {{ Number(totalOutstanding).toFixed(2) }} PLN
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('financial.unpaidAppointments') }}</CardTitle>
                    <CardDescription>{{ t('financial.unpaidDescription') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('financial.client') }}</TableHead>
                                <TableHead>{{ t('financial.date') }}</TableHead>
                                <TableHead>{{ t('financial.service') }}</TableHead>
                                <TableHead class="text-right">{{ t('financial.amount') }}</TableHead>
                                <TableHead class="text-right">{{ t('financial.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="unpaidAppointments.length === 0">
                                <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                                    {{ t('financial.noUnpaid') }}
                                </TableCell>
                            </TableRow>
                            
                            <TableRow v-for="apt in unpaidAppointments" :key="apt.id">
                                <TableCell class="font-medium">{{ apt.client_name }}</TableCell>
                                <TableCell>{{ apt.date }}</TableCell>
                                <TableCell>{{ apt.service_name }}</TableCell>
                                <TableCell class="text-right font-bold">{{ Number(apt.price).toFixed(2) }} PLN</TableCell>
                                <TableCell class="text-right">
                                    <Button size="sm" variant="outline" class="gap-1 text-green-600 hover:text-green-700 hover:bg-green-50 border-green-200" @click="markAsPaid(apt.id)">
                                        <CheckCircle2 class="h-3 w-3" />
                                        {{ t('financial.markPaid') }}
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
