<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { format, isToday, isYesterday } from 'date-fns';
import { Anchor, Check, CheckCheck, ChevronLeft, Hammer, MessageSquare, Paperclip, PawPrint, Search, Send, User, Users, X } from 'lucide-vue-next';
import Pusher from 'pusher-js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

// Computed properties
const filteredContacts = computed(() => {
    if (!searchQuery.value) return contacts.value;

    const query = searchQuery.value.toLowerCase();
    return contacts.value.filter((contact) => contact.name.toLowerCase().includes(query) || contact.last_name.toLowerCase().includes(query));
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
    await fetchMessages(contact.id);

    // Reset message input and file
    newMessage.value = '';
    selectedFile.value = null;
    filePreview.value = null;
};
const sendMessage = async () => {
    // Don't send if there's no message or file
    if ((!newMessage.value || newMessage.value.trim() === '') && !selectedFile.value) return;

    try {
        // Create a formData object to send to the server
        const formData = new FormData();
        formData.append('receiver_id', selectedContact.value.id);

        // Add the message to formData if it's not empty
        if (newMessage.value.trim() !== '') {
            formData.append('message', newMessage.value);
        }

        // Add the selected file (image/video) to formData
        if (selectedFile.value) {
            formData.append('file', selectedFile.value);
        }

        // Create a temporary message for instant feedback
        const tempId = `temp-${Date.now()}`;
        const tempMessage = {
            id: tempId,
            sender_id: props.user.id,
            receiver_id: selectedContact.value.id,
            message: newMessage.value.trim() !== '' ? newMessage.value : null,
            image_path: selectedFile.value && selectedFile.value.type.startsWith('image/') ? 'pending' : null,
            video_path: selectedFile.value && selectedFile.value.type.startsWith('video/') ? 'pending' : null,
            created_at: new Date().toISOString(),
            unread: true,
            sender: {
                id: props.user.id,
                name: props.user.name,
                profile_picture: props.user.profile_picture,
            },
        };

        // Add the temporary message to the local messages array for instant feedback
        messages.value.push(tempMessage);
        await nextTick();
        scrollToBottom();

        // Reset input fields after sending the message
        newMessage.value = '';
        selectedFile.value = null;
        filePreview.value = null;

        // Send the form data to the server (the actual message sending process)
        const response = await axios.post('/chats/send', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        logDebug('Message sent response:', response.data);

        // If the message is successfully sent, update the temporary message
        if (response.data && response.data.data) {
            const { image_path, video_path } = response.data.data;

            // Update the message paths with the real URLs (if any)
            tempMessage.image_path = image_path || null;
            tempMessage.video_path = video_path || null;

            // Replace the temp message with the real message
            messages.value = messages.value.map((msg) => (msg.id === tempId ? { ...msg, ...tempMessage } : msg));
        }
    } catch (error) {
        console.error('Error sending message:', error);

        // In case of error, remove the temp message
        messages.value = messages.value.filter((m) => m.id.toString().startsWith('temp-'));

        // Optionally, notify the user that the message failed
        alert('Failed to send message, please try again.');
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
                    profile_picture: selectedContact.value.profile_photo,
                },
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

const backToContactList = () => {
    selectedContact.value = null;
    messages.value = [];
};

// Method to trigger file input click
const openFileInput = () => {
    // Make sure the DOM element exists before trying to click it
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

// Lifecycle hooks
onMounted(() => {
    logDebug('Component mounted, initializing...');
    fetchContacts();
    fetchUnreadCount();
    initializePusher();
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
    <AppSidebar>
        <Head title="Admin Chat" />

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
                                <MessageSquare class="h-10 w-10 text-amber-800" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-amber-100">Admin Chat</h1>
                                <p class="mt-2 max-w-2xl text-amber-200">Communicate with staff and users to provide support and answer questions.</p>
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

                <!-- Chat Interface -->
                <div
                    class="h-[calc(100vh-16rem)] overflow-hidden rounded-lg border border-amber-200 bg-white shadow-md dark:border-amber-800 dark:bg-amber-900"
                >
                    <div class="flex h-full">
                        <!-- Contacts List (hidden on mobile when a chat is selected) -->
                        <div
                            :class="[
                                'flex w-full flex-col border-r border-amber-200 md:w-1/3 lg:w-1/4 dark:border-amber-800',
                                { 'hidden md:flex': selectedContact },
                            ]"
                        >
                            <!-- Search Bar -->
                            <div class="border-b border-amber-200 p-4 dark:border-amber-800">
                                <div class="relative">
                                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-amber-500" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search contacts..."
                                        class="border-amber-200 pl-10 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700"
                                    />
                                </div>
                            </div>

                            <!-- Contacts -->
                            <div class="flex-1 overflow-y-auto">
                                <div v-if="isLoading" class="flex h-full items-center justify-center">
                                    <div class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-amber-700"></div>
                                </div>

                                <div
                                    v-else-if="filteredContacts.length === 0"
                                    class="flex h-full flex-col items-center justify-center text-amber-700 dark:text-amber-300"
                                >
                                    <Users class="mb-4 h-16 w-16 opacity-50" />
                                    <p class="text-lg font-medium">No contacts found</p>
                                    <p class="mt-2 max-w-xs text-center text-sm text-amber-600 dark:text-amber-400">
                                        {{ searchQuery ? 'Try a different search term' : 'You have no contacts yet' }}
                                    </p>
                                </div>

                                <div v-else>
                                    <button
                                        v-for="contact in filteredContacts"
                                        :key="contact.id"
                                        @click="selectContact(contact)"
                                        class="flex w-full items-start gap-3 border-b border-amber-100 p-4 transition-colors hover:bg-amber-50 dark:border-amber-800 dark:hover:bg-amber-800"
                                        :class="{ 'bg-amber-50 dark:bg-amber-800': selectedContact && selectedContact.id === contact.id }"
                                    >
                                        <!-- Profile Picture or Placeholder -->
                                        <div class="relative">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border-2 border-amber-300 bg-amber-200 dark:border-amber-600 dark:bg-amber-700"
                                            >
                                                <img
                                                    v-if="contact.profile_photo"
                                                    :src="contact.profile_photo"
                                                    :alt="contact.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <User v-else class="h-6 w-6 text-amber-700 dark:text-amber-300" />
                                            </div>

                                            <!-- Online indicator -->
                                            <div
                                                v-if="contact.is_online"
                                                class="absolute right-0 bottom-0 h-3 w-3 rounded-full border-2 border-white bg-green-500 dark:border-amber-900"
                                            ></div>
                                        </div>

                                        <!-- Contact Info -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between">
                                                <h3 class="truncate font-medium text-amber-900 dark:text-amber-100">
                                                    {{ contact.name }} {{ contact.last_name }}
                                                </h3>
                                                <span class="text-xs whitespace-nowrap text-amber-600 dark:text-amber-400">
                                                    {{ formatLastMessageTime(contact.last_message_time) }}
                                                </span>
                                            </div>

                                            <p class="mt-1 truncate text-sm text-amber-700 dark:text-amber-300">
                                                {{ contact.last_message || 'No messages yet' }}
                                            </p>

                                            <!-- Unread indicator -->
                                            <div class="mt-1 flex items-center justify-between">
                                                <span
                                                    v-if="contact.usertype"
                                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-800 dark:text-amber-200"
                                                >
                                                    {{ contact.usertype === 'admin' ? 'Admin' : contact.usertype === 'staff' ? 'Staff' : 'User' }}
                                                </span>

                                                <div
                                                    v-if="contact.unread_messages_count > 0"
                                                    class="flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-600 px-1 text-xs text-white"
                                                >
                                                    {{ contact.unread_messages_count }}
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div :class="['flex flex-1 flex-col', { 'hidden md:flex': !selectedContact }]">
                            <!-- Chat Header -->
                            <div v-if="selectedContact" class="flex items-center justify-between border-b border-amber-200 p-4 dark:border-amber-800">
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="backToContactList"
                                        class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-amber-100 md:hidden dark:hover:bg-amber-800"
                                    >
                                        <ChevronLeft class="h-5 w-5 text-amber-700 dark:text-amber-300" />
                                    </button>

                                    <div class="relative">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-amber-300 bg-amber-200 dark:border-amber-600 dark:bg-amber-700"
                                        >
                                            <img
                                                v-if="selectedContact.profile_photo"
                                                :src="selectedContact.profile_photo"
                                                :alt="selectedContact.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <User v-else class="h-5 w-5 text-amber-700 dark:text-amber-300" />
                                        </div>

                                        <!-- Online indicator -->
                                        <div
                                            v-if="selectedContact.is_online"
                                            class="absolute right-0 bottom-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-green-500 dark:border-amber-900"
                                        ></div>
                                    </div>

                                    <div>
                                        <h3 class="font-medium text-amber-900 dark:text-amber-100">
                                            {{ selectedContact.name }} {{ selectedContact.last_name }}
                                        </h3>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">
                                            {{ selectedContact.is_online ? 'Online' : 'Offline' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="!selectedContact"
                                class="flex flex-1 flex-col items-center justify-center p-6 text-amber-700 dark:text-amber-300"
                            >
                                <div class="mb-6 rounded-full bg-amber-100 p-6 dark:bg-amber-800">
                                    <MessageSquare class="h-16 w-16" />
                                </div>
                                <h3 class="mb-2 text-xl font-medium">Select a conversation</h3>
                                <p class="max-w-md text-center text-amber-600 dark:text-amber-400">
                                    Choose a contact from the list to start chatting or continue a conversation.
                                </p>
                            </div>

                            <!-- Messages Container -->
                            <div v-else ref="messagesContainerRef" class="flex-1 space-y-6 overflow-y-auto p-4">
                                <div v-if="isLoadingMessages" class="flex h-full items-center justify-center">
                                    <div class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-amber-700"></div>
                                </div>

                                <div
                                    v-else-if="messages.length === 0"
                                    class="flex h-full flex-col items-center justify-center text-amber-700 dark:text-amber-300"
                                >
                                    <PawPrint class="mb-4 h-16 w-16 opacity-50" />
                                    <p class="text-lg font-medium">No messages yet</p>
                                    <p class="mt-2 max-w-xs text-center text-sm text-amber-600 dark:text-amber-400">
                                        Start the conversation by sending a message below.
                                    </p>
                                </div>

                                <template v-else>
                                    <!-- Messages grouped by date -->
                                    <div v-for="(messagesGroup, date) in sortedMessages" :key="date" class="space-y-4">
                                        <!-- Date separator -->
                                        <div class="flex justify-center">
                                            <div
                                                class="rounded-full bg-amber-100 px-3 py-1 text-xs text-amber-800 dark:bg-amber-800 dark:text-amber-200"
                                            >
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
                                                            v-if="message.sender?.profile_picture"
                                                            :src="message.sender.profile_picture"
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
                                                                <div
                                                                    class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-white"
                                                                ></div>
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
                                                                <div
                                                                    class="h-8 w-8 animate-spin rounded-full border-t-2 border-b-2 border-white"
                                                                ></div>
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

                                <!-- Typing indicator -->
                                <div v-if="isTyping" class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                                    <div class="flex space-x-1">
                                        <div class="h-2 w-2 animate-bounce rounded-full bg-amber-500"></div>
                                        <div class="h-2 w-2 animate-bounce rounded-full bg-amber-500" style="animation-delay: 0.2s"></div>
                                        <div class="h-2 w-2 animate-bounce rounded-full bg-amber-500" style="animation-delay: 0.4s"></div>
                                    </div>
                                    <span class="text-sm">{{ selectedContact?.name }} is typing...</span>
                                </div>
                            </div>

                            <!-- Message Input -->
                            <div v-if="selectedContact" class="border-t border-amber-200 p-4 dark:border-amber-800">
                                <!-- File preview -->
                                <div v-if="filePreview" class="mb-3 rounded-lg bg-amber-50 p-3 dark:bg-amber-800">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200">Attachment</h4>
                                        <button
                                            @click="removeSelectedFile"
                                            class="text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="mt-2">
                                        <!-- Image preview -->
                                        <div v-if="filePreview.type === 'image'" class="relative">
                                            <img :src="filePreview.url" alt="Image preview" class="max-h-40 rounded-lg object-contain" />
                                        </div>

                                        <!-- Video preview -->
                                        <div v-else-if="filePreview.type === 'video'" class="relative">
                                            <video controls class="max-h-40 rounded-lg">
                                                <source :src="filePreview.url" type="video/mp4" />
                                                Your browser does not support the video tag.
                                            </video>
                                        </div>

                                        <!-- File name -->
                                        <div v-else class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                            <Paperclip class="h-4 w-4" />
                                            <span class="truncate text-sm">{{ filePreview.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-end gap-2">
                                    <!-- Emoji button -->
                                    <!-- <button 
                                        class="h-10 w-10 flex items-center justify-center rounded-full hover:bg-amber-100 dark:hover:bg-amber-800 text-amber-700 dark:text-amber-300"
                                        @click="showEmojiPicker = !showEmojiPicker"
                                    >
                                        <Smile class="h-5 w-5" />
                                    </button> -->

                                    <!-- File input -->
                                    <input type="file" ref="fileInputRef" @change="handleFileSelect" accept="image/*,video/*" class="hidden" />

                                    <button
                                        @click="openFileInput"
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-amber-700 hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-800"
                                    >
                                        <Paperclip class="h-5 w-5" />
                                    </button>

                                    <!-- Message input -->
                                    <div class="relative flex-1">
                                        <textarea
                                            v-model="newMessage"
                                            @input="handleTyping"
                                            @keydown.enter.prevent="sendMessage"
                                            placeholder="Type a message..."
                                            class="max-h-32 min-h-[40px] w-full resize-none rounded-lg border-amber-200 py-2 pr-10 focus:border-amber-500 focus:ring-amber-500 dark:border-amber-700 dark:bg-amber-900 dark:text-white"
                                            rows="1"
                                        ></textarea>
                                    </div>

                                    <!-- Send button -->
                                    <button
                                        @click="sendMessage"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-600 text-white hover:bg-amber-700"
                                        :disabled="(!newMessage || newMessage.trim() === '') && !selectedFile"
                                        :class="{ 'cursor-not-allowed opacity-50': (!newMessage || newMessage.trim() === '') && !selectedFile }"
                                    >
                                        <Send class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
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

/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Auto-resize textarea */
textarea {
    overflow-y: auto;
}
</style>
