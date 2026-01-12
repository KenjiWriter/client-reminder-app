<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle 
} from '@/components/ui/dialog';
import { Calendar, Clock, ArrowRight, Check, X, AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { format, addDays, differenceInHours } from 'date-fns';
import { route } from 'ziggy-js';
import axios from 'axios';

const { t } = useTranslation();

interface Appointment {
    id: number;
    starts_at: string;
    duration_minutes: number;
    requested_starts_at?: string;
    requested_at?: string;
    created_at: string;
    note?: string;
    client: {
        id: number;
        full_name: string;
        created_at: string;
        notes?: string;
    };
}

const props = defineProps<{
    appointments: Appointment[];
}>();



const selectedAppointment = ref<Appointment | null>(null);
const isSuggestDialogOpen = ref(false);
const isLoadingAvailability = ref(false);
const availableSlots = ref<any[]>([]);
const selectedSlot = ref<string | null>(null);

const isNewClient = (appointment: Appointment) => {
    // If client created within 24h of appointment creation (or roughly now if displayed live), consider new.
    // Or closer link: if client created_at is very close to now.
    // Better: if client.created_at is close to appointment.created_at
    if (!appointment.client.created_at) return false;
    const clientCreated = new Date(appointment.client.created_at);
    const appointmentCreated = new Date(appointment.created_at);
    return Math.abs(differenceInHours(clientCreated, appointmentCreated)) < 24;
};

const approveRequest = (appointmentId: number) => {
    router.patch(route('admin.appointments.approve', appointmentId));
};

const openSuggestDialog = (appointment: Appointment) => {
    selectedAppointment.value = appointment;
    isSuggestDialogOpen.value = true;
    fetchAvailability();
};

const fetchAvailability = async () => {
    if (!selectedAppointment.value) return;
    
    isLoadingAvailability.value = true;
    try {
        const response = await axios.get(route('admin.appointments.availability'), {
            params: {
                from: format(addDays(new Date(), 1), 'yyyy-MM-dd'),
                to: format(addDays(new Date(), 14), 'yyyy-MM-dd'),
                duration: selectedAppointment.value.duration_minutes
            }
        });
        availableSlots.value = response.data;
    } catch (error) {
        console.error('Failed to fetch availability', error);
    } finally {
        isLoadingAvailability.value = false;
    }
};

const submitSuggestion = () => {
    if (!selectedAppointment.value || !selectedSlot.value) return;

    router.patch(route('admin.appointments.reject-with-suggestion', selectedAppointment.value.id), {
        suggested_starts_at: selectedSlot.value
    }, {
        onSuccess: () => {
            isSuggestDialogOpen.value = false;
            selectedSlot.value = null;
        }
    });
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('pl-PL', { 
        weekday: 'short', 
        day: 'numeric', 
        month: 'short',
        hour: 'numeric',
        minute: 'numeric'
    }).format(new Date(date));
};
</script>

<template>
    <AppShell>
        <Head :title="t('nav.review')" />
        <template #header-title>
             <h1 class="text-2xl font-semibold">{{ t('nav.review') }}</h1>
        </template>

        <div class="p-6 max-w-5xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ t('review.title') }}</h1>
                <Badge variant="secondary" class="px-3 py-1 text-sm">
                    {{ appointments.length }} {{ t('review.pending') }}
                </Badge>
            </div>

            <div v-if="appointments.length === 0" class="flex flex-col items-center justify-center py-20 bg-white border border-dashed rounded-xl space-y-4">
                <div class="bg-gray-50 p-4 rounded-full">
                    <Check class="h-8 w-8 text-gray-400" />
                </div>
                <p class="text-gray-500 font-medium">{{ t('review.allCaughtUp') }}</p>
            </div>

            <div v-else class="space-y-4">
                <Card v-for="appointment in appointments" :key="appointment.id" class="overflow-hidden">
                    <CardContent class="p-0">
                        <div class="grid grid-cols-1 md:grid-cols-4 items-center">
                            <!-- Client Info -->
                            <!-- Client Info -->
                            <div class="p-4 md:border-r space-y-2">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">{{ t('review.client') }}</p>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-lg leading-tight">{{ appointment.client.full_name }}</p>
                                        <Badge v-if="isNewClient(appointment)" variant="default" class="bg-indigo-600 hover:bg-indigo-700 text-[10px] h-5 px-1.5">
                                            Nowy Klient
                                        </Badge>
                                    </div>
                                </div>
                                <div class="text-xs text-muted-foreground space-y-1">
                                    <div class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ appointment.requested_starts_at ? t('review.requestedAt') : 'Złożono:' }} {{ formatDate(appointment.created_at) }}
                                    </div>
                                    <!-- Show notes if available -->
                                    <div v-if="appointment.note" class="pt-2 border-t mt-2">
                                        <p class="font-semibold text-gray-700">Wiadomość:</p>
                                        <p class="italic text-gray-600 line-clamp-3">{{ appointment.note }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Date Info -->
                            <div class="p-4 md:col-span-2 flex flex-col items-center justify-center">
                                
                                <!-- Case A: Reschedule Request (Has requested_starts_at) -->
                                <div v-if="appointment.requested_starts_at" class="flex flex-col md:flex-row items-center gap-4 md:gap-8">
                                    <div class="text-center space-y-1 opacity-60">
                                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ t('review.original') }}</p>
                                        <div class="flex flex-col items-center">
                                            <span class="font-semibold line-through">{{ formatDate(appointment.starts_at) }}</span>
                                        </div>
                                    </div>

                                    <div class="hidden md:block bg-gray-100 p-2 rounded-full">
                                        <ArrowRight class="h-5 w-5 text-gray-400" />
                                    </div>

                                    <div class="text-center space-y-1">
                                        <p class="text-xs font-medium text-amber-600 uppercase tracking-wider">{{ t('review.requested') }}</p>
                                        <div class="flex flex-col items-center">
                                            <span class="font-bold text-amber-700 text-lg">{{ formatDate(appointment.requested_starts_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Case B: New Booking (No requested_starts_at) -->
                                <div v-else class="text-center space-y-2">
                                    <Badge class="bg-blue-100 text-blue-700 hover:bg-blue-100 border-none mb-1">Nowa Rezerwacja</Badge>
                                    <div class="flex flex-col items-center">
                                        <span class="text-3xl font-bold text-gray-900">{{ formatDate(appointment.starts_at) }}</span>
                                        <span class="text-sm text-gray-500 font-medium">{{ appointment.duration_minutes }} min</span>
                                    </div>
                                </div>

                            </div>

                            <!-- Actions -->
                            <div class="p-4 bg-gray-50 flex md:flex-col gap-2 justify-center h-full">
                                <Button class="flex-1 w-full bg-emerald-600 hover:bg-emerald-700 shadow-sm" @click="approveRequest(appointment.id)">
                                    <Check class="h-4 w-4 mr-2" />
                                    {{ t('review.approve') }}
                                </Button>
                                <Button variant="outline" class="flex-1 w-full border-amber-200 text-amber-700 hover:bg-amber-50" @click="openSuggestDialog(appointment)">
                                    <X class="h-4 w-4 mr-2" />
                                    {{ t('review.rejectSuggest') }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Suggestion Dialog -->
        <Dialog v-model:open="isSuggestDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('review.suggestTitle') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('review.suggestDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4 space-y-6">
                    <div v-if="isLoadingAvailability" class="flex flex-col items-center justify-center py-8 space-y-4">
                        <Loader2 class="h-8 w-8 animate-spin text-primary" />
                    </div>
                    <div v-else class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
                        <div v-for="(slots, date) in availableSlots.reduce((acc: any, slot: any) => {
                            if (!acc[slot.date]) acc[slot.date] = [];
                            acc[slot.date].push(slot);
                            return acc;
                        }, {})" :key="date" class="space-y-3">
                            <h4 class="font-medium sticky top-0 bg-background py-1">{{ date }}</h4>
                            <div class="grid grid-cols-4 gap-2">
                                <Button
                                    v-for="slot in slots"
                                    :key="slot.start"
                                    size="sm"
                                    :variant="selectedSlot === slot.start ? 'default' : 'outline'"
                                    class="w-full"
                                    @click="selectedSlot = slot.start"
                                >
                                    {{ slot.display }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="ghost" @click="isSuggestDialogOpen = false">{{ t('review.cancel') }}</Button>
                    <Button @click="submitSuggestion" :disabled="!selectedSlot">{{ t('review.proposeNotify') }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppShell>
</template>

