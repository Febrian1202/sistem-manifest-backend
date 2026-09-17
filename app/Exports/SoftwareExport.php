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

class SoftwareExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $softwares;

    protected $startDate;

    protected $endDate;

    protected $approvalData;

    public function __construct($softwares, $startDate, $endDate, $approvalData = null)
    {
        $this->softwares = $softwares;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->approvalData = $approvalData;
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

        $categoryLabel = match ($sw->category) {
            'Commercial' => 'Komersial',
            'Open Source' => 'Sumber Terbuka',
            'Freeware' => 'Gratis (Freeware)',
            default => $sw->category ?? '-',
        };

        return [
            $this->rowNumber,
            $sw->normalized_name,
            $sw->version,
            $sw->computer_count,
            $status,
            $categoryLabel,
        ];
    }

    public function headings(): array
    {
        return [
            ['Inventaris Software ('.$this->startDate->format('d/m/Y').' - '.$this->endDate->format('d/m/Y').')'],
            [],
            ['No', 'Nama Software', 'Versi', 'Jumlah Komputer', 'Status Lisensi', 'Kategori'],
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

            if ($isCommercial && ! $hasLicense) {
                $row = $index + 4;
                $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
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
