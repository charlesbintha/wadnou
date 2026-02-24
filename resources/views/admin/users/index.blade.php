@extends('layouts.master')

@section('title', 'Utilisateurs')

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Utilisateurs</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Utilisateurs</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Liste des utilisateurs</h5>
                    <span>Filtrer par role, statut, ou rechercher par nom, email, telephone.</span>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-success" href="{{ route('admin.users.export', request()->query()) }}">
                        <i data-feather="download" style="width:14px;height:14px;"></i> Excel
                    </a>
                    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Nouvel utilisateur</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-4">
                    <input class="form-control" type="text" name="q" value="{{ request('q') }}" placeholder="Nom, email, telephone...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">Tous les roles</option>
                        <option value="patient" @selected(request('role') === 'patient')>Patient</option>
                        <option value="doctor" @selected(request('role') === 'doctor')>Medecin</option>
                        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                        <option value="active" @selected(request('status') === 'active')>Actif</option>
                        <option value="suspended" @selected(request('status') === 'suspended')>Suspendu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
            @php
                $roleLabels   = ['patient' => 'Patient', 'doctor' => 'Medecin', 'admin' => 'Admin'];
                $statusLabels = ['pending' => 'En attente', 'active' => 'Actif', 'suspended' => 'Suspendu'];
                $statusBadge  = ['pending' => 'warning', 'active' => 'success', 'suspended' => 'danger'];
                $roleBadge    = ['patient' => 'info', 'doctor' => 'primary', 'admin' => 'dark'];
            @endphp
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Statut</th>
                            <th>Telephone</th>
                            <th>Langue</th>
                            <th>Cree le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $roleBadge[$user->role] ?? 'secondary' }}">
                                        {{ $roleLabels[$user->role] ?? $user->role }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusBadge[$user->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$user->status] ?? $user->status }}
                                    </span>
                                </td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>{{ strtoupper($user->locale) }}</td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-primary" href="{{ route('admin.users.show', $user) }}">Voir</a>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $user) }}">Modifier</a>
                                        <form action="{{ route('admin.users.toggle-status', $user) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $user->status === 'active' ? 'Suspendre' : 'Activer' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Aucun utilisateur trouve.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
