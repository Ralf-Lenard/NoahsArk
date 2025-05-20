<?php

namespace App\Events;

use App\Models\AdoptionRequest;
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class AdoptionStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $adoptionRequest;
    public $message;
    public $userId;
    public $type;
    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(AdoptionRequest $adoptionRequest)
    {
        // Eager load animalProfile relationship to ensure it's available
        $this->adoptionRequest = $adoptionRequest->load('animal');

        $this->userId = $this->adoptionRequest->user_id;
        $this->type = 'AdoptionStatusUpdated';

        $animalName = optional($this->adoptionRequest->animal)->name ?? 'an animal';
        $status = $this->adoptionRequest->status;

        // Build the message based on status
        $this->message = match ($status) {
            'approved' => "Your adoption request for {$animalName} has been approved.",
            'rejected' => "Your adoption request for {$animalName} was rejected. Reason: {$this->adoptionRequest->rejection_reason}",
            default => "Your adoption request for {$animalName} is now pending.",
        };

        // Get the image path from animal profile (or null if no profile/image)
        $imagePath = optional($this->adoptionRequest->animal)->image;

        // Save the notification to the database
        $this->notification = Notification::create([
            'user_id' => $this->userId,
            'message' => $this->message,
            'image_path' => $imagePath,
            'type' => $this->type,
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'adoption.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->message,
            'userId' => $this->userId,
            'animal_name' => optional($this->adoptionRequest->animal)->name,
            'animal_image' => optional($this->adoptionRequest->animal)->image,
            'status' => $this->adoptionRequest->status,
            'type' => $this->type,
        ];
    }
}
