<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
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
import debounce from 'lodash/debounce';
import { Plus, Trash, Pencil } from 'lucide-vue-next';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/clients',
    },
];

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

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Clients</h1>
                <Link :href="route('clients.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" /> Add Client
                    </Button>
                </Link>
            </div>

            <div class="flex w-full items-center space-x-2">
                <Input
                    v-model="search"
                    placeholder="Search clients..."
                    class="max-w-sm"
                />
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="client in clients.data" :key="client.id">
                            <TableCell class="font-medium">
                                {{ client.full_name }}
                            </TableCell>
                            <TableCell>{{ client.phone_e164 }}</TableCell>
                            <TableCell>{{ client.email || '-' }}</TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="route('clients.edit', client.id)">
                                    <Button variant="outline" size="sm">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="deleteClient(client.id)">
                                    <Trash class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="clients.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                No clients found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
