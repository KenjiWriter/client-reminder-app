<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useWindowScroll } from '@vueuse/core';
import { CheckCircle2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const { t } = useTranslation();
const { y } = useWindowScroll();
const page = usePage();

const flashMessage = computed(() => (page.props.flash as any)?.message);

const parallaxOffset = ref(0);

const form = useForm({
    full_name: '',
    phone: '',
    email: '',
    note: '',
});

const submit = () => {
    form.post(route('consultation.store'), {
        preserveScroll: true,
        onSuccess: () => {
             form.reset();
        }
    });
};

// Scroll Reveal Logic
const revealElements = ref<Set<string>>(new Set());

onMounted(() => {
    requestAnimationFrame(updateParallax);

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Add the id or a unique key to the set to trigger animation class
                revealElements.value.add((entry.target as HTMLElement).dataset.revealId || '');
                observer.unobserve(entry.target); // Reveal only once
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });

    document.querySelectorAll('[data-reveal-id]').forEach(el => observer.observe(el));
});

// Use requestAnimationFrame for smooth parallax
const updateParallax = () => {
    // Limit offset to 80px max for subtle effect
    parallaxOffset.value = Math.min(y.value * 0.4, 80);
    requestAnimationFrame(updateParallax);
};
</script>

<template>
    <Head>
        <title>{{ t('landing.hero_title') }} - Facemodeling Studio</title>
        <meta name="description" :content="t('landing.hero_subtitle')" />
        <meta property="og:title" :content="t('landing.hero_title') + ' - Facemodeling Studio'" />
        <meta property="og:description" :content="t('landing.hero_subtitle')" />
        <meta property="og:image" content="/img/bg.png" />
    </Head>
    
    <div class="min-h-screen bg-background text-foreground overflow-x-hidden font-sans scroll-smooth relative">
        <!-- Language Switcher -->
        <div class="absolute top-4 right-4 z-50 flex gap-2">
            <Button 
                v-for="l in ['pl', 'en']" 
                :key="l"
                variant="ghost" 
                size="sm"
                class="uppercase font-semibold text-white/80 hover:text-white hover:bg-white/10"
                :class="{ 'text-white bg-white/20': $page.props.locale === l }"
                @click="router.post('/locale', { locale: l })"
            >
                {{ l }}
            </Button>
        </div>

        <!-- Hero Section -->
        <section class="relative h-screen w-full overflow-hidden flex items-center justify-center">
            <!-- Background Parallax Layer -->
            <div 
                class="absolute inset-0 z-0 bg-cover bg-center transition-transform duration-75 ease-out"
                :style="{
                    backgroundImage: 'url(/img/bg.png)',
                    transform: `translateY(${parallaxOffset}px) scale(1.1)`,
                }"
            ></div>
             
             <!-- Overlay Gradient -->
            <div class="absolute inset-0 z-10 bg-gradient-to-b from-black/40 via-black/20 to-background"></div>

            <!-- Hero Content -->
            <div class="relative z-20 text-center px-4 max-w-4xl mx-auto space-y-6 flex flex-col items-center">
                <h1 
                    class="text-4xl md:text-7xl font-bold text-white tracking-tight drop-shadow-lg opacity-0 translate-y-8 animate-in"
                    style="animation-delay: 0ms;"
                >
                    {{ t('landing.hero_title') }}
                </h1>
                <p 
                    class="text-xl md:text-2xl text-white/90 max-w-2xl drop-shadow opacity-0 translate-y-8 animate-in"
                     style="animation-delay: 200ms;"
                >
                     {{ t('landing.hero_subtitle') }}
                </p>
                
                <div 
                    class="pt-8 opacity-0 translate-y-8 animate-in"
                     style="animation-delay: 400ms;"
                >
                    <Button 
                        size="lg" 
                        class="text-lg px-8 py-6 rounded-full shadow-lg hover:scale-105 transition-transform duration-300 bg-white text-black hover:bg-white/90"
                        as-child
                    >
                        <Link href="#consultation">
                             {{ t('landing.cta_button') }}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Placeholder for Benefits, About, Form (Next Tasks) -->
        <section class="py-24 px-4 bg-background">
             <div 
                class="max-w-4xl mx-auto text-center transition-all duration-1000 transform translate-y-12 opacity-0"
                :class="{ 'translate-y-0 opacity-100': revealElements.has('features') }"
                data-reveal-id="features"
             >
                <h2 class="text-3xl md:text-4xl font-bold mb-12">{{ t('landing.features_title') }}</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Cards will go here -->
                     <div class="p-8 border rounded-2xl bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow">
                        <div class="h-12 w-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                            <!-- Icon placeholder -->
                            <span class="text-2xl">✨</span>
                        </div>
                        <h3 class="font-semibold text-xl mb-3">{{ t('landing.feature_1_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">{{ t('landing.feature_1_desc') }}</p>
                     </div>
                     <div class="p-8 border rounded-2xl bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow delay-100">
                        <div class="h-12 w-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                            <span class="text-2xl">🌿</span>
                        </div>
                        <h3 class="font-semibold text-xl mb-3">{{ t('landing.feature_2_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">{{ t('landing.feature_2_desc') }}</p>
                     </div>
                     <div class="p-8 border rounded-2xl bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow delay-200">
                        <div class="h-12 w-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                             <span class="text-2xl">💆‍♀️</span>
                        </div>
                        <h3 class="font-semibold text-xl mb-3">{{ t('landing.feature_3_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">{{ t('landing.feature_3_desc') }}</p>
                     </div>
                </div>
             </div>
        </section>

        <section 
            id="consultation" 
            class="py-24 bg-secondary/30 scroll-mt-0 relative overflow-hidden"
        >
             <div 
                class="max-w-xl mx-auto px-4 relative z-10 transition-all duration-1000 transform translate-y-12 opacity-0"
                :class="{ 'translate-y-0 opacity-100': revealElements.has('form') }"
                data-reveal-id="form"
             >
                  <div class="text-center mb-12">
                      <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ t('landing.form_title') }}</h2>
                      <p class="text-lg text-muted-foreground">{{ t('landing.form_subtitle') }}</p>
                  </div>

                  <div v-if="flashMessage" class="bg-emerald-50 border border-emerald-200 rounded-lg p-8 text-center animate-fade-in-up">
                        <div class="flex justify-center mb-4">
                            <CheckCircle2 class="h-12 w-12 text-emerald-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-emerald-900 mb-2">{{ t('landing.form_success_title', { default: 'Dziękujemy!' }) }}</h3>
                        <p class="text-emerald-700">{{ flashMessage }}</p>
                  </div>

                  <form v-else @submit.prevent="submit" class="bg-card p-8 rounded-xl shadow-sm border space-y-6">
                        <div class="space-y-2">
                            <Label for="full_name">{{ t('landing.form_name') }}</Label>
                            <Input id="full_name" v-model="form.full_name" required placeholder="np. Anna Kowalska" />
                            <p v-if="form.errors.full_name" class="text-sm text-destructive">{{ form.errors.full_name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">{{ t('landing.form_phone') }}</Label>
                            <Input id="phone" v-model="form.phone" required type="tel" placeholder="np. 500 600 700" />
                            <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                        </div>

                        <div class="space-y-2">
                             <Label for="email">{{ t('landing.form_email') }}</Label>
                             <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" />
                             <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                        
                        <div class="space-y-2">
                             <Label for="note">{{ t('landing.form_note') }}</Label>
                             <Textarea id="note" v-model="form.note" placeholder="" />
                             <p v-if="form.errors.note" class="text-sm text-destructive">{{ form.errors.note }}</p>
                        </div>

                        <Button type="submit" class="w-full text-lg h-12" :disabled="form.processing">
                             <span v-if="form.processing">{{ t('landing.form_sending') }}</span>
                             <span v-else>{{ t('landing.form_submit') }}</span>
                        </Button>
                  </form>
             </div>
        </section>
        
        <footer class="py-8 text-center text-sm text-muted-foreground">
            &copy; {{ new Date().getFullYear() }} Facemodeling Studio. {{ t('landing.footer_rights') }}
        </footer>
    </div>
</template>

<style scoped>
.animate-in {
    animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
