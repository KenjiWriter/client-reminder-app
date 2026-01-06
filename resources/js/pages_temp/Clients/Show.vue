<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import QuickAddConditionModal from '@/components/QuickAddConditionModal.vue';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { AlertCircle, Calendar, Syringe, Heart, Plus } from 'lucide-vue-next';

const { t } = useTranslation();

interface MedicalConditionType {
    id: number;
    name: string;
    category: 'contraindication' | 'esthetic';
    severity: 'high' | 'medium' | 'info';
    requires_date: boolean;
    is_active: boolean;
}

interface ClientCondition {
    id: number;
    name: string;
    pivot: {
        occurred_at: string | null;
        notes: string | null;
        is_active: boolean;
    };
}

interface Appointment {
    id: number;
    starts_at: string;
    ends_at: string;
    status: string;
    service_name?: string;
}

interface Client {
    id: number;
    full_name: string;
    email: string | null;
    phone_e164: string;
    notes?: string | null;
    conditions?: ClientCondition[];
    appointments?: Appointment[];
}

const props = defineProps<{
    client: Client;
    allConditionTypes: MedicalConditionType[];
}>();

// Active tab state
const activeTab = ref<'basic' | 'medical' | 'appointments'>('basic');

// Local state for condition types (will be updated when Quick Add creates new one)
const localConditionTypes = ref<MedicalConditionType[]>([...props.allConditionTypes]);

// Quick Add modal state
const showQuickAdd = ref(false);
const quickAddCategory = ref<'contraindication' | 'esthetic'>('contraindication');

// Group conditions by category
const contraindications = computed(() => 
    localConditionTypes.value.filter(c => c.category === 'contraindication')
);

const estheticProcedures = computed(() => 
    localConditionTypes.value.filter(c => c.category === 'esthetic')
);

// Safety badge computation (based on client's active conditions)
const safetyBadges = computed(() => {
    const badges: { text: string; variant: 'destructive' | 'default'; icon: any }[] = [];
    
    if (!props.client.conditions) return badges;
    
    props.client.conditions.forEach(condition => {
        const condType = localConditionTypes.value.find(ct => ct.name === condition.name);
        if (!condType || !condition.pivot.is_active) return;
        
        if (condType.severity === 'high') {
            badges.push({ text: condition.name, variant: 'destructive', icon: AlertCircle });
        } else if (condType.category === 'esthetic') {
            badges.push({ text: condition.name, variant: 'default', icon: Syringe });
        }
    });
    
    return badges;
});

// Basic Info Form
const basicForm = useForm({
    full_name: props.client.full_name || '',
    email: props.client.email || '',
    phone: props.client.phone_e164 || '',
    notes: props.client.notes || '',
});

const submitBasicInfo = () => {
    basicForm.put(route('clients.update', props.client.id), {
        preserveScroll: true,
    });
};

// Medical Conditions Form (Dynamic)
// Build form data: array of condition IDs that are checked, with metadata
const selectedConditions = ref<Record<number, { checked: boolean; occurred_at: string; notes: string }>>({});

// Initialize from client's existing conditions
localConditionTypes.value.forEach(condType => {
    const existing = props.client.conditions?.find(c => c.name === condType.name);
    selectedConditions.value[condType.id] = {
        checked: existing?.pivot.is_active ?? false,
        occurred_at: existing?.pivot.occurred_at || '',
        notes: existing?.pivot.notes || '',
    };
});

const medicalForm = useForm({
    conditions: selectedConditions.value,
    // Keep old fields for compatibility with existing backend
    allergies: '',
    medications: '',
    additional_notes: '',
});

const submitMedicalHistory = () => {
    // TODO: Update this to send to new endpoint that handles pivot table
    // For now, send to existing endpoint
    medicalForm.put(route('clients.medical-history.update', props.client.id), {
        preserveScroll: true,
    });
};

// Quick Add handlers
const openQuickAdd = (category: 'contraindication' | 'esthetic') => {
    quickAddCategory.value = category;
    showQuickAdd.value = true;
};

const handleConditionCreated = (newCondition: MedicalConditionType) => {
    // Add to local types array
    localConditionTypes.value.push(newCondition);
    
    // Auto-check the new condition
    selectedConditions.value[newCondition.id] = {
        checked: true,
        occurred_at: '',
        notes: '',
    };
    
    // Update form
    medicalForm.conditions = selectedConditions.value;
};

// Format date for display
const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pl-PL', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatStatus = (status: string) => {
    const statusMap: Record<string, string> = {
        'scheduled': 'Zaplanowana',
        'completed': 'Zakończona',
        'cancelled': 'Anulowana',
        'no_show': 'Nieobecność',
    };
    return statusMap[status] || status;
};
</script>

<template>
    <Head :title="`Pacjent: ${client.full_name}`" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">{{ client.full_name }}</h1>
                <div class="flex items-center gap-2">
                    <Badge
                        v-for="(badge, index) in safetyBadges"
                        :key="index"
                        :variant="badge.variant"
                        class="flex items-center gap-1"
                    >
                        <component :is="badge.icon" class="h-3 w-3" />
                        {{ badge.text }}
                    </Badge>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto">
            <!-- Tab Navigation -->
            <div class="border-b border-border mb-6">
                <nav class="flex gap-4" aria-label="Tabs">
                    <button
                        @click="activeTab = 'basic'"
                        :class="[
                            'py-3 px-4 font-medium text-sm border-b-2 transition-colors',
                            activeTab === 'basic'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'
                        ]"
                    >
                        Dane podstawowe
                    </button>
                    <button
                        @click="activeTab = 'medical'"
                        :class="[
                            'py-3 px-4 font-medium text-sm border-b-2 transition-colors',
                            activeTab === 'medical'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'
                        ]"
                    >
                        Karta Medyczna
                    </button>
                    <button
                        @click="activeTab = 'appointments'"
                        :class="[
                            'py-3 px-4 font-medium text-sm border-b-2 transition-colors',
                            activeTab === 'appointments'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'
                        ]"
                    >
                        Historia Wizyt
                    </button>
                </nav>
            </div>

            <!-- Tab 1: Basic Info -->
            <div v-show="activeTab === 'basic'">
                <Card>
                    <CardHeader>
                        <CardTitle>Dane podstawowe pacjenta</CardTitle>
                        <CardDescription>Podstawowe informacje kontaktowe i notatki</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitBasicInfo" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="full_name">Imię i nazwisko</Label>
                                <Input id="full_name" v-model="basicForm.full_name" required />
                                <div v-if="basicForm.errors.full_name" class="text-sm text-destructive">
                                    {{ basicForm.errors.full_name }}
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" type="email" v-model="basicForm.email" />
                                <div v-if="basicForm.errors.email" class="text-sm text-destructive">
                                    {{ basicForm.errors.email }}
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="phone">Telefon</Label>
                                <Input id="phone" v-model="basicForm.phone" type="tel" required />
                                <div v-if="basicForm.errors.phone" class="text-sm text-destructive">
                                    {{ basicForm.errors.phone }}
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="notes">Notatki</Label>
                                <Textarea id="notes" v-model="basicForm.notes" rows="4" />
                                <div v-if="basicForm.errors.notes" class="text-sm text-destructive">
                                    {{ basicForm.errors.notes }}
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="basicForm.processing">
                                    {{ basicForm.processing ? 'Zapisywanie...' : 'Zapisz zmiany' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab 2: Medical History (DYNAMIC) -->
            <div v-show="activeTab === 'medical'">
                <form @submit.prevent="submitMedicalHistory" class="space-y-6">
                    <!-- Contraindications (DYNAMIC) -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <AlertCircle class="h-5 w-5 text-destructive" />
                                    <CardTitle>Przeciwwskazania</CardTitle>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="openQuickAdd('contraindication')">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Dodaj nowy
                                </Button>
                            </div>
                            <CardDescription>Schorzenia stanowiące przeciwwskazanie do zabiegów</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="condition in contraindications" :key="condition.id" class="flex items-center justify-between">
                                <Label :for="`cond-${condition.id}`">{{ condition.name }}</Label>
                                <Checkbox 
                                    :id="`cond-${condition.id}`" 
                                    v-model:checked="selectedConditions[condition.id].checked" 
                                />
                            </div>
                            <div v-if="contraindications.length === 0" class="text-sm text-muted-foreground text-center py-4">
                                Brak przeciwwskazań. Kliknij "Dodaj nowy" aby dodać.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Esthetic Procedures (DYNAMIC) -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Syringe class="h-5 w-5 text-primary" />
                                    <CardTitle>Zabiegi estetyczne</CardTitle>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="openQuickAdd('esthetic')">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Dodaj nowy
                                </Button>
                            </div>
                            <CardDescription>Historia zabiegów medycyny estetycznej</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="condition in estheticProcedures" :key="condition.id" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label :for="`cond-${condition.id}`">{{ condition.name }}</Label>
                                    <Checkbox 
                                        :id="`cond-${condition.id}`" 
                                        v-model:checked="selectedConditions[condition.id].checked" 
                                    />
                                </div>
                                <!-- Show date picker if condition requires date and is checked -->
                                <div v-if="condition.requires_date && selectedConditions[condition.id].checked" class="ml-4 grid gap-2">
                                    <Label :for="`date-${condition.id}`" class="text-sm text-muted-foreground">
                                        Data ostatniego zabiegu
                                    </Label>
                                    <Input 
                                        :id="`date-${condition.id}`" 
                                        type="date" 
                                        v-model="selectedConditions[condition.id].occurred_at" 
                                    />
                                </div>
                            </div>
                            <div v-if="estheticProcedures.length === 0" class="text-sm text-muted-foreground text-center py-4">
                                Brak zabiegów. Kliknij "Dodaj nowy" aby dodać.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Additional Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Heart class="h-5 w-5" />
                                Informacje dodatkowe
                            </CardTitle>
                            <CardDescription>Alergie, leki i inne istotne informacje</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="allergies">Alergie</Label>
                                <Textarea id="allergies" v-model="medicalForm.allergies" rows="3" placeholder="Wymień wszystkie znane alergie..." />
                            </div>

                            <div class="grid gap-2">
                                <Label for="medications">Przyjmowane leki</Label>
                                <Textarea id="medications" v-model="medicalForm.medications" rows="3" placeholder="Wymień wszystkie przyjmowane leki..." />
                            </div>

                            <div class="grid gap-2">
                                <Label for="additional_notes">Dodatkowe notatki</Label>
                                <Textarea id="additional_notes" v-model="medicalForm.additional_notes" rows="4" placeholder="Inne istotne informacje medyczne..." />
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="medicalForm.processing">
                            {{ medicalForm.processing ? 'Zapisywanie...' : 'Zapisz kartę medyczną' }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Tab 3: Appointments History -->
            <div v-show="activeTab === 'appointments'">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Historia wizyt
                        </CardTitle>
                        <CardDescription>Wszystkie wizyty pacjenta w systemie</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!client.appointments || client.appointments.length === 0" class="text-center py-8 text-muted-foreground">
                            Brak wizyt w historii
                        </div>
                        <div v-else class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Data i godzina</TableHead>
                                        <TableHead>Usługa</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="appointment in client.appointments" :key="appointment.id">
                                        <TableCell class="font-medium">
                                            {{ formatDate(appointment.starts_at) }}
                                        </TableCell>
                                        <TableCell>
                                            {{ appointment.service_name || '-' }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="appointment.status === 'completed' ? 'default' : 'secondary'">
                                                {{ formatStatus(appointment.status) }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Quick Add Modal -->
        <QuickAddConditionModal
            :open="showQuickAdd"
            :preselectedCategory="quickAddCategory"
            @close="showQuickAdd = false"
            @created="handleConditionCreated"
        />
    </AppShell>
</template>

