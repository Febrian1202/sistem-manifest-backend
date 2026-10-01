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

class LicenseNeedsProcurementSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected Collection $procurementInsights;

    protected array $summary;

    public function __construct(Collection $procurementInsights, array $summary)
    {
        $this->procurementInsights = $procurementInsights;
        $this->summary = $summary;
    }

    public function collection(): Collection
    {
        return $this->procurementInsights->map(function ($item, $index) {
            $facultyDeficits = empty($item['faculty_deficits'])
                ? '-'
                : collect($item['faculty_deficits'])
                    ->map(fn ($f) => ($f['faculty_code'] ?? $f['faculty_name']).': +'.$f['deficit'])
                    ->implode(', ');

            $facultySurpluses = empty($item['faculty_surpluses'])
                ? '-'
                : collect($item['faculty_surpluses'])
                    ->map(fn ($f) => ($f['faculty_code'] ?? $f['faculty_name']).': -'.$f['surplus'])
                    ->implode(', ');

            return [
                'no' => $index + 1,
                'software' => $item['software_name'] ?? '-',
                'owned' => $item['owned'] ?? 0,
                'allocated' => $item['allocated'] ?? 0,
                'installed' => $item['installed'] ?? 0,
                'deficit' => $item['net_deficit'] ?? 0,
                'surplus' => $item['net_surplus'] ?? 0,
                'deficits_spread' => $facultyDeficits,
                'surpluses_spread' => $facultySurpluses,
                'recommendation' => $item['recommendation'] ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['Analisis & Rekomendasi Pengadaan Lisensi Software — Universitas Sembilanbelas November Kolaka'],
            [
                sprintf(
                    'Ringkasan: %d Software Komersial | Total Hak USN: %d seat | Total Alokasi: %d seat | Terpasang: %d unit | Defisit Bersih: %d unit',
                    $this->summary['total_commercial_software'] ?? 0,
                    $this->summary['total_owned'] ?? 0,
                    $this->summary['total_allocated'] ?? 0,
                    $this->summary['total_installed'] ?? 0,
                    $this->summary['total_deficit'] ?? 0
                ),
            ],
            [],
            [
                'No',
                'Nama Software',
                'Hak USN (Owned)',
                'Total Dialokasikan',
                'Total Terpasang',
                'Defisit Univ',
                'Surplus Univ',
                'Sebaran Defisit Fakultas',
                'Sebaran Surplus Fakultas',
                'Rekomendasi Pengadaan / Redistribusi',
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekomendasi Pengadaan';
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Title rows
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('555555');

        // Header table styling
        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'], // Teal dark
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow >= 5) {
            $sheet->getStyle("A5:J{$highestRow}")->applyFromArray([
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

            // Center align numeric columns
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
