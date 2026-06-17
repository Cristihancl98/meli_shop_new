<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $customers) {}

    public function collection(): Collection
    {
        return $this->customers->map(fn ($customer) => [
            $customer->meli_customer_id ?? '—',
            $customer->name ?: $customer->nickname,
            $customer->nickname ?? '—',
            $customer->email ?? '—',
            $customer->phone ?? '—',
            $customer->orders_count ?? 0,
            number_format($customer->orders_sum_total_amount ?? 0, 2, '.', ''),
            $customer->orders_count > 0
                ? number_format(($customer->orders_sum_total_amount ?? 0) / $customer->orders_count, 2, '.', '')
                : '0.00',
            $customer->created_at->format('d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID MeLi',
            'Nombre',
            'Nickname',
            'Email',
            'Teléfono',
            'Total Órdenes',
            'Total Gastado COP',
            'Ticket Promedio COP',
            'Fecha Registro',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3483FA'],
            ]],
        ];
    }
}
