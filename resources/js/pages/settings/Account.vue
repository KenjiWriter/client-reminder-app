<script setup lang="ts">
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = page.props.auth.user;

const profileForm = useForm({
    _method: 'PUT',
    name: user.name,
    email: user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <SettingsLayout>
        <div class="space-y-12">
            <!-- Profile Information -->
            <section class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium">Dane profilu</h3>
                    <p class="text-sm text-muted-foreground">Zaktualizuj swoje imię i adres email.</p>
                </div>
                
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Imię i nazwisko</Label>
                        <Input id="name" v-model="profileForm.name" required autocomplete="name" />
                        <InputError :message="profileForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="profileForm.email" required autocomplete="username" />
                        <InputError :message="profileForm.errors.email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="profileForm.processing">Zapisz</Button>
                        <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600">Zapisano.</span>
                    </div>
                </form>
            </section>

            <!-- Password Update -->
             <section class="space-y-6">
                 <div>
                    <h3 class="text-lg font-medium">Zmiana hasła</h3>
                    <p class="text-sm text-muted-foreground">Dla bezpieczeństwa używaj długiego, unikalnego hasła.</p>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="current_password">Aktualne hasło</Label>
                        <Input id="current_password" type="password" v-model="passwordForm.current_password" autocomplete="current-password" />
                        <InputError :message="passwordForm.errors.current_password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Nowe hasło</Label>
                        <Input id="password" type="password" v-model="passwordForm.password" autocomplete="new-password" />
                        <InputError :message="passwordForm.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">Potwierdź hasło</Label>
                        <Input id="password_confirmation" type="password" v-model="passwordForm.password_confirmation" autocomplete="new-password" />
                        <InputError :message="passwordForm.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="passwordForm.processing">Zmień hasło</Button>
                        <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">Zapisano.</span>
                    </div>
                </form>
            </section>
        </div>
    </SettingsLayout>
</template>
