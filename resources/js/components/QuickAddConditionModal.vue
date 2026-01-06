<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Props {
    open: boolean;
    preselectedCategory?: 'contraindication' | 'esthetic';
}

const props = withDefaults(defineProps<Props>(), {
    preselectedCategory: 'contraindication',
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'created', conditionType: any): void;
}>();

const name = ref('');
const category = ref<'contraindication' | 'esthetic'>(props.preselectedCategory);
const severity = ref<'high' | 'medium' | 'info'>('medium');
const requiresDate = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Watch for category prop changes
watch(() => props.preselectedCategory, (newCat) => {
    category.value = newCat;
});

const resetForm = () => {
    name.value = '';
    category.value = props.preselectedCategory;
    severity.value = 'medium';
    requiresDate.value = false;
    errors.value = {};
};

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post(route('medical-condition-types.store'), {
            name: name.value,
            category: category.value,
            severity: severity.value,
            requires_date: requiresDate.value,
        });

        if (response.status === 201 && response.data.conditionType) {
            emit('created', response.data.conditionType);
            resetForm();
            emit('close');
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { general: 'Wystąpił błąd podczas dodawania' };
        }
    } finally {
        processing.value = false;
    }
};

const close = () => {
    resetForm();
    emit('close');
};
</script>

<script lang="ts">
import { watch } from 'vue';
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Dodaj nowy typ schorzenia</DialogTitle>
                <DialogDescription>
                    Wprowadź dane dla nowego typu schorzenia. Będzie on natychmiast dostępny na tej stronie.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="quick-name">Nazwa</Label>
                    <Input 
                        id="quick-name" 
                        v-model="name" 
                        required 
                        placeholder="np. Alergia na lidokainę" 
                        :disabled="processing"
                    />
                    <div v-if="errors.name" class="text-sm text-destructive">
                        {{ errors.name[0] }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-category">Kategoria</Label>
                    <Select v-model="category" required :disabled="processing">
                        <SelectTrigger id="quick-category">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="contraindication">Przeciwwskazanie</SelectItem>
                            <SelectItem value="esthetic">Zabieg estetyczny</SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="errors.category" class="text-sm text-destructive">
                        {{ errors.category[0] }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-severity">Poziom ważności</Label>
                    <Select v-model="severity" required :disabled="processing">
                        <SelectTrigger id="quick-severity">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="high">Wysokie (czerwona odznaka)</SelectItem>
                            <SelectItem value="medium">Średnie (żółta odznaka)</SelectItem>
                            <SelectItem value="info">Info (szara odznaka)</SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="errors.severity" class="text-sm text-destructive">
                        {{ errors.severity[0] }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox id="quick-requires-date" v-model:checked="requiresDate" :disabled="processing" />
                    <Label for="quick-requires-date" class="font-normal cursor-pointer">
                        Wymaga daty (np. dla zabiegów)
                    </Label>
                </div>

                <div v-if="errors.general" class="text-sm text-destructive">
                    {{ errors.general }}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="close" :disabled="processing">
                        Anuluj
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Dodawanie...' : 'Dodaj i zaznacz' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
