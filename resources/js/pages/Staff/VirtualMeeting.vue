<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import {
    Anchor,
    Check,
    Clock,
    Copy,
    Hammer,
    Info,
    Maximize,
    Mic,
    MicOff,
    Minimize,
    PawPrint,
    Phone,
    Settings,
    Shield,
    Users,
    Video,
    VideoOff,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Define props
const props = defineProps({
    METERED_DOMAIN: {
        type: String,
        required: true,
    },
    MEETING_ID: {
        type: String,
        required: true,
    },
    authUser: {
        type: Object,
        default: () => ({
            id: null,
            name: 'User',
        }),
    },
    readOnly: {
        type: Boolean,
        default: true,
    },
});

// State variables
const isLoading = ref(true);
const error = ref(null);
const meeting = ref(null);
const username = ref(props.authUser?.name || 'User');
const localVideoStream = ref(null);
const cameraOn = ref(false);
const micOn = ref(false);
const meetingInfo = ref({});
const participants = ref([]);
const videoInputDevices = ref([]);
const audioInputDevices = ref([]);
const selectedVideoDevice = ref('');
const selectedAudioDevice = ref('');
const isJoined = ref(false);
const showWaitingArea = ref(true);
const showMeetingView = ref(false);
const showLeaveMeetingView = ref(false);
const remoteParticipants = ref({});
const meetingDuration = ref(0);
const durationInterval = ref(null);
const isParticipantsOpen = ref(false);
const isFullScreen = ref(false);
const copySuccess = ref(false);
const showDeviceSettings = ref(false);
const connectionStatus = ref('connecting');
const showMobileControls = ref(true);
const mobileControlsTimeout = ref(null);
const localVideoPosition = ref({ x: 20, y: 20 });
const isDraggingLocalVideo = ref(false);
const dragStartPosition = ref({ x: 0, y: 0 });
const primaryRemoteParticipant = ref(null);

// Computed properties
const participantCount = computed(() => {
    return Object.keys(remoteParticipants.value).length + 1; // +1 for local participant
});

const formattedDuration = computed(() => {
    return formatDuration(meetingDuration.value);
});

const mainRemoteParticipant = computed(() => {
    // If we have a primary participant selected, use that
    if (primaryRemoteParticipant.value && remoteParticipants.value[primaryRemoteParticipant.value]) {
        return remoteParticipants.value[primaryRemoteParticipant.value];
    }

    // Otherwise, get the first remote participant
    const participantIds = Object.keys(remoteParticipants.value);
    if (participantIds.length > 0) {
        return remoteParticipants.value[participantIds[0]];
    }

    return null;
});

// Initialize the meeting
const initializeMeeting = async () => {
    try {
        isLoading.value = true;
        connectionStatus.value = 'connecting';

        // Load the Metered SDK from CDN if not already loaded
        if (!(window as any).Metered) {
            await loadScript('https://cdn.metered.ca/sdk/latest/sdk.min.js');
        }

        if (!(window as any).Metered) {
            throw new Error('Metered SDK not loaded properly');
        }

        // Create a new meeting instance
        meeting.value = new (window as any).Metered.Meeting();

        // Get available devices
        await getAvailableDevices();

        // Set up event listeners
        setupEventListeners();

        isLoading.value = false;
        connectionStatus.value = 'ready';
    } catch (err) {
        console.error('Error initializing meeting:', err);
        error.value = err.message || 'Failed to initialize meeting';
        isLoading.value = false;
        connectionStatus.value = 'error';
    }
};

// Load script helper function
const loadScript = (url) => {
    return new Promise((resolve, reject) => {
        // Check if script already exists
        const existingScript = document.querySelector(`script[src="${url}"]`);
        if (existingScript) {
            console.log('Script already loaded');
            resolve(true);
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.async = true;

        script.onload = () => {
            console.log('Script loaded successfully');
            resolve(true);
        };

        script.onerror = (err) => {
            console.error('Error loading script:', err);
            reject(new Error(`Failed to load Metered SDK from ${url}`));
        };

        document.body.appendChild(script);
    });
};

// Get available devices
const getAvailableDevices = async () => {
    try {
        if (!meeting.value) return;

        // Get video input devices
        const cameras = await meeting.value.listVideoInputDevices();
        videoInputDevices.value = cameras || [];

        if (cameras && cameras.length > 0) {
            selectedVideoDevice.value = cameras[0].deviceId;
        }

        // Get audio input devices
        const microphones = await meeting.value.listAudioInputDevices();
        audioInputDevices.value = microphones || [];

        if (microphones && microphones.length > 0) {
            selectedAudioDevice.value = microphones[0].deviceId;
        }
    } catch (err) {
        console.error('Error getting available devices:', err);
    }
};

// Set up event listeners for the meeting
const setupEventListeners = () => {
    if (!meeting.value) return;

    // Handle online participants
    meeting.value.on('onlineParticipants', (onlineParticipants) => {
        participants.value = onlineParticipants;

        // Create remote participant objects
        for (let participant of onlineParticipants) {
            if (participant._id !== meeting.value.participantInfo._id && !remoteParticipants.value[participant._id]) {
                remoteParticipants.value[participant._id] = {
                    id: participant._id,
                    name: participant.name,
                    videoStream: null,
                    audioStream: null,
                };

                // If this is the first remote participant, set it as primary
                if (Object.keys(remoteParticipants.value).length === 1) {
                    primaryRemoteParticipant.value = participant._id;
                }
            }
        }
    });

    // Handle participant left
    meeting.value.on('participantLeft', (participantInfo) => {
        if (remoteParticipants.value[participantInfo._id]) {
            // If the primary participant left, select a new one
            if (primaryRemoteParticipant.value === participantInfo._id) {
                const remainingIds = Object.keys(remoteParticipants.value).filter((id) => id !== participantInfo._id);
                primaryRemoteParticipant.value = remainingIds.length > 0 ? remainingIds[0] : null;
            }

            delete remoteParticipants.value[participantInfo._id];
            remoteParticipants.value = { ...remoteParticipants.value };
        }
    });

    // Handle remote track started
    meeting.value.on('remoteTrackStarted', (remoteTrackItem) => {
        if (!remoteParticipants.value[remoteTrackItem.participantSessionId]) {
            return;
        }

        let mediaStream = new MediaStream();
        mediaStream.addTrack(remoteTrackItem.track);

        if (remoteTrackItem.type === 'video') {
            remoteParticipants.value[remoteTrackItem.participantSessionId].videoStream = mediaStream;
        }

        if (remoteTrackItem.type === 'audio') {
            remoteParticipants.value[remoteTrackItem.participantSessionId].audioStream = mediaStream;
        }

        // Force reactivity update
        remoteParticipants.value = { ...remoteParticipants.value };
    });

    // Handle remote track stopped
    meeting.value.on('remoteTrackStopped', (remoteTrackItem) => {
        if (!remoteParticipants.value[remoteTrackItem.participantSessionId]) {
            return;
        }

        if (remoteTrackItem.type === 'video') {
            remoteParticipants.value[remoteTrackItem.participantSessionId].videoStream = null;
        }

        if (remoteTrackItem.type === 'audio') {
            remoteParticipants.value[remoteTrackItem.participantSessionId].audioStream = null;
        }

        // Force reactivity update
        remoteParticipants.value = { ...remoteParticipants.value };
    });

    // Handle connection state changes
    meeting.value.on('connectionStateChanged', (state) => {
        connectionStatus.value = state;
    });
};

// Set a participant as the primary remote participant
const setPrimaryParticipant = (participantId) => {
    primaryRemoteParticipant.value = participantId;
};

// Join the meeting
const joinMeeting = async () => {
    if (!username.value) {
        error.value = 'Please enter a username';
        return;
    }

    try {
        isLoading.value = true;
        connectionStatus.value = 'connecting';

        meetingInfo.value = await meeting.value.join({
            roomURL: `${props.METERED_DOMAIN}/${props.MEETING_ID}`,
            name: username.value,
        });

        console.log('Meeting joined', meetingInfo.value);

        isJoined.value = true;
        showWaitingArea.value = false;
        showMeetingView.value = true;

        // Start camera if enabled
        if (cameraOn.value) {
            await meeting.value.startVideo();
            localVideoStream.value = await meeting.value.getLocalVideoStream();
            const videoElement = document.getElementById('localVideoTag');
            if (videoElement) {
                videoElement.srcObject = localVideoStream.value;
            }
        }

        // Start microphone if enabled
        if (micOn.value) {
            await meeting.value.startAudio();
        }

        // Start duration timer
        startDurationTimer();

        isLoading.value = false;
        connectionStatus.value = 'connected';
    } catch (err) {
        console.error('Error joining meeting:', err);
        error.value = err.message || 'Failed to join meeting';
        isLoading.value = false;
        connectionStatus.value = 'error';
    }
};

// Toggle camera in waiting area
const toggleWaitingAreaCamera = async () => {
    try {
        if (cameraOn.value) {
            cameraOn.value = false;

            if (localVideoStream.value) {
                const tracks = localVideoStream.value.getTracks();
                tracks.forEach((track) => track.stop());
                localVideoStream.value = null;
            }

            const videoElement = document.getElementById('waitingAreaLocalVideo');
            if (videoElement) {
                videoElement.srcObject = null;
            }
        } else {
            cameraOn.value = true;

            if (meeting.value) {
                localVideoStream.value = await meeting.value.getLocalVideoStream();

                const videoElement = document.getElementById('waitingAreaLocalVideo');
                if (videoElement) {
                    videoElement.srcObject = localVideoStream.value;
                }
            }
        }
    } catch (err) {
        console.error('Error toggling waiting area camera:', err);
    }
};

// Toggle microphone in waiting area
const toggleWaitingAreaMicrophone = () => {
    micOn.value = !micOn.value;
};

// Change video input device
const changeVideoDevice = async () => {
    try {
        if (!meeting.value) return;

        await meeting.value.chooseVideoInputDevice(selectedVideoDevice.value);

        if (cameraOn.value) {
            localVideoStream.value = await meeting.value.getLocalVideoStream();

            const videoElement = document.getElementById('waitingAreaLocalVideo');
            if (videoElement) {
                videoElement.srcObject = localVideoStream.value;
            }

            if (isJoined.value) {
                const localVideoTag = document.getElementById('localVideoTag');
                if (localVideoTag) {
                    localVideoTag.srcObject = localVideoStream.value;
                }
            }
        }
    } catch (err) {
        console.error('Error changing video device:', err);
    }
};

// Change audio input device
const changeAudioDevice = async () => {
    try {
        if (!meeting.value) return;

        await meeting.value.chooseAudioInputDevice(selectedAudioDevice.value);
    } catch (err) {
        console.error('Error changing audio device:', err);
    }
};

// Toggle camera in meeting
const toggleCamera = async () => {
    try {
        if (cameraOn.value) {
            cameraOn.value = false;

            await meeting.value.stopVideo();

            if (localVideoStream.value) {
                const tracks = localVideoStream.value.getTracks();
                tracks.forEach((track) => track.stop());
                localVideoStream.value = null;
            }

            const videoElement = document.getElementById('localVideoTag');
            if (videoElement) {
                videoElement.srcObject = null;
            }
        } else {
            cameraOn.value = true;

            await meeting.value.startVideo();
            localVideoStream.value = await meeting.value.getLocalVideoStream();

            const videoElement = document.getElementById('localVideoTag');
            if (videoElement) {
                videoElement.srcObject = localVideoStream.value;
            }
        }
    } catch (err) {
        console.error('Error toggling camera:', err);
    }
};

// Toggle microphone in meeting
const toggleMicrophone = async () => {
    try {
        if (micOn.value) {
            micOn.value = false;
            await meeting.value.stopAudio();
        } else {
            micOn.value = true;
            await meeting.value.startAudio();
        }
    } catch (err) {
        console.error('Error toggling microphone:', err);
    }
};

// Copy meeting link
const copyMeetingLink = () => {
    const meetingLink = `${window.location.origin}/admin/meeting/${props.MEETING_ID}`;
    navigator.clipboard.writeText(meetingLink);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};

// Toggle fullscreen
const toggleFullScreen = () => {
    const meetingContainer = document.getElementById('meeting-container');

    if (!isFullScreen.value) {
        if (meetingContainer.requestFullscreen) {
            meetingContainer.requestFullscreen();
        } else if (meetingContainer.mozRequestFullScreen) {
            meetingContainer.mozRequestFullScreen();
        } else if (meetingContainer.webkitRequestFullscreen) {
            meetingContainer.webkitRequestFullscreen();
        } else if (meetingContainer.msRequestFullscreen) {
            meetingContainer.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
};

// Handle fullscreen change
const handleFullscreenChange = () => {
    isFullScreen.value = !!document.fullscreenElement;
};

// Toggle mobile controls
const toggleMobileControls = () => {
    showMobileControls.value = !showMobileControls.value;

    // Auto-hide controls after 5 seconds
    if (showMobileControls.value && mobileControlsTimeout.value) {
        clearTimeout(mobileControlsTimeout.value);
    }

    if (showMobileControls.value) {
        mobileControlsTimeout.value = setTimeout(() => {
            showMobileControls.value = false;
        }, 5000);
    }
};

// Handle mouse movement
const handleMouseMove = () => {
    if (!showMobileControls.value) {
        showMobileControls.value = true;
    }

    if (mobileControlsTimeout.value) {
        clearTimeout(mobileControlsTimeout.value);
    }

    mobileControlsTimeout.value = setTimeout(() => {
        showMobileControls.value = false;
    }, 5000);
};

// Handle touch start
const handleTouchStart = () => {
    handleMouseMove();
};

// Start dragging local video
const startDragLocalVideo = (event) => {
    isDraggingLocalVideo.value = true;

    // Store the initial position
    if (event.type.startsWith('touch')) {
        dragStartPosition.value = {
            x: event.touches[0].clientX - localVideoPosition.value.x,
            y: event.touches[0].clientY - localVideoPosition.value.y,
        };
    } else {
        dragStartPosition.value = {
            x: event.clientX - localVideoPosition.value.x,
            y: event.clientY - localVideoPosition.value.y,
        };
    }

    // Prevent default behavior to avoid text selection
    event.preventDefault();
};

// Drag local video
const dragLocalVideo = (event) => {
    if (!isDraggingLocalVideo.value) return;

    // Calculate new position
    if (event.type.startsWith('touch')) {
        localVideoPosition.value = {
            x: event.touches[0].clientX - dragStartPosition.value.x,
            y: event.touches[0].clientY - dragStartPosition.value.y,
        };
    } else {
        localVideoPosition.value = {
            x: event.clientX - dragStartPosition.value.x,
            y: event.clientY - dragStartPosition.value.y,
        };
    }

    // Prevent default behavior
    event.preventDefault();
};

// Stop dragging local video
const stopDragLocalVideo = () => {
    isDraggingLocalVideo.value = false;
};

// Start duration timer
const startDurationTimer = () => {
    durationInterval.value = setInterval(() => {
        meetingDuration.value += 1;
    }, 1000);
};

// Stop duration timer
const stopDurationTimer = () => {
    if (durationInterval.value) {
        clearInterval(durationInterval.value);
        durationInterval.value = null;
    }
};

// Format duration
const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return [hours.toString().padStart(2, '0'), minutes.toString().padStart(2, '0'), secs.toString().padStart(2, '0')].join(':');
};

// Leave the meeting
const leaveMeeting = async () => {
    try {
        if (meeting.value) {
            await meeting.value.leaveMeeting();
        }

        stopDurationTimer();
        showMeetingView.value = false;
        showLeaveMeetingView.value = true;
    } catch (err) {
        console.error('Error leaving meeting:', err);
        router.visit('/admin/virtual-meeting');
    }
};

// Return to dashboard
const returnToDashboard = () => {
    router.visit('/dashboard');
};

// Watch for changes in selected devices
watch(selectedVideoDevice, changeVideoDevice);
watch(selectedAudioDevice, changeAudioDevice);

// Lifecycle hooks
onMounted(() => {
    initializeMeeting();

    // Listen for fullscreen change events
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    // Listen for mouse movement to show controls on mobile
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('touchstart', handleTouchStart);

    // Listen for drag events
    document.addEventListener('mousemove', dragLocalVideo);
    document.addEventListener('touchmove', dragLocalVideo);
    document.addEventListener('mouseup', stopDragLocalVideo);
    document.addEventListener('touchend', stopDragLocalVideo);
});

onBeforeUnmount(() => {
    // Clean up
    if (meeting.value) {
        try {
            meeting.value.leaveMeeting();
        } catch (err) {
            console.error('Error leaving meeting during cleanup:', err);
        }
    }

    stopDurationTimer();

    if (localVideoStream.value) {
        const tracks = localVideoStream.value.getTracks();
        tracks.forEach((track) => track.stop());
        localVideoStream.value = null;
    }

    // Remove event listeners
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', handleFullscreenChange);

    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('touchstart', handleTouchStart);

    // Remove drag event listeners
    document.removeEventListener('mousemove', dragLocalVideo);
    document.removeEventListener('touchmove', dragLocalVideo);
    document.removeEventListener('mouseup', stopDragLocalVideo);
    document.removeEventListener('touchend', stopDragLocalVideo);

    if (mobileControlsTimeout.value) {
        clearTimeout(mobileControlsTimeout.value);
    }
});
</script>

<template>
    <AppSidebar>
        <Head title="Virtual Meeting" />

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
                                <Video class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Virtual Meeting</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">Connect with adopters and staff through secure video conferencing.</p>
                            </div>
                        </div>

                        <div v-if="showMeetingView" class="flex flex-wrap items-center justify-end gap-3">
                            <div class="flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 shadow-md">
                                <div
                                    class="h-2 w-2 rounded-full"
                                    :class="{
                                        'animate-pulse bg-green-500': connectionStatus === 'connected',
                                        'animate-pulse bg-yellow-500': connectionStatus === 'connecting',
                                        'bg-red-500': connectionStatus === 'error',
                                    }"
                                ></div>
                                <span class="font-medium text-amber-800">
                                    {{
                                        connectionStatus === 'connected'
                                            ? 'Live'
                                            : connectionStatus === 'connecting'
                                              ? 'Connecting...'
                                              : 'Connection Error'
                                    }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 shadow-md">
                                <Clock class="h-4 w-4 text-amber-800" />
                                <span class="font-medium text-amber-800">{{ formattedDuration }}</span>
                            </div>

                            <div class="flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 shadow-md">
                                <Users class="h-4 w-4 text-amber-800" />
                                <span class="font-medium text-amber-800"
                                    >{{ participantCount }} {{ participantCount === 1 ? 'Participant' : 'Participants' }}</span
                                >
                            </div>

                            <div class="relative">
                                <Button
                                    variant="outline"
                                    @click="copyMeetingLink"
                                    class="border border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200"
                                >
                                    <span v-if="!copySuccess">
                                        <Copy class="mr-2 h-4 w-4" />
                                        Copy Link
                                    </span>
                                    <span v-else>
                                        <Check class="mr-2 h-4 w-4" />
                                        Copied!
                                    </span>
                                </Button>
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

                <!-- Loading State -->
                <div v-if="isLoading" class="rounded-lg border border-amber-200 bg-white p-8 shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 h-16 w-16 animate-spin rounded-full border-t-2 border-b-2 border-amber-500"></div>
                        <p class="text-lg font-medium text-amber-800 dark:text-amber-200">
                            {{ connectionStatus === 'connecting' ? 'Connecting to meeting...' : 'Setting up your meeting...' }}
                        </p>
                        <p class="mt-2 max-w-md text-center text-sm text-amber-600 dark:text-amber-400">
                            This may take a moment as we establish a secure connection to the meeting server.
                        </p>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="rounded-lg border border-amber-200 bg-white p-8 shadow-md dark:border-amber-800 dark:bg-amber-900">
                    <div class="mx-auto max-w-md text-center">
                        <div class="mb-4 text-red-600">
                            <X class="mx-auto h-16 w-16" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-red-700">Failed to Set Up Meeting</h3>
                        <p class="mb-4 text-gray-700 dark:text-gray-300">{{ error }}</p>
                        <div class="mb-4 rounded-lg bg-amber-50 p-4 dark:bg-amber-800/30">
                            <div class="flex items-start gap-3">
                                <Info class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-700 dark:text-amber-300" />
                                <div>
                                    <h4 class="text-sm font-medium text-amber-900 dark:text-amber-100">Troubleshooting Tips</h4>
                                    <ul class="mt-1 list-disc space-y-1 pl-4 text-xs text-amber-700 dark:text-amber-300">
                                        <li>Check your internet connection</li>
                                        <li>Make sure your camera and microphone are properly connected</li>
                                        <li>Try refreshing the page</li>
                                        <li>Ensure the meeting ID is correct</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <Button @click="returnToDashboard" class="bg-amber-700 text-white hover:bg-amber-800"> Return to Meeting Page </Button>
                    </div>
                </div>

                <!-- Waiting Area -->
                <div
                    v-else-if="showWaitingArea"
                    class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900"
                >
                    <div class="p-6">
                        <h2 class="mb-6 text-2xl font-bold text-amber-800 dark:text-amber-200">Join Meeting</h2>

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <!-- Preview Area -->
                            <div class="flex flex-col">
                                <div class="relative mb-4 aspect-video overflow-hidden rounded-lg bg-gray-900">
                                    <video id="waitingAreaLocalVideo" autoplay muted class="h-full w-full object-cover"></video>
                                    <div v-if="!cameraOn" class="absolute inset-0 flex items-center justify-center">
                                        <div class="bg-opacity-75 rounded-full bg-amber-800 p-3">
                                            <VideoOff class="h-8 w-8 text-white" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-6 flex justify-center gap-4">
                                    <button
                                        @click="toggleWaitingAreaMicrophone"
                                        class="flex h-12 w-12 items-center justify-center rounded-full"
                                        :class="micOn ? 'bg-amber-600 hover:bg-amber-700' : 'bg-gray-400 hover:bg-gray-500'"
                                    >
                                        <Mic v-if="micOn" class="h-6 w-6 text-white" />
                                        <MicOff v-else class="h-6 w-6 text-white" />
                                    </button>

                                    <button
                                        @click="toggleWaitingAreaCamera"
                                        class="flex h-12 w-12 items-center justify-center rounded-full"
                                        :class="cameraOn ? 'bg-amber-600 hover:bg-amber-700' : 'bg-gray-400 hover:bg-gray-500'"
                                    >
                                        <Video v-if="cameraOn" class="h-6 w-6 text-white" />
                                        <VideoOff v-else class="h-6 w-6 text-white" />
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-amber-800 dark:text-amber-200"> Camera </label>
                                        <select
                                            v-model="selectedVideoDevice"
                                            class="w-full rounded-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        >
                                            <option v-for="device in videoInputDevices" :key="device.deviceId" :value="device.deviceId">
                                                {{ device.label || `Camera ${videoInputDevices.indexOf(device) + 1}` }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-amber-800 dark:text-amber-200"> Microphone </label>
                                        <select
                                            v-model="selectedAudioDevice"
                                            class="w-full rounded-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                        >
                                            <option v-for="device in audioInputDevices" :key="device.deviceId" :value="device.deviceId">
                                                {{ device.label || `Microphone ${audioInputDevices.indexOf(device) + 1}` }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Join Form -->
                            <div class="flex flex-col">
                                <div class="mb-6 rounded-lg bg-amber-50 p-6 dark:bg-amber-800">
                                    <h3 class="mb-4 text-lg font-medium text-amber-800 dark:text-amber-200">Meeting Information</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-amber-800 dark:text-amber-200"> Meeting ID </label>
                                            <div
                                                class="flex items-center justify-between rounded-md border border-amber-200 bg-white p-3 text-amber-800 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-200"
                                            >
                                                <span>{{ MEETING_ID }}</span>
                                                <Shield class="h-4 w-4 text-green-600" />
                                            </div>
                                        </div>

                                        <div>
                                            <label for="username" class="mb-2 block text-sm font-medium text-amber-800 dark:text-amber-200">
                                                Your Name
                                            </label>
                                            <Input
                                                id="username"
                                                v-model="username"
                                                placeholder="Enter your name"
                                                class="w-full"
                                                :disabled="props.readOnly"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-6 rounded-lg bg-amber-50 p-4 dark:bg-amber-800">
                                    <div class="flex items-start gap-3">
                                        <Info class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-700 dark:text-amber-300" />
                                        <div>
                                            <h4 class="text-sm font-medium text-amber-900 dark:text-amber-100">Meeting Tips</h4>
                                            <ul class="mt-1 list-disc space-y-1 pl-4 text-xs text-amber-700 dark:text-amber-300">
                                                <li>Use headphones for better audio quality</li>
                                                <li>Find a quiet location with good lighting</li>
                                                <li>Mute your microphone when not speaking</li>
                                                <li>Ensure your internet connection is stable</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <Button @click="joinMeeting" class="w-full bg-amber-700 py-3 text-lg text-white hover:bg-amber-800">
                                    Join Meeting
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meeting View -->
                <div
                    v-else-if="showMeetingView"
                    class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900"
                >
                    <div id="meeting-container" class="relative h-[calc(100vh-16rem)]" @click="toggleMobileControls">
                        <!-- Main Video Area - Always shows the remote participant -->
                        <div class="absolute inset-0 flex flex-col bg-gray-900">
                            <!-- Main Remote Participant Video -->
                            <div class="relative flex-1">
                                <!-- No remote participants yet -->
                                <div v-if="!mainRemoteParticipant" class="absolute inset-0 flex flex-col items-center justify-center text-white">
                                    <Users class="mb-4 h-16 w-16 text-amber-500" />
                                    <p class="text-lg font-medium">Waiting for others to join...</p>
                                    <p class="mt-2 text-sm text-gray-400">Share your meeting link to invite participants</p>
                                </div>

                                <!-- Remote participant video -->
                                <template v-else>
                                    <video :srcObject="mainRemoteParticipant.videoStream" autoplay class="h-full w-full object-contain"></video>
                                    <audio :srcObject="mainRemoteParticipant.audioStream" autoplay></audio>

                                    <!-- Show camera off indicator if remote participant has no video -->
                                    <div v-if="!mainRemoteParticipant.videoStream" class="absolute inset-0 flex items-center justify-center">
                                        <div class="bg-opacity-75 rounded-full bg-amber-800 p-6">
                                            <VideoOff class="h-12 w-12 text-white" />
                                        </div>
                                    </div>

                                    <!-- Remote participant name -->
                                    <div class="bg-opacity-50 absolute bottom-4 left-4 rounded-lg bg-black px-4 py-2 text-white">
                                        {{ mainRemoteParticipant.name }}
                                    </div>
                                </template>

                                <!-- Local Video (Picture-in-Picture) - Draggable -->
                                <div
                                    v-if="cameraOn"
                                    class="absolute aspect-video w-1/4 max-w-[240px] cursor-move overflow-hidden rounded-lg border-2 border-amber-500 shadow-lg"
                                    :style="`top: ${localVideoPosition.y}px; right: ${localVideoPosition.x}px;`"
                                    @mousedown="startDragLocalVideo"
                                    @touchstart="startDragLocalVideo"
                                >
                                    <video id="localVideoTag" autoplay muted class="h-full w-full object-cover"></video>
                                    <div class="bg-opacity-50 absolute bottom-0 w-full bg-black p-1 text-center text-xs text-white">
                                        {{ username }} (You)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meeting Controls (Desktop) -->
                        <div
                            v-if="showMobileControls"
                            class="absolute right-0 bottom-0 left-0 hidden bg-gradient-to-t from-black to-transparent p-4 md:block"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <!-- Meeting Info -->
                                    <div class="text-white">
                                        <p class="text-sm font-medium">Room: {{ MEETING_ID }}</p>
                                        <p class="text-xs">{{ participantCount }} participant(s) • {{ formattedDuration }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <!-- Audio Toggle -->
                                    <button
                                        @click="toggleMicrophone"
                                        class="flex h-10 w-10 items-center justify-center rounded-full"
                                        :class="micOn ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'"
                                    >
                                        <Mic v-if="micOn" class="h-5 w-5 text-white" />
                                        <MicOff v-else class="h-5 w-5 text-white" />
                                    </button>

                                    <!-- Video Toggle -->
                                    <button
                                        @click="toggleCamera"
                                        class="flex h-10 w-10 items-center justify-center rounded-full"
                                        :class="cameraOn ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'"
                                    >
                                        <Video v-if="cameraOn" class="h-5 w-5 text-white" />
                                        <VideoOff v-else class="h-5 w-5 text-white" />
                                    </button>

                                    <!-- Settings -->
                                    <button
                                        @click="showDeviceSettings = !showDeviceSettings"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-600 hover:bg-amber-700"
                                    >
                                        <Settings class="h-5 w-5 text-white" />
                                    </button>

                                    <!-- Fullscreen Toggle -->
                                    <button
                                        @click="toggleFullScreen"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-600 hover:bg-amber-700"
                                    >
                                        <Maximize v-if="!isFullScreen" class="h-5 w-5 text-white" />
                                        <Minimize v-else class="h-5 w-5 text-white" />
                                    </button>

                                    <!-- Leave Meeting -->
                                    <button
                                        @click="leaveMeeting"
                                        class="flex h-10 items-center justify-center rounded-full bg-red-600 px-4 text-white hover:bg-red-700"
                                    >
                                        <Phone class="mr-2 h-5 w-5 rotate-135 transform" />
                                        Leave
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Meeting Controls (Mobile) -->
                        <div
                            v-if="showMobileControls"
                            class="absolute right-0 bottom-0 left-0 bg-gradient-to-t from-black to-transparent p-4 md:hidden"
                        >
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-white">
                                        <p class="text-sm font-medium">Room: {{ MEETING_ID }}</p>
                                        <p class="text-xs">{{ participantCount }} participant(s)</p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-white">{{ formattedDuration }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-3">
                                    <!-- Audio Toggle -->
                                    <button
                                        @click="toggleMicrophone"
                                        class="flex h-12 w-12 items-center justify-center rounded-full"
                                        :class="micOn ? 'bg-amber-600' : 'bg-red-600'"
                                    >
                                        <Mic v-if="micOn" class="h-6 w-6 text-white" />
                                        <MicOff v-else class="h-6 w-6 text-white" />
                                    </button>

                                    <!-- Video Toggle -->
                                    <button
                                        @click="toggleCamera"
                                        class="flex h-12 w-12 items-center justify-center rounded-full"
                                        :class="cameraOn ? 'bg-amber-600' : 'bg-red-600'"
                                    >
                                        <Video v-if="cameraOn" class="h-6 w-6 text-white" />
                                        <VideoOff v-else class="h-6 w-6 text-white" />
                                    </button>

                                    <!-- Leave Meeting -->
                                    <button @click="leaveMeeting" class="flex h-12 w-12 items-center justify-center rounded-full bg-red-600">
                                        <Phone class="h-6 w-6 rotate-135 transform text-white" />
                                    </button>

                                    <!-- More Options -->
                                    <button
                                        @click="showDeviceSettings = !showDeviceSettings"
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-600"
                                    >
                                        <Settings class="h-6 w-6 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Device Settings Panel -->
                        <div
                            v-if="showDeviceSettings"
                            class="absolute top-1/2 left-1/2 z-30 w-96 max-w-[90%] -translate-x-1/2 -translate-y-1/2 transform rounded-lg border border-amber-200 bg-white shadow-xl dark:border-amber-800 dark:bg-amber-900"
                        >
                            <div class="flex items-center justify-between border-b border-amber-200 p-4 dark:border-amber-800">
                                <h3 class="font-medium text-amber-900 dark:text-amber-100">Device Settings</h3>
                                <button
                                    @click="showDeviceSettings = false"
                                    class="text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="space-y-4 p-4">
                                <!-- Microphone Selection -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-amber-900 dark:text-amber-100"> Microphone </label>
                                    <select
                                        v-model="selectedAudioDevice"
                                        class="w-full rounded-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    >
                                        <option v-for="device in audioInputDevices" :key="device.deviceId" :value="device.deviceId">
                                            {{ device.label || `Microphone ${audioInputDevices.indexOf(device) + 1}` }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Camera Selection -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-amber-900 dark:text-amber-100"> Camera </label>
                                    <select
                                        v-model="selectedVideoDevice"
                                        class="w-full rounded-md border-amber-200 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                    >
                                        <option v-for="device in videoInputDevices" :key="device.deviceId" :value="device.deviceId">
                                            {{ device.label || `Camera ${videoInputDevices.indexOf(device) + 1}` }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Meeting Controls -->
                                <div class="border-t border-amber-200 pt-4 dark:border-amber-800">
                                    <h4 class="mb-3 text-sm font-medium text-amber-900 dark:text-amber-100">Meeting Controls</h4>

                                    <div class="grid grid-cols-2 gap-3">
                                        <Button
                                            @click="toggleMicrophone"
                                            variant="outline"
                                            class="flex items-center justify-center gap-2"
                                            :class="micOn ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'"
                                        >
                                            <Mic v-if="micOn" class="h-4 w-4" />
                                            <MicOff v-else class="h-4 w-4" />
                                            {{ micOn ? 'Mute' : 'Unmute' }}
                                        </Button>

                                        <Button
                                            @click="toggleCamera"
                                            variant="outline"
                                            class="flex items-center justify-center gap-2"
                                            :class="cameraOn ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'"
                                        >
                                            <Video v-if="cameraOn" class="h-4 w-4" />
                                            <VideoOff v-else class="h-4 w-4" />
                                            {{ cameraOn ? 'Stop Video' : 'Start Video' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end border-t border-amber-200 p-4 dark:border-amber-800">
                                <Button @click="showDeviceSettings = false" class="bg-amber-700 text-white hover:bg-amber-800"> Done </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Meeting View -->
                <div
                    v-else-if="showLeaveMeetingView"
                    class="rounded-lg border border-amber-200 bg-white p-8 shadow-md dark:border-amber-800 dark:bg-amber-900"
                >
                    <div class="mx-auto max-w-md text-center">
                        <div class="mb-4 text-amber-600">
                            <Check class="mx-auto h-16 w-16" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-amber-700 dark:text-amber-300">You've Left the Meeting</h3>
                        <p class="mb-6 text-gray-700 dark:text-gray-300">Thank you for participating in the virtual meeting.</p>
                        <Button @click="returnToDashboard" class="bg-amber-700 text-white hover:bg-amber-800"> Return to Dashboard </Button>
                    </div>
                </div>

                <!-- Decorative Element -->
                <div class="mt-8 flex justify-center">
                    <PawPrint class="h-8 w-8 text-amber-400 opacity-50 dark:text-amber-700" />
                </div>
            </div>
        </div>
    </AppSidebar>
</template>

<style scoped>
/* Custom scrollbar for the content */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
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

/* Video container styles */
video {
    background-color: #1a1a1a;
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

/* Connection status indicators */
.connection-status-dot {
    height: 8px;
    width: 8px;
    border-radius: 50%;
    display: inline-block;
}

.connection-status-connected {
    background-color: #10b981;
    animation: pulse 2s infinite;
}

.connection-status-connecting {
    background-color: #f59e0b;
    animation: pulse 1s infinite;
}

.connection-status-error {
    background-color: #ef4444;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}
</style>
