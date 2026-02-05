<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;

class AppointmentsController extends Controller
{
    /**
     * Liste des rendez-vous du medecin.
     */
    public function index(Request $request)
    {
        $doctor = $request->user();

        $appointments = Appointment::whereHas('consultationRequest', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
            ->with(['consultationRequest:id,patient_id,reason,status', 'consultationRequest.patient:id,name,phone'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => $appointments->map(fn ($a) => $this->formatAppointment($a)),
        ]);
    }

    /**
     * Detail d'un rendez-vous.
     */
    public function show(Request $request, Appointment $appointment)
    {
        $doctor = $request->user();

        if ($appointment->consultationRequest->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $appointment->load([
            'consultationRequest:id,patient_id,reason,status,location_id',
            'consultationRequest.patient:id,name,email,phone',
            'consultationRequest.location',
        ]);

        return response()->json([
            'data' => $this->formatAppointmentDetail($appointment),
        ]);
    }

    /**
     * Planifier un nouveau rendez-vous.
     */
    public function store(Request $request)
    {
        $doctor = $request->user();

        $validated = $request->validate([
            'consultation_request_id' => ['required', 'exists:consultation_requests,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $consultation = ConsultationRequest::find($validated['consultation_request_id']);

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($consultation->status, ['accepted'])) {
            return response()->json(['message' => 'La consultation doit etre acceptee avant de planifier un RDV.'], 422);
        }

        if ($consultation->appointment) {
            return response()->json(['message' => 'Un RDV existe deja pour cette consultation.'], 422);
        }

        $appointment = Appointment::create([
            'consultation_request_id' => $consultation->id,
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ], 201);
    }

    /**
     * Replanifier un rendez-vous.
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $doctor = $request->user();

        if ($appointment->consultationRequest->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($appointment->status, ['scheduled'])) {
            return response()->json(['message' => 'Ce RDV ne peut pas etre replanifie.'], 422);
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $appointment->update([
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    /**
     * Annuler un rendez-vous.
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $doctor = $request->user();

        if ($appointment->consultationRequest->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($appointment->status, ['scheduled'])) {
            return response()->json(['message' => 'Ce RDV ne peut pas etre annule.'], 422);
        }

        $appointment->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    /**
     * Demarrer un rendez-vous.
     */
    public function start(Request $request, Appointment $appointment)
    {
        $doctor = $request->user();

        if ($appointment->consultationRequest->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if ($appointment->status !== 'scheduled') {
            return response()->json(['message' => 'Ce RDV ne peut pas etre demarre.'], 422);
        }

        $appointment->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    /**
     * Terminer un rendez-vous.
     */
    public function complete(Request $request, Appointment $appointment)
    {
        $doctor = $request->user();

        if ($appointment->consultationRequest->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if ($appointment->status !== 'in_progress') {
            return response()->json(['message' => 'Ce RDV ne peut pas etre termine.'], 422);
        }

        $appointment->update([
            'status' => 'completed',
            'ended_at' => now(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    private function formatAppointment(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'consultation_request_id' => $a->consultation_request_id,
            'scheduled_at' => $a->scheduled_at?->toIso8601String(),
            'status' => $a->status,
            'started_at' => $a->started_at?->toIso8601String(),
            'ended_at' => $a->ended_at?->toIso8601String(),
            'completed_at' => $a->completed_at?->toIso8601String(),
            'canceled_at' => $a->canceled_at?->toIso8601String(),
            'patient' => $a->consultationRequest?->patient ? [
                'id' => $a->consultationRequest->patient->id,
                'name' => $a->consultationRequest->patient->name,
                'phone' => $a->consultationRequest->patient->phone,
            ] : null,
            'reason' => $a->consultationRequest?->reason,
        ];
    }

    private function formatAppointmentDetail(Appointment $a): array
    {
        $data = $this->formatAppointment($a);

        $data['patient'] = $a->consultationRequest?->patient ? [
            'id' => $a->consultationRequest->patient->id,
            'name' => $a->consultationRequest->patient->name,
            'email' => $a->consultationRequest->patient->email,
            'phone' => $a->consultationRequest->patient->phone,
        ] : null;

        $data['location'] = $a->consultationRequest?->location ? [
            'id' => $a->consultationRequest->location->id,
            'latitude' => $a->consultationRequest->location->latitude,
            'longitude' => $a->consultationRequest->location->longitude,
            'address' => $a->consultationRequest->location->address,
        ] : null;

        return $data;
    }
}
