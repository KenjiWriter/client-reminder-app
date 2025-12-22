<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { Component } from 'vue';

interface Props {
    title: string;
    value: string | number;
    description?: string;
    icon?: Component;
    variant?: 'default' | 'primary' | 'success' | 'warning' | 'destructive';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default'
});

const variantClasses = {
    default: 'text-foreground',
    primary: 'text-primary',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    destructive: 'text-destructive',
};

const bgClasses = {
    default: 'bg-muted/50',
    primary: 'bg-primary/10',
    success: 'bg-green-100 dark:bg-green-900/20',
    warning: 'bg-yellow-100 dark:bg-yellow-900/20',
    destructive: 'bg-destructive/10',
};
</script>

<template>
    <Card class="overflow-hidden border-sidebar-border/70 shadow-sm transition-all hover:shadow-md">
        <CardContent class="p-6">
            <div class="flex items-center justify-between space-x-4">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                    <div class="flex items-baseline space-x-1">
                        <h2 :class="cn('text-3xl font-bold tracking-tight', variantClasses[variant])">
                            {{ value }}
                        </h2>
                    </div>
                    <p v-if="description" class="text-xs text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <div v-if="icon" :class="cn('rounded-xl p-3', bgClasses[variant])">
                    <component :is="icon" :class="cn('h-6 w-6', variantClasses[variant])" />
                </div>
            </div>
        </CardContent>
    </Card>
</template>
