<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Anchor,
    Calendar,
    CalendarCheck,
    CalendarClock,
    CalendarX,
    CheckCircle,
    Clock,
    Compass,
    Eye,
    FileText,
    Filter,
    Hammer,
    PawPrint,
    Plus,
    Search,
    Ship,
    Trash2,
    User,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// Define props
const props = defineProps({
    appointments: {
        type: Array,
        required: true,
        default: () => [],
    },
    approvedRequests: {
        type: Array,
        required: true,
        default: () => [],
    },
});

// Search and filter state
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all', 'pending', 'confirmed', 'cancelled', 'completed'

// Modal states
const showViewModal = ref(false);
const showUpdateStatusModal = ref(false);
const showDeleteModal = ref(false);
const showCreateModal = ref(false);

// Selected appointment for operations
const selectedAppointment = ref(null);

// Form data for status update
const statusForm = ref({
    status: 'pending',
});

// Form data for new appointment
const appointmentForm = ref({
    adoption_request_id: '',
    appointment_date: '',
    appointment_time: '',
    notes: '',
});

// Form errors
const errors = ref({});

// Computed filtered appointments
const filteredAppointments = computed(() => {
    return props.appointments.filter((appointment) => {
        // Apply search filter (search by user name, animal name, or ID)
        const searchLower = searchQuery.value.toLowerCase();
        const matchesSearch =
            appointment.user?.name?.toLowerCase().includes(searchLower) ||
            false ||
            appointment.animal?.name?.toLowerCase().includes(searchLower) ||
            false ||
            appointment.id.toString().includes(searchLower);

        // Apply status filter
        const matchesStatus = filterStatus.value === 'all' || appointment.status === filterStatus.value;

        return matchesSearch && matchesStatus;
    });
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredAppointments.value.length / itemsPerPage));
const paginatedAppointments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredAppointments.value.slice(start, end);
});

// Methods
const openViewModal = (appointment) => {
    selectedAppointment.value = appointment;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedAppointment.value = null;
};

const openUpdateStatusModal = (appointment) => {
    selectedAppointment.value = appointment;
    statusForm.value = {
        status: appointment.status,
    };
    showUpdateStatusModal.value = true;
    errors.value = {};
};

const closeUpdateStatusModal = () => {
    showUpdateStatusModal.value = false;
    selectedAppointment.value = null;
    errors.value = {};
};

const openDeleteModal = (appointment) => {
    selectedAppointment.value = appointment;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedAppointment.value = null;
};

const openCreateModal = () => {
    appointmentForm.value = {
        adoption_request_id: '',
        appointment_date: '',
        appointment_time: '',
        notes: '',
    };
    showCreateModal.value = true;
    errors.value = {};
};

const closeCreateModal = () => {
    showCreateModal.value = false;
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
    if (selectedAppointment.value) {
        router.post(route('appointments.update', selectedAppointment.value.id), statusForm.value, {
            onSuccess: () => {
                closeUpdateStatusModal();
            },
            onError: (err) => {
                errors.value = err;
            },
        });
    }
};

const submitDeleteAppointment = () => {
    if (selectedAppointment.value) {
        router.delete(route('appointments.destroy', selectedAppointment.value.id), {
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (err) => {
                errors.value = err;
            },
        });
    }
};

const submitCreateAppointment = () => {
    router.post(route('appointments.store'), appointmentForm.value, {
        onSuccess: () => {
            closeCreateModal();
        },
        onError: (err) => {
            errors.value = err;
        },
    });
};

// Helper function to get status badge classes
const getStatusClasses = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100';
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100';
        case 'completed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100';
    }
};

// Helper function to get status icon
const getStatusIcon = (status) => {
    switch (status) {
        case 'pending':
            return Clock;
        case 'confirmed':
            return CalendarCheck;
        case 'cancelled':
            return CalendarX;
        case 'completed':
            return CheckCircle;
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
    });
};

// Format time helper
const formatTime = (timeString) => {
    if (!timeString) return 'N/A';

    // Handle different time formats
    let time;
    if (timeString.includes(':')) {
        // If it's already in HH:MM format
        time = timeString;
    } else {
        // If it's stored as minutes since midnight
        const hours = Math.floor(parseInt(timeString) / 60);
        const minutes = parseInt(timeString) % 60;
        time = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }

    // Convert to 12-hour format
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;

    return `${hour12}:${minutes} ${ampm}`;
};

// Helper to truncate text
const truncateText = (text, length = 100) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <AppSidebar>
        <Head title="Adoption Appointments" />

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
                                <Calendar class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Adoption Appointments</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">Schedule and manage virtual appointments for approved adoption requests.</p>
                            </div>
                        </div>

                        <Button @click="openCreateModal" class="border border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200">
                            <Plus class="mr-2 h-4 w-4" />
                            Schedule Appointment
                        </Button>
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
                            <p class="text-sm text-amber-600 dark:text-amber-400">Find and filter adoption appointments</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="relative flex-grow">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by adopter name, animal name, or appointment ID..."
                                    class="border-amber-200 pl-10 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                />
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    :class="
                                        filterStatus === 'all'
                                            ? 'bg-amber-700 text-white hover:bg-amber-800'
                                            : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-800 dark:text-amber-200'
                                    "
                                    @click="filterStatus = 'all'"
                                >
                                    All Appointments
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
                                        filterStatus === 'confirmed'
                                            ? 'bg-green-700 text-white hover:bg-green-800'
                                            : 'border-green-300 bg-white text-green-700 hover:bg-green-50 dark:border-green-800 dark:bg-amber-800 dark:text-green-300'
                                    "
                                    @click="filterStatus = 'confirmed'"
                                >
                                    <CalendarCheck class="mr-1 h-4 w-4" />
                                    Confirmed
                                </Button>
                                <Button
                                    :class="
                                        filterStatus === 'cancelled'
                                            ? 'bg-red-700 text-white hover:bg-red-800'
                                            : 'border-red-300 bg-white text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-amber-800 dark:text-red-300'
                                    "
                                    @click="filterStatus = 'cancelled'"
                                >
                                    <CalendarX class="mr-1 h-4 w-4" />
                                    Cancelled
                                </Button>
                                <Button
                                    :class="
                                        filterStatus === 'completed'
                                            ? 'bg-blue-700 text-white hover:bg-blue-800'
                                            : 'border-blue-300 bg-white text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:bg-amber-800 dark:text-blue-300'
                                    "
                                    @click="filterStatus = 'completed'"
                                >
                                    <CheckCircle class="mr-1 h-4 w-4" />
                                    Completed
                                </Button>
                                <Button variant="ghost" @click="resetFilters" class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                    <Filter class="h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-amber-50 text-xs text-amber-800 uppercase dark:bg-amber-800 dark:text-amber-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Adopter</th>
                                    <th scope="col" class="px-6 py-3">Animal</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                    <th scope="col" class="px-6 py-3">Time</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="appointment in paginatedAppointments"
                                    :key="appointment.id"
                                    class="border-b border-amber-200 bg-white hover:bg-amber-50 dark:border-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900"
                                >
                                    <td class="px-6 py-4 font-medium text-amber-900 dark:text-amber-100">#{{ appointment.id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-100 dark:border-amber-700"
                                            >
                                                <User class="h-4 w-4 text-amber-700" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-amber-900 dark:text-amber-100">
                                                    {{ appointment.user?.name || 'Unknown User' }}
                                                </div>
                                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                                    {{ appointment.user?.email || 'No email' }}
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
                                                    {{ appointment.animal?.name || 'Unknown Animal' }}
                                                </div>
                                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                                    {{ appointment.animal?.breed || 'No breed info' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">
                                        {{ formatDate(appointment.date) }}
                                    </td>
                                    <td class="px-6 py-4 text-amber-800 dark:text-amber-200">
                                        {{ formatTime(appointment.time) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusClasses(appointment.status)"
                                        >
                                            <component :is="getStatusIcon(appointment.status)" class="mr-1 h-3 w-3" />
                                            {{ appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openViewModal(appointment)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="View Details"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openUpdateStatusModal(appointment)"
                                                class="h-8 w-8 border-amber-300 p-0 text-amber-700 hover:bg-amber-50 hover:text-amber-800 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-800"
                                                title="Update Status"
                                            >
                                                <component :is="getStatusIcon(appointment.status)" class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openDeleteModal(appointment)"
                                                class="h-8 w-8 border-red-300 p-0 text-red-700 hover:bg-red-50 hover:text-red-800 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-800"
                                                title="Delete Appointment"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="paginatedAppointments.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-amber-500">
                                            <Ship class="mb-3 h-16 w-16" />
                                            <h3 class="text-xl font-medium text-amber-700 dark:text-amber-300">No appointments found</h3>
                                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                                {{
                                                    searchQuery || filterStatus !== 'all'
                                                        ? 'Try adjusting your search filters'
                                                        : 'No adoption appointments have been scheduled yet'
                                                }}
                                            </p>
                                            <Button @click="openCreateModal" class="mt-4 bg-amber-700 text-white hover:bg-amber-800">
                                                <Plus class="mr-2 h-4 w-4" />
                                                Schedule New Appointment
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

        <!-- View Appointment Modal -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeViewModal"></div>

                <!-- Modal panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle dark:bg-amber-950"
                >
                    <!-- Modal header with Noah's Ark theme -->
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <Calendar class="mr-2 h-5 w-5" />
                                Appointment Details
                            </h3>
                            <button @click="closeViewModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Decorative elements -->
                        <div class="absolute right-2 bottom-1 opacity-20">
                            <Hammer class="h-8 w-8 rotate-12 transform text-amber-800 dark:text-amber-300" />
                        </div>
                    </div>

                    <!-- Appointment content -->
                    <div class="bg-white p-6 dark:bg-amber-950">
                        <!-- Status badge -->
                        <div class="mb-6 flex items-center">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                                :class="getStatusClasses(selectedAppointment?.status)"
                            >
                                <component :is="getStatusIcon(selectedAppointment?.status)" class="mr-2 h-4 w-4" />
                                {{ selectedAppointment?.status.charAt(0).toUpperCase() + selectedAppointment?.status.slice(1) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Left column - Appointment details -->
                            <div class="space-y-6">
                                <!-- Appointment information -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <CalendarClock class="mr-2 h-5 w-5" />
                                        Appointment Schedule
                                    </h4>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Date</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ formatDate(selectedAppointment?.date) }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Time</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ formatTime(selectedAppointment?.time) }}</p>
                                        </div>

                                        <div v-if="selectedAppointment?.rejection_reason">
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Rejection Reason</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedAppointment?.rejection_reason }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <FileText class="mr-2 h-5 w-5" />
                                        Notes
                                    </h4>

                                    <div class="rounded-md border border-amber-200 bg-white p-4 dark:border-amber-800 dark:bg-amber-950">
                                        <p class="whitespace-pre-line text-amber-900 dark:text-amber-100">
                                            {{ selectedAppointment?.notes || 'No notes provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column - Adopter and Animal details -->
                            <div class="space-y-6">
                                <!-- Adopter information -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                    <h4 class="mb-3 flex items-center text-lg font-medium text-amber-800 dark:text-amber-300">
                                        <User class="mr-2 h-5 w-5" />
                                        Adopter Information
                                    </h4>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Name</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedAppointment?.user?.name || 'Unknown' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Email</p>
                                            <p class="text-amber-900 dark:text-amber-100">{{ selectedAppointment?.user?.email || 'Unknown' }}</p>
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
                                            <PawPrint class="h-8 w-8 text-amber-400" />
                                        </div>

                                        <div class="flex-1 space-y-2">
                                            <div>
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Name</p>
                                                <p class="text-amber-900 dark:text-amber-100">{{ selectedAppointment?.animal?.name || 'Unknown' }}</p>
                                            </div>

                                            <div>
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Breed</p>
                                                <p class="text-amber-900 dark:text-amber-100">
                                                    {{ selectedAppointment?.animal?.breed || 'Unknown' }}
                                                </p>
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
                            <Button
                                type="button"
                                @click="openUpdateStatusModal(selectedAppointment)"
                                class="bg-amber-700 text-white hover:bg-amber-800"
                            >
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
                                Update Appointment Status
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
                                    Appointment for: <span class="font-semibold">{{ selectedAppointment?.animal?.name }}</span>
                                </label>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    With: <span class="font-semibold">{{ selectedAppointment?.user?.name }}</span>
                                </label>
                                <label class="mt-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Scheduled for:
                                    <span class="font-semibold"
                                        >{{ formatDate(selectedAppointment?.date) }} at {{ formatTime(selectedAppointment?.time) }}</span
                                    >
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
                                            value="confirmed"
                                            class="form-radio text-green-600 focus:ring-green-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <CalendarCheck class="mr-1 h-4 w-4 text-green-600" />
                                            Confirmed
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input
                                            type="radio"
                                            v-model="statusForm.status"
                                            value="cancelled"
                                            class="form-radio text-red-600 focus:ring-red-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <CalendarX class="mr-1 h-4 w-4 text-red-600" />
                                            Cancelled
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input
                                            type="radio"
                                            v-model="statusForm.status"
                                            value="completed"
                                            class="form-radio text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-2 flex items-center">
                                            <CheckCircle class="mr-1 h-4 w-4 text-blue-600" />
                                            Completed
                                        </span>
                                    </label>
                                </div>
                                <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status }}</p>
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
                                        'bg-green-700 hover:bg-green-800': statusForm.status === 'confirmed',
                                        'bg-red-700 hover:bg-red-800': statusForm.status === 'cancelled',
                                        'bg-blue-700 hover:bg-blue-800': statusForm.status === 'completed',
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

        <!-- Delete Appointment Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeDeleteModal"></div>

                <!-- Dialog panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-amber-950"
                >
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <AlertCircle class="mr-2 h-5 w-5 text-red-600" />
                                Delete Appointment
                            </h3>
                            <button @click="closeDeleteModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-amber-950">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <Trash2 class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Delete Appointment</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to delete this appointment? This action cannot be undone.
                                    </p>
                                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900">
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Appointment Details:</p>
                                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                            <span class="font-medium">Date:</span> {{ formatDate(selectedAppointment?.date) }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-400">
                                            <span class="font-medium">Time:</span> {{ formatTime(selectedAppointment?.time) }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-400">
                                            <span class="font-medium">Adopter:</span> {{ selectedAppointment?.user?.name }}
                                        </p>
                                        <p class="text-sm text-amber-700 dark:text-amber-400">
                                            <span class="font-medium">Animal:</span> {{ selectedAppointment?.animal?.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeDeleteModal"
                                class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                            >
                                Cancel
                            </Button>
                            <Button type="button" @click="submitDeleteAppointment" class="bg-red-700 text-white hover:bg-red-800">
                                Delete Appointment
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Appointment Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay with wood texture -->
                <div class="bg-opacity-75 fixed inset-0 bg-amber-950 transition-opacity" @click="closeCreateModal"></div>

                <!-- Dialog panel -->
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-amber-950"
                >
                    <div class="relative border-b border-amber-200 bg-amber-100 px-6 py-4 dark:border-amber-700 dark:bg-amber-900">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center text-xl font-semibold text-amber-800 dark:text-amber-100">
                                <Calendar class="mr-2 h-5 w-5" />
                                Schedule New Appointment
                            </h3>
                            <button @click="closeCreateModal" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-amber-950">
                        <form @submit.prevent="submitCreateAppointment">
                            <div class="mb-4">
                                <label for="adoption_request_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Adoption Request <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="adoption_request_id"
                                    v-model="appointmentForm.adoption_request_id"
                                    class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    :class="{ 'border-red-500': errors.adoption_request_id }"
                                    required
                                >
                                    <option value="" disabled>Select an approved adoption request</option>
                                    <option v-for="request in props.approvedRequests" :key="request.id" :value="request.id">
                                        {{ request.user.name }} - {{ request.animal.name }}
                                    </option>
                                </select>
                                <p v-if="errors.adoption_request_id" class="mt-1 text-sm text-red-600">{{ errors.adoption_request_id }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="appointment_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Appointment Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="appointment_date"
                                    v-model="appointmentForm.appointment_date"
                                    class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    :class="{ 'border-red-500': errors.appointment_date }"
                                    required
                                />
                                <p v-if="errors.appointment_date" class="mt-1 text-sm text-red-600">{{ errors.appointment_date }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="appointment_time" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Appointment Time <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="time"
                                    id="appointment_time"
                                    v-model="appointmentForm.appointment_time"
                                    class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    :class="{ 'border-red-500': errors.appointment_time }"
                                    required
                                />
                                <p v-if="errors.appointment_time" class="mt-1 text-sm text-red-600">{{ errors.appointment_time }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="notes" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Notes </label>
                                <textarea
                                    id="notes"
                                    v-model="appointmentForm.notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-amber-200 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    :class="{ 'border-red-500': errors.notes }"
                                    placeholder="Add any additional notes about the appointment"
                                ></textarea>
                                <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes }}</p>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="closeCreateModal"
                                    class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" class="bg-amber-700 text-white hover:bg-amber-800"> Schedule Appointment </Button>
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
