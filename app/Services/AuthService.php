<?php
namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthService
{


function register($data) {
 
 DB::beginTransaction();

    try {
 $user=User::create(
    [
        'name'=>$data['name'],
        "email"=>$data['email'],
        'password'=>Hash::make($data['password']),
        'phone'=>$data["phone"]??null
    ]
 );
 $otp = $this->generateOtp($user->email);

 Mail::to($user->email)->send(new SendOtpMail($otp));
 
  DB::commit();
        return [
            'user' => $user,
            
        ]; 

   } catch (\Exception $e) {

        DB::rollBack();

        throw $e;
    }
}




public function login($data)
{
    if (!Auth::attempt([
        'email' => $data['email'],
        'password' => $data['password']
    ])) {
        return [
            'status' => 'invalid_credentials',
            'message' => 'Email or password is incorrect'
        ];
    }

    $user = Auth::user();

    if (!$user->hasVerifiedEmail()) {
        return [
            'status' => 'not_verified',
            'message' => 'Please verify your email first'
        ];
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return [
        'status' => 'success',
        'user' => $user,
        'token' => $token
    ];
}

public function logout($user)
{

    $user->currentAccessToken()->delete();

    return true;

}

public function profile($user)
{
    return $user;
}




public function generateOtp($email)
{

    $code = random_int(100000, 999999);

    Otp::updateOrCreate(

        ['email' => $email],

        [
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0
        ]

    );

    return $code;

}

public function verifyOtp($data)
{

    $otp = Otp::where('email',$data['email'])->first();

    if(!$otp)
    {
        return 'invalid';
    }

    if($otp->expires_at < now())
    {
        return 'expired';
    }

    if(!Hash::check($data['code'],$otp->code))
    {
        $otp->increment('attempts');

        if($otp->attempts >= 5)
        {
            $otp->delete();
        }

        return 'invalid';
    }

    $user = User::where('email',$data['email'])->first();

    $user->email_verified_at = now();
    $user->assignRole('user');
    $user->save();

    $otp->delete();

    return 'verified';

}

public function resendOtp($email)
{
    $user = User::where('email', $email)->first();

    if (!$user) {
        return [
            'status' => 'not_found',
            'message' => 'User not found'
        ];
    }

    if ($user->hasVerifiedEmail()) {
        return [
            'status' => 'already_verified',
            'message' => 'Email already verified'
        ];
    }

    
    Otp::where('email', $email)->delete();

    
    $otp = $this->generateOtp($email);

    Mail::to($email)->send(new SendOtpMail($otp));

    return [
        'status' => 'success',
        'message' => 'OTP resent successfully'
    ];
}


public function forgotPassword($email)
{
    $user = User::where('email', $email)->first();

    if (!$user) {
        return [
            'status' => 'not_found',
            'message' => 'User not found'
        ];
    }

    // حذف OTP القديم
    Otp::where('email', $email)->delete();

    // إنشاء OTP جديد
    $otp = $this->generateOtp($email);

    Mail::to($email)->send(new SendOtpMail($otp));

    return [
        'status' => 'success',
        'message' => 'OTP sent to your email'
    ];
}

public function resetPassword($data)
{
   $otpRecord = Otp::where('email', $data['email'])->first();

if (!$otpRecord || !Hash::check($data['code'], $otpRecord->code)) {
    return [
        'status' => 'invalid_otp',
        'message' => 'Invalid OTP'
    ];
}

    // تحقق من انتهاء الصلاحية (مثلاً 10 دقائق)
    if (now()->diffInMinutes($otpRecord->created_at) > 10) {
        return [
            'status' => 'expired_otp',
            'message' => 'OTP expired'
        ];
    }

    $user = User::where('email', $data['email'])->first();

    $user->update([
        'password' => Hash::make($data['password'])
    ]);

    // حذف OTP بعد الاستخدام
    $otpRecord->delete();

    return [
        'status' => 'success',
        'message' => 'Password reset successful'
    ];
}




}