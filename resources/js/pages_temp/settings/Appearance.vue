<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { useDark, useToggle } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

// Use VueUse for dark mode matching the user's request, or fallback to manual logic if not available.
// Assuming VueUse is used in this project or standard class switching. 
// If VueUse is not available, we can use simple class toggling.
// The user prompt mentioned "You can use @vueuse/core (useDark/useToggle) if available".

const isDark = useDark({
    selector: 'html',
    attribute: 'class',
    valueDark: 'dark',
    valueLight: '',
});
const toggleDark = useToggle(isDark);

const setMode = (mode: 'light' | 'dark' | 'auto') => {
    if (mode === 'light') {
        isDark.value = false;
        localStorage.theme = 'light';
    } else if (mode === 'dark') {
        isDark.value = true;
        localStorage.theme = 'dark';
    } else {
        localStorage.removeItem('theme');
        // Re-init auto detection logic if needed, simplify for now by just setting per system preference
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        isDark.value = systemDark;
    }
};
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
             <div>
                <h3 class="text-lg font-medium">{{ t('settings.appearance.title') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('settings.appearance.description') }}</p>
            </div>

            <div class="flex gap-4">
                <Button variant="outline" class="flex flex-col items-center h-24 w-24 gap-2" @click="setMode('light')" :class="{ 'border-primary bg-primary/5': !isDark }">
                    <Sun class="h-6 w-6" />
                    <span>{{ t('settings.appearance.light') }}</span>
                </Button>

                <Button variant="outline" class="flex flex-col items-center h-24 w-24 gap-2" @click="setMode('dark')" :class="{ 'border-primary bg-primary/5': isDark }">
                    <Moon class="h-6 w-6" />
                    <span>{{ t('settings.appearance.dark') }}</span>
                </Button>
            </div>
        </div>
    </SettingsLayout>
</template>

