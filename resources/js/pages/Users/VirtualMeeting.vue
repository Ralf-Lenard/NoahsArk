<script setup lang="ts">
import AppNavBarUser from '@/components/AppNavBarUser.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { Check, Clock, Copy, Info, Maximize, Mic, MicOff, Minimize, PawPrint, Phone, Ship, User, Users, Video, VideoOff, X } from 'lucide-vue-next';
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

const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: 'Virtual Meeting', href: '/virtual-meeting' },
];

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
    <div class="min-h-screen bg-amber-50">
        <Head title="Virtual Meeting - Noah's Ark" />

        <!-- User Navbar -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <!-- Main Content -->
        <div id="meeting-container" class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex h-[70vh] items-center justify-center">
                <div class="text-center">
                    <div class="mb-4 inline-block h-12 w-12 animate-spin rounded-full border-4 border-amber-200 border-t-amber-600"></div>
                    <p class="text-lg font-medium text-amber-800">
                        {{ connectionStatus === 'connecting' ? 'Connecting to meeting...' : 'Loading...' }}
                    </p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center shadow-sm">
                <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <X class="h-6 w-6 text-red-600" />
                </div>
                <h2 class="mb-2 text-lg font-medium text-red-800">Error</h2>
                <p class="text-red-600">{{ error }}</p>
                <Button @click="initializeMeeting" class="mt-4 bg-amber-600 text-white hover:bg-amber-700"> Try Again </Button>
            </div>

            <!-- Waiting Area -->
            <div v-else-if="showWaitingArea" class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-amber-700 to-amber-900 p-4 text-white">
                    <div class="flex items-center">
                        <Ship class="mr-2 h-6 w-6" />
                        <h2 class="text-xl font-bold">Noah's Ark Virtual Meeting</h2>
                    </div>
                    <p class="mt-1 text-amber-100">Meeting ID: {{ props.MEETING_ID }}</p>
                </div>

                <div class="p-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Video Preview -->
                        <div class="flex flex-col">
                            <div class="relative mb-4 h-64 overflow-hidden rounded-lg border border-amber-200 bg-amber-100">
                                <video id="waitingAreaLocalVideo" autoplay muted playsinline class="h-full w-full object-cover"></video>

                                <div v-if="!cameraOn" class="absolute inset-0 flex flex-col items-center justify-center">
                                    <User class="h-16 w-16 text-amber-400" />
                                    <p class="mt-2 text-amber-700">Camera is off</p>
                                </div>

                                <div class="absolute right-0 bottom-4 left-0 flex justify-center space-x-2">
                                    <button
                                        @click="toggleWaitingAreaCamera"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-700 text-white hover:bg-amber-800"
                                        :title="cameraOn ? 'Turn off camera' : 'Turn on camera'"
                                    >
                                        <Video v-if="cameraOn" class="h-5 w-5" />
                                        <VideoOff v-else class="h-5 w-5" />
                                    </button>

                                    <button
                                        @click="toggleWaitingAreaMicrophone"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-700 text-white hover:bg-amber-800"
                                        :title="micOn ? 'Turn off microphone' : 'Turn on microphone'"
                                    >
                                        <Mic v-if="micOn" class="h-5 w-5" />
                                        <MicOff v-else class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Device Settings -->
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <h3 class="mb-3 font-medium text-amber-900">Device Settings</h3>

                                <div class="space-y-4">
                                    <div>
                                        <label for="videoDevice" class="mb-1 block text-sm font-medium text-amber-700"> Camera </label>
                                        <select
                                            id="videoDevice"
                                            v-model="selectedVideoDevice"
                                            class="w-full rounded-md border border-amber-300 bg-white p-2 text-amber-900 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                        >
                                            <option v-for="device in videoInputDevices" :key="device.deviceId" :value="device.deviceId">
                                                {{ device.label || `Camera ${videoInputDevices.indexOf(device) + 1}` }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="audioDevice" class="mb-1 block text-sm font-medium text-amber-700"> Microphone </label>
                                        <select
                                            id="audioDevice"
                                            v-model="selectedAudioDevice"
                                            class="w-full rounded-md border border-amber-300 bg-white p-2 text-amber-900 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                        >
                                            <option v-for="device in audioInputDevices" :key="device.deviceId" :value="device.deviceId">
                                                {{ device.label || `Microphone ${audioInputDevices.indexOf(device) + 1}` }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Join Form -->
                        <div class="flex flex-col">
                            <div class="h-full rounded-lg border border-amber-200 bg-amber-50 p-6">
                                <h3 class="mb-6 text-xl font-bold text-amber-900">Join Meeting</h3>

                                <div class="space-y-6">
                                    <div>
                                        <label for="username" class="mb-1 block text-sm font-medium text-amber-700"> Your Name </label>
                                        <Input
                                            id="username"
                                            v-model="username"
                                            type="text"
                                            placeholder="Enter your name"
                                            class="w-full border-amber-300 focus:border-amber-500 focus:ring-amber-500"
                                        />
                                    </div>

                                    <div class="rounded-lg bg-amber-100 p-4 text-amber-800">
                                        <div class="flex items-start">
                                            <Info class="mr-2 h-5 w-5 flex-shrink-0 text-amber-600" />
                                            <div>
                                                <p class="text-sm">
                                                    You're about to join a virtual meeting. Make sure your camera and microphone are working properly.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <button
                                                @click="copyMeetingLink"
                                                class="flex items-center space-x-1 rounded-md bg-amber-100 px-3 py-1 text-sm text-amber-700 hover:bg-amber-200"
                                            >
                                                <span>Copy Link</span>
                                                <Copy v-if="!copySuccess" class="h-4 w-4" />
                                                <Check v-else class="h-4 w-4 text-green-600" />
                                            </button>
                                        </div>

                                        <Button
                                            @click="joinMeeting"
                                            class="bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                                            :disabled="isLoading"
                                        >
                                            Join Now
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meeting Tips -->
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h3 class="mb-3 font-medium text-amber-900">Meeting Tips</h3>
                        <ul class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                            <li class="flex items-start">
                                <PawPrint class="mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">Find a quiet place with good lighting</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">Use headphones for better audio</span>
                            </li>
                            <li class="flex items-start">
                                <PawPrint class="mr-2 h-4 w-4 text-amber-600" />
                                <span class="text-sm text-amber-700">Mute when not speaking</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Meeting View -->
            <div
                v-else-if="showMeetingView"
                class="relative h-[80vh] overflow-hidden rounded-xl bg-amber-900"
                @click="toggleMobileControls"
                @mousemove="handleMouseMove"
                @touchstart="handleTouchStart"
            >
                <!-- Main Video Area -->
                <div class="absolute inset-0 flex items-center justify-center bg-amber-950">
                    <!-- No Remote Participants -->
                    <div v-if="!mainRemoteParticipant" class="text-center text-white">
                        <Ship class="mx-auto h-16 w-16 text-amber-400" />
                        <h3 class="mt-4 text-xl font-medium">Waiting for others to join</h3>
                        <p class="mt-2 text-amber-300">You're the only one here right now</p>
                    </div>

                    <!-- Remote Participant Video -->
                    <video
                        v-else-if="mainRemoteParticipant.videoStream"
                        :srcObject="mainRemoteParticipant.videoStream"
                        autoplay
                        playsinline
                        class="h-full w-full object-contain"
                    ></video>

                    <!-- Remote Participant No Video -->
                    <div v-else-if="mainRemoteParticipant" class="text-center text-white">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-amber-800">
                            <User class="h-12 w-12 text-amber-300" />
                        </div>
                        <h3 class="mt-4 text-xl font-medium">{{ mainRemoteParticipant.name }}</h3>
                        <p class="mt-2 text-amber-300">Camera is turned off</p>
                    </div>

                    <!-- Remote Participant Audio -->
                    <audio
                        v-if="mainRemoteParticipant && mainRemoteParticipant.audioStream"
                        :srcObject="mainRemoteParticipant.audioStream"
                        autoplay
                    ></audio>
                </div>

                <!-- Local Video -->
                <div
                    v-if="cameraOn"
                    class="absolute z-10 h-36 w-48 overflow-hidden rounded-lg border-2 border-amber-600 bg-amber-950 shadow-lg"
                    :style="{
                        top: `${localVideoPosition.y}px`,
                        left: `${localVideoPosition.x}px`,
                    }"
                    @mousedown="startDragLocalVideo"
                    @touchstart="startDragLocalVideo"
                >
                    <video id="localVideoTag" autoplay muted playsinline class="h-full w-full object-cover"></video>
                    <div class="absolute bottom-1 left-1 rounded bg-black/50 px-1 py-0.5 text-xs text-white">You</div>
                </div>

                <!-- Meeting Controls -->
                <div
                    class="absolute right-0 bottom-0 left-0 bg-gradient-to-t from-black/80 to-transparent p-4 transition-opacity duration-300"
                    :class="{ 'opacity-0': !showMobileControls && !isParticipantsOpen }"
                >
                    <div class="flex items-center justify-between">
                        <!-- Left Controls -->
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2 text-white">
                                <Clock class="h-4 w-4" />
                                <span>{{ formattedDuration }}</span>
                            </div>

                            <div class="hidden items-center space-x-2 text-white md:flex">
                                <Users class="h-4 w-4" />
                                <span>{{ participantCount }} {{ participantCount === 1 ? 'participant' : 'participants' }}</span>
                            </div>
                        </div>

                        <!-- Center Controls -->
                        <div class="flex items-center space-x-3">
                            <button
                                @click.stop="toggleCamera"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-700 text-white hover:bg-amber-800"
                                :class="{ 'bg-red-600 hover:bg-red-700': !cameraOn }"
                            >
                                <Video v-if="cameraOn" class="h-5 w-5" />
                                <VideoOff v-else class="h-5 w-5" />
                            </button>

                            <button
                                @click.stop="toggleMicrophone"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-700 text-white hover:bg-amber-800"
                                :class="{ 'bg-red-600 hover:bg-red-700': !micOn }"
                            >
                                <Mic v-if="micOn" class="h-5 w-5" />
                                <MicOff v-else class="h-5 w-5" />
                            </button>

                            <button
                                @click.stop="leaveMeeting"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700"
                            >
                                <Phone class="h-5 w-5 rotate-[135deg]" />
                            </button>
                        </div>

                        <!-- Right Controls -->
                        <div class="flex items-center space-x-3">
                            <button
                                @click.stop="isParticipantsOpen = !isParticipantsOpen"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-700/50 text-white hover:bg-amber-700"
                                :class="{ 'bg-amber-600': isParticipantsOpen }"
                            >
                                <Users class="h-4 w-4" />
                            </button>

                            <button
                                @click.stop="toggleFullScreen"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-700/50 text-white hover:bg-amber-700"
                            >
                                <Maximize v-if="!isFullScreen" class="h-4 w-4" />
                                <Minimize v-else class="h-4 w-4" />
                            </button>

                            <button
                                @click.stop="copyMeetingLink"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-700/50 text-white hover:bg-amber-700"
                            >
                                <Copy v-if="!copySuccess" class="h-4 w-4" />
                                <Check v-else class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Participants Panel -->
                <div v-if="isParticipantsOpen" class="absolute top-0 right-0 bottom-0 z-20 w-64 bg-amber-800 p-4 text-white shadow-lg md:w-80">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Participants ({{ participantCount }})</h3>
                        <button @click.stop="isParticipantsOpen = false" class="rounded-full p-1 hover:bg-amber-700">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <!-- Local Participant -->
                        <div class="flex items-center rounded-lg bg-amber-700/30 p-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-600">
                                <User class="h-4 w-4" />
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="font-medium">{{ username }} (You)</p>
                            </div>
                            <div class="flex space-x-1">
                                <div class="text-xs">
                                    <VideoOff v-if="!cameraOn" class="h-4 w-4 text-amber-400" />
                                    <MicOff v-if="!micOn" class="h-4 w-4 text-amber-400" />
                                </div>
                            </div>
                        </div>

                        <!-- Remote Participants -->
                        <div
                            v-for="(participant, id) in remoteParticipants"
                            :key="id"
                            class="flex cursor-pointer items-center rounded-lg p-2 hover:bg-amber-700/30"
                            :class="{ 'bg-amber-700/50': primaryRemoteParticipant === id }"
                            @click="setPrimaryParticipant(id)"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-600">
                                <User class="h-4 w-4" />
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="font-medium">{{ participant.name }}</p>
                            </div>
                            <div class="flex space-x-1">
                                <div class="text-xs">
                                    <VideoOff v-if="!participant.videoStream" class="h-4 w-4 text-amber-400" />
                                    <MicOff v-if="!participant.audioStream" class="h-4 w-4 text-amber-400" />
                                </div>
                            </div>
                        </div>

                        <!-- No Remote Participants -->
                        <div v-if="Object.keys(remoteParticipants).length === 0" class="py-4 text-center text-amber-300">
                            <p>No other participants yet</p>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <div
                    v-if="connectionStatus !== 'connected'"
                    class="absolute top-4 left-1/2 -translate-x-1/2 rounded-full bg-amber-800 px-3 py-1 text-sm text-white"
                >
                    {{ connectionStatus === 'connecting' ? 'Connecting...' : connectionStatus }}
                </div>
            </div>

            <!-- Leave Meeting View -->
            <div v-else-if="showLeaveMeetingView" class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-amber-700 to-amber-900 p-6 text-center text-white">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-600">
                        <Ship class="h-8 w-8" />
                    </div>
                    <h2 class="text-2xl font-bold">You've Left the Meeting</h2>
                    <p class="mt-2 text-amber-100">Meeting duration: {{ formattedDuration }}</p>
                </div>

                <div class="p-6 text-center">
                    <p class="mb-6 text-amber-700">Thank you for participating in the Noah's Ark virtual meeting.</p>

                    <div class="flex flex-col justify-center space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                        <Button @click="returnToDashboard" class="border border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200">
                            Return to Dashboard
                        </Button>

                        <Button
                            @click="
                                showWaitingArea = true;
                                showLeaveMeetingView = false;
                            "
                            class="bg-gradient-to-r from-amber-600 to-amber-800 text-white hover:from-amber-700 hover:to-amber-900"
                        >
                            Rejoin Meeting
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
