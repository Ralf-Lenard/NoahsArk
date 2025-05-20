<?php

namespace App\Events;

use App\Models\AdoptionAppointment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdoptionAppointmentScheduled implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $appointment;
    public $userId;
    public $message;
    public $type;
    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(AdoptionAppointment $appointment)
    {
        $this->appointment = $appointment->load('adoptionRequest.user', 'adoptionRequest.animal');
        $this->userId = $this->appointment->adoptionRequest->user_id;
        $this->type = 'AdoptionAppointmentScheduled';
    
        $animalName = optional($this->appointment->adoptionRequest->animal)->name ?? 'an animal';
    
        // Format the date and time using Carbon
        $formattedDate = Carbon::parse($this->appointment->appointment_date)->format('F j, Y'); // e.g., April 27, 2025
        $formattedTime = Carbon::parse($this->appointment->appointment_time)->format('g:i A');  // e.g., 3:00 PM
    
        $this->message = "Your virtual appointment for adopting {$animalName} has been scheduled on {$formattedDate} at {$formattedTime}.";
    
        $imagePath = optional($this->appointment->adoptionRequest->animal)->image;
    
        $this->notification = Notification::create([
            'user_id' => $this->userId,
            'message' => $this->message,
            'image_path' => $imagePath,
            'type' => $this->type,
            'appointment_date' => $this->appointment->appointment_date,
            'appointment_time' => $this->appointment->appointment_time,
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'adoption.appointment.scheduled';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->message,
            'userId' => $this->userId,
            'type' => $this->type,
            'appointment_date' => $this->appointment->appointment_date,
            'appointment_time' => $this->appointment->appointment_time,
            'image_path' => $this->notification->image_path,
        ];
    }
}
