@extends('layouts.master')

@section('title', 'Tableau de bord')

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Tableau de bord</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Tableau de bord</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">

    {{-- Titre de section --}}
    <h6 class="text-muted text-uppercase fw-bold mb-3 small">Utilisateurs</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                        <i data-feather="users" class="text-primary" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Total utilisateurs</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['users_total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-info bg-opacity-10 rounded-3 p-3">
                        <i data-feather="user" class="text-info" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Patients</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['patients_total'] }}</h4>
                        <span class="text-success small">+{{ $metrics['patients_new_month'] }} ce mois</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                        <i data-feather="briefcase" class="text-success" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Medecins</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['doctors_total'] }}</h4>
                        <span class="text-muted small">{{ $metrics['doctors_active'] }} actifs</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-3 p-3">
                        <i data-feather="file-text" class="text-warning" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Documents en attente</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['doctor_docs_pending'] }}</h4>
                        @if ($metrics['doctor_docs_pending'] > 0)
                            <a href="{{ route('admin.doctor-documents.index') }}" class="text-warning small">Traiter &rarr;</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h6 class="text-muted text-uppercase fw-bold mb-3 small">Consultations & Rendez-vous</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                        <i data-feather="activity" class="text-primary" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Total consultations</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['consultations_total'] }}</h4>
                        <span class="text-muted small">{{ $metrics['consultations_month'] }} ce mois</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 rounded-3 p-3">
                        <i data-feather="clock" class="text-danger" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Consultations en attente</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['consultations_pending'] }}</h4>
                        @if ($metrics['consultations_pending'] > 0)
                            <a href="{{ route('admin.consultations.index', ['status' => 'pending']) }}" class="text-danger small">Voir &rarr;</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                        <i data-feather="calendar" class="text-success" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Rendez-vous aujourd'hui</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['appointments_today'] }}</h4>
                        <span class="text-muted small">{{ $metrics['appointments_month'] }} ce mois</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            {{-- Placeholder alerte SLA --}}
            <a href="{{ route('admin.sla.index') }}" class="text-decoration-none">
                <div class="card h-100 border-warning">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-3 p-3">
                            <i data-feather="alert-triangle" class="text-warning" style="width:24px;height:24px;"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1">Suivi SLA</p>
                            <span class="text-warning fw-semibold">Voir le rapport &rarr;</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <h6 class="text-muted text-uppercase fw-bold mb-3 small">Abonnements</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                        <i data-feather="check-circle" class="text-success" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Abonnements actifs</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['subscriptions_active'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                        <i data-feather="trending-up" class="text-primary" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Nouveaux ce mois</p>
                        <h4 class="mb-0 fw-bold">{{ $metrics['subscriptions_month'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                        <i data-feather="dollar-sign" class="text-success" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Revenus ce mois</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($metrics['revenue_month'], 0, ',', ' ') }} FCFA</h4>
                        <span class="text-muted small">paiements confirmes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableaux recents --}}
    <div class="row g-3">
        {{-- Dernieres consultations --}}
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dernieres consultations</h5>
                    <a href="{{ route('admin.consultations.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                </div>
                <div class="card-body p-0">
                    @php
                        $cBadge = ['pending' => 'warning', 'assigned' => 'info', 'accepted' => 'primary', 'rejected' => 'danger', 'closed' => 'success', 'canceled' => 'secondary', 'expired' => 'dark'];
                        $cLabels = ['pending' => 'En attente', 'assigned' => 'Assigne', 'accepted' => 'Accepte', 'rejected' => 'Refuse', 'closed' => 'Clos', 'canceled' => 'Annule', 'expired' => 'Expire'];
                    @endphp
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Medecin</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentConsultations as $c)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.consultations.show', $c) }}" class="text-decoration-none">
                                                {{ optional($c->patient)->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td>{{ optional($c->doctor)->name ?? 'Non assigne' }}</td>
                                        <td><span class="badge bg-{{ $cBadge[$c->status] ?? 'secondary' }}">{{ $cLabels[$c->status] ?? $c->status }}</span></td>
                                        <td class="text-muted small">{{ $c->requested_at?->format('d/m/Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Aucune consultation.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Derniers abonnements --}}
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Derniers abonnements</h5>
                    <a href="{{ route('admin.patient-subscriptions.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                </div>
                <div class="card-body p-0">
                    @php
                        $sBadge = ['active' => 'success', 'paused' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'];
                        $sLabels = ['active' => 'Actif', 'paused' => 'En pause', 'cancelled' => 'Annule', 'expired' => 'Expire'];
                    @endphp
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Forfait</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSubscriptions as $s)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.patient-subscriptions.show', $s) }}" class="text-decoration-none">
                                                {{ optional($s->patient)->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td>{{ optional($s->plan)->name ?? '—' }}</td>
                                        <td><span class="badge bg-{{ $sBadge[$s->status] ?? 'secondary' }}">{{ $sLabels[$s->status] ?? $s->status }}</span></td>
                                        <td class="text-muted small">{{ $s->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Aucun abonnement.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
