<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { route } from 'ziggy-js';

const { t } = useTranslation();

interface Settings {
    timezone: string;
    sms_footer_note: string;
    reminder_hours: number;
}

const props = withDefaults(defineProps<{
    settings: Settings;
}>(), {
    settings: () => ({
        timezone: 'UTC',
        sms_footer_note: '',
        reminder_hours: 24,
    }),
});

const form = useForm({
    timezone: props.settings.timezone,
    sms_footer_note: props.settings.sms_footer_note,
    reminder_hours: props.settings.reminder_hours,
});

const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
    });
};

const timezones = [
    'UTC',
    'Europe/Warsaw',
    'Europe/London',
    'Europe/Paris',
    'America/New_York',
    'America/Los_Angeles',
];
</script>

<template>
    <Head :title="t('nav.settings')" />

    <AppShell>
        <template #header-title>
            <h1 class="text-2xl font-semibold">{{ t('settings.businessSettings') }}</h1>
        </template>
        
        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
            <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Regional Settings</CardTitle>
                        <CardDescription>Configure timezone for appointments</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-2">
                            <Label for="timezone">{{ t('settings.timezone') }}</Label>
                            <Select v-model="form.timezone">
                                <SelectTrigger>
                                    <SelectValue :placeholder="t('settings.timezone')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="tz in timezones" :key="tz" :value="tz">
                                        {{ tz }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.timezone" class="text-sm text-red-500">{{ form.errors.timezone }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>SMS Reminder Settings</CardTitle>
                        <CardDescription>Configure when and how SMS reminders are sent</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="reminder_hours">{{ t('settings.reminderHours') }}</Label>
                            <Input
                                id="reminder_hours"
                                type="number"
                                v-model.number="form.reminder_hours"
                                min="1"
                                max="72"
                                required
                            />
                            <div v-if="form.errors.reminder_hours" class="text-sm text-red-500">{{ form.errors.reminder_hours }}</div>
                            <p class="text-sm text-muted-foreground">Default: 24 hours (recommended)</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="sms_footer_note">{{ t('settings.smsFooterNote') }}</Label>
                            <Textarea
                                id="sms_footer_note"
                                v-model="form.sms_footer_note"
                                :placeholder="t('settings.smsFooterNote')"
                                rows="3"
                                maxlength="500"
                            />
                            <div v-if="form.errors.sms_footer_note" class="text-sm text-red-500">{{ form.errors.sms_footer_note }}</div>
                            <p class="text-sm text-muted-foreground">{{ form.sms_footer_note.length }}/500 characters</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        {{ t('common.save') }}
                    </Button>
                </div>
            </form>
        </div>
    </AppShell>
</template>
