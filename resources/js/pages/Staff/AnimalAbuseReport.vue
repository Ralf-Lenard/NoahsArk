<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Anchor,
    CheckCircle,
    Clock,
    Compass,
    Eye,
    FileText,
    Filter,
    Hammer,
    Image,
    Search,
    Ship,
    User,
    Video,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// Access the route function from the usePage hook
const page = usePage();

// Define props
const props = defineProps({
    animalAbuseReports: {
        type: Array,
        required: true,
    },
});

// Search and filter state
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all', 'pending', 'approved', 'rejected'

// Modal states
const showViewModal = ref(false);
const showUpdateStatusModal = ref(false);
const showMediaModal = ref(false);

// Selected report for operations
const selectedReport = ref(null);
const selectedMedia = ref(null);
const selectedMediaType = ref(''); // 'photo' or 'video'

// Form data for status update
const statusForm = ref({
    status: 'pending',
    rejection_reason: '',
});

// Form errors
const errors = ref({});

// Computed filtered reports
const filteredReports = computed(() => {
    return props.animalAbuseReports.filter((report) => {
        // Apply search filter (search by user name or description)
        const searchLower = searchQuery.value.toLowerCase();
        const matchesSearch =
            report.user?.name?.toLowerCase().includes(searchLower) ||
            false ||
            report.description?.toLowerCase().includes(searchLower) ||
            false ||
            report.id.toString().includes(searchLower);

        // Apply status filter
        const matchesStatus = filterStatus.value === 'all' || report.status === filterStatus.value;

        return matchesSearch && matchesStatus;
    });
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredReports.value.length / itemsPerPage));
const paginatedReports = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredReports.value.slice(start, end);
});

// Methods
const openViewModal = (report) => {
    selectedReport.value = report;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedReport.value = null;
};

const openUpdateStatusModal = (report) => {
    selectedReport.value = report;
    statusForm.value = {
        status: report.status,
        rejection_reason: report.rejection_reason || '',
    };
    showUpdateStatusModal.value = true;
    errors.value = {};
};

const closeUpdateStatusModal = () => {
    showUpdateStatusModal.value = false;
    selectedReport.value = null;
    errors.value = {};
};

const openMediaModal = (media, type) => {
    selectedMedia.value = media;
    selectedMediaType.value = type;
    showMediaModal.value = true;
};

const closeMediaModal = () => {
    showMediaModal.value = false;
    selectedMedia.value = null;
    selectedMediaType.value = '';
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
    if (selectedReport.value) {
        router.post(route('animal-abuse-reports.update-status', selectedReport.value.id), statusForm.value, {
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

// Helper to truncate text
const truncateText = (text, length = 100) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

// Helper to count media items
const countMedia = (report) => {
    const photoCount = Array.isArray(report.photos) ? report.photos.length : 0;
    const videoCount = Array.isArray(report.videos) ? report.videos.length : 0;
    return { photoCount, videoCount };
};
</script>

<template>
    <AppSidebar>
        <Head title="Animal Abuse Reports" />

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
                                <AlertTriangle class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Animal Abuse Reports</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">
                                    Review and manage reports of animal abuse submitted by concerned citizens.
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
                            <p class="text-sm text-amber-600 dark:text-amber-400">Find and filter abuse reports</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="relative flex-grow">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by reporter name, description, or report ID..."
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
                                    All Reports
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

                <!-- Abuse Reports Table -->
                <div class="rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-amber-50 text-xs text-amber-800 uppercase dark:bg-amber-800 dark:text-amber-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Reporter</th>
                                    <th scope="col" class="px-6 py-3">Description</th>
                                    <th scope="col" class="px-6 py-3">Media</th>
                                    <th scope="col" class="px-6 py-3">Submitted</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="report in paginatedReports"
                                    :key="report.id"
                                    class="border-b border-amber-200 bg-white hover:bg-amber-50 dark:border-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900"
                                >
                                    <td class="px-6 py-4 font-medium text-amber-900 dark:text-amber-100">#{{ report.id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-100 dark:border-amber-700"
                                            >
                                                <User class="h-4 w-4 text-amber-700" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-amber-900 dark:text-amber-100">
                                                    {{ report.user?.name || 'Anonymous' }}
                                                </div>
                                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                                    {{ report.user?.email || 'No email' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">
                                        {{ truncateText(report.description, 50) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                v-if="countMedia(report).photoCount > 0"
                                                class="inline-flex items-center rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100"
                                            >
                                                <Image class="mr-1 h-3 w-3" />
                                                {{ countMedia(report).photoCount }}
                                            </span>
                                            <span
                                                v-if="countMedia(report).videoCount > 0"
                                                class="inline-flex items-center rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100"
                                            >
                                                <Video class="mr-1 h-3 w-3" />
                                                {{ countMedia(report).videoCount }}
                                            </span>
                                            <span
                                                v-if="countMedia(report).photoCount === 0 && countMedia(report).videoCount === 0"
                                                class="text-xs text-amber-500 italic dark:text-amber-400"
                                            >
                                                No media
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">
                                        {{ formatDate(report.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusClasses(report.status)"
                                        >
                                            <component :is="getStatusIcon(report.status)" class="mr-1 h-3 w-3" />
                                            {{ report.status.charAt(0).toUpperCase() + report.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openViewModal(report)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="View Details"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openUpdateStatusModal(report)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="Update Status"
                                            >
                                                <component :is="getStatusIcon(report.status)" class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="paginatedReports.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-amber-500">
                                            <Ship class="mb-3 h-16 w-16" />
                                            <h3 class="text-xl font-medium text-amber-700 dark:text-amber-300">No abuse reports found</h3>
                                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                                {{
                                                    searchQuery || filterStatus !== 'all'
                                                        ? 'Try adjusting your search filters'
                                                        : 'No abuse reports have been submitted yet'
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

        <!-- View Abuse Report Modal -->
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
                                <AlertTriangle class="mr-2 h-5 w-5" />
                                Abuse Report #{{ selectedReport?.id }}
                            </h3>
                            <button @click="closeViewModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-amber-600 dark:text-amber-300">Submitted on {{ formatDate(selectedReport?.created_at) }}</p>

                        <!-- Decorative elements -->
                        <div class="absolute right-2 bottom-1 opacity-20">
                            <Hammer class="h-8 w-8 rotate-12 transform text-amber-800 dark:text-amber-300" />
                        </div>
                    </div>

                    <!-- Report content -->
                    <div class="bg-white p-6 dark:bg-amber-950">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Left column - Reporter details and description -->
                            <div class="space-y-6">
                                <!-- Status badge -->
                                <div class="mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                                        :class="getStatusClasses(selectedReport?.status)"
                                    >
                                        <component :is="getStatusIcon(selectedReport?.status)" class="mr-2 h-4 w-4" />
                                        {{ selectedReport?.status.charAt(0).toUpperCase() + selectedReport?.status.slice(1) }}
                                    </span>

                                    <span v-if="selectedReport?.status === 'rejected'" class="ml-3 text-sm text-red-600 dark:text-red-400">
                                        Reason: {{ selectedReport?.rejection_reason || 'No reason provided' }}
                                    </span>
                                </div>

                              <!-- Reporter information -->
<div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
    <h4 class="mb-4 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
        <User class="mr-2 h-5 w-5" />
        Reporter Information
    </h4>

    <div class="flex flex-col sm:flex-row sm:items-start sm:space-x-6">
        <!-- Profile Photo / Initials -->
        <div class="flex-shrink-0 mb-4 sm:mb-0">
            <template v-if="selectedReport?.user?.profile_photo">
                <img
                    :src="`/${selectedReport.user.profile_photo}`"
                    alt="Profile Photo"
                    class="h-24 w-24 rounded-full object-cover border-2 border-amber-400"
                />
            </template>
            <template v-else>
                <div class="h-24 w-24 rounded-full bg-amber-400 flex items-center justify-center text-white text-2xl font-bold uppercase">
                    {{ selectedReport?.user?.name?.split(' ').map(n => n[0]).join('') || 'A' }}
                </div>
            </template>
        </div>

        <!-- User Info -->
        <div class="space-y-3">
            <div>
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Name</p>
                <p class="text-amber-900 dark:text-amber-100">{{ selectedReport?.user?.name || 'Anonymous' }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Email</p>
                <p class="text-amber-900 dark:text-amber-100">{{ selectedReport?.user?.email || 'Not provided' }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Phone</p>
                <p class="text-amber-900 dark:text-amber-100">0{{ selectedReport?.user?.phone_number || 'Not provided' }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Address</p>
                <p class="text-amber-900 dark:text-amber-100">{{ selectedReport?.user?.address || 'Not provided' }}</p>
            </div>
        </div>
    </div>
</div>

                                <!-- Abuse description -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <FileText class="mr-2 h-5 w-5" />
                                        Abuse Description
                                    </h4>

                                    <div class="rounded-md border border-amber-200 bg-white p-4 dark:border-amber-800 dark:bg-amber-950">
                                        <p class="whitespace-pre-line text-amber-900 dark:text-amber-100">
                                            {{ selectedReport?.description || 'No description provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column - Media evidence -->
                            <div class="space-y-6">
                                <!-- Photos -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <Image class="mr-2 h-5 w-5" />
                                        Photo Evidence
                                    </h4>

                                    <div
                                        v-if="selectedReport?.photos && selectedReport.photos.length > 0"
                                        class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                                    >
                                        <div v-for="(photo, index) in selectedReport.photos" :key="'photo-' + index" class="group relative">
                                            <div
                                                class="h-32 w-full overflow-hidden rounded-md border border-amber-200 bg-white dark:border-amber-800 dark:bg-amber-950"
                                            >
                                                <img
                                                    :src="`/${photo}`"
                                                    alt="Evidence photo"
                                                    class="h-full w-full cursor-pointer object-cover"
                                                    @click="openMediaModal(photo, 'photo')"
                                                />
                                            </div>
                                            <div
                                                class="bg-opacity-0 group-hover:bg-opacity-30 absolute inset-0 flex items-center justify-center bg-black transition-all"
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    @click="openMediaModal(photo, 'photo')"
                                                    class="border-amber-300 bg-white text-amber-800 opacity-0 group-hover:opacity-100 hover:bg-amber-50"
                                                >
                                                    <Eye class="mr-1 h-4 w-4" />
                                                    View
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-md border border-amber-200 bg-white p-4 text-center dark:border-amber-800 dark:bg-amber-950"
                                    >
                                        <p class="text-amber-500 italic dark:text-amber-400">No photos provided</p>
                                    </div>
                                </div>

                                <!-- Videos -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <Video class="mr-2 h-5 w-5" />
                                        Video Evidence
                                    </h4>

                                    <div
                                        v-if="selectedReport?.videos && selectedReport.videos.length > 0"
                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                    >
                                        <div v-for="(video, index) in selectedReport.videos" :key="'video-' + index" class="group relative">
                                            <div
                                                class="flex h-40 w-full items-center justify-center overflow-hidden rounded-md border border-amber-200 bg-white dark:border-amber-800 dark:bg-amber-950"
                                            >
                                                <div class="bg-opacity-30 absolute inset-0 flex items-center justify-center bg-black">
                                                    <Video class="h-10 w-10 text-white" />
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    @click="openMediaModal(video, 'video')"
                                                    class="z-10 border-amber-300 bg-white text-amber-800 hover:bg-amber-50"
                                                >
                                                    <Video class="mr-1 h-4 w-4" />
                                                    Play Video
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-md border border-amber-200 bg-white p-4 text-center dark:border-amber-800 dark:bg-amber-950"
                                    >
                                        <p class="text-amber-500 italic dark:text-amber-400">No videos provided</p>
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
                            <Button type="button" @click="openUpdateStatusModal(selectedReport)" class="bg-amber-700 text-white hover:bg-amber-800">
                                Update Status
                            </Button>
                        </div>
                    </div>
                </div>
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
                                Update Report Status
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
                                    Report ID: <span class="font-semibold">#{{ selectedReport?.id }}</span>
                                </label>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    From: <span class="font-semibold">{{ selectedReport?.user?.name || 'Anonymous' }}</span>
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

        <!-- Media Viewer Modal -->
        <div v-if="showMediaModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="bg-opacity-90 fixed inset-0 bg-black transition-opacity" @click="closeMediaModal"></div>

                <!-- Media content -->
                <div class="my-8 inline-block w-full max-w-4xl p-2 align-middle">
                    <div class="relative">
                        <!-- Close button -->
                        <button
                            @click="closeMediaModal"
                            class="bg-opacity-50 hover:bg-opacity-70 absolute top-2 right-2 z-10 rounded-full bg-black p-1 text-white"
                        >
                            <X class="h-6 w-6" />
                        </button>

                        <!-- Photo viewer -->
                        <div v-if="selectedMediaType === 'photo'" class="flex items-center justify-center">
                            <img :src="`/${selectedMedia}`" alt="Evidence photo" class="max-h-[80vh] max-w-full rounded-lg object-contain" />
                        </div>

                        <!-- Video player -->
                        <div v-else-if="selectedMediaType === 'video'" class="flex items-center justify-center">
                            <video :src="`/${selectedMedia}`" controls autoplay class="max-h-[80vh] max-w-full rounded-lg"></video>
                        </div>
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
