<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultationsController extends Controller
{
    /**
     * Liste des consultations du patient connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $consultations = ConsultationRequest::where('patient_id', $user->id)
            ->with(['doctor:id,name', 'location:id,latitude,longitude,address', 'appointment'])
            ->orderByDesc('requested_at')
            ->get();

        return response()->json([
            'data' => $consultations->map(function ($c) {
                return [
                    'id' => $c->id,
                    'status' => $c->status,
                    'reason' => $c->reason,
                    'notes' => $c->notes,
                    'requested_at' => $c->requested_at?->toIso8601String(),
                    'accepted_at' => $c->accepted_at?->toIso8601String(),
                    'rejected_at' => $c->rejected_at?->toIso8601String(),
                    'canceled_at' => $c->canceled_at?->toIso8601String(),
                    'closed_at' => $c->closed_at?->toIso8601String(),
                    'sla_due_at' => $c->sla_due_at?->toIso8601String(),
                    'doctor' => $c->doctor ? [
                        'id' => $c->doctor->id,
                        'name' => $c->doctor->name,
                    ] : null,
                    'location' => $c->location ? [
                        'id' => $c->location->id,
                        'latitude' => $c->location->latitude,
                        'longitude' => $c->location->longitude,
                        'address' => $c->location->address,
                    ] : null,
                    'appointment' => $c->appointment ? [
                        'id' => $c->appointment->id,
                        'scheduled_at' => $c->appointment->scheduled_at?->toIso8601String(),
                        'status' => $c->appointment->status,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Détail d'une consultation.
     */
    public function show(Request $request, ConsultationRequest $consultation)
    {
        $user = $request->user();

        if ($consultation->patient_id !== $user->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $consultation->load(['doctor:id,name,email,phone', 'location', 'appointment']);

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
                'reason' => $consultation->reason,
                'notes' => $consultation->notes,
                'requested_at' => $consultation->requested_at?->toIso8601String(),
                'accepted_at' => $consultation->accepted_at?->toIso8601String(),
                'rejected_at' => $consultation->rejected_at?->toIso8601String(),
                'canceled_at' => $consultation->canceled_at?->toIso8601String(),
                'closed_at' => $consultation->closed_at?->toIso8601String(),
                'sla_due_at' => $consultation->sla_due_at?->toIso8601String(),
                'doctor' => $consultation->doctor ? [
                    'id' => $consultation->doctor->id,
                    'name' => $consultation->doctor->name,
                    'email' => $consultation->doctor->email,
                    'phone' => $consultation->doctor->phone,
                ] : null,
                'location' => $consultation->location ? [
                    'id' => $consultation->location->id,
                    'latitude' => $consultation->location->latitude,
                    'longitude' => $consultation->location->longitude,
                    'address' => $consultation->location->address,
                    'captured_at' => $consultation->location->captured_at?->toIso8601String(),
                ] : null,
                'appointment' => $consultation->appointment ? [
                    'id' => $consultation->appointment->id,
                    'scheduled_at' => $consultation->appointment->scheduled_at?->toIso8601String(),
                    'status' => $consultation->appointment->status,
                    'started_at' => $consultation->appointment->started_at?->toIso8601String(),
                    'ended_at' => $consultation->appointment->ended_at?->toIso8601String(),
                    'completed_at' => $consultation->appointment->completed_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Annuler une consultation (patient).
     */
    public function cancel(Request $request, ConsultationRequest $consultation)
    {
        $user = $request->user();

        if ($consultation->patient_id !== $user->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($consultation->status, ['pending', 'accepted'])) {
            return response()->json(['message' => 'Impossible d\'annuler cette demande.'], 422);
        }

        $consultation->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Acces refuse.',
            ], 401);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'payment_method' => ['required', 'in:orange_money,wave,cash'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Vérifier que c'est bien un médecin actif
        $doctor = User::where('id', $validated['doctor_id'])
            ->where('role', 'doctor')
            ->where('status', 'active')
            ->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Medecin non disponible.',
            ], 422);
        }

        // Récupérer la localisation du médecin
        $doctorLocation = Location::where('user_id', $doctor->id)
            ->latest('captured_at')
            ->first();

        if (!$doctorLocation) {
            return response()->json([
                'message' => 'Localisation du medecin non disponible.',
            ], 422);
        }

        // Calculer la distance
        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            (float) $doctorLocation->latitude,
            (float) $doctorLocation->longitude
        );

        $distanceKm = round($distance / 1000, 2);
        $priceAmount = (int) ceil($distanceKm * ConsultationRequest::PRICE_PER_KM);

        // Créer ou récupérer la location du patient
        $locationId = $validated['location_id'] ?? null;

        if ($locationId) {
            $belongs = Location::where('id', $locationId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$belongs) {
                return response()->json([
                    'message' => 'Position invalide.',
                ], 422);
            }
        } else {
            // Créer une nouvelle location pour le patient
            $location = Location::create([
                'user_id' => $user->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'captured_at' => now(),
            ]);
            $locationId = $location->id;
        }

        $consultation = ConsultationRequest::create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'status' => 'pending',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'location_id' => $locationId,
            'distance_km' => $distanceKm,
            'price_amount' => $priceAmount,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                ],
                'distance_km' => $distanceKm,
                'price_amount' => $priceAmount,
                'price_formatted' => number_format($priceAmount, 0, ',', ' ') . ' FCFA',
                'payment_method' => $validated['payment_method'],
            ],
        ], 201);
    }

    /**
     * Calcule la distance entre deux points (formule de Haversine)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // en mètres

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
