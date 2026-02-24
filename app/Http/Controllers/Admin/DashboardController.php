<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationRequest;
use App\Models\DoctorDocument;
use App\Models\PatientSubscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $metrics = [
            // Utilisateurs
            'users_total'          => User::count(),
            'patients_total'       => User::where('role', 'patient')->count(),
            'patients_new_month'   => User::where('role', 'patient')->where('created_at', '>=', $startOfMonth)->count(),
            'doctors_total'        => User::where('role', 'doctor')->count(),
            'doctors_active'       => User::where('role', 'doctor')->where('status', 'active')->count(),

            // Consultations
            'consultations_total'   => ConsultationRequest::count(),
            'consultations_pending' => ConsultationRequest::where('status', 'pending')->count(),
            'consultations_month'   => ConsultationRequest::where('created_at', '>=', $startOfMonth)->count(),

            // Rendez-vous
            'appointments_today'    => Appointment::whereDate('scheduled_at', $now->toDateString())->count(),
            'appointments_month'    => Appointment::whereDate('scheduled_at', '>=', $startOfMonth->toDateString())->count(),

            // Documents
            'doctor_docs_pending'   => DoctorDocument::where('status', 'pending')->count(),

            // Abonnements
            'subscriptions_active'  => PatientSubscription::where('status', 'active')->count(),
            'subscriptions_month'   => PatientSubscription::where('created_at', '>=', $startOfMonth)->count(),
            'revenue_month'         => PatientSubscription::with('plan')
                ->where('created_at', '>=', $startOfMonth)
                ->where('payment_status', 'paid')
                ->get()
                ->sum(fn($s) => $s->plan?->price ?? 0),
        ];

        $recentConsultations = ConsultationRequest::with(['patient', 'doctor'])
            ->latest('requested_at')
            ->limit(5)
            ->get();

        $recentSubscriptions = PatientSubscription::with(['patient', 'plan'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('metrics', 'recentConsultations', 'recentSubscriptions'));
    }
}
