@extends('layouts.master')

@section('title', 'Modifier — ' . $user->name)

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Modifier l'utilisateur</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations du compte</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Telephone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $user->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="patient" @selected(old('role', $user->role) === 'patient')>Patient</option>
                                    <option value="doctor" @selected(old('role', $user->role) === 'doctor')>Medecin</option>
                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                                </select>
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Statut <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="pending" @selected(old('status', $user->status) === 'pending')>En attente</option>
                                    <option value="active" @selected(old('status', $user->status) === 'active')>Actif</option>
                                    <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspendu</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Langue <span class="text-danger">*</span></label>
                                <select name="locale" class="form-select @error('locale') is-invalid @enderror" required>
                                    <option value="fr" @selected(old('locale', $user->locale) === 'fr')>Francais</option>
                                    <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                                </select>
                                @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                                <p class="text-muted small mb-0">Laisser vide pour conserver le mot de passe actuel.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
