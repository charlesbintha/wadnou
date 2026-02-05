<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Voir le profil du medecin connecte.
     */
    public function show(Request $request)
    {
        $doctor = $request->user();
        $doctor->load('doctorProfile');

        return response()->json([
            'data' => $this->formatProfile($doctor),
        ]);
    }

    /**
     * Modifier le profil du medecin.
     */
    public function update(Request $request)
    {
        $doctor = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'locale' => ['sometimes', 'string', 'in:fr,en'],
            'specialty' => ['sometimes', 'string', 'max:100'],
            'bio' => ['sometimes', 'string', 'max:1000'],
        ]);

        // Update user fields
        $userFields = array_intersect_key($validated, array_flip(['name', 'phone', 'locale']));
        if (!empty($userFields)) {
            $doctor->update($userFields);
        }

        // Update doctor profile fields
        $profileFields = array_intersect_key($validated, array_flip(['specialty', 'bio']));
        if (!empty($profileFields)) {
            $doctor->doctorProfile()->updateOrCreate(
                ['user_id' => $doctor->id],
                $profileFields
            );
        }

        $doctor->load('doctorProfile');

        return response()->json([
            'data' => $this->formatProfile($doctor),
        ]);
    }

    private function formatProfile($doctor): array
    {
        $profile = $doctor->doctorProfile;

        return [
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $doctor->email,
            'phone' => $doctor->phone,
            'role' => $doctor->role,
            'status' => $doctor->status,
            'locale' => $doctor->locale,
            'specialty' => $profile?->specialty,
            'license_number' => $profile?->license_number,
            'verification_status' => $profile?->verification_status,
            'verified_at' => $profile?->verified_at?->toIso8601String(),
            'bio' => $profile?->bio,
        ];
    }
}
