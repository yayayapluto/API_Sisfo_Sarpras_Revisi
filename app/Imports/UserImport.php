<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'username' => 'required|string|min:3|unique:users,username',
            'password' => 'required|string|min:8',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|min:10|unique:users,phone',
            'role' => 'required|in:user,admin',
        ]);
        if ($validator->fails()) {
            throw new \Exception('Row validation failed: ' . json_encode($validator->errors()->all()));
        }
        return new User([
            'username' => $row['username'],
            'password' => Hash::make($row['password']),
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'role' => $row['role'],
        ]);
    }
} 