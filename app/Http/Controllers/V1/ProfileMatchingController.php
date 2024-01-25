<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileMatchingController extends Controller
{
    public function findMatchingProfiles(Request $request)
    {
        $user = auth()->user();

        $userInterests = $user->profile;

        if (!$userInterests) {
            return response()->json(['status_code' => 2, 'data'=>[], 'message' => 'User profile not found.'], 200);
        }

        $matchingProfiles = Profile::whereHas('user', function ($query) use ($userInterests,$request) {
            $query->where('sex', '!=', $userInterests->sex);
        
            if ($userInterests->looking_for === 'sugar daddy') {
                $query->where('sex', 'female');
                $query->whereDate('dob', '<=', now()->subYears(40)->format('Y-m-d')); // Assuming age > 40
            } elseif ($userInterests->looking_for === 'sugar momma') {
                $query->where('sex', 'male');
                $query->whereDate('dob', '<=', now()->subYears(40)->format('Y-m-d')); // Assuming age > 40
            } else {
                $query->where('looking_for', $userInterests->looking_for);
            }

            if ($request->has('q')) {
                $query->where('name', 'like', '%' . $request->input('q') . '%');
            }
            if ($request->has('distance')) {

                $distance = $request->input('distance');
            }
            else {
                $distance = 50;
            }
            $latitude = $userInterests->latitude;
            $longitude = $userInterests->longitude;
                
            if($latitude != null) {
                $query->whereRaw(DB::raw(
                    "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians($longitude) - radians(longitude)) + sin(radians($latitude)) * sin(radians(latitude)))) <= $distance"
                ));
            }
        })
        ->with(['user:id,name,profile_picture'])
        ->get(['user_id']); 
        
        // Add online_status field (static for now)
        $matchingProfiles->each(function ($profile) {
            $profile->user->online_status = 'online'; 
        });

        return response()->json(['status_code' => 1, 'data'=>['user'=>$user, 'profiles' => $matchingProfiles], 'message' => 'Profiles fetched.'], 200);
    }
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = auth()->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['status_code' => 2, 'data'=>[], 'message' => 'User profile not found.'], 200);
        }

        $profile->update([
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        return response()->json(['status_code' => 1, 'data'=>[], 'message' => 'Location updated successfully'], 200);
    }
}

?>