<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSubscription extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_PAUSED => 'En pause',
        self::STATUS_CANCELLED => 'Annule',
        self::STATUS_EXPIRED => 'Expire',
    ];

    protected $fillable = [
        'patient_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'consultations_used',
        'payment_method',
        'payment_status',
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'consultations_used' => 'integer',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getRemainingConsultationsAttribute(): int
    {
        return max(0, $this->plan->consultations_per_period - $this->consultations_used);
    }

    public function getRemainingDaysAttribute(): int
    {
        if ($this->current_period_end->isPast()) {
            return 0;
        }

        return $this->current_period_end->diffInDays(now());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function canUseConsultation(): bool
    {
        return $this->isActive()
            && $this->remaining_consultations > 0
            && !$this->current_period_end->isPast();
    }

    public function useConsultation(): bool
    {
        if (!$this->canUseConsultation()) {
            return false;
        }

        $this->increment('consultations_used');

        return true;
    }

    public function renew(): bool
    {
        if (!$this->auto_renew) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return false;
        }

        $periodDays = $this->plan->getPeriodDays();

        $this->update([
            'current_period_start' => Carbon::today(),
            'current_period_end' => Carbon::today()->addDays($periodDays),
            'consultations_used' => 0,
            'status' => self::STATUS_ACTIVE,
            'payment_status' => 'pending',
        ]);

        return true;
    }

    public function pause(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->update(['status' => self::STATUS_PAUSED]);

        return true;
    }

    public function resume(): bool
    {
        if ($this->status !== self::STATUS_PAUSED) {
            return false;
        }

        $this->update(['status' => self::STATUS_ACTIVE]);

        return true;
    }

    public function cancel(): bool
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'plan' => $this->plan->toApiArray(),
            'current_period_start' => $this->current_period_start->toDateString(),
            'current_period_end' => $this->current_period_end->toDateString(),
            'consultations_used' => $this->consultations_used,
            'remaining_consultations' => $this->remaining_consultations,
            'remaining_days' => $this->remaining_days,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'auto_renew' => $this->auto_renew,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
