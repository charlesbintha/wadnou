<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRequest extends Model
{
    use HasFactory;

    // Prix par km en FCFA
    public const PRICE_PER_KM = 300;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'location_id',
        'distance_km',
        'price_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'status',
        'reason',
        'notes',
        'requested_at',
        'accepted_at',
        'rejected_at',
        'canceled_at',
        'closed_at',
        'sla_due_at',
        'sla_warning_sent_at',
        'sla_breach_sent_at',
        'navigation_started_at',
        'navigation_ended_at',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'price_amount' => 'integer',
        'paid_at' => 'datetime',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_warning_sent_at' => 'datetime',
        'sla_breach_sent_at' => 'datetime',
        'navigation_started_at' => 'datetime',
        'navigation_ended_at' => 'datetime',
    ];

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_amount ?? 0, 0, ',', ' ') . ' FCFA';
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class);
    }

    public function auditActions()
    {
        return $this->morphMany(AuditAction::class, 'target');
    }

    public function comments()
    {
        return $this->hasMany(ConsultationComment::class);
    }
}
