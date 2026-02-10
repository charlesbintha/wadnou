<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    public const PERIODICITY_WEEKLY = 'weekly';
    public const PERIODICITY_BIWEEKLY = 'biweekly';
    public const PERIODICITY_MONTHLY = 'monthly';
    public const PERIODICITY_QUARTERLY = 'quarterly';
    public const PERIODICITY_YEARLY = 'yearly';

    public const PERIODICITY_DAYS = [
        self::PERIODICITY_WEEKLY => 7,
        self::PERIODICITY_BIWEEKLY => 14,
        self::PERIODICITY_MONTHLY => 30,
        self::PERIODICITY_QUARTERLY => 90,
        self::PERIODICITY_YEARLY => 365,
    ];

    public const PERIODICITY_LABELS = [
        self::PERIODICITY_WEEKLY => 'Hebdomadaire',
        self::PERIODICITY_BIWEEKLY => 'Bi-hebdomadaire',
        self::PERIODICITY_MONTHLY => 'Mensuel',
        self::PERIODICITY_QUARTERLY => 'Trimestriel',
        self::PERIODICITY_YEARLY => 'Annuel',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'periodicity',
        'consultations_per_period',
        'price',
        'discount_percent',
        'includes_home_visits',
        'includes_teleconsultation',
        'priority_booking',
        'display_order',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'consultations_per_period' => 'integer',
        'price' => 'integer',
        'discount_percent' => 'integer',
        'includes_home_visits' => 'boolean',
        'includes_teleconsultation' => 'boolean',
        'priority_booking' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PatientSubscription::class, 'plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    public function getPeriodDays(): int
    {
        return self::PERIODICITY_DAYS[$this->periodicity] ?? 30;
    }

    public function getPeriodicityLabelAttribute(): string
    {
        return self::PERIODICITY_LABELS[$this->periodicity] ?? $this->periodicity;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' FCFA';
    }

    public function getPricePerConsultationAttribute(): int
    {
        if ($this->consultations_per_period === 0) {
            return $this->price;
        }

        return (int) round($this->price / $this->consultations_per_period);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'periodicity' => $this->periodicity,
            'periodicity_label' => $this->periodicity_label,
            'period_days' => $this->getPeriodDays(),
            'consultations_per_period' => $this->consultations_per_period,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'price_per_consultation' => $this->price_per_consultation,
            'discount_percent' => $this->discount_percent,
            'includes_home_visits' => $this->includes_home_visits,
            'includes_teleconsultation' => $this->includes_teleconsultation,
            'priority_booking' => $this->priority_booking,
            'is_featured' => $this->is_featured,
        ];
    }
}
