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
import { Plus, ChevronLeft, ChevronRight, Calculator, Calendar as CalendarIcon, Clock, Trash2, UserPlus, Search, RefreshCw } from 'lucide-vue-next';
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
    // If we just finished dragging, don't open the modal
    if (dragState.value.wasDragging) {
        dragState.value.wasDragging = false;
        return;
    }

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
    // If we just finished dragging, don't open the modal
    if (dragState.value.wasDragging) {
        dragState.value.wasDragging = false;
        return;
    }

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

// --- Interactive DnD & Resize Logic ---

const MIN_DURATION = 15;
const PIXELS_PER_MINUTE = 1; // 60px per hour means 1px per minute
const SNAP_MINUTES = 15;

// State
const dragState = ref({
    isDragging: false,
    eventId: null as number | null,
    originalStart: null as Date | null,
    originalDuration: 0,
    startX: 0,
    startY: 0,
    initialTop: 0, // current top in pixels
    newTop: 0,
    newDayIndex: 0, // 0-6 relative to current view
    ghostHeight: 0,
    wasDragging: false,
});

const resizeState = ref({
    isResizing: false,
    isTopHandle: false,
    eventId: null as number | null,
    originalStart: null as Date | null,
    originalDuration: 0,
    startY: 0,
    initialHeight: 0, // in pixels
    initialTop: 0,
    newHeight: 0,
    newTop: 0, // only changes if top resizing
});

// Helper: Calculate pixels from start of day (6:00 AM)
// We assume day starts at 6:00
const DAY_START_HOUR = 6;

const getTopFromTime = (date: Date): number => {
    const hours = date.getHours();
    const minutes = date.getMinutes();
    const minutesFromStart = (hours - DAY_START_HOUR) * 60 + minutes;
    return minutesFromStart * PIXELS_PER_MINUTE;
};

// Helper: Calculate Time from pixels
const getTimeFromTop = (topPixels: number, dayDate: Date): Date => {
    const minutesFromStart = Math.round(topPixels / PIXELS_PER_MINUTE);
    // Snap
    const snappedMinutes = Math.round(minutesFromStart / SNAP_MINUTES) * SNAP_MINUTES;
    
    const newDate = new Date(dayDate);
    newDate.setHours(DAY_START_HOUR, 0, 0, 0);
    newDate.setMinutes(snappedMinutes);
    return newDate;
};

// Start Drag
const startDrag = (event: MouseEvent, appointment: CalendarEvent, dayIndex: number) => {
    if (resizeState.value.isResizing) return; // Priority to resize
    
    // Prevent dragging if clicking buttons/text
    // (Checked via helper or just simple capture)
    
    const startDate = parseISO(appointment.start);
    const top = getTopFromTime(startDate);
    
    dragState.value = {
        isDragging: true,
        eventId: appointment.id,
        originalStart: startDate,
        originalDuration: appointment.duration_minutes,
        startX: event.clientX,
        startY: event.clientY,
        initialTop: top,
        newTop: top,
        newDayIndex: dayIndex,
        ghostHeight: appointment.duration_minutes * PIXELS_PER_MINUTE,
        wasDragging: false,
    };

    document.body.style.cursor = 'grabbing';
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};

// Start Resize
const startResize = (event: MouseEvent, appointment: CalendarEvent, isTop: boolean) => {
    event.stopPropagation(); // prevent drag
    
    const startDate = parseISO(appointment.start);
    const top = getTopFromTime(startDate);
    const height = appointment.duration_minutes * PIXELS_PER_MINUTE;

    resizeState.value = {
        isResizing: true,
        isTopHandle: isTop,
        eventId: appointment.id,
        originalStart: startDate,
        originalDuration: appointment.duration_minutes,
        startY: event.clientY,
        initialHeight: height,
        initialTop: top,
        newHeight: height,
        newTop: top,
    };
    
    document.body.style.cursor = 'ns-resize';
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};

const onMouseMove = (event: MouseEvent) => {
    if (dragState.value.isDragging) {
        const deltaY = event.clientY - dragState.value.startY;
        
        // Calculate new top (snapped visually for ghost?)
        // Let's keep smooth for ghost, snap for logic
        let rawTop = dragState.value.initialTop + deltaY;
        
        // Boundaries (0 to 16 hours * 60 = 960px)
        // 8 AM to 11 PM = 15 hours? grid is 8..23 = 16 slots?
        // Let's assume height is fixed
        
        dragState.value.newTop = rawTop;
        
        // Horizontal: Determine column
        // This requires knowing column widths. 
        // Simplification: We track which element we are hovering or just rely on standard cursor
        // For now, simpler implementation: Drop is only valid if we hover a specific day column
        // We can track `mouseover` on day columns to update `newDayIndex`.
        
    } else if (resizeState.value.isResizing) {
        const deltaY = event.clientY - resizeState.value.startY;
        
        if (resizeState.value.isTopHandle) {
             const newHeight = Math.max(MIN_DURATION * PIXELS_PER_MINUTE, resizeState.value.initialHeight - deltaY);
             // When top resizing, Top moves, Height changes
             // Delta positive (down) -> Top increases, Height decreases
             
             // Snap logic needed for UI feedback
             resizeState.value.newHeight = newHeight;
             resizeState.value.newTop = resizeState.value.initialTop + (resizeState.value.initialHeight - newHeight);
             
        } else {
            // Bottom resizing
            const newHeight = Math.max(MIN_DURATION * PIXELS_PER_MINUTE, resizeState.value.initialHeight + deltaY);
            resizeState.value.newHeight = newHeight;
        }
    }
};

const onMouseUp = async (event: MouseEvent) => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
    document.body.style.cursor = '';
    
    if (dragState.value.isDragging) {
        // Calculate distance moved
        const dist = Math.sqrt(Math.pow(event.clientX - dragState.value.startX, 2) + Math.pow(event.clientY - dragState.value.startY, 2));
        const wasRealDrag = dist > 5;

        // Set flag to prevent click event on grid if it was a real drag
        if (wasRealDrag) {
            dragState.value.wasDragging = true;
            // Clear flag after a short delay to allow click event to fire and be ignored
            setTimeout(() => { dragState.value.wasDragging = false; }, 100);
            await handleDrop();
        } else {
             dragState.value.wasDragging = false;
        }
        
        dragState.value.isDragging = false;
    }
    
    if (resizeState.value.isResizing) {
        await handleResizeEnd();
        resizeState.value.isResizing = false;
    }
};

const handleDrop = async () => {
    const { newTop, newDayIndex, eventId } = dragState.value;
    const day = days.value[newDayIndex]; // using the computed `days`
    
    const newStart = getTimeFromTop(newTop, day);
    
    // Check if changed
    if (newStart.getTime() === dragState.value.originalStart?.getTime() && 
        newDayIndex === days.value.findIndex(d => isSameDay(d, dragState.value.originalStart!))) {
        return; // No change
    }
    
    await updateAppointment(eventId!, newStart, dragState.value.originalDuration);
};

const handleResizeEnd = async () => {
    const { newTop, newHeight, eventId, isTopHandle } = resizeState.value;
    
    let newStart = resizeState.value.originalStart!;
    let newDuration = Math.round(newHeight / PIXELS_PER_MINUTE);
    
    // Snap duration
    newDuration = Math.round(newDuration / SNAP_MINUTES) * SNAP_MINUTES;
    
    if (isTopHandle) {
         // Start time changed
         // We need to calculate start time based on the VISUAL new top
         // NOTE: The `newTop` in state was calculated from (initialTop + delta)
         // We should recalculate start based on that
         
         // Start date is same day
         const day = startOfToday(); // Just need the hours
         // Actually we need the real day
         const currentDay = startOfDay(newStart); 
         // Helper: getTimeFromTop uses 'hours' so it returns a date on "today". We should mix it with currentDay.
         
         const timePart = getTimeFromTop(resizeState.value.newTop, currentDay);
         newStart = timePart;
    }
    
    await updateAppointment(eventId!, newStart, newDuration);
};

const updateAppointment = async (id: number, start: Date, duration: number) => {
    try {
        const response = await axios.patch(route('appointments.quick-update', id), {
            starts_at: format(start, 'yyyy-MM-dd HH:mm:00'),
            duration_minutes: duration
        });
        
        // Optimistic / Success update
        // We can reload or just splice the event array
        // Reloading is safer for full sync
        router.reload({ only: ['events'] });
        
    } catch (error: any) {
        console.error('Update failed', error);
        // Snap back animation or alert
        if (error.response?.status === 422) {
             alert(t('calendar.overlapError') || 'Conflict detected! Appointment reverted.');
        } else {
             alert(t('common.error') || 'An error occurred.');
        }
    }
};

// Helper for date utils
const startOfDay = (date: Date) => {
    const d = new Date(date);
    d.setHours(0,0,0,0);
    return d;
}

// Track mouse hover over columns
const setHoverDay = (index: number) => {
    if (dragState.value.isDragging) {
        dragState.value.newDayIndex = index;
    }
};

const isSyncing = ref(false);

const syncCalendar = () => {
    if (isSyncing.value) return;
    if (!confirm('Sync all future appointments to Google Calendar?')) return;

    isSyncing.value = true;
    router.post(route('settings.integrations.google.sync'), {}, {
        onFinish: () => {
            isSyncing.value = false;
        }
    });
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
                     <Button variant="outline" @click="syncCalendar" title="Sync to Google Calendar">
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isSyncing }" />
                    </Button>
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
                                <Input id="duration" type="number" v-model="form.duration_minutes" min="15" step="5" />
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


                <!-- Week Grid (Column-based) -->
                <div class="grid grid-cols-[80px_repeat(7,1fr)] bg-background select-none" 
                     @mouseleave="() => { /* Optional: cancel drag if leaves component? */ }">
                    
                    <!-- Top Left Empty -->
                    <div class="border-b border-r border-border p-3"></div>

                    <!-- Header Row (Days) -->
                    <div
                        v-for="(day, index) in days"
                        :key="day.toISOString()"
                        class="border-b border-r border-border p-3 text-center bg-muted/30"
                        :class="{'bg-primary/10': isSameDay(day, new Date())}"
                    >
                        <div class="text-sm font-semibold" :class="{'text-primary': isSameDay(day, new Date())}">
                            {{ formatDayName(day) }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ format(day, 'd') }}
                        </div>
                    </div>

                    <!-- Time Labels Column -->
                    <div class="border-r border-border">
                         <div v-for="hour in Array.from({length: 18}, (_, i) => i + 6)" :key="hour" class="h-[60px] border-b border-border p-2 text-right text-xs text-muted-foreground relative">
                            <span class="-top-3 relative">{{ formatHourLabel(hour) }}</span>
                        </div>
                    </div>

                    <!-- Day Columns -->
                    <!-- Note: We must track MouseOver for Drag Destination -->
                    <div
                        v-for="(day, index) in days"
                        :key="`col-${day.toISOString()}`"
                        class="relative border-r border-border"
                        :class="{'bg-primary/5': isSameDay(day, new Date())}"
                        @mouseenter="setHoverDay(index)"
                    >
                        <!-- Background Grid Lines (1 hour slots) -->
                         <!-- Click to create new appointment -->
                        <div 
                            v-for="hour in Array.from({length: 18}, (_, i) => i + 6)" 
                            :key="`slot-${hour}`" 
                            class="h-[60px] border-b border-border/50 hover:bg-muted/50 transition-colors cursor-pointer box-border"
                            @click="openCreateModalAtTime(day, hour)"
                        ></div>

                        <!-- Ghost Element (Drag Preview) -->
                         <div
                            v-if="dragState.isDragging && dragState.newDayIndex === index"
                            class="absolute w-[95%] left-[2.5%] rounded-md bg-primary/20 border-2 border-primary border-dashed z-20 pointer-events-none transition-all duration-75 ease-linear"
                            :style="{
                                top: `${dragState.newTop}px`,
                                height: `${dragState.ghostHeight}px`
                            }"
                        >
                            <div class="text-xs font-semibold p-1 text-primary">
                                {{ format(getTimeFromTop(dragState.newTop, day), 'HH:mm') }}
                            </div>
                        </div>

                        <!-- Events -->
                        <!-- Filter events for this day -->
                        <!-- NOTE: We need to filter and position them absolutely -->
                        <template v-for="event in getEventsForDay(day)" :key="event.id">
                            <!-- Helper to calculate top -->
                            <!-- We must hide the event being dragged to show ghost instead? Or dim it? -->
                            <!-- Let's dim it: opacity-50 -->
                            <div
                                v-if="getTopFromTime(parseISO(event.start)) >= 0"
                                class="absolute w-[95%] left-[2.5%] rounded-md p-2 text-xs hover:shadow-lg transition-shadow bg-event-upcoming border-l-2 border-event-upcoming-dot select-none group z-10"
                                :class="{
                                    'opacity-30': dragState.isDragging && dragState.eventId === event.id,
                                    'z-30': resizeState.isResizing && resizeState.eventId === event.id
                                }"
                                :style="{
                                    top: `${resizeState.isResizing && resizeState.eventId === event.id && resizeState.isTopHandle ? resizeState.newTop : getTopFromTime(parseISO(event.start))}px`,
                                    height: `${resizeState.isResizing && resizeState.eventId === event.id ? resizeState.newHeight : (event.duration_minutes * PIXELS_PER_MINUTE)}px`,
                                    cursor: 'grab'
                                }"
                                @mousedown.stop="startDrag($event, event, index)"
                                @click.stop="editAppointment(event)"
                            >
                                <!-- Top Resize Handle -->
                                <div 
                                    class="absolute top-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover:opacity-100 hover:bg-primary/20 z-50 rounded-t-md"
                                    @mousedown.stop="startResize($event, event, true)"
                                    @click.stop
                                ></div>

                                <div class="font-medium text-event-upcoming-dot pointer-events-none truncate">
                                    {{ event.title }}
                                </div>
                                <div class="text-event-upcoming-dot/70 text-[10px] mt-0.5 pointer-events-none flex items-center gap-1">
                                    <Clock class="w-3 h-3" />
                                    <!-- Dynamic Time Label during Resize/Drag -->
                                    <span v-if="resizeState.isResizing && resizeState.eventId === event.id">
                                        {{ resizeState.isTopHandle 
                                            ? format(getTimeFromTop(resizeState.newTop, day), 'HH:mm')
                                            : format(parseISO(event.start), 'HH:mm') 
                                        }} - 
                                        {{ resizeState.isTopHandle
                                            ? format(parseISO(event.end), 'HH:mm')
                                            : format(getTimeFromTop(getTopFromTime(parseISO(event.start)) + resizeState.newHeight, day), 'HH:mm')
                                        }}
                                    </span>
                                    <span v-else>
                                        {{ formatTime(event.start) }}
                                    </span>
                                </div>

                                <!-- Bottom Resize Handle -->
                                <div 
                                    class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover:opacity-100 hover:bg-primary/20 z-50 rounded-b-md"
                                    @mousedown.stop="startResize($event, event, false)"
                                    @click.stop
                                ></div>
                            </div>
                        </template>
                    </div>
                </div>
                </div>
            </div>
    </AppShell>
</template>

