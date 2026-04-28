<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { Inbox, RefreshCw, ChevronLeft, ChevronRight, AlertCircle, Mail, MailOpen, Edit, Trash, Loader2, Send, Settings } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

interface Email {
    uid: string;
    subject: string;
    snippet: string;
    from: string;
    from_email: string;
    date: string;
    is_seen: boolean;
}

const props = defineProps<{
    emails: Email[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    imapError?: string | null;
    currentFolder?: string;
}>();

const activeFolder = computed(() => props.currentFolder || 'INBOX');
const sentFolderName = computed(() => (usePage().props.settings as any)?.imap_sent_folder || 'Sent');

const isRefreshing = ref(false);

const refresh = () => {
    isRefreshing.value = true;
    router.reload({ onFinish: () => { isRefreshing.value = false; } });
};

const goToPage = (page: number) => {
    router.get(route('emails.index'), { page, folder: activeFolder.value }, { preserveState: false });
};

const deletingId = ref<string | null>(null);

const deleteEmail = (uid: string) => {
    if (!confirm(t('common.confirm_delete') || 'Czy na pewno chcesz usunąć tę wiadomość?')) return;
    
    router.delete(route('emails.destroy', uid), {
        onStart: () => deletingId.value = uid,
        onFinish: () => deletingId.value = null,
        preserveScroll: true,
    });
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    if (isToday) {
        return new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' }).format(date);
    }
    return new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
};

const hasPrev = computed(() => props.current_page > 1);
const hasNext = computed(() => props.current_page < props.last_page);
</script>

<template>
    <Head :title="t('email.inbox')" />

    <AppShell>
        <template #header-title>
            <div>
                <h1 class="text-2xl font-semibold">{{ t('email.inbox') }}</h1>
                <p class="text-sm text-muted-foreground">{{ activeFolder === 'INBOX' ? t('email.inbox') : t('email.sent') }} ({{ t('email.total_messages', { total: total }) }})</p>
            </div>
        </template>

        <div class="flex flex-col gap-4 p-4 lg:p-6 pb-12 w-full">
            
            <!-- IMAP Error Banner -->
            <div v-if="imapError" class="rounded-lg border border-destructive/20 bg-destructive/10 p-4 text-destructive flex items-start gap-3">
                <AlertCircle class="h-5 w-5 flex-shrink-0 mt-0.5" />
                <div class="flex-1 text-sm">
                    <p class="font-semibold text-lg">{{ t('email.connection_error') }}</p>
                    <p class="mt-1 opacity-90 truncate max-w-full">
                        <template v-if="imapError && imapError.includes('Skonfiguruj')">
                            {{ t('email.configure_settings') }}
                            <Link :href="route('settings.email')" class="text-blue-500 underline hover:text-blue-600 transition-colors">
                                {{ t('email.settings_link') }}
                            </Link>
                        </template>
                        <template v-else-if="imapError">
                            {{ imapError }}
                        </template>
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center border-b border-border gap-6">
                <Link
                    :href="route('emails.index', { folder: 'INBOX' })"
                    class="pb-3 border-b-2 transition-colors font-medium text-sm flex items-center gap-2"
                    :class="activeFolder === 'INBOX' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Inbox class="w-4 h-4" /> {{ t('email.inbox') }}
                </Link>
                <!-- Dynamic Sent folder -->
                <Link
                    :href="route('emails.index', { folder: sentFolderName })"
                    class="pb-3 border-b-2 transition-colors font-medium text-sm flex items-center gap-2"
                    :class="activeFolder === sentFolderName ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Send class="w-4 h-4" /> {{ t('email.sent') }}
                </Link>
            </div>

            <!-- Toolbar -->
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <component :is="activeFolder === 'INBOX' ? Inbox : Send" class="h-4 w-4" />
                    <span>{{ t('email.page_info', { current: current_page, last: last_page }) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" as-child class="h-9 w-9 text-muted-foreground hover:text-foreground">
                        <Link :href="route('settings.email')">
                            <Settings class="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" @click="refresh" :disabled="isRefreshing">
                        <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                        {{ t('email.refresh') }}
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="route('emails.create')">
                            <Edit class="h-4 w-4 mr-2" />
                            {{ t('email.compose') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Email list -->
            <div class="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden">
                <!-- Empty state -->
                <div v-if="emails.length === 0" class="flex flex-col items-center justify-center py-20 text-center text-muted-foreground gap-3">
                    <Inbox class="h-12 w-12 opacity-30" />
                    <p class="text-lg font-medium">{{ t('email.empty_inbox') }}</p>
                    <p class="text-sm">{{ t('email.empty_inbox_desc') }}</p>
                </div>

                <!-- Email rows -->
                <div v-else class="divide-y divide-border">
                    <Link
                        v-for="email in emails"
                        :key="email.uid"
                        :href="route('emails.show', { uid: email.uid, folder: activeFolder })"
                        class="flex items-start gap-4 px-5 py-4 hover:bg-muted/40 transition-colors cursor-pointer"
                        :class="{ 'bg-primary/5': !email.is_seen }"
                    >
                        <!-- Read/unread indicator -->
                        <div class="mt-0.5 flex-shrink-0">
                            <MailOpen v-if="email.is_seen" class="h-5 w-5 text-muted-foreground/40" />
                            <Mail v-else class="h-5 w-5 text-primary" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="text-sm truncate"
                                    :class="email.is_seen ? 'text-muted-foreground' : 'font-semibold text-foreground'"
                                >
                                    {{ email.from || email.from_email }}
                                </span>
                                <span class="text-xs text-muted-foreground flex-shrink-0">{{ formatDate(email.date) }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span
                                    class="text-sm truncate"
                                    :class="email.is_seen ? 'text-muted-foreground' : 'font-medium text-foreground'"
                                >
                                    {{ email.subject }}
                                </span>
                                <Badge v-if="!email.is_seen" variant="default" class="text-[10px] py-0 px-1.5 h-4 flex-shrink-0">
                                    {{ t('email.new') }}
                                </Badge>
                            </div>
                            <!-- Snippet -->
                            <p class="text-xs text-muted-foreground mt-1 truncate">
                                {{ email.snippet }}
                            </p>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex-shrink-0 ml-2">
                            <Button 
                                variant="ghost" 
                                size="icon" 
                                class="h-8 w-8 text-muted-foreground hover:text-destructive" 
                                @click.prevent="deleteEmail(email.uid)"
                                :disabled="deletingId === email.uid"
                                :class="{ 'opacity-50 cursor-not-allowed hidden sm:flex': deletingId === email.uid }"
                            >
                                <Loader2 v-if="deletingId === email.uid" class="h-4 w-4 animate-spin" />
                                <Trash v-else class="h-4 w-4" />
                            </Button>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="last_page > 1" class="flex items-center justify-between py-2">
                <p class="text-sm text-muted-foreground">
                    {{ t('email.total_messages', { total: total }) }}
                </p>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="!hasPrev" @click="goToPage(current_page - 1)">
                        <ChevronLeft class="h-4 w-4 mr-1" /> {{ t('email.prev') }}
                    </Button>
                    <Button variant="outline" size="sm" :disabled="!hasNext" @click="goToPage(current_page + 1)">
                        {{ t('email.next') }} <ChevronRight class="h-4 w-4 ml-1" />
                    </Button>
                </div>
            </div>
        </div>
    </AppShell>
</template>
