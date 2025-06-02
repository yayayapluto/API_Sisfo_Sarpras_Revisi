<?php

namespace App\Imports;

use App\Custom\Formatter;
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
            'condition' => 'required|string',
            'notes' => 'nullable|string',
            'acquisition_source' => 'required|string',
            'acquisition_date' => 'required|date_format:Y-m-d',
            'acquisition_notes' => 'nullable|string',
            'status' => 'required|in:available,borrowed,unknown,unavailable',
            'quantity' => 'required|integer|min:1',
            'qr_image_url' => 'required|string',
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'current_location' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception('Row validation failed: ' . json_encode($validator->errors()->all()));
        }
        $item = Item::query()->find($row["item_id"]);
        $warehouse = Warehouse::query()->find($row["warehouse_id"]);
        return new ItemUnit([
            'sku' => Formatter::makeDash($warehouse->name) . "-" . Formatter::makeDash($item->name) . "-" . ($item->itemUnits->sum('quantity') + 1),
            'condition' => $row['condition'],
            'notes' => $row['notes'] ?? null,
            'acquisition_source' => $row['acquisition_source'],
            'acquisition_date' => $row['acquisition_date'],
            'acquisition_notes' => $row['acquisition_notes'] ?? null,
            'status' => $row['status'],
            'quantity' => $row['quantity'],
            'qr_image_url' => $row['qr_image_url'],
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'current_location' => $row['current_location'] ?? null,
        ]);
    }
}
