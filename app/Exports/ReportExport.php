<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromView, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $viewName;
    protected $data;

    public function __construct($viewName, $data)
    {
        //
        $this->viewName = $viewName;
        $this->data = $data;
    }

    public function view(): View
    {
        // Menggunakan view blad yang sama dengan PDF
        return view('exports.' . $this->viewName, $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Membaut baris pertama (Header) menjadi bold di excel
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
