<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Head, router, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Camera,
    Compass,
    Edit,
    Hammer,
    Heart,
    Info,
    MapPin,
    PawPrint,
    Ship,
    Stethoscope,
    Tag,
    User
} from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

// Define props
const props = defineProps({
    animalProfile: {
        type: Object,
        required: true,
    },
});

// Format date function
const formatDate = (dateString) => {
    if (!dateString) return 'Not specified';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Calculate age from birthdate
const calculateAge = (birthdate, currentAge) => {
    if (!birthdate) return currentAge + ' years';
    
    const birth = new Date(birthdate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age + ' years';
};

// Modal states
const showAdoptModal = ref(false);

// Open and close adopt modal
const openAdoptModal = () => {
    showAdoptModal.value = true;
};

const closeAdoptModal = () => {
    showAdoptModal.value = false;
};

// Mark as adopted function
const markAsAdopted = () => {
    router.post(
        route('animal-profiles.mark-as-adopted', props.animalProfile.id),
        {},
        {
            onSuccess: () => {
                closeAdoptModal();
            },
        },
    );
};

// Navigate to edit page
const editAnimalProfile = () => {
    router.visit(route('animal-profiles.edit', props.animalProfile.id));
};

// Navigate back to list
const backToList = () => {
    router.visit(route('animal-profiles.show'));
};
</script>

<template>
    <AppSidebar>
        <Head :title="`Animal Profile - ${animalProfile.name}`" />

        <div class="min-h-screen w-full bg-gradient-to-b from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
            <div class="mx-auto w-full px-4 py-6 sm:px-6 lg:px-8">
                <!-- Back button -->
                <Button 
                    @click="backToList" 
                    variant="outline" 
                    class="mb-4 flex items-center gap-2 border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Manifest
                </Button>

                <!-- Header with Noah's Ark wooden theme -->
                <div class="relative mb-8 overflow-hidden rounded-xl bg-gradient-to-r from-amber-800 to-amber-700 shadow-lg">
                    <!-- Wood grain texture overlay -->
                    <div class="pointer-events-none absolute inset-0 opacity-10">
                        <svg width="100%" height="100%">
                            <filter id="wood-filter-header">
                                <feTurbulence type="fractalNoise" baseFrequency="0.01" numOctaves="3" seed="2" />
                                <feDisplacementMap in="SourceGraphic" scale="10" />
                            </filter>
                            <rect width="100%" height="100%" filter="url(#wood-filter-header)" fill="#451a03" />
                        </svg>
                    </div>

                    <!-- Decorative nails/rivets -->
                    <div class="pointer-events-none absolute top-0 right-0 left-0 flex h-12 items-center justify-between px-8 opacity-50">
                        <div class="h-2 w-2 rounded-full bg-amber-200"></div>
                        <div class="h-2 w-2 rounded-full bg-amber-200"></div>
                        <div class="h-2 w-2 rounded-full bg-amber-200"></div>
                        <div class="h-2 w-2 rounded-full bg-amber-200"></div>
                    </div>

                    <div class="relative flex flex-col justify-between gap-4 p-6 md:flex-row md:items-center md:p-8">
                        <div class="flex items-start gap-4">
                            <div class="rounded-full bg-amber-100 p-3 shadow-lg">
                                <PawPrint class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">{{ animalProfile.name }}</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">
                                    {{ animalProfile.species }} • {{ animalProfile.breed }} • {{ animalProfile.color }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button 
                                v-if="!animalProfile.is_adopted"
                                @click="openAdoptModal" 
                                class="flex items-center gap-2 bg-red-600 text-white hover:bg-red-700"
                            >
                                <Heart class="h-4 w-4" />
                                Mark as Disembarked
                            </Button>
                            
                            <Button 
                                @click="editAnimalProfile" 
                                class="flex items-center gap-2 bg-amber-100 text-amber-800 hover:bg-amber-50"
                            >
                                <Edit class="h-4 w-4" />
                                Edit Profile
                            </Button>
                            
                            <Link :href="route('admin.animal-location', animalProfile.id)">
                                <Button 
                                    class="flex items-center gap-2 border-amber-300 bg-white text-amber-800 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300"
                                >
                                    <MapPin class="h-4 w-4" />
                                    View Location
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute right-2 bottom-2 opacity-30">
                        <Hammer class="h-12 w-12 rotate-12 transform text-amber-200" />
                    </div>
                </div>

                <!-- Main content -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left column - Image and status -->
                    <div class="space-y-6">
                        <!-- Profile Image -->
                        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-800">
                                <h3 class="flex items-center text-lg font-medium text-amber-800 dark:text-amber-200">
                                    <Camera class="mr-2 h-5 w-5" />
                                    Profile Image
                                </h3>
                            </div>
                            <div class="flex items-center justify-center p-6">
                                <div class="relative h-64 w-64 overflow-hidden rounded-lg border-2 border-amber-300 bg-amber-100 dark:border-amber-700">
                                    <img 
                                        v-if="animalProfile.image" 
                                        :src="`/${animalProfile.image}`" 
                                        :alt="animalProfile.name" 
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <PawPrint class="h-20 w-20 text-amber-400" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Card -->
                        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-800">
                                <h3 class="flex items-center text-lg font-medium text-amber-800 dark:text-amber-200">
                                    <Info class="mr-2 h-5 w-5" />
                                    Status
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-amber-700 dark:text-amber-300">Current Status:</span>
                                        <span 
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="animalProfile.is_adopted 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' 
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'"
                                        >
                                            {{ animalProfile.is_adopted ? 'Disembarked' : 'On Board' }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-amber-700 dark:text-amber-300">ID:</span>
                                        <span class="text-sm font-mono text-amber-800 dark:text-amber-200">{{ animalProfile.id }}</span>
                                    </div>
                                    
                                    <div v-if="animalProfile.device_id" class="flex items-center justify-between">
                                        <span class="text-sm text-amber-700 dark:text-amber-300">Tracker ID:</span>
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-mono text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            {{ animalProfile.device_id }}
                                        </span>
                                    </div>
                                    
                                    <div v-if="animalProfile.traccar_id" class="flex items-center justify-between">
                                        <span class="text-sm text-amber-700 dark:text-amber-300">Traccar ID:</span>
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-mono text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            {{ animalProfile.traccar_id }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right column - Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-800">
                                <h3 class="flex items-center text-lg font-medium text-amber-800 dark:text-amber-200">
                                    <Tag class="mr-2 h-5 w-5" />
                                    Basic Information
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Name</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ animalProfile.name }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Species</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ animalProfile.species }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Breed</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ animalProfile.breed }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Color</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ animalProfile.color }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Gender</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ animalProfile.gender }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Age</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                            {{ calculateAge(animalProfile.birthdate, animalProfile.age) }}
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Birthdate</p>
                                        <p class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                            {{ formatDate(animalProfile.birthdate) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-800">
                                <h3 class="flex items-center text-lg font-medium text-amber-800 dark:text-amber-200">
                                    <Info class="mr-2 h-5 w-5" />
                                    Description
                                </h3>
                            </div>
                            <div class="p-4">
                                <p class="whitespace-pre-line text-amber-900 dark:text-amber-100">{{ animalProfile.description }}</p>
                            </div>
                        </div>

                        <!-- Medical Records -->
                        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-800">
                                <h3 class="flex items-center text-lg font-medium text-amber-800 dark:text-amber-200">
                                    <Stethoscope class="mr-2 h-5 w-5" />
                                    Medical Records
                                </h3>
                            </div>
                            <div class="p-4">
                                <p v-if="animalProfile.medical_records" class="whitespace-pre-line text-amber-900 dark:text-amber-100">
                                    {{ animalProfile.medical_records }}
                                </p>
                                <p v-else class="italic text-amber-500 dark:text-amber-400">No medical records available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark as Adopted Modal -->
        <div v-if="showAdoptModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeAdoptModal"></div>

                <!-- Dialog panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-amber-950"
                >
                    <div class="relative border-b border-green-200 bg-green-50 px-6 py-4 dark:border-green-800 dark:bg-green-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-green-800 dark:text-green-100">
                                <Heart class="mr-2 h-5 w-5 text-red-500" />
                                Disembark from Ark
                            </h3>
                            <button @click="closeAdoptModal" class="text-green-500 hover:text-green-700 dark:hover:text-green-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-amber-950">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <CheckCircle class="h-6 w-6 text-green-600" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Confirm Disembarkation</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to mark
                                        <span class="font-medium text-amber-700 dark:text-amber-300">{{ animalProfile.name }}</span>
                                        as disembarked from Noah's Ark? This will update their status in the system.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-amber-900">
                        <Button
                            @click="markAsAdopted"
                            class="inline-flex w-full justify-center bg-green-600 text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Confirm Disembarkation
                        </Button>
                        <Button
                            variant="outline"
                            @click="closeAdoptModal"
                            class="mt-3 inline-flex w-full justify-center sm:mt-0 sm:w-auto sm:text-sm dark:border-amber-700 dark:text-amber-300"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebar>
</template>

<style scoped>
/* Custom scrollbar for the content */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(120, 53, 15, 0.1);
}

::-webkit-scrollbar-thumb {
    background: rgba(217, 119, 6, 0.3);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(217, 119, 6, 0.5);
}

/* Wood grain animation for texture overlays */
@keyframes grain {
    0% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(1px, 1px) scale(1.01);
    }
    100% {
        transform: translate(0, 0) scale(1);
    }
}
</style>