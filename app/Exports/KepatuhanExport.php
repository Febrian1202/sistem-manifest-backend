<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KepatuhanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $reports;
    protected $startDate;
    protected $endDate;

    public function __construct($reports, $startDate, $endDate)
    {
        $this->reports = $reports;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->reports;
    }

    private int $rowNumber = 0;

    public function map($computer): array
    {
        $this->rowNumber++;
        $report = $computer->latestComplianceReport;
        
        $statusMap = [
            'Safe' => 'Berlisensi',
            'Warning' => 'Grace Period',
            'Critical' => 'Tidak Berlisensi',
        ];

        $status = $report ? ($statusMap[$report->status] ?? $report->status) : 'Belum Diperiksa';

        // Extract software names from violation_details JSON
        $swNames = $report && is_array($report->violation_details)
            ? collect($report->violation_details)->pluck('software_name')->filter()->implode(', ')
            : '-';

        return [
            $this->rowNumber,
            $computer->hostname ?? '-',
            $computer->ip_address ?? '-',
            \Illuminate\Support\Str::limit($swNames, 60),
            $status,
            $report && $report->scanned_at ? $report->scanned_at->format('d/m/Y H:i') : '-',
            $report ? "Pelanggaran: $report->unlicensed_count | Blacklist: $report->blacklisted_count" : "-",
        ];
    }

    public function headings(): array
    {
        return [
            ['Kepatuhan Lisensi (' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y') . ')'],
            [],
            ['No', 'Nama Komputer', 'IP Address', 'Nama Software', 'Status', 'Tanggal Deteksi', 'Keterangan']
        ];
    }

    public function title(): string
    {
        return 'Kepatuhan Lisensi';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Color-code Status column
        foreach ($this->reports as $index => $computer) {
            $report = $computer->latestComplianceReport;
            if (!$report) continue;

            $row = $index + 4;
            $cell = 'E' . $row;
            if ($report->status === 'Safe') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
            } elseif ($report->status === 'Warning') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            } elseif ($report->status === 'Critical') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            }
        }

        // Summary row
        $total = $this->reports->count();
        $withReport = $this->reports->filter(fn($c) => $c->latestComplianceReport)->count();
        $safe = $this->reports->filter(fn($c) => $c->latestComplianceReport?->status === 'Safe')->count();
        $warning = $this->reports->filter(fn($c) => $c->latestComplianceReport?->status === 'Warning')->count();
        $critical = $withReport - $safe - $warning;
        $noReport = $total - $withReport;
        
        $summaryRow = $lastRow + 1;
        $summaryText = "Total: $total | $safe berlisensi | $warning grace period | $critical tidak berlisensi | $noReport belum diperiksa";
        $sheet->setCellValue('A' . $summaryRow, $summaryText);
        $sheet->mergeCells('A' . $summaryRow . ':G' . $summaryRow);
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

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
