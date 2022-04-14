<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController
{
    public function register(Request $request)
    {
        DB::beginTransaction();
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|unique:users,email',
            'password' => 'required|min:8',
            'c_password' => 'required|same:password',
            'address' => 'string',
            'cellphone' => 'required|string|min:11',
            'province_id' => 'required',
            'city_id' => 'required'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'cellphone' => $request->cellphone,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
        ]);

        $token = $user->createToken('test1')->plainTextToken;

        DB::commit();
        return $this->successResponse([
            'token' => $token,
            'user' => $user
        ], 200);

    }

    public function login(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user){
            return $this->errorResponse('Not Found User', 401);
        }

        if (!Hash::check($request->password, $user->password)){
            return $this->errorResponse('Error, check password', 422);
        }

        $token = $user->createToken('test2')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ],202);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json('success', 200);
    }
}
