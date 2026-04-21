<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LisensiExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $licenses;
    protected $startDate;
    protected $endDate;

    public function __construct($licenses, $startDate, $endDate)
    {
        $this->licenses = $licenses;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->licenses;
    }

    private int $rowNumber = 0;

    public function map($license): array
    {
        $this->rowNumber++;
        
        $status = 'Tersedia';
        if ($license->remaining <= 0) $status = 'Penuh';
        elseif ($license->usage_pct >= 80) $status = 'Hampir Habis';
        if ($license->expiry_date && $license->expiry_date->lt(now())) $status = 'Kedaluwarsa';

        return [
            $this->rowNumber,
            $license->catalog->normalized_name ?? '-',
            $license->catalog->category ?? '-',
            $license->quota_limit,
            $license->used_count,
            $license->remaining,
            $license->usage_pct . '%',
            $status,
            $license->expiry_date ? $license->expiry_date->format('d/m/Y') : '-',
        ];
    }

    public function headings(): array
    {
        return [
            ['Status Lisensi (' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y') . ')'],
            [],
            ['No', 'Nama Software', 'Tipe Lisensi', 'Total Seat', 'Terpakai', 'Sisa', '% Penggunaan', 'Status', 'Expired']
        ];
    }

    public function title(): string
    {
        return 'Status Lisensi';
    }

    public function styles(Worksheet $sheet)
    {
        foreach ($this->licenses as $index => $license) {
            $row = $index + 4;
            
            $status = 'Tersedia';
            if ($license->remaining <= 0) $status = 'Penuh';
            elseif ($license->usage_pct >= 80) $status = 'Hampir Habis';
            if ($license->expiry_date && $license->expiry_date->lt(now())) $status = 'Kedaluwarsa';

            if ($status === 'Penuh' || $status === 'Kedaluwarsa') {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            } elseif ($status === 'Hampir Habis') {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            }
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }
}
