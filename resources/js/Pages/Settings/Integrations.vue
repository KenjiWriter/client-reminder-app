<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Calendar as CalendarIcon, Check, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import { useTranslation } from '@/composables/useTranslation';

interface PageProps {
    google_calendar_email: string | null;
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}

const { t } = useTranslation();
const page = usePage<PageProps>();
const googleEmail = computed(() => page.props.google_calendar_email);
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);

const connect = () => {
    window.location.href = route('settings.integrations.google.connect');
};

const disconnect = () => {
    if (confirm(t('settings.integrations.google.confirm_disconnect'))) {
        router.post(route('settings.integrations.google.disconnect'));
    }
};

const sync = () => {
    if (confirm('Start manual synchronization of all future appointments?')) {
        router.post(route('settings.integrations.google.sync'));
    }
};
</script>

<template>
    <Head :title="`Settings - ${t('settings.integrations.title')}`" />

    <SettingsLayout>
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-medium">{{ t('settings.integrations.title') }}</h3>
                <p class="text-sm text-muted-foreground">
                    {{ t('settings.integrations.description') }}
                </p>
            </div>

            <!-- Flash Messages -->
            <Alert v-if="success" class="bg-green-50 border-green-200 text-green-800">
                <Check class="h-4 w-4" />
                <AlertTitle>{{ t('common.success') || 'Success' }}</AlertTitle>
                <AlertDescription>{{ success }}</AlertDescription>
            </Alert>

            <Alert v-if="error" variant="destructive">
                <XCircle class="h-4 w-4" />
                <AlertTitle>{{ t('common.error') || 'Error' }}</AlertTitle>
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <div class="flex items-center space-x-4">
                        <div class="p-2 bg-primary/10 rounded-full">
                            <CalendarIcon class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <CardTitle>{{ t('settings.integrations.google.title') }}</CardTitle>
                            <CardDescription>
                                {{ t('settings.integrations.google.description') }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="googleEmail" class="flex items-center justify-between p-4 border rounded-lg bg-emerald-50/50 border-emerald-200">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-emerald-100 text-emerald-600">
                                <Check class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="font-medium text-sm text-emerald-900">{{ t('settings.integrations.google.connected_as') }}</p>
                                <p class="text-sm text-emerald-700 font-medium">{{ googleEmail }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                             <Button variant="secondary" size="sm" @click="sync">
                                {{ t('settings.integrations.google.sync_button') || 'Sync Now' }}
                            </Button>
                            <Button variant="outline" size="sm" @click="disconnect">
                                {{ t('settings.integrations.google.disconnect_button') }}
                            </Button>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-start gap-4">
                        <p class="text-sm text-muted-foreground">
                            {{ t('settings.integrations.google.connect_description') }}
                        </p>
                        <Button @click="connect">
                            {{ t('settings.integrations.google.connect_button') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </SettingsLayout>
</template>
