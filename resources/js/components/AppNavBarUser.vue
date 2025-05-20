<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, Menu, MessageCircle, Video } from 'lucide-vue-next';
import Pusher from 'pusher-js';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const isMobile = ref(false);
const actualNotificationCount = ref(0);
const notifications = ref([]);
const actualMessageCount = ref(0);
const notificationOpen = ref(false);
const newNotification = ref(false);
const pusher = ref(null);

const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    notificationCount: {
        type: Number,
        default: 0,
    },
    messageCount: {
        type: Number,
        default: 0,
    },
});

// Check if we're on mobile
const checkMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

// Set up resize listener
onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

// Clean up resize listener
onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);

    // Clean up Pusher connection when component is unmounted
    if (pusher.value) {
        pusher.value.disconnect();
    }
});

// Create a safe auth object with proper fallbacks
const auth = computed(() => {
    const authData = page.props.auth;
    // Ensure auth and auth.user exist
    return {
        user: authData && authData.user ? authData.user : null,
    };
});

const isCurrentRoute = computed(() => (url: string) => page.url === url);

const activeItemStyles = computed(
    () => (url: string) =>
        isCurrentRoute.value(url)
            ? 'text-amber-800 font-semibold border-b-2 border-amber-800'
            : 'text-amber-700 hover:text-amber-900 transition-colors',
);

const mainNavItems: NavItem[] = [
    { title: 'Adoption Page', href: '/home' },
    { title: 'Animal Report Abuse Page', href: '/report-abuse' },
];

// Check if user is authenticated
const isAuthenticated = computed(() => !!auth.value.user);

const initials = computed(() => {
    let result = 'G';
    if (isAuthenticated.value && auth.value.user && auth.value.user.name) {
        result = getInitials(auth.value.user.name);
    }
    return result;
});

// Initialize Pusher and set up real-time listeners
onMounted(async () => {
    try {
        // Fetch initial notification count
        const notifResponse = await axios.get('/notifications/unread-count');
        actualNotificationCount.value = notifResponse.data.count;

        // Fetch initial message count
        const msgResponse = await axios.get('/chats/unread-count');
        actualMessageCount.value = msgResponse.data.unread_count;

        // Fetch initial notifications list
        const notificationsResponse = await axios.get('/notifications');
        notifications.value = notificationsResponse.data.notifications;

        // Set up Pusher for real-time updates if user is logged in
        if (isAuthenticated.value && auth.value.user) {
            const userId = auth.value.user.id;

            // Initialize Pusher
            pusher.value = new Pusher('c27cf1cca7e151dffc12', {
                cluster: 'ap1',
                encrypted: true,
            });

            console.log('Subscribing to user channel:', `user.${userId}`);

            // Subscribe to the user's notification channel
            const notificationChannel = pusher.value.subscribe(`user.${userId}`);

            notificationChannel.bind('pusher:subscription_succeeded', () => {
                console.log('Successfully subscribed to user channel');
            });

            notificationChannel.bind('pusher:subscription_error', (error) => {
                console.error('Error subscribing to user channel:', error);
            });

            // Handle adoption status updates
            notificationChannel.bind('adoption.status.updated', (data) => {
                console.log('Received adoption notification:', data);

                notifications.value.unshift({
                    id: data.id,
                    message: data.message,
                    image_path: data.animal_image || null,
                    type: data.type,
                    created_at: new Date().toISOString(),
                    status: data.status,
                });

                actualNotificationCount.value++;
                newNotification.value = true;
                setTimeout(() => {
                    newNotification.value = false;
                }, 5000);
            });

            // Handle animal abuse status updates
            notificationChannel.bind('animal.abuse.status.updated', (data) => {
                console.log('Received animal abuse status notification:', data);

                notifications.value.unshift({
                    id: data.id,
                    message: data.message,
                    image_path: data.image_path || null, // No image for animal abuse reports
                    type: data.type,
                    created_at: new Date().toISOString(),
                    status: data.status,
                });

                actualNotificationCount.value++;
                newNotification.value = true;
                setTimeout(() => {
                    newNotification.value = false;
                }, 5000);
            });

            // appointment
            notificationChannel.bind('adoption.appointment.scheduled', (data) => {
                console.log('Received appointment notification:', data);

                notifications.value.unshift({
                    id: data.id,
                    message: data.message,
                    image_path: data.image_path || null,
                    type: data.type,
                    appointment_date: data.appointment_date,
                    appointment_time: data.appointment_time,
                    created_at: new Date().toISOString(), // optional: format as needed
                });

                actualNotificationCount.value++;
                newNotification.value = true;

                setTimeout(() => {
                    newNotification.value = false;
                }, 5000);
            });


            // Subscribe to chat channel
            console.log('Subscribing to chat channel:', `chat.${userId}`);
            const chatChannel = pusher.value.subscribe(`chat.${userId}`);

            chatChannel.bind('pusher:subscription_succeeded', () => {
                console.log('Successfully subscribed to chat channel');
            });

            chatChannel.bind('MessageSent', (data) => {
                console.log('Received message:', data);
                actualMessageCount.value++;
            });

            chatChannel.bind('message.sent', (data) => {
                console.log('Received message (alternate event):', data);
                actualMessageCount.value++;
            });

            chatChannel.bind_global((eventName, data) => {
                console.log('Global event received:', eventName, data);
                if (eventName.includes('message') || eventName.includes('Message')) {
                    actualMessageCount.value++;
                }
            });
        }
    } catch (error) {
        console.error('Failed to initialize real-time updates:', error);
    }
});

// Watch for notification dropdown open/close
watch(notificationOpen, (isOpen) => {
    if (isOpen && actualNotificationCount.value > 0) {
        markNotificationsAsRead();
    }
});

function getIcon(notification) {
    // If you want to use the animal image directly, just return it:
    if (notification.image_path) {
        return notification.image_path; // This should be a full URL or relative path to the image
    }
    // fallback icon if no image available
    return 'https://img.icons8.com/color/48/question-mark.png';
}

function getStatusText(notification) {
    const message = notification.message || '';
    const messageLower = message.toLowerCase();

    if (notification.type === 'AdoptionStatusUpdated') {
        if (messageLower.includes('has been approved')) {
            return `Your <span class="font-semibold">adoption request</span> has been <span class="font-bold text-green-700">approved</span>.`;
        } else if (messageLower.includes('was rejected')) {
            const reasonMatch = message.match(/Reason:\s*(.*)/i);
            const reason = reasonMatch ? reasonMatch[1] : 'No reason provided';
            return `Your <span class="font-semibold">adoption request</span> was <span class="font-bold text-red-600">rejected</span>. Reason: <span class="italic text-gray-800">${reason}</span>.`;
        } else if (messageLower.includes('is now pending')) {
            return `Your <span class="font-semibold">adoption request</span> is currently <span class="font-bold text-yellow-600">pending</span>.`;
        }
    }

    else if (notification.type === 'AnimalAbuseStatusUpdated') {
        if (notification.status === 'approved') {
            return `Your <span class="font-semibold">animal abuse report</span> has been <span class="font-bold text-green-700">approved</span>. Thank you for taking action.`;
        } else if (notification.status === 'rejected') {
            const reason = notification.rejection_reason || 'No reason provided';
            return `Your <span class="font-semibold">animal abuse report</span> was <span class="font-bold text-red-600">rejected</span>. Reason: <span class="italic text-gray-800">${reason}</span>.`;
        } else if (notification.status === 'pending') {
            return `Your <span class="font-semibold">animal abuse report</span> is currently <span class="font-bold text-yellow-600">pending review</span>.`;
        }
    }

    else if (notification.type === 'AdoptionAppointmentScheduled') {
        if (notification.appointment_date && notification.appointment_time) {
            const date = new Date(notification.appointment_date + 'T' + notification.appointment_time);
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            const formattedTime = date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });

            return `A <span class="font-semibold">virtual meeting</span> has been <span class="font-bold text-blue-600">scheduled</span> for 
                    <span class="text-gray-900">${formattedDate}</span> at 
                    <span class="text-gray-900">${formattedTime}</span>. Please be ready on time.`;
        } else {
            return `A <span class="font-semibold">virtual meeting</span> has been <span class="font-bold text-blue-600">scheduled</span>. Please check your schedule.`;
        }
    }

    return message;
}


function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        // If today, show time
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = hours % 12 || 12;
        const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;
        return `Today at ${formattedHours}:${formattedMinutes} ${ampm}`;
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        // Format as MM/DD/YYYY for older notifications
        return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
    }
}

async function markNotificationsAsRead() {
    try {
        await axios.post('/notifications/mark-as-read');
        actualNotificationCount.value = 0;
    } catch (error) {
        console.error('Failed to mark notifications as read:', error);
    }
}

async function deleteNotification(id) {
    try {
        await axios.delete(`/notifications/${id}`);
        notifications.value = notifications.value.filter((n) => n.id !== id);
    } catch (error) {
        console.error('Failed to delete notification:', error);
    }
}

async function clearAllNotifications() {
    try {
        await axios.post('/notifications/clear-all');
        notifications.value = [];
        actualNotificationCount.value = 0;
    } catch (error) {
        console.error('Failed to clear notifications:', error);
    }
}
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-amber-300 bg-amber-50 shadow-sm dark:bg-amber-900">
        <div class="mx-auto flex h-16 max-w-screen-xl items-center px-4 sm:px-6 lg:px-8">
            <!-- Mobile Menu -->
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger>
                        <Button variant="ghost" size="icon" class="mr-2 h-10 w-10 text-amber-800 hover:bg-amber-100">
                            <Menu class="h-6 w-6" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[280px] border-amber-200 bg-amber-50 p-6">
                        <SheetHeader>
                            <SheetTitle class="text-lg font-semibold text-amber-900">
                                <AppLogoIcon class="h-6 w-6 fill-amber-800" />
                            </SheetTitle>
                        </SheetHeader>
                        <nav class="mt-6 space-y-2">
                            <Link
                                v-for="item in mainNavItems"
                                :key="item.title"
                                :href="item.href"
                                class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-amber-100"
                                :class="activeItemStyles(item.href)"
                            >
                                {{ item.title }}
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>

            <!-- Logo -->
            <Link :href="route('user.home')" class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <!-- Desktop Navigation -->
            <nav class="ml-10 hidden space-x-8 lg:flex">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="text-lg font-medium text-amber-800 transition-colors duration-300 hover:text-amber-900"
                    :class="activeItemStyles(item.href)"
                >
                    {{ item.title }}
                </Link>
            </nav>

            <!-- Notification, Chat, Video on Desktop and Mobile -->
            <div class="ml-auto flex items-center space-x-4 lg:space-x-6">
                <!-- Icons (Notification, Chat, Video) -->
                <div class="flex items-center space-x-0 lg:space-x-6">
                    <!-- Notifications Dropdown (Desktop and Mobile) -->
                    <DropdownMenu v-model:open="notificationOpen">
                        <DropdownMenuTrigger>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative rounded-full p-2 transition-all duration-300 hover:bg-amber-600 hover:text-white"
                                :class="{ 'animate-pulse': newNotification }"
                            >
                                <Bell class="h-5 w-5 text-amber-700 group-hover:text-white" />
                                <span
                                    v-if="actualNotificationCount > 0"
                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow-lg"
                                >
                                    {{ actualNotificationCount > 99 ? '99+' : actualNotificationCount }}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="center"
                            :class="[
                                'mt-2 border bg-amber-50 shadow-xl dark:bg-amber-800',
                                isMobile ? 'left-0 w-screen !rounded-none' : 'w-[400px] rounded-2xl',
                            ]"
                        >
                            <!-- Notification Header -->
                            <div
                                class="flex items-center justify-between border-b border-amber-200 bg-amber-100/50 px-4 py-3 dark:border-amber-700 dark:bg-amber-700/50"
                            >
                                <p class="text-base font-semibold text-amber-900 dark:text-white">Notifications</p>
                                <button
                                    @click="clearAllNotifications"
                                    class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100 hover:text-red-700 dark:hover:bg-red-900/20"
                                >
                                    Clear All
                                </button>
                            </div>

                            <!-- Notification Items -->
                            <div class="max-h-[800px] overflow-y-auto" v-if="notifications.length">
                                <div
                                    v-for="(notification, index) in notifications"
                                    :key="notification.id || index"
                                    class="group border-b border-amber-100 px-4 py-3 transition-colors hover:bg-amber-100/50 dark:border-amber-700/50 dark:hover:bg-amber-700/30"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3">
                                            <div class="relative h-10 w-10 flex-shrink-0 overflow-hidden rounded-full">
                                                <img
                                                    :src="`/${getIcon(notification)}`"
                                                    alt="Notification"
                                                    class="h-full w-full object-cover"
                                                    onerror="this.src='https://img.icons8.com/color/48/question-mark.png'"
                                                />
                                            </div>

                                            <div class="flex-1">
                                                <p class="text-sm text-amber-900 dark:text-white" v-html="getStatusText(notification)"></p>
                                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-300">
                                                    {{ formatDate(notification.created_at) }}
                                                </span>
                                            </div>
                                        </div>

                                        <button
                                            @click="deleteNotification(notification.id)"
                                            class="mt-1 ml-2 hidden rounded-full p-1 text-gray-400 group-hover:block hover:bg-red-100 hover:text-red-500 dark:hover:bg-red-900/20"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                                <Bell class="mb-2 h-10 w-10 text-amber-300" />
                                <p class="text-sm text-gray-500 dark:text-gray-300">No notifications yet</p>
                                <p class="mt-1 text-xs text-gray-400">We'll notify you when something arrives</p>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <!-- Chat -->
                    <Link
                        href="/chats"
                        class="group relative rounded-full p-2 transition-all duration-300 hover:bg-amber-600 hover:text-white"
                        title="Messages"
                    >
                        <MessageCircle class="h-5 w-5 text-amber-700 group-hover:text-white" />
                        <span
                            v-if="actualMessageCount > 0"
                            class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow-lg"
                        >
                            {{ actualMessageCount > 99 ? '99+' : actualMessageCount }}
                        </span>

                        <span class="sr-only">Messages</span>
                    </Link>

                    <!-- Virtual Meeting -->
                    <Link
                        href="/virtual-meeting"
                        class="group rounded-full p-2 transition-all duration-300 hover:bg-amber-600 hover:text-white"
                        title="Virtual Meeting"
                    >
                        <Video class="h-5 w-5 text-amber-700 group-hover:text-white" />
                        <span class="sr-only">Virtual Meeting</span>
                    </Link>
                </div>

                <!-- User Avatar - Only show dropdown if authenticated -->
                <template v-if="isAuthenticated">
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <Button variant="ghost" size="icon" class="rounded-full p-0 hover:ring-2 hover:ring-amber-600">
                                <Avatar class="h-9 w-9">
                                    <AvatarImage
                                        v-if="auth.user && auth.user.profile_photo"
                                        :src="`/${auth.user.profile_photo}`"
                                        :alt="auth.user.name || 'User'"
                                    />
                                    <AvatarFallback class="bg-amber-200 text-amber-900 dark:bg-amber-700 dark:text-amber-100">
                                        {{ initials }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="mt-2 w-56 rounded-lg border bg-amber-50 shadow-lg dark:bg-amber-800">
                            <UserMenuContent v-if="auth.user" :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <!-- Guest Avatar - Show when not authenticated -->
                <template v-else>
                    <Link href="/login" class="rounded-full p-0 hover:ring-2 hover:ring-amber-600">
                        <Avatar class="h-9 w-9">
                            <AvatarFallback class="bg-amber-200 text-amber-900 dark:bg-amber-700 dark:text-amber-100">
                                {{ initials }}
                            </AvatarFallback>
                        </Avatar>
                    </Link>
                </template>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs && props.breadcrumbs.length > 0"
            class="border-t border-amber-200 bg-amber-100 dark:border-amber-700 dark:bg-amber-800"
        >
            <div class="mx-auto flex h-10 max-w-screen-xl items-center px-4 sm:px-6 lg:px-8">
                <nav class="flex text-sm">
                    <ol class="flex items-center space-x-1">
                        <li v-for="(crumb, index) in props.breadcrumbs" :key="index">
                            <div class="flex items-center">
                                <span v-if="index > 0" class="mx-2 text-amber-500">/</span>
                                <Link
                                    v-if="index < props.breadcrumbs.length - 1"
                                    :href="crumb.href"
                                    class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                                >
                                    {{ crumb.title }}
                                </Link>
                                <span v-else class="font-medium text-amber-800 dark:text-amber-300">{{ crumb.title }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </header>
</template>

<style scoped>
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}
.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
