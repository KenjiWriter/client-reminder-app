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
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { AlertCircle, Calendar, Syringe, Heart } from 'lucide-vue-next';

const { t } = useTranslation();

interface MedicalHistory {
    id?: number;
    client_id: number;
    is_pregnant: boolean;
    has_epilepsy: boolean;
    has_thyroid_issues: boolean;
    has_cancer: boolean;
    has_herpes: boolean;
    has_botox: boolean;
    botox_last_date: string | null;
    has_fillers: boolean;
    fillers_last_date: string | null;
    has_threads: boolean;
    allergies: string | null;
    medications: string | null;
    additional_notes: string | null;
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
    medical_history?: MedicalHistory;
    appointments?: Appointment[];
}

const props = defineProps<{
    client: Client;
}>();

// Active tab state
const activeTab = ref<'basic' | 'medical' | 'appointments'>('basic');

// Safety badge computation
const safetyBadges = computed(() => {
    const badges: { text: string; variant: 'destructive' | 'default'; icon: any }[] = [];
    const medHistory = props.client.medical_history;
    
    if (medHistory?.is_pregnant) {
        badges.push({ text: 'Ciąża', variant: 'destructive', icon: AlertCircle });
    }
    if (medHistory?.has_epilepsy) {
        badges.push({ text: 'Epilepsja', variant: 'destructive', icon: AlertCircle });
    }
    if (medHistory?.has_botox) {
        badges.push({ text: 'Botox', variant: 'default', icon: Syringe });
    }
    if (medHistory?.has_fillers) {
        badges.push({ text: 'Wypełniacze', variant: 'default', icon: Syringe });
    }
    
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
        onSuccess: () => {
            // Success feedback handled by backend flash message
        }
    });
};

// Medical History Form
const medicalForm = useForm({
    is_pregnant: props.client.medical_history?.is_pregnant ?? false,
    has_epilepsy: props.client.medical_history?.has_epilepsy ?? false,
    has_thyroid_issues: props.client.medical_history?.has_thyroid_issues ?? false,
    has_cancer: props.client.medical_history?.has_cancer ?? false,
    has_herpes: props.client.medical_history?.has_herpes ?? false,
    has_botox: props.client.medical_history?.has_botox ?? false,
    botox_last_date: props.client.medical_history?.botox_last_date || '',
    has_fillers: props.client.medical_history?.has_fillers ?? false,
    fillers_last_date: props.client.medical_history?.fillers_last_date || '',
    has_threads: props.client.medical_history?.has_threads ?? false,
    allergies: props.client.medical_history?.allergies || '',
    medications: props.client.medical_history?.medications || '',
    additional_notes: props.client.medical_history?.additional_notes || '',
});

const submitMedicalHistory = () => {
    medicalForm.put(route('clients.medical-history.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Success feedback handled by backend flash message
        }
    });
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

            <!-- Tab 2: Medical History -->
            <div v-show="activeTab === 'medical'">
                <form @submit.prevent="submitMedicalHistory" class="space-y-6">
                    <!-- Contraindications -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <AlertCircle class="h-5 w-5 text-destructive" />
                                Przeciwwskazania
                            </CardTitle>
                            <CardDescription>Schorzenia stanowiące przeciwwskazanie do zabiegów</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Label for="is_pregnant">Ciąża</Label>
                                <Checkbox id="is_pregnant" v-model:checked="medicalForm.is_pregnant" />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="has_epilepsy">Epilepsja</Label>
                                <Checkbox id="has_epilepsy" v-model:checked="medicalForm.has_epilepsy" />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="has_cancer">Nowotwór</Label>
                                <Checkbox id="has_cancer" v-model:checked="medicalForm.has_cancer" />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="has_herpes">Opryszczka</Label>
                                <Checkbox id="has_herpes" v-model:checked="medicalForm.has_herpes" />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="has_thyroid_issues">Problemy z tarczycą</Label>
                                <Checkbox id="has_thyroid_issues" v-model:checked="medicalForm.has_thyroid_issues" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Esthetic Procedures -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Syringe class="h-5 w-5 text-primary" />
                                Zabiegi estetyczne
                            </CardTitle>
                            <CardDescription>Historia zabiegów medycyny estetycznej</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label for="has_botox">Botox</Label>
                                    <Checkbox id="has_botox" v-model:checked="medicalForm.has_botox" />
                                </div>
                                <div v-if="medicalForm.has_botox" class="ml-4 grid gap-2">
                                    <Label for="botox_last_date" class="text-sm text-muted-foreground">
                                        Data ostatniego zabiegu
                                    </Label>
                                    <Input id="botox_last_date" type="date" v-model="medicalForm.botox_last_date" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label for="has_fillers">Wypełniacze</Label>
                                    <Checkbox id="has_fillers" v-model:checked="medicalForm.has_fillers" />
                                </div>
                                <div v-if="medicalForm.has_fillers" class="ml-4 grid gap-2">
                                    <Label for="fillers_last_date" class="text-sm text-muted-foreground">
                                        Data ostatniego zabiegu
                                    </Label>
                                    <Input id="fillers_last_date" type="date" v-model="medicalForm.fillers_last_date" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="has_threads">Nici liftingujące</Label>
                                <Checkbox id="has_threads" v-model:checked="medicalForm.has_threads" />
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
    </AppShell>
</template>
