<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $headings;

    public function __construct($data)
    {
        $this->data = collect($data);
        
        // Extract headings from first row if data exists
        if ($this->data->isNotEmpty()) {
            $this->headings = array_keys($this->data->first());
        } else {
            $this->headings = [];
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->headings as $index => $heading) {
            $column = chr(65 + $index); // A, B, C, etc.
            $widths[$column] = 20; // Default width
        }
        return $widths;
    }
}

// File location: app/Exports/GenericExport.php