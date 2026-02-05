<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'consultation_request_id',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function consultationRequest()
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    public function itineraries()
    {
        return $this->hasMany(Itinerary::class);
    }
}
