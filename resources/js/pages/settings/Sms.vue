<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

const props = defineProps<{
    settings?: {
        sms_api_token: string | null;
        sms_sender_name: string | null;
        sms_send_time: string | null;
    }
}>();

const form = useForm({
    sms_api_token: props.settings?.sms_api_token || '',
    sms_sender_name: props.settings?.sms_sender_name || 'GABINET',
    sms_send_time: props.settings?.sms_send_time || '09:00',
});

const submit = () => {
    form.put('/settings/sms', {
        preserveScroll: true,
    });
};
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-medium">{{ t('settings.sms.title') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('settings.sms.description') }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="sms_api_token">{{ t('settings.sms.token_label') }}</Label>
                    <Input id="sms_api_token" type="password" v-model="form.sms_api_token" placeholder="Twój token API" />
                    <InputError :message="form.errors.sms_api_token" />
                    <p class="text-xs text-muted-foreground">{{ t('settings.sms.token_desc') }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="sms_sender_name">{{ t('settings.sms.sender_label') }}</Label>
                    <Input id="sms_sender_name" v-model="form.sms_sender_name" placeholder="np. GABINET" />
                    <InputError :message="form.errors.sms_sender_name" />
                    <p class="text-xs text-muted-foreground">{{ t('settings.sms.sender_desc') }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="sms_send_time">{{ t('settings.sms.time_label') }}</Label>
                    <Input id="sms_send_time" type="time" v-model="form.sms_send_time" />
                    <InputError :message="form.errors.sms_send_time" />
                    <p class="text-xs text-muted-foreground">{{ t('settings.sms.time_desc') }}</p>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">{{ t('settings.sms.save') }}</Button>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600">{{ t('settings.sms.saved') }}</span>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>
