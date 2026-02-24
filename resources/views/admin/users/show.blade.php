@extends('layouts.master')

@section('title', 'Utilisateur — ' . $user->name)

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>{{ $user->name }}</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
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

    @php
        $statusBadge  = ['pending' => 'warning', 'active' => 'success', 'suspended' => 'danger'];
        $statusLabels = ['pending' => 'En attente', 'active' => 'Actif', 'suspended' => 'Suspendu'];
        $roleBadge    = ['patient' => 'info', 'doctor' => 'primary', 'admin' => 'dark'];
        $roleLabels   = ['patient' => 'Patient', 'doctor' => 'Medecin', 'admin' => 'Admin'];
    @endphp

    <div class="row">
        {{-- Carte identite --}}
        <div class="col-xl-4 col-md-5">
            <div class="card">
                <div class="card-body text-center p-4">
                    <div class="avatar-xl mx-auto mb-3 bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                        <span class="fs-1 text-primary fw-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-{{ $roleBadge[$user->role] ?? 'secondary' }}">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                        <span class="badge bg-{{ $statusBadge[$user->status] ?? 'secondary' }}">{{ $statusLabels[$user->status] ?? $user->status }}</span>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">Modifier</a>
                        <form action="{{ route('admin.users.toggle-status', $user) }}" method="post">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                {{ $user->status === 'active' ? 'Suspendre' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-footer">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Telephone</span>
                            <span>{{ $user->phone ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Langue</span>
                            <span>{{ $user->locale === 'fr' ? 'Francais' : 'English' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Inscrit le</span>
                            <span>{{ $user->created_at->format('d/m/Y') }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Derniere modif.</span>
                            <span>{{ $user->updated_at->format('d/m/Y') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Forfait actif --}}
            @if ($user->activeSubscription)
                @php($sub = $user->activeSubscription)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Forfait actif</h5>
                    </div>
                    <div class="card-body">
                        <p class="fw-bold mb-1">{{ $sub->plan->name }}</p>
                        <p class="text-muted small mb-2">{{ $sub->plan->periodicity_label }}</p>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Consultations</span>
                            <span class="small fw-semibold">{{ $sub->consultations_used }} / {{ $sub->plan->consultations_per_period }}</span>
                        </div>
                        <div class="progress mb-2" style="height:6px;">
                            @php($pct = $sub->plan->consultations_per_period > 0 ? round($sub->consultations_used / $sub->plan->consultations_per_period * 100) : 0)
                            <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                        </div>
                        <p class="text-muted small mb-0">Expire le {{ $sub->current_period_end->format('d/m/Y') }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Details et consultations --}}
        <div class="col-xl-8 col-md-7">

            {{-- Profil patient --}}
            @if ($user->patientProfile)
                @php($profile = $user->patientProfile)
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Profil patient</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if ($profile->gender)
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Genre</span>
                                    <span>{{ $profile->gender === 'male' ? 'Homme' : 'Femme' }}</span>
                                </div>
                            @endif
                            @if ($profile->date_of_birth)
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Date de naissance</span>
                                    <span>{{ \Carbon\Carbon::parse($profile->date_of_birth)->format('d/m/Y') }}</span>
                                </div>
                            @endif
                            @if ($profile->address)
                                <div class="col-md-8">
                                    <span class="text-muted small d-block">Adresse</span>
                                    <span>{{ $profile->address }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Profil medecin --}}
            @if ($user->doctorProfile)
                @php($profile = $user->doctorProfile)
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Profil medecin</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Specialite</span>
                                <span>{{ $profile->specialty ?? '—' }}</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Numero de licence</span>
                                <span>{{ $profile->license_number ?? '—' }}</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Verification</span>
                                @php($vBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'])
                                @php($vLabels = ['pending' => 'En attente', 'approved' => 'Approuve', 'rejected' => 'Refuse'])
                                <span class="badge bg-{{ $vBadge[$profile->verification_status] ?? 'secondary' }}">
                                    {{ $vLabels[$profile->verification_status] ?? $profile->verification_status }}
                                </span>
                            </div>
                            @if ($profile->bio)
                                <div class="col-12">
                                    <span class="text-muted small d-block">Bio</span>
                                    <p class="mb-0">{{ $profile->bio }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Dernieres consultations (patients) --}}
            @if ($user->isPatient() && $user->consultationRequestsAsPatient->isNotEmpty())
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Dernieres consultations</h5>
                        <a href="{{ route('admin.consultations.index', ['patient_id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $cStatusLabels = ['pending' => 'En attente', 'assigned' => 'Assigne', 'accepted' => 'Accepte', 'rejected' => 'Refuse', 'closed' => 'Clos', 'canceled' => 'Annule', 'expired' => 'Expire'];
                            $cStatusBadge  = ['pending' => 'warning', 'assigned' => 'info', 'accepted' => 'primary', 'rejected' => 'danger', 'closed' => 'success', 'canceled' => 'secondary', 'expired' => 'dark'];
                        @endphp
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Medecin</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($user->consultationRequestsAsPatient as $consultation)
                                        <tr>
                                            <td>{{ optional($consultation->doctor)->name ?? 'Non assigne' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $cStatusBadge[$consultation->status] ?? 'secondary' }}">
                                                    {{ $cStatusLabels[$consultation->status] ?? $consultation->status }}
                                                </span>
                                            </td>
                                            <td>{{ $consultation->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.consultations.show', $consultation) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Zone dangereuse --}}
    @if (auth()->id() !== $user->id)
        <div class="card mt-2 border-danger">
            <div class="card-header bg-danger text-white"><h5 class="mb-0">Zone dangereuse</h5></div>
            <div class="card-body">
                <p class="mb-3">Supprimer definitivement cet utilisateur et toutes ses donnees. Cette action est irreversible.</p>
                <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer {{ addslashes($user->name) }} ? Cette action est irreversible.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer le compte</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
