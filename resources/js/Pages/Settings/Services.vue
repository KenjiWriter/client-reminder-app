<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Service {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

const props = defineProps<{
    services: Service[];
}>();

// Modal states
const showModal = ref(false);
const editingService = ref<Service | null>(null);

// Form
const form = useForm({
    name: '',
    description: '',
    duration_minutes: 60,
    price: 0,
});

const openCreateModal = () => {
    editingService.value = null;
    form.reset();
    form.duration_minutes = 60; // Default
    showModal.value = true;
};

const openEditModal = (service: Service) => {
    editingService.value = service;
    form.name = service.name;
    form.description = service.description || '';
    form.duration_minutes = service.duration_minutes;
    form.price = service.price;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingService.value = null;
    form.reset();
};

const submit = () => {
    if (editingService.value) {
        // Update
        form.put(route('services.update', editingService.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        // Create
        form.post(route('services.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const deleteService = (id: number) => {
    if (confirm('Czy na pewno chcesz usunąć tę usługę?')) {
        router.delete(route('services.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatPrice = (price: number | string) => {
    return `${Number(price).toFixed(2)} PLN`;
};

const formatDuration = (minutes: number) => {
    if (minutes >= 60) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
    }
    return `${minutes} min`;
};
</script>

<template>
    <Head title="Ustawienia - Katalog Usług" />

    <SettingsLayout>
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Katalog Usług</CardTitle>
                        <CardDescription>
                            Zarządzaj dostępnymi usługami i zabiegami
                        </CardDescription>
                    </div>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Dodaj usługę
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nazwa</TableHead>
                                <TableHead>Czas trwania</TableHead>
                                <TableHead>Cena</TableHead>
                                <TableHead class="text-right">Akcje</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="services.length === 0">
                                <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                                    Brak usług w katalogu
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="service in services" :key="service.id">
                                <TableCell>
                                    <div>
                                        <div class="font-medium">{{ service.name }}</div>
                                        <div v-if="service.description" class="text-sm text-muted-foreground line-clamp-4 break-words max-w-[300px]">
                                            {{ service.description }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>{{ formatDuration(service.duration_minutes) }}</TableCell>
                                <TableCell class="font-medium">{{ formatPrice(service.price) }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openEditModal(service)">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deleteService(service.id)">
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Add/Edit Modal -->
        <Dialog :open="showModal" @update:open="closeModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingService ? 'Edytuj usługę' : 'Dodaj nową usługę' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ editingService ? 'Zaktualizuj informacje o usłudze' : 'Wprowadź dane nowej usługi' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="name">Nazwa usługi *</Label>
                        <Input id="name" v-model="form.name" required placeholder="np. Masaż Kobido" />
                        <div v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Opis (opcjonalnie)</Label>
                        <Textarea 
                            id="description" 
                            v-model="form.description" 
                            placeholder="Krótki opis usługi..."
                            rows="3"
                        />
                        <div v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="duration">Czas trwania (minuty) *</Label>
                            <Input 
                                id="duration" 
                                v-model.number="form.duration_minutes" 
                                type="number" 
                                min="0"
                                step="5"
                                required 
                                placeholder="60"
                            />
                            <div v-if="form.errors.duration_minutes" class="text-sm text-destructive">
                                {{ form.errors.duration_minutes }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="price">Cena (PLN) *</Label>
                            <Input 
                                id="price" 
                                v-model.number="form.price" 
                                type="number" 
                                min="0"
                                step="0.01"
                                required 
                                placeholder="250.00"
                            />
                            <div v-if="form.errors.price" class="text-sm text-destructive">
                                {{ form.errors.price }}
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeModal">
                            Anuluj
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Zapisywanie...' : (editingService ? 'Zaktualizuj' : 'Dodaj') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </SettingsLayout>
</template>

