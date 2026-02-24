<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load([
            'patientProfile',
            'doctorProfile',
            'consultationRequestsAsPatient' => fn($q) => $q->latest()->limit(5),
            'activeSubscription.plan',
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:32'],
            'role'     => ['required', 'in:patient,doctor,admin'],
            'status'   => ['required', 'in:pending,active,suspended'],
            'locale'   => ['required', 'in:fr,en'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique'       => 'Cet email est deja utilise.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caracteres.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'role'     => $validated['role'],
            'status'   => $validated['status'],
            'locale'   => $validated['locale'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur cree avec succes.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', "unique:users,email,{$user->id}"],
            'phone'    => ['nullable', 'string', 'max:32'],
            'role'     => ['required', 'in:patient,doctor,admin'],
            'status'   => ['required', 'in:pending,active,suspended'],
            'locale'   => ['required', 'in:fr,en'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique'       => 'Cet email est deja utilise.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caracteres.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $data = [
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'phone'  => $validated['phone'] ?? null,
            'role'   => $validated['role'],
            'status' => $validated['status'],
            'locale' => $validated['locale'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Utilisateur mis a jour avec succes.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Proteger le compte admin connecte
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur supprime avec succes.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas modifier votre propre statut.');
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'active' : 'suspendu';

        return redirect()
            ->back()
            ->with('status', "Compte {$label}.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new UsersExport($request), 'utilisateurs_' . now()->format('Ymd_His') . '.xlsx');
    }
}
