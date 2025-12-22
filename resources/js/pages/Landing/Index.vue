<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { useWindowScroll } from '@vueuse/core';

const { t } = useTranslation();
const { y } = useWindowScroll();

const parallaxOffset = ref(0);

// Use requestAnimationFrame for smooth parallax
const updateParallax = () => {
    // Limit offset to 80px max for subtle effect
    parallaxOffset.value = Math.min(y.value * 0.4, 80);
    requestAnimationFrame(updateParallax);
};

onMounted(() => {
    requestAnimationFrame(updateParallax);
});

// Clean up is handled automatically by Vue, but RAF loop is harmless
</script>

<template>
    <Head title="Facemodeling" />
    
    <div class="min-h-screen bg-background text-foreground overflow-x-hidden font-sans">
        <!-- Hero Section -->
        <section class="relative h-screen w-full overflow-hidden flex items-center justify-center">
            <!-- Background Parallax Layer -->
            <div 
                class="absolute inset-0 z-0 bg-cover bg-center"
                :style="{
                    backgroundImage: 'url(/img/bg.png)',
                    transform: `translateY(${parallaxOffset}px) scale(1.1)`,
                    willChange: 'transform'
                }"
            ></div>
             
             <!-- Overlay Gradient -->
            <div class="absolute inset-0 z-10 bg-gradient-to-b from-black/30 via-black/20 to-background"></div>

            <!-- Hero Content -->
            <div class="relative z-20 text-center px-4 max-w-4xl mx-auto space-y-6 flex flex-col items-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white tracking-tight drop-shadow-md animate-fade-in-up">
                    {{ t('landing.hero_title', { default: 'Naturalne Piękno' }) }}
                </h1>
                <p class="text-xl md:text-2xl text-white/90 max-w-2xl drop-shadow animate-fade-in-up delay-100">
                     {{ t('landing.hero_subtitle', { default: 'Odkryj moc facemodelingu i masażu Kobido' }) }}
                </p>
                
                <div class="pt-8 animate-fade-in-up delay-200">
                    <Button 
                        size="lg" 
                        class="text-lg px-8 py-6 rounded-full shadow-lg hover:scale-105 transition-transform duration-300"
                        as-child
                    >
                        <Link href="#consultation">
                             {{ t('landing.cta_button', { default: 'Zapisz się na konsultację' }) }}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Placeholder for Benefits, About, Form (Next Tasks) -->
        <section class="py-20 px-4 bg-background">
             <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold mb-8">Dlaczego Facemodeling?</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Cards will go here -->
                     <div class="p-6 border rounded-xl bg-card text-card-foreground shadow-sm">
                        <h3 class="font-semibold text-xl mb-2">Naturalny Lifting</h3>
                        <p class="text-muted-foreground">Bezinwazyjna alternatywa dla medycyny estetycznej.</p>
                     </div>
                     <div class="p-6 border rounded-xl bg-card text-card-foreground shadow-sm">
                        <h3 class="font-semibold text-xl mb-2">Redukcja Napięć</h3>
                        <p class="text-muted-foreground">Głęboki relaks dla mięśni twarzy i szyi.</p>
                     </div>
                     <div class="p-6 border rounded-xl bg-card text-card-foreground shadow-sm">
                        <h3 class="font-semibold text-xl mb-2">Poprawa Owalu</h3>
                        <p class="text-muted-foreground">Przywrócenie młodzieńczego konturu twarzy.</p>
                     </div>
                </div>
             </div>
        </section>

        <section id="consultation" class="py-20 bg-secondary/20">
             <div class="max-w-xl mx-auto text-center">
                  <h2 class="text-3xl font-bold mb-6">Formularz Konsultacji</h2>
                  <p class="text-muted-foreground mb-8">Zostaw namiar, oddzwonimy.</p>
                  <!-- Form Component will go here -->
             </div>
        </section>
        
        <footer class="py-8 text-center text-sm text-muted-foreground">
            &copy; {{ new Date().getFullYear() }} Facemodeling Studio. All rights reserved.
        </footer>
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
