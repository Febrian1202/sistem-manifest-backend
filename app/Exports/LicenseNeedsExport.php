<?php

namespace App\Exports;

use App\Exports\Sheets\LicenseNeedsFacultySheet;
use App\Exports\Sheets\LicenseNeedsProcurementSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LicenseNeedsExport implements WithMultipleSheets
{
    protected array $analysis;

    public function __construct(array $analysis)
    {
        $this->analysis = $analysis;
    }

    public function sheets(): array
    {
        return [
            new LicenseNeedsFacultySheet($this->analysis['faculty_distributions'] ?? collect()),
            new LicenseNeedsProcurementSheet(
                $this->analysis['procurement_insights'] ?? collect(),
                $this->analysis['summary'] ?? []
            ),
        ];
    }
}
