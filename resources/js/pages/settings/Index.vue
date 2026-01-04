<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Moon, Sun, Lock } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { ref, onMounted } from 'vue';

const { t } = useTranslation();

// Dark Mode State
const isDark = ref(false);

onMounted(() => {
    // Check if dark mode is enabled from localStorage
    isDark.value = localStorage.getItem('theme') === 'dark';
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    }
});

const toggleDarkMode = (value: boolean) => {
    isDark.value = value;
    if (value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Password Change Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPasswordChange = () => {
    passwordForm.put(route('settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="t('nav.settings') || 'Settings'" />

    <AppShell>
        <template #header-title>
            <h1 class="text-2xl font-semibold">{{ t('settings.title') || 'Settings' }}</h1>
        </template>
        
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6 max-w-2xl">
            <!-- Theme Settings -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Sun class="h-5 w-5" />
                        {{ t('settings.theme') || 'Theme' }}
                    </CardTitle>
                    <CardDescription>{{ t('settings.themeDesc') || 'Choose between dark and light mode' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Moon class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <Label class="text-base">{{ t('settings.darkMode') || 'Dark Mode' }}</Label>
                                <p class="text-sm text-muted-foreground">
                                    {{ isDark ? (t('settings.darkModeEnabled') || 'Dark theme enabled') : (t('settings.lightModeEnabled') || 'Light theme enabled') }}
                                </p>
                            </div>
                        </div>
                        <Switch 
                            :checked="isDark" 
                            @update:checked="toggleDarkMode"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Password Change -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Lock class="h-5 w-5" />
                        {{ t('settings.changePassword') || 'Change Password' }}
                    </CardTitle>
                    <CardDescription>{{ t('settings.changePasswordDesc') || 'Update your account password' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitPasswordChange" class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="current_password">{{ t('settings.currentPassword') || 'Current Password' }}</Label>
                            <Input
                                id="current_password"
                                type="password"
                                v-model="passwordForm.current_password"
                                required
                                autocomplete="current-password"
                            />
                            <div v-if="passwordForm.errors.current_password" class="text-sm text-red-500">{{ passwordForm.errors.current_password }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">{{ t('settings.newPassword') || 'New Password' }}</Label>
                            <Input
                                id="password"
                                type="password"
                                v-model="passwordForm.password"
                                required
                                autocomplete="new-password"
                            />
                            <div v-if="passwordForm.errors.password" class="text-sm text-red-500">{{ passwordForm.errors.password }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">{{ t('settings.confirmPassword') || 'Confirm New Password' }}</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                v-model="passwordForm.password_confirmation"
                                required
                                autocomplete="new-password"
                            />
                            <div v-if="passwordForm.errors.password_confirmation" class="text-sm text-red-500">{{ passwordForm.errors.password_confirmation }}</div>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="passwordForm.processing">
                                {{ t('settings.updatePassword') || 'Update Password' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
