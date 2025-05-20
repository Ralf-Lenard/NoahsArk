<script setup lang="ts">
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Anchor,
    Bell,
    CalendarCheck,
    ChevronLeft,
    Compass,
    Hammer,
    LifeBuoy,
    Map,
    Menu,
    MessageCircle,
    PawPrint,
    ShieldAlert,
    Ship,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// Import the UI components needed for the dropdown
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import UserMenuContent from './UserMenuContent.vue';
import DropdownMenu from './ui/dropdown-menu/DropdownMenu.vue';
import DropdownMenuContent from './ui/dropdown-menu/DropdownMenuContent.vue';
import DropdownMenuTrigger from './ui/dropdown-menu/DropdownMenuTrigger.vue';

const props = defineProps({
    mainNavItems: {
        type: Array as () => NavItem[],
        default: () => [
            {
                title: 'Dashboard',
                href: '/dashboard',
                icon: Compass,
            },
            {
                title: 'Animal Profiles',
                href: '/admin/animal-profiles',
                icon: PawPrint,
                active: route().current('animal-profiles.show'),
            },
            {
                title: 'Adoption Requests',
                href: '/admin/adoption-requests',
                icon: LifeBuoy,
            },
            {
                title: 'Animal Abuse Report',
                href: '/admin/animal-abuse-report',
                icon: ShieldAlert,
            },
            {
                title: 'Appointments',
                href: '/admin/appointments',
                icon: CalendarCheck,
            },
            {
                title: 'Chat',
                href: '/chats',
                icon: MessageCircle,
            },
            {
                title: 'Virtual Meeting',
                href: '/admin/virtual-meeting',
                icon: Video,
            },
            {
                title: 'Notifications',
                href: '/admin/notifications',
                icon: Bell,
            },
            // Removed Settings item from here
        ],
    },
    // footerNavItems: {
    //     type: Array as () => NavItem[],
    //     default: () => [
    //         {
    //             title: 'Animal Tracking',
    //             href: '/admin/tracking',
    //             icon: Map,
    //         },
    //         {
    //             title: 'Help & Support',
    //             href: '/admin/help',
    //             icon: LifeBuoy,
    //             external: true,
    //         },
    //     ],
    // },
});

// Sidebar state
const isCollapsed = ref(false);
const isMobileOpen = ref(false);
const hoveredItem = ref<string | null>(null);

// Get authentication state and user data
const page = usePage();
const auth = computed(() => page.props.auth || { user: null });
const isAuthenticated = computed(() => !!auth.value.user);

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

const toggleMobileSidebar = () => {
    isMobileOpen.value = !isMobileOpen.value;
};

// Check if a link is active based on current route
const isActive = (href: string) => {
    const currentPath = window.location.pathname;
    return currentPath === href || currentPath.startsWith(href + '/');
};

// Set hovered item for tooltip
const setHoveredItem = (title: string | null) => {
    hoveredItem.value = title;
};

// Get user initials for avatar fallback
const initials = computed(() => {
    if (!auth.value.user || !auth.value.user.name) return '';
    return auth.value.user.name
        .split(' ')
        .map((name) => name[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
});
</script>

<template>
    <div class="flex h-screen">
        <!-- Mobile sidebar toggle -->
        <div class="fixed top-4 left-4 z-50 md:hidden">
            <button
                @click="toggleMobileSidebar"
                class="rounded-full bg-amber-700 p-2 text-amber-50 shadow-lg transition-colors duration-200 hover:bg-amber-600 focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:outline-none"
            >
                <Menu v-if="!isMobileOpen" class="h-5 w-5" />
                <X v-else class="h-5 w-5" />
            </button>
        </div>

        <!-- Sidebar for desktop -->
        <div
            class="relative hidden flex-col overflow-hidden border-r border-amber-700/20 shadow-xl transition-all duration-300 ease-in-out md:flex dark:border-amber-800/30"
            :class="isCollapsed ? 'w-20' : 'w-72'"
            style="background: linear-gradient(135deg, #92400e 0%, #78350f 50%, #451a03 100%)"
        >
            <!-- Wood grain texture overlay -->
            <div class="bg-texture pointer-events-none absolute inset-0 opacity-15"></div>

            <!-- Header -->
            <div class="relative z-10 flex items-center justify-between border-b border-amber-700/30 p-4">
                <Link :href="route('dashboard')" class="group flex items-center gap-3">
                    <div
                        class="rounded-full bg-gradient-to-br from-amber-50 to-amber-200 p-2 shadow-md transition-transform duration-200 group-hover:scale-105"
                    >
                        <Ship class="h-8 w-8 text-amber-800" />
                    </div>
                    <div v-if="!isCollapsed" class="flex flex-col transition-opacity duration-200">
                        <span class="text-lg font-bold text-amber-50">Noah's Ark</span>
                        <span class="text-xs font-medium text-amber-200/90">Animal Sanctuary</span>
                    </div>
                </Link>

                <!-- Decorative element -->
                <div v-if="!isCollapsed" class="h-2 w-2 rounded-full bg-amber-300/70 shadow-sm shadow-amber-300/50"></div>
            </div>

            <!-- Main Navigation -->
            <div class="scrollbar-thin relative z-10 flex-1 overflow-y-auto p-3">
                <div v-if="!isCollapsed" class="mb-3 flex items-center px-3 text-xs font-medium tracking-wider text-amber-200/90 uppercase">
                    <Hammer class="mr-2 h-3 w-3" />
                    <span>Upper Deck</span>
                </div>
                <nav class="space-y-1.5">
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.title"
                        :href="item.href"
                        :class="[
                            'group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
                            isActive(item.href)
                                ? 'nav-active bg-amber-800/80 text-amber-50 shadow-md'
                                : 'text-amber-100 hover:bg-amber-800/40 hover:text-amber-50',
                        ]"
                        @mouseenter="setHoveredItem(isCollapsed ? item.title : null)"
                        @mouseleave="setHoveredItem(null)"
                    >
                        <div
                            :class="[
                                'flex items-center justify-center rounded-lg p-2 transition-all duration-200',
                                isActive(item.href) ? 'bg-amber-700/80' : 'bg-amber-800/40 group-hover:bg-amber-700/60',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                        <span v-if="!isCollapsed" class="truncate">{{ item.title }}</span>

                        <!-- Tooltip that shows on hover when sidebar is collapsed -->
                        <div
                            v-if="isCollapsed && hoveredItem === item.title"
                            class="tooltip absolute left-full z-50 ml-3 rounded-md bg-amber-900 px-3 py-2 whitespace-nowrap text-amber-50 shadow-lg"
                        >
                            {{ item.title }}
                            <!-- Tooltip arrow -->
                            <div class="absolute top-1/2 -left-1.5 h-3 w-3 -translate-y-1/2 rotate-45 transform bg-amber-900"></div>
                        </div>
                    </Link>
                </nav>
            </div>

            <!-- Footer -->
            <div class="relative z-10 border-t border-amber-700/30 p-3">
                <div v-if="!isCollapsed" class="mb-3 flex items-center px-3 text-xs font-medium tracking-wider text-amber-200/90 uppercase">
                    <Anchor class="mr-2 h-3 w-3" />
                    <span>Lower Deck</span>
                </div>

                <!-- User Profile Dropdown -->
                <div class="mt-4 flex flex-col items-center space-y-3">
                    <template v-if="isAuthenticated">
                        <div v-if="!isCollapsed" class="mb-2 w-full text-center">
                            <span class="text-base font-semibold text-amber-100">Account</span>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger class="w-full text-amber-200/80">
                                <Button
                                    variant="ghost"
                                    :class="[
                                        'flex h-16 w-full items-center gap-4 rounded-xl px-4 py-3 transition-all duration-200 hover:bg-amber-700/50 hover:text-white',
                                        isCollapsed ? 'justify-center' : 'justify-start',
                                    ]"
                                >
                                    <Avatar class="h-12 w-12 shrink-0 border-2 border-amber-600/40">
                                        <AvatarImage
                                            v-if="auth.user && auth.user.profile_photo"
                                            :src="`/${auth.user.profile_photo}`"
                                            :alt="auth.user.name || 'User'"
                                        />
                                        <AvatarFallback class="bg-gradient-to-br from-amber-100 to-amber-300 text-lg font-bold text-amber-900">
                                            {{ initials }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div v-if="!isCollapsed" class="flex flex-col items-start overflow-hidden">
                                        <span class="w-full truncate text-sm font-medium text-amber-100">
                                            {{ auth.user?.name || 'User' }}
                                        </span>
                                        <span class="w-full truncate text-sm text-amber-200/70">
                                            {{ auth.user?.email || 'user@example.com' }}
                                        </span>
                                    </div>
                                    <ChevronLeft v-if="!isCollapsed" class="ml-auto h-5 w-5 rotate-90 text-amber-200/60" />
                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="end"
                                class="mt-3 max-h-72 w-64 overflow-y-auto rounded-xl border border-amber-300/30 bg-gradient-to-b from-amber-50 to-amber-100 shadow-2xl dark:from-amber-800 dark:to-amber-900"
                            >
                                <UserMenuContent v-if="auth.user" :user="auth.user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>

                    <template v-else>
                        <!-- Fallback for non-authenticated users -->
                        <nav class="w-full space-y-2">
                            <a
                                v-for="item in footerNavItems"
                                :key="item.title"
                                :href="item.href"
                                :target="item.external ? '_blank' : undefined"
                                :rel="item.external ? 'noopener noreferrer' : undefined"
                                :class="[
                                    'group relative flex h-16 items-center gap-4 rounded-xl px-4 py-3 text-base font-medium transition-all duration-200',
                                    isActive(item.href)
                                        ? 'nav-active bg-amber-800/80 text-amber-50 shadow-md'
                                        : 'text-amber-100 hover:bg-amber-700/40 hover:text-amber-50',
                                ]"
                                @mouseenter="setHoveredItem(isCollapsed ? item.title : null)"
                                @mouseleave="setHoveredItem(null)"
                            >
                                <div
                                    :class="[
                                        'flex items-center justify-center rounded-lg p-2 transition-all duration-200',
                                        isActive(item.href) ? 'bg-amber-700/80' : 'bg-amber-800/40 group-hover:bg-amber-700/60',
                                    ]"
                                >
                                    <component :is="item.icon" class="h-6 w-6" />
                                </div>
                                <span v-if="!isCollapsed" class="text-base leading-tight">{{ item.title }}</span>

                                <!-- Tooltip when collapsed -->
                                <div
                                    v-if="isCollapsed && hoveredItem === item.title"
                                    class="tooltip absolute left-full z-50 ml-3 rounded-md bg-amber-900 px-3 py-2 whitespace-nowrap text-amber-50 shadow-lg"
                                >
                                    {{ item.title }}
                                    <div class="absolute top-1/2 -left-1.5 h-3 w-3 -translate-y-1/2 rotate-45 transform bg-amber-900"></div>
                                </div>
                            </a>
                        </nav>
                    </template>
                </div>

                <!-- Collapse button with tooltip -->
                <button
                    @click="toggleSidebar"
                    class="relative mt-4 flex w-full items-center justify-center rounded-lg p-2 text-amber-200 transition-all duration-200 hover:bg-amber-800/50 hover:text-amber-50"
                    @mouseenter="setHoveredItem(isCollapsed ? 'Open Hatch' : null)"
                    @mouseleave="setHoveredItem(null)"
                >
                    <ChevronLeft class="h-5 w-5 transition-transform duration-300" :class="isCollapsed ? 'rotate-180' : ''" />
                    <span v-if="!isCollapsed" class="ml-2 font-medium">Close Hatch</span>

                    <!-- Tooltip for collapse button when sidebar is collapsed -->
                    <div
                        v-if="isCollapsed && hoveredItem === 'Open Hatch'"
                        class="tooltip absolute left-full z-50 ml-3 rounded-md bg-amber-900 px-3 py-2 whitespace-nowrap text-amber-50 shadow-lg"
                    >
                        Open Hatch
                        <!-- Tooltip arrow -->
                        <div class="absolute top-1/2 -left-1.5 h-3 w-3 -translate-y-1/2 rotate-45 transform bg-amber-900"></div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Mobile sidebar (overlay) -->
        <div v-if="isMobileOpen" class="fixed inset-0 z-40 md:hidden" @click="isMobileOpen = false">
            <div class="absolute inset-0 bg-amber-950/80 backdrop-blur-sm transition-opacity duration-300"></div>
        </div>

        <div
            class="fixed inset-y-0 left-0 z-40 w-72 transform overflow-hidden transition-transform duration-300 ease-in-out md:hidden"
            :class="isMobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
            style="background: linear-gradient(135deg, #92400e 0%, #78350f 50%, #451a03 100%)"
        >
            <!-- Wood grain texture overlay -->
            <div class="bg-texture pointer-events-none absolute inset-0 opacity-15"></div>

            <!-- Mobile sidebar content -->
            <div class="relative z-10 flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-amber-700/30 p-4">
                    <Link :href="route('dashboard')" class="group flex items-center gap-3">
                        <div
                            class="rounded-full bg-gradient-to-br from-amber-50 to-amber-200 p-2 shadow-md transition-transform duration-200 group-hover:scale-105"
                        >
                            <Ship class="h-8 w-8 text-amber-800" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-bold text-amber-50">Noah's Ark</span>
                            <span class="text-xs font-medium text-amber-200/90">Animal Sanctuary</span>
                        </div>
                    </Link>
                    <button
                        @click="isMobileOpen = false"
                        class="rounded-full bg-amber-800/80 p-2 text-amber-100 transition-colors duration-200 hover:bg-amber-700 focus:ring-2 focus:ring-amber-400 focus:outline-none"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Main Navigation -->
                <div class="scrollbar-thin flex-1 overflow-y-auto p-3">
                    <div class="mb-3 flex items-center px-3 text-xs font-medium tracking-wider text-amber-200/90 uppercase">
                        <Hammer class="mr-2 h-3 w-3" />
                        <span>Upper Deck</span>
                    </div>
                    <nav class="space-y-1.5">
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.title"
                            :href="item.href"
                            :class="[
                                'relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
                                isActive(item.href)
                                    ? 'nav-active bg-amber-800/80 text-amber-50 shadow-md'
                                    : 'text-amber-100 hover:bg-amber-800/40 hover:text-amber-50',
                            ]"
                            @click="isMobileOpen = false"
                        >
                            <div
                                :class="[
                                    'flex items-center justify-center rounded-lg p-2 transition-all duration-200',
                                    isActive(item.href) ? 'bg-amber-700/80' : 'bg-amber-800/40 hover:bg-amber-700/60',
                                ]"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                            </div>
                            <span>{{ item.title }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- Footer -->
                <div class="border-t border-amber-700/30 p-3">
                    <div class="mb-3 flex items-center px-3 text-xs font-medium tracking-wider text-amber-200/90 uppercase">
                        <Anchor class="mr-2 h-3 w-3" />
                        <span>Lower Deck</span>
                    </div>

                    <!-- User Profile Dropdown for Mobile -->
                    <div class="mt-2 flex flex-col items-center space-y-2">
                        <template v-if="isAuthenticated">
                            <div class="mb-1 w-full text-center">
                                <span class="text-sm font-medium text-amber-100">Account</span>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger class="w-full">
                                    <Button
                                        variant="ghost"
                                        class="flex w-full items-center justify-start gap-3 rounded-lg p-2 transition-all duration-200 hover:bg-amber-800/50 hover:text-amber-50"
                                    >
                                        <Avatar class="h-10 w-10 shrink-0 border-2 border-amber-600/30">
                                            <AvatarImage
                                                v-if="auth.user && auth.user.profile_photo"
                                                :src="`/${auth.user.profile_photo}`"
                                                :alt="auth.user.name || 'User'"
                                            />
                                            <AvatarFallback class="bg-gradient-to-br from-amber-100 to-amber-300 font-semibold text-amber-900">
                                                {{ initials }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex flex-col items-start overflow-hidden">
                                            <span class="w-full truncate text-xs text-amber-200/70">{{ auth.user?.name || 'User' }}</span>
                                            <span class="w-full truncate text-xs text-amber-200/70">{{
                                                auth.user?.email || 'user@example.com'
                                            }}</span>
                                        </div>
                                        <ChevronLeft class="ml-auto h-4 w-4 rotate-90" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    class="mt-2 w-56 rounded-lg border border-amber-200/20 bg-gradient-to-b from-amber-50 to-amber-100 shadow-xl dark:from-amber-800 dark:to-amber-900"
                                >
                                    <UserMenuContent v-if="auth.user" :user="auth.user" />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </template>
                        <template v-else>
                            <!-- Fallback for non-authenticated users on mobile -->
                            <nav class="w-full space-y-1.5">
                                <a
                                    v-for="item in footerNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    :target="item.external ? '_blank' : undefined"
                                    :rel="item.external ? 'noopener noreferrer' : undefined"
                                    :class="[
                                        'relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
                                        isActive(item.href)
                                            ? 'nav-active bg-amber-800/80 text-amber-50 shadow-md'
                                            : 'text-amber-100 hover:bg-amber-800/40 hover:text-amber-50',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'flex items-center justify-center rounded-lg p-2 transition-all duration-200',
                                            isActive(item.href) ? 'bg-amber-700/80' : 'bg-amber-800/40 hover:bg-amber-700/60',
                                        ]"
                                    >
                                        <component :is="item.icon" class="h-5 w-5" />
                                    </div>
                                    <span>{{ item.title }}</span>
                                </a>
                            </nav>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-1 overflow-auto">
            <slot></slot>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for the sidebar */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: rgba(120, 53, 15, 0.1);
    border-radius: 6px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 237, 213, 0.2);
    border-radius: 6px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 237, 213, 0.3);
}

/* Active navigation item styling */
.nav-active {
    position: relative;
}

.nav-active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 60%;
    width: 3px;
    background: linear-gradient(to bottom, #fbbf24, #f59e0b);
    border-radius: 0 3px 3px 0;
    box-shadow: 0 0 8px rgba(251, 191, 36, 0.4);
}

/* Tooltip animation */
.tooltip {
    animation: fadeIn 0.2s ease-in-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Wood grain texture */
.bg-texture {
    background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
    opacity: 0.07;
}

/* Transitions for menu items */
a,
button {
    transition: all 0.2s ease-in-out;
}

a:hover,
button:hover {
    transform: translateY(-1px);
}

a:active,
button:active {
    transform: translateY(0);
}
</style>
