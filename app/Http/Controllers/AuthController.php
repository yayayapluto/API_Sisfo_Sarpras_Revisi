<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "username" => "required|string|exists:users,username|min:4|max:64",
            "password" => "required|string|min:6|max:24"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $user = User::query()->where("username", $request->username)->first();

        if (is_null($user) || !Hash::check($request->password, $user->password)) {
            return Formatter::apiResponse(400, "Username or password doesn't match");
        }

        $token = $user->createToken("auth_token")->plainTextToken;
        return Formatter::apiResponse(200, "Logged in", [
            "token" => $token,
            "user" => $user
        ]);
    }

    public function logout()
    {
        Auth::guard("sanctum")->user()->tokens()->delete();
        return Formatter::apiResponse(200, "Logged out");
    }

    public function self()
    {
        return Formatter::apiResponse(200, "Self auth data received", Auth::guard("sanctum")->user());
    }
}
