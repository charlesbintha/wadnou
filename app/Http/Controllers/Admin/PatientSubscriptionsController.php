<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PatientSubscriptionsExport;
use App\Http\Controllers\Controller;
use App\Models\PatientSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PatientSubscriptionsController extends Controller
{
    public function index(Request $request): View
    {
        $query = PatientSubscription::with(['patient', 'plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->whereHas('patient', function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $subscriptions = $query->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::ordered()->get();

        return view('admin.patient-subscriptions.index', compact('subscriptions', 'plans'));
    }

    public function show(PatientSubscription $patientSubscription): View
    {
        $patientSubscription->load(['patient', 'plan']);

        return view('admin.patient-subscriptions.show', [
            'subscription' => $patientSubscription,
        ]);
    }

    public function cancel(PatientSubscription $patientSubscription): RedirectResponse
    {
        $patientSubscription->cancel();

        return redirect()
            ->back()
            ->with('status', 'Abonnement annule.');
    }

    public function pause(PatientSubscription $patientSubscription): RedirectResponse
    {
        $patientSubscription->pause();

        return redirect()
            ->back()
            ->with('status', 'Abonnement mis en pause.');
    }

    public function resume(PatientSubscription $patientSubscription): RedirectResponse
    {
        $patientSubscription->resume();

        return redirect()
            ->back()
            ->with('status', 'Abonnement repris.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new PatientSubscriptionsExport($request), 'abonnements_' . now()->format('Ymd_His') . '.xlsx');
    }
}
