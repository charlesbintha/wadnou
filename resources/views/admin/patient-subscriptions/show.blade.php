@extends('layouts.master')

@section('title', 'Abonnement — ' . $subscription->patient->name)

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Abonnement de {{ $subscription->patient->name }}</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.patient-subscriptions.index') }}">Abonnements</a></li>
                    <li class="breadcrumb-item active">{{ $subscription->patient->name }}</li>
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
        $statusBadge  = ['active' => 'success', 'paused' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'];
        $statusLabels = ['active' => 'Actif', 'paused' => 'En pause', 'cancelled' => 'Annule', 'expired' => 'Expire'];
    @endphp

    <div class="row">
        {{-- Colonne gauche : patient + actions --}}
        <div class="col-xl-4 col-md-5">

            {{-- Carte patient --}}
            <div class="card">
                <div class="card-body text-center p-4">
                    <div class="avatar-xl mx-auto mb-3 bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:70px;height:70px;">
                        <span class="fs-2 text-primary fw-bold">{{ strtoupper(substr($subscription->patient->name, 0, 1)) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $subscription->patient->name }}</h5>
                    <p class="text-muted mb-3">{{ $subscription->patient->email }}</p>
                    <a href="{{ route('admin.users.show', $subscription->patient) }}" class="btn btn-sm btn-outline-primary">
                        Voir le profil
                    </a>
                </div>
            </div>

            {{-- Actions --}}
            @if ($subscription->status !== 'cancelled')
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Actions</h5></div>
                    <div class="card-body d-flex flex-column gap-2">
                        @if ($subscription->status === 'active')
                            <form method="post" action="{{ route('admin.patient-subscriptions.pause', $subscription) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-warning w-100">Mettre en pause</button>
                            </form>
                        @endif

                        @if ($subscription->status === 'paused')
                            <form method="post" action="{{ route('admin.patient-subscriptions.resume', $subscription) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success w-100">Reprendre</button>
                            </form>
                        @endif

                        <form method="post" action="{{ route('admin.patient-subscriptions.cancel', $subscription) }}"
                            onsubmit="return confirm('Annuler cet abonnement ? Cette action est irreversible.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline-danger w-100">Annuler l'abonnement</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Colonne droite : details --}}
        <div class="col-xl-8 col-md-7">

            {{-- Details du forfait --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Forfait souscrit</h5>
                    <span class="badge bg-{{ $statusBadge[$subscription->status] ?? 'secondary' }} fs-6">
                        {{ $statusLabels[$subscription->status] ?? $subscription->status }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Nom du forfait</span>
                            <span class="fw-semibold">{{ $subscription->plan->name }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Periodicite</span>
                            <span>{{ $subscription->plan->periodicity_label }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Prix</span>
                            <span class="fw-semibold">{{ $subscription->plan->formatted_price }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Renouvellement auto</span>
                            <span>{{ $subscription->auto_renew ? 'Oui' : 'Non' }}</span>
                        </div>
                    </div>

                    <hr>

                    {{-- Caracteristiques du plan --}}
                    <div class="d-flex flex-wrap gap-2">
                        @if ($subscription->plan->includes_home_visits)
                            <span class="badge bg-info">Visites a domicile</span>
                        @endif
                        @if ($subscription->plan->includes_teleconsultation)
                            <span class="badge bg-info">Teleconsultation</span>
                        @endif
                        @if ($subscription->plan->priority_booking)
                            <span class="badge bg-primary">Prioritaire</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Periode en cours --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Periode en cours</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Debut</span>
                            <span>{{ $subscription->current_period_start->format('d/m/Y') }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Fin</span>
                            <span class="{{ $subscription->current_period_end->isPast() ? 'text-danger fw-semibold' : '' }}">
                                {{ $subscription->current_period_end->format('d/m/Y') }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Jours restants</span>
                            <span class="fw-semibold">{{ $subscription->remaining_days }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Consultations utilisees</span>
                        <span class="small fw-semibold">
                            {{ $subscription->consultations_used }} / {{ $subscription->plan->consultations_per_period }}
                        </span>
                    </div>
                    @php($pct = $subscription->plan->consultations_per_period > 0
                        ? round($subscription->consultations_used / $subscription->plan->consultations_per_period * 100)
                        : 0)
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="small text-muted">{{ $pct }}% utilise</span>
                        <span class="small text-muted">{{ $subscription->remaining_consultations }} restante(s)</span>
                    </div>
                </div>
            </div>

            {{-- Paiement --}}
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Paiement</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Methode</span>
                            <span>{{ $subscription->payment_method ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Statut paiement</span>
                            <span>
                                @if ($subscription->payment_status === 'paid')
                                    <span class="badge bg-success">Paye</span>
                                @elseif ($subscription->payment_status === 'pending')
                                    <span class="badge bg-warning">En attente</span>
                                @else
                                    {{ $subscription->payment_status ?? '—' }}
                                @endif
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Date de souscription</span>
                            <span>{{ $subscription->created_at->format('d/m/Y H:i') }}</span>
                        </li>
                        @if ($subscription->cancelled_at)
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-muted">Annule le</span>
                                <span class="text-danger">{{ $subscription->cancelled_at->format('d/m/Y H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
