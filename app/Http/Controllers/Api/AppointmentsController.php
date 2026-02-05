<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentsController extends Controller
{
    /**
     * Liste des rendez-vous du patient connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::whereHas('consultationRequest', function ($q) use ($user) {
            $q->where('patient_id', $user->id);
        })
            ->with(['consultationRequest:id,reason,status,doctor_id', 'consultationRequest.doctor:id,name'])
            ->orderByDesc('scheduled_at')
            ->get();

        return response()->json([
            'data' => $appointments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                    'status' => $a->status,
                    'started_at' => $a->started_at?->toIso8601String(),
                    'ended_at' => $a->ended_at?->toIso8601String(),
                    'completed_at' => $a->completed_at?->toIso8601String(),
                    'canceled_at' => $a->canceled_at?->toIso8601String(),
                    'consultation' => $a->consultationRequest ? [
                        'id' => $a->consultationRequest->id,
                        'reason' => $a->consultationRequest->reason,
                        'status' => $a->consultationRequest->status,
                        'doctor' => $a->consultationRequest->doctor ? [
                            'id' => $a->consultationRequest->doctor->id,
                            'name' => $a->consultationRequest->doctor->name,
                        ] : null,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Détail d'un rendez-vous.
     */
    public function show(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        $appointment->load(['consultationRequest.patient', 'consultationRequest.doctor', 'consultationRequest.location']);

        if ($appointment->consultationRequest->patient_id !== $user->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $c = $appointment->consultationRequest;

        return response()->json([
            'data' => [
                'id' => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
                'status' => $appointment->status,
                'started_at' => $appointment->started_at?->toIso8601String(),
                'ended_at' => $appointment->ended_at?->toIso8601String(),
                'completed_at' => $appointment->completed_at?->toIso8601String(),
                'canceled_at' => $appointment->canceled_at?->toIso8601String(),
                'consultation' => [
                    'id' => $c->id,
                    'reason' => $c->reason,
                    'notes' => $c->notes,
                    'status' => $c->status,
                    'requested_at' => $c->requested_at?->toIso8601String(),
                    'doctor' => $c->doctor ? [
                        'id' => $c->doctor->id,
                        'name' => $c->doctor->name,
                        'email' => $c->doctor->email,
                        'phone' => $c->doctor->phone,
                    ] : null,
                    'location' => $c->location ? [
                        'id' => $c->location->id,
                        'latitude' => $c->location->latitude,
                        'longitude' => $c->location->longitude,
                        'address' => $c->location->address,
                    ] : null,
                ],
            ],
        ]);
    }
}
