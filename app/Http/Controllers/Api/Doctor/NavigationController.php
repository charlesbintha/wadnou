<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Location;
use App\Services\Maps\GoogleMapsDirections;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    public function __construct(
        protected GoogleMapsDirections $mapsService
    ) {}

    /**
     * Start navigation to a patient
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'consultation_id' => 'required|integer|exists:consultation_requests,id',
            'origin_latitude' => 'required|numeric|between:-90,90',
            'origin_longitude' => 'required|numeric|between:-180,180',
        ]);

        $doctor = $request->user();
        $consultation = ConsultationRequest::where('id', $request->consultation_id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'accepted')
            ->firstOrFail();

        // Get patient's latest location
        $patientLocation = Location::where('user_id', $consultation->patient_id)
            ->latest('captured_at')
            ->first();

        if (!$patientLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Localisation du patient non disponible',
            ], 404);
        }

        // Calculate route using Google Maps
        $route = $this->mapsService->getFastestRoute(
            $request->origin_latitude,
            $request->origin_longitude,
            (float) $patientLocation->latitude,
            (float) $patientLocation->longitude
        );

        if (!$route['ok']) {
            return response()->json([
                'success' => false,
                'message' => $route['message'] ?? 'Erreur calcul itineraire',
            ], 500);
        }

        // Store doctor's current position
        Location::create([
            'user_id' => $doctor->id,
            'latitude' => $request->origin_latitude,
            'longitude' => $request->origin_longitude,
            'captured_at' => now(),
        ]);

        // Mark consultation as in_navigation
        $consultation->update([
            'navigation_started_at' => now(),
        ]);

        $distanceMeters = ($route['distance_km'] ?? 0) * 1000;
        $etaMinutes = $route['eta_minutes'] ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $consultation->id,
                'consultation_id' => $consultation->id,
                'patient' => [
                    'id' => $consultation->patient_id,
                    'name' => $consultation->patient->name ?? 'Patient',
                    'location' => [
                        'latitude' => (float) $patientLocation->latitude,
                        'longitude' => (float) $patientLocation->longitude,
                        'address' => $patientLocation->address ?? null,
                    ],
                ],
                'distance_meters' => (int) $distanceMeters,
                'eta_minutes' => $etaMinutes,
                'formatted_distance' => $this->formatDistance($distanceMeters),
                'formatted_eta' => $this->formatDuration($etaMinutes),
                'polyline' => $route['route_data']['routes'][0]['overview_polyline']['points'] ?? null,
                'route_data' => $route['route_data'] ?? null,
            ],
        ]);
    }

    /**
     * Update doctor's position during navigation
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $request->validate([
            'consultation_id' => 'required|integer|exists:consultation_requests,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|min:0|max:360',
        ]);

        $doctor = $request->user();
        $consultation = ConsultationRequest::where('id', $request->consultation_id)
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('navigation_started_at')
            ->firstOrFail();

        // Store new position
        Location::create([
            'user_id' => $doctor->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'captured_at' => now(),
        ]);

        // Get patient's latest location
        $patientLocation = Location::where('user_id', $consultation->patient_id)
            ->latest('captured_at')
            ->first();

        if (!$patientLocation) {
            return response()->json([
                'success' => true,
                'data' => [
                    'position_updated' => true,
                ],
            ]);
        }

        // Calculate remaining distance
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $patientLocation->latitude,
            $patientLocation->longitude
        );

        // Estimate remaining time (assuming average speed of 30 km/h in city)
        $avgSpeed = $request->speed && $request->speed > 0 ? $request->speed : 30;
        $etaMinutes = ($distance / 1000) / $avgSpeed * 60;

        return response()->json([
            'success' => true,
            'data' => [
                'position_updated' => true,
                'distance_to_patient' => round($distance),
                'eta_minutes' => round($etaMinutes),
                'formatted_distance' => $this->formatDistance($distance),
                'formatted_eta' => $this->formatDuration($etaMinutes),
            ],
        ]);
    }

    /**
     * Get updated route (recalculate from current position)
     */
    public function refreshRoute(Request $request): JsonResponse
    {
        $request->validate([
            'consultation_id' => 'required|integer|exists:consultation_requests,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $doctor = $request->user();
        $consultation = ConsultationRequest::where('id', $request->consultation_id)
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('navigation_started_at')
            ->firstOrFail();

        // Get patient's latest location
        $patientLocation = Location::where('user_id', $consultation->patient_id)
            ->latest('captured_at')
            ->first();

        if (!$patientLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Localisation du patient non disponible',
            ], 404);
        }

        // Calculate new route
        $route = $this->mapsService->getFastestRoute(
            $request->latitude,
            $request->longitude,
            (float) $patientLocation->latitude,
            (float) $patientLocation->longitude
        );

        if (!$route['ok']) {
            return response()->json([
                'success' => false,
                'message' => $route['message'] ?? 'Erreur calcul itineraire',
            ], 500);
        }

        // Update doctor's position
        Location::create([
            'user_id' => $doctor->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'captured_at' => now(),
        ]);

        $distanceMeters = ($route['distance_km'] ?? 0) * 1000;
        $etaMinutes = $route['eta_minutes'] ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $consultation->id,
                'distance_meters' => (int) $distanceMeters,
                'eta_minutes' => $etaMinutes,
                'formatted_distance' => $this->formatDistance($distanceMeters),
                'formatted_eta' => $this->formatDuration($etaMinutes),
                'polyline' => $route['route_data']['routes'][0]['overview_polyline']['points'] ?? null,
                'route_data' => $route['route_data'] ?? null,
            ],
        ]);
    }

    /**
     * Stop navigation
     */
    public function stop(Request $request): JsonResponse
    {
        $request->validate([
            'consultation_id' => 'required|integer|exists:consultation_requests,id',
        ]);

        $doctor = $request->user();
        $consultation = ConsultationRequest::where('id', $request->consultation_id)
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('navigation_started_at')
            ->firstOrFail();

        $consultation->update([
            'navigation_ended_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Navigation terminee',
        ]);
    }

    /**
     * Get doctor's current position (for patient to track)
     */
    public function getDoctorPosition(int $consultationId): JsonResponse
    {
        $user = request()->user();
        $consultation = ConsultationRequest::where('id', $consultationId)
            ->where('patient_id', $user->id)
            ->whereNotNull('navigation_started_at')
            ->whereNull('navigation_ended_at')
            ->firstOrFail();

        $doctorLocation = Location::where('user_id', $consultation->doctor_id)
            ->latest('captured_at')
            ->first();

        if (!$doctorLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Position du medecin non disponible',
            ], 404);
        }

        // Get patient's location
        $patientLocation = Location::where('user_id', $user->id)
            ->latest('captured_at')
            ->first();

        $distance = null;
        $eta = null;

        if ($patientLocation) {
            $distance = $this->calculateDistance(
                $doctorLocation->latitude,
                $doctorLocation->longitude,
                $patientLocation->latitude,
                $patientLocation->longitude
            );
            $eta = round(($distance / 1000) / 30 * 60); // Assuming 30 km/h
        }

        return response()->json([
            'success' => true,
            'data' => [
                'doctor' => [
                    'name' => $consultation->doctor->name ?? 'Medecin',
                ],
                'position' => [
                    'latitude' => $doctorLocation->latitude,
                    'longitude' => $doctorLocation->longitude,
                    'recorded_at' => $doctorLocation->recorded_at->toIso8601String(),
                ],
                'distance_meters' => $distance ? round($distance) : null,
                'eta_minutes' => $eta,
                'formatted_distance' => $distance ? $this->formatDistance($distance) : null,
                'formatted_eta' => $eta ? $this->formatDuration($eta) : null,
            ],
        ]);
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

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

    private function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }
        return number_format($meters / 1000, 1) . ' km';
    }

    private function formatDuration(float $minutes): string
    {
        if ($minutes < 60) {
            return round($minutes) . ' min';
        }
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        return $hours . ' h ' . $mins . ' min';
    }
}
