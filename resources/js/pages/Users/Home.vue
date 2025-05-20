<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
// import { Textarea } from '@/components/ui/textarea';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Calendar, Heart, Info, MapPin, PawPrint, Search, Upload, User, Droplets } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const props = defineProps({
    animals: Object,
    filters: Object,
});

const breadcrumbs = [{ title: 'Home', href: '/home' }];

const search = ref(props.filters.search || '');
const selectedAnimal = ref(null);
const isModalOpen = ref(false);

// Search form
const searchForm = useForm({
    search: props.filters.search || '',
});

// Adoption request form
const adoptionForm = useForm({
    animal_profile_id: '',
    question1: '',
    question2: '',
    question3: '',
    valid_id: null,
    selfie_with_id: null,
});

const validIdPreview = ref('');
const selfiePreview = ref('');

// Handle file uploads with previews
const handleFileUpload = (event, field) => {
    const file = event.target.files[0];
    if (!file) return;

    adoptionForm[field] = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        if (field === 'valid_id') {
            validIdPreview.value = e.target.result;
        } else {
            selfiePreview.value = e.target.result;
        }
    };
    reader.readAsDataURL(file);
};

// Submit adoption request
const submitAdoption = () => {
    adoptionForm.post(route('adoption.store'), {
        preserveScroll: true,
        onSuccess: () => {
            adoptionForm.reset();
            validIdPreview.value = '';
            selfiePreview.value = '';
            isModalOpen.value = false;
        },
    });
};

// Open adoption dialog with selected animal
const openAdoptionModal = (animal) => {
    selectedAnimal.value = animal;
    adoptionForm.animal_profile_id = animal.id;
    isModalOpen.value = true;
};

// Close the modal and reset form
const closeModal = () => {
    isModalOpen.value = false;
};

// Debounced search
const debouncedSearch = debounce(() => {
    searchForm.get(route('user.home'), {
        preserveState: true,
        preserveScroll: true,
        only: ['animals', 'filters'],
    });
}, 300);

watch(search, (value) => {
    searchForm.search = value;
    debouncedSearch();
});

// Get animal age in a readable format
const getAnimalAge = (birthdate) => {
    if (!birthdate) return 'Unknown';

    const birth = new Date(birthdate);
    const now = new Date();
    const years = now.getFullYear() - birth.getFullYear();
    const months = now.getMonth() - birth.getMonth();

    if (years > 0) {
        return `${years} ${years === 1 ? 'year' : 'years'}`;
    } else {
        return `${months} ${months === 1 ? 'month' : 'months'}`;
    }
};

// Get appropriate badge color based on animal status
const getStatusColor = (status) => {
    switch (status) {
        case 'available':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-amber-100 text-amber-800';
        case 'adopted':
            return 'bg-amber-100 text-amber-800';
        default:
            return 'bg-amber-100 text-amber-800';
    }
};


// Navigate to animal profile page
const viewAnimalProfile = (animalId) => {
    // const route = useRoute();
    router.visit(route('animal-profile.show', { animalProfile: animalId }));
};
</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <!-- User Navbar -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <!-- Hero Section -->
        <div class="relative bg-gradient-to-r from-amber-700 to-amber-900 py-10 text-white">
            <!-- Background overlays -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="absolute inset-0 bg-[url('/images/paw-pattern.png')] bg-cover bg-center opacity-10"></div>
            </div>

            <!-- Content -->
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Noah's Ark Animal Adoption</h1>
                    <p class="mt-3 max-w-xl text-base text-amber-100 sm:text-lg">
                        Find your perfect companion aboard our ark. Browse our available animals and start your adoption journey today.
                    </p>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Search and Filters -->
            <div class="mb-8 rounded-xl border border-amber-200 bg-amber-100 p-4 shadow-sm sm:p-6">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                    <div class="relative flex-grow">
                        <Search class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-amber-600" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or breed..."
                            class="border-amber-300 bg-white pl-10 focus:border-amber-500 focus:ring-amber-500"
                        />
                    </div>
                    <div class="flex space-x-2">
                        <Button variant="outline" class="flex items-center gap-2 border-amber-600 text-amber-800 hover:bg-amber-200">
                            <PawPrint class="h-4 w-4" />
                            <span class="hidden sm:inline">Filter</span>
                        </Button>
                        <Button variant="outline" class="flex items-center gap-2 border-amber-600 text-amber-800 hover:bg-amber-200">
                            <Info class="h-4 w-4" />
                            <span class="hidden sm:inline">How to Adopt</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Animals Grid -->
            <div v-if="props.animals.data.length > 0">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="animal in props.animals.data"
                        :key="animal.id"
                        class="group overflow-hidden rounded-xl border border-amber-200 bg-white shadow-md transition-all duration-300 hover:shadow-lg"
                    >
                        <div class="relative h-64 overflow-hidden cursor-pointer" @click="viewAnimalProfile(animal.id)">
                            <img
                                :src="`${animal.image}` || '/placeholder.svg?height=300&width=400'"
                                :alt="animal.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div class="absolute top-3 right-3">
                                <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getStatusColor(animal.status)]">
                                    {{ animal.status }}
                                </span>
                            </div>
                            <button
                                class="absolute top-12 right-3 rounded-full bg-white/80 p-2 text-amber-600 backdrop-blur-sm transition-all hover:bg-white hover:text-amber-700"
                                title="Add to favorites"
                                @click.stop
                            >
                                <Heart class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="bg-amber-50 p-5">
                            <div class="mb-3 flex items-center justify-between cursor-pointer" @click="viewAnimalProfile(animal.id)">
                                <h3 class="text-xl font-bold text-amber-900">{{ animal.name }}</h3>
                                <span class="text-sm font-medium text-amber-700">{{ animal.breed }}</span>
                            </div>

                            <div class="mb-4 flex flex-wrap gap-2 cursor-pointer" @click="viewAnimalProfile(animal.id)">
                                <div class="flex items-center text-sm text-amber-700">
                                    <Calendar class="mr-1 h-4 w-4" />
                                    {{ getAnimalAge(animal.birthdate) }}
                                </div>
                                <div class="flex items-center text-sm text-amber-700">
                                    <MapPin class="mr-1 h-4 w-4" />
                                    {{ animal.location || 'Shelter' }}
                                </div>
                                <div class="flex items-center text-sm text-amber-700">
                                    <User class="mr-1 h-4 w-4" />
                                    {{ animal.gender || 'Unknown' }}
                                </div>
                                <div class="flex items-center text-sm text-amber-700">
                                    <Droplets class="mr-1 h-4 w-4" />
                                    {{ animal.color || 'Mixed' }}
                                </div>
                            </div>

                            <div class="mb-2 flex items-center text-sm text-amber-700">
                                <span class="font-medium">Species:</span>
                                <span class="ml-2">{{ animal.species || 'Unknown' }}</span>
                            </div>

                            <p class="mb-4 line-clamp-2 text-sm text-amber-700 cursor-pointer" @click="viewAnimalProfile(animal.id)">
                                {{ animal.description || 'No description available for this pet.' }}
                            </p>

                            <Button
                                @click.stop="openAdoptionModal(animal)"
                                class="w-full bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                            >
                                Adopt Me
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex items-center justify-between">
                    <div class="text-sm text-amber-700">
                        Showing {{ props.animals.from }} to {{ props.animals.to }} of {{ props.animals.total }} animals
                    </div>
                    <div class="flex space-x-1">
                        <Button
                            v-for="link in props.animals.links"
                            :key="link.label"
                            :disabled="!link.url"
                            :class="{ 'bg-amber-700 text-white': link.active, 'border-amber-300 text-amber-700': !link.active }"
                            variant="outline"
                            size="sm"
                            @click="searchForm.get(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center rounded-xl border border-amber-200 bg-white p-12 text-center shadow-sm">
                <div class="mb-4 rounded-full bg-amber-100 p-4">
                    <PawPrint class="h-10 w-10 text-amber-700" />
                </div>
                <h3 class="mb-2 text-xl font-medium text-amber-900">No animals found</h3>
                <p class="mb-6 text-amber-700">
                    We couldn't find any animals matching your search criteria. Try adjusting your filters or check back later.
                </p>
                <Button
                    @click="
                        search = '';
                        searchForm.get(route('user.home'));
                    "
                    class="bg-amber-700 text-white hover:bg-amber-800"
                >
                    Clear Search
                </Button>
            </div>
        </main>

        <!-- Adoption Request Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent v-if="selectedAnimal" class="border-amber-300 bg-amber-50 sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-amber-900">Adopt {{ selectedAnimal.name }}</DialogTitle>
                    <DialogDescription class="text-amber-700">
                        Please complete this form to submit your adoption request. We'll review your application and contact you soon.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 grid gap-6">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="h-32 w-32 flex-shrink-0 overflow-hidden rounded-lg border border-amber-300">
                            <img
                                :src="`${selectedAnimal.image}` || '/placeholder.svg?height=150&width=150'"
                                :alt="selectedAnimal.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-amber-900">{{ selectedAnimal.name }}</h4>
                            <p class="text-sm text-amber-700">{{ selectedAnimal.breed }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        getStatusColor(selectedAnimal.status),
                                    ]"
                                >
                                    {{ selectedAnimal.status }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                    {{ getAnimalAge(selectedAnimal.birthdate) }}
                                </span>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Species:</span> {{ selectedAnimal.species || 'Unknown' }}
                                </p>
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Gender:</span> {{ selectedAnimal.gender || 'Unknown' }}
                                </p>
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Color:</span> {{ selectedAnimal.color || 'Mixed' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitAdoption">
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label
                                    class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Why do you want to adopt this pet?
                                </label>
                                <textarea
                                    v-model="adoptionForm.question1"
                                    placeholder="Tell us why you're interested in adopting this pet..."
                                    rows="3"
                                    class="w-full border-amber-300 bg-white text-amber-900 placeholder:text-amber-400 focus:border-amber-500 focus:ring-amber-500"
                                />
                                <p v-if="adoptionForm.errors.question1" class="text-sm text-red-500">
                                    {{ adoptionForm.errors.question1 }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Describe your living situation and experience with pets
                                </label>
                                <textarea
                                    v-model="adoptionForm.question2"
                                    placeholder="Tell us about your home, family, and experience with pets..."
                                    rows="3"
                                    class="w-full border-amber-300 bg-white text-amber-900 placeholder:text-amber-400 focus:border-amber-500 focus:ring-amber-500"
                                />
                                <p v-if="adoptionForm.errors.question2" class="text-sm text-red-500">
                                    {{ adoptionForm.errors.question2 }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    How will you care for this pet?
                                </label>
                                <textarea
                                    v-model="adoptionForm.question3"
                                    placeholder="Describe how you plan to care for this pet, including exercise, veterinary care, etc..."
                                    rows="3"
                                    class="w-full border-amber-300 bg-white text-amber-900 placeholder:text-amber-400 focus:border-amber-500 focus:ring-amber-500"
                                />
                                <p v-if="adoptionForm.errors.question3" class="text-sm text-red-500">
                                    {{ adoptionForm.errors.question3 }}
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label
                                        class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Upload Valid ID
                                    </label>
                                    <div class="flex flex-col gap-2">
                                        <div
                                            class="flex h-32 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 hover:bg-amber-100"
                                            :class="{ 'border-red-300 bg-red-50': adoptionForm.errors.valid_id }"
                                            @click="$refs.validIdInput.click()"
                                        >
                                            <div v-if="!validIdPreview" class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <Upload class="mb-2 h-8 w-8 text-amber-500" />
                                                <p class="text-xs text-amber-600">Click to upload (JPG, PNG, PDF)</p>
                                            </div>
                                            <img v-else :src="validIdPreview" class="h-full w-full object-contain" />
                                            <input
                                                ref="validIdInput"
                                                type="file"
                                                class="hidden"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                                @change="handleFileUpload($event, 'valid_id')"
                                            />
                                        </div>
                                        <p v-if="adoptionForm.errors.valid_id" class="text-sm text-red-500">
                                            {{ adoptionForm.errors.valid_id }}
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label
                                        class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Upload Selfie with ID
                                    </label>
                                    <div class="flex flex-col gap-2">
                                        <div
                                            class="flex h-32 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 hover:bg-amber-100"
                                            :class="{ 'border-red-300 bg-red-50': adoptionForm.errors.selfie_with_id }"
                                            @click="$refs.selfieInput.click()"
                                        >
                                            <div v-if="!selfiePreview" class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <Upload class="mb-2 h-8 w-8 text-amber-500" />
                                                <p class="text-xs text-amber-600">Click to upload (JPG, PNG)</p>
                                            </div>
                                            <img v-else :src="selfiePreview" class="h-full w-full object-contain" />
                                            <input
                                                ref="selfieInput"
                                                type="file"
                                                class="hidden"
                                                accept=".jpg,.jpeg,.png"
                                                @change="handleFileUpload($event, 'selfie_with_id')"
                                            />
                                        </div>
                                        <p v-if="adoptionForm.errors.selfie_with_id" class="text-sm text-red-500">
                                            {{ adoptionForm.errors.selfie_with_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="mt-6">
                            <Button type="button" variant="outline" class="border-amber-600 text-amber-800 hover:bg-amber-100" @click="closeModal">
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="adoptionForm.processing"
                                class="bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                            >
                                {{ adoptionForm.processing ? 'Submitting...' : 'Submit Adoption Request' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>