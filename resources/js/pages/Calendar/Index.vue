<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
import { Plus, ChevronLeft, ChevronRight, Calculator, Calendar as CalendarIcon, Clock } from 'lucide-vue-next';
import { format, startOfWeek, addDays, getDay, isSameDay, parseISO, startOfToday, addWeeks, subWeeks } from 'date-fns';

const props = defineProps<{
    events: Array<{
        id: number;
        title: string;
        start: string;
        end: string;
        client_id: number;
        note: string | null;
        send_reminder: boolean;
    }>;
    clients: Array<{
        id: number;
        full_name: string;
        phone_e164: string;
    }>;
}>;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Calendar',
        href: '/calendar',
    },
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

// Create Form State
const isCreateOpen = ref(false);
const form = useForm({
    client_id: '',
    date: format(new Date(), 'yyyy-MM-dd'),
    time: '12:00',
    duration_minutes: 60,
    note: '',
    send_reminder: true,
});

const submit = () => {
    const starts_at = `${form.date}T${form.time}:00`;
    
    form.transform((data) => ({
        ...data,
        starts_at: starts_at,
    })).post(route('appointments.store'), {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
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

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" @click="prevWeek">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <h2 class="text-xl font-semibold">
                        {{ format(currentStartDate, 'MMMM yyyy') }}
                    </h2>
                    <Button variant="outline" size="icon" @click="nextWeek">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>

                <Dialog v-model:open="isCreateOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" /> New Appointment
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Add Appointment</DialogTitle>
                            <DialogDescription>
                                Schedule a new visit for a client.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="client">Client</Label>
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
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="date">Date</Label>
                                    <Input id="date" type="date" v-model="form.date" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="time">Time</Label>
                                    <Input id="time" type="time" v-model="form.time" required />
                                </div>
                            </div>
                             <div v-if="form.errors.starts_at" class="text-sm text-red-500">{{ form.errors.starts_at }}</div>

                            <div class="grid gap-2">
                                <Label for="duration">Duration (minutes)</Label>
                                <Input id="duration" type="number" v-model="form.duration_minutes" min="15" step="15" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="note">Note (Optional)</Label>
                                <Textarea id="note" v-model="form.note" placeholder="Treatment details..." />
                            </div>

                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Save Appointment</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <div v-for="day in days" :key="day.toISOString()" class="min-h-[200px] border rounded-lg p-2 bg-background flex flex-col gap-2">
                    <div class="text-center border-b pb-2 font-medium" :class="{'text-primary': isSameDay(day, new Date())}">
                        {{ format(day, 'EEE, MMM d') }}
                    </div>
                    
                    <div class="flex-1 flex flex-col gap-2">
                        <div v-for="event in getEventsForDay(day)" :key="event.id" class="p-2 rounded bg-primary/10 border border-primary/20 text-xs">
                           <div class="font-bold">{{ formatTime(event.start) }} - {{ event.title }}</div>
                           <div class="text-muted-foreground line-clamp-1">{{ event.note }}</div>
                        </div>
                         <div v-if="getEventsForDay(day).length === 0" class="text-center text-muted-foreground text-xs py-4 italic">
                            No appointments
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
