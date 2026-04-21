<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SoftwareExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $softwares;
    protected $startDate;
    protected $endDate;

    public function __construct($softwares, $startDate, $endDate)
    {
        $this->softwares = $softwares;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->softwares;
    }

    private int $rowNumber = 0;

    public function map($sw): array
    {
        $this->rowNumber++;
        
        $hasLicense = $sw->catalog && $sw->catalog->licenses->count() > 0;
        
        if ($sw->category === 'Commercial') {
            $status = $hasLicense ? 'Berlisensi' : 'Tidak Berlisensi';
        } else {
            $status = 'Gratis / Tidak Perlu';
        }

        return [
            $this->rowNumber,
            $sw->normalized_name,
            $sw->version,
            $sw->computer_count,
            $status,
            $sw->category,
        ];
    }

    public function headings(): array
    {
        return [
            ['Inventaris Software (' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y') . ')'],
            [],
            ['No', 'Nama Software', 'Versi', 'Jumlah Komputer', 'Status Lisensi', 'Kategori']
        ];
    }

    public function title(): string
    {
        return 'Inventaris Software';
    }

    public function styles(Worksheet $sheet)
    {
        // Highlight "Tidak Berlisensi" rows
        foreach ($this->softwares as $index => $sw) {
            $hasLicense = $sw->catalog && $sw->catalog->licenses->count() > 0;
            $isCommercial = $sw->category === 'Commercial';
            
            if ($isCommercial && !$hasLicense) {
                $row = $index + 4;
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
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
