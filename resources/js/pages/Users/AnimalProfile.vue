<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Heart, MapPin, MessageCircle, PawPrint, Share2, Upload, Droplets, User } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    animalProfile: Object,
});

const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: props.animalProfile.name, href: '#' },
];

// Adoption request form
const isModalOpen = ref(false);
const adoptionForm = useForm({
    animal_profile_id: props.animalProfile.id,
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
    router.post(route('adoption.store'), adoptionForm, {
        preserveScroll: true,
        onSuccess: () => {
            adoptionForm.reset();
            validIdPreview.value = '';
            selfiePreview.value = '';
            isModalOpen.value = false;
        },
    });
};

// Open adoption dialog
const openAdoptionModal = () => {
    isModalOpen.value = true;
};

// Close the modal and reset form
const closeModal = () => {
    isModalOpen.value = false;
};

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

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'Not specified';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <!-- User Navbar -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="/home" class="inline-flex items-center text-amber-700 hover:text-amber-900">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to all animals
                </a>
            </div>

            <!-- Animal Profile -->
            <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-lg">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <!-- Image Section -->
                    <div class="relative h-[400px] lg:h-full">
                        <img
                            :src="`/${animalProfile.image}` || '/placeholder.svg?height=600&width=800'"
                            :alt="animalProfile.name"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute top-4 right-4 flex space-x-2">
                            <button
                                class="rounded-full bg-white/80 p-2 text-amber-600 backdrop-blur-sm transition-all hover:bg-white hover:text-amber-700"
                                title="Add to favorites"
                            >
                                <Heart class="h-5 w-5" />
                            </button>
                            <button
                                class="rounded-full bg-white/80 p-2 text-amber-600 backdrop-blur-sm transition-all hover:bg-white hover:text-amber-700"
                                title="Share"
                            >
                                <Share2 class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="absolute bottom-4 left-4">
                            <span
                                :class="['inline-flex items-center rounded-full px-3 py-1 text-sm font-medium', getStatusColor(animalProfile.status)]"
                            >
                                {{ animalProfile.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="p-6 lg:p-8">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-amber-900">{{ animalProfile.name }}</h1>
                            <div class="mt-1 flex items-center gap-2">
                                <p class="text-lg text-amber-700">{{ animalProfile.breed }}</p>
                                <span class="text-amber-600">•</span>
                                <p class="text-lg text-amber-700">{{ animalProfile.species }}</p>
                            </div>
                        </div>

                        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <Calendar class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Age</p>
                                        <p class="font-medium">{{ getAnimalAge(animalProfile.birthdate) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <User class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Gender</p>
                                        <p class="font-medium">{{ animalProfile.gender || 'Unknown' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <Droplets class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Color</p>
                                        <p class="font-medium">{{ animalProfile.color || 'Mixed' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <PawPrint class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Species</p>
                                        <p class="font-medium">{{ animalProfile.species || 'Unknown' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <MapPin class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Location</p>
                                        <p class="font-medium">{{ animalProfile.location || 'Shelter' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-3">
                                <div class="flex items-center text-amber-800">
                                    <Calendar class="mr-2 h-5 w-5" />
                                    <div>
                                        <p class="text-xs">Birthdate</p>
                                        <p class="font-medium">{{ formatDate(animalProfile.birthdate) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h2 class="mb-2 text-xl font-semibold text-amber-900">About {{ animalProfile.name }}</h2>
                            <p class="text-amber-700">
                                {{ animalProfile.description || 'No description available for this pet.' }}
                            </p>
                        </div>

                        <div class="mb-6">
                            <h2 class="mb-2 text-xl font-semibold text-amber-900">Details</h2>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Size:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.size || 'Medium' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Color:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.color || 'Mixed' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Species:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.species || 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Gender:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.gender || 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Vaccinated:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.vaccinated ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Neutered/Spayed:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.neutered ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Good with kids:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.good_with_kids ? 'Yes' : 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Good with pets:</span>
                                    <span class="font-medium text-amber-900">{{ animalProfile.good_with_pets ? 'Yes' : 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-amber-600">Birthdate:</span>
                                    <span class="font-medium text-amber-900">{{ formatDate(animalProfile.birthdate) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                            <Button
                                @click="openAdoptionModal"
                                class="flex-1 bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                            >
                                Adopt {{ animalProfile.name }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-md">
                    <h2 class="mb-4 text-xl font-semibold text-amber-900">Adoption Process</h2>
                    <ol class="ml-5 list-decimal space-y-2 text-amber-700">
                        <li>Submit an adoption application through our website</li>
                        <li>Our team will review your application</li>
                        <li>Schedule a virtual meet</li>
                        <li>Finalize the adoption process online</li>
                        <li>Visit the shelter to pick up your new family member!</li>
                    </ol>

                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-md">
                    <h2 class="mb-4 text-xl font-semibold text-amber-900">Adoption Cost</h2>
                    <p class="mb-4 text-amber-700">
                        There are no adoption fees required. Our mission is to find loving homes for our animals, and the entire process is handled
                        online for your convenience.
                    </p>
                </div>
            </div>
        </main>

        <!-- Adoption Request Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="border-amber-300 bg-amber-50 sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-amber-900">Adopt {{ animalProfile.name }}</DialogTitle>
                    <DialogDescription class="text-amber-700">
                        Please complete this form to submit your adoption request. We'll review your application and contact you soon.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 grid gap-6">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="h-32 w-32 flex-shrink-0 overflow-hidden rounded-lg border border-amber-300">
                            <img
                                :src="`/${animalProfile.image}` || '/placeholder.svg?height=150&width=150'"
                                :alt="animalProfile.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-amber-900">{{ animalProfile.name }}</h4>
                            <p class="text-sm text-amber-700">{{ animalProfile.breed }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        getStatusColor(animalProfile.status),
                                    ]"
                                >
                                    {{ animalProfile.status }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                    {{ getAnimalAge(animalProfile.birthdate) }}
                                </span>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Species:</span> {{ animalProfile.species || 'Unknown' }}
                                </p>
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Gender:</span> {{ animalProfile.gender || 'Unknown' }}
                                </p>
                                <p class="text-sm text-amber-700">
                                    <span class="font-medium">Color:</span> {{ animalProfile.color || 'Mixed' }}
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