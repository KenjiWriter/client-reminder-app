<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import debounce from 'lodash/debounce';
import { Plus, Trash2, MoreHorizontal, Loader2, FileText } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Client {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
    public_uid: string;
}

interface ClientsData {
    data: Client[];
    next_cursor: string | null;
    next_page_url: string | null;
}

interface Filters {
    search: string;
}

const props = withDefaults(defineProps<{
    clients: ClientsData;
    filters: Filters;
}>(), {
    clients: () => ({ data: [], next_cursor: null, next_page_url: null }),
    filters: () => ({ search: '' }),
});

const { t } = useTranslation();

const search = ref(props.filters.search || '');
const allClients = ref<Client[]>([...props.clients.data]);
const nextCursor = ref(props.clients.next_cursor);
const isLoadingMore = ref(false);

// Reset clients when search changes
watch(search, debounce((value: string) => {
    allClients.value = [];
    nextCursor.value = null;
    router.get('/clients', { search: value }, { 
        preserveState: true, 
        replace: true,
        onSuccess: (page: any) => {
            allClients.value = page.props.clients.data;
            nextCursor.value = page.props.clients.next_cursor;
        },
    });
}, 300));

// Watch for prop changes when navigating back
watch(() => props.clients, (newClients) => {
    if (!isLoadingMore.value) {
        allClients.value = [...newClients.data];
        nextCursor.value = newClients.next_cursor;
    }
}, { deep: true });

const loadMore = () => {
    if (!nextCursor.value || isLoadingMore.value) return;
    
    isLoadingMore.value = true;
    
    router.get('/clients', { 
        search: search.value,
        cursor: nextCursor.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['clients'],
        onSuccess: (page: any) => {
            allClients.value = [...allClients.value, ...page.props.clients.data];
            nextCursor.value = page.props.clients.next_cursor;
            isLoadingMore.value = false;
        },
        onError: () => {
            isLoadingMore.value = false;
        },
    });
};

const deleteClient = (id: number) => {
    if (confirm('Are you sure you want to delete this client?')) {
        router.delete(route('clients.destroy', id), {
            onSuccess: () => {
                // Remove from local array
                allClients.value = allClients.value.filter(c => c.id !== id);
            },
        });
    }
};
</script>

<template>
    <Head title="Clients" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center justify-between gap-4 w-full">
                <h1 class="text-2xl font-semibold">{{ t('clients.title') }}</h1>
                <Link :href="route('clients.create')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2">
                    <Plus class="h-4 w-4" /> {{ t('clients.newClient') }}
                </Link>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex w-full items-center space-x-2">
                <Input
                    v-model="search"
                    type="search"
                    :placeholder="t('common.search')"
                    class="max-w-sm"
                />
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('clients.fullName') }}</TableHead>
                            <TableHead>{{ t('clients.phone') }}</TableHead>
                            <TableHead>{{ t('clients.email') }}</TableHead>
                            <TableHead class="text-right">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="client in allClients" :key="client.id">
                            <TableCell class="font-medium">
                                {{ client.full_name }}
                            </TableCell>
                            <TableCell>{{ client.phone_e164 }}</TableCell>
                            <TableCell>{{ client.email || '-' }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                            <span class="sr-only">{{ t('common.openMenu') }}</span>
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>{{ t('common.actions') }}</DropdownMenuLabel>
                                        <DropdownMenuItem as-child>
                                            <Link :href="route('clients.show', client.id)">
                                                <FileText class="mr-2 h-4 w-4" /> Zobacz profil
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="deleteClient(client.id)" class="text-destructive">
                                            <Trash2 class="mr-2 h-4 w-4" /> {{ t('common.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="allClients.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                {{ t('clients.noClientsFound') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Load More Button -->
            <div v-if="nextCursor" class="flex justify-center py-4">
                <Button 
                    variant="outline" 
                    @click="loadMore" 
                    :disabled="isLoadingMore"
                    class="min-w-[200px]"
                >
                    <Loader2 v-if="isLoadingMore" class="mr-2 h-4 w-4 animate-spin" />
                    {{ isLoadingMore ? (t('common.loading') || 'Loading...') : (t('common.loadMore') || 'Load More') }}
                </Button>
            </div>
        </div>
    </AppShell>
</template>

