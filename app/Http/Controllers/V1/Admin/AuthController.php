<?php

namespace App\Http\Controllers\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
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

        $user = User::where('email', $request->email)->where('role',2)->first();
        
        if($user) { 
            if($user->is_verified) {
                if ($user && Hash::check($request->input('password'), $user->password)) { 
                    $token = $user->createToken('api-token')->plainTextToken;
                    return response()->json(['status_code' => 1,'data' => ['user' => $user,'token' => $token ],'message'=>'Login successfull.']);
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
}
