<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'slug' => 'required|string|unique:categories,slug',
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            throw new \Exception('Row validation failed: ' . json_encode($validator->errors()->all()));
        }
        return new Category([
            'slug' => $row['slug'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
        ]);
    }
} 