<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, Clock } from 'lucide-vue-next';
import { format } from 'date-fns';
import { route } from 'ziggy-js';

const { t, locale } = useTranslation();

interface Appointment {
    id: number;
    starts_at: string;
    duration_minutes: number;
    note: string | null;
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

const form = useForm({
    opt_out: props.client.sms_opt_out,
});

const toggleOptOut = () => {
    form.post(route('public.client.toggle-opt-out', props.client.public_uid), {
        preserveScroll: true,
        onSuccess: () => {
            form.opt_out = !form.opt_out;
        },
    });
};
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
                            :checked="!client.sms_opt_out" 
                            @click="toggleOptOut"
                            :disabled="form.processing"
                        />
                        <Label class="cursor-pointer" @click="toggleOptOut">
                            {{ t('public.enableReminders') }}
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <!-- Appointments List -->
            <div class="space-y-4">
                <h2 class="text-2xl font-semibold">{{ t('public.upcomingAppointments') }}</h2>
                
                <div v-if="appointments.length === 0" class="text-center py-12">
                    <Calendar class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-lg text-muted-foreground">{{ t('public.noAppointments') }}</p>
                </div>

                <Card v-for="appointment in appointments" :key="appointment.id" class="hover:shadow-md transition-shadow">
                    <CardContent class="pt-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary/10 p-3 rounded-lg">
                                    <Calendar class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-semibold">
                                        {{ new Intl.DateTimeFormat(locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(appointment.starts_at)) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-muted-foreground">
                                        {{ new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: 'numeric', hour12: locale === 'en' }).format(new Date(appointment.starts_at)) }}
                                        ({{ appointment.duration_minutes }} {{ t('common.minutes') }})
                                    </span>
                                </div>
                                <p v-if="appointment.note" class="text-sm text-muted-foreground mt-2 pl-6">
                                    {{ appointment.note }}
                                </p>
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
