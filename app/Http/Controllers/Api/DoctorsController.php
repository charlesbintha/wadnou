<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorsController extends Controller
{
    /**
     * Liste les médecins disponibles avec leur distance par rapport au patient
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $patientLat = $request->latitude;
        $patientLng = $request->longitude;

        // Récupérer tous les médecins actifs
        $doctors = User::where('role', 'doctor')
            ->where('status', 'active')
            ->with(['doctorProfile'])
            ->get();

        $doctorsWithDistance = [];

        foreach ($doctors as $doctor) {
            // Récupérer la dernière localisation du médecin
            $doctorLocation = Location::where('user_id', $doctor->id)
                ->latest('captured_at')
                ->first();

            if (!$doctorLocation) {
                continue; // Ignorer les médecins sans localisation
            }

            // Calculer la distance
            $distance = $this->calculateDistance(
                $patientLat,
                $patientLng,
                (float) $doctorLocation->latitude,
                (float) $doctorLocation->longitude
            );

            $distanceKm = round($distance / 1000, 2);
            $price = (int) ceil($distanceKm * ConsultationRequest::PRICE_PER_KM);

            $doctorsWithDistance[] = [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'phone' => $doctor->phone,
                'email' => $doctor->email,
                'specialty' => $doctor->doctorProfile?->specialty ?? 'Médecin généraliste',
                'location' => [
                    'latitude' => (float) $doctorLocation->latitude,
                    'longitude' => (float) $doctorLocation->longitude,
                ],
                'distance_km' => $distanceKm,
                'distance_formatted' => $this->formatDistance($distance),
                'price_amount' => $price,
                'price_formatted' => number_format($price, 0, ',', ' ') . ' FCFA',
                'price_per_km' => ConsultationRequest::PRICE_PER_KM,
            ];
        }

        // Trier par distance croissante
        usort($doctorsWithDistance, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return response()->json([
            'data' => $doctorsWithDistance,
            'meta' => [
                'price_per_km' => ConsultationRequest::PRICE_PER_KM,
                'currency' => 'FCFA',
            ],
        ]);
    }

    /**
     * Calcule le prix pour un médecin spécifique
     */
    public function calculatePrice(Request $request, User $doctor): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($doctor->role !== 'doctor') {
            return response()->json([
                'message' => 'Utilisateur non médecin',
            ], 404);
        }

        $doctorLocation = Location::where('user_id', $doctor->id)
            ->latest('captured_at')
            ->first();

        if (!$doctorLocation) {
            return response()->json([
                'message' => 'Localisation du médecin non disponible',
            ], 404);
        }

        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            (float) $doctorLocation->latitude,
            (float) $doctorLocation->longitude
        );

        $distanceKm = round($distance / 1000, 2);
        $price = (int) ceil($distanceKm * ConsultationRequest::PRICE_PER_KM);

        return response()->json([
            'data' => [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'distance_km' => $distanceKm,
                'distance_formatted' => $this->formatDistance($distance),
                'price_amount' => $price,
                'price_formatted' => number_format($price, 0, ',', ' ') . ' FCFA',
                'price_per_km' => ConsultationRequest::PRICE_PER_KM,
            ],
        ]);
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

    /**
     * Formate la distance pour l'affichage
     */
    private function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }
        return number_format($meters / 1000, 1, ',', ' ') . ' km';
    }
}
