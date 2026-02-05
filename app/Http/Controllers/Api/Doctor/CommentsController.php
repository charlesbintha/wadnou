<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ConsultationComment;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    /**
     * Liste des commentaires d'une consultation.
     */
    public function index(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $comments = $consultation->comments()
            ->with('author:id,name,role')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $comments->map(fn ($comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'author' => $comment->author ? [
                    'id' => $comment->author->id,
                    'name' => $comment->author->name,
                    'role' => $comment->author->role,
                ] : null,
                'created_at' => $comment->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Ajouter un commentaire a une consultation.
     */
    public function store(Request $request, ConsultationRequest $consultation)
    {
        $doctor = $request->user();

        if ($consultation->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $comment = ConsultationComment::create([
            'consultation_request_id' => $consultation->id,
            'author_id' => $doctor->id,
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'author' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                ],
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
