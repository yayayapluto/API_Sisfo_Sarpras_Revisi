<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\Formatter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UserImport;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $userQuery = User::query()->where("id", "!=", Auth::guard("sanctum")->user()->id);
        $validColumns = ['username', 'email', 'phone', 'role'];

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $userQuery->where(function ($query) use ($searchTerm) {
                $query->where('username', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm)
                    ->orWhere('phone', 'LIKE', $searchTerm);
            });
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'search']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $userQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $userQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $users = $userQuery->simplePaginate($size);

        return Formatter::apiResponse(200, 'User list retrieved', $users);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|unique:users,username',
            'email' => 'sometimes|email|unique:users,email',
            'phone' => 'sometimes|string|min:10|unique:users,phone',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,admin'
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $validated['password'] = bcrypt($validated['password']);

        $newUser = User::create($validated);
        return Formatter::apiResponse(200, 'User created', $newUser);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::query()->find($id)->load(["logActivities", "borrowRequests.borrowDetails","approvals"]);
        if (is_null($user)) {
            return Formatter::apiResponse(404, 'User not found');
        }
        return Formatter::apiResponse(200, 'User found', $user);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (is_null($user)) {
            return Formatter::apiResponse(404, 'User not found');
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|min:3|unique:users,username,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|min:10|unique:users,phone,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:user,admin'
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);
        return Formatter::apiResponse(200, 'User updated', $user);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (is_null($user)) {
            return Formatter::apiResponse(404, 'User not found');
        }

        $user->delete();
        return Formatter::apiResponse(200, 'User deleted');
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);
        try {
            \DB::beginTransaction();
            Excel::import(new UserImport, $request->file('file'));
            \DB::commit();
            return Formatter::apiResponse(200, 'Users imported successfully', []);
        } catch (\Exception $e) {
            \DB::rollBack();
            return Formatter::apiResponse(422, 'Import failed', null, $e->getMessage());
        }
    }
}
