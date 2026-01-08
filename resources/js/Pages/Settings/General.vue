<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { useTranslation } from '@/composables/useTranslation';
import { Image } from 'lucide-vue-next';

interface Props {
    settings: {
        app_name: string | null;
        app_logo: string | null;
    };
}

const props = defineProps<Props>();
const { t } = useTranslation();

const form = useForm({
    app_name: props.settings.app_name || '',
    app_logo: null as File | null,
});

const logoPreview = ref<string | null>(props.settings.app_logo);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.app_logo = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.post(route('settings.general.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Logo preview will be updated from props
            if (!form.app_logo) {
                logoPreview.value = props.settings.app_logo;
            }
        },
    });
};
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-medium">{{ t('settings.general.title') }}</h3>
                <p class="text-sm text-muted-foreground">{{ t('settings.general.description') }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Application Name -->
                <div class="space-y-2">
                    <Label for="app_name">{{ t('settings.general.app_name') }}</Label>
                    <Input 
                        id="app_name" 
                        v-model="form.app_name" 
                        :placeholder="t('settings.general.app_name_placeholder')"
                    />
                    <InputError :message="form.errors.app_name" />
                </div>

                <!-- Application Logo -->
                <div class="space-y-2">
                    <Label for="app_logo">{{ t('settings.general.app_logo') }}</Label>
                    
                    <!-- Logo Preview -->
                    <div v-if="logoPreview" class="mb-4">
                        <div class="relative inline-block">
                            <img 
                                :src="logoPreview" 
                                alt="Logo preview" 
                                class="h-24 w-24 rounded-lg object-cover border-2 border-border"
                            />
                        </div>
                    </div>

                    <!-- Placeholder if no logo -->
                    <div v-else class="mb-4 flex items-center justify-center h-24 w-24 rounded-lg border-2 border-dashed border-border bg-muted">
                        <Image class="h-8 w-8 text-muted-foreground" />
                    </div>

                    <Input 
                        id="app_logo" 
                        type="file" 
                        accept="image/jpeg,image/jpg,image/png,image/svg+xml"
                        @change="handleLogoChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('settings.general.app_logo_hint') }}
                    </p>
                    <InputError :message="form.errors.app_logo" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">
                        {{ t('settings.general.save') }}
                    </Button>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600">
                        {{ t('settings.general.saved') }}
                    </span>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>
