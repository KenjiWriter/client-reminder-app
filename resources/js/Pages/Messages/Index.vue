<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import debounce from 'lodash/debounce';
import { Search, Info, ExternalLink, Calendar, User } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Message {
    id: number;
    to_e164: string;
    status: 'success' | 'failed';
    error: string | null;
    sent_at: string;
    client: {
        id: number;
        full_name: string;
    } | null;
    appointment: {
        id: number;
        starts_at: string;
    } | null;
}

interface MessagesData {
    data: Message[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Filters {
    search: string;
    status: string;
}

const props = defineProps<{
    messages: MessagesData;
    filters: Filters;
}>();

const { t } = useTranslation();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const updateFilters = () => {
    router.get(route('messages.index'), {
        search: search.value,
        status: status.value === 'all' ? null : status.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, debounce(updateFilters, 300));

const formatDateTime = (dateStr: string) => {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(new Date(dateStr));
};
</script>

<template>
    <Head :title="t('messages.title')" />

    <AppShell>
        <template #header-title>
            <div>
                <h1 class="text-2xl font-semibold">{{ t('messages.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('messages.subtitle') }}</p>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-2 max-w-sm">
                    <div class="relative w-full">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="search"
                            :placeholder="t('messages.search_placeholder')"
                            class="pl-9 h-10"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Select v-model="status" @update:model-value="updateFilters">
                        <SelectTrigger class="w-[180px] h-10">
                            <SelectValue :placeholder="t('messages.table.status')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{ t('common.all') || 'All' }}</SelectItem>
                            <SelectItem value="success">{{ t('messages.status_success') }}</SelectItem>
                            <SelectItem value="failed">{{ t('messages.status_failed') }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[120px]">{{ t('messages.table.status') }}</TableHead>
                            <TableHead>{{ t('messages.table.recipient') }}</TableHead>
                            <TableHead>{{ t('messages.table.client') }}</TableHead>
                            <TableHead>{{ t('messages.table.sent_at') }}</TableHead>
                            <TableHead class="text-right">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="msg in messages.data" :key="msg.id" class="group transition-colors hover:bg-muted/50">
                            <TableCell>
                                <Badge 
                                    :variant="msg.status === 'success' ? 'success' : 'destructive'"
                                    class="font-medium"
                                >
                                    {{ msg.status === 'success' ? t('messages.status_success') : t('messages.status_failed') }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="font-medium font-mono text-sm">{{ msg.to_e164 }}</div>
                            </TableCell>
                            <TableCell>
                                <div v-if="msg.client" class="flex items-center gap-2 text-sm text-foreground">
                                    <User class="h-3.5 w-3.5 text-muted-foreground" />
                                    <span>{{ msg.client.full_name }}</span>
                                </div>
                                <span v-else class="text-muted-foreground">-</span>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm text-muted-foreground">
                                    {{ formatDateTime(msg.sent_at) }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button v-if="msg.error" variant="ghost" size="icon" :title="msg.error">
                                        <Info class="h-4 w-4 text-destructive" />
                                    </Button>
                                    <Button v-if="msg.client" variant="ghost" size="icon" as-child>
                                        <Link :href="route('clients.show', msg.client.id)">
                                            <ExternalLink class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="msg.appointment" variant="ghost" size="icon" as-child>
                                        <Link :href="route('calendar.index')">
                                            <Calendar class="h-4 w-4 text-primary" />
                                        </Link>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="messages.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-muted-foreground">
                                {{ t('messages.empty') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="messages.last_page > 1" class="flex items-center justify-between py-2">
                <p class="text-sm text-muted-foreground">
                    Showing {{ messages.total }} results
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!messages.links[0].url"
                        as-child
                    >
                        <component 
                            :is="messages.links[0].url ? Link : 'span'" 
                            :href="messages.links[0].url"
                            preserve-scroll
                        >
                            {{ t('common.previous') || 'Previous' }}
                        </component>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!messages.links[messages.links.length - 1].url"
                        as-child
                    >
                        <component 
                            :is="messages.links[messages.links.length - 1].url ? Link : 'span'" 
                            :href="messages.links[messages.links.length - 1].url"
                            preserve-scroll
                        >
                            {{ t('common.next') || 'Next' }}
                        </component>
                    </Button>
                </div>
            </div>
        </div>
    </AppShell>
</template>

