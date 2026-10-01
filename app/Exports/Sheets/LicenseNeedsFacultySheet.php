<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LicenseNeedsFacultySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected Collection $facultyDistributions;

    public function __construct(Collection $facultyDistributions)
    {
        $this->facultyDistributions = $facultyDistributions;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $no = 1;

        foreach ($this->facultyDistributions as $dist) {
            $facultyName = $dist['faculty']->name ?? '-';
            foreach ($dist['breakdown'] as $item) {
                $rows->push([
                    'no' => $no++,
                    'faculty' => $facultyName,
                    'software' => $item['software_name'] ?? $item['normalized_name'] ?? '-',
                    'allocated' => $item['allocated'] ?? 0,
                    'installed' => $item['installed'] ?? 0,
                    'deficit' => $item['deficit'] ?? 0,
                    'surplus' => $item['surplus'] ?? 0,
                    'status' => $item['status'] ?? '-',
                    'recommendation' => $item['recommendation'] ?? '-',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Laporan Kebutuhan Lisensi per Fakultas — Universitas Sembilanbelas November Kolaka'],
            ['Tanggal Cetak: '.now()->format('d/m/Y H:i')],
            [],
            [
                'No',
                'Fakultas',
                'Nama Software',
                'Alokasi Kuota',
                'Unit Terpasang',
                'Defisit',
                'Surplus',
                'Status Kepatuhan',
                'Rekomendasi Tindakan',
            ],
        ];
    }

    public function title(): string
    {
        return 'Distribusi Fakultas';
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Title row
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('555555');

        // Header table styling
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow >= 5) {
            $sheet->getStyle("A5:I{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center align columns No, Alokasi, Terpasang, Defisit, Surplus
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H5:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
