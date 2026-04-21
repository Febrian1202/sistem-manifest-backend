<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KomputerExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $computers;
    protected $startDate;
    protected $endDate;

    public function __construct($computers, $startDate, $endDate)
    {
        $this->computers = $computers;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->computers;
    }

    public function map($computer): array
    {
        static $no = 0;
        $no++;
        
        $status = $computer->last_seen_at && $computer->last_seen_at->lt(now()->subDays(7)) ? 'Tidak Aktif' : 'Aktif';

        return [
            $no,
            $computer->hostname,
            $computer->ip_address,
            $computer->mac_address,
            $computer->processor,
            $computer->ram_gb . ' GB',
            $computer->os_name,
            $status,
            $computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i') : '-',
            $computer->softwares_count,
        ];
    }

    public function headings(): array
    {
        return [
            ['Inventaris Komputer (' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y') . ')'],
            [],
            ['No', 'Hostname', 'IP Address', 'MAC Address', 'CPU', 'RAM', 'OS', 'Status', 'Last Seen', 'Jumlah Software']
        ];
    }

    public function title(): string
    {
        return 'Inventaris Komputer';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Highlight "Tidak Aktif" rows
        foreach ($this->computers as $index => $computer) {
            $status = $computer->last_seen_at && $computer->last_seen_at->lt(now()->subDays(7)) ? 'Tidak Aktif' : 'Aktif';
            if ($status === 'Tidak Aktif') {
                $row = $index + 4; // Start after 3 header rows
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            }
        }

        // Summary row
        $total = $this->computers->count();
        $aktif = $this->computers->filter(fn($c) => !$c->last_seen_at || $c->last_seen_at->gt(now()->subDays(7)))->count();
        $tidakAktif = $total - $aktif;
        
        $summaryRow = $lastRow + 1;
        $sheet->setCellValue('A' . $summaryRow, "Total: $total komputer | $aktif aktif | $tidakAktif tidak aktif");
        $sheet->mergeCells('A' . $summaryRow . ':J' . $summaryRow);
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
