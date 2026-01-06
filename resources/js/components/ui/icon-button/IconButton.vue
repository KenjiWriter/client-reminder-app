<script setup lang="ts">
import { type Component } from 'vue';

interface Props {
    icon: Component;
    variant?: 'default' | 'ghost' | 'outline';
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'md',
});

const variantClasses = {
    default: 'bg-primary text-primary-foreground hover:bg-primary/90',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
};

const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
};

const iconSizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
};
</script>

<template>
    <button
        type="button"
        :class="[
            'inline-flex items-center justify-center rounded-lg font-medium transition-colors',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            'disabled:pointer-events-none disabled:opacity-50',
            variantClasses[variant],
            sizeClasses[size],
        ]"
    >
        <component :is="icon" :class="iconSizeClasses[size]" />
    </button>
</template>

