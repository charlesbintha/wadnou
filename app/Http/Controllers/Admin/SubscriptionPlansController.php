<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPlansController extends Controller
{
    public function index(Request $request): View
    {
        $query = SubscriptionPlan::query();

        if ($request->filled('periodicity')) {
            $query->where('periodicity', $request->periodicity);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $plans = $query->ordered()->paginate(15)->withQueryString();

        return view('admin.subscription-plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.subscription-plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'periodicity' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'consultations_per_period' => 'required|integer|min:1|max:100',
            'price' => 'required|integer|min:0',
            'discount_percent' => 'required|integer|min:0|max:100',
            'includes_home_visits' => 'boolean',
            'includes_teleconsultation' => 'boolean',
            'priority_booking' => 'boolean',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['includes_home_visits'] = $request->boolean('includes_home_visits');
        $validated['includes_teleconsultation'] = $request->boolean('includes_teleconsultation');
        $validated['priority_booking'] = $request->boolean('priority_booking');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured');

        SubscriptionPlan::create($validated);

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('status', 'Forfait cree avec succes.');
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('admin.subscription-plans.edit', ['plan' => $subscriptionPlan]);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'periodicity' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'consultations_per_period' => 'required|integer|min:1|max:100',
            'price' => 'required|integer|min:0',
            'discount_percent' => 'required|integer|min:0|max:100',
            'includes_home_visits' => 'boolean',
            'includes_teleconsultation' => 'boolean',
            'priority_booking' => 'boolean',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['includes_home_visits'] = $request->boolean('includes_home_visits');
        $validated['includes_teleconsultation'] = $request->boolean('includes_teleconsultation');
        $validated['priority_booking'] = $request->boolean('priority_booking');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $subscriptionPlan->update($validated);

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('status', 'Forfait mis a jour avec succes.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        // Verifier qu'il n'y a pas d'abonnements actifs
        if ($subscriptionPlan->activeSubscriptions()->exists()) {
            return redirect()
                ->route('admin.subscription-plans.index')
                ->with('error', 'Impossible de supprimer un forfait avec des abonnements actifs.');
        }

        $subscriptionPlan->delete();

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('status', 'Forfait supprime avec succes.');
    }

    public function toggle(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->update(['is_active' => !$subscriptionPlan->is_active]);

        $message = $subscriptionPlan->is_active
            ? 'Forfait active.'
            : 'Forfait desactive.';

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('status', $message);
    }
}
