<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import axios from 'axios';
import SegmentedControl from '@/components/ui/segmented-control/SegmentedControl.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, ChevronLeft, ChevronRight, Calculator, Calendar as CalendarIcon, Clock, Trash2, UserPlus, Search } from 'lucide-vue-next';
import { format, startOfWeek, addDays, getDay, isSameDay, parseISO, startOfToday, addWeeks, subWeeks } from 'date-fns';
import { route } from 'ziggy-js';
import { useDebounceFn } from '@vueuse/core';

interface CalendarEvent {
    id: number;
    title: string;
    start: string;
    end: string;
    client_id: number;
    service_id?: number | null;
    duration_minutes: number;
    note: string | null;
    send_reminder: boolean;
}

interface CalendarClient {
    id: number;
    full_name: string;
    phone_e164: string;
}

interface Service {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

const props = withDefaults(defineProps<{
    events: CalendarEvent[];
    clients: CalendarClient[];
    allServices?: Service[];
}>(), {
    events: () => [],
    clients: () => [],
    allServices: () => [],
});

const { t, locale } = useTranslation();

// Localized formatting helpers
const formatHeaderDate = (date: Date) => {
    return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(date);
};

const formatDayName = (date: Date) => {
    return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date);
};

const formatHourLabel = (hour: number) => {
    return new Intl.DateTimeFormat(locale, { hour: 'numeric', hour12: locale === 'en' }).format(new Date().setHours(hour, 0, 0, 0));
};

// View mode state (only Week implemented for MVP)
const viewMode = ref('week');
const viewOptions = [
    { value: 'week', label: 'Week' },
];

// Calendar State
const currentStartDate = ref(startOfWeek(new Date(), { weekStartsOn: 1 })); // Monday start

const days = computed(() => {
    return Array.from({ length: 7 }, (_, i) => addDays(currentStartDate.value, i));
});

const nextWeek = () => {
    currentStartDate.value = addWeeks(currentStartDate.value, 1);
    const weekEnd = addDays(currentStartDate.value, 6);
    router.visit(route('calendar.index', { 
        start: currentStartDate.value.toISOString(), 
        end: weekEnd.toISOString() 
    }), { preserveState: true, preserveScroll: true });
};

const prevWeek = () => {
    currentStartDate.value = subWeeks(currentStartDate.value, 1);
    const weekEnd = addDays(currentStartDate.value, 6);
    router.visit(route('calendar.index', { 
        start: currentStartDate.value.toISOString(), 
        end: weekEnd.toISOString() 
    }), { preserveState: true, preserveScroll: true });
};

const goToToday = () => {
    currentStartDate.value = startOfWeek(new Date(), { weekStartsOn: 1 });
    const weekEnd = addDays(currentStartDate.value, 6);
    router.visit(route('calendar.index', { 
        start: currentStartDate.value.toISOString(), 
        end: weekEnd.toISOString() 
    }), { preserveState: true, preserveScroll: true });
};

// Create/Edit Form State
const isCreateOpen = ref(false);
const editingAppointmentId = ref<number | null>(null);

const form = useForm({
    client_id: '',
    service_id: 'none',
    date: format(new Date(), 'yyyy-MM-dd'),
    time: '12:00',
    starts_at: '', // Synced from date + time
    duration_minutes: 90,
    note: '',
    send_reminder: true,
});

// Watch service selection to auto-update duration
watch(() => form.service_id, (newServiceId) => {
    if (newServiceId && newServiceId !== 'none' && props.allServices) {
        const service = props.allServices.find(s => s.id === Number(newServiceId));
        if (service) {
            form.duration_minutes = service.duration_minutes;
        }
    }
});

// Watch date and time to update starts_at (with local timezone)
watch(() => form.date, (newDate) => {
    if (newDate && form.time) {
        // Create datetime in local timezone (not UTC)
        const tentativeDateTime = `${newDate} ${form.time}:00`;
        
        // Apply smart scheduling if we have a date and time
        const smartDateTime = findNextAvailableTime(newDate, form.time, editingAppointmentId.value);
        
        // Update form with smart time
        if (smartDateTime) {
            form.date = format(smartDateTime, 'yyyy-MM-dd');
            form.time = format(smartDateTime, 'HH:mm');
            form.starts_at = `${format(smartDateTime, 'yyyy-MM-dd')} ${format(smartDateTime, 'HH:mm')}:00`;
        } else {
            form.starts_at = tentativeDateTime;
        }
    }
});

watch(() => form.time, (newTime) => {
    if (form.date && newTime) {
        // Create datetime in local timezone (not UTC)
        const tentativeDateTime = `${form.date} ${newTime}:00`;
        
        // Apply smart scheduling if we have a date and time
        const smartDateTime = findNextAvailableTime(form.date, newTime, editingAppointmentId.value);
        
        // Update form with smart time
        if (smartDateTime) {
            form.date = format(smartDateTime, 'yyyy-MM-dd');
            form.time = format(smartDateTime, 'HH:mm');
            form.starts_at = `${format(smartDateTime, 'yyyy-MM-dd')} ${format(smartDateTime, 'HH:mm')}:00`;
        } else {
            form.starts_at = tentativeDateTime;
        }
    }
});

// Smart scheduling helper: Find next available time considering conflicts and buffers
const findNextAvailableTime = (dateStr: string, timeStr: string, excludeAppointmentId: number | null = null): Date | null => {
    const BUFFER_MINUTES = 20;
    
    try {
        // Parse the requested date and time
        const requestedDate = new Date(dateStr);
        const [hours, minutes] = timeStr.split(':').map(Number);
        requestedDate.setHours(hours, minutes, 0, 0);
        
        // Get all events for this day, excluding the one being edited
        const dayEvents = props.events
            .filter(event => {
                const eventDate = parseISO(event.start);
                return isSameDay(eventDate, requestedDate) && event.id !== excludeAppointmentId;
            })
            .sort((a, b) => a.start.localeCompare(b.start));
        
        let suggestedDateTime = requestedDate;
        
        // Check for conflicts with existing appointments (including buffer)
        for (const event of dayEvents) {
            const eventStart = parseISO(event.start);
            const eventEnd = parseISO(event.end);
            const eventEndWithBuffer = new Date(eventEnd.getTime() + BUFFER_MINUTES * 60000);
            
            // Check if requested time falls within appointment or its buffer zone
            if (suggestedDateTime >= eventStart && suggestedDateTime < eventEndWithBuffer) {
                // Adjust to next available time (after this appointment's buffer)
                suggestedDateTime = eventEndWithBuffer;
            }
        }
        
        // Return the suggested time if it's different from requested
        return suggestedDateTime.getTime() !== requestedDate.getTime() ? suggestedDateTime : null;
    } catch (error) {
        console.error('Error in findNextAvailableTime:', error);
        return null;
    }
};

const openCreateModal = () => {
    editingAppointmentId.value = null;
    form.reset();

    // Select first service by default if available
    if (props.allServices && props.allServices.length > 0) {
        const defaultService = props.allServices[0];
        form.service_id = String(defaultService.id);
        form.duration_minutes = defaultService.duration_minutes;
    } else {
        form.service_id = 'none';
        form.duration_minutes = 90;
    }

    form.date = format(new Date(), 'yyyy-MM-dd');
    form.time = '12:00';
    form.starts_at = `${format(new Date(), 'yyyy-MM-dd')} 12:00:00`;
    form.send_reminder = true;
    isCreateOpen.value = true;
};

const openCreateModalAtTime = (day: Date, hour: number) => {
    editingAppointmentId.value = null;
    form.reset();

    // Select first service by default if available
    if (props.allServices && props.allServices.length > 0) {
        const defaultService = props.allServices[0];
        form.service_id = String(defaultService.id);
        form.duration_minutes = defaultService.duration_minutes;
    } else {
        form.service_id = 'none';
        form.duration_minutes = 90;
    }
    
    // Find the smart time slot considering existing appointments
    const timeStr = `${hour.toString().padStart(2, '0')}:00`;
    const smartDateTime = findNextAvailableTime(format(day, 'yyyy-MM-dd'), timeStr, null);
    
    // Use smart time if available, otherwise use clicked time
    const finalDateTime = smartDateTime || day;
    if (!smartDateTime) {
        finalDateTime.setHours(hour, 0, 0, 0);
    }
    
    form.date = format(finalDateTime, 'yyyy-MM-dd');
    form.time = format(finalDateTime, 'HH:mm');
    form.starts_at = `${format(finalDateTime, 'yyyy-MM-dd')} ${format(finalDateTime, 'HH:mm')}:00`;
    form.send_reminder = true;
    isCreateOpen.value = true;
};

const editAppointment = (event: typeof props.events[0]) => {
    editingAppointmentId.value = event.id;
    form.client_id = String(event.client_id);
    form.service_id = event.service_id ? String(event.service_id) : 'none';
    form.date = format(parseISO(event.start), 'yyyy-MM-dd');
    form.time = format(parseISO(event.start), 'HH:mm');
    form.starts_at = event.start; // Set starts_at directly
    form.duration_minutes = event.duration_minutes;
    form.note = event.note || '';
    form.send_reminder = !!event.send_reminder;
    isCreateOpen.value = true;
};

const closeDialog = () => {
    isCreateOpen.value = false;
    form.reset();
    editingAppointmentId.value = null;
};

const submit = () => {
    // starts_at is already synced via watchers
    
    const transformedForm = form.transform((data) => ({
        ...data,
        service_id: data.service_id === 'none' ? null : data.service_id,
    }));

    if (editingAppointmentId.value) {
        transformedForm.put(route('appointments.update', editingAppointmentId.value), {
            onSuccess: () => closeDialog(),
        });
    } else {
        transformedForm.post(route('appointments.store'), {
            onSuccess: () => closeDialog(),
        });
    }
};

const deleteAppointment = () => {
    if (!editingAppointmentId.value) return;
    if (confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
        router.delete(route('appointments.destroy', editingAppointmentId.value), {
            onSuccess: () => closeDialog(),
        });
    }
};

const getEventsForDay = (day: Date) => {
    return props.events.filter(event => isSameDay(parseISO(event.start), day))
        .sort((a, b) => a.start.localeCompare(b.start));
};

const formatTime = (isoString: string) => {
    return format(parseISO(isoString), 'HH:mm');
};

// Quick Create Client Modal State
const isClientModalOpen = ref(false);
const clientForm = ref({
    full_name: '',
    phone: '',
    email: '',
    sms_opt_out: false,
});
const clientFormErrors = ref<Record<string, string>>({});
const isSubmittingClient = ref(false);

const openClientModal = () => {
    clientForm.value = {
        full_name: '',
        phone: '',
        email: '',
        sms_opt_out: false,
    };
    clientFormErrors.value = {};
    isClientModalOpen.value = true;
};

const closeClientModal = () => {
    isClientModalOpen.value = false;
    clientForm.value = {
        full_name: '',
        phone: '',
        email: '',
        sms_opt_out: false,
    };
    clientFormErrors.value = {};
};



const submitClient = async () => {
    if (isSubmittingClient.value) return;
    
    clientFormErrors.value = {};
    isSubmittingClient.value = true;
    
    try {
        const response = await axios.post(route('clients.store'), clientForm.value, {
            headers: {
                'Accept': 'application/json',
            },
        });
        
        const data = response.data;
        
        if (data.success && data.client) {
            // Add to clients list (reactive update)
            props.clients.push({
                id: data.client.id,
                full_name: data.client.full_name,
                phone_e164: data.client.phone_e164,
            });
            
            // Auto-select the new client
            form.client_id = String(data.client.id);
            
            closeClientModal();
        }
    } catch (error: any) {
        console.error('Error creating client:', error);
        
        if (error.response && error.response.status === 422) {
            clientFormErrors.value = error.response.data.errors;
        } else {
            clientFormErrors.value = { general: 'An error occurred while creating the client.' };
        }
    } finally {
        isSubmittingClient.value = false;
    }
};

// Search Logic
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);

const performSearch = useDebounceFn(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }

    try {
        isSearching.value = true;
        const response = await axios.get(route('appointments.search'), {
            params: { query }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(searchQuery, (newQuery) => {
    performSearch(newQuery);
});

const selectSearchResult = (appointment: any) => {
    // Clear search
    searchQuery.value = '';
    searchResults.value = [];

    // Navigate to date
    const date = parseISO(appointment.start_time);
    currentStartDate.value = startOfWeek(date, { weekStartsOn: 1 });
    
    // Refresh calendar view
     const weekEnd = addDays(currentStartDate.value, 6);
    router.visit(route('calendar.index', { 
        start: currentStartDate.value.toISOString(), 
        end: weekEnd.toISOString() 
    }), { preserveState: true, preserveScroll: true });
};

// Close search dropdown when clicking outside
const closeSearch = () => {
    searchResults.value = [];
};

</script>

<template>
    <Head title="Calendar" />


    <AppShell>
            <!-- Calendar Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-card px-6 py-4">
                <!-- Week Navigation -->
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" @click="prevWeek">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <div class="min-w-[140px] text-center text-sm font-medium">
                        {{ formatHeaderDate(currentStartDate) }}
                    </div>
                    <Button variant="outline" size="icon" @click="nextWeek">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Search Input -->
                 <div class="relative w-full max-w-sm">
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            :placeholder="t('common.search') || 'Search appointments...'"
                            class="pl-9 w-full"
                            v-model="searchQuery"
                        />
                    </div>
                    
                    <!-- Search Results Dropdown -->
                    <div 
                        v-if="searchResults.length > 0" 
                        class="absolute top-full left-0 right-0 z-50 mt-1 max-h-[300px] overflow-auto rounded-md border border-border bg-popover p-1 shadow-md"
                    >
                        <div
                            v-for="result in searchResults"
                            :key="result.id"
                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                            @click="selectSearchResult(result)"
                        >
                            <div class="flex flex-col gap-1 w-full">
                                <div class="flex justify-between font-medium">
                                    <span>{{ result.client.name }}</span>
                                    <span>{{ result.starts_at_formatted }}</span>
                                </div>
                                <div class="text-xs text-muted-foreground flex justify-between">
                                    <span>{{ result.client.phone }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="goToToday">{{ t('common.today') }}</Button>
                    <Dialog v-model:open="isCreateOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-green-600 hover:bg-green-700 text-white" @click="openCreateModal">
                                <Plus class="mr-2 h-4 w-4" /> {{ t('common.newAppointment') }}
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>{{ editingAppointmentId ? t('calendar.editAppointment') : t('calendar.addAppointment') }}</DialogTitle>
                                <DialogDescription>{{ t('calendar.updateDetails') }}</DialogDescription>
                            </DialogHeader>
                        <form @submit.prevent="submit" class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="service">{{ t('appointments.service') || 'Usługa' }}</Label>
                                <Select v-model="form.service_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Wybierz usługę (opcjonalnie)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Brak - niestandardowa</SelectItem>
                                        <SelectItem v-for="service in allServices" :key="service.id" :value="String(service.id)">
                                            {{ service.name }} ({{ service.duration_minutes }} min, {{ Number(service.price).toFixed(2) }} PLN)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.service_id" class="text-sm text-red-500">{{ form.errors.service_id }}</div>
                            </div>

                            <div class="grid gap-2">
                                <div class="flex items-center justify-between">
                                    <Label for="client">{{ t('appointments.client') }}</Label>
                                    <Button 
                                        type="button" 
                                        variant="ghost" 
                                        size="sm" 
                                        @click="openClientModal"
                                        class="h-7 text-xs"
                                    >
                                        <UserPlus class="mr-1 h-3 w-3" /> {{ t('common.newClient') || 'New Client' }}
                                    </Button>
                                </div>
                                <Select v-model="form.client_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="client in clients" :key="client.id" :value="String(client.id)">
                                            {{ client.full_name }} ({{ client.phone_e164 }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.client_id" class="text-sm text-red-500">{{ form.errors.client_id }}</div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="send_reminder" v-model="form.send_reminder" class="h-4 w-4" />
                                <Label for="send_reminder">{{ t('calendar.sendReminder') }}</Label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="date">{{ t('appointments.date') }}</Label>
                                    <Input id="date" type="date" v-model="form.date" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="time">{{ t('appointments.time') }}</Label>
                                    <Input id="time" type="time" v-model="form.time" required />
                                </div>
                            </div>
                             <div v-if="form.errors.starts_at" class="text-sm text-red-500">{{ form.errors.starts_at }}</div>

                            <div class="grid gap-2">
                                <Label for="duration">{{ t('appointments.duration') }}</Label>
                                <Input id="duration" type="number" v-model="form.duration_minutes" min="15" step="15" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="note">{{ t('appointments.note') }}</Label>
                                <Textarea id="note" v-model="form.note" placeholder="Treatment details..." />
                            </div>

                            <DialogFooter class="flex justify-between sm:justify-between">
                                <Button v-if="editingAppointmentId" type="button" variant="destructive" @click="deleteAppointment">
                                    {{ t('common.delete') }}
                                </Button>
                                <Button type="button" variant="outline" @click="closeDialog">{{ t('common.cancel') }}</Button>
                                <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                </div>
            </div>

            <!-- Quick Create Client Modal (Nested) -->
            <Dialog v-model:open="isClientModalOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ t('common.newClient') || 'New Client' }}</DialogTitle>
                        <DialogDescription>Add a new client to your system</DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitClient" class="grid gap-4 py-4">
                        <div v-if="clientFormErrors.general" class="text-sm text-red-500 p-2 bg-red-50 rounded">
                            {{ clientFormErrors.general }}
                        </div>
                        
                        <div class="grid gap-2">
                            <Label for="full_name">{{ t('clients.fullName') }}</Label>
                            <Input 
                                id="full_name" 
                                v-model="clientForm.full_name" 
                                required 
                                :class="{ 'border-red-500': clientFormErrors.full_name }"
                            />
                            <div v-if="clientFormErrors.full_name" class="text-sm text-red-500">{{ clientFormErrors.full_name }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">{{ t('clients.phone') }}</Label>
                            <Input 
                                id="phone" 
                                v-model="clientForm.phone" 
                                type="tel" 
                                placeholder="+48..." 
                                required 
                                :class="{ 'border-red-500': clientFormErrors.phone }"
                            />
                            <div v-if="clientFormErrors.phone" class="text-sm text-red-500">{{ clientFormErrors.phone }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">{{ t('clients.email') }} ({{ t('common.optional') || 'optional' }})</Label>
                            <Input 
                                id="email" 
                                v-model="clientForm.email" 
                                type="email" 
                                :class="{ 'border-red-500': clientFormErrors.email }"
                            />
                            <div v-if="clientFormErrors.email" class="text-sm text-red-500">{{ clientFormErrors.email }}</div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="closeClientModal">{{ t('common.cancel') }}</Button>
                            <Button type="submit" :disabled="isSubmittingClient">
                                {{ isSubmittingClient ? (t('common.saving') || 'Saving...') : t('common.save') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Calendar Content -->
            <div class="flex-1 overflow-auto p-6">
                <!-- Calendar Grid Card -->
                <div class="rounded-2xl border border-border bg-card shadow-sm p-4">


                <!-- Week Grid -->
                <div class="grid grid-cols-[80px_repeat(7,1fr)] gap-0">
                    <!-- Header Row -->
                    <div class="border-b border-border bg-muted/30"></div>
                    <div
                        v-for="day in days"
                        :key="day.toISOString()"
                        class="border-b border-l border-border p-3 text-center"
                        :class="{'bg-primary/5': isSameDay(day, new Date())}"
                    >
                        <div class="text-sm font-semibold" :class="{'text-primary': isSameDay(day, new Date())}">
                            {{ formatDayName(day) }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ format(day, 'd') }}
                        </div>
                    </div>

                    <!-- Time Grid Rows (8 AM - 11 PM) -->
                    <template v-for="hour in Array.from({length: 16}, (_, i) => i + 8)" :key="hour">
                        <!-- Time Label -->
                        <div class="border-b border-border p-2 text-right text-xs text-muted-foreground">
                            {{ formatHourLabel(hour) }}
                        </div>

                        <!-- Day Cells -->
                        <div
                            v-for="day in days"
                            :key="`${day.toISOString()}-${hour}`"
                            class="relative border-b border-l border-border min-h-[60px] hover:bg-muted/50 transition-colors cursor-pointer"
                            :class="{'bg-primary/5': isSameDay(day, new Date())}"
                            @click="openCreateModalAtTime(day, hour)"
                        >
                            <!-- Events for this hour/day -->
                            <div
                                v-for="event in getEventsForDay(day).filter(e => {
                                    const startHour = parseISO(e.start).getHours();
                                    return startHour === hour;
                                })"
                                :key="event.id"
                                class="absolute left-1 right-1 top-1 rounded-md p-2 text-xs cursor-pointer hover:shadow-md transition-shadow bg-event-upcoming border-l-2 border-event-upcoming-dot"
                                :style="{height: `${(event.duration_minutes / 60) * 60 - 4}px`}"
                                @click.stop="editAppointment(event)"
                            >
                                <div class="font-medium text-event-upcoming-dot">{{ event.title }}</div>
                                <div class="text-event-upcoming-dot/70 text-[10px] mt-0.5">
                                    {{ formatTime(event.start) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                </div>
            </div>
    </AppShell>
</template>
