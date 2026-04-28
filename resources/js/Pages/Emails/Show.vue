<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { ArrowLeft, AlertCircle, Reply, Paperclip, Download, FileAudio, FileVideo, FileImage, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import EmailForm from '@/components/Email/EmailForm.vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

interface Email {
    uid: string;
    subject: string;
    from: string;
    from_email: string;
    date: string;
    message_id: string;
    body_html: string;
    body_text: string;
    attachments?: Array<{
        index: number;
        name: string;
        mime: string;
    }>;
    error: string | null;
}

const props = defineProps<{
    email: Email;
    email_signature?: string;
}>();

const replyOpen = ref(false);

// Build the quoted body text for the reply
const replyInitialData = computed(() => {
    const quotedBody = props.email.body_text 
        ? props.email.body_text.replace(/\n/g, '<br>') 
        : '';
        
    return {
        to: props.email.from_email,
        subject: props.email.subject,
        message_id: props.email.message_id,
        // Pass only the raw quoted content — EmailForm.vue handles 
        // inserting the 5 empty lines + signature above this
        body: quotedBody
    };
});

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '—';
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateStr));
};

// Build the srcdoc content for the iframe.
// Injects a minimal CSS reset and respects sandboxing (no scripts).
const iframeSrcdoc = computed(() => {
    const baseStyle = `<style>
        body { background-color: #ffffff !important; color: #000000 !important; font-family: system-ui, sans-serif; font-size: 14px; line-height: 1.6; margin: 0; padding: 1rem; word-break: break-word; }
        a { color: #2563eb; }
        img { max-width: 100%; height: auto; }
        pre { white-space: pre-wrap; word-break: break-word; font-family: inherit; }
    </style>`;

    if (props.email.body_html) {
        return `<!DOCTYPE html><html><head><meta charset="utf-8"><base target="_blank">${baseStyle}</head><body>${props.email.body_html}</body></html>`;
    }
    // plain text fallback – wrapped in <pre> for formatting
    return `<!DOCTYPE html><html><head><meta charset="utf-8"><base target="_blank">${baseStyle}</head><body><pre>${props.email.body_text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre></body></html>`;
});
</script>

<template>
    <Head :title="email.subject || t('email.no_subject')" />

    <AppShell>
        <template #header-title>
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('emails.index')" class="flex-shrink-0">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <h1 class="text-xl font-semibold truncate">{{ email.subject || t('email.no_subject') }}</h1>
            </div>
        </template>

        <div class="flex flex-1 flex-col gap-4 p-4 lg:p-6 max-w-4xl mx-auto w-full overflow-y-auto pb-10">

            <!-- Email meta card -->
            <div class="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden flex-shrink-0">
                <div class="px-6 py-4 border-b border-border flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <p class="font-semibold text-foreground truncate">{{ email.from || email.from_email }}</p>
                        <p class="text-sm text-muted-foreground">{{ email.from_email }}</p>
                    </div>
                    <p class="text-sm text-muted-foreground flex-shrink-0">{{ formatDate(email.date) }}</p>
                </div>

                <!-- Body via sandboxed iframe – XSS-safe -->
                <div class="px-0">
                    <!-- HTML Body -->
                    <iframe
                        v-if="email.body_html || email.body_text"
                        :srcdoc="iframeSrcdoc"
                        sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin"
                        class="w-full h-[50vh] min-h-[500px] bg-white border-b border-gray-200 dark:border-gray-700"
                        style="display:block;"
                        title="Email content"
                    />
                    <div v-else class="flex flex-col items-center justify-center py-16 text-muted-foreground gap-2 border-b border-border">
                        <AlertCircle class="h-8 w-8 opacity-40" />
                        <p class="text-sm">{{ t('email.no_content') }}</p>
                    </div>
                </div>

                <!-- Attachments Section -->
                <div v-if="email.attachments && email.attachments.length > 0" class="px-6 py-4 bg-muted/20">
                    <h3 class="text-sm font-semibold flex items-center gap-2 mb-3 text-foreground">
                        <Paperclip class="h-4 w-4" />
                        {{ t('email.attachments', 'Załączniki') }} ({{ email.attachments.length }})
                    </h3>
                    
                    <div class="flex flex-col gap-3">
                        <div v-for="att in email.attachments" :key="att.index" class="flex flex-col gap-2 rounded-lg border bg-background p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <FileVideo v-if="att.mime.startsWith('video/')" class="h-8 w-8 text-blue-500 flex-shrink-0" />
                                    <FileImage v-else-if="att.mime.startsWith('image/')" class="h-8 w-8 text-green-500 flex-shrink-0" />
                                    <FileAudio v-else-if="att.mime.startsWith('audio/')" class="h-8 w-8 text-yellow-500 flex-shrink-0" />
                                    <FileText v-else-if="att.mime.includes('pdf') || att.mime.includes('text')" class="h-8 w-8 text-red-500 flex-shrink-0" />
                                    <Paperclip v-else class="h-8 w-8 text-muted-foreground flex-shrink-0" />
                                    
                                    <span class="text-sm font-medium truncate" :title="att.name">{{ att.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                    <a :href="route('emails.attachment', { uid: email.uid, index: att.index })" target="_blank"
                                       class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-8 px-3 gap-1">
                                        <Download class="h-4 w-4" />
                                        <span>{{ t('common.download', 'Pobierz') }}</span>
                                    </a>
                                </div>
                            </div>
                            <!-- Inline video player for mp4 and webm -->
                            <div v-if="att.mime.startsWith('video/')" class="mt-2 text-center bg-black/5 rounded overflow-hidden">
                                <video controls class="max-h-[60vh] max-w-full mx-auto" preload="metadata">
                                    <source :src="route('emails.attachment', { uid: email.uid, index: att.index })" :type="att.mime">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <!-- Inline image preview -->
                            <div v-if="att.mime.startsWith('image/')" class="mt-2 text-center bg-black/5 rounded overflow-hidden">
                                <img :src="route('emails.attachment', { uid: email.uid, index: att.index })" :alt="att.name" class="max-h-[60vh] max-w-full mx-auto object-contain">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reply section -->
            <div class="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden">
                <!-- Collapsed toggle -->
                <button
                    v-if="!replyOpen"
                    class="w-full flex items-center gap-3 px-6 py-4 hover:bg-muted/40 transition-colors text-left"
                    @click="replyOpen = true"
                >
                    <Reply class="h-4 w-4 text-primary flex-shrink-0" />
                    <span class="text-sm font-medium">{{ t('email.reply_to', { name: email.from || email.from_email }) }}</span>
                </button>

                <div v-else class="flex flex-col flex-1 min-h-[600px] border-t border-border">
                    <EmailForm
                        mode="reply"
                        :submit-url="route('emails.reply', email.uid)"
                        :email-signature="props.email_signature || ''"
                        :initial-data="replyInitialData"
                        @cancel="replyOpen = false"
                        @success="replyOpen = false"
                    />
                </div>
            </div>

        </div>
    </AppShell>
</template>
