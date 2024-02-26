<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'sex' => 'required|in:male,female,other',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'religion' => 'required|string',
            'looking_for' => 'required|string',
            'drinking' => 'required|in:socially,no,often,regularly',
            'smoking' => 'required|in:socially,no,often,regularly',
        ]);
        $user->profile()->updateOrCreate([], $request->all());

        return response()->json(['status_code' => 1,'data' => ['profile' => $user->profile], 'message' => 'Profile details saved successfully']);
    }
    public function fetchProfile()
    {
        $user = auth()->user();

        $user->load('profile');

        $followersCount = $user->followers->count();

        $media = Post::where('user_id',$user->id)->get();
        $media->load('postMedia');

        $profileData = [
            'followers_count' => $followersCount,
            'bio' => $user->profile->bio,
            'name' => $user->name,
            'profile_picture' =>$user->profile_picture,
            'email' => $user->email,
            'phone_number' => $user->mobile_number,
            'sex' => $user->profile->sex,
            'marital_status' => $user->profile->marital_status,
            'religion' => $user->profile->religion,
            'looking_for' => $user->profile->looking_for,
            'drinking' => $user->profile->drinking,
            'smoking' => $user->profile->smoking,
            'media' => $media
        ];

        return response()->json(['status_code'=>1,'data'=>['profile' => $profileData],'message'=> 'Profile fetched']);
    }
    public function updateProfile(Request $request)
    {
        $request->validate([
            'mobile_number' => 'string|max:20',
            'bio' => 'string|max:255',
            'profile_picture' => 'mimes:jpeg,jpg,png|max:2048',
            'sex' => 'in:male,female,other',
            'marital_status' => 'in:single,married,divorced,widowed',
            'religion' => 'string',
            'looking_for' => 'string',
            'drinking' => 'in:socially,no,often,regularly',
            'smoking' => 'in:socially,no,often,regularly',
        ]);
        

        $user = auth()->user();

        if($request->has('profile_picture'))
        {
            $file = $request->file('profile_picture');
            $dir = '/uploads/profile/';
            $path = Helper::saveImageToServer($file,$dir);
            $data = [
                'mobile_number' => $request->input('mobile_number', $user->mobile_number),
                'profile_picture' => $path 
            ];
        }
        else
        {
            $data = [
                'mobile_number' => $request->input('mobile_number', $user->mobile_number),
            ];
        }
        $user->update($data);

        if ($user->profile) {
            $user->profile->update([
                'bio' => $request->input('bio', $user->profile->bio),
                'sex' => $request->input('sex', $user->profile->sex),
                'marital_status' => $request->input('marital_status', $user->profile->marital_status),
                'religion' => $request->input('religion', $user->profile->religion),
                'looking_for' => $request->input('looking_for', $user->profile->looking_for),
                'drinking' => $request->input('drinking', $user->profile->drinking),
                'smoking' => $request->input('smoking', $user->profile->smoking),
            ]);
        }

        return response()->json(['status_code' => 1, 'data' => ['user' => $user], 'message' => 'Profile updated successfully']);
        
    }

    public function fetchProfileById(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->input('user_id');
        $user = User::where('id',$userId)->first();

        $user->load('profile');

        $followersCount = $user->followers->count();

        $media = Post::where('user_id',$userId)->get();
        $media->load('postMedia');

        $friendRequestStatus = null;
        if (auth()->check()) {
            $loggedInUser = auth()->user();
            $friendRequest = FriendRequest::where(function ($query) use ($loggedInUser, $userId) {
                $query->where('sender_id', $loggedInUser->id)->where('receiver_id', $userId);
            })->orWhere(function ($query) use ($loggedInUser, $userId) {
                $query->where('sender_id', $userId)->where('receiver_id', $loggedInUser->id);
            })->first();
            if ($friendRequest) {
                $friendRequestStatus = $friendRequest->accepted;
            }
        }

        $profileData = [
            'followers_count' => $followersCount,
            'bio' => $user->profile->bio,
            'name' => $user->name,
            'profile_picture' =>$user->profile_picture,
            'email' => $user->email,
            'phone_number' => $user->mobile_number,
            'sex' => $user->profile->sex,
            'marital_status' => $user->profile->marital_status,
            'religion' => $user->profile->religion,
            'looking_for' => $user->profile->looking_for,
            'drinking' => $user->profile->drinking,
            'smoking' => $user->profile->smoking,
            'friend_request_status' => $friendRequestStatus == null ? 'NOT_FRIENDS' : $friendRequestStatus,
            'media' => $media,
        ];

        return response()->json(['status_code'=>1,'data'=>['profile' => $profileData],'message'=> 'Profile fetched']);
    } 
}

?>