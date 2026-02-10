<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    /**
     * Liste les forfaits disponibles.
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(fn($plan) => $plan->toApiArray());

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }

    /**
     * Detail d'un forfait.
     */
    public function showPlan(SubscriptionPlan $plan): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $plan->toApiArray(),
        ]);
    }

    /**
     * Abonnement actif du patient.
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $subscription = PatientSubscription::with('plan')
            ->forPatient($request->user()->id)
            ->active()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $subscription?->toApiArray(),
        ]);
    }

    /**
     * Historique des abonnements.
     */
    public function history(Request $request): JsonResponse
    {
        $subscriptions = PatientSubscription::with('plan')
            ->forPatient($request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($sub) => $sub->toApiArray());

        return response()->json([
            'status' => 'success',
            'data' => $subscriptions,
        ]);
    }

    /**
     * Souscrire a un forfait.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|string|in:orange_money,wave,free_money,cash',
        ]);

        $user = $request->user();

        // Verifier qu'il n'a pas deja un abonnement actif
        $existing = PatientSubscription::forPatient($user->id)
            ->active()
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous avez deja un abonnement actif. Veuillez l\'annuler avant d\'en souscrire un nouveau.',
            ], 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ce forfait n\'est plus disponible.',
            ], 422);
        }

        $subscription = PatientSubscription::create([
            'patient_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PatientSubscription::STATUS_ACTIVE,
            'current_period_start' => Carbon::today(),
            'current_period_end' => Carbon::today()->addDays($plan->getPeriodDays()),
            'consultations_used' => 0,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'auto_renew' => true,
        ]);

        $subscription->load('plan');

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement cree avec succes.',
            'data' => $subscription->toApiArray(),
        ], 201);
    }

    /**
     * Annuler l'abonnement.
     */
    public function cancel(Request $request): JsonResponse
    {
        $subscription = PatientSubscription::forPatient($request->user()->id)
            ->active()
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun abonnement actif a annuler.',
            ], 404);
        }

        $subscription->cancel();

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement annule. Vous pouvez utiliser vos consultations restantes jusqu\'a la fin de la periode.',
        ]);
    }

    /**
     * Mettre en pause l'abonnement.
     */
    public function pause(Request $request): JsonResponse
    {
        $subscription = PatientSubscription::forPatient($request->user()->id)
            ->active()
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun abonnement actif a mettre en pause.',
            ], 404);
        }

        $subscription->pause();

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement mis en pause.',
        ]);
    }

    /**
     * Reprendre l'abonnement.
     */
    public function resume(Request $request): JsonResponse
    {
        $subscription = PatientSubscription::forPatient($request->user()->id)
            ->where('status', PatientSubscription::STATUS_PAUSED)
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun abonnement en pause a reprendre.',
            ], 404);
        }

        $subscription->resume();

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement repris.',
        ]);
    }

    /**
     * Estimer le prix d'un forfait personnalise.
     */
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'periodicity' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'consultations_per_period' => 'required|integer|min:1|max:50',
        ]);

        // Prix de base par consultation (sans forfait)
        $basePrice = 15000; // 15 000 FCFA par consultation

        $consultations = $request->consultations_per_period;
        $totalBase = $basePrice * $consultations;

        // Remises selon le nombre de consultations
        $discountPercent = match (true) {
            $consultations >= 20 => 30,
            $consultations >= 10 => 20,
            $consultations >= 5 => 15,
            $consultations >= 3 => 10,
            default => 5,
        };

        // Bonus de remise selon la periodicite
        $periodicityBonus = match ($request->periodicity) {
            'yearly' => 10,
            'quarterly' => 5,
            'monthly' => 2,
            default => 0,
        };

        $discountPercent = min($discountPercent + $periodicityBonus, 40);
        $discountAmount = (int) round($totalBase * $discountPercent / 100);
        $finalPrice = $totalBase - $discountAmount;

        return response()->json([
            'status' => 'success',
            'data' => [
                'periodicity' => $request->periodicity,
                'periodicity_label' => SubscriptionPlan::PERIODICITY_LABELS[$request->periodicity],
                'consultations_per_period' => $consultations,
                'price_per_consultation' => $basePrice,
                'total_without_discount' => $totalBase,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'formatted_price' => number_format($finalPrice, 0, ',', ' ') . ' FCFA',
            ],
        ]);
    }
}
