<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { Anchor, ArrowRight, PawPrint, Users, VideoIcon } from 'lucide-vue-next';
import { ref } from 'vue';

// State
const meetingId = ref('');
const isLoading = ref(false);
const error = ref('');

const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: 'Virtual Meeting', href: '/virtual-meeting' },
];

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

        router.visit(`/virtual-meeting/${result.roomName}`);
    } catch (err) {
        console.error('Error validating meeting:', err);
        error.value = err.message || 'Invalid meeting ID. Please check and try again.';
    } finally {
        isLoading.value = false;
    }
};


</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <Head title="Join Meeting - Noah's Ark" />

        <!-- Hero Section -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <div class="relative bg-gradient-to-r from-amber-700 to-amber-900 py-10 text-white">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="absolute inset-0 bg-[url('/images/paw-pattern.png')] bg-cover bg-center opacity-10"></div>
            </div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Join Virtual Meeting</h1>
                <p class="mt-3 max-w-xl text-base text-amber-100 sm:text-lg">
                    Connect with other animal lovers and caretakers through our virtual meeting platform.
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Join Meeting Card -->
            <div class="mx-auto max-w-md">
                <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-center">
                            <div class="rounded-full bg-amber-100 p-3">
                                <VideoIcon class="h-8 w-8 text-amber-700" />
                            </div>
                        </div>

                        <h2 class="mb-6 text-center text-xl font-bold text-amber-900">Join a Meeting</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="meetingId" class="mb-1 block text-sm font-medium text-amber-700"> Meeting ID </label>
                                <div class="relative">
                                    <Input
                                        id="meetingId"
                                        v-model="meetingId"
                                        type="text"
                                        placeholder="Enter meeting ID"
                                        class="w-full border-amber-300 pr-10 focus:border-amber-500 focus:ring-amber-500"
                                        :disabled="isLoading"
                                    />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <Anchor class="h-5 w-5 text-amber-500" />
                                    </div>
                                </div>
                                <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
                            </div>

                            <Button
                                @click="validateMeeting"
                                class="flex w-full items-center justify-center gap-2 bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                                :disabled="isLoading"
                            >
                                <span v-if="isLoading">Joining...</span>
                                <span v-else>Join Meeting</span>
                                <ArrowRight v-if="!isLoading" class="h-4 w-4" />
                                <div v-else class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            </Button>
                        </div>
                    </div>

                    <!-- Tips Section -->
                    <div class="border-t border-amber-200 bg-amber-50 p-6">
                        <h3 class="mb-3 text-sm font-medium text-amber-800">Meeting Tips</h3>
                        <ul class="space-y-2 text-sm text-amber-700">
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span>Make sure your camera and microphone are working</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span>Find a quiet place with good lighting</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span>Have your meeting ID ready before joining</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="mt-8 text-center">
                    <div class="mb-4 flex items-center justify-center">
                        <Users class="mr-2 h-5 w-5 text-amber-600" />
                        <span class="text-amber-700">Need help? Contact our support team</span>
                    </div>
                    <p class="text-sm text-amber-600">By joining a meeting, you agree to our terms of service and privacy policy.</p>
                </div>
            </div>
        </main>
    </div>
</template>
