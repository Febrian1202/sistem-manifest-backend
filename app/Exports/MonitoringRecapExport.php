<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringRecapExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $computers;

    protected $startDate;

    protected $endDate;

    protected $summary;

    protected $approvalData;

    private int $rowNumber = 0;

    public function __construct($computers, $startDate, $endDate, array $summary = [], $approvalData = null)
    {
        $this->computers = $computers;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->summary = $summary;
        $this->approvalData = $approvalData;
    }

    public function collection()
    {
        return $this->computers;
    }

    public function map($computer): array
    {
        $this->rowNumber++;

        $latestScan = $computer->scanSessions->first();
        $latestStatus = $latestScan ? match ($latestScan->status) {
            'completed' => 'Berhasil',
            'failed' => 'Gagal',
            default => ucfirst($latestScan->status),
        } : 'Belum Ada';

        $lastScannedAt = $latestScan && $latestScan->started_at
            ? $latestScan->started_at->format('d/m/Y H:i')
            : ($computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i') : '-');

        return [
            $this->rowNumber,
            $computer->hostname,
            $computer->laboratory?->name ?? 'Belum Ditugaskan',
            $computer->ip_address ?? '-',
            $computer->os_name ?? '-',
            (int) ($computer->total_scans ?? 0),
            (int) ($computer->successful_scans ?? 0),
            (int) ($computer->failed_scans ?? 0),
            $lastScannedAt,
            $latestStatus,
        ];
    }

    public function headings(): array
    {
        $totalComp = $this->summary['total_computers'] ?? count($this->computers);
        $totalScans = $this->summary['total_scans'] ?? 0;
        $successScans = $this->summary['successful_scans'] ?? 0;
        $failedScans = $this->summary['failed_scans'] ?? 0;
        $rate = $this->summary['success_rate'] ?? 0;

        return [
            ['Rekapitulasi Monitoring Berkala ('.$this->startDate->format('d/m/Y').' - '.$this->endDate->format('d/m/Y').')'],
            ['Total Komputer: '.$totalComp.' | Total Scan: '.$totalScans.' | Berhasil: '.$successScans.' | Gagal: '.$failedScans.' | Tingkat Keberhasilan: '.$rate.'%'],
            [],
            ['No', 'Hostname', 'Laboratorium', 'Alamat IP', 'Sistem Operasi', 'Total Scan', 'Scan Berhasil', 'Scan Gagal', 'Scan Terakhir', 'Status Terakhir'],
        ];
    }

    public function title(): string
    {
        return 'Rekap Monitoring';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Title row styling
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Subtitle summary row
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('4B5563');

        // Header table styling
        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Tailwind blue-800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Borders and data alignment
        if ($lastRow >= 5) {
            $sheet->getStyle("A5:J{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F5:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I5:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
