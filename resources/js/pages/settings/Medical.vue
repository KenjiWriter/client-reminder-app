<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Plus, Pencil, Trash2, Check, X } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface MedicalConditionType {
    id: number;
    name: string;
    category: 'contraindication' | 'esthetic';
    severity: 'high' | 'medium' | 'info';
    requires_date: boolean;
    is_active: boolean;
}

const props = defineProps<{
    conditionTypes: MedicalConditionType[];
}>();

// Modal states
const showModal = ref(false);
const editingCondition = ref<MedicalConditionType | null>(null);

// Form
const form = useForm({
    name: '',
    category: 'contraindication' as 'contraindication' | 'esthetic',
    severity: 'medium' as 'high' | 'medium' | 'info',
    requires_date: false,
});

const openCreateModal = () => {
    editingCondition.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (condition: MedicalConditionType) => {
    editingCondition.value = condition;
    form.name = condition.name;
    form.category = condition.category;
    form.severity = condition.severity;
    form.requires_date = condition.requires_date;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCondition.value = null;
    form.reset();
};

const submit = () => {
    if (editingCondition.value) {
        // Update
        form.put(route('medical-condition-types.update', editingCondition.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        // Create
        form.post(route('medical-condition-types.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const deleteCondition = (id: number) => {
    if (confirm('Czy na pewno chcesz usunąć ten typ schorzenia?')) {
        router.delete(route('medical-condition-types.destroy', id), {
            preserveScroll: true,
        });
    }
};

const getCategoryLabel = (category: string) => {
    return category === 'contraindication' ? 'Przeciwwskazanie' : 'Zabieg estetyczny';
};

const getSeverityBadge = (severity: string) => {
    const variants: Record<string, 'destructive' | 'default' | 'secondary'> = {
        high: 'destructive',
        medium: 'default',
        info: 'secondary',
    };
    const labels: Record<string, string> = {
        high: 'Wysokie',
        medium: 'Średnie',
        info: 'Info',
    };
    return { variant: variants[severity], label: labels[severity] };
};
</script>

<template>
    <Head title="Ustawienia - Schorzenia medyczne" />

    <SettingsLayout>
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Schorzenia medyczne</CardTitle>
                        <CardDescription>
                            Zarządzaj typami schorzeń i zabiegów w systemie
                        </CardDescription>
                    </div>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4 mr-2" />
                        Dodaj nowy
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nazwa</TableHead>
                                <TableHead>Kategoria</TableHead>
                                <TableHead>Poziom ważności</TableHead>
                                <TableHead>Wymaga daty</TableHead>
                                <TableHead class="text-right">Akcje</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="conditionTypes.length === 0">
                                <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                                    Brak schorzeń medycznych
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="condition in conditionTypes" :key="condition.id">
                                <TableCell class="font-medium">{{ condition.name }}</TableCell>
                                <TableCell>{{ getCategoryLabel(condition.category) }}</TableCell>
                                <TableCell>
                                    <Badge :variant="getSeverityBadge(condition.severity).variant">
                                        {{ getSeverityBadge(condition.severity).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Check v-if="condition.requires_date" class="h-4 w-4 text-green-600" />
                                    <X v-else class="h-4 w-4 text-muted-foreground" />
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openEditModal(condition)">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deleteCondition(condition.id)">
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
                        {{ editingCondition ? 'Edytuj schorzenie' : 'Dodaj nowe schorzenie' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ editingCondition ? 'Zaktualizuj informacje o schorzeniu' : 'Wprowadź dane nowego typu schorzenia' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="name">Nazwa</Label>
                        <Input id="name" v-model="form.name" required placeholder="np. Ciąża, Botox" />
                        <div v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Kategoria</Label>
                        <Select v-model="form.category" required>
                            <SelectTrigger id="category">
                                <SelectValue placeholder="Wybierz kategorię" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="contraindication">Przeciwwskazanie</SelectItem>
                                <SelectItem value="esthetic">Zabieg estetyczny</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.category" class="text-sm text-destructive">
                            {{ form.errors.category }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="severity">Poziom ważności</Label>
                        <Select v-model="form.severity" required>
                            <SelectTrigger id="severity">
                                <SelectValue placeholder="Wybierz poziom" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="high">Wysokie (czerwona odznaka)</SelectItem>
                                <SelectItem value="medium">Średnie (żółta odznaka)</SelectItem>
                                <SelectItem value="info">Info (szara odznaka)</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.severity" class="text-sm text-destructive">
                            {{ form.errors.severity }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="requires_date" v-model:checked="form.requires_date" />
                        <Label for="requires_date" class="font-normal cursor-pointer">
                            Wymaga wprowadzenia daty (np. dla Botox, Wypełniacze)
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeModal">
                            Anuluj
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Zapisywanie...' : (editingCondition ? 'Zaktualizuj' : 'Dodaj') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </SettingsLayout>
</template>
