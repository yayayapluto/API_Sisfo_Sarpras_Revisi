<?php

namespace App\Imports;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehouseImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|unique:warehouses,name',
            'location' => 'required|string',
            'capacity' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            throw new \Exception('Row validation failed: ' . json_encode($validator->errors()->all()));
        }
        return new Warehouse([
            'name' => $row['name'],
            'location' => $row['location'],
            'capacity' => $row['capacity'],
        ]);
    }
} 