<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    Camera,
    CheckCircle,
    Compass,
    Edit,
    Eye,
    Filter,
    Hammer,
    Heart,
    PawPrint,
    PlusCircle,
    Search,
    Ship,
    Trash2,
    Upload,
    X,
    MapPin 
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// Access the route function from the usePage hook
const page = usePage();

// Define props
const props = defineProps({
    animals: {
        type: Array,
        required: true,
    },
});

// Search and filter state
const searchQuery = ref('');
const filterAdopted = ref('all'); // 'all', 'adopted', 'not-adopted'

// Modal states
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showAdoptModal = ref(false);

// Selected animal for operations
const selectedAnimal = ref(null);

// Form data
const form = ref({
    name: '',
    age: '',
    breed: '',
    color: '',
    gender: '',
    species: '',
    birthdate: '',
    description: '',
    medical_records: '',
    unique_id: '',
    profile_picture: null,
});

// Edit form data
const editForm = ref({
    id: '',
    name: '',
    age: '',
    breed: '',
    color: '',
    gender: '',
    species: '',
    birthdate: '',
    description: '',
    medical_records: '',
    unique_id: '',
    profile_picture: null,
});

// Form errors
const errors = ref({});
const editErrors = ref({});

// Preview images
const previewImage = ref(null);
const editPreviewImage = ref(null);

// Computed filtered animals
const filteredAnimals = computed(() => {
    return props.animals.filter((animal) => {
        // Apply search filter
        const matchesSearch =
            animal.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            animal.breed.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (animal.description && animal.description.toLowerCase().includes(searchQuery.value.toLowerCase()));

        // Apply adoption status filter
        const matchesAdoptionStatus =
            filterAdopted.value === 'all' ||
            (filterAdopted.value === 'adopted' && animal.is_adopted) ||
            (filterAdopted.value === 'not-adopted' && !animal.is_adopted);

        return matchesSearch && matchesAdoptionStatus;
    });
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredAnimals.value.length / itemsPerPage));
const paginatedAnimals = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredAnimals.value.slice(start, end);
});

// Methods
const openDeleteModal = (animal) => {
    selectedAnimal.value = animal;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedAnimal.value = null;
};

const deleteAnimal = () => {
    if (selectedAnimal.value) {
        router.delete(route('animal-profiles.destroy', selectedAnimal.value.id), {
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    }
};

const openAdoptModal = (animal) => {
    selectedAnimal.value = animal;
    showAdoptModal.value = true;
};

const closeAdoptModal = () => {
    showAdoptModal.value = false;
    selectedAnimal.value = null;
};

const markAsAdopted = () => {
    if (selectedAnimal.value) {
        router.post(
            route('animal-profiles.mark-as-adopted', selectedAnimal.value.id),
            {},
            {
                onSuccess: () => {
                    closeAdoptModal();
                },
            },
        );
    }
};

const openEditModal = (animal) => {
    selectedAnimal.value = animal;

    // Populate edit form with animal data
    editForm.value = {
        id: animal.id,
        name: animal.name,
        age: animal.age,
        breed: animal.breed,
        color: animal.color || '',
        gender: animal.gender || '',
        species: animal.species || '',
        birthdate: animal.birthdate || '',
        description: animal.description || '',
        medical_records: animal.medical_records || '',
        unique_id: animal.device_id || '',
        profile_picture: null,
    };

    // Set preview image if available
    editPreviewImage.value = animal.image || null;

    showEditModal.value = true;
    editErrors.value = {};
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedAnimal.value = null;
    editPreviewImage.value = null;
    editErrors.value = {};
};

const resetFilters = () => {
    searchQuery.value = '';
    filterAdopted.value = 'all';
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const openAddModal = () => {
    showAddModal.value = true;
    resetForm();
};

const closeAddModal = () => {
    showAddModal.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        name: '',
        age: '',
        breed: '',
        color: '',
        gender: '',
        species: '',
        birthdate: '',
        description: '',
        medical_records: '',
        unique_id: '',
        profile_picture: null,
    };
    previewImage.value = null;
    errors.value = {};
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.profile_picture = file;
        previewImage.value = URL.createObjectURL(file);
    }
};

const handleEditFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        editForm.value.profile_picture = file;
        editPreviewImage.value = URL.createObjectURL(file);
    }
};

const submitForm = () => {
    // Create FormData object to handle file uploads
    const formData = new FormData();

    // Append all form fields to FormData
    Object.keys(form.value).forEach((key) => {
        if (form.value[key] !== null && form.value[key] !== undefined) {
            formData.append(key, form.value[key]);
        }
    });

    // Submit the form using Inertia
    router.post(route('animal-profiles.store'), formData, {
        onSuccess: () => {
            closeAddModal();
        },
        onError: (err) => {
            errors.value = err;
        },
        forceFormData: true,
    });
};

const submitEditForm = () => {
    // Create FormData object to handle file uploads
    const formData = new FormData();

    // Append all form fields to FormData
    Object.keys(editForm.value).forEach((key) => {
        if (editForm.value[key] !== null && editForm.value[key] !== undefined && key !== 'id') {
            formData.append(key, editForm.value[key]);
        }
    });

    // Add method spoofing for PUT request
    formData.append('_method', 'PUT');

    // Submit the form using Inertia
    router.post(route('animal-profiles.update', editForm.value.id), formData, {
        onSuccess: () => {
            closeEditModal();
        },
        onError: (err) => {
            editErrors.value = err;
        },
        forceFormData: true,
    });
};

const viewAnimalProfile = (animalId) => {
    router.visit(route('animal-profile-staff.show', animalId));
};
</script>

<template>
    <AppSidebar>
        <Head title="Animal Profiles" />

        <div class="min-h-screen w-full bg-gradient-to-b from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
            <div class="mx-auto w-full px-4 py-6 sm:px-6 lg:px-8">
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
                                <Ship class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Animal Manifest</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">
                                    All creatures aboard Noah's Ark. Track their health, adoption status, and location.
                                </p>
                            </div>
                        </div>

                        <Button @click="openAddModal" class="flex items-center gap-2 bg-amber-100 text-amber-800 shadow-lg hover:bg-amber-50">
                            <PlusCircle class="h-4 w-4" />
                            Add New Animal
                        </Button>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute right-2 bottom-2 opacity-30">
                        <Hammer class="h-12 w-12 rotate-12 transform text-amber-200" />
                    </div>
                    <div class="absolute top-3 right-20 opacity-30">
                        <Hammer class="h-8 w-8 -rotate-12 transform text-amber-200" />
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="flex items-center gap-2 border-b border-amber-200 p-4 dark:border-amber-800">
                        <Compass class="h-5 w-5 text-amber-700 dark:text-amber-400" />
                        <div>
                            <h3 class="text-lg font-medium text-amber-800 dark:text-amber-300">Navigation Tools</h3>
                            <p class="text-sm text-amber-600 dark:text-amber-400">Find and filter animals in the ark</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="relative flex-grow">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by name, breed, or description..."
                                    class="border-amber-200 pl-10 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                />
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    :class="
                                        filterAdopted === 'all'
                                            ? 'bg-amber-700 text-white hover:bg-amber-800'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-200'
                                    "
                                    @click="filterAdopted = 'all'"
                                >
                                    All Aboard
                                </Button>
                                <Button
                                    :class="
                                        filterAdopted === 'adopted'
                                            ? 'bg-green-700 text-white hover:bg-green-800'
                                            : 'border-green-300 bg-white text-green-700 hover:bg-green-50 dark:border-green-800 dark:bg-amber-800 dark:text-green-300'
                                    "
                                    @click="filterAdopted = 'adopted'"
                                >
                                    Disembarked
                                </Button>
                                <Button
                                    :class="
                                        filterAdopted === 'not-adopted'
                                            ? 'bg-amber-700 text-white hover:bg-amber-800'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-200'
                                    "
                                    @click="filterAdopted = 'not-adopted'"
                                >
                                    On Board
                                </Button>
                                <Button variant="ghost" @click="resetFilters" class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                    <Filter class="h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Animal Profiles Table -->
                <div class="rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-amber-50 text-xs text-amber-800 uppercase dark:bg-amber-800 dark:text-amber-200">
                                <tr>
                                    <th scope="col" class="w-[80px] px-6 py-3">Image</th>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Age</th>
                                    <th scope="col" class="px-6 py-3">Species</th>
                                    <th scope="col" class="px-6 py-3">Breed</th>
                                    <th scope="col" class="px-6 py-3">Color</th>
                                    <th scope="col" class="px-6 py-3">Gender</th>
                                    <th scope="col" class="px-6 py-3">Tracker ID</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="animal in paginatedAnimals"
                                    :key="animal.id"
                                    class="border-b border-amber-200 bg-white hover:bg-amber-50 dark:border-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900"
                                >
                                    <td class="px-6 py-4">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border-2 border-amber-300 bg-amber-100 dark:border-amber-700"
                                        >
                                            <img
                                                v-if="animal.image"
                                                :src="`/${animal.image}`"
                                                :alt="animal.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <PawPrint v-else class="h-6 w-6 text-amber-400" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-amber-900 dark:text-amber-100">{{ animal.name }}</td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">{{ animal.age }} years</td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">{{ animal.species }}</td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">{{ animal.breed }}</td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">{{ animal.color }}</td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">{{ animal.gender }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="animal.device_id"
                                            class="rounded bg-amber-100 px-2 py-1 font-mono text-xs text-amber-800 dark:bg-amber-800 dark:text-amber-200"
                                        >
                                            {{ animal.device_id }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400">Not assigned</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="animal.is_adopted"
                                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-100"
                                        >
                                            Disembarked
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100"
                                        >
                                            On Board
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                @click="viewAnimalProfile(animal.id)"
                                                title="View Profile"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            
                                            <Button
                                                v-if="!animal.is_adopted"
                                                variant="outline"
                                                size="sm"
                                                @click="openAdoptModal(animal)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="Mark as Disembarked"
                                            >
                                                <Heart class="h-4 w-4 text-red-500" />
                                            </Button>

                                            <Link :href="route('admin.animal-location', animal.id)">
                                                <Button variant="outline" size="sm" class="h-8 w-8 p-0 border-amber-300 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800" title="View Location">
                                                    <MapPin class="h-4 w-4" />
                                                </Button>
                                            </Link>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                @click="openEditModal(animal)"
                                                title="Edit"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                @click="openDeleteModal(animal)"
                                                title="Delete"
                                            >
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="paginatedAnimals.length === 0">
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-amber-500">
                                            <Ship class="mb-3 h-16 w-16" />
                                            <h3 class="text-xl font-medium text-amber-700 dark:text-amber-300">No animals found</h3>
                                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                                {{
                                                    searchQuery || filterAdopted !== 'all'
                                                        ? 'Try adjusting your navigation filters'
                                                        : 'Add your first animal to the ark'
                                                }}
                                            </p>
                                            <Button @click="openAddModal" class="mt-4 bg-amber-700 text-white hover:bg-amber-800">
                                                Board Your First Animal
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Custom Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-center border-t border-amber-200 py-4 dark:border-amber-800">
                        <div class="flex items-center gap-1">
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-white text-sm font-medium text-amber-700 hover:bg-amber-50 disabled:pointer-events-none disabled:opacity-50 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800"
                                aria-label="Previous page"
                            >
                                <span class="sr-only">Previous</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div v-for="page in totalPages" :key="page">
                                <button
                                    @click="goToPage(page)"
                                    :class="[
                                        'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border text-sm font-medium',
                                        currentPage === page
                                            ? 'border-amber-700 bg-amber-700 text-white dark:border-amber-700 dark:bg-amber-700'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800',
                                    ]"
                                >
                                    {{ page }}
                                </button>
                            </div>

                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-white text-sm font-medium text-amber-700 hover:bg-amber-50 disabled:pointer-events-none disabled:opacity-50 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800"
                                aria-label="Next page"
                            >
                                <span class="sr-only">Next</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeDeleteModal"></div>

                <!-- Dialog panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-amber-950"
                >
                    <div class="relative border-b border-red-200 bg-red-50 px-6 py-4 dark:border-red-800 dark:bg-red-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-red-800 dark:text-red-100">
                                <Trash2 class="mr-2 h-5 w-5 text-red-500" />
                                Remove from Ark
                            </h3>
                            <button @click="closeDeleteModal" class="text-red-500 hover:text-red-700 dark:hover:text-red-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-amber-950">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <AlertCircle class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Confirm Removal</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to remove
                                        <span class="font-medium text-amber-700 dark:text-amber-300">{{ selectedAnimal?.name }}</span>
                                        from Noah's Ark? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-amber-900">
                        <Button variant="destructive" @click="deleteAnimal" class="inline-flex w-full justify-center sm:ml-3 sm:w-auto sm:text-sm">
                            Remove
                        </Button>
                        <Button
                            variant="outline"
                            @click="closeDeleteModal"
                            class="mt-3 inline-flex w-full justify-center sm:mt-0 sm:w-auto sm:text-sm dark:border-amber-700 dark:text-amber-300"
                        >
                            Cancel
                        </Button>
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
                                        <span class="font-medium text-amber-700 dark:text-amber-300">{{ selectedAnimal?.name }}</span>
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

        <!-- Add Animal Modal (Full Width) -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeAddModal"></div>

                <!-- Modal panel (full width) -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-7xl sm:align-middle dark:bg-amber-950"
                >
                    <!-- Modal header with Noah's Ark theme -->
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <Ship class="mr-2 h-5 w-5" />
                                Board New Animal
                            </h3>
                            <button @click="closeAddModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-amber-600 dark:text-amber-300">Add a new animal to Noah's Ark manifest</p>

                        <!-- Decorative elements -->
                        <div class="absolute right-2 bottom-1 opacity-20">
                            <Hammer class="h-8 w-8 rotate-12 transform text-amber-800 dark:text-amber-300" />
                        </div>
                    </div>

                    <!-- Form content -->
                    <div class="bg-white p-6 dark:bg-amber-950">
                        <form @submit.prevent="submitForm" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Left column - Animal details -->
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Animal Name <span class="text-red-500">*</span>
                                    </label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                        :class="{ 'border-red-500': errors.name }"
                                        placeholder="Enter animal name"
                                        required
                                    />
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Age (years) <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="age"
                                            v-model="form.age"
                                            type="number"
                                            min="0"
                                            step="0.1"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.age }"
                                            placeholder="Age in years"
                                            required
                                        />
                                        <p v-if="errors.age" class="mt-1 text-sm text-red-600">{{ errors.age }}</p>
                                    </div>

                                    <div>
                                        <label for="species" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Species <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="species"
                                            v-model="form.species"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.species }"
                                            placeholder="Animal species"
                                            required
                                        />
                                        <p v-if="errors.species" class="mt-1 text-sm text-red-600">{{ errors.species }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="breed" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Breed <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="breed"
                                            v-model="form.breed"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.breed }"
                                            placeholder="Animal breed"
                                            required
                                        />
                                        <p v-if="errors.breed" class="mt-1 text-sm text-red-600">{{ errors.breed }}</p>
                                    </div>

                                    <div>
                                        <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Color <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="color"
                                            v-model="form.color"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.color }"
                                            placeholder="Animal color"
                                            required
                                        />
                                        <p v-if="errors.color" class="mt-1 text-sm text-red-600">{{ errors.color }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="gender"
                                            v-model="form.gender"
                                            class="mt-1 block w-full rounded-md border-amber-200 py-2 pl-3 pr-10 text-base focus:border-amber-500 focus:outline-none focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white sm:text-sm"
                                            :class="{ 'border-red-500': errors.gender }"
                                            required
                                        >
                                            <option value="" disabled>Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Unknown">Unknown</option>
                                        </select>
                                        <p v-if="errors.gender" class="mt-1 text-sm text-red-600">{{ errors.gender }}</p>
                                    </div>

                                    <div>
                                        <label for="birthdate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Birthdate
                                        </label>
                                        <Input
                                            id="birthdate"
                                            v-model="form.birthdate"
                                            type="date"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.birthdate }"
                                        />
                                        <p v-if="errors.birthdate" class="mt-1 text-sm text-red-600">{{ errors.birthdate }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        :class="{ 'border-red-500': errors.description }"
                                        placeholder="Describe the animal's personality, behavior, etc."
                                        required
                                    ></textarea>
                                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                                </div>

                                <div>
                                    <label for="medical_records" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Medical Records
                                    </label>
                                    <textarea
                                        id="medical_records"
                                        v-model="form.medical_records"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        :class="{ 'border-red-500': errors.medical_records }"
                                        placeholder="Vaccination history, medical conditions, etc."
                                    ></textarea>
                                    <p v-if="errors.medical_records" class="mt-1 text-sm text-red-600">{{ errors.medical_records }}</p>
                                </div>

                                <div>
                                    <label for="unique_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tracking Device ID
                                    </label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <span
                                            class="inline-flex items-center rounded-l-md border border-r-0 border-amber-200 bg-amber-50 px-3 text-amber-700 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300"
                                        >
                                            <Compass class="mr-1 h-4 w-4" />
                                            ID
                                        </span>
                                        <Input
                                            id="unique_id"
                                            v-model="form.unique_id"
                                            type="text"
                                            class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': errors.unique_id }"
                                            placeholder="Enter tracking device ID"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        Optional: Enter the unique ID of the tracking device if available
                                    </p>
                                    <p v-if="errors.unique_id" class="mt-1 text-sm text-red-600">{{ errors.unique_id }}</p>
                                </div>
                            </div>

                            <!-- Right column - Image upload and preview -->
                            <div class="flex flex-col items-center justify-start">
                                <div class="flex w-full flex-col items-center rounded-lg bg-amber-50 p-6 dark:bg-amber-900">
                                    <h4 class="mb-4 text-lg font-medium text-amber-800 dark:text-amber-300">Animal Photo</h4>

                                    <!-- Image preview -->
                                    <div class="mb-4 flex w-full justify-center">
                                        <div
                                            class="relative flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-amber-300 bg-white dark:border-amber-700 dark:bg-amber-800"
                                        >
                                            <img v-if="previewImage" :src="previewImage" class="h-full w-full object-cover" alt="Animal preview" />
                                            <div v-else class="p-6 text-center">
                                                <Camera class="mx-auto mb-2 h-12 w-12 text-amber-400" />
                                                <p class="text-amber-600 dark:text-amber-400">No image selected</p>
                                                <p class="mt-1 text-xs text-amber-500 dark:text-amber-500">Upload a photo of the animal</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File upload -->
                                    <label for="profile_picture" class="w-full">
                                        <div
                                            class="flex cursor-pointer items-center justify-center rounded-md border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-700 shadow-sm hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-300 dark:hover:bg-amber-700"
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            <span>Upload Photo</span>
                                            <input id="profile_picture" type="file" class="sr-only" accept="image/*" @change="handleFileUpload" />
                                        </div>
                                    </label>
                                    <p v-if="errors.profile_picture" class="mt-2 text-sm text-red-600">{{ errors.profile_picture }}</p>

                                    <p class="mt-4 text-center text-xs text-amber-600 dark:text-amber-400">
                                        Accepted formats: JPG, PNG, GIF<br />
                                        Maximum size: 2MB
                                    </p>
                                </div>

                                <!-- Noah's Ark tips -->
                                <div class="mt-6 w-full rounded-lg bg-amber-50 p-4 dark:bg-amber-900">
                                    <h4 class="mb-2 flex items-center text-sm font-medium text-amber-800 dark:text-amber-300">
                                        <Ship class="mr-1 h-4 w-4" />
                                        Ark Boarding Tips
                                    </h4>
                                    <ul class="space-y-2 text-xs text-amber-700 dark:text-amber-400">
                                        <li class="flex items-start">
                                            <span class="mr-1 inline-block h-4 w-4 text-amber-500">•</span>
                                            All animals must be registered before boarding
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-1 inline-block h-4 w-4 text-amber-500">•</span>
                                            Tracking devices help monitor location during the journey
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-1 inline-block h-4 w-4 text-amber-500">•</span>
                                            Document all medical needs for proper care during the voyage
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Form actions -->
                            <div
                                class="col-span-1 mt-2 flex justify-end space-x-3 border-t border-amber-200 pt-4 md:col-span-2 dark:border-amber-800"
                            >
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="closeAddModal"
                                    class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" class="bg-amber-700 text-white hover:bg-amber-800"> Board Animal </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Animal Modal (Full Width) -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeEditModal"></div>

                <!-- Modal panel (full width) -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-7xl sm:align-middle dark:bg-amber-950"
                >
                    <!-- Modal header with Noah's Ark theme -->
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <Edit class="mr-2 h-5 w-5" />
                                Update Animal: {{ selectedAnimal?.name }}
                            </h3>
                            <button @click="closeEditModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-amber-600 dark:text-amber-300">Update animal information in Noah's Ark manifest</p>

                        <!-- Decorative elements -->
                        <div class="absolute right-2 bottom-1 opacity-20">
                            <Hammer class="h-8 w-8 rotate-12 transform text-amber-800 dark:text-amber-300" />
                        </div>
                    </div>

                    <!-- Form content -->
                    <div class="bg-white p-6 dark:bg-amber-950">
                        <form @submit.prevent="submitEditForm" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Left column - Animal details -->
                            <div class="space-y-6">
                                <div>
                                    <label for="edit-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Animal Name <span class="text-red-500">*</span>
                                    </label>
                                    <Input
                                        id="edit-name"
                                        v-model="editForm.name"
                                        type="text"
                                        class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                        :class="{ 'border-red-500': editErrors.name }"
                                        placeholder="Enter animal name"
                                        required
                                    />
                                    <p v-if="editErrors.name" class="mt-1 text-sm text-red-600">{{ editErrors.name }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="edit-age" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Age (years) <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="edit-age"
                                            v-model="editForm.age"
                                            type="number"
                                            min="0"
                                            step="0.1"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.age }"
                                            placeholder="Age in years"
                                            required
                                        />
                                        <p v-if="editErrors.age" class="mt-1 text-sm text-red-600">{{ editErrors.age }}</p>
                                    </div>

                                    <div>
                                        <label for="edit-species" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Species <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="edit-species"
                                            v-model="editForm.species"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.species }"
                                            placeholder="Animal species"
                                            required
                                        />
                                        <p v-if="editErrors.species" class="mt-1 text-sm text-red-600">{{ editErrors.species }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="edit-breed" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Breed <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="edit-breed"
                                            v-model="editForm.breed"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.breed }"
                                            placeholder="Animal breed"
                                            required
                                        />
                                        <p v-if="editErrors.breed" class="mt-1 text-sm text-red-600">{{ editErrors.breed }}</p>
                                    </div>

                                    <div>
                                        <label for="edit-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Color <span class="text-red-500">*</span>
                                        </label>
                                        <Input
                                            id="edit-color"
                                            v-model="editForm.color"
                                            type="text"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.color }"
                                            placeholder="Animal color"
                                            required
                                        />
                                        <p v-if="editErrors.color" class="mt-1 text-sm text-red-600">{{ editErrors.color }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="edit-gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="edit-gender"
                                            v-model="editForm.gender"
                                            class="mt-1 block w-full rounded-md border-amber-200 py-2 pl-3 pr-10 text-base focus:border-amber-500 focus:outline-none focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white sm:text-sm"
                                            :class="{ 'border-red-500': editErrors.gender }"
                                            required
                                        >
                                            <option value="" disabled>Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Unknown">Unknown</option>
                                        </select>
                                        <p v-if="editErrors.gender" class="mt-1 text-sm text-red-600">{{ editErrors.gender }}</p>
                                    </div>

                                    <div>
                                        <label for="edit-birthdate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Birthdate
                                        </label>
                                        <Input
                                            id="edit-birthdate"
                                            v-model="editForm.birthdate"
                                            type="date"
                                            class="mt-1 block w-full border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.birthdate }"
                                        />
                                        <p v-if="editErrors.birthdate" class="mt-1 text-sm text-red-600">{{ editErrors.birthdate }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="edit-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="edit-description"
                                        v-model="editForm.description"
                                        rows="4"
                                        class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        :class="{ 'border-red-500': editErrors.description }"
                                        placeholder="Describe the animal's personality, behavior, etc."
                                        required
                                    ></textarea>
                                    <p v-if="editErrors.description" class="mt-1 text-sm text-red-600">{{ editErrors.description }}</p>
                                </div>

                                <div>
                                    <label for="edit-medical_records" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Medical Records
                                    </label>
                                    <textarea
                                        id="edit-medical_records"
                                        v-model="editForm.medical_records"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        :class="{ 'border-red-500': editErrors.medical_records }"
                                        placeholder="Vaccination history, medical conditions, etc."
                                    ></textarea>
                                    <p v-if="editErrors.medical_records" class="mt-1 text-sm text-red-600">{{ editErrors.medical_records }}</p>
                                </div>

                                <div>
                                    <label for="edit-unique_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tracking Device ID
                                    </label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <span
                                            class="inline-flex items-center rounded-l-md border border-r-0 border-amber-200 bg-amber-50 px-3 text-amber-700 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-300"
                                        >
                                            <Compass class="mr-1 h-4 w-4" />
                                            ID
                                        </span>
                                        <Input
                                            id="edit-unique_id"
                                            v-model="editForm.unique_id"
                                            type="text"
                                            class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                            :class="{ 'border-red-500': editErrors.unique_id }"
                                            placeholder="Enter tracking device ID"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        Optional: Enter the unique ID of the tracking device if available
                                    </p>
                                    <p v-if="editErrors.unique_id" class="mt-1 text-sm text-red-600">{{ editErrors.unique_id }}</p>
                                </div>
                            </div>

                            <!-- Right column - Image upload and preview -->
                            <div class="flex flex-col items-center justify-start">
                                <div class="flex w-full flex-col items-center rounded-lg bg-amber-50 p-6 dark:bg-amber-900">
                                    <h4 class="mb-4 text-lg font-medium text-amber-800 dark:text-amber-300">Animal Photo</h4>

                                    <!-- Image preview -->
                                    <div class="mb-4 flex w-full justify-center">
                                        <div
                                            class="relative flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-amber-300 bg-white dark:border-amber-700 dark:bg-amber-800"
                                        >
                                            <img
                                                v-if="editPreviewImage"
                                                :src="typeof editPreviewImage === 'string' && editPreviewImage.startsWith('/') ? editPreviewImage : `/${editPreviewImage}`"
                                                class="h-full w-full object-cover"
                                                alt="Animal preview"
                                            />
                                            <div v-else class="p-6 text-center">
                                                <Camera class="mx-auto mb-2 h-12 w-12 text-amber-400" />
                                                <p class="text-amber-600 dark:text-amber-400">No image selected</p>
                                                <p class="mt-1 text-xs text-amber-500 dark:text-amber-500">Upload a photo of the animal</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File upload -->
                                    <label for="edit-profile_picture" class="w-full">
                                        <div
                                            class="flex cursor-pointer items-center justify-center rounded-md border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-700 shadow-sm hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-300 dark:hover:bg-amber-700"
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            <span>Change Photo</span>
                                            <input
                                                id="edit-profile_picture"
                                                type="file"
                                                class="sr-only"
                                                accept="image/*"
                                                @change="handleEditFileUpload"
                                            />
                                        </div>
                                    </label>
                                    <p v-if="editErrors.profile_picture" class="mt-2 text-sm text-red-600">{{ editErrors.profile_picture }}</p>

                                    <p class="mt-4 text-center text-xs text-amber-600 dark:text-amber-400">Leave empty to keep the current image</p>
                                </div>

                                <!-- Current status -->
                                <div class="mt-6 w-full rounded-lg bg-amber-50 p-4 dark:bg-amber-900">
                                    <h4 class="mb-2 flex items-center text-sm font-medium text-amber-800 dark:text-amber-300">
                                        <Ship class="mr-1 h-4 w-4" />
                                        Voyage Status
                                    </h4>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="
                                                    selectedAnimal?.is_adopted
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'
                                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'
                                                "
                                            >
                                                {{ selectedAnimal?.is_adopted ? 'Disembarked' : 'On Board' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">ID: {{ selectedAnimal?.id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Form actions -->
                            <div
                                class="col-span-1 mt-2 flex justify-end space-x-3 border-t border-amber-200 pt-4 md:col-span-2 dark:border-amber-800"
                            >
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="closeEditModal"
                                    class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" class="bg-amber-700 text-white hover:bg-amber-800"> Update Animal </Button>
                            </div>
                        </form>
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