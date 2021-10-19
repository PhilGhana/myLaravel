<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ExcelExport implements FromArray, WithStrictNullComparison, WithHeadings, WithEvents
{
    use Exportable;

    protected $res;
    protected $header;

    public function __construct($res, $header)
    {
        $this->res = $res;
        $this->header = $header;
        // dd($this->res);
    }

    // /**
    //  * @return \Illuminate\Support\Collection
    //  */
    // public function collection()
    // {
    //     return $this->res->get();
    // }

    public function array(): array
    {
        return $this->res;
    }

    public function headings(): array
    {
        return $this->header;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('L')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('M')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('N')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('O')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('P')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('Q')->setWidth(20);
                $event->sheet->getDelegate()->getStyle('A1:Z1265')->getAlignment()->setVertical('center');
                $event->sheet->getDelegate()->getStyle('A1:Z1265')->getAlignment()->setHorizontal('center');
                $event->sheet->getDelegate()->getStyle('A1:Z1')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'italic' => false,
                        'strikethrough' => false,
                        'color' => [
                            'rgb' => 'FFFFFF'
                        ]
                    ],
                    'fill' => [
                        'fillType' => 'linear',
                        'rotation' => 45,
                        'startColor' => [
                            'rgb' => '54AE54'
                        ],
                        'endColor' => [
                            'argb' => '54AE54'
                        ]
                    ]
                ]);
            }
        ];
    }

    // public function columnFormats(): array
    // {
    //     return [
    //         'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, //日期
    //         'C' => NumberFormat::FORMAT_NUMBER_00, //金额保留两位小数
    //     ];
    // }
}
