<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { route } from 'ziggy-js';

const { t } = useTranslation();

interface Client {
    id: number;
    full_name: string;
    email: string | null;
    phone_e164: string;
}

const props = withDefaults(defineProps<{
    client?: Client;
}>(), {
    client: undefined,
});

const isEditing = !!props.client;

const form = useForm({
    full_name: props.client?.full_name || '',
    email: props.client?.email || '',
    phone: props.client?.phone_e164 || '',
});

const submit = () => {
    if (isEditing) {
        form.put(route('clients.update', props.client!.id));
    } else {
        form.post(route('clients.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Client' : 'Add Client'" />

    <AppShell>
        <template #header-title>
            <h1 class="text-2xl font-semibold">{{ client ? t('clients.editClient') : t('clients.addClient') }}</h1>
        </template>
        <div class="max-w-2xl mx-auto p-4 w-full">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="full_name">{{ t('clients.fullName') }}</Label>
                    <Input id="full_name" v-model="form.full_name" required placeholder="John Doe" />
                    <div v-if="form.errors.full_name" class="text-sm text-red-500">{{ form.errors.full_name }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('clients.email') }}</Label>
                    <Input id="email" type="email" v-model="form.email" placeholder="john@example.com" />
                    <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="phone_e164">{{ t('clients.phone') }}</Label>
                    <Input id="phone_e164" v-model="form.phone" type="tel" required placeholder="+48..." />
                    <p class="text-xs text-muted-foreground">Formats accepted: 123456789, +48123456789</p>
                    <div v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</div>
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <div class="flex items-center gap-4">
                        <Button type="button" variant="outline" @click="$inertia.visit(route('clients.index'))">{{ t('common.cancel') }}</Button>
                        <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppShell>
</template>

