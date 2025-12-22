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
import { Plus, Trash2, Edit, MoreHorizontal } from 'lucide-vue-next';
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
    links: any[];
}

interface Filters {
    search: string;
}

const props = withDefaults(defineProps<{
    clients: ClientsData;
    filters: Filters;
}>(), {
    clients: () => ({ data: [], links: [] }),
    filters: () => ({ search: '' }),
});

const { t } = useTranslation();

const search = ref(props.filters.search || '');

watch(search, debounce((value: string) => {
    router.get('/clients', { search: value }, { preserveState: true, replace: true });
}, 300));

const deleteClient = (id: number) => {
    if (confirm('Are you sure you want to delete this client?')) {
        router.delete(route('clients.destroy', id));
    }
};
</script>

<template>
    <Head title="Clients" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center justify-between w-full">
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
                        <TableRow v-for="client in clients.data" :key="client.id">
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
                                            <Link :href="route('clients.edit', client.id)">
                                                <Edit class="mr-2 h-4 w-4" /> {{ t('common.edit') }}
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
                        <TableRow v-if="clients.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                {{ t('clients.noClientsFound') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppShell>
</template>
