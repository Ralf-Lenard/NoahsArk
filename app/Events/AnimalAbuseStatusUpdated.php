<?php

namespace App\Events;

use App\Models\AnimalAbuseReport;
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class AnimalAbuseStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $animalAbuseReport;
    public $message;
    public $userId;
    public $type;
    public $notification;

    public function __construct(AnimalAbuseReport $animalAbuseReport)
    {
        $this->animalAbuseReport = $animalAbuseReport;
        $this->userId = $animalAbuseReport->user_id;
        $this->type = 'AnimalAbuseStatusUpdated';

        $status = $animalAbuseReport->status;
        $reason = $animalAbuseReport->rejection_reason;

        $this->message = match ($status) {
            'approved' => "Your animal abuse report has been approved.",
            'rejected' => "Your animal abuse report was rejected. Reason: {$reason}",
            default => "Your animal abuse report is now pending.",
        };

        $photos = $animalAbuseReport->photos ?? [];
        $firstPhoto = $photos[0] ?? null;
        $this->imagePath = $firstPhoto ? $firstPhoto : 'images/default-abuse.png';

        $this->notification = Notification::create([
            'user_id' => $this->userId,
            'message' => $this->message,
            'image_path' => $this->imagePath,
            'type' => $this->type,
        ]);
    }


    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'animal.abuse.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->message,
            'userId' => $this->userId,
            'status' => $this->animalAbuseReport->status,
            'type' => $this->type,
            'image_path' => $this->notification->image_path,
        ];
    }
}
