<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Calendar as CalendarIcon, Clock, CheckCircle2, XCircle, AlertCircle, Loader2 } from 'lucide-vue-next';
import { format, addDays, startOfDay } from 'date-fns';
import { route } from 'ziggy-js';
import axios from 'axios';

onMounted(() => {
    // Force light mode for this public page
    document.documentElement.classList.remove('dark');
});

const { t, locale } = useTranslation();

interface Appointment {
    id: number;
    starts_at: string;
    duration_minutes: number;
    note: string | null;
    status: 'confirmed' | 'pending_approval' | 'canceled';
    requested_starts_at: string | null;
    suggested_starts_at: string | null;
    can_reschedule: boolean;
}

interface Client {
    full_name: string;
    public_uid: string;
    sms_opt_out: boolean;
    email: string | null;
    phone: string | null;
}

const props = withDefaults(defineProps<{
    client: Client;
    appointments: Appointment[];
    settings: {
        app_name: string;
        app_logo: string | null;
    };
}>(), {
    appointments: () => [],
});

const bookNow = () => {
    const url = route('home');
    const params = new URLSearchParams();
    
    if (props.client.full_name) params.append('client_name', props.client.full_name);
    if (props.client.phone) params.append('client_phone', props.client.phone);
    if (props.client.email) params.append('client_email', props.client.email);
    
    window.location.href = `${url}?${params.toString()}#booking`;
};

const isOptedOut = ref(props.client.sms_opt_out);

const setOptOut = (shouldOptOut: boolean) => {
    // Optimistic update
    isOptedOut.value = shouldOptOut;
    
    router.post(route('public.client.toggle-opt-out', props.client.public_uid), {
        opt_out: shouldOptOut
    }, {
        preserveScroll: true,
        onError: () => {
             // Revert on error
             isOptedOut.value = !shouldOptOut;
        },
    });
};

const cancelAppointment = (appointment: Appointment) => {
    if (!confirm(t('public.cancel.confirm', { date: formatDate(appointment.starts_at) }))) return;

    router.delete(route('public.client.cancel-appointment', [props.client.public_uid, appointment.id]), {
        preserveScroll: true
    });
};


// Rescheduling logic
const selectedAppointment = ref<Appointment | null>(null);
const isRescheduleDialogOpen = ref(false);
const isLoadingAvailability = ref(false);
const availableSlots = ref<any[]>([]);
const selectedSlot = ref<string | null>(null);

const openRescheduleDialog = (appointment: Appointment) => {
    selectedAppointment.value = appointment;
    selectedSlot.value = null;
    isRescheduleDialogOpen.value = true;
    fetchAvailability();
};

const fetchAvailability = async () => {
    if (!selectedAppointment.value) return;
    
    isLoadingAvailability.value = true;
    try {
        const response = await axios.get(route('public.client.availability', props.client.public_uid), {
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

const submitReschedule = () => {
    if (!selectedAppointment.value || !selectedSlot.value) return;

    router.patch(route('public.client.request-reschedule', [props.client.public_uid, selectedAppointment.value.id]), {
        new_start: selectedSlot.value
    }, {
        onSuccess: () => {
            isRescheduleDialogOpen.value = false;
            selectedSlot.value = null;
        }
    });
};

const acceptSuggestion = (appointmentId: number) => {
    router.patch(route('public.client.accept-suggestion', [props.client.public_uid, appointmentId]), {}, {
        preserveScroll: true
    });
};

const rejectSuggestion = (appointmentId: number) => {
    router.patch(route('public.client.reject-suggestion', [props.client.public_uid, appointmentId]), {}, {
        preserveScroll: true
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'pending_approval': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'canceled': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat(locale, { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    }).format(new Date(date));
};

const formatTime = (date: string) => {
    return new Intl.DateTimeFormat(locale, { 
        hour: 'numeric', 
        minute: 'numeric', 
        hour12: locale === 'en' 
    }).format(new Date(date));
};

const slotsByDate = computed(() => {
    const grouped: Record<string, any[]> = {};
    availableSlots.value.forEach(slot => {
        if (!grouped[slot.date]) grouped[slot.date] = [];
        grouped[slot.date].push(slot);
    });
    return grouped;
});
</script>

<template>
    <Head :title="t('public.title', { name: client.full_name })" />

    <div class="min-h-screen bg-background">
        <div class="max-w-3xl mx-auto p-6 space-y-8">
            <!-- Header -->
            <div class="text-center space-y-4">
                <div class="flex justify-center">
                    <div v-if="settings.app_logo" class="h-24 w-auto max-w-[200px]">
                        <img 
                            :src="settings.app_logo" 
                            :alt="settings.app_name" 
                            class="h-full w-full object-contain"
                        />
                    </div>
                    <div v-else class="h-12 w-12 bg-primary rounded-xl flex items-center justify-center text-primary-foreground shadow-lg">
                        <CalendarIcon class="h-7 w-7" />
                    </div>
                </div>
                <div class="space-y-2">
                    <h2 class="text-xl font-medium text-foreground tracking-tight">{{ settings.app_name }}</h2>
                    <h1 class="text-3xl font-bold">{{ t('public.hello', { name: client.full_name }) }}</h1>
                    <p class="text-muted-foreground">{{ t('public.viewUpcoming') }}</p>
                </div>
            </div>

            <!-- SMS Opt-Out Toggle -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('public.smsReminders') }}</CardTitle>
                    <CardDescription>
                        {{ client.sms_opt_out 
                            ? t('public.remindersDisabled') 
                            : t('public.remindersEnabled') 
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!isOptedOut" class="space-y-3">
                         <div class="flex items-center gap-2 text-emerald-700 bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                            <CheckCircle2 class="h-5 w-5 flex-shrink-0" />
                            <p class="text-sm font-medium">Otrzymasz przypomienie dzień przed terminem wizyty</p>
                         </div>
                         <Button variant="outline" size="sm" class="text-muted-foreground hover:text-foreground" @click="setOptOut(true)">
                            Wyłącz powiadomienia
                         </Button>
                    </div>
                    <div v-else class="space-y-3">
                         <div class="flex items-center gap-2 text-amber-700 bg-amber-50 p-3 rounded-lg border border-amber-100">
                            <XCircle class="h-5 w-5 flex-shrink-0" />
                            <p class="text-sm font-medium">Wyłączyłeś powiadomienia SMS dzień przed terminem wizyty</p>
                         </div>
                         <p class="text-sm text-muted-foreground">Aby otrzymywać powiadomienia, naciśnij przycisk poniżej.</p>
                         <Button variant="default" size="sm" @click="setOptOut(false)">
                            Włącz powiadomienia
                         </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Appointments List -->
            <div class="space-y-4">
                <h2 class="text-2xl font-semibold">{{ t('public.upcomingAppointments') }}</h2>
                
                <div v-if="appointments.length === 0" class="text-center py-12">
                    <CalendarIcon class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-lg text-muted-foreground">{{ t('public.noAppointments') }}</p>
                </div>

                <Card v-for="appointment in appointments" :key="appointment.id" class="hover:shadow-md transition-shadow">
                    <CardContent class="pt-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary/10 p-3 rounded-lg">
                                    <CalendarIcon class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div class="flex-1 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <CalendarIcon class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-semibold">
                                                {{ formatDate(appointment.starts_at) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="text-muted-foreground">
                                                {{ formatTime(appointment.starts_at) }}
                                                ({{ appointment.duration_minutes }} {{ t('common.minutes') }})
                                            </span>
                                        </div>
                                    </div>
                                    <Badge :class="getStatusColor(appointment.status)">
                                        {{ t(`public.status.${appointment.status}`) }}
                                    </Badge>
                                </div>

                                <!-- Client Rejected Suggestion / Needs Action -->
                                <div v-if="appointment.status === 'pending_approval' && !appointment.requested_starts_at && !appointment.suggested_starts_at"
                                     class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-2">
                                     <div class="flex items-center gap-2 text-amber-800 font-medium">
                                         <AlertCircle class="h-5 w-5" />
                                         Wymagana akcja
                                     </div>
                                     <p class="text-sm text-amber-700">
                                         Odrzuciłeś propozycję zmiany terminu. Proszę wybierz inny termin wizyty lub skontaktuj się z nami.
                                     </p>
                                     <Button size="sm" class="w-full sm:w-auto mt-2" @click="openRescheduleDialog(appointment)">
                                         Wybierz inny termin
                                     </Button>
                                </div>

                                <!-- Pending approval: Client's request -->
                                <div v-if="appointment.status === 'pending_approval' && appointment.requested_starts_at" 
                                    class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-3">
                                    <AlertCircle class="h-5 w-5 text-amber-600 mt-0.5" />
                                    <p class="text-sm text-amber-800">
                                        {{ t('public.reschedule.waiting', { time: format(new Date(appointment.requested_starts_at), 'HH:mm') }) }}
                                    </p>
                                </div>

                                <!-- Pending approval: Mother's suggestion -->
                                <div v-if="appointment.status === 'pending_approval' && appointment.suggested_starts_at" 
                                    class="bg-primary/5 border border-primary/20 rounded-lg p-4 space-y-3">
                                    <div class="flex items-center gap-2 font-medium text-primary">
                                        <CheckCircle2 class="h-5 w-5" />
                                        {{ t('public.reschedule.suggestionHeader') }}
                                    </div>
                                    <div class="pl-7 space-y-1">
                                        <p class="font-semibold">{{ formatDate(appointment.suggested_starts_at) }}</p>
                                        <p class="text-muted-foreground">{{ formatTime(appointment.suggested_starts_at) }}</p>
                                    </div>
                                    <div class="flex gap-2 pl-7 pt-1">
                                        <Button size="sm" @click="acceptSuggestion(appointment.id)">
                                            {{ t('public.reschedule.accept') }}
                                        </Button>
                                        <Button size="sm" variant="outline" @click="rejectSuggestion(appointment.id)">
                                            {{ t('public.reschedule.reject') }}
                                        </Button>
                                    </div>
                                </div>

                                <p v-if="appointment.note" class="text-sm text-muted-foreground pl-6 border-l-2 border-muted italic">
                                    {{ appointment.note }}
                                </p>

                                <!-- Reschedule Button -->
                                <div v-if="appointment.can_reschedule && !(!appointment.requested_starts_at && !appointment.suggested_starts_at && appointment.status === 'pending_approval')" class="pt-2">
                                    <Button variant="outline" size="sm" @click="openRescheduleDialog(appointment)">
                                        {{ t('public.reschedule.button') }}
                                    </Button>
                                    
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 ml-2"
                                        @click="cancelAppointment(appointment)"
                                    >
                                        {{ t('public.cancel.button') }}
                                    </Button>
                                </div>
                                <div v-else-if="appointment.status === 'confirmed'" class="text-xs text-muted-foreground pt-2 italic">
                                    {{ t('public.reschedule.restriction') }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Footer Action: Book Now -->
            <div class="flex justify-center pt-8 pb-4">
                <Button
                    size="lg"
                    @click="bookNow"
                    class="w-full sm:w-auto text-lg px-8 py-6 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-105 bg-primary text-primary-foreground"
                >
                    Umów kolejną wizytę
                </Button>
            </div>

            <!-- Footer -->
            <div class="border-t pt-8 mt-4 text-center text-sm text-muted-foreground space-y-4">
                 <div class="flex flex-wrap justify-center gap-6">
                    <a href="/regulamin" target="_blank" class="hover:text-primary transition-colors">Regulamin</a>
                    <a href="/polityka-prywatnosci" target="_blank" class="hover:text-primary transition-colors">Polityka Prywatności</a>
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Emilia Wiśniewska. Wszelkie prawa zastrzeżone.</p>
                <p>{{ t('public.footerContact') }}</p>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isRescheduleDialogOpen">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('public.reschedule.title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('public.reschedule.description') }}
                </DialogDescription>
            </DialogHeader>
            
            <div class="py-4 space-y-6">
                <div v-if="isLoadingAvailability" class="flex flex-col items-center justify-center py-8 space-y-4">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <div v-else class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
                    <div v-for="(slots, date) in slotsByDate" :key="date" class="space-y-3">
                        <h4 class="font-medium sticky top-0 bg-background py-1">{{ formatDate(date) }}</h4>
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
                <Button variant="ghost" @click="isRescheduleDialogOpen = false">
                    {{ t('public.reschedule.cancel') }}
                </Button>
                <Button @click="submitReschedule" :disabled="!selectedSlot">
                    {{ t('public.reschedule.submit') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

