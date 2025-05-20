<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
        'breed',
        'species',
        'birthdate',
        'color',
        'gender',
        'description',
        'image',
        'medical_records',
        'is_adopted',
        'device_id',
        'traccar_id',
        'is_temporarily_adopted'
    ];

    // Relationships
    public function adoptionRequests()
    {
        return $this->hasMany(AdoptionRequest::class);
    }

    
}
