<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LisensiExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $licenses;

    protected $startDate;

    protected $endDate;

    protected $approvalData;

    public function __construct($licenses, $startDate, $endDate, $approvalData = null)
    {
        $this->licenses = $licenses;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->approvalData = $approvalData;
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
        if ($license->remaining <= 0) {
            $status = 'Penuh';
        } elseif ($license->usage_pct >= 80) {
            $status = 'Hampir Habis';
        }
        if ($license->expiry_date && $license->expiry_date->lt(now())) {
            $status = 'Kedaluwarsa';
        }

        $category = $license->catalog->category ?? '-';
        $categoryLabel = match ($category) {
            'Commercial' => 'Komersial',
            'Open Source' => 'Sumber Terbuka',
            'Freeware' => 'Gratis (Freeware)',
            default => $category,
        };

        return [
            $this->rowNumber,
            $license->catalog->normalized_name ?? '-',
            $categoryLabel,
            $license->quota_limit,
            $license->used_count,
            $license->remaining,
            $license->usage_pct.'%',
            $status,
            $license->expiry_date ? $license->expiry_date->format('d/m/Y') : '-',
        ];
    }

    public function headings(): array
    {
        return [
            ['Status Lisensi ('.$this->startDate->format('d/m/Y').' - '.$this->endDate->format('d/m/Y').')'],
            [],
            ['No', 'Nama Software', 'Tipe Lisensi', 'Total Kuota', 'Terpakai', 'Sisa', '% Penggunaan', 'Status', 'Kedaluwarsa'],
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
            if ($license->remaining <= 0) {
                $status = 'Penuh';
            } elseif ($license->usage_pct >= 80) {
                $status = 'Hampir Habis';
            }
            if ($license->expiry_date && $license->expiry_date->lt(now())) {
                $status = 'Kedaluwarsa';
            }

            if ($status === 'Penuh' || $status === 'Kedaluwarsa') {
                $sheet->getStyle('A'.$row.':I'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            } elseif ($status === 'Hampir Habis') {
                $sheet->getStyle('A'.$row.':I'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            }
        }

        if ($this->approvalData && $this->approvalData->isNotEmpty()) {
            $lastRow = $sheet->getHighestRow();
            $currentRow = $lastRow + 2;
            $sheet->setCellValue('A'.$currentRow, 'STATUS VERIFIKASI PENANGGUNG JAWAB LABORATORIUM');
            $sheet->getStyle('A'.$currentRow)->getFont()->setBold(true);

            $currentRow++;
            $sheet->setCellValue('A'.$currentRow, 'Laboratorium');
            $sheet->setCellValue('B'.$currentRow, 'Status');
            $sheet->setCellValue('C'.$currentRow, 'Diverifikasi Oleh');
            $sheet->setCellValue('D'.$currentRow, 'Tanggal Verifikasi');
            $sheet->setCellValue('E'.$currentRow, 'Catatan Evaluasi');
            $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');

            foreach ($this->approvalData as $approval) {
                $currentRow++;
                $sheet->setCellValue('A'.$currentRow, ($approval->laboratory->name ?? '-').' ('.($approval->laboratory->code ?? '-').')');
                $sheet->setCellValue('B'.$currentRow, $approval->status === 'approved' ? 'Disetujui' : ucfirst($approval->status));
                $sheet->setCellValue('C'.$currentRow, $approval->reviewer?->name ?? '-');
                $sheet->setCellValue('D'.$currentRow, $approval->reviewed_at ? $approval->reviewed_at->format('d/m/Y H:i') : '-');
                $sheet->setCellValue('E'.$currentRow, $approval->notes ?: '-');
            }
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }
}
