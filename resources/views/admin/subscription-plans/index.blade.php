@extends('layouts.master')

@section('title', 'Forfaits')

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Forfaits</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Forfaits</li>
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
                    <h5 class="mb-1">Gestion des forfaits</h5>
                    <span>Definissez les forfaits de consultations pour vos patients.</span>
                </div>
                <a class="btn btn-primary" href="{{ route('admin.subscription-plans.create') }}">Nouveau forfait</a>
            </div>
        </div>
        <div class="card-body">
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-4">
                    <select class="form-select" name="periodicity">
                        <option value="">Toutes les periodicites</option>
                        @foreach (\App\Models\SubscriptionPlan::PERIODICITY_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(request('periodicity') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="active">
                        <option value="">Tous les statuts</option>
                        <option value="1" @selected(request('active') === '1')>Actifs</option>
                        <option value="0" @selected(request('active') === '0')>Inactifs</option>
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
                            <th>Nom</th>
                            <th>Periodicite</th>
                            <th>Consultations</th>
                            <th>Prix</th>
                            <th>Remise</th>
                            <th>Options</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr>
                                <td>
                                    <strong>{{ $plan->name }}</strong>
                                    @if ($plan->is_featured)
                                        <span class="badge bg-warning text-dark ms-1">Populaire</span>
                                    @endif
                                </td>
                                <td>{{ $plan->periodicity_label }}</td>
                                <td>{{ $plan->consultations_per_period }} / periode</td>
                                <td>{{ $plan->formatted_price }}</td>
                                <td>{{ $plan->discount_percent }}%</td>
                                <td>
                                    @if ($plan->includes_home_visits)
                                        <span class="badge bg-info">Domicile</span>
                                    @endif
                                    @if ($plan->includes_teleconsultation)
                                        <span class="badge bg-secondary">Teleconsult</span>
                                    @endif
                                    @if ($plan->priority_booking)
                                        <span class="badge bg-success">Priorite</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($plan->is_active)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.subscription-plans.edit', $plan) }}">Modifier</a>
                                        <form action="{{ route('admin.subscription-plans.toggle', $plan) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $plan->is_active ? 'Desactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Aucun forfait trouve.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $plans->links() }}
        </div>
    </div>
</div>
@endsection
