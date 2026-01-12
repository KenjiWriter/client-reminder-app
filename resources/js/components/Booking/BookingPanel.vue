<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { format, addDays, startOfWeek, endOfWeek, isSameDay } from 'date-fns';
import { pl } from 'date-fns/locale';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ChevronLeft, ChevronRight, CheckCircle2, Calendar as CalendarIcon, Clock, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { cn } from '@/lib/utils'; // Assuming this util exists, or I'll implement simple class merge

// Props
const props = defineProps<{
    services: Record<string, any[]>; // Grouped by category
}>();

// Steps
const steps = ['Wybór usługi', 'Termin', 'Dane', 'Podsumowanie'];
const currentStep = ref(0);

// Selection State
const selectedService = ref<any>(null);
const selectedDate = ref<Date | null>(null);
const selectedTime = ref<string | null>(null);
const availableSlots = ref<any[]>([]);
const isLoadingSlots = ref(false);

// Form State
const form = useForm({
    first_visit: false,
    goals: '',
    full_name: '',
    phone: '',
    email: '',
    terms_accepted: false,
});

const isSubmitting = ref(false);
const submittedSuccess = ref(false);

// Formatting Helpers
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(amount);
};

const formatDuration = (minutes: number) => {
    if (minutes >= 60) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return m > 0 ? `${h}h ${m}min` : `${h}h`;
    }
    return `${minutes} min`;
};

// --- Step 1: Service Selection ---
const selectService = (service: any) => {
    selectedService.value = service;
    nextStep();
};

// --- Step 2: Calendar & Slots ---
const currentMonth = ref(new Date());

const calendarDays = computed(() => {
    const start = startOfWeek(currentMonth.value, { weekStartsOn: 1 });
    const end = endOfWeek(addDays(start, 41), { weekStartsOn: 1 }); // 6 weeks view
    // Simplified: Just 2 weeks view for mobile friendly? Or standard month grid.
    // Let's do a simple horizontal day scroller for mobile-first vibe or standard grid.
    // Standard grid is safer.
    
    // For MVP, let's just do a simple list of days for the next 14 days maybe?
    // User requested "Calendar: Real-time availability".
    // Let's stick to a standard date picker or simple day list.
    // I'll implement a simple day picker (next 30 days scrollable horizontally)
    
    const days = [];
    let d = new Date();
    for(let i=0; i<30; i++) {
        days.push(addDays(d, i));
    }
    return days;
});

const fetchAvailability = async (date: Date) => {
    if (!selectedService.value) return;
    
    selectedDate.value = date;
    selectedTime.value = null; // Reset time
    isLoadingSlots.value = true;
    availableSlots.value = [];

    try {
        const response = await axios.get(route('api.booking.availability'), {
            params: {
                date: format(date, 'yyyy-MM-dd'),
                duration: selectedService.value.duration_minutes
            }
        });
        availableSlots.value = response.data.slots;
    } catch (e) {
        console.error("Failed to fetch slots", e);
    } finally {
        isLoadingSlots.value = false;
    }
};

const selectTime = (time: string) => {
    selectedTime.value = time;
};

// --- Step Navigation ---
const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// --- Submit ---
const submitBooking = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    try {
        await axios.post(route('api.booking.store'), {
            service_id: selectedService.value.id,
            date: format(selectedDate.value!, 'yyyy-MM-dd'),
            time: selectedTime.value, // "HH:mm"
            ...form.data(),
        });
        submittedSuccess.value = true;
    } catch (e) {
        console.error("Booking failed", e);
        // Handle validation errors - simplified for MVP
        alert("Wystąpił błąd podczas rezerwacji. Sprawdź poprawność danych.");
    } finally {
        isSubmitting.value = false;
    }
};

</script>

<template>
    <div class="w-full max-w-4xl mx-auto rounded-2xl shadow-xl border overflow-hidden min-h-[600px] flex flex-col" :class="$attrs.class">
        <!-- Progress Header -->
        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-white/5" v-if="!submittedSuccess">
             <Button variant="ghost" size="sm" @click="prevStep" :disabled="currentStep === 0" class="text-white/60 hover:text-white hover:bg-white/10">
                 <ArrowLeft class="w-4 h-4 mr-1" /> Wstecz
             </Button>
             <div class="text-sm font-medium text-white/90">
                 Krok {{ currentStep + 1 }}/{{ steps.length }}: {{ steps[currentStep] }}
             </div>
             <div class="w-20"></div> <!-- Spacer -->
        </div>

        <!-- Success Screen -->
        <div v-if="submittedSuccess" class="flex-1 flex flex-col items-center justify-center p-12 text-center animate-in fade-in zoom-in duration-500">
            <div class="w-20 h-20 bg-green-900/40 rounded-full flex items-center justify-center mb-6 text-green-400">
                <CheckCircle2 class="w-10 h-10" />
            </div>
            <h2 class="text-3xl font-bold mb-4 text-white">Rezerwacja wysłana!</h2>
            <p class="text-lg text-white/60 max-w-md mb-8">
                Dziękujemy za zgłoszenie. Otrzymasz potwierdzenie SMS/Email po zaakceptowaniu terminu przez administratora.
            </p>
            <Button size="lg" @click="$emit('close')" class="min-w-[200px] bg-white text-black hover:bg-white/90">
                Wróć do strony głównej
            </Button>
        </div>

        <!-- Wizard Content -->
        <div v-else class="flex-1 p-6 md:p-8 overflow-y-auto">
            
            <!-- Step 1: Services -->
            <div v-if="currentStep === 0" class="space-y-8 animate-in slide-in-from-right duration-300">
                <div v-for="(categoryServices, categoryName) in services" :key="categoryName">
                    <h3 class="text-xl font-semibold mb-4 text-white/90">{{ categoryName || 'Pozostałe' }}</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <button 
                            v-for="service in categoryServices" 
                            :key="service.id"
                            @click="selectService(service)"
                            class="text-left group relative p-6 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all hover:shadow-lg active:scale-[0.98]"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-lg text-white group-hover:text-white transition-colors">{{ service.name }}</span>
                                <span class="font-medium bg-white/10 px-2 py-1 rounded text-xs text-white/80">
                                     {{ formatDuration(service.duration_minutes) }}
                                </span>
                            </div>
                            <p class="text-white/60 text-sm line-clamp-2 mb-3">{{ service.description }}</p>
                            <div class="font-semibold text-white/90">
                                {{ formatCurrency(Number(service.price)) }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Calendar -->
            <div v-if="currentStep === 1" class="h-full flex flex-col md:flex-row gap-8 animate-in slide-in-from-right duration-300">
                <!-- Days List -->
                <div class="md:w-1/3 border-r border-white/10 pr-4 overflow-y-auto max-h-[500px]">
                    <h3 class="text-lg font-semibold mb-4 text-white/90">Wybierz dzień</h3>
                    <div class="space-y-2">
                        <button 
                            v-for="day in calendarDays" 
                            :key="day.toISOString()"
                            @click="fetchAvailability(day)"
                            class="w-full p-3 rounded-lg text-left flex justify-between items-center transition-colors border border-transparent"
                            :class="selectedDate && isSameDay(selectedDate, day) ? 'bg-white text-black font-semibold' : 'hover:bg-white/10 text-white/80'"
                        >
                            <span>{{ format(day, 'EEEE, d MMMM', { locale: pl }) }}</span>
                        </button>
                    </div>
                </div>

                <!-- Slots -->
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-4 text-white/90">
                        <span v-if="selectedDate">Godziny dla {{ format(selectedDate, 'd MMMM', { locale: pl }) }}</span>
                        <span v-else>Wybierz datę aby zobaczyć godziny</span>
                    </h3>
                    
                    <div v-if="isLoadingSlots" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                    </div>
                    
                    <div v-else-if="selectedDate && availableSlots.length === 0" class="text-center py-12 text-white/50">
                        Brak wolnych terminów w tym dniu.
                    </div>

                    <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        <button 
                            v-for="slot in availableSlots" 
                            :key="slot.start"
                            @click="selectTime(slot.display)"
                            class="p-3 rounded-lg border text-center transition-all bg-white/5"
                            :class="selectedTime === slot.display ? 'bg-white text-black border-white' : 'border-white/10 text-white/80 hover:border-white/50 hover:bg-white/10'"
                        >
                            {{ slot.display }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Intake -->
            <div v-if="currentStep === 2" class="max-w-md mx-auto space-y-6 animate-in slide-in-from-right duration-300">
                <div class="space-y-4">
                    <div class="p-4 bg-white/5 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-white">{{ selectedService?.name }}</span>
                            <span class="text-sm text-white/60">{{ formatDuration(selectedService?.duration_minutes) }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-white/80">
                            <CalendarIcon class="w-4 h-4" />
                            <span>{{ format(selectedDate!, 'd MMMM yyyy (EEEE)', { locale: pl }) }}</span>
                            <Clock class="w-4 h-4 ml-2" />
                            <span>{{ selectedTime }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                         <input type="checkbox" id="first_visit" v-model="form.first_visit" class="h-4 w-4 rounded border-white/20 bg-white/5 text-white focus:ring-offset-gray-900">
                         <Label for="first_visit" class="font-medium text-white/90">To moja pierwsza wizyta</Label>
                    </div>

                    <div class="space-y-2">
                        <Label for="goals" class="text-white/80">Na czym zależy Ci najbardziej podczas tej wizyty?</Label>
                        <Textarea id="goals" v-model="form.goals" placeholder="Np. relaks, napięcie szczęki, lifting..." class="bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-white/30" />
                    </div>

                    <div class="space-y-2">
                        <Label for="full_name" class="text-white/80">Imię i nazwisko</Label>
                        <Input id="full_name" v-model="form.full_name" required class="bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-white/30" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                         <div class="space-y-2">
                            <Label for="phone" class="text-white/80">Telefon</Label>
                            <Input id="phone" type="tel" v-model="form.phone" required placeholder="500 600 700" class="bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-white/30" />
                        </div>
                        <div class="space-y-2">
                            <Label for="email" class="text-white/80">Email</Label>
                            <Input id="email" type="email" v-model="form.email" class="bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-white/30" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Summary -->
            <div v-if="currentStep === 3" class="max-w-md mx-auto space-y-8 animate-in slide-in-from-right duration-300">
                <div class="text-center">
                    <h3 class="text-2xl font-bold mb-2 text-white">Podsumowanie</h3>
                    <p class="text-white/60">Sprawdź czy wszystko się zgadza</p>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-xl p-6 space-y-4 shadow-sm">
                    <div class="flex justify-between py-2 border-b border-white/10">
                        <span class="text-white/60">Usługa</span>
                        <span class="font-medium text-right text-white">{{ selectedService?.name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-white/10">
                        <span class="text-white/60">Termin</span>
                        <span class="font-medium text-right text-white">
                            {{ format(selectedDate!, 'd MMMM yyyy', { locale: pl }) }}<br>
                            godz. {{ selectedTime }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-white/10">
                        <span class="text-white/60">Czas trwania</span>
                        <span class="font-medium text-white">{{ formatDuration(selectedService?.duration_minutes) }}</span>
                    </div>
                    <div class="flex justify-between py-2 text-lg font-bold">
                        <span class="text-white/90">Do zapłaty (na miejscu)</span>
                        <span class="text-white">{{ formatCurrency(Number(selectedService?.price)) }}</span>
                    </div>
                </div>

                <div class="bg-red-900/20 text-red-200 border border-red-900/30 p-4 rounded-lg text-sm">
                    <strong>Polityka odwoływania wizyt:</strong><br>
                    Anulowanie wizyty w czasie krótszym niż 24h przed terminem wiąże się z opłatą w wysokości 50% ceny wizyty.
                </div>

                <div class="flex items-start gap-3 p-2 rounded-lg hover:bg-white/5 transition-colors">
                    <input type="checkbox" id="terms" v-model="form.terms_accepted" class="mt-0.5 h-5 w-5 rounded border-white/20 bg-white/5 text-primary focus:ring-offset-gray-900 cursor-pointer">
                    <label for="terms" class="text-sm leading-relaxed cursor-pointer text-white/70 select-none">
                        Akceptuję <a href="/regulamin" target="_blank" class="text-blue-300 hover:text-blue-200 underline decoration-blue-300/30 underline-offset-4 transition-colors" @click.stop>regulamin</a> oraz <a href="/polityka-prywatnosci" target="_blank" class="text-blue-300 hover:text-blue-200 underline decoration-blue-300/30 underline-offset-4 transition-colors" @click.stop>politykę prywatności</a> i zgadzam się na przetwarzanie moich danych osobowych w celu realizacji usługi.
                    </label>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div class="p-4 border-t border-white/10 bg-white/5 flex justify-between" v-if="!submittedSuccess">
            <div class="text-xs text-white/40 self-center hidden sm:block">
                 Facemodeling Studio
            </div>
            
            <div class="flex gap-4 w-full sm:w-auto justify-end">
                 <!-- Next Button handling -->
                 <Button 
                    v-if="currentStep === 1" 
                    :disabled="!selectedTime"
                    @click="nextStep"
                    class="w-full sm:w-auto bg-white text-black hover:bg-white/90"
                 >
                    Dalej <ArrowRight class="w-4 h-4 ml-2" />
                 </Button>

                 <Button 
                    v-else-if="currentStep === 2" 
                    :disabled="!form.full_name || !form.phone"
                    @click="nextStep"
                    class="w-full sm:w-auto bg-white text-black hover:bg-white/90"
                 >
                    Podsumowanie <ArrowRight class="w-4 h-4 ml-2" />
                 </Button>

                 <Button 
                    v-else-if="currentStep === 3" 
                    :disabled="!form.terms_accepted || isSubmitting"
                    @click="submitBooking"
                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white border-none"
                 >
                    <span v-if="isSubmitting">Wysyłanie...</span>
                    <span v-else>Potwierdzam rezerwację</span>
                 </Button>
            </div>
        </div>
    </div>
</template>
