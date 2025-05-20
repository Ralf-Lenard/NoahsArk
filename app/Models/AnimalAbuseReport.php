<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalAbuseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'photos',
        'videos',
        'rejection_reason',
        'status'
    ];

    protected $casts = [
        'photos' => 'array',
        'videos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
