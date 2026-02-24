<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $query = User::query();

        if ($this->request->filled('q')) {
            $term = trim($this->request->input('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($this->request->filled('role')) {
            $query->where('role', $this->request->input('role'));
        }

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->input('status'));
        }

        return $query->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return ['ID', 'Nom', 'Email', 'Telephone', 'Role', 'Statut', 'Langue', 'Inscrit le'];
    }

    public function map($user): array
    {
        $roleLabels   = ['patient' => 'Patient', 'doctor' => 'Medecin', 'admin' => 'Admin'];
        $statusLabels = ['pending' => 'En attente', 'active' => 'Actif', 'suspended' => 'Suspendu'];

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? '',
            $roleLabels[$user->role] ?? $user->role,
            $statusLabels[$user->status] ?? $user->status,
            strtoupper($user->locale),
            $user->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
