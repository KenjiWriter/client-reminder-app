<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import BookingPanel from '@/components/Booking/BookingPanel.vue';
import { MapPin, Phone } from 'lucide-vue-next';

defineProps<{
    services: Record<string, any[]>;
}>();

const phoneDisplay = '793 173 748';
const phoneHref = 'tel:+48793173748';
const email = 'kontakt@face-modeling.pl';
const shortAddress = 'Emilianów 8A · okolice Żyrardowa';
const street = 'Emilianów 8A';
const cityLine = '96-330 Puszcza Mariańska';
const mapsQuery = 'Emilianów 8A, 96-330 Puszcza Mariańska';
const mapsEmbed = `https://maps.google.com/maps?q=${encodeURIComponent(mapsQuery)}&z=15&output=embed`;
const mapsDirections = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(mapsQuery)}`;

const initialClientData = ref<{ full_name?: string; phone?: string; email?: string }>({});

const aboutParagraphs = [
    'Nazywam się Emilia Wiśniewska. Od kilku lat zajmuję się masażem twarzy i ciała. W Emilianowie prowadzę kameralny gabinet Face Modeling. Mam wykształcenie w zakresie masażu. Stale się rozwijam i uczę. Uwielbiam pracę z ludźmi, a terapia manualna jest dla mnie powołaniem.',
    'Każdą wizytę zaczynam od rozmowy. Chcę wiedzieć, co Ci dokucza, kiedy najbardziej to odczuwasz i czego potrzebujesz tego dnia. Tak wygląda u mnie podejście holistyczne. Gdy czujesz napięcie w żuchwie, pytam również o sen i stres. Podczas masażu twarzy zwracam uwagę na szyję i kark oraz na to, czy możesz wygodnie oprzeć głowę i rozluźnić barki.',
    'W pracy z bólem i napięciem łączę masaż z technikami powięziowymi i korzystam z elementów osteopatii. W zależności od potrzeb sięgam też po kapilaroterapię, czyli pracę z bańkami, lub kinesiotaping, czyli oklejanie elastycznymi taśmami. Wykonuję również drenaż limfatyczny twarzy i ciała. Jeśli przychodzisz po intensywnym tygodniu i potrzebujesz wyciszenia, wybieram spokojniejsze tempo i łagodniejszy dotyk.',
    'Ważne jest dla mnie również to, jak czujesz się po powrocie do domu. Możesz do mnie napisać, opowiedzieć, co zmieniło się od wizyty, albo dopytać o ruch czy automasaż, który Ci pokazałam. Tak rozumiem dotyk, uważność i troskę o Ciebie.',
];

const nav = [
    { id: 'o-mnie', label: 'O mnie' },
    { id: 'oferta', label: 'Oferta i ceny' },
    { id: 'kontakt', label: 'Kontakt' },
];

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('client_name')) {
        initialClientData.value = {
            full_name: params.get('client_name') || '',
            phone: params.get('client_phone') || '',
            email: params.get('client_email') || '',
        };
    }

    const hash = window.location.hash.replace('#', '');
    if (hash) {
        requestAnimationFrame(() => scrollToSection(hash));
    }
});

const scrollToSection = (id: string) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
};

const formatDuration = (minutes: number) => {
    if (minutes >= 60) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return m > 0 ? `${h}h ${m}min` : `${h}h`;
    }
    return `${minutes} min`;
};

const formatCurrency = (amount: number, maxAmount: number | null = null) => {
    const formatter = new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' });
    if (maxAmount) {
        return `${formatter.format(amount)} – ${formatter.format(maxAmount)}`;
    }
    return formatter.format(amount);
};
</script>

<template>
    <Head>
        <title>Face Modeling — Emilia Wiśniewska</title>
        <meta
            name="description"
            content="Masaż i terapie manualne twarzy i ciała. Emilianów 8A, okolice Żyrardowa. Dotyk, uważność i troska o Ciebie."
        />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Outfit:wght@300;400;500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="fm-sans min-h-screen bg-[#F7F1E8] text-[#3C2418] antialiased">
        <header class="sticky top-0 z-40 border-b border-[#F3E6D4]/10 bg-[#2C1612]/95 text-[#F6EBDD] backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-3 py-2.5 sm:px-5 lg:px-8 lg:py-3.5">
                <a href="#start" class="flex min-w-0 items-center gap-2 lg:gap-3" @click.prevent="scrollToSection('start')">
                    <img src="/img/landing/logo-mark-light.png" alt="" class="h-9 w-9 shrink-0 lg:h-12 lg:w-12" />
                    <span class="min-w-0 leading-tight">
                        <span class="fm-serif block truncate text-[1.05rem] font-medium tracking-tight sm:text-xl lg:text-[1.35rem]">Face Modeling</span>
                        <span class="block truncate text-[0.48rem] font-medium uppercase tracking-[0.16em] text-[#E4C89A] sm:text-[0.62rem] sm:tracking-[0.22em] lg:tracking-[0.28em]">Emilia Wiśniewska</span>
                    </span>
                </a>

                <nav class="flex shrink-0 items-center gap-2.5 sm:gap-5 lg:gap-8">
                    <button
                        v-for="item in nav"
                        :key="item.id"
                        type="button"
                        class="text-[11px] tracking-wide text-[#F6EBDD]/90 transition hover:text-[#E4C89A] sm:text-sm"
                        @click="scrollToSection(item.id)"
                    >
                        {{ item.label }}
                    </button>
                </nav>
            </div>
        </header>

        <main>
            <section id="start" class="relative overflow-hidden bg-[#2C1612] text-[#F6EBDD]">
                <div class="grid grid-cols-2 pb-12 lg:min-h-[calc(100vh-5.25rem)] lg:pb-0">
                    <div class="relative z-10 flex flex-col justify-center px-3 py-4 pb-8 sm:px-6 sm:py-8 lg:py-16 lg:pb-36 lg:pl-[max(2rem,calc((100vw-72rem)/2))] lg:pr-16">
                        <h1 class="fm-serif max-w-xl text-[1.35rem] font-medium leading-[1.05] min-[420px]:text-[1.55rem] sm:text-[2.4rem] lg:text-[4.15rem]">
                            Dotyk, uważność<br />i troska o Ciebie.
                        </h1>
                        <p class="mt-3 max-w-md text-[0.78rem] font-semibold leading-snug text-[#F6EBDD] sm:mt-5 sm:text-base lg:mt-6 lg:text-xl lg:font-medium">
                            Masaż i terapie manualne twarzy i ciała
                        </p>
                        <p class="mt-2 max-w-md text-[0.68rem] font-light leading-snug text-[#E7D5C4] sm:mt-4 sm:text-sm sm:leading-relaxed lg:mt-5 lg:text-[1.02rem]">
                            Czujesz napięcie w karku, plecach lub żuchwie? A może potrzebujesz chwili odpoczynku?
                            Na początku wizyty pytam, co Ci dokucza i czego potrzebujesz. Do tego dobieram masaż.
                        </p>

                        <p class="mt-3 flex items-start gap-1.5 text-[0.65rem] text-[#E4C89A] sm:mt-6 sm:items-center sm:gap-2 sm:text-sm lg:mt-8">
                            <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 sm:mt-0 sm:h-4 sm:w-4" />
                            {{ shortAddress }}
                        </p>

                        <div class="mt-3 flex flex-col items-start gap-2.5 sm:mt-5 sm:gap-4">
                            <a
                                :href="phoneHref"
                                class="inline-flex items-center justify-center rounded-md bg-[#F3E6D4] px-3 py-2.5 text-center text-[0.72rem] font-medium tracking-wide text-[#2C1612] transition hover:bg-[#E7D3B5] sm:px-5 sm:text-sm lg:px-7 lg:py-3.5 lg:text-[0.95rem]"
                            >
                                Zadzwoń i umów wizytę
                            </a>
                            <a :href="phoneHref" class="fm-serif inline-flex items-center gap-1.5 text-lg text-[#F6EBDD] hover:text-[#E4C89A] sm:gap-2.5 sm:text-2xl">
                                <Phone class="h-4 w-4 sm:h-5 sm:w-5" />
                                {{ phoneDisplay }}
                            </a>
                            <button
                                type="button"
                                class="text-[0.65rem] text-[#E7D5C4]/80 underline decoration-[#E4C89A]/40 underline-offset-4 hover:text-[#F6EBDD] sm:text-sm"
                                @click="scrollToSection('booking')"
                            >
                                lub umów wizytę online
                            </button>
                        </div>
                    </div>

                    <div class="relative min-h-full">
                        <img
                            src="/img/landing/hero-hands.jpg"
                            alt="Dłonie podczas masażu twarzy i ciała"
                            class="absolute inset-0 h-full w-full object-cover object-[center_62%] lg:object-[center_28%]"
                        />
                        <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-24 bg-gradient-to-r from-[#2C1612] to-transparent lg:block"></div>
                    </div>
                </div>

                <svg class="absolute bottom-0 left-0 w-full text-[#E4C89A]" viewBox="0 0 1440 140" preserveAspectRatio="none" aria-hidden="true">
                    <path
                        fill="currentColor"
                        fill-opacity="0.55"
                        d="M0,78 C240,120 420,40 720,72 C1020,104 1200,36 1440,70 L1440,140 L0,140 Z"
                    />
                    <path fill="#F7F1E8" d="M0,98 C260,132 480,64 760,92 C1040,120 1240,70 1440,96 L1440,140 L0,140 Z" />
                </svg>
            </section>

            <section id="o-mnie" class="scroll-mt-24 px-5 py-20 md:px-8 md:py-28">
                <div class="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <img
                        src="/img/landing/about.jpg"
                        alt="Emilia Wiśniewska podczas terapii manualnej"
                        class="aspect-[4/5] w-full object-cover object-center shadow-sm"
                    />
                    <div>
                        <h2 class="fm-serif text-5xl font-medium">O mnie</h2>
                        <div class="mt-3 h-px w-16 bg-[#C4A574]"></div>
                        <div class="mt-6 space-y-4 text-[1.02rem] font-light leading-relaxed text-[#4A3428]">
                            <p v-for="(paragraph, index) in aboutParagraphs" :key="index">{{ paragraph }}</p>
                        </div>
                        <p class="fm-serif mt-8 text-2xl italic text-[#3C2418]">Emilia Wiśniewska</p>
                    </div>
                </div>
            </section>

            <section id="oferta" class="scroll-mt-24 bg-[#F3EBE0] px-5 py-20 md:px-8 md:py-28">
                <div class="mx-auto max-w-3xl">
                    <h2 class="fm-serif text-center text-5xl font-medium">Oferta i ceny</h2>
                    <p class="mx-auto mt-4 max-w-xl text-center font-light leading-relaxed text-[#6A5344]">
                        Dobieram masaż do tego, co dokucza tego dnia. Czas i cenę widzisz przy każdej usłudze.
                    </p>

                    <div v-if="Object.keys(services).length === 0" class="mt-12 text-center text-[#6A5344]">
                        Cennik pojawi się wkrótce. Zadzwoń, a opowiem, co mogę dla Ciebie zrobić.
                    </div>

                    <div v-for="(categoryServices, categoryName) in services" :key="categoryName" class="mt-14">
                        <h3 class="border-b border-[#3C2418]/15 pb-3 text-xs font-medium uppercase tracking-[0.22em] text-[#8A6A52]">
                            {{ categoryName || 'Usługi' }}
                        </h3>
                        <div class="mt-6 space-y-7">
                            <div v-for="service in categoryServices" :key="service.id" class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between sm:gap-8">
                                <div class="min-w-0">
                                    <h4 class="text-lg font-medium">{{ service.name }}</h4>
                                    <p v-if="service.description" class="mt-1 max-w-xl text-sm font-light leading-relaxed text-[#6A5344]">
                                        {{ service.description }}
                                    </p>
                                    <p class="mt-1 text-xs tracking-wide text-[#8A6A52]">{{ formatDuration(service.duration_minutes) }}</p>
                                </div>
                                <p class="fm-serif shrink-0 text-xl">
                                    {{ formatCurrency(Number(service.price), service.max_price ? Number(service.max_price) : null) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-14 text-center">
                        <button
                            type="button"
                            class="inline-flex rounded-md bg-[#3C2418] px-7 py-3.5 text-sm font-medium tracking-wide text-[#F6EBDD] transition hover:bg-[#2C1612]"
                            @click="scrollToSection('booking')"
                        >
                            Umów wizytę
                        </button>
                    </div>
                </div>
            </section>

            <section id="booking" class="scroll-mt-24 bg-[#2C1612] px-5 py-20 text-[#F6EBDD] md:px-8 md:py-28">
                <div class="mx-auto mb-12 max-w-3xl text-center">
                    <h2 class="fm-serif text-5xl font-medium">Rezerwacja online</h2>
                    <p class="mt-4 font-light text-[#E7D5C4]">
                        Wybierz usługę i termin. Potwierdzenie dostaniesz, gdy przyjmę wizytę.
                    </p>
                </div>
                <BookingPanel :services="services" :initial-client-data="initialClientData" class="border-[#F3E6D4]/15 bg-[#3A1E18]" />
            </section>

            <section id="kontakt" class="scroll-mt-24 px-5 py-20 md:px-8 md:py-28">
                <div class="mx-auto grid max-w-6xl items-start gap-12 lg:grid-cols-2">
                    <div>
                        <h2 class="fm-serif text-5xl font-medium">Kontakt i dojazd</h2>
                        <div class="mt-3 h-px w-16 bg-[#C4A574]"></div>
                        <p class="mt-6 max-w-md font-light leading-relaxed text-[#4A3428]">
                            Gabinet jest kameralny. Najprościej zadzwonić — powiem, jak dojechać i co zabrać ze sobą na wizytę.
                        </p>

                        <address class="mt-8 space-y-1 text-lg not-italic leading-relaxed">
                            <p class="fm-serif text-2xl">Emilia Wiśniewska</p>
                            <p>Face Modeling</p>
                            <p class="pt-3">{{ street }}</p>
                            <p>{{ cityLine }}</p>
                            <p class="text-[#6A5344]">okolice Żyrardowa</p>
                        </address>

                        <div class="mt-6 space-y-2">
                            <a :href="phoneHref" class="flex items-center gap-2 text-lg hover:text-[#8A6A52]">
                                <Phone class="h-4 w-4" />
                                {{ phoneDisplay }}
                            </a>
                            <a :href="`mailto:${email}`" class="block text-[#4A3428] hover:text-[#8A6A52]">{{ email }}</a>
                        </div>

                        <a
                            :href="mapsDirections"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-8 inline-flex rounded-md bg-[#3C2418] px-7 py-3.5 text-sm font-medium tracking-wide text-[#F6EBDD] transition hover:bg-[#2C1612]"
                        >
                            Wyznacz trasę
                        </a>

                        <div class="mt-8 flex gap-3">
                            <a
                                href="https://www.instagram.com/facemodeling.expert"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#3C2418]/20 hover:bg-[#3C2418] hover:text-[#F6EBDD]"
                                aria-label="Instagram"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </a>
                            <a
                                href="https://www.facebook.com/profile.php?id=100064131938944"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#3C2418]/20 hover:bg-[#3C2418] hover:text-[#F6EBDD]"
                                aria-label="Facebook"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-[#3C2418]/10 bg-white shadow-sm">
                        <iframe
                            :src="mapsEmbed"
                            title="Mapa dojazdu do gabinetu Face Modeling, Emilianów 8A"
                            class="h-[420px] w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-[#2C1612] px-5 py-8 text-center text-xs text-[#F6EBDD]/45">
            <div class="mb-3 space-x-4">
                <a href="/regulamin" class="hover:text-[#F6EBDD]/80">Regulamin</a>
                <a href="/polityka-prywatnosci" class="hover:text-[#F6EBDD]/80">Polityka prywatności</a>
            </div>
            <p>&copy; {{ new Date().getFullYear() }} Emilia Wiśniewska. Wszelkie prawa zastrzeżone.</p>
        </footer>
    </div>
</template>

<style>
.fm-sans {
    font-family: Outfit, "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
}

.fm-serif {
    font-family: "Cormorant Garamond", "Times New Roman", serif;
}
</style>
