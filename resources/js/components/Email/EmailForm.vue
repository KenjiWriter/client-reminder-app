<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

const quillInstance = ref<any>(null);

import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps<{
    mode: 'create' | 'reply';
    submitUrl: string;
    emailSignature?: string;  // passed explicitly from parent page
    initialData?: {
        to?: string;
        cc?: string;
        subject?: string;
        body?: string;  // raw quoted body for reply mode
        message_id?: string;
    };
}>();

const emit = defineEmits(['cancel', 'success']);

// 5 empty Quill paragraphs = comfortable typing space above signature
const EMPTY_LINES = '<p><br></p>'.repeat(5);

const form = useForm({
    to: props.initialData?.to || '',
    cc: props.initialData?.cc || '',
    subject: props.initialData?.subject || '',
    body: '',  // intentionally empty — assembled below in onReady
    message_id: props.initialData?.message_id || '',
    attachments: [] as File[],
});

import { usePage } from '@inertiajs/vue3';
const page = usePage();

/**
 * Handle Quill initialization via the @ready event.
 */
const handleQuillReady = (quill: any) => {
    // console.warn('[EmailForm] handleQuillReady triggered.');
    quillInstance.value = quill;
    
    // Clear editor completely before injection to avoid appending to existing <p><br></p>
    quill.root.innerHTML = '';

    // Fallback order: direct prop -> global page settings -> empty
    const signature = props.emailSignature ?? (page.props.settings as any)?.email_signature ?? '';
    
    // Build the content bits based on mode
    const blocks: string[] = [EMPTY_LINES, signature];
    
    if (props.mode === 'reply') {
        const quotedBody = props.initialData?.body ?? '';
        blocks.push(`<br><hr><br><strong>${t('email.original_message')}</strong><br>${quotedBody}`);
    }

    // Join pieces and clean up any accidental "undefined" strings leaking from somewhere
    let finalHtml = blocks.join('');
    finalHtml = finalHtml.replace(/undefined/g, '');

    // Paste cleaned HTML
    quill.clipboard.dangerouslyPasteHTML(0, finalHtml);
    
    // Sync the initial content to the form
    form.body = quill.root.innerHTML;

    // Place cursor at the very top (first of the 5 empty lines)
    quill.setSelection(0, 0);

    // Keep form.body in sync with every subsequent edit
    quill.on('text-change', () => {
        form.body = quill.root.innerHTML;
    });
};

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        form.attachments = Array.from(target.files);
    }
};

const submit = () => {
    if (quillInstance.value) {
        form.body = quillInstance.value.getHTML();
    }
    form.post(props.submitUrl, {
        forceFormData: true,
        onSuccess: () => {
            if (props.mode === 'reply') {
                form.reset('body', 'attachments');
            }
            emit('success');
        }
    });
};
</script>

<template>
    <div class="flex flex-col flex-1 h-full w-full">
        <form @submit.prevent="submit" class="flex flex-col flex-1 h-full">
            
            <!-- Metadata inputs -->
            <div class="px-6 py-4 border-b border-border flex flex-col gap-4 flex-shrink-0">
                <!-- To -->
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                    <Label for="to" class="text-muted-foreground w-16 whitespace-nowrap">{{ t('email.to') }}</Label>
                    <div class="w-full">
                        <Input 
                            id="to" 
                            v-model="form.to" 
                            type="text" 
                            placeholder="adres@email.com, drugi@email.com" 
                            class="border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 px-0 rounded-none bg-transparent"
                            :readonly="mode === 'reply'"
                        />
                        <p v-if="form.errors.to" class="text-xs text-destructive mt-1">{{ form.errors.to }}</p>
                    </div>
                </div>

                <!-- CC -->
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center border-t border-border/50 pt-4">
                    <Label for="cc" class="text-muted-foreground w-16 whitespace-nowrap">{{ t('email.cc') }}</Label>
                    <div class="w-full">
                        <Input 
                            id="cc" 
                            v-model="form.cc" 
                            type="text" 
                            placeholder="adres@email.com" 
                            class="border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 px-0 rounded-none bg-transparent"
                        />
                        <p v-if="form.errors.cc" class="text-xs text-destructive mt-1">{{ form.errors.cc }}</p>
                    </div>
                </div>
                
                <!-- Subject -->
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center border-t border-border/50 pt-4">
                    <Label for="subject" class="text-muted-foreground w-16 whitespace-nowrap">{{ t('email.subject') }}</Label>
                    <div class="w-full">
                        <Input 
                            id="subject" 
                            v-model="form.subject" 
                            type="text" 
                            placeholder="..." 
                            class="border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 px-0 rounded-none bg-transparent font-medium"
                            :readonly="mode === 'reply'"
                        />
                        <p v-if="form.errors.subject" class="text-xs text-destructive mt-1">{{ form.errors.subject }}</p>
                    </div>
                </div>
            </div>

            <!-- Body editor -->
            <div class="flex-1 flex flex-col p-6 min-h-[300px]">
                <div class="flex-1 flex flex-col pt-2 ql-custom-wrapper">
                    <!-- Note: No v-model here. Sync is manual via @ready and text-change listener -->
                    <QuillEditor 
                        contentType="html"
                        theme="snow" 
                        :placeholder="t('email.body_placeholder')"
                        class="flex-1 min-h-[300px]"
                        @ready="handleQuillReady"
                    />
                </div>
                <p v-if="form.errors.body" class="text-xs text-destructive mt-2">{{ form.errors.body }}</p>
            </div>

            <!-- Attachments -->
            <div class="px-6 py-4 border-t border-border flex flex-col gap-2 flex-shrink-0 bg-muted/5">
                <Label for="attachments" class="text-sm font-medium">{{ t('email.attachments') }}</Label>
                <Input 
                    id="attachments" 
                    type="file" 
                    multiple 
                    @change="handleFileUpload" 
                    class="cursor-pointer file:cursor-pointer transition-all bg-background"
                />
                <p v-for="(error, key) in form.errors" :key="key">
                    <span v-if="key.toString().startsWith('attachments')" class="text-xs text-destructive block">
                        {{ error }}
                    </span>
                </p>
            </div>

            <!-- Actions footer -->
            <div class="px-6 py-4 border-t border-border bg-muted/20 flex items-center justify-between flex-shrink-0 rounded-b-xl">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="form.processing"
                    @click="emit('cancel')"
                >
                    {{ t('email.cancel') }}
                </Button>
                <Button
                    type="submit"
                    size="sm"
                    :disabled="form.processing || !form.to.trim()"
                >
                    <Send class="h-4 w-4 mr-2" />
                    {{ form.processing ? t('email.sending') : (mode === 'reply' ? t('email.send') : t('email.send')) }}
                </Button>
            </div>
        </form>
    </div>
</template>

<style>
/* Adjust Quill editor styling to fit the UI */
.ql-custom-wrapper .ql-toolbar {
    border: none;
    border-bottom: 1px solid hsl(var(--border) / 0.5);
    padding: 0.5rem 0;
    font-family: inherit;
}
.ql-custom-wrapper .ql-container {
    border: none;
    font-family: inherit;
    font-size: 0.875rem; /* text-sm */
}
.ql-custom-wrapper .ql-editor {
    padding: 1rem 0;
    min-height: 300px;
}
</style>
