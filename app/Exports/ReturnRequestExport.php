<?php

namespace App\Exports;

use App\Models\ReturnRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReturnRequestExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $start;
    protected $end;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ReturnRequest::with(['borrowRequest.user', 'handler'])
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->end)
            ->get();
    }

    public function headings(): array
    {
        return [
            ['PT. Nama Perusahaan'],
            ['Laporan Permintaan Pengembalian Barang'],
            ["Periode: {$this->start} s/d {$this->end}"],
            [],
            ['No', 'Tanggal', 'User', 'Status', 'Handled By', 'Notes', 'Borrow Request ID', 'Created At'],
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->created_at->format('Y-m-d'),
            $row->borrowRequest->user->username ?? '-',
            ucfirst($row->status),
            $row->handler->username ?? '-',
            $row->notes,
            $row->borrow_request_id,
            $row->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Corporate styling: bold headers, center title, etc
        return [
            1    => ['font' => ['bold' => true, 'size' => 16]], // Company name
            2    => ['font' => ['bold' => true, 'size' => 14]], // Report title
            3    => ['font' => ['italic' => true]], // Date range
            5    => ['font' => ['bold' => true]], // Table headers
        ];
    }
}
