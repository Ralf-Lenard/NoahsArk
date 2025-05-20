<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdoptionAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adoption_request_id',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
    ];

    public function adoptionRequest()
    {
        return $this->belongsTo(AdoptionRequest::class, 'adoption_request_id');
    }
    

    
}
