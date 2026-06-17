<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $orders) {}

    public function collection(): Collection
    {
        return $this->orders->map(fn ($order) => [
            $order->id,
            $order->meli_order_id ?? '—',
            $order->customer?->name ?: ($order->customer?->nickname ?: '—'),
            number_format($order->total_amount, 2, '.', ''),
            $order->status,
            $order->payment_status,
            $order->shipping_status ?? '—',
            $order->order_date?->format('d/m/Y H:i'),
            $order->items->count(),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'ID MeLi',
            'Cliente',
            'Total COP',
            'Estado',
            'Estado Pago',
            'Estado Envío',
            'Fecha Orden',
            'Productos',
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
