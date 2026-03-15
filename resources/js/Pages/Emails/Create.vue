<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import EmailForm from '@/components/Email/EmailForm.vue';

// Receive the signature directly from EmailController@create()
const props = defineProps<{
    email_signature?: string;
}>();

const handleCancel = () => {
    router.visit(route('emails.index'));
};

const handleSuccess = () => {
    // Backend handles the redirect with flashed messages
};
</script>

<template>
    <Head title="Nowa wiadomość" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('emails.index')" class="flex-shrink-0">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <h1 class="text-xl font-semibold truncate">Nowa wiadomość</h1>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6 max-w-4xl mx-auto w-full pb-20 overflow-y-auto">
            <div class="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden flex flex-col flex-1 min-h-[600px]">
                <EmailForm 
                    mode="create" 
                    :submit-url="route('emails.store')"
                    :email-signature="props.email_signature || ''"
                    @cancel="handleCancel"
                    @success="handleSuccess"
                />
            </div>
        </div>
    </AppShell>
</template>
