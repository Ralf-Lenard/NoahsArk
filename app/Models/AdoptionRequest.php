<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdoptionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animal_profile_id',
        'question1',
        'question2',
        'question3',
        'valid_id',
        'selfie_with_id',
        'status',
        'rejection_reason',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function animal_profile()
    {
        return $this->belongsTo(AnimalProfile::class, 'animal_profile_id');
    }

    // AdoptionRequest.php (Model)

    public function adoptionAppointments()
{
    return $this->hasMany(AdoptionAppointment::class);  // Ensure this exists in the model
}


    public function appointment()
    {
        return $this->hasOne(AdoptionAppointment::class);
    }


public function animal()
{
    return $this->belongsTo(AnimalProfile::class, 'animal_profile_id');
}




}
