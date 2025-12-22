<script setup lang="ts">
import { computed } from 'vue';

interface Option {
    value: string;
    label: string;
}

const props = defineProps<{
    modelValue: string;
    options: Option[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const isActive = (value: string) => props.modelValue === value;

const select = (value: string) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <div class="inline-flex items-center gap-1 rounded-lg bg-muted p-1">
        <button
           v-for="option in options"
            :key="option.value"
            type="button"
            @click="select(option.value)"
            :class="[
                'px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                isActive(option.value)
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground'
            ]"
        >
            {{ option.label }}
        </button>
    </div>
</template>
