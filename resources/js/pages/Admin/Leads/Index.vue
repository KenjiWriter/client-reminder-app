<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';

interface Lead {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
    source: string;
    status: string;
    note: string | null;
    created_at: string;
}

interface LeadsData {
    data: Lead[];
    links: any[];
}

const props = defineProps<{
    leads: LeadsData;
}>();

const { t, locale } = useTranslation();

const updateStatus = (lead: Lead, newStatus: string) => {
    router.put(route('admin.leads.update', lead.id), {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat(locale, { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'new': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'contacted': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'closed': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head :title="t('leads.title')" />

    <AppShell>
        <template #header-title>
            <h1 class="text-2xl font-semibold">{{ t('leads.title') }}</h1>
        </template>

        <div class="h-full flex-1 flex-col gap-4 p-4">
            <div class="rounded-md border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('clients.fullName') }}</TableHead>
                            <TableHead>{{ t('clients.phone') }}</TableHead>
                            <TableHead>{{ t('leads.createdAt') }}</TableHead>
                            <TableHead>{{ t('leads.source') }}</TableHead>
                            <TableHead>{{ t('leads.status') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="lead in leads.data" :key="lead.id">
                            <TableCell class="font-medium">
                                <div class="flex flex-col">
                                    <span>{{ lead.full_name }}</span>
                                    <span v-if="lead.email" class="text-xs text-muted-foreground">{{ lead.email }}</span>
                                </div>
                            </TableCell>
                            <TableCell>{{ lead.phone_e164 }}</TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatDate(lead.created_at) }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" class="uppercase text-[10px]">{{ lead.source }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Select :model-value="lead.status" @update:model-value="(val) => updateStatus(lead, val)">
                                    <SelectTrigger class="h-7 w-[130px] text-xs" :class="getStatusColor(lead.status)">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="new">{{ t('leads.statuses.new') }}</SelectItem>
                                        <SelectItem value="contacted">{{ t('leads.statuses.contacted') }}</SelectItem>
                                        <SelectItem value="closed">{{ t('leads.statuses.closed') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="leads.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center">
                                {{ t('clients.noClientsFound').replace('clients', 'leads') }} <!-- quick hack or use specific key -->
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <!-- Pagination (if needed) -->
        </div>
    </AppShell>
</template>
