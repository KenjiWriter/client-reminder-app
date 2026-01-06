<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <!-- Full screen background with forest image -->
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 relative overflow-hidden"
        style="background-image: url('/img/bg-login.png'); background-size: cover; background-position: center; background-repeat: no-repeat;"
    >
        <!-- Overlay for better contrast -->
        <div class="absolute inset-0 bg-black/20 backdrop-blur-[2px]"></div>
        
        <!-- Login card with glassmorphism -->
        <div class="w-full max-w-md relative z-10">
            <!-- Glassmorphism card -->
            <div 
                class="backdrop-blur-xl bg-white/[0.02] rounded-2xl p-8"
            >
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-medium text-white drop-shadow-lg">{{ title }}</h1>
                            <p class="text-center text-sm text-white/80 drop-shadow">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Enhanced glassmorphism effect */
.backdrop-blur-xl {
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}
</style>
