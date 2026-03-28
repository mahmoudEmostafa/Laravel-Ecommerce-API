<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService ;

use Illuminate\Http\Request;

class AuthController 
{


    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {

        $data = $this->authService->register($request->validated());

        return response()->json([

            'message' => 'User registered successfully',

            'user' => new UserResource($data['user']),

            

        ], 201);

    }



    public function login(LoginRequest $request)
{
    $data = $this->authService->login($request->validated());

    if ($data['status'] === 'invalid_credentials') {
        return response()->json([
            'message' => $data['message']
        ], 401);
    }

    if ($data['status'] === 'not_verified') {
        return response()->json([
            'message' => $data['message']
        ], 403);
    }

    return response()->json([
        'message' => 'Login successful',
        'user' => new UserResource($data['user']),
        'token' => $data['token']
    ]);
}
    
    

   

public function logout()
{

    $this->authService->logout(auth()->user());

    return response()->json([

        'message' => 'Logged out successfully'

    ]);

}

public function profile()
{

    $user = $this->authService->profile(auth()->user());

    return response()->json([

        'user' => new UserResource($user)

    ]);

}
public function verifyOtp(VerifyOtpRequest $request)
{

    $result = $this->authService->verifyOtp($request->validated());

    if($result === 'invalid'){
        return response()->json([
            'message' => 'Invalid OTP'
        ],400);
    }

    if($result === 'expired'){
        return response()->json([
            'message' => 'OTP expired'
        ],400);
    }

    return response()->json([
        'message' => 'Email verified successfully'
    ]);

}

public function resendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $data = $this->authService->resendOtp($request->email);

    if ($data['status'] === 'not_found') {
        return response()->json([
            'message' => $data['message']
        ], 404);
    }

    if ($data['status'] === 'already_verified') {
        return response()->json([
            'message' => $data['message']
        ], 400);
    }

    return response()->json([
        'message' => $data['message']
    ]);
}

public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $data = $this->authService->forgotPassword($request->email);

    if ($data['status'] === 'not_found') {
        return response()->json([
            'message' => $data['message']
        ], 404);
    }

    return response()->json([
        'message' => $data['message']
    ]);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code' => 'required',
        'password' => 'required|min:8|confirmed'
    ]);

    $data = $this->authService->resetPassword($request->all());

    if ($data['status'] === 'invalid_otp') {
        return response()->json([
            'message' => $data['message']
        ], 400);
    }

    if ($data['status'] === 'expired_otp') {
        return response()->json([
            'message' => $data['message']
        ], 400);
    }

    return response()->json([
        'message' => $data['message']
    ]);
}

}

