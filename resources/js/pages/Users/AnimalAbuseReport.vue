<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Camera, FileText, FileVideo, MapPin, Phone, Shield, X } from 'lucide-vue-next';
import { ref } from 'vue';

const page = usePage();
const isModalOpen = ref(false);

const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: 'Report Abuse', href: '/report-abuse' },
];

// Form for reporting animal abuse
const reportForm = useForm({
    description: '',
    photos: [],
    videos: [],
});

// For previewing uploaded files
const photoPreviewUrls = ref([]);
const videoPreviewUrls = ref([]);

// Handle photo uploads
const handlePhotoUpload = (event) => {
    const files = Array.from(event.target.files);

    // Add to form data
    reportForm.photos = [...reportForm.photos, ...files];

    // Create previews
    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreviewUrls.value.push({
                url: e.target.result,
                name: file.name,
                size: formatFileSize(file.size),
            });
        };
        reader.readAsDataURL(file);
    });
};

// Handle video uploads
const handleVideoUpload = (event) => {
    const files = Array.from(event.target.files);

    // Add to form data
    reportForm.videos = [...reportForm.videos, ...files];

    // Create previews (just metadata for videos)
    files.forEach((file) => {
        videoPreviewUrls.value.push({
            name: file.name,
            size: formatFileSize(file.size),
            type: file.type,
        });
    });
};

// Remove a photo from the uploads
const removePhoto = (index) => {
    const newPhotos = [...reportForm.photos];
    newPhotos.splice(index, 1);
    reportForm.photos = newPhotos;

    photoPreviewUrls.value.splice(index, 1);
};

// Remove a video from the uploads
const removeVideo = (index) => {
    const newVideos = [...reportForm.videos];
    newVideos.splice(index, 1);
    reportForm.videos = newVideos;

    videoPreviewUrls.value.splice(index, 1);
};

// Format file size to human-readable format
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Submit the report
const submitReport = () => {
    router.post(
        route('report-abuse.store'),
        {
            ...reportForm.data(),
            _method: 'POST',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                reportForm.reset();
                photoPreviewUrls.value = [];
                videoPreviewUrls.value = [];
                isModalOpen.value = false;
            },
        },
    );
};

// Open the modal
const openReportModal = () => {
    isModalOpen.value = true;
};

// Close the modal
const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <!-- User Navbar -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <!-- Hero Section -->
        <div class="relative bg-gradient-to-r from-amber-700 to-amber-900 py-10 text-white">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="absolute inset-0 bg-[url('/images/paw-pattern.png')] bg-cover bg-center opacity-10"></div>
            </div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Report Animal Mistreatment</h1>
                    <p class="mt-3 max-w-xl text-base text-amber-100 sm:text-lg">
                        Help us protect animals from cruelty and neglect. Your report can make a difference in an animal's life.
                    </p>
                    <!-- <div class="mt-8">
                        <Button @click="openReportModal" class="bg-white px-6 py-3 text-lg font-medium text-amber-700 shadow-lg hover:bg-amber-50">
                            Report Now
                        </Button>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Information Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- What is Animal Abuse Card -->
                <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                        <AlertTriangle class="h-6 w-6 text-amber-700" />
                    </div>
                    <h2 class="mb-3 text-xl font-bold text-amber-900">What is Animal Mistreatment?</h2>
                    <p class="text-amber-700">
                        Animal mistreatment includes physical harm, neglect, abandonment, hoarding, organized fighting, and any actions that cause
                        unnecessary suffering to animals.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-amber-700">
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Physical abuse or violence
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Neglect or abandonment
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Animal hoarding
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Organized animal fighting
                        </li>
                    </ul>
                </div>

                <!-- How to Report Card -->
                <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                        <FileText class="h-6 w-6 text-amber-700" />
                    </div>
                    <h2 class="mb-3 text-xl font-bold text-amber-900">How to Report</h2>
                    <p class="text-amber-700">
                        When reporting animal mistreatment, please provide as much detail as possible to help us investigate the situation
                        effectively.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-amber-700">
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Describe the situation in detail
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Include photos or videos if possible
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Provide location information
                        </li>
                    </ul>
                </div>

                <!-- What Happens Next Card -->
                <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                        <Shield class="h-6 w-6 text-amber-700" />
                    </div>
                    <h2 class="mb-3 text-xl font-bold text-amber-900">What Happens Next</h2>
                    <p class="text-amber-700">
                        After you submit a report, our team will review the information and take appropriate action to help the animal in need.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-amber-700">
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Report review by our team
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Investigation of the situation
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Coordination with local authorities
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Animal rescue and rehabilitation
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Emergency Contact Section -->
            <div class="mt-8 rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div>
                        <h2 class="text-2xl font-bold text-amber-900">Emergency Animal Rescue</h2>
                        <p class="mt-2 text-amber-700">
                            If you witness animal mistreatment that requires immediate attention, please contact local authorities or animal control
                            directly.
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                            <Phone class="h-7 w-7 text-amber-700" />
                        </div>
                        <div>
                            <p class="text-sm text-amber-600">Emergency Hotline</p>
                            <p class="text-xl font-bold text-amber-900">1-800-555-HELP</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resources Section -->
            <div class="mt-8">
                <h2 class="mb-4 text-2xl font-bold text-amber-900">Additional Resources</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <a href="#" class="rounded-lg border border-amber-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        <h3 class="font-medium text-amber-700">Animal Protection Laws</h3>
                        <p class="mt-1 text-sm text-amber-600">Learn about laws protecting animals in your area</p>
                    </a>
                    <a href="#" class="rounded-lg border border-amber-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        <h3 class="font-medium text-amber-700">Signs of Animal Mistreatment</h3>
                        <p class="mt-1 text-sm text-amber-600">How to recognize when an animal is being mistreated</p>
                    </a>
                    <a href="#" class="rounded-lg border border-amber-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        <h3 class="font-medium text-amber-700">Support for Rescued Animals</h3>
                        <p class="mt-1 text-sm text-amber-600">Ways to help animals that have been rescued</p>
                    </a>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-12 flex justify-center">
                <Button
                    @click="openReportModal"
                    class="bg-gradient-to-r from-amber-600 to-amber-800 px-8 py-4 text-lg font-medium text-white hover:from-amber-700 hover:to-amber-900"
                >
                    Report Animal Mistreatment
                </Button>
            </div>
        </main>

        <!-- Report Abuse Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="max-h-[90vh] overflow-y-auto border-amber-300 bg-amber-50 sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-amber-900">Report Animal Mistreatment</DialogTitle>
                    <DialogDescription class="text-amber-700">
                        Please provide details about the situation. Your information will be kept confidential.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitReport">
                    <div class="mt-4 space-y-6">
                        <!-- Description Field -->
                        <div class="space-y-2">
                            <label class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Description of the Situation*
                            </label>
                            <textarea
                                v-model="reportForm.description"
                                placeholder="Please describe the situation in detail. Include information about the animal(s), location, and any relevant details about the mistreatment or neglect."
                                rows="5"
                                class="w-full resize-none rounded-xl border border-amber-300 bg-white p-4 text-amber-900 shadow-sm transition duration-200 ease-in-out placeholder:text-amber-400 focus:border-amber-500 focus:ring focus:ring-amber-200"
                            ></textarea>

                            <p class="text-xs text-amber-600">{{ reportForm.description.length }}/2000 characters</p>
                            <p v-if="reportForm.errors.description" class="text-sm text-red-500">
                                {{ reportForm.errors.description }}
                            </p>
                        </div>

                        <!-- Photo Upload -->
                        <div class="space-y-2">
                            <label class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Upload Photos (Optional)
                            </label>
                            <div
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 p-4 hover:bg-amber-100"
                                @click="$refs.photoInput.click()"
                            >
                                <div class="flex flex-col items-center justify-center">
                                    <Camera class="mb-2 h-8 w-8 text-amber-500" />
                                    <p class="text-sm text-amber-600">Click to upload photos</p>
                                    <p class="text-xs text-amber-500">JPG, PNG (Max: 2MB each)</p>
                                </div>
                                <input ref="photoInput" type="file" multiple class="hidden" accept="image/*" @change="handlePhotoUpload" />
                            </div>
                            <p v-if="reportForm.errors.photos" class="text-sm text-red-500">
                                {{ reportForm.errors.photos }}
                            </p>

                            <!-- Photo Previews -->
                            <div v-if="photoPreviewUrls.length > 0" class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="(photo, index) in photoPreviewUrls"
                                    :key="index"
                                    class="group relative rounded-lg border border-amber-200 bg-white p-2"
                                >
                                    <img :src="photo.url" class="h-24 w-full rounded object-cover" />
                                    <div class="mt-1 text-xs text-amber-600">
                                        <div class="truncate">{{ photo.name }}</div>
                                        <div>{{ photo.size }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removePhoto(index)"
                                        class="absolute -top-2 -right-2 rounded-full bg-amber-100 p-1 text-amber-700 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Video Upload -->
                        <div class="space-y-2">
                            <label class="text-sm leading-none font-medium text-amber-800 peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Upload Videos (Optional)
                            </label>
                            <div
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 p-4 hover:bg-amber-100"
                                @click="$refs.videoInput.click()"
                            >
                                <div class="flex flex-col items-center justify-center">
                                    <FileVideo class="mb-2 h-8 w-8 text-amber-500" />
                                    <p class="text-sm text-amber-600">Click to upload videos</p>
                                    <p class="text-xs text-amber-500">MP4, AVI, MPEG (Max: 10MB each)</p>
                                </div>
                                <input
                                    ref="videoInput"
                                    type="file"
                                    multiple
                                    class="hidden"
                                    accept="video/mp4,video/avi,video/mpeg"
                                    @change="handleVideoUpload"
                                />
                            </div>
                            <p v-if="reportForm.errors.videos" class="text-sm text-red-500">
                                {{ reportForm.errors.videos }}
                            </p>

                            <!-- Video Previews -->
                            <div v-if="videoPreviewUrls.length > 0" class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="(video, index) in videoPreviewUrls"
                                    :key="index"
                                    class="group relative rounded-lg border border-amber-200 bg-white p-2"
                                >
                                    <div class="flex h-24 items-center justify-center rounded bg-amber-100">
                                        <FileVideo class="h-10 w-10 text-amber-500" />
                                    </div>
                                    <div class="mt-1 text-xs text-amber-600">
                                        <div class="truncate">{{ video.name }}</div>
                                        <div>{{ video.size }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeVideo(index)"
                                        class="absolute -top-2 -right-2 rounded-full bg-amber-100 p-1 text-amber-700 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div class="rounded-lg border border-amber-200 bg-amber-100 p-4">
                            <div class="flex items-start">
                                <MapPin class="mr-3 h-5 w-5 text-amber-600" />
                                <div>
                                    <h4 class="text-sm font-medium text-amber-800">Location Information</h4>
                                    <p class="mt-1 text-xs text-amber-700">
                                        Providing specific location details helps us investigate the situation more effectively. Your current location
                                        will be automatically included with your report.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Notice -->
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-600">
                            <p>
                                Your report will be handled confidentially. We may need to share information with law enforcement or animal welfare
                                agencies as necessary to address the situation.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="mt-6">
                        <Button type="button" variant="outline" class="border-amber-600 text-amber-800 hover:bg-amber-100" @click="closeModal">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="reportForm.processing"
                            class="bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                        >
                            {{ reportForm.processing ? 'Submitting...' : 'Submit Report' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
