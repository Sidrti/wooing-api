<?php

namespace App\Http\Controllers\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\Post;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function fetchUsers() 
    {
        $users = User::leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
        ->select('users.*', DB::raw('IF(profiles.id IS NOT NULL, true, false) as profile_filled'))
        ->where('role','!=',2)
        ->paginate(20);

       return response()->json(['status_code' => 1, 'data' => ['users' => $users]]);
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

        $friends = FriendRequest::where('sender_id', $userId)
        ->where('accepted', 'ACCEPTED')
        ->orWhere('receiver_id', $userId)
        ->where('accepted', 'ACCEPTED')
        ->get();

        $friendIds = $friends->pluck('sender_id')->merge($friends->pluck('receiver_id'))->reject(function ($friendId) use ($user) {
            return $friendId == $user->id;
        })->unique();
        // Retrieve friend details
        $friendDetails = User::whereIn('id', $friendIds)->select('id', 'name', 'email','profile_picture')->get();

        $profileData = [
            'followers_count' => $followersCount,
            'bio' => $user->profile->bio,
            'name' => $user->name,
            'profile_picture' => $user->profile_picture,
            'email' => $user->email,
            'phone_number' => $user->mobile_number,
            'sex' => $user->profile->sex,
            'marital_status' => $user->profile->marital_status,
            'religion' => $user->profile->religion,
            'looking_for' => $user->profile->looking_for,
            'drinking' => $user->profile->drinking,
            'smoking' => $user->profile->smoking,
            'media' => $media,
            'friends' => $friendDetails
        ];

        return response()->json(['status_code'=>1,'data'=>['profile' => $profileData],'message'=> 'Profile fetched']);
    } 
    public function searchUsers(Request $request)
    {
        // Validate the search query parameter
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        // Get the search query from the request
        $query = $request->input('query');

        $users = User::leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
        ->select('users.*', DB::raw('IF(profiles.id IS NOT NULL, true, false) as profile_filled'))
        ->where('name', 'like', "%$query%")
        ->orWhere('email', 'like', "%$query%")
        ->paginate(20);
             

        // Return the search results as JSON response
        return response()->json(['status_code' => 1, 'data' => ['users' => $users], 'message' => 'Users searched successfully']);
    }
    public function updateUserStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);
        $user = User::findOrFail($request->input('user_id'));
        $user->status = $request->input('status');
        $user->save();

        return response()->json([
            'status_code' => 1,
            'message' => 'User status updated successfully',
        ]);
    }
}
