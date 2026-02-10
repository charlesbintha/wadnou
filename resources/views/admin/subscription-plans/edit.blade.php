@extends('layouts.master')

@section('title', 'Modifier forfait')

@section('main_content')
<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                <h3>Modifier forfait</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.subscription-plans.index') }}">Forfaits</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5>Modifier le forfait : {{ $plan->name }}</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('admin.subscription-plans.update', $plan) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name">Nom du forfait</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $plan->name) }}" required>
                        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="periodicity">Periodicite</label>
                        <select class="form-select" id="periodicity" name="periodicity" required>
                            @foreach (\App\Models\SubscriptionPlan::PERIODICITY_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected(old('periodicity', $plan->periodicity) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('periodicity')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $plan->description) }}</textarea>
                        @error('description')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="consultations_per_period">Consultations par periode</label>
                        <input class="form-control" id="consultations_per_period" name="consultations_per_period" type="number" min="1" max="100" value="{{ old('consultations_per_period', $plan->consultations_per_period) }}" required>
                        @error('consultations_per_period')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="price">Prix (FCFA)</label>
                        <input class="form-control" id="price" name="price" type="number" min="0" value="{{ old('price', $plan->price) }}" required>
                        @error('price')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="discount_percent">Remise (%)</label>
                        <input class="form-control" id="discount_percent" name="discount_percent" type="number" min="0" max="100" value="{{ old('discount_percent', $plan->discount_percent) }}" required>
                        @error('discount_percent')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includes_home_visits" name="includes_home_visits" value="1" @checked(old('includes_home_visits', $plan->includes_home_visits))>
                            <label class="form-check-label" for="includes_home_visits">
                                Inclut les visites a domicile
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includes_teleconsultation" name="includes_teleconsultation" value="1" @checked(old('includes_teleconsultation', $plan->includes_teleconsultation))>
                            <label class="form-check-label" for="includes_teleconsultation">
                                Inclut la teleconsultation
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="priority_booking" name="priority_booking" value="1" @checked(old('priority_booking', $plan->priority_booking))>
                            <label class="form-check-label" for="priority_booking">
                                Reservation prioritaire
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="display_order">Ordre d'affichage</label>
                        <input class="form-control" id="display_order" name="display_order" type="number" min="0" value="{{ old('display_order', $plan->display_order) }}">
                        @error('display_order')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                            <label class="form-check-label" for="is_active">
                                Forfait actif
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured))>
                            <label class="form-check-label" for="is_featured">
                                Mettre en avant (Populaire)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.subscription-plans.index') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    @if ($plan->activeSubscriptions()->count() === 0)
    <div class="card mt-3">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Zone dangereuse</h5>
        </div>
        <div class="card-body">
            <p>Supprimer ce forfait de maniere permanente. Cette action est irreversible.</p>
            <form method="post" action="{{ route('admin.subscription-plans.destroy', $plan) }}" onsubmit="return confirm('Etes-vous sur de vouloir supprimer ce forfait ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Supprimer le forfait</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
