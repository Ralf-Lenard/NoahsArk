<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
        \Log::info('Message timestamp:', ['timestamp' => $this->message->created_at]);
    }
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->receiver_id);
    }

    public function broadcastWith()
    {
        return [
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
            'timestamp' => $this->message->created_at->toIso8601String(), // Ensure this is properly set
            'image_path' => $this->message->image_path,
            'video_path' => $this->message->video_path,
        ];
    }

}
