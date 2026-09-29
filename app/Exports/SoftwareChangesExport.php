<?php

namespace App\Exports;

use Carbon\Carbon;
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

class SoftwareChangesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $changes;

    protected $startDate;

    protected $endDate;

    protected $summary;

    protected $approvalData;

    private int $rowNumber = 0;

    public function __construct($changes, $startDate, $endDate, array $summary = [], $approvalData = null)
    {
        $this->changes = $changes;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->summary = $summary;
        $this->approvalData = $approvalData;
    }

    public function collection()
    {
        return $this->changes;
    }

    public function map($change): array
    {
        $this->rowNumber++;

        $typeLabel = match ($change['type'] ?? '') {
            'added' => 'Software Baru',
            'removed' => 'Software Dihapus',
            'version_changed' => 'Perubahan Versi',
            'returned' => 'Software Kembali',
            default => ucfirst(str_replace('_', ' ', $change['type'] ?? '-')),
        };

        $versionInfo = match ($change['type'] ?? '') {
            'version_changed' => ($change['old_version'] ?? '-').' -> '.($change['new_version'] ?? '-'),
            'added', 'returned' => $change['version'] ?? '-',
            'removed' => $change['version'] ?? '-',
            default => $change['version'] ?? '-',
        };

        $scannedAt = ! empty($change['scanned_at'])
            ? ($change['scanned_at'] instanceof Carbon ? $change['scanned_at']->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($change['scanned_at'])))
            : '-';

        return [
            $this->rowNumber,
            $scannedAt,
            $change['computer_hostname'] ?? '-',
            $change['laboratory_name'] ?? '-',
            $change['raw_name'] ?? '-',
            $typeLabel,
            $versionInfo,
            $change['vendor'] ?? '-',
        ];
    }

    public function headings(): array
    {
        $total = $this->summary['total'] ?? count($this->changes);
        $added = $this->summary['added'] ?? 0;
        $removed = $this->summary['removed'] ?? 0;
        $changed = $this->summary['version_changed'] ?? 0;
        $returned = $this->summary['returned'] ?? 0;

        return [
            ['Rekapitulasi Perubahan Software ('.$this->startDate->format('d/m/Y').' - '.$this->endDate->format('d/m/Y').')'],
            ['Total Perubahan: '.$total.' | Baru: '.$added.' | Dihapus: '.$removed.' | Versi Berubah: '.$changed.' | Muncul Kembali: '.$returned],
            [],
            ['No', 'Waktu Terdeteksi', 'Hostname', 'Laboratorium', 'Nama Software', 'Jenis Perubahan', 'Detail Versi', 'Vendor'],
        ];
    }

    public function title(): string
    {
        return 'Perubahan Software';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Title row styling
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Subtitle summary row
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('4B5563');

        // Header table styling
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'], // Tailwind purple-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Borders and data alignment
        if ($lastRow >= 5) {
            $sheet->getStyle("A5:H{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F5:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
