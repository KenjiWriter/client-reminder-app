<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
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
import { Plus, ChevronLeft, ChevronRight, Calculator, Calendar as CalendarIcon, Clock, Trash2 } from 'lucide-vue-next';
import { format, startOfWeek, addDays, getDay, isSameDay, parseISO, startOfToday, addWeeks, subWeeks } from 'date-fns';
import { route } from 'ziggy-js';

interface CalendarEvent {
    id: number;
    title: string;
    start: string;
    end: string;
    client_id: number;
    duration_minutes: number;
    note: string | null;
    send_reminder: boolean;
}

interface CalendarClient {
    id: number;
    full_name: string;
    phone_e164: string;
}

const props = withDefaults(defineProps<{
    events: CalendarEvent[];
    clients: CalendarClient[];
}>(), {
    events: () => [],
    clients: () => [],
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
    router.visit(route('calendar.index', { start: currentStartDate.value.toISOString() }), { preserveState: true, preserveScroll: true });
};

const prevWeek = () => {
    currentStartDate.value = subWeeks(currentStartDate.value, 1);
    router.visit(route('calendar.index', { start: currentStartDate.value.toISOString() }), { preserveState: true, preserveScroll: true });
};

const goToToday = () => {
    currentStartDate.value = startOfWeek(new Date(), { weekStartsOn: 1 });
    router.visit(route('calendar.index', { start: currentStartDate.value.toISOString() }), { preserveState: true, preserveScroll: true });
};

// Create/Edit Form State
const isCreateOpen = ref(false);
const editingAppointmentId = ref<number | null>(null);

const form = useForm({
    client_id: '',
    date: format(new Date(), 'yyyy-MM-dd'),
    time: '12:00',
    starts_at: '', // Synced from date + time
    duration_minutes: 60,
    note: '',
    send_reminder: true,
});

// Watch date and time to update starts_at (with local timezone)
watch(() => form.date, (newDate) => {
    if (newDate && form.time) {
        // Create datetime in local timezone (not UTC)
        form.starts_at = `${newDate} ${form.time}:00`;
    }
});

watch(() => form.time, (newTime) => {
    if (form.date && newTime) {
        // Create datetime in local timezone (not UTC)
        form.starts_at = `${form.date} ${newTime}:00`;
    }
});

const openCreateModal = () => {
    editingAppointmentId.value = null;
    form.reset();
    form.date = format(new Date(), 'yyyy-MM-dd');
    form.time = '12:00';
    form.starts_at = `${format(new Date(), 'yyyy-MM-dd')} 12:00:00`;
    form.duration_minutes = 60;
    form.send_reminder = true;
    isCreateOpen.value = true;
};

const openCreateModalAtTime = (day: Date, hour: number) => {
    editingAppointmentId.value = null;
    form.reset();
    form.date = format(day, 'yyyy-MM-dd');
    form.time = `${hour.toString().padStart(2, '0')}:00`;
    form.starts_at = `${format(day, 'yyyy-MM-dd')} ${hour.toString().padStart(2, '0')}:00:00`;
    form.duration_minutes = 60;
    form.send_reminder = true;
    isCreateOpen.value = true;
};

const editAppointment = (event: typeof props.events[0]) => {
    editingAppointmentId.value = event.id;
    form.client_id = String(event.client_id);
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
    if (editingAppointmentId.value) {
        form.put(route('appointments.update', editingAppointmentId.value), {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.post(route('appointments.store'), {
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
                                <Label for="client">{{ t('appointments.client') }}</Label>
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
