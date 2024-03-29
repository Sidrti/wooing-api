<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\GroupUsers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendRequestController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $sender = auth()->user();

        $receiver = User::find($request->input('receiver_id'));

        $existingRequest = FriendRequest::where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->first();

        if ($existingRequest) {
            if($existingRequest->accepted == 'CANCELLED') {
                $existingRequest->accepted = 'NO_ACTION';
                $existingRequest->save();
                return response()->json(['status_code' => 1, 'data' => [], 'message' => 'Friend request sent']);
            }
            return response()->json(['status_code' => 0, 'data' => [], 'message' => 'Friend request already sent']);
        }

        $friendRequest = new FriendRequest();
        $friendRequest->sender_id = $sender->id;
        $friendRequest->receiver_id = $receiver->id;
        $friendRequest->accepted = 'NO_ACTION';
        $friendRequest->save();

        $message = $sender->name.' sent you a friend request';
        $data = [
            'friend_request_id' => $friendRequest->id,
            'sender_id' => $sender->id,
            'profile_picture' => $sender->profile_picture
        ];
        Helper::createNotification($receiver->id, 'FRIEND_REQUEST',$message,$data);

        return response()->json(['status_code' => 1, 'data' => [], 'message' => 'Friend request sent']);
    }
    public function updateFriendRequestStatus(Request $request)
    {
        $request->validate([
            'friend_request_id' => 'required|exists:friend_requests,id',
            'status' => 'required|in:ACCEPTED,CANCELLED',
        ]);

        $friendRequest = FriendRequest::find($request->input('friend_request_id'));

        $friendRequest->accepted = $request->input('status');
        $friendRequest->save();

        if($request->input('status') === 'ACCEPTED') {
            $message = auth()->user()->name.' accepted your friend request';
            $responseMessage = 'Friend request accepted';
        }
        else {
            $message = auth()->user()->name.' rejected your friend request';
            $responseMessage = 'Friend request rejected';
        }
        
        $data = [
            'friend_request_id' => $friendRequest->id,
            'sender_id' => auth()->user()->id,
            'profile_picture' => auth()->user()->profile_picture
        ];
        Helper::createNotification($friendRequest->sender_id , 'FRIEND_REQUEST_RESPONSE',$message,$data);

        return response()->json(['status_code' => 1, 'data' => [], 'message' => $responseMessage]);
    }

    public function fetchFriends()
    {
        $user = auth()->user();

        // Retrieve accepted friend requests for the user
        $friends = FriendRequest::where('sender_id', $user->id)
                                ->where('accepted', 'ACCEPTED')
                                ->orWhere('receiver_id', $user->id)
                                ->where('accepted', 'ACCEPTED')
                                ->get();

        // Extract friend IDs excluding the user's own ID
        $friendIds = $friends->pluck('sender_id')->merge($friends->pluck('receiver_id'))->reject(function ($friendId) use ($user) {
            return $friendId == $user->id;
        })->unique();
        // Retrieve friend details
        $friendDetails = User::whereIn('id', $friendIds)->select('id', 'name', 'email','profile_picture')->get();

        $loggedInUserGroupIds = [];
        foreach ($friendIds as $friendId) {
            $groupId = GroupUsers::whereIn('user_id', [$user->id, $friendId])
                ->where('groups.type','SINGLE')
                ->join('groups','groups.id','group_users.group_id')
                ->groupBy('group_id')
                ->havingRaw('COUNT(DISTINCT user_id) = 2')
                ->pluck('group_id')
                ->first();

            $loggedInUserGroupIds[$friendId] = $groupId;
        }

        // Map additional data to each friend
        $friendDetails->map(function ($friend) use ($loggedInUserGroupIds) {
            $friend->type = 'SINGLE';
            $friend->chatfetchid = $loggedInUserGroupIds[$friend->id] ?? null;
            return $friend;
        });
        
        return response()->json(['status_code' => 1, 'data' => ['friends' => $friendDetails], 'message' => 'Friends fetched successfully']);
    }
    public function fetchFriendRequest(Request $request)
    {
        $user = auth()->user();
    
        // Fetch friend requests involving the user
        // $friendRequests = FriendRequest::where(function ($query) use ($user) {
        //     $query->where('sender_id', $user->id)
        //           ->orWhere('receiver_id', $user->id);
        // })
        // ->where('accepted', 'NO_ACTION')
        // ->get();
        
        $friendRequests = FriendRequest::where('receiver_id', $user->id)
        ->join('users', 'users.id', 'friend_requests.sender_id')
        ->select(
            'users.id as friend_id',
            'users.name',
            DB::raw("CONCAT('" . config('app.media_base_url') . "', users.profile_picture) as profile_picture"),
            'friend_requests.id as friend_request_id',
            'friend_requests.accepted as friend_request_status'
        )
        ->where('accepted', 'NO_ACTION')
        ->get();
    

    
        // Extract friend IDs excluding the user's own ID
        // $friendIds = $friendRequests->pluck('sender_id')->merge($friendRequests->pluck('receiver_id'))->reject(function ($friendId) use ($user) {
        //     return $friendId == $user->id;
        // })->unique();

        // Retrieve friend details and their request status
        // $friendsWithStatus = collect();
        // foreach ($friendIds as $friendId) {
        //     $friend = User::find($friendId);
        //     $friendRequest = $friendRequests->first(function ($request) use ($friendId, $user) {
        //         return ($request->sender_id == $friendId && $request->receiver_id == $user->id) ||
        //                ($request->sender_id == $user->id && $request->receiver_id == $friendId);
        //     });
        //     $status = $friendRequest ? $friendRequest->accepted : null;
    
        //     $friendsWithStatus->push([
        //         'friend_request_id' => $friendRequest ? $friendRequest->id : null,
        //         'friend_id' => $friend->id,
        //         'name' => $friend->name,
        //         'email' => $friend->email,
        //         'profile_picture' => $friend->profile_picture,
        //         'friend_request_status' => $status,
         //   ]);
        // }
    
        return response()->json([
            'status_code' => 1,
            'data' => ['friends' => $friendRequests],
            'message' => 'Friends fetched successfully'
        ]);
    }
    public function fetchFriendRequestStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $user_id = $request->input('user_id');
        $user = auth()->user();
       // Check if the logged-in user has sent a friend request
        $sentRequest = FriendRequest::where('sender_id', $user->id)
        ->where('receiver_id', $user_id)
        ->first();

        // Check if the logged-in user has received a friend request
        $receivedRequest = FriendRequest::where('sender_id', $user_id)
        ->where('receiver_id', $user->id)
        ->first();

        // Check if the users are already friends
        $areFriends = FriendRequest::where(function ($query) use ($user, $user_id) {
            $query->where('sender_id', $user->id)
                ->where('receiver_id', $user_id)
                ->orWhere('sender_id', $user_id)
                ->where('receiver_id', $user->id);
        })
        ->where('accepted', 'ACCEPTED')
        ->exists();

        // Determine the response based on the conditions
        if ($areFriends) {
            $status =  "FRIENDS";
        }
        else if ($sentRequest) {
        $status =  "REQUEST SENT";
        } 
        else if ($receivedRequest) {
        $status =  "REQUEST RECEIVED";
        } 
        else {
        $status =  "REQUEST NOT SENT";
        }


        return response()->json(['status_code' => 1, 'data' => ['friend_request_status' => $status], 'message' => 'Friends fetched successfully']);
    }
} 

?>