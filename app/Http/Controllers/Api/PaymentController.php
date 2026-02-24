<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientSubscription;
use App\Models\SubscriptionPlan;
use App\Services\PayTechService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PayTechService $paytech) {}

    /**
     * Initier un paiement PayTech pour un forfait.
     * POST /api/payments/initiate
     * Auth: Sanctum
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();

        // Verifier qu'il n'a pas deja un abonnement actif
        $existing = PatientSubscription::forPatient($user->id)->active()->first();
        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous avez deja un abonnement actif. Annulez-le avant d\'en souscrire un nouveau.',
            ], 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ce forfait n\'est plus disponible.',
            ], 422);
        }

        // Creer la souscription en attente de confirmation paiement
        $subscription = PatientSubscription::create([
            'patient_id'           => $user->id,
            'plan_id'              => $plan->id,
            'status'               => PatientSubscription::STATUS_ACTIVE,
            'current_period_start' => Carbon::today(),
            'current_period_end'   => Carbon::today()->addDays($plan->getPeriodDays()),
            'consultations_used'   => 0,
            'payment_method'       => 'paytech',
            'payment_status'       => 'pending',
            'auto_renew'           => true,
        ]);

        // Construire la reference commande
        $refCommand = "wadnou_sub_{$subscription->id}";

        // Appel a l'API PayTech
        $result = $this->paytech->requestPayment([
            'item_name'    => $plan->name,
            'item_price'   => $plan->price,
            'ref_command'  => $refCommand,
            'command_name' => "Abonnement Wadnou — {$plan->name}",
            'ipn_url'      => url('/api/payments/ipn'),
            'custom_field' => json_encode([
                'subscription_id' => $subscription->id,
                'user_id'         => $user->id,
                'plan_id'         => $plan->id,
            ]),
        ]);

        if (!$result['success']) {
            // Supprimer la souscription si PayTech est inaccessible
            $subscription->delete();

            Log::error('PayTech initiate echoue', ['plan' => $plan->id, 'user' => $user->id, 'error' => $result['error']]);

            return response()->json([
                'status'  => 'error',
                'message' => $result['error'] ?? 'Impossible d\'initialiser le paiement. Reessayez.',
            ], 502);
        }

        Log::info('PayTech initiate OK', [
            'subscription_id' => $subscription->id,
            'token'           => $result['token'],
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'redirect_url'    => $result['redirect_url'],
                'token'           => $result['token'],
                'subscription_id' => $subscription->id,
            ],
        ]);
    }

    /**
     * Notification IPN de PayTech (webhook public, pas d'auth).
     * POST /api/payments/ipn
     */
    public function ipn(Request $request): Response
    {
        $params = $request->all();

        Log::info('PayTech IPN recu', $params ?? []);

        // Verifier la signature
        if (!$this->paytech->verifyIpn($params)) {
            Log::warning('PayTech IPN: signature invalide', ['ref' => $params['ref_command'] ?? null]);
            return response('Signature invalide', 400);
        }

        $typeEvent  = $params['type_event'] ?? null;
        $refCommand = $params['ref_command'] ?? null;

        // Extraire l'ID de souscription du ref_command
        if (!$refCommand || !str_starts_with($refCommand, 'wadnou_sub_')) {
            Log::warning('PayTech IPN: ref_command inconnu', ['ref' => $refCommand]);
            return response('OK');
        }

        $subscriptionId = (int) str_replace('wadnou_sub_', '', $refCommand);
        $subscription   = PatientSubscription::with('plan')->find($subscriptionId);

        if (!$subscription) {
            Log::warning('PayTech IPN: souscription introuvable', ['id' => $subscriptionId]);
            return response('OK');
        }

        // Traiter l'evenement
        if ($typeEvent === 'sale_complete') {
            $subscription->update([
                'payment_status' => 'paid',
                'payment_method' => $params['payment_method'] ?? $subscription->payment_method,
            ]);

            Log::info('PayTech IPN: paiement confirme', [
                'subscription_id' => $subscriptionId,
                'method'          => $params['payment_method'] ?? null,
            ]);
        } elseif (in_array($typeEvent, ['sale_canceled', 'refund_complete'])) {
            $subscription->update([
                'status'         => PatientSubscription::STATUS_CANCELLED,
                'payment_status' => 'failed',
                'cancelled_at'   => now(),
            ]);

            Log::info('PayTech IPN: paiement annule/rembourse', [
                'subscription_id' => $subscriptionId,
                'event'           => $typeEvent,
            ]);
        }

        return response('OK');
    }
}
