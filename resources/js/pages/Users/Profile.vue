<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, Anchor, Calendar, Camera, Check, Key, Lock, Mail, MapPin, PawPrint, Phone, Shield, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.user);
const photoPreview = ref(null);

// Check if flash messages exist
const hasFlashSuccess = computed(() => {
    return page.props.flash && page.props.flash.success;
});

const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: 'Profile', href: '/profile' },
];

// Form setup
const form = useForm({
    name: user.value.name || '',
    email: user.value.email || '',
    address: user.value.address || '',
    age: user.value.age || '',
    gender: user.value.gender || '',
    civil_status: user.value.civil_status || '',
    phone_number: user.value.phone_number || '',
    profile_photo: null,
    _method: 'PUT',
});

// Password change form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    _method: 'PUT',
});

// Handle profile photo selection
const selectNewPhoto = () => {
    document.getElementById('profile_photo')?.click();
};

const updatePhotoPreview = () => {
    const photoInput = document.getElementById('profile_photo') as HTMLInputElement;
    if (!photoInput || !photoInput.files || photoInput.files.length === 0) return;

    const photo = photoInput.files[0];
    form.profile_photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result;
    };
    reader.readAsDataURL(photo);
};

// Submit profile form
const updateProfile = () => {
    form.post('/profile/update', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('profile_photo');
            photoPreview.value = null;
        },
    });
};

// Submit password form
const updatePassword = () => {
    passwordForm.post('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

// Get profile photo URL
const getProfilePhotoUrl = computed(() => {
    if (photoPreview.value) {
        return photoPreview.value;
    }
    if (user.value.profile_photo) {
        return `/${user.value.profile_photo}`;
    }
    return null; // We'll use initials fallback in the template
});

// Get initials fallback
function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

// User initials when photo is not available
const userInitials = computed(() => {
    return user.value?.name ? getInitials(user.value.name) : 'G';
});

// Civil status options
const civilStatusOptions = [
    { value: 'single', label: 'Single' },
    { value: 'married', label: 'Married' },
    { value: 'divorced', label: 'Divorced' },
    { value: 'widowed', label: 'Widowed' },
    { value: 'separated', label: 'Separated' },
];
</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <Head title="My Profile - Noah's Ark" />

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
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">My Profile</h1>
                    <p class="mt-3 text-lg text-amber-100">Update your personal information and preferences</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Success Message -->
            <div v-if="hasFlashSuccess" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                <div class="flex">
                    <Check class="mr-3 h-5 w-5 flex-shrink-0 text-green-600" />
                    <p class="text-green-700">{{ page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex">
                    <AlertCircle class="mr-3 h-5 w-5 flex-shrink-0 text-red-600" />
                    <div>
                        <p class="font-medium text-red-700">Please fix the following errors:</p>
                        <ul class="mt-1 list-inside list-disc text-sm text-red-600">
                            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="grid gap-8 md:grid-cols-3">
                <!-- Profile Photo Section -->
                <div class="md:col-span-1">
                    <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                        <div class="p-6">
                            <h2 class="mb-4 text-xl font-bold text-amber-900">Profile Photo</h2>

                            <div class="flex flex-col items-center">
                                <div class="relative mb-4">
                                    <div
                                        class="flex h-40 w-40 items-center justify-center overflow-hidden rounded-full border-4 border-amber-200 bg-amber-100 text-4xl font-bold text-amber-800"
                                    >
                                        <!-- Show image if available -->
                                        <img
                                            v-if="user.profile_photo"
                                            :src="`/${user.profile_photo}`"
                                            alt="Profile Photo"
                                            class="h-full w-full object-cover"
                                        />

                                        <!-- Show initials if no image -->
                                        <span v-else>{{ userInitials }}</span>
                                    </div>

                                    <button
                                        @click="selectNewPhoto"
                                        type="button"
                                        class="absolute right-0 bottom-0 flex h-10 w-10 items-center justify-center rounded-full bg-amber-600 text-white shadow-md hover:bg-amber-700"
                                    >
                                        <Camera class="h-5 w-5" />
                                    </button>
                                </div>

                                <input id="profile_photo" ref="profile_photo" type="file" class="hidden" @change="updatePhotoPreview" />

                                <p class="text-center text-sm text-amber-600">Click the camera icon to upload a new photo</p>
                            </div>

                            <!-- User Info Card -->
                            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <h3 class="mb-3 flex items-center font-medium text-amber-800">
                                    <Anchor class="mr-2 h-4 w-4 text-amber-600" />
                                    Account Information
                                </h3>

                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center text-amber-700">
                                        <User class="mr-2 h-4 w-4 text-amber-600" />
                                        <span>{{ user.name }}</span>
                                    </div>

                                    <div class="flex items-center text-amber-700">
                                        <Mail class="mr-2 h-4 w-4 text-amber-600" />
                                        <span>{{ user.email }}</span>
                                    </div>

                                    <div v-if="user.created_at" class="flex items-center text-amber-700">
                                        <Calendar class="mr-2 h-4 w-4 text-amber-600" />
                                        <span>Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Section -->
                    <div class="mt-6 overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                        <div class="p-6">
                            <h2 class="mb-4 text-xl font-bold text-amber-900">Change Password</h2>

                            <!-- Password Error Message -->
                            <div v-if="Object.keys(passwordForm.errors).length > 0" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3">
                                <div class="flex">
                                    <AlertCircle class="mr-2 h-4 w-4 flex-shrink-0 text-red-600" />
                                    <div>
                                        <ul class="list-inside list-disc text-sm text-red-600">
                                            <li v-for="(error, key) in passwordForm.errors" :key="key">{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="updatePassword">
                                <div class="space-y-4">
                                    <!-- Current Password -->
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-amber-800">Current Password</label>
                                        <div class="relative mt-1">
                                            <Key class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="current_password"
                                                v-model="passwordForm.current_password"
                                                type="password"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Enter your current password"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <!-- New Password -->
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-amber-800">New Password</label>
                                        <div class="relative mt-1">
                                            <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="password"
                                                v-model="passwordForm.password"
                                                type="password"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Enter new password"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-amber-800">Confirm Password</label>
                                        <div class="relative mt-1">
                                            <Shield class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="password_confirmation"
                                                v-model="passwordForm.password_confirmation"
                                                type="password"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Confirm new password"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <Button
                                        type="submit"
                                        class="w-full bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                                        :disabled="passwordForm.processing"
                                    >
                                        <span v-if="passwordForm.processing">Updating...</span>
                                        <span v-else>Change Password</span>
                                    </Button>
                                </div>
                            </form>

                            <!-- Password Tips -->
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <h4 class="mb-2 text-sm font-medium text-amber-800">Password Tips</h4>
                                <ul class="space-y-1">
                                    <li class="flex items-start">
                                        <PawPrint class="mt-0.5 mr-1.5 h-3 w-3 text-amber-600" />
                                        <span class="text-xs text-amber-700">Use at least 8 characters</span>
                                    </li>
                                    <li class="flex items-start">
                                        <PawPrint class="mt-0.5 mr-1.5 h-3 w-3 text-amber-600" />
                                        <span class="text-xs text-amber-700">Include uppercase and lowercase letters</span>
                                    </li>
                                    <li class="flex items-start">
                                        <PawPrint class="mt-0.5 mr-1.5 h-3 w-3 text-amber-600" />
                                        <span class="text-xs text-amber-700">Include at least one number and special character</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="md:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                        <div class="p-6">
                            <h2 class="mb-6 text-xl font-bold text-amber-900">Personal Information</h2>

                            <form @submit.prevent="updateProfile">
                                <div class="space-y-6">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-amber-800">Full Name</label>
                                        <div class="relative mt-1">
                                            <User class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="name"
                                                v-model="form.name"
                                                type="text"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Your full name"
                                                required
                                            />
                                        </div>
                                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-amber-800">Email Address</label>
                                        <div class="relative mt-1">
                                            <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Your email address"
                                                required
                                            />
                                        </div>
                                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                                    </div>

                                    <!-- Phone Number -->
                                    <div>
                                        <label for="phone_number" class="block text-sm font-medium text-amber-800">Phone Number</label>
                                        <div class="relative mt-1">
                                            <Phone class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="phone_number"
                                                v-model="form.phone_number"
                                                type="tel"
                                                maxlength="11"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Your phone number"
                                                @input="form.phone_number = '0' + form.phone_number.replace(/\D/g, '').replace(/^0+/, '')"
                                            />
                                        </div>
                                        <p v-if="form.errors.phone_number" class="mt-1 text-sm text-red-600">{{ form.errors.phone_number }}</p>
                                    </div>

                                    <!-- Address -->
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-amber-800">Address</label>
                                        <div class="relative mt-1">
                                            <MapPin class="absolute top-3 left-3 h-4 w-4 text-amber-500" />
                                            <textarea
                                                id="address"
                                                v-model="form.address"
                                                class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 pl-10 text-sm ring-offset-white placeholder:text-amber-400 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                placeholder="Your address"
                                                rows="3"
                                            ></textarea>
                                        </div>
                                        <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                                    </div>

                                    <!-- Age -->
                                    <div>
                                        <label for="age" class="block text-sm font-medium text-amber-800">Age</label>
                                        <div class="relative mt-1">
                                            <Calendar class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                            <Input
                                                id="age"
                                                v-model="form.age"
                                                type="number"
                                                min="0"
                                                class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                                placeholder="Your age"
                                            />
                                        </div>
                                        <p v-if="form.errors.age" class="mt-1 text-sm text-red-600">{{ form.errors.age }}</p>
                                    </div>

                                    <!-- Gender -->
                                    <div>
                                        <span class="mb-2 block text-sm font-medium text-amber-800">Gender</span>
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center space-x-2">
                                                <input
                                                    type="radio"
                                                    id="male"
                                                    name="gender"
                                                    value="male"
                                                    v-model="form.gender"
                                                    class="h-4 w-4 rounded-full border-amber-300 text-amber-600 focus:ring-amber-500"
                                                />
                                                <label for="male" class="text-sm text-amber-700">Male</label>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <input
                                                    type="radio"
                                                    id="female"
                                                    name="gender"
                                                    value="female"
                                                    v-model="form.gender"
                                                    class="h-4 w-4 rounded-full border-amber-300 text-amber-600 focus:ring-amber-500"
                                                />
                                                <label for="female" class="text-sm text-amber-700">Female</label>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <input
                                                    type="radio"
                                                    id="other"
                                                    name="gender"
                                                    value="other"
                                                    v-model="form.gender"
                                                    class="h-4 w-4 rounded-full border-amber-300 text-amber-600 focus:ring-amber-500"
                                                />
                                                <label for="other" class="text-sm text-amber-700">Other</label>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600">{{ form.errors.gender }}</p>
                                    </div>

                                    <!-- Civil Status -->
                                    <div>
                                        <label for="civil_status" class="block text-sm font-medium text-amber-800">Civil Status</label>
                                        <div class="mt-1">
                                            <select
                                                id="civil_status"
                                                v-model="form.civil_status"
                                                class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-amber-400 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <option value="" disabled>Select your civil status</option>
                                                <option v-for="option in civilStatusOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <p v-if="form.errors.civil_status" class="mt-1 text-sm text-red-600">{{ form.errors.civil_status }}</p>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="pt-4">
                                        <Button
                                            type="submit"
                                            class="w-full bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                                            :disabled="form.processing"
                                        >
                                            <span v-if="form.processing">Updating...</span>
                                            <span v-else>Update Profile</span>
                                            <PawPrint v-if="!form.processing" class="ml-2 h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Profile Tips -->
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h3 class="mb-3 font-medium text-amber-900">Profile Tips</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">Keep your contact information up to date for important notifications</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">A clear profile photo helps others recognize you in the community</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mt-0.5 mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">Your profile information is only visible to authorized Noah's Ark staff</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
