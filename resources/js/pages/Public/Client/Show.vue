<script setup lang="ts">
import { ref, computed } from 'vue';
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
}

const props = withDefaults(defineProps<{
    client: Client;
    appointments: Appointment[];
}>(), {
    appointments: () => [],
});

const isOptedOut = ref(props.client.sms_opt_out);

const toggleOptOut = (checked: boolean) => {
    // Optimistic update
    isOptedOut.value = !checked;
    
    // Explicitly send the current button state to backend
    // If button is CHECKED (true), user wants reminders -> opt_out should be FALSE
    // If button is UNCHECKED (false), user disables -> opt_out should be TRUE
    
    router.post(route('public.client.toggle-opt-out', props.client.public_uid), {
        opt_out: !checked // Send the explicit status
    }, {
        preserveScroll: true,
        onError: () => {
             // Revert on error
             isOptedOut.value = !isOptedOut.value;
        },
        onFinish: () => {
            // No-op, props will update automatically if we wanted to rely on them, 
            // but local ref is smoother for 'clicked' behavior
        }
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
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold">{{ t('public.hello', { name: client.full_name }) }}</h1>
                <p class="text-muted-foreground">{{ t('public.viewUpcoming') }}</p>
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
                    <div class="flex items-center space-x-2">
                        <Checkbox 
                            id="sms-opt-in"
                            :checked="!isOptedOut" 
                            @update:checked="toggleOptOut"
                        />
                        <Label for="sms-opt-in" class="cursor-pointer">
                            {{ t('public.enableReminders') }}
                        </Label>
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
                                <div v-if="appointment.can_reschedule" class="pt-2">
                                    <Dialog v-model:open="isRescheduleDialogOpen">
                                        <DialogTrigger asChild>
                                            <Button variant="outline" size="sm" @click="openRescheduleDialog(appointment)">
                                                {{ t('public.reschedule.button') }}
                                            </Button>
                                        </DialogTrigger>
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

            <!-- Footer -->
            <div class="text-center text-sm text-muted-foreground pt-8">
                <p>{{ t('public.footerContact') }}</p>
            </div>
        </div>
    </div>
</template>
