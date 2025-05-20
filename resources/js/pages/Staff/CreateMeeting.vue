<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { Anchor, ArrowRight, Check, Copy, Hammer, PawPrint, Ship, Users, Video } from 'lucide-vue-next';
import { ref } from 'vue';

// State
const activeTab = ref('create');
const meetingId = ref('');
const isLoading = ref(false);
const error = ref('');
const copySuccess = ref(false);
const lastCreatedMeetingId = ref('');

// Methods
const createMeeting = async () => {
    try {
        isLoading.value = true;
        error.value = '';

        const response = await fetch('/createMeeting', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to create meeting');
        }

        const data = await response.json();
        lastCreatedMeetingId.value = data.roomName;

        // Redirect to the meeting page using the admin route
        router.visit(`/admin/meeting/${data.roomName}`);
    } catch (err) {
        console.error('Error creating meeting:', err);
        error.value = err.message || 'Failed to create meeting. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

// Methods
const validateMeeting = async () => {
    if (!meetingId.value.trim()) {
        error.value = 'Please enter a valid meeting ID';
        return;
    }

    try {
        isLoading.value = true;
        error.value = '';

        const formData = new FormData();
        formData.append('meetingId', meetingId.value);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/validateMeeting', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json' // This is critical
            },
            body: formData,
        });

        // Try to parse JSON safely
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Invalid meeting ID');
        }

        router.visit(`/admin/meeting/${result.roomName}`);
    } catch (err) {
        console.error('Error validating meeting:', err);
        error.value = err.message || 'Invalid meeting ID. Please check and try again.';
    } finally {
        isLoading.value = false;
    }
};

const copyMeetingLink = () => {
    const meetingLink = `${window.location.origin}/admin/meeting/${lastCreatedMeetingId.value}`;
    navigator.clipboard.writeText(meetingLink);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};
</script>

<template>
    <AppSidebar>
        <Head title="Create Virtual Meeting" />

        <!-- Full width container with improved background -->
        <div
            class="min-h-screen w-full bg-gradient-to-b from-amber-50 via-amber-100 to-amber-50 dark:from-amber-950 dark:via-amber-900 dark:to-amber-950"
        >
            <!-- Removed max-width constraint for full width -->
            <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
                <!-- Improved header with more prominent design -->
                <div
                    class="relative mb-8 overflow-hidden rounded-xl border border-amber-300 bg-gradient-to-r from-amber-800 via-amber-700 to-amber-800 shadow-xl dark:border-amber-700"
                >
                    <!-- Enhanced wood grain texture overlay -->
                    <div class="pointer-events-none absolute inset-0 opacity-15">
                        <svg width="100%" height="100%">
                            <filter id="wood-filter-header">
                                <feTurbulence type="fractalNoise" baseFrequency="0.01" numOctaves="5" seed="2" />
                                <feDisplacementMap in="SourceGraphic" scale="15" />
                            </filter>
                            <rect width="100%" height="100%" filter="url(#wood-filter-header)" fill="#451a03" />
                        </svg>
                    </div>

                    <!-- Improved decorative nails/rivets -->
                    <div class="pointer-events-none absolute top-0 right-0 left-0 flex h-12 items-center justify-between px-8 opacity-60">
                        <div class="h-3 w-3 rounded-full bg-amber-200 shadow-sm"></div>
                        <div class="h-3 w-3 rounded-full bg-amber-200 shadow-sm"></div>
                        <div class="h-3 w-3 rounded-full bg-amber-200 shadow-sm"></div>
                        <div class="h-3 w-3 rounded-full bg-amber-200 shadow-sm"></div>
                        <div class="h-3 w-3 rounded-full bg-amber-200 shadow-sm"></div>
                    </div>

                    <div class="relative flex flex-col justify-between gap-4 p-6 md:flex-row md:items-center md:p-8">
                        <div class="flex items-start gap-4">
                            <div class="rounded-full border border-amber-300 bg-amber-100 p-3 shadow-lg">
                                <Video class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100 drop-shadow-md">Virtual Meeting</h1>
                                <p class="mt-2 max-w-2xl text-lg text-amber-200">Create or join a secure video conference with adopters and staff.</p>
                            </div>
                        </div>

                        <!-- Added a new decorative element -->
                        <div class="hidden md:block">
                            <Ship class="h-16 w-16 text-amber-200 opacity-40" />
                        </div>
                    </div>

                    <!-- Enhanced decorative elements -->
                    <div class="absolute right-2 bottom-2 opacity-40">
                        <Hammer class="h-14 w-14 rotate-12 transform text-amber-200" />
                    </div>
                    <div class="absolute top-3 right-20 opacity-40">
                        <Anchor class="h-10 w-10 -rotate-12 transform text-amber-200" />
                    </div>
                </div>

                <!-- Improved Meeting Creation/Join Card with full width -->
                <div
                    class="mx-auto max-w-6xl overflow-hidden rounded-lg border border-amber-200 bg-white shadow-xl dark:border-amber-800 dark:bg-amber-900"
                >
                    <!-- Enhanced Tabs -->
                    <div class="flex border-b border-amber-200 dark:border-amber-800">
                        <button
                            @click="activeTab = 'create'"
                            class="flex-1 px-6 py-5 text-center text-lg font-medium transition-colors"
                            :class="
                                activeTab === 'create'
                                    ? 'border-b-2 border-amber-600 bg-amber-50 text-amber-900 dark:bg-amber-800 dark:text-amber-100'
                                    : 'text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-800/50'
                            "
                        >
                            <div class="flex items-center justify-center gap-2">
                                <Video class="h-5 w-5" />
                                Create Meeting
                            </div>
                        </button>
                        <button
                            @click="activeTab = 'join'"
                            class="flex-1 px-6 py-5 text-center text-lg font-medium transition-colors"
                            :class="
                                activeTab === 'join'
                                    ? 'border-b-2 border-amber-600 bg-amber-50 text-amber-900 dark:bg-amber-800 dark:text-amber-100'
                                    : 'text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-800/50'
                            "
                        >
                            <div class="flex items-center justify-center gap-2">
                                <Users class="h-5 w-5" />
                                Join Meeting
                            </div>
                        </button>
                    </div>

                    <!-- Create Meeting Tab with improved spacing and layout -->
                    <div v-if="activeTab === 'create'" class="p-8">
                        <div class="mx-auto max-w-3xl space-y-8">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-800/30">
                                <div class="flex items-start gap-4">
                                    <Users class="mt-0.5 h-6 w-6 text-amber-700 dark:text-amber-300" />
                                    <div>
                                        <h3 class="text-lg font-medium text-amber-900 dark:text-amber-100">Create a New Meeting</h3>
                                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                            Start a new virtual meeting and invite others to join using the meeting ID. Your meeting will be secure
                                            and only accessible to those with the link.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Last Created Meeting (if available) with improved design -->
                            <div
                                v-if="lastCreatedMeetingId"
                                class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-800/30"
                            >
                                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                    <div>
                                        <h3 class="text-lg font-medium text-amber-900 dark:text-amber-100">Last Created Meeting</h3>
                                        <p
                                            class="mt-2 rounded border border-amber-200 bg-amber-100 px-3 py-1 font-mono text-sm dark:border-amber-700 dark:bg-amber-800"
                                        >
                                            {{ lastCreatedMeetingId }}
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        @click="copyMeetingLink"
                                        class="border border-amber-300 bg-amber-100 px-4 py-2 text-amber-800 hover:bg-amber-200"
                                    >
                                        <span v-if="!copySuccess" class="flex items-center">
                                            <Copy class="mr-2 h-4 w-4" />
                                            Copy Link
                                        </span>
                                        <span v-else class="flex items-center">
                                            <Check class="mr-2 h-4 w-4" />
                                            Copied!
                                        </span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Error Message with improved visibility -->
                            <div
                                v-if="error"
                                class="rounded-lg border border-red-200 bg-red-50 p-6 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
                            >
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ error }}
                                </div>
                            </div>

                            <!-- Create Button with improved design -->
                            <div class="flex justify-center sm:justify-end">
                                <Button
                                    @click="createMeeting"
                                    :disabled="isLoading"
                                    class="bg-amber-700 px-8 py-6 text-lg text-white shadow-lg transition-all hover:bg-amber-800 hover:shadow-xl"
                                >
                                    <span v-if="isLoading" class="flex items-center">
                                        <svg
                                            class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        Creating...
                                    </span>
                                    <span v-else class="flex items-center">
                                        Create Meeting
                                        <ArrowRight class="ml-2 h-5 w-5" />
                                    </span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Join Meeting Tab with improved spacing and layout -->
                    <div v-if="activeTab === 'join'" class="p-8">
                        <div class="mx-auto max-w-3xl space-y-8">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-800/30">
                                <div class="flex items-start gap-4">
                                    <Video class="mt-0.5 h-6 w-6 text-amber-700 dark:text-amber-300" />
                                    <div>
                                        <h3 class="text-lg font-medium text-amber-900 dark:text-amber-100">Join an Existing Meeting</h3>
                                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                            Enter a meeting ID to join an existing virtual meeting. You'll be connected to the video conference
                                            immediately.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Meeting ID Input with improved design -->
                            <div class="mx-auto max-w-lg space-y-3">
                                <label for="meetingId" class="block text-lg font-medium text-amber-900 dark:text-amber-100"> Meeting ID </label>
                                <Input
                                    id="meetingId"
                                    v-model="meetingId"
                                    placeholder="Enter meeting ID"
                                    class="w-full border-amber-200 py-6 text-lg focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                />
                            </div>

                            <!-- Error Message with improved visibility -->
                            <div
                                v-if="error"
                                class="mx-auto max-w-lg rounded-lg border border-red-200 bg-red-50 p-6 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
                            >
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ error }}
                                </div>
                            </div>

                            <!-- Join Button with improved design -->
                            <div class="flex justify-center">
                                <Button
                                    @click="validateMeeting"
                                    :disabled="isLoading || !meetingId.trim()"
                                    class="bg-amber-700 px-8 py-6 text-lg text-white shadow-lg transition-all hover:bg-amber-800 hover:shadow-xl"
                                    :class="{ 'cursor-not-allowed opacity-50': !meetingId.trim() }"
                                >
                                    <span v-if="isLoading" class="flex items-center">
                                        <svg
                                            class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        Joining...
                                    </span>
                                    <span v-else class="flex items-center">
                                        Join Meeting
                                        <ArrowRight class="ml-2 h-5 w-5" />
                                    </span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Decorative Element -->
                <div class="mt-12 flex justify-center">
                    <div class="flex items-center gap-3 text-amber-500 opacity-70 dark:text-amber-600">
                        <PawPrint class="h-6 w-6" />
                        <PawPrint class="h-8 w-8" />
                        <PawPrint class="h-6 w-6" />
                    </div>
                </div>
            </div>
        </div>
    </AppSidebar>
</template>

<style scoped>
/* Custom scrollbar for the content */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(120, 53, 15, 0.1);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: rgba(217, 119, 6, 0.4);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(217, 119, 6, 0.6);
}

/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Add subtle animation to the paw prints */
@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-5px);
    }
    100% {
        transform: translateY(0px);
    }
}

.mt-12 .flex .flex {
    animation: float 3s ease-in-out infinite;
}
</style>
