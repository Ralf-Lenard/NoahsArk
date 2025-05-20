<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Anchor,
    Camera,
    CheckCircle,
    Clock,
    Compass,
    Eye,
    FileText,
    Filter,
    Hammer,
    Heart,
    PawPrint,
    Search,
    Ship,
    User,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// Access the route function from the usePage hook
const page = usePage();

// Define props
const props = defineProps({
    adoptionRequests: {
        type: Array,
        required: true,
    },
});

// Search and filter state
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all', 'pending', 'approved', 'rejected'

// Modal states
const showViewModal = ref(false);
const showModalSelfie = ref(false);
const showModalValid = ref(false);
const showUpdateStatusModal = ref(false);

// Selected adoption request for operations
const selectedRequest = ref(null);

// Form data for status update
const statusForm = ref({
    status: 'pending',
    rejection_reason: '',
});

// Form errors
const errors = ref({});

// Computed filtered adoption requests
const filteredRequests = computed(() => {
    return props.adoptionRequests.filter((request) => {
        // Apply search filter (search by user name, animal name, or ID)
        const searchLower = searchQuery.value.toLowerCase();
        const matchesSearch =
            request.user?.name?.toLowerCase().includes(searchLower) ||
            false ||
            request.animal_profile?.name?.toLowerCase().includes(searchLower) ||
            false ||
            request.id.toString().includes(searchLower);

        // Apply status filter
        const matchesStatus = filterStatus.value === 'all' || request.status === filterStatus.value;

        return matchesSearch && matchesStatus;
    });
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredRequests.value.length / itemsPerPage));
const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredRequests.value.slice(start, end);
});

// Methods
const openViewModal = (request) => {
    selectedRequest.value = request;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedRequest.value = null;
};

const openUpdateStatusModal = (request) => {
    selectedRequest.value = request;
    statusForm.value = {
        status: request.status,
        rejection_reason: request.rejection_reason || '',
    };
    showUpdateStatusModal.value = true;
    errors.value = {};
};

const closeUpdateStatusModal = () => {
    showUpdateStatusModal.value = false;
    selectedRequest.value = null;
    errors.value = {};
};

const resetFilters = () => {
    searchQuery.value = '';
    filterStatus.value = 'all';
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const submitStatusUpdate = () => {
    if (selectedRequest.value) {
        router.post(route('adoption-requests.update-status', selectedRequest.value.id), statusForm.value, {
            onSuccess: () => {
                closeUpdateStatusModal();
            },
            onError: (err) => {
                errors.value = err;
            },
        });
    }
};

// Helper function to get status badge classes
const getStatusClasses = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100';
        case 'approved':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100';
    }
};

// Helper function to get status icon
const getStatusIcon = (status) => {
    switch (status) {
        case 'pending':
            return Clock;
        case 'approved':
            return CheckCircle;
        case 'rejected':
            return XCircle;
        default:
            return Clock;
    }
};

// Format date helper
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppSidebar>
        <Head title="Adoption Requests" />

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
                                <Heart class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Adoption Requests</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">
                                    Review and manage requests from people wanting to provide homes for animals from Noah's Ark.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute right-2 bottom-2 opacity-30">
                        <Hammer class="h-12 w-12 rotate-12 transform text-amber-200" />
                    </div>
                    <div class="absolute top-3 right-20 opacity-30">
                        <Anchor class="h-8 w-8 -rotate-12 transform text-amber-200" />
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="flex items-center gap-2 border-b border-amber-200 p-4 dark:border-amber-800">
                        <Compass class="h-5 w-5 text-amber-700 dark:text-amber-400" />
                        <div>
                            <h3 class="text-lg font-medium text-amber-800 dark:text-amber-300">Navigation Tools</h3>
                            <p class="text-sm text-amber-600 dark:text-amber-400">Find and filter adoption requests</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="relative flex-grow">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by applicant name, animal name, or request ID..."
                                    class="border-amber-200 pl-10 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                />
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    :class="
                                        filterStatus === 'all'
                                            ? 'bg-amber-700 text-white hover:bg-amber-800'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-200'
                                    "
                                    @click="filterStatus = 'all'"
                                >
                                    All Requests
                                </Button>
                                <Button
                                    :class="
                                        filterStatus === 'pending'
                                            ? 'bg-amber-700 text-white hover:bg-amber-800'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-200'
                                    "
                                    @click="filterStatus = 'pending'"
                                >
                                    <Clock class="mr-1 h-4 w-4" />
                                    Pending
                                </Button>
                                <Button
                                    :class="
                                        filterStatus === 'approved'
                                            ? 'bg-green-700 text-white hover:bg-green-800'
                                            : 'border-green-300 bg-white text-green-700 hover:bg-green-50 dark:border-green-800 dark:bg-amber-800 dark:text-green-300'
                                    "
                                    @click="filterStatus = 'approved'"
                                >
                                    <CheckCircle class="mr-1 h-4 w-4" />
                                    Approved
                                </Button>
                                <Button
                                    :class="
                                        filterStatus === 'rejected'
                                            ? 'bg-red-700 text-white hover:bg-red-800'
                                            : 'border-red-300 bg-white text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-amber-800 dark:text-red-300'
                                    "
                                    @click="filterStatus = 'rejected'"
                                >
                                    <XCircle class="mr-1 h-4 w-4" />
                                    Rejected
                                </Button>
                                <Button variant="ghost" @click="resetFilters" class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                    <Filter class="h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adoption Requests Table -->
                <div class="rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-amber-50 text-xs text-amber-800 uppercase dark:bg-amber-800 dark:text-amber-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Applicant</th>
                                    <th scope="col" class="px-6 py-3">Animal</th>
                                    <th scope="col" class="px-6 py-3">Submitted</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="request in paginatedRequests"
                                    :key="request.id"
                                    class="border-b border-amber-200 bg-white hover:bg-amber-50 dark:border-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900"
                                >
                                    <td class="px-6 py-4 font-medium text-amber-900 dark:text-amber-100">#{{ request.id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-100 dark:border-amber-700"
                                            >
                                                <User class="h-4 w-4 text-amber-700" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-amber-900 dark:text-amber-100">
                                                    {{ request.user?.name || 'Unknown User' }}
                                                </div>
                                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                                    {{ request.user?.email || 'No email' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-100 dark:border-amber-700"
                                            >
                                                <PawPrint class="h-4 w-4 text-amber-700" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-amber-900 dark:text-amber-100">
                                                    {{ request.animal_profile?.name || 'Unknown Animal' }}
                                                </div>
                                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                                    {{ request.animal_profile?.breed || 'No breed info' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">
                                        {{ formatDate(request.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusClasses(request.status)"
                                        >
                                            <component :is="getStatusIcon(request.status)" class="mr-1 h-3 w-3" />
                                            {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openViewModal(request)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="View Details"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openUpdateStatusModal(request)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="Update Status"
                                            >
                                                <component :is="getStatusIcon(request.status)" class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="paginatedRequests.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-amber-500">
                                            <Ship class="mb-3 h-16 w-16" />
                                            <h3 class="text-xl font-medium text-amber-700 dark:text-amber-300">No adoption requests found</h3>
                                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                                {{
                                                    searchQuery || filterStatus !== 'all'
                                                        ? 'Try adjusting your search filters'
                                                        : 'No one has requested to adopt an animal yet'
                                                }}
                                            </p>
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

        <!-- View Adoption Request Modal -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeViewModal"></div>

                <!-- Modal panel (full width) -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-7xl sm:align-middle dark:bg-amber-950"
                >
                    <!-- Modal header with Noah's Ark theme -->
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <FileText class="mr-2 h-5 w-5" />
                                Adoption Request #{{ selectedRequest?.id }}
                            </h3>
                            <button @click="closeViewModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-amber-600 dark:text-amber-300">Submitted on {{ formatDate(selectedRequest?.created_at) }}</p>

                        <!-- Decorative elements -->
                        <div class="absolute right-2 bottom-1 opacity-20">
                            <Hammer class="h-8 w-8 rotate-12 transform text-amber-800 dark:text-amber-300" />
                        </div>
                    </div>

                    <!-- Request content -->
                    <div class="bg-white p-6 dark:bg-amber-950">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Left column - Applicant and Animal details -->
                            <div class="space-y-6">
                                <!-- Status badge -->
                                <div class="mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                                        :class="getStatusClasses(selectedRequest?.status)"
                                    >
                                        <component :is="getStatusIcon(selectedRequest?.status)" class="mr-2 h-4 w-4" />
                                        {{ selectedRequest?.status.charAt(0).toUpperCase() + selectedRequest?.status.slice(1) }}
                                    </span>

                                    <span v-if="selectedRequest?.status === 'rejected'" class="ml-3 text-sm text-red-600 dark:text-red-400">
                                        Reason: {{ selectedRequest?.rejection_reason || 'No reason provided' }}
                                    </span>
                                </div>

                                <!-- Applicant information -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <User class="mr-2 h-5 w-5" />
                                        Applicant Information
                                    </h4>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Name</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedRequest?.user?.name || 'Unknown' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Email</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedRequest?.user?.email || 'Unknown' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Phone</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedRequest?.user?.phone || 'Not provided' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Address</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedRequest?.user?.address || 'Not provided' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Animal information -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <PawPrint class="mr-2 h-5 w-5" />
                                        Animal Information
                                    </h4>

                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg border-2 border-amber-300 bg-amber-100 dark:border-amber-700"
                                        >
                                            <img
                                                v-if="selectedRequest?.animal_profile?.image"
                                                :src="`/${selectedRequest?.animal_profile?.image}`"
                                                :alt="selectedRequest?.animal_profile?.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <PawPrint v-else class="h-8 w-8 text-amber-400" />
                                        </div>

                                        <div class="flex-1 space-y-2">
                                            <div>
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Name</p>
                                                <p class="text-amber-900 dark:text-amber-100">
                                                    {{ selectedRequest?.animal_profile?.name || 'Unknown' }}
                                                </p>
                                            </div>

                                            <div>
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Breed</p>
                                                <p class="text-amber-900 dark:text-amber-100">
                                                    {{ selectedRequest?.animal_profile?.breed || 'Unknown' }}
                                                </p>
                                            </div>

                                            <div>
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Age</p>
                                                <p class="text-amber-900 dark:text-amber-100">
                                                    {{ selectedRequest?.animal_profile?.age || 'Unknown' }} years
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column - Application details -->
                            <div class="space-y-6">
                                <!-- Application questions -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <FileText class="mr-2 h-5 w-5" />
                                        Application Questions
                                    </h4>

                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                                                Question 1: Why do you want to adopt this animal?
                                            </p>
                                            <p
                                                class="mt-1 rounded-md border border-amber-200 bg-white p-3 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                                            >
                                                {{ selectedRequest?.question1 || 'No answer provided' }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                                                Question 2: Describe your living situation and how it would accommodate this animal.
                                            </p>
                                            <p
                                                class="mt-1 rounded-md border border-amber-200 bg-white p-3 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                                            >
                                                {{ selectedRequest?.question2 || 'No answer provided' }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                                                Question 3: What experience do you have with animals?
                                            </p>
                                            <p
                                                class="mt-1 rounded-md border border-amber-200 bg-white p-3 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                                            >
                                                {{ selectedRequest?.question3 || 'No answer provided' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Uploaded documents -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <Camera class="mr-2 h-5 w-5" />
                                        Uploaded Documents
                                    </h4>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <p class="mb-2 text-sm font-medium text-amber-700 dark:text-amber-400">Valid ID</p>
                                            <div class="rounded-md border border-amber-200 bg-white p-2 dark:border-amber-800 dark:bg-amber-950">
                                                <button
                                                    v-if="selectedRequest?.valid_id"
                                                    @click="showModalValid = true"
                                                    class="flex cursor-pointer items-center text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                                >
                                                    <FileText class="mr-2 h-5 w-5" />
                                                    View ID Document
                                                </button>
                                                <p v-else class="text-amber-500 italic">No document uploaded</p>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="mb-2 text-sm font-medium text-amber-700 dark:text-amber-400">Selfie with ID</p>
                                            <div class="rounded-md border border-amber-200 bg-white p-2 dark:border-amber-800 dark:bg-amber-950">
                                                <button
                                                    v-if="selectedRequest?.selfie_with_id"
                                                    @click="showModalSelfie = true"
                                                    class="flex cursor-pointer items-center text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                                >
                                                    <Camera class="mr-2 h-5 w-5" />
                                                    View Selfie
                                                </button>
                                                <p v-else class="text-amber-500 italic">No selfie uploaded</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end space-x-3 border-t border-amber-200 pt-4 dark:border-amber-800">
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeViewModal"
                                class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                            >
                                Close
                            </Button>
                            <Button type="button" @click="openUpdateStatusModal(selectedRequest)" class="bg-amber-700 text-white hover:bg-amber-800">
                                Update Status
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal selfie -->
        <div v-if="showModalValid" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
            <div class="relative w-full max-w-lg rounded-lg bg-white p-4 dark:bg-amber-950">
                <button @click="showModalValid = false" class="absolute top-2 right-2 text-gray-700 hover:text-red-500 dark:text-gray-200">
                    &times;
                </button>
                <img :src="`/${selectedRequest.valid_id}`" alt="Selfie with ID" class="mx-auto max-h-[90vh] w-auto rounded" />
            </div>
        </div>

        <!-- Modal selfie -->
        <div v-if="showModalSelfie" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
            <div class="relative w-full max-w-lg rounded-lg bg-white p-4 dark:bg-amber-950">
                <button @click="showModalSelfie = false" class="absolute top-2 right-2 text-gray-700 hover:text-red-500 dark:text-gray-200">
                    &times;
                </button>
                <img :src="`/${selectedRequest.selfie_with_id}`" alt="Selfie with ID" class="mx-auto max-h-[90vh] w-auto rounded" />
            </div>
        </div>

        <!-- Update Status Modal -->
        <div v-if="showUpdateStatusModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeUpdateStatusModal"></div>

                <!-- Dialog panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-amber-950"
                >
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <component :is="getStatusIcon(statusForm.status)" class="mr-2 h-5 w-5" />
                                Update Adoption Status
                            </h3>
                            <button @click="closeUpdateStatusModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-amber-950">
                        <form @submit.prevent="submitStatusUpdate">
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Request for: <span class="font-semibold">{{ selectedRequest?.animal_profile?.name }}</span>
                                </label>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    From: <span class="font-semibold">{{ selectedRequest?.user?.name }}</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Status </label>
                                <div class="flex flex-col space-y-2">
                                    <label class="inline-flex items-center">
                                        <input
                                            type="radio"
                                            v-model="statusForm.status"
                                            value="pending"
                                            class="form-radio text-amber-600 focus:ring-amber-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <Clock class="mr-1 h-4 w-4 text-amber-600" />
                                            Pending
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input
                                            type="radio"
                                            v-model="statusForm.status"
                                            value="approved"
                                            class="form-radio text-green-600 focus:ring-green-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <CheckCircle class="mr-1 h-4 w-4 text-green-600" />
                                            Approved
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input
                                            type="radio"
                                            v-model="statusForm.status"
                                            value="rejected"
                                            class="form-radio text-red-600 focus:ring-red-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <XCircle class="mr-1 h-4 w-4 text-red-600" />
                                            Rejected
                                        </span>
                                    </label>
                                </div>
                                <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status }}</p>
                            </div>

                            <div v-if="statusForm.status === 'rejected'" class="mb-4">
                                <label for="rejection_reason" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Rejection Reason <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="rejection_reason"
                                    v-model="statusForm.rejection_reason"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    :class="{ 'border-red-500': errors.rejection_reason }"
                                    placeholder="Please provide a reason for rejection"
                                    required
                                ></textarea>
                                <p v-if="errors.rejection_reason" class="mt-1 text-sm text-red-600">{{ errors.rejection_reason }}</p>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="closeUpdateStatusModal"
                                    class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    :class="{
                                        'bg-amber-700 hover:bg-amber-800': statusForm.status === 'pending',
                                        'bg-green-700 hover:bg-green-800': statusForm.status === 'approved',
                                        'bg-red-700 hover:bg-red-800': statusForm.status === 'rejected',
                                    }"
                                    class="text-white"
                                >
                                    Update Status
                                </Button>
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
