<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request) {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->load('profile');
        if($user) {
            if($user->is_verified) {
                if ($user && Hash::check($request->input('password'), $user->password)) { 
                    $token = $user->createToken('api-token')->plainTextToken;
                    return response()->json(['status_code' => 1,'data' => ['user' => $user,'profile_filled' => isset($user->profile), 'token' => $token ],'message'=>'Login successfull.']);
                }
                else {
                    return response()->json(['status_code' => 2, 'data' => [], 'message'=>'Incorrect password.']);
                }
            }
            else {
                return response()->json(['status_code' => 2, 'data' => [], 'message'=>'Account not verified. Please goto register first']);
            }
        }
        else {
            return response()->json(['status_code' => 2, 'data' => [], 'message'=>'Account not registered']);
        }
    }
    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'city' => 'required',
            'email' => 'required|email',
            'mobile_number' => 'required',
            'password' => 'required|min:6',
            'dob' => 'required|date_format:Y-m-d',
            'profile_picture' => 'required|mimes:jpeg,jpg,png|max:2048',
        ]);

        $otp = mt_rand(1000, 9999); // Generate OTP

        $user = User::where('email', $request->email)->first();

        $path = '';

        if($user)
        {
            if(!$user->is_verified)
            {
                if($request->has('profile_picture'))
                {
                    $file = $request->file('profile_picture');
                    $dir = '/uploads/profile/';
                    $path = Helper::saveImageToServer($file,$dir);
                }

                $user->update([
                    'name' => $request->input('name'),
                    'city' => $request->input('city'),
                    'mobile_number' =>  $request->input('mobile_number'),
                    'password' => bcrypt($request->input('password')),
                    'dob' => $request->input('dob'),
                    'otp' => $otp,
                    'profile_picture' => $path
                ]);
            }
            else
            {
                return response()->json(['status_code'=>2,'data'=>[],'message' => 'User already registered. Please login.']);
            }
        }
        else 
        {
            if($request->has('profile_picture'))
            {
                $file = $request->file('profile_picture');
                $dir = '/uploads/profile/';
                $path = Helper::saveImageToServer($file,$dir);
            }
            $user = new User();
            $user->name = $request->input('name');
            $user->city = $request->input('city');
            $user->email = $request->input('email');
            $user->mobile_number = $request->input('mobile_number');
            $user->dob = $request->input('dob');
            $user->password = bcrypt($request->input('password'));
            $user->otp = $otp;
            $user->profile_picture = $path;
            $user->save();
        }

        // Send OTP to user via email (You can use Laravel's built-in mail or a third-party service)
       // Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['status_code'=>1,'data'=>['id'=>$user->id],'message' => 'User registered successfully. Please verify your email.','test_otp' => $otp]);
    }
    public function verifyUser(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'otp' => 'required'
        ]);

        $user = User::find($request->input('id'));
        if (!$user || $user->is_verified == 1) {
            return response()->json(['status_code'=> 2,'data'=> [],'message' => 'User not found']);
        }
        if ($user->otp == $request->input('otp')) {

            $user->update([
                'is_verified' => true,
                'otp' => ''
            ]);
            $token = $user->createToken('api-token')->plainTextToken;
            return response()->json(['status_code' => 1,'data' => ['user' => $user, 'token' => $token ],'message'=>'User verified successfully']);
        }
        return response()->json(['status_code'=>2,'data'=> [],'message' => 'Invalid Otp']);
    }
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        $password = mt_rand(1000000, 99999999); // Generate OTP

        if($user) {
            if($user->is_verified)
            {
                $user->update([
                    'password' => bcrypt($password),
                ]);
            }
            else
            {
                return response()->json(['status_code'=>2,'data'=>[],'message' => 'User not verified.']);
            }
             // Mail::to($user->email)->send(new OtpMail($otp));

            return response()->json(['status_code'=>1,'data'=>['id'=>$user->id],'message' => 'Password has been sent to your registered email id. You can later change it.','password' => $password]);
        }
        else {
            return response()->json(['status_code'=>2,'data'=> [],'message' => 'User not registered']);
        }        
    }
    public function forgetPasswordVerifyUser(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'otp' => 'required'
        ]);

        $user = User::find($request->input('id'));

        if (!$user) {
            return response()->json(['status_code'=> 2,'data'=> [],'message' => 'User not found']);
        }
        if ($user->otp == $request->input('otp')) {
            $uid = Str::uuid()->toString();
            $user->update([
                'otp' => '',
                'verification_uid' => $uid
            ]);

            return response()->json(['status_code'=>1,'data'=>['id'=>$user->id, 'uid' => $uid ,'first_name' => $user->first_name],'message' => 'Email verified. Continue to change your password']);
        }
        return response()->json(['status_code'=>2,'data'=> [],'message' => 'Invalid Otp']);
    } 
    /* NOT IN SCOPE */
    /* 
    public function forgetPasswordChangePassword(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'password' => 'required',
            'verification_uid' =>'required|string'
        ]);

        $user = User::where('id', $request->input('id'))
        ->where('verification_uid', $request->input('verification_uid'))
        ->first();

        if (!$user) {
            return response()->json(['status_code'=> 2,'data'=> [],'message' => 'User not found']);
        }

        $user->update([
            'password' =>  bcrypt($request->input('password')),
            'verification_uid' => ''
        ]);

        return response()->json(['status_code'=>1,'data'=> [],'message' => 'Password changed.']);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' =>'required|string'
        ]);

        $user = auth()->user(); 

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['status_code' => 2, 'data' => [], 'message' => 'Incorrect old password'], 200);
        }

        $user->update([
            'password' => bcrypt($request->input('new_password')),
        ]);

        return response()->json(['status_code'=>1,'data'=> [],'message' => 'Password changed.']);
    }
    */
    public function meProfile()
    {
        return response()->json(['status_code'=>1,'data'=> [auth()->user()],'message' => 'User profile fetched successfully']);
    }    
}
