<?php

namespace App\Imports;

use App\Models\ItemUnit;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemUnitImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'sku' => 'required|string|unique:item_units,sku',
            'condition' => 'required|string',
            'notes' => 'nullable|string',
            'acquisition_source' => 'required|string',
            'acquisition_date' => 'required|date_format:Y-m-d',
            'acquisition_notes' => 'nullable|string',
            'status' => 'required|in:available,borrowed,unknown,unavailable',
            'quantity' => 'required|integer|min:1',
            'qr_image_url' => 'required|string',
            'item_name' => 'required|exists:items,name',
            'warehouse_name' => 'required|exists:warehouses,name',
            'current_location' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception('Row validation failed: ' . json_encode($validator->errors()->all()));
        }
        $item_id = Item::where('name', $row['item_name'])->value('id');
        $warehouse_id = Warehouse::where('name', $row['warehouse_name'])->value('id');
        return new ItemUnit([
            'sku' => $row['sku'],
            'condition' => $row['condition'],
            'notes' => $row['notes'] ?? null,
            'acquisition_source' => $row['acquisition_source'],
            'acquisition_date' => $row['acquisition_date'],
            'acquisition_notes' => $row['acquisition_notes'] ?? null,
            'status' => $row['status'],
            'quantity' => $row['quantity'],
            'qr_image_url' => $row['qr_image_url'],
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'current_location' => $row['current_location'] ?? null,
        ]);
    }
}
 