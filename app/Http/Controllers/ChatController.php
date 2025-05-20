<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


use Inertia\Inertia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


use App\Events\MessageSent;
use App\Events\MessageRead;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();
    
        if ($user->usertype === 'user') {
            return Inertia::render('Users/Chats', [
                'user' => $user,
            ]);
        } elseif ($user->usertype === 'staff') {
            return Inertia::render('Staff/Chats', [
                'user' => $user,
            ]);
        } elseif ($user->usertype === 'admin') {
            return Inertia::render('Admin/Chats', [
                'user' => $user,
            ]);
        } else {
            abort(403, 'Unauthorized');
        }
    }

    public function getMessages($receiverId)
    {
        $senderId = Auth::id();

        // Fetch messages between sender and receiver
        $messages = Message::with(['sender', 'receiver']) // Eager load sender and receiver
            ->where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['message' => 'No messages found'], 404);
        }

        // Mark all unread messages from the receiver as read
        Message::where('sender_id', $receiverId)
            ->where('receiver_id', $senderId)
            ->where('unread', true)
            ->update(['unread' => false]);

        // Add formatted timestamp and profile picture URLs
        $messages = $messages->map(function ($message) {
            $message->timestamp = $message->created_at->toIso8601String();

            // Add sender's profile picture URL
            $message->sender->profile_picture = $message->sender->profile_picture
                ? Storage::url($message->sender->profile_picture)
                : null;

            // Add receiver's profile picture URL
            $message->receiver->profile_picture = $message->receiver->profile_picture
                ? Storage::url($message->receiver->profile_picture)
                : null;

            return $message;
        });

        return response()->json($messages);
    }



    public function sendMessage(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'message' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,avi,mkv,mov|max:30720',
            ]);
    
            // Initialize file paths as null
            $imagePath = null;
            $videoPath = null;
    
            // Check if a file has been uploaded
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
    
                // Handle image files
                if (in_array($fileExtension, ['jpeg', 'png', 'jpg', 'gif'])) {
                    // Store image and get the relative path
                    $imagePath = $file->store('messages/images', 'public');
                }
                // Handle video files
                elseif (in_array($fileExtension, ['mp4', 'avi', 'mkv', 'mov'])) {
                    // Store video and get the relative path
                    $videoPath = $file->store('messages/videos', 'public');
                }
            }
    
            // Create a new message
            $message = Message::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $request->receiver_id,
                'message' => $validated['message'] ?? null,
                'image_path' => $imagePath,
                'video_path' => $videoPath,
                'unread' => true,
                'seen' => false,
                'created_at' => now(),
            ]);
    
            // Get receiver details for the broadcast
            $receiver = User::find($request->receiver_id);
    
            // Broadcast the message
            broadcast(new MessageSent($message));
    
            // Return the response with message and file URLs
            return response()->json([
                'message' => 'Message sent successfully!',
                'data' => $message,
                'receiver_name' => $receiver->name ?? '',
                'receiver_last_name' => $receiver->last_name ?? '',
                'receiver_profile_photo' => $receiver && $receiver->profile_photo
                    ? asset('storage/profile_photos/' . $receiver->profile_photo)
                    : null,
                'image_path' => $imagePath ? asset('storage/' . $imagePath) : null, // Add full URL for image
                'video_path' => $videoPath ? asset('storage/' . $videoPath) : null, // Add full URL for video
            ]);
        } catch (\Exception $e) {
            \Log::error('Message sending error: ' . $e->getMessage());
            \Log::error('In file: ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['error' => 'Something went wrong, please try again'], 500);
        }
    }
    
    
    public function getContacts()
    {
        $loggedInUserId = Auth::id();
        $user = auth()->user();
    
        // Determine which contacts to fetch
        if (in_array($user->usertype, ['admin', 'staff'])) {
            // Admin and staff can see all users except themselves
            $contacts = User::where('id', '!=', $loggedInUserId)->get();
        } else {
            // Regular users can only see admin and staff
            $contacts = User::whereIn('usertype', ['admin', 'staff'])
                ->where('id', '!=', $loggedInUserId)
                ->get();
        }
    
        // Map the contact list with extra info
        $contacts = $contacts->map(function ($contact) use ($loggedInUserId) {
            $lastMessage = Message::where(function ($query) use ($loggedInUserId, $contact) {
                $query->where('sender_id', $loggedInUserId)
                    ->where('receiver_id', $contact->id);
            })
                ->orWhere(function ($query) use ($loggedInUserId, $contact) {
                    $query->where('sender_id', $contact->id)
                        ->where('receiver_id', $loggedInUserId);
                })
                ->orderBy('created_at', 'desc')
                ->first();
    
            $unreadMessagesCount = Message::where('sender_id', $contact->id)
                ->where('receiver_id', $loggedInUserId)
                ->where('unread', true)
                ->count();
    
            // Safe check for is_online column
            $isOnline = false;
            if ($contact->is_online !== null) {
                $lastSeen = Carbon::parse($contact->is_online);
                $isOnline = $lastSeen->gt(Carbon::now()->subMinutes(2));
            }
    
            return [
                'id' => $contact->id,
                'name' => $contact->name ?? 'Unknown',
                'last_name' => $contact->last_name ?? '',
                'profile_photo' => $contact->profile_photo,
                'last_message' => $lastMessage ? $lastMessage->message : 'No messages yet',
                'last_message_time' => $lastMessage ? $lastMessage->created_at->toDateTimeString() : null,
                'unread_messages_count' => $unreadMessagesCount,
                'is_online' => $isOnline,
            ];
        });
    
        // Sort by recent conversation
        $contacts = $contacts->sortByDesc('last_message_time')->values();
    
        return response()->json(['contacts' => $contacts]);
    }

    // MessageController.php
    public function markAsRead($contactId)
    {
        $user = auth()->user();

        // Mark messages as read
        $messages = Message::where('receiver_id', $user->id)
            ->where('sender_id', $contactId)
            ->where('unread', true)
            ->update(['unread' => false]);

        return response()->json([
            'message' => 'Messages marked as read successfully.',
            'data' => $messages,
        ]);
    }


    public function getUnreadCount()
    {
        $unreadCount = Message::where('receiver_id', Auth::id())
            ->where('unread', true)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
