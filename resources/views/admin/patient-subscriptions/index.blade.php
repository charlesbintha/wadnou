@extends('layouts.master')

@section('title', 'Abonnements patients')

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Abonnements patients</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Abonnements</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php
        $statusBadge  = ['active' => 'success', 'paused' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'];
        $statusLabels = ['active' => 'Actif', 'paused' => 'En pause', 'cancelled' => 'Annule', 'expired' => 'Expire'];
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des abonnements</h5>
            <a class="btn btn-success btn-sm" href="{{ route('admin.patient-subscriptions.export', request()->query()) }}">
                <i data-feather="download" style="width:14px;height:14px;"></i> Excel
            </a>
        </div>
        <div class="card-body">
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-4">
                    <input class="form-control" type="text" name="q" value="{{ request('q') }}"
                        placeholder="Nom ou email du patient...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="plan_id">
                        <option value="">Tous les forfaits</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        @foreach ($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Forfait</th>
                            <th>Statut</th>
                            <th>Consultations</th>
                            <th>Expire le</th>
                            <th>Paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscriptions as $sub)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.users.show', $sub->patient) }}" class="fw-semibold text-decoration-none">
                                        {{ $sub->patient->name }}
                                    </a>
                                    <div class="text-muted small">{{ $sub->patient->email }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $sub->plan->name }}</span>
                                    <div class="text-muted small">{{ $sub->plan->periodicity_label }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusBadge[$sub->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$sub->status] ?? $sub->status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $sub->consultations_used }} / {{ $sub->plan->consultations_per_period }}
                                    @php($pct = $sub->plan->consultations_per_period > 0 ? round($sub->consultations_used / $sub->plan->consultations_per_period * 100) : 0)
                                    <div class="progress mt-1" style="height:4px;">
                                        <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    {{ $sub->current_period_end->format('d/m/Y') }}
                                    @if ($sub->current_period_end->isPast())
                                        <span class="badge bg-danger ms-1">Expire</span>
                                    @elseif ($sub->current_period_end->diffInDays(now()) <= 7)
                                        <span class="badge bg-warning ms-1">Bientot</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sub->payment_status === 'paid')
                                        <span class="badge bg-success">Paye</span>
                                    @elseif ($sub->payment_status === 'pending')
                                        <span class="badge bg-warning">En attente</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $sub->payment_status ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.patient-subscriptions.show', $sub) }}" class="btn btn-sm btn-primary">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucun abonnement trouve.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
@endsection
