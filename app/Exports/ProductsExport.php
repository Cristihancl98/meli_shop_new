<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $products) {}

    public function collection(): Collection
    {
        return $this->products->map(fn ($product) => [
            $product->meli_item_id ?? '—',
            $product->title,
            $product->category?->name ?? '—',
            number_format($product->price, 2, '.', ''),
            $product->stock,
            $product->status,
            $product->statistics?->quantity_sold ?? 0,
            number_format($product->statistics?->total_revenue ?? 0, 2, '.', ''),
            $product->statistics?->last_sale_date?->format('d/m/Y') ?? '—',
            $product->last_sync?->format('d/m/Y H:i') ?? '—',
        ]);
    }

    public function headings(): array
    {
        return [
            'ID MeLi',
            'Título',
            'Categoría',
            'Precio COP',
            'Stock',
            'Estado',
            'Unidades Vendidas',
            'Ingresos COP',
            'Última Venta',
            'Última Sincronización',
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
