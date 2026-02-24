<?php

namespace App\Exports;

use App\Models\PatientSubscription;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientSubscriptionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $query = PatientSubscription::with(['patient', 'plan']);

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->input('status'));
        }

        if ($this->request->filled('plan_id')) {
            $query->where('plan_id', $this->request->input('plan_id'));
        }

        if ($this->request->filled('q')) {
            $term = trim($this->request->input('q'));
            $query->whereHas('patient', function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID', 'Patient', 'Email patient', 'Forfait', 'Periodicite',
            'Statut', 'Debut periode', 'Fin periode',
            'Consultations utilisees', 'Consultations totales',
            'Methode paiement', 'Statut paiement', 'Auto-renouvellement', 'Date souscription',
        ];
    }

    public function map($s): array
    {
        $statusLabels = [
            'active'    => 'Actif',
            'paused'    => 'En pause',
            'cancelled' => 'Annule',
            'expired'   => 'Expire',
        ];

        return [
            $s->id,
            optional($s->patient)->name ?? '—',
            optional($s->patient)->email ?? '—',
            optional($s->plan)->name ?? '—',
            optional($s->plan)->periodicity_label ?? '—',
            $statusLabels[$s->status] ?? $s->status,
            $s->current_period_start->format('d/m/Y'),
            $s->current_period_end->format('d/m/Y'),
            $s->consultations_used,
            optional($s->plan)->consultations_per_period ?? '—',
            $s->payment_method ?? '—',
            $s->payment_status ?? '—',
            $s->auto_renew ? 'Oui' : 'Non',
            $s->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
