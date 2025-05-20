<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { User } from 'lucide-vue-next';

import { format, isToday, isYesterday } from 'date-fns';
import Pusher from 'pusher-js';

import AppNavBarUser from '@/components/AppNavBarUser.vue';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';

import axios from 'axios';
import { ArrowLeft, Check, CheckCheck, Paperclip, Search, Send, Video, X } from 'lucide-vue-next';

// Define props
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

// State
const contacts = ref([]);
const messages = ref([]);
const selectedContact = ref(null);
const newMessage = ref('');
const isLoading = ref(false);
const isLoadingMessages = ref(false);
const searchQuery = ref('');
const selectedFile = ref(null);
const filePreview = ref(null);
const fileInputRef = ref(null);
const messagesContainerRef = ref(null);
const unreadCount = ref(0);
const showEmojiPicker = ref(false);
const isTyping = ref(false);
const typingTimeout = ref(null);
const pusher = ref(null);
const channel = ref(null);
const debugMode = ref(true); // Enable for debugging

// Added missing variables used in template
const isMobileView = ref(window.innerWidth < 768);
const showConversation = ref(false);
const breadcrumbs = [
    { title: 'Home', href: '/home' },
    { title: 'Messages', href: '/chats' },
];
const messageText = ref(''); // This will replace newMessage for consistency with template

// Computed properties
const filteredContacts = computed(() => {
    if (!searchQuery.value) return contacts.value;

    const query = searchQuery.value.toLowerCase();
    return contacts.value.filter((contact) => contact.name.toLowerCase().includes(query));
});

const sortedMessages = computed(() => {
    // Group messages by date
    const groupedMessages = {};

    messages.value.forEach((message) => {
        const date = new Date(message.created_at);
        const dateKey = format(date, 'yyyy-MM-dd');

        if (!groupedMessages[dateKey]) {
            groupedMessages[dateKey] = [];
        }

        groupedMessages[dateKey].push(message);
    });

    return groupedMessages;
});

// Added computed property for file type
const fileType = computed(() => {
    if (!selectedFile.value) return null;
    if (selectedFile.value.type.startsWith('image/')) return 'image';
    if (selectedFile.value.type.startsWith('video/')) return 'video';
    return 'file';
});

// Debug logging function
const logDebug = (...args) => {
    if (debugMode.value) {
        console.log(...args);
    }
};

// Methods
const fetchContacts = async () => {
    try {
        const response = await axios.get('/chats/contacts');
        contacts.value = response.data.contacts;
        logDebug('Contacts fetched:', contacts.value);
    } catch (error) {
        console.error('Error fetching contacts:', error);
    }
};

const fetchMessages = async (contactId) => {
    if (!contactId) return;

    try {
        isLoadingMessages.value = true;
        const response = await axios.get(`/chats/messages/${contactId}`);
        messages.value = response.data;
        logDebug('Messages fetched:', messages.value);

        // Mark messages as read
        await axios.post(`/chats/mark-as-read/${contactId}`);

        // Update unread count in contacts list
        const contactIndex = contacts.value.findIndex((c) => c.id === contactId);
        if (contactIndex !== -1) {
            // Update the local contacts array to show 0 unread messages
            contacts.value = contacts.value.map((contact) => {
                if (contact.id === contactId) {
                    return { ...contact, unread_messages_count: 0 };
                }
                return contact;
            });

            // Update the global unread count
            fetchUnreadCount();
        }

        // Scroll to bottom
        await nextTick();
        scrollToBottom();
    } catch (error) {
        if (error.response && error.response.status === 404) {
            // No messages yet, that's okay
            messages.value = [];
        } else {
            console.error('Error fetching messages:', error);
        }
    } finally {
        isLoadingMessages.value = false;
    }
};

const selectContact = async (contact) => {
    selectedContact.value = contact;
    showConversation.value = true; // Show conversation on mobile
    await fetchMessages(contact.id);

    // Reset message input and file
    messageText.value = '';
    newMessage.value = '';
    selectedFile.value = null;
    filePreview.value = null;
};
const sendMessage = async () => {
    if ((!messageText.value || messageText.value.trim() === '') && !selectedFile.value) return;

    try {
        const formData = new FormData();
        formData.append('receiver_id', selectedContact.value.id);

        if (messageText.value.trim() !== '') {
            formData.append('message', messageText.value);
        }

        if (selectedFile.value) {
            formData.append('file', selectedFile.value);
        }

        // Add the message to our local messages immediately for instant feedback
        const tempId = `temp-${Date.now()}`;
        const tempMessage = {
            id: tempId,
            sender_id: props.user.id,
            receiver_id: selectedContact.value.id,
            message: messageText.value.trim() !== '' ? messageText.value : null,
            image_path: selectedFile.value && selectedFile.value.type.startsWith('image/') ? 'pending' : null,
            video_path: selectedFile.value && selectedFile.value.type.startsWith('video/') ? 'pending' : null,
            created_at: new Date().toISOString(),
            unread: true,
            sender: {
                id: props.user.id,
                name: props.user.name,
                profile_photo: props.user.profile_photo,
            },
            timestamp: new Date().toISOString(),
        };

        messages.value.push(tempMessage);
        await nextTick();
        scrollToBottom();

        // Reset input fields
        messageText.value = '';
        selectedFile.value = null;
        filePreview.value = null;

        // Send to server
        const response = await axios.post('/chats/send', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        logDebug('Message sent response:', response.data);

        // After receiving a successful response from the server, update the message paths
        const { image_path, video_path } = response.data.data;
        tempMessage.image_path = image_path || null;
        tempMessage.video_path = video_path || null;

        // Replace temp message with actual message from server if needed
        messages.value = messages.value.map((msg) => (msg.id === tempId ? { ...msg, ...tempMessage } : msg));
    } catch (error) {
        console.error('Error sending message:', error);
        messages.value = messages.value.filter((m) => !m.id.toString().startsWith('temp-'));
    }
};

const updateContactLastMessage = (contactId, message) => {
    logDebug('Updating contact last message:', contactId, message);

    const contactIndex = contacts.value.findIndex((c) => c.id === contactId);
    if (contactIndex !== -1) {
        // Create a new contacts array to ensure reactivity
        contacts.value = contacts.value.map((contact) => {
            if (contact.id === contactId) {
                return {
                    ...contact,
                    last_message:
                        message.message || (message.image_path ? 'Sent an image' : message.video_path ? 'Sent a video' : 'Sent an attachment'),
                    last_message_time: message.created_at,
                    // Only increment unread count if this contact is not currently selected
                    unread_messages_count:
                        message.sender_id !== props.user.id && (!selectedContact.value || selectedContact.value.id !== contactId)
                            ? (contact.unread_messages_count || 0) + 1
                            : contact.unread_messages_count,
                };
            }
            return contact;
        });

        // Re-sort contacts to bring the most recent to the top
        contacts.value = [...contacts.value].sort((a, b) => {
            return new Date(b.last_message_time) - new Date(a.last_message_time);
        });
    }
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    selectedFile.value = file;

    // Create preview
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = {
                type: 'image',
                url: e.target.result,
            };
        };
        reader.readAsDataURL(file);
    } else if (file.type.startsWith('video/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = {
                type: 'video',
                url: e.target.result,
            };
        };
        reader.readAsDataURL(file);
    } else {
        filePreview.value = {
            type: 'file',
            name: file.name,
        };
    }
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    filePreview.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const scrollToBottom = () => {
    if (messagesContainerRef.value) {
        messagesContainerRef.value.scrollTop = messagesContainerRef.value.scrollHeight;
    }
};

const formatMessageDate = (dateString) => {
    const date = new Date(dateString);

    if (isToday(date)) {
        return 'Today';
    } else if (isYesterday(date)) {
        return 'Yesterday';
    } else {
        return format(date, 'MMMM d, yyyy');
    }
};

const formatMessageTime = (dateString) => {
    const date = new Date(dateString);
    return format(date, 'h:mm a');
};

const formatLastMessageTime = (dateString) => {
    if (!dateString) return '';

    const date = new Date(dateString);

    if (isToday(date)) {
        return format(date, 'h:mm a');
    } else if (isYesterday(date)) {
        return 'Yesterday';
    } else {
        return format(date, 'MMM d');
    }
};

const fetchUnreadCount = async () => {
    try {
        const response = await axios.get('/chats/unread-count');
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Error fetching unread count:', error);
    }
};

const handleNewMessage = (data) => {
    logDebug('New message received via Pusher:', data);

    // Check if the message is for the current user
    if (data.receiver_id === props.user.id) {
        // Check if the sender is the currently selected contact
        const isFromSelectedContact = selectedContact.value && data.sender_id === selectedContact.value.id;

        // Only increment unread count if the message is not from the selected contact
        if (!isFromSelectedContact) {
            unreadCount.value++;
        }

        // If the sender is the currently selected contact, add the message to the chat
        if (isFromSelectedContact) {
            // Format the message properly to match the expected structure
            const newMessage = {
                id: data.id || `temp-${Date.now()}`,
                sender_id: data.sender_id,
                receiver_id: data.receiver_id,
                message: data.message,
                image_path: data.image_path,
                video_path: data.video_path,
                created_at: data.timestamp || new Date().toISOString(),
                // Mark as read immediately since we're viewing this conversation
                unread: false,
                // Add sender info if available
                sender: {
                    id: data.sender_id,
                    name: selectedContact.value.name,
                    profile_photo: selectedContact.value.profile_photo,
                },
                // Add timestamp for template
                timestamp: data.timestamp || new Date().toISOString(),
            };

            // Add the message to the messages array
            messages.value = [...messages.value, newMessage];

            // Mark the message as read since we're viewing this conversation
            axios.post(`/chats/mark-as-read/${data.sender_id}`);

            // Scroll to bottom
            nextTick(() => {
                scrollToBottom();
            });
        }

        // Update the contact's last message
        updateContactLastMessage(data.sender_id, {
            message: data.message || (data.image_path ? 'Sent an image' : data.video_path ? 'Sent a video' : 'Sent an attachment'),
            created_at: data.timestamp || new Date().toISOString(),
            sender_id: data.sender_id,
            image_path: data.image_path,
            video_path: data.video_path,
        });
    }
};

const initializePusher = () => {
    try {
        // Initialize Pusher with debug logging
        Pusher.logToConsole = debugMode.value;

        pusher.value = new Pusher('c27cf1cca7e151dffc12', {
            cluster: 'ap1',
            forceTLS: true,
        });

        // Subscribe to the user's channel
        const channelName = `chat.${props.user.id}`;
        logDebug('Subscribing to Pusher channel:', channelName);

        channel.value = pusher.value.subscribe(channelName);

        // Listen for connection events
        pusher.value.connection.bind('connected', () => {
            logDebug('Connected to Pusher successfully');
        });

        pusher.value.connection.bind('error', (err) => {
            console.error('Pusher connection error:', err);
        });

        // Listen for new messages
        channel.value.bind('App\\Events\\MessageSent', (data) => {
            logDebug('MessageSent event received:', data);
            handleNewMessage(data);
        });

        // Debug: Log all events on this channel
        if (debugMode.value) {
            channel.value.bind_global((event, data) => {
                logDebug(`Event ${event} received:`, data);
            });
        }
    } catch (error) {
        console.error('Error initializing Pusher:', error);
    }
};

const handleTyping = () => {
    // Clear previous timeout
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    isTyping.value = true;

    // Set a new timeout
    typingTimeout.value = setTimeout(() => {
        isTyping.value = false;
    }, 2000);
};

// Added missing methods used in template
const backToContactList = () => {
    selectedContact.value = null;
    messages.value = [];
};

const goBackToContacts = () => {
    showConversation.value = false;
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const isCurrentUser = (senderId) => {
    return senderId === props.user.id;
};

const autoResize = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
};

// Method to trigger file input click
const openFileInput = () => {
    // Make sure the DOM element exists before trying to click it
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

// Window resize handler for mobile view
const handleResize = () => {
    isMobileView.value = window.innerWidth < 768;
};

// Lifecycle hooks
onMounted(() => {
    logDebug('Component mounted, initializing...');
    fetchContacts();
    fetchUnreadCount();
    initializePusher();

    // Add window resize listener
    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check
});

onBeforeUnmount(() => {
    logDebug('Component unmounting, cleaning up...');

    // Clean up Pusher subscription
    if (channel.value) {
        channel.value.unbind_all();
        if (pusher.value) {
            pusher.value.unsubscribe(`chat.${props.user.id}`);
        }
    }

    if (pusher.value) {
        pusher.value.disconnect();
    }

    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    // Remove window resize listener
    window.removeEventListener('resize', handleResize);
});

// Watch for changes
watch(selectedContact, (newContact) => {
    if (newContact) {
        fetchMessages(newContact.id);
    }
});

// Watch for changes in messages array length to scroll to bottom
watch(
    () => messages.value.length,
    () => {
        nextTick(() => {
            scrollToBottom();
        });
    },
);
</script>

<template>
    <div class="min-h-screen bg-amber-50">
        <!-- User Navbar -->
        <AppNavBarUser :breadcrumbs="breadcrumbs" />

        <!-- Chat Container -->
        <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="flex h-[calc(100vh-12rem)]">
                    <!-- Contacts List -->
                    <div class="flex w-full flex-col border-r border-amber-200 md:w-1/3" :class="{ hidden: isMobileView && showConversation }">
                        <div class="border-b border-amber-200 bg-amber-50 p-4">
                            <h2 class="text-xl font-bold text-amber-900">Messages</h2>
                            <div class="relative mt-3">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search contacts..."
                                    class="border-amber-300 pl-10 focus:border-amber-500 focus:ring-amber-500"
                                />
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto">
                            <div
                                v-for="contact in filteredContacts"
                                :key="contact.id"
                                class="cursor-pointer border-b border-amber-100 p-4 transition-colors hover:bg-amber-50"
                                :class="{ 'bg-amber-50': selectedContact && selectedContact.id === contact.id }"
                                @click="selectContact(contact)"
                            >
                                <div class="flex items-center">
                                    <div class="relative">
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage :src="contact.profile_photo" :alt="contact.name" />
                                            <AvatarFallback class="bg-amber-100 text-amber-800">
                                                {{ getInitials(contact.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span
                                            v-if="contact.is_online"
                                            class="absolute right-0 bottom-0 h-3 w-3 rounded-full bg-green-500 ring-2 ring-white"
                                        ></span>
                                    </div>

                                    <div class="ml-4 flex-1">
                                        <div class="flex items-start justify-between">
                                            <h3 class="truncate font-medium text-amber-900">{{ contact.name }}</h3>
                                            <span class="text-xs text-amber-600">
                                                {{ formatLastMessageTime(contact.last_message_time) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between">
                                            <p class="max-w-[180px] truncate text-sm text-amber-600">
                                                {{ contact.last_message }}
                                            </p>
                                            <div
                                                v-if="contact.unread_messages_count > 0"
                                                class="ml-2 flex-shrink-0 rounded-full bg-amber-600 px-1.5 py-0.5 text-xs font-medium text-white"
                                            >
                                                {{ contact.unread_messages_count }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="filteredContacts.length === 0" class="p-8 text-center text-amber-600">No contacts found</div>
                        </div>
                    </div>

                    <!-- Conversation Area -->
                    <div class="flex w-full flex-col md:w-2/3" :class="{ hidden: isMobileView && !showConversation }">
                        <!-- Conversation Header -->
                        <div v-if="selectedContact" class="flex items-center border-b border-amber-200 bg-amber-50 p-4">
                            <button v-if="isMobileView" @click="goBackToContacts" class="mr-2 rounded-full p-1 hover:bg-amber-100">
                                <ArrowLeft class="h-5 w-5 text-amber-600" />
                            </button>

                            <Avatar class="h-10 w-10">
                                <AvatarImage :src="selectedContact.profile_photo" :alt="selectedContact.name" />
                                <AvatarFallback class="bg-amber-100 text-amber-800">
                                    {{ getInitials(selectedContact.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="ml-3 flex-1">
                                <h3 class="font-medium text-amber-900">{{ selectedContact.name }}</h3>
                                <p class="text-xs text-amber-600">
                                    {{ selectedContact.is_online ? 'Online' : 'Offline' }}
                                </p>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!selectedContact" class="flex flex-1 flex-col items-center justify-center bg-amber-50/50 p-8 text-center">
                            <div class="mb-4 rounded-full bg-amber-100 p-6">
                                <Send class="h-10 w-10 text-amber-700" />
                            </div>
                            <h3 class="mb-2 text-xl font-medium text-amber-900">Your Messages</h3>
                            <p class="max-w-sm text-amber-700">Select a contact to start messaging. You can send text, images, and videos.</p>
                        </div>

                        <!-- Messages -->
                        <div
                            v-else
                            ref="messagesContainerRef"
                            class="flex-1 space-y-4 overflow-y-auto bg-amber-50/30 bg-[url('/images/parchment-bg.png')] p-4"
                        >
                            <div v-if="isLoading" class="flex justify-center py-4">
                                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-amber-600"></div>
                            </div>

                            <div v-else-if="messages.length === 0" class="flex justify-center py-8">
                                <div class="text-center">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 p-4">
                                        <Send class="h-8 w-8 text-amber-600" />
                                    </div>
                                    <p class="text-amber-700">No messages yet. Start the conversation!</p>
                                </div>
                            </div>

                            <template v-else>
                                <!-- Messages grouped by date -->
                                <div v-for="(messagesGroup, date) in sortedMessages" :key="date" class="space-y-4">
                                    <!-- Date separator -->
                                    <div class="flex justify-center">
                                        <div class="rounded-full bg-amber-100 px-3 py-1 text-xs text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            {{ formatMessageDate(date) }}
                                        </div>
                                    </div>

                                    <!-- Messages for this date -->
                                    <div v-for="message in messagesGroup" :key="message.id" class="space-y-2">
                                        <!-- Message bubble -->
                                        <div class="flex" :class="message.sender_id === props.user.id ? 'justify-end' : 'justify-start'">
                                            <div class="flex max-w-[80%] items-end gap-2">
                                                <!-- Sender avatar (only for received messages) -->
                                                <div
                                                    v-if="message.sender_id !== props.user.id"
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-amber-200 dark:bg-amber-700"
                                                >
                                                    <img
                                                        v-if="message.sender?.profile_photo"
                                                        :src="`/${message.sender.profile_photo}`"
                                                        :alt="message.sender.name"
                                                        class="h-full w-full object-cover"
                                                    />
                                                    <User v-else class="h-4 w-4 text-amber-700 dark:text-amber-300" />
                                                </div>

                                                <!-- Message content -->
                                                <div
                                                    class="rounded-lg p-3 break-words"
                                                    :class="
                                                        message.sender_id === props.user.id
                                                            ? 'rounded-br-none bg-amber-600 text-white'
                                                            : 'rounded-bl-none bg-amber-100 text-amber-900 dark:bg-amber-800 dark:text-amber-100'
                                                    "
                                                >
                                                    <!-- Text message -->
                                                    <p v-if="message.message" class="whitespace-pre-line">{{ message.message }}</p>

                                                    <!-- Image attachment -->
                                                    <div v-if="message.image_path && message.image_path !== 'pending'" class="mt-2">
                                                        <img
                                                            :src="`/storage/${message.image_path}`"
                                                            alt="Image attachment"
                                                            class="max-h-60 max-w-full cursor-pointer rounded-lg object-contain"
                                                            @click="() => window.open(`/storage/${message.image_path}`, '_blank')"
                                                        />
                                                    </div>

                                                    <!-- Pending image -->
                                                    <div v-else-if="message.image_path === 'pending'" class="mt-2">
                                                        <div
                                                            class="flex h-40 w-full items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-700"
                                                        >
                                                            <div class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-white"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Video attachment -->
                                                    <div v-if="message.video_path && message.video_path !== 'pending'" class="mt-2">
                                                        <video controls class="max-h-60 max-w-full rounded-lg">
                                                            <source :src="`/storage/${message.video_path}`" type="video/mp4" />
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    </div>

                                                    <!-- Pending video -->
                                                    <div v-else-if="message.video_path === 'pending'" class="mt-2">
                                                        <div
                                                            class="flex h-40 w-full items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-700"
                                                        >
                                                            <div class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-white"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Message time -->
                                                    <div class="mt-1 flex items-center justify-end gap-1">
                                                        <span class="text-xs opacity-70">
                                                            {{ formatMessageTime(message.created_at) }}
                                                        </span>

                                                        <!-- Read status (only for sent messages) -->
                                                        <span v-if="message.sender_id === props.user.id">
                                                            <CheckCheck v-if="!message.unread" class="h-3 w-3 opacity-70" />
                                                            <Check v-else class="h-3 w-3 opacity-70" />
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Message Input -->
                        <div v-if="selectedContact" class="border-t border-amber-200 bg-amber-50 p-4">
                            <!-- File Preview -->
                            <div v-if="filePreview" class="relative mb-3">
                                <div class="relative rounded-lg border border-amber-200 bg-amber-50 p-2">
                                    <button
                                        @click="removeSelectedFile"
                                        class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 hover:bg-red-200"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>

                                    <div v-if="fileType === 'image'" class="flex items-center">
                                        <img :src="filePreview.url" class="h-16 w-auto rounded" alt="Selected image" />
                                        <span class="ml-2 text-sm text-amber-700">{{ selectedFile.name }}</span>
                                    </div>

                                    <div v-else-if="fileType === 'video'" class="flex items-center">
                                        <div class="flex h-16 w-24 items-center justify-center rounded bg-amber-100">
                                            <Video class="h-8 w-8 text-amber-600" />
                                        </div>
                                        <span class="ml-2 text-sm text-amber-700">{{ selectedFile.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-end space-x-2">
                                <div class="relative flex-1">
                                    <textarea
                                        v-model="messageText"
                                        placeholder="Type a message..."
                                        class="max-h-[120px] min-h-[40px] w-full resize-none rounded-md border border-amber-300 bg-white px-3 py-2 pr-10 text-sm text-amber-900 ring-offset-white placeholder:text-amber-400 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        rows="1"
                                        @keydown.enter.prevent="sendMessage"
                                        @input="autoResize"
                                    />

                                    <div class="absolute right-2 bottom-2">
                                        <input type="file" ref="fileInputRef" class="hidden" accept="image/*,video/*" @change="handleFileSelect" />
                                        <button @click="openFileInput" class="rounded-full p-1 hover:bg-amber-100" title="Attach file">
                                            <Paperclip class="h-5 w-5 text-amber-600" />
                                        </button>
                                    </div>
                                </div>

                                <Button
                                    @click="sendMessage"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-700 text-white hover:bg-amber-800"
                                >
                                    <Send class="h-5 w-5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
