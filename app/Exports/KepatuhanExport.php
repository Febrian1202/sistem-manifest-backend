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

    /**
     * @param \App\Models\ComplianceReport $report
     */
    public function map($report): array
    {
        $this->rowNumber++;
        
        $statusMap = [
            'Berlisensi' => 'Berlisensi',
            'Grace Period' => 'Grace Period',
            'Tidak Berlisensi' => 'Tidak Berlisensi',
        ];

        $status = $statusMap[$report->status] ?? $report->status;

        return [
            $this->rowNumber,
            $report->computer->hostname ?? '-',
            $report->computer->ip_address ?? '-',
            $report->software_name ?? '-',
            $status,
            $report->scanned_at ? $report->scanned_at->format('d/m/Y H:i') : '-',
            $report->keterangan ?? '-',
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

        // Color-code Status column (Column E)
        foreach ($this->reports as $index => $report) {
            $row = $index + 4;
            $cell = 'E' . $row;
            if ($report->status === 'Berlisensi') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
            } elseif ($report->status === 'Grace Period') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            } elseif ($report->status === 'Tidak Berlisensi') {
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            }
        }

        // Summary row
        $total = $this->reports->count();
        $safe = $this->reports->where('status', 'Berlisensi')->count();
        $warning = $this->reports->where('status', 'Grace Period')->count();
        $critical = $this->reports->where('status', 'Tidak Berlisensi')->count();
        
        $summaryRow = $lastRow + 1;
        $summaryText = "Total Temuan: $total | $safe berlisensi | $warning grace period | $critical tidak berlisensi";
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
