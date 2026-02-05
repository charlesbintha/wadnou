<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Events\ConsultationAccepted;
use App\Events\ConsultationRejected;
use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;

class ConsultationsController extends Controller
{
    /**
     * Liste des consultations assignees au medecin connecte.
     */
    public function index(Request $request)
    {
        $doctor = $request->user();

        $consultations = ConsultationRequest::where('doctor_id', $doctor->id)
            ->with(['patient:id,name,phone', 'location:id,latitude,longitude,address', 'appointment'])
            ->orderByDesc('requested_at')
            ->get();

        return response()->json([
            'data' => $consultations->map(fn ($c) => $this->formatConsultation($c)),
        ]);
    }

    /**
     * Liste des demandes en attente assignees au medecin.
     */
    public function pending(Request $request)
    {
        $doctor = $request->user();

        $consultations = ConsultationRequest::where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'assigned'])
            ->with(['patient:id,name,phone', 'location:id,latitude,longitude,address'])
            ->orderBy('sla_due_at')
            ->get();

        return response()->json([
            'data' => $consultations->map(fn ($c) => $this->formatConsultation($c)),
        ]);
    }

    /**
     * Detail d'une consultation avec commentaires.
     */
    public function show(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $consultation->load([
            'patient:id,name,email,phone',
            'location',
            'appointment',
            'comments' => fn ($q) => $q->with('author:id,name')->orderBy('created_at'),
        ]);

        return response()->json([
            'data' => $this->formatConsultationDetail($consultation),
        ]);
    }

    /**
     * Accepter une demande de consultation.
     */
    public function accept(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($consultation->status, ['pending', 'assigned'])) {
            return response()->json(['message' => 'Impossible d\'accepter cette demande.'], 422);
        }

        $consultation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        event(new ConsultationAccepted($consultation));

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
                'accepted_at' => $consultation->accepted_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Rejeter une demande de consultation.
     */
    public function reject(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!in_array($consultation->status, ['pending', 'assigned'])) {
            return response()->json(['message' => 'Impossible de rejeter cette demande.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $consultation->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'notes' => $validated['reason'] ?? $consultation->notes,
        ]);

        event(new ConsultationRejected($consultation, $validated['reason'] ?? null));

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
                'rejected_at' => $consultation->rejected_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Cloturer une consultation.
     */
    public function close(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if ($consultation->status !== 'accepted') {
            return response()->json(['message' => 'Seule une consultation acceptee peut etre cloturee.'], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $consultation->update([
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $validated['notes'] ?? $consultation->notes,
        ]);

        return response()->json([
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
                'closed_at' => $consultation->closed_at->toIso8601String(),
            ],
        ]);
    }

    private function formatConsultation(ConsultationRequest $c): array
    {
        return [
            'id' => $c->id,
            'status' => $c->status,
            'reason' => $c->reason,
            'notes' => $c->notes,
            'requested_at' => $c->requested_at?->toIso8601String(),
            'accepted_at' => $c->accepted_at?->toIso8601String(),
            'rejected_at' => $c->rejected_at?->toIso8601String(),
            'closed_at' => $c->closed_at?->toIso8601String(),
            'sla_due_at' => $c->sla_due_at?->toIso8601String(),
            'patient' => $c->patient ? [
                'id' => $c->patient->id,
                'name' => $c->patient->name,
                'phone' => $c->patient->phone,
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
    }

    private function formatConsultationDetail(ConsultationRequest $c): array
    {
        $data = $this->formatConsultation($c);

        $data['patient'] = $c->patient ? [
            'id' => $c->patient->id,
            'name' => $c->patient->name,
            'email' => $c->patient->email,
            'phone' => $c->patient->phone,
        ] : null;

        $data['location'] = $c->location ? [
            'id' => $c->location->id,
            'latitude' => $c->location->latitude,
            'longitude' => $c->location->longitude,
            'address' => $c->location->address,
            'captured_at' => $c->location->captured_at?->toIso8601String(),
        ] : null;

        $data['appointment'] = $c->appointment ? [
            'id' => $c->appointment->id,
            'scheduled_at' => $c->appointment->scheduled_at?->toIso8601String(),
            'status' => $c->appointment->status,
            'started_at' => $c->appointment->started_at?->toIso8601String(),
            'ended_at' => $c->appointment->ended_at?->toIso8601String(),
            'completed_at' => $c->appointment->completed_at?->toIso8601String(),
        ] : null;

        $data['comments'] = $c->comments->map(fn ($comment) => [
            'id' => $comment->id,
            'content' => $comment->content,
            'is_internal' => $comment->is_internal,
            'author' => $comment->author ? [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
            ] : null,
            'created_at' => $comment->created_at->toIso8601String(),
        ])->toArray();

        return $data;
    }
}
