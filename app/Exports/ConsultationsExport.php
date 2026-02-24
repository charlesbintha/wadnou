<?php

namespace App\Exports;

use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsultationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $query = ConsultationRequest::with(['patient', 'doctor']);

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->input('status'));
        }

        return $query->orderByDesc('requested_at');
    }

    public function headings(): array
    {
        return ['ID', 'Patient', 'Email patient', 'Medecin', 'Statut', 'Date demande', 'Date acceptation', 'Date cloture'];
    }

    public function map($c): array
    {
        $statusLabels = [
            'pending'  => 'En attente',
            'assigned' => 'Assigne',
            'accepted' => 'Accepte',
            'rejected' => 'Refuse',
            'closed'   => 'Clos',
            'canceled' => 'Annule',
            'expired'  => 'Expire',
        ];

        return [
            $c->id,
            optional($c->patient)->name ?? '—',
            optional($c->patient)->email ?? '—',
            optional($c->doctor)->name ?? 'Non assigne',
            $statusLabels[$c->status] ?? $c->status,
            $c->requested_at?->format('d/m/Y H:i') ?? '—',
            $c->accepted_at?->format('d/m/Y H:i') ?? '—',
            $c->closed_at?->format('d/m/Y H:i') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
