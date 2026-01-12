<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import BookingPanel from '@/components/Booking/BookingPanel.vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps<{
    services: Record<string, any[]>;
}>();

const scrollToBooking = () => {
    document.getElementById('booking')?.scrollIntoView({ behavior: 'smooth' });
};

// Pricing Section Helpers
const formatDuration = (minutes: number) => {
    if (minutes >= 60) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return m > 0 ? `${h}h ${m}min` : `${h}h`;
    }
    return `${minutes} min`;
};
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(amount);
};
</script>

<template>
    <Head>
        <title>Emilia Wiśniewska - Face Modeling & Body Therapy</title>
        <meta name="description" content="Terapie manualne twarzy i ciała. Odzyskaj spokój i naturalne piękno." />
    </Head>

    <div class="min-h-screen bg-[#FDFBF7] text-[#2C2C2C] font-sans selection:bg-[#EDE5D8]">
        
        <!-- Hero Section -->
        <section class="relative min-h-screen flex flex-col justify-center items-center text-center px-4 overflow-hidden">
            <!-- Background Decoration (Subtle) -->
            <div class="absolute inset-0 z-0 opacity-10 pointer-events-none">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#D4C3A3] rounded-full blur-[100px] translate-x-1/3 -translate-y-1/3"></div>
                <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#A3B1D4] rounded-full blur-[80px] -translate-x-1/3 translate-y-1/3"></div>
            </div>

            <div class="relative z-10 space-y-8 max-w-4xl mx-auto animate-in fade-in slide-in-from-bottom-8 duration-1000">
                <p class="text-xs md:text-sm uppercase tracking-[0.3em] text-[#8A8A8A]">Holistyczna Praca z Ciałem</p>
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-serif text-[#1A1A1A] leading-tight">
                    Emilia Wiśniewska
                </h1>
                <p class="text-xl md:text-2xl font-light text-[#5A5A5A] max-w-2xl mx-auto leading-relaxed">
                    Face Modeling. Terapia Czaszkowo-Krzyżowa. <br class="hidden md:block" />
                    Powrót do równowagi.
                </p>
                
                <div class="pt-8">
                    <Button 
                        @click="scrollToBooking" 
                        size="lg" 
                        class="bg-[#1A1A1A] text-white hover:bg-[#333] rounded-full px-10 py-7 text-lg uppercase tracking-widest transition-all hover:scale-105 duration-300 shadow-xl"
                    >
                        Umów sesję
                    </Button>
                </div>
            </div>

            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce duration-[2000ms] text-[#8A8A8A]">
                <ChevronDown class="w-6 h-6" />
            </div>
        </section>

        <!-- "Dla kogo" Section -->
        <section class="py-24 md:py-32 px-4 bg-white">
            <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-16 items-center">
                <div class="space-y-6">
                    <h2 class="text-sm uppercase tracking-[0.2em] text-[#8A8A8A] mb-2">Dla kogo jest ta praca?</h2>
                    <h3 class="text-3xl md:text-4xl font-serif mb-6">Gdy Twoje ciało mówi "dość"</h3>
                    <p class="text-[#5A5A5A] leading-loose">
                        Moje terapie są dla osób, które czują, że stres zapisał się w ich twarzy i ciele. 
                        Dla tych, którzy szukają naturalnych metod odmładzania, ale także głębokiego relaksu i uwolnienia od napięć.
                    </p>
                </div>
                <ul class="space-y-4">
                    <li v-for="item in ['Przewlekłe napięcie mięśniowe', 'Bruksizm i zaciskanie szczęki', 'Obrzęki i zmęczona twarz', 'Migreny i bóle głowy', 'Potrzeba głębokiego wyciszenia']" 
                        :key="item"
                        class="flex items-center space-x-4 p-4 border-b border-[#F0F0F0]"
                    >
                        <span class="w-2 h-2 rounded-full bg-[#D4C3A3]"></span>
                        <span class="text-lg text-[#333]">{{ item }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- "Jak wygląda sesja" Section -->
        <section class="py-24 md:py-32 px-4 bg-[#F5F2EB]">
            <div class="max-w-5xl mx-auto space-y-16">
                <div class="text-center space-y-4">
                    <h2 class="text-3xl md:text-4xl font-serif">Jak wygląda sesja?</h2>
                    <p class="text-[#8A8A8A] max-w-xl mx-auto">To nie tylko zabieg, to proces przywracania harmonii.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 md:gap-12">
                     <div class="space-y-4 text-center">
                        <div class="w-16 h-16 rounded-full bg-[#E0D8CC] flex items-center justify-center mx-auto text-xl font-serif">I</div>
                        <h4 class="text-xl font-medium">Konsultacja</h4>
                        <p class="text-[#666] text-sm leading-relaxed">Rozmawiamy o Twoich potrzebach, dolegliwościach i oczekiwaniach. Analizuję anatomię Twojej twarzy i postawę ciała.</p>
                     </div>
                     <div class="space-y-4 text-center">
                        <div class="w-16 h-16 rounded-full bg-[#E0D8CC] flex items-center justify-center mx-auto text-xl font-serif">II</div>
                        <h4 class="text-xl font-medium">Terapia Manualna</h4>
                        <p class="text-[#666] text-sm leading-relaxed">Praca na tkankach głębokich, powięziach i mięśniach. Wykorzystuję techniki masażu transbukalnego, kobido i osteopatii estetycznej.</p>
                     </div>
                     <div class="space-y-4 text-center">
                        <div class="w-16 h-16 rounded-full bg-[#E0D8CC] flex items-center justify-center mx-auto text-xl font-serif">III</div>
                        <h4 class="text-xl font-medium">Integracja</h4>
                        <p class="text-[#666] text-sm leading-relaxed">Czas na wyciszenie i integrację zmian w ciele. Zalecenia do domu i plan dalszej terapii.</p>
                     </div>
                </div>
            </div>
        </section>

        <!-- Cennik (Full Menu) Section -->
        <section class="py-24 px-4 bg-white">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-serif text-center mb-16">Cennik Usług</h2>
                
                <div v-for="(categoryServices, categoryName) in services" :key="categoryName" class="mb-12 last:mb-0">
                    <h3 class="text-xl uppercase tracking-widest text-[#8A8A8A] border-b border-[#F0F0F0] pb-4 mb-8">{{ categoryName || 'Pozostałe' }}</h3>
                    <div class="space-y-8">
                        <div v-for="service in categoryServices" :key="service.id" class="flex flex-col md:flex-row md:items-baseline justify-between gap-4 group cursor-default">
                             <div class="flex-1">
                                 <div class="flex items-baseline justify-between">
                                     <h4 class="text-xl font-medium text-[#1A1A1A] group-hover:text-[#D4C3A3] transition-colors">{{ service.name }}</h4>
                                     <span class="md:hidden text-lg font-serif">{{ formatCurrency(Number(service.price)) }}</span>
                                 </div>
                                 <p class="text-[#888] text-sm mt-2 max-w-xl">{{ service.description }}</p>
                                 <p class="text-xs text-[#AAA] mt-1">{{ formatDuration(service.duration_minutes) }}</p>
                             </div>
                             <div class="hidden md:block w-32 text-right">
                                 <span class="text-xl font-serif text-[#1A1A1A]">{{ formatCurrency(Number(service.price)) }}</span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Booking Section -->
        <section id="booking" class="py-24 px-4 bg-[#1A1A1A] text-white">
            <div class="max-w-4xl mx-auto text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-serif mb-4">Rezerwacja Online</h2>
                <p class="text-white/60">Wybierz dogodny termin i zadbaj o siebie. Rezerwacja zajmie Ci mniej niż minutę.</p>
            </div>
            
            <BookingPanel :services="services" class="bg-[#222] text-white border-white/10" />
            
            <div class="text-center mt-12 space-y-2">
                 <p class="text-sm text-white/40">Masz pytania? Zadzwoń lub napisz.</p>
                 <p class="text-lg">+48 793 173 748 &bull; kontakt@face-modeling.pl</p>
                 <div class="flex justify-center gap-6 mt-6">
                     <!-- Instagram -->
                     <a href="https://www.instagram.com/facemodeling.expert?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" class="w-12 h-12 bg-[#1A1A1A] border border-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-black transition-all hover:scale-110 group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                     </a>
                     <!-- Facebook -->
                     <a href="https://www.facebook.com/profile.php?id=100064131938944" target="_blank" class="w-12 h-12 bg-[#1A1A1A] border border-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-black transition-all hover:scale-110 group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                     </a>
                 </div>
            </div>
        </section>
        
        <footer class="py-8 bg-[#111] text-white/20 text-center text-xs">
            <div class="mb-2 space-x-4">
                <a href="/regulamin" class="hover:text-white/40 transition-colors">Regulamin</a>
                <a href="/polityka-prywatnosci" class="hover:text-white/40 transition-colors">Polityka Prywatności</a>
            </div>
            &copy; {{ new Date().getFullYear() }} Emilia Wiśniewska. Wszelkie prawa zastrzeżone.
        </footer>
    </div>
</template>


