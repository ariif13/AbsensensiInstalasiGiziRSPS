<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function headings(): array
    {
        return array_merge(['email', 'nama', 'bulan', 'tahun'], range(1, 31));
    }

    public function array(): array
    {
        return [
            [
                'user1@example.com',
                'Nama Karyawan 1',
                3,
                2026,
                'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'OFF', '',
            ],
            [
                'user2@example.com',
                'Nama Karyawan 2',
                3,
                2026,
                'M', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'S', 'M', 'OFF', 'P', 'P', 'S', 'OFF',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Schedule Template';
    }
}
