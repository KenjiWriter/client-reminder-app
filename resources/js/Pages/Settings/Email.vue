<script setup lang="ts">
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';

const props = defineProps<{
    settings?: {
        email_sender_name: string | null;
        imap_host: string | null;
        imap_port: number | null;
        imap_username: string | null;
        imap_sent_folder: string | null;
        email_signature: string | null;
        has_imap_password: boolean;
        smtp_host: string | null;
        smtp_port: number | null;
        smtp_username: string | null;
        has_smtp_password: boolean;
    } | null;
}>();

import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const form = useForm({
    email_sender_name: props.settings?.email_sender_name || '',
    
    // Initial value for form submission - seeded value or empty string
    email_signature: props.settings?.email_signature || '',

    imap_host: props.settings?.imap_host || '',
    imap_port: props.settings?.imap_port || 993,
    imap_username: props.settings?.imap_username || '',
    imap_sent_folder: props.settings?.imap_sent_folder || '',
    imap_password: '', // Kept empty unless changing
    
    smtp_host: props.settings?.smtp_host || '',
    smtp_port: props.settings?.smtp_port || 465,
    smtp_username: props.settings?.smtp_username || '',
    smtp_password: '', // Kept empty unless changing
});

const submit = () => {
    form.put(route('settings.email.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('imap_password', 'smtp_password');
        }
    });
};

/**
 * Handle Quill initialization via the @ready event.
 */
const handleQuillReady = (quill: any) => {
    // Set initial content if it exists
    const initialContent = props.settings?.email_signature || '';
    if (initialContent) {
        quill.clipboard.dangerouslyPasteHTML(0, initialContent);
    }

    // Sync editor changes back to the inertia form
    quill.on('text-change', () => {
        form.email_signature = quill.root.innerHTML;
    });
};
</script>

<template>
    <Head title="Ustawienia Poczty" />
    <SettingsLayout>
        <div class="space-y-6 max-w-2xl">
            <div>
                <h3 class="text-lg font-medium">Ustawienia Poczty Email (IMAP/SMTP)</h3>
                <p class="text-sm text-muted-foreground">
                    Skonfiguruj połączenie z serwerem poczty. Pamiętaj, aby wprowadzić poprawne porty i poświadczenia dostępu dla IMAP (odbieranie) oraz SMTP (wysyłanie).
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- General Email Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold border-b pb-2">Dane Nadawcy</h4>
                    
                    <div class="space-y-2">
                        <Label for="email_sender_name">Nazwa Nadawcy (Opcjonalnie)</Label>
                        <Input id="email_sender_name" v-model="form.email_sender_name" placeholder="np. Gabinet XYZ" />
                        <InputError :message="form.errors.email_sender_name" />
                        <p class="text-xs text-muted-foreground mt-1">
                            Ta nazwa będzie widoczna jako wysyłający w skrzynce odbiorczej klienta. Jeśli puste, użyty zostanie adres email.
                        </p>
                    </div>

                    <div class="space-y-2 ql-custom-wrapper">
                        <Label for="email_signature">Stopka HTML</Label>
                        <!-- Note: No v-model here. Sync is manual via @ready and text-change listener -->
                        <QuillEditor 
                            contentType="html"
                            theme="snow" 
                            placeholder="Twoja stopka pojawi się tutaj po uruchomieniu seedera lub ręcznym wpisaniu..."
                            class="min-h-[200px]"
                            @ready="handleQuillReady"
                        />
                        <InputError :message="form.errors.email_signature" />
                        <p class="text-xs text-muted-foreground mt-1">
                            Stopka będzie automatycznie dołączana do każdej wysyłanej wiadomości.
                        </p>
                    </div>
                </div>

                <!-- IMAP Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold border-b pb-2">Poczta Przychodząca (IMAP)</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="imap_host">Serwer IMAP (Host)</Label>
                            <Input id="imap_host" v-model="form.imap_host" placeholder="np. imap.zoho.eu" />
                            <InputError :message="form.errors.imap_host" />
                        </div>
                        <div class="space-y-2">
                            <Label for="imap_port">Port IMAP</Label>
                            <Input id="imap_port" type="number" v-model="form.imap_port" placeholder="993" />
                            <InputError :message="form.errors.imap_port" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="imap_username">Nazwa użytkownika (Email)</Label>
                            <Input id="imap_username" v-model="form.imap_username" placeholder="kontakt@domena.pl" />
                            <InputError :message="form.errors.imap_username" />
                        </div>
                        <div class="space-y-2">
                            <Label for="imap_password">Hasło IMAP</Label>
                            <Input id="imap_password" type="password" v-model="form.imap_password" :placeholder="settings?.has_imap_password ? '******** (Zapisane - wprowadź by zmienić)' : 'Wprowadź hasło'" />
                            <InputError :message="form.errors.imap_password" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-2">
                            <Label for="imap_sent_folder">Nazwa folderu wysłanych (IMAP)</Label>
                            <Input id="imap_sent_folder" v-model="form.imap_sent_folder" placeholder="np. Sent, Wysłane, INBOX.Sent" />
                            <InputError :message="form.errors.imap_sent_folder" />
                            <p class="text-xs text-muted-foreground mt-1">
                                Pozostaw puste aby użyć domyślnego ('Sent'). Na serwerach polskich często to 'Wysłane' lub 'INBOX.Sent'.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SMTP Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold border-b pb-2">Poczta Wychodząca (SMTP)</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="smtp_host">Serwer SMTP (Host)</Label>
                            <Input id="smtp_host" v-model="form.smtp_host" placeholder="np. smtp.zoho.eu" />
                            <InputError :message="form.errors.smtp_host" />
                        </div>
                        <div class="space-y-2">
                            <Label for="smtp_port">Port SMTP</Label>
                            <Input id="smtp_port" type="number" v-model="form.smtp_port" placeholder="465" />
                            <InputError :message="form.errors.smtp_port" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="smtp_username">Nazwa użytkownika (Email)</Label>
                            <Input id="smtp_username" v-model="form.smtp_username" placeholder="kontakt@domena.pl" />
                            <InputError :message="form.errors.smtp_username" />
                        </div>
                        <div class="space-y-2">
                            <Label for="smtp_password">Hasło SMTP</Label>
                            <Input id="smtp_password" type="password" v-model="form.smtp_password" :placeholder="settings?.has_smtp_password ? '******** (Zapisane - wprowadź by zmienić)' : 'Wprowadź hasło'" />
                            <InputError :message="form.errors.smtp_password" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <Button :disabled="form.processing">Zapisz Ustawienia Poczty</Button>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Zapisano pomyślnie.</span>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>

<style>
/* Adjust Quill editor styling to fit the UI */
.ql-custom-wrapper .ql-toolbar {
    border: none;
    border-bottom: 1px solid hsl(var(--border) / 0.5);
    background-color: hsl(var(--muted) / 0.3);
    border-radius: var(--radius) var(--radius) 0 0;
    padding: 0.5rem;
    font-family: inherit;
}
.ql-custom-wrapper .ql-container {
    border: 1px solid hsl(var(--border));
    border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    font-family: inherit;
    font-size: 0.875rem; /* text-sm */
    background-color: transparent;
}
.ql-custom-wrapper .ql-editor {
    padding: 1rem;
    min-height: 200px;
}
</style>
