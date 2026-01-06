<script setup lang="ts">
interface Props {
    variant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'upcoming' | 'completed' | 'rescheduled' | 'canceled' | 'noshow' | 'success';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const variantClasses = {
    default: 'bg-primary text-primary-foreground',
    secondary: 'bg-secondary text-secondary-foreground',
    destructive: 'bg-destructive text-destructive-foreground',
    outline: 'border border-input bg-background text-foreground',
    upcoming: 'bg-event-upcoming text-event-upcoming-dot',
    completed: 'bg-event-completed text-event-completed-dot',
    rescheduled: 'bg-event-rescheduled text-event-rescheduled-dot',
    canceled: 'bg-event-canceled text-event-canceled-dot',
    noshow: 'bg-event-noshow text-event-noshow-dot',
    success: 'bg-green-500/15 text-green-700 dark:text-green-400 border-green-500/20',
};
</script>

<template>
    <div
        :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
            variantClasses[variant],
        ]"
    >
        <slot />
    </div>
</template>

