<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        if (SubscriptionPlan::count() > 0) {
            return;
        }

        $plans = [
            [
                'name' => 'Essentiel Hebdo',
                'slug' => 'essentiel-hebdo',
                'description' => 'Ideal pour un suivi medical regulier. 1 consultation par semaine.',
                'periodicity' => 'weekly',
                'consultations_per_period' => 1,
                'price' => 12000,
                'discount_percent' => 5,
                'includes_home_visits' => false,
                'includes_teleconsultation' => false,
                'priority_booking' => false,
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Famille Mensuel',
                'slug' => 'famille-mensuel',
                'description' => 'Le plus populaire ! 4 consultations par mois pour toute la famille.',
                'periodicity' => 'monthly',
                'consultations_per_period' => 4,
                'price' => 45000,
                'discount_percent' => 15,
                'includes_home_visits' => true,
                'includes_teleconsultation' => false,
                'priority_booking' => false,
                'display_order' => 2,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Premium Mensuel',
                'slug' => 'premium-mensuel',
                'description' => 'Acces complet avec visites a domicile et teleconsultation incluses.',
                'periodicity' => 'monthly',
                'consultations_per_period' => 6,
                'price' => 75000,
                'discount_percent' => 20,
                'includes_home_visits' => true,
                'includes_teleconsultation' => true,
                'priority_booking' => true,
                'display_order' => 3,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Trimestriel Eco',
                'slug' => 'trimestriel-eco',
                'description' => 'Economisez avec un engagement trimestriel. 10 consultations sur 3 mois.',
                'periodicity' => 'quarterly',
                'consultations_per_period' => 10,
                'price' => 120000,
                'discount_percent' => 25,
                'includes_home_visits' => true,
                'includes_teleconsultation' => true,
                'priority_booking' => false,
                'display_order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Annuel Gold',
                'slug' => 'annuel-gold',
                'description' => 'Le meilleur rapport qualite-prix. 48 consultations par an avec tous les avantages.',
                'periodicity' => 'yearly',
                'consultations_per_period' => 48,
                'price' => 400000,
                'discount_percent' => 30,
                'includes_home_visits' => true,
                'includes_teleconsultation' => true,
                'priority_booking' => true,
                'display_order' => 5,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
