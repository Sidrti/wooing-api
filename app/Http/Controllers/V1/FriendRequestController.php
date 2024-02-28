<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;

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

        
        $friendDetails->map(function ($friend) {
            $friend->type = 'SINGLE';
            return $friend;
        });

        return response()->json(['status_code' => 1, 'data' => ['friends' => $friendDetails], 'message' => 'Friends fetched successfully']);
    }
    public function fetchFriendRequest(Request $request)
    {
        $user = auth()->user();
    
        // Fetch friend requests involving the user
        $friendRequests = FriendRequest::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
        })
        ->where('accepted', 'NO_ACTION')
        ->get();
        
    
        // Extract friend IDs excluding the user's own ID
        $friendIds = $friendRequests->pluck('sender_id')->merge($friendRequests->pluck('receiver_id'))->reject(function ($friendId) use ($user) {
            return $friendId == $user->id;
        })->unique();

        // Retrieve friend details and their request status
        $friendsWithStatus = collect();
        foreach ($friendIds as $friendId) {
            $friend = User::find($friendId);
            $friendRequest = $friendRequests->first(function ($request) use ($friendId, $user) {
                return ($request->sender_id == $friendId && $request->receiver_id == $user->id) ||
                       ($request->sender_id == $user->id && $request->receiver_id == $friendId);
            });
            $status = $friendRequest ? $friendRequest->accepted : null;
    
            $friendsWithStatus->push([
                'friend_request_id' => $friendRequest ? $friendRequest->id : null,
                'friend_id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
                'profile_picture' => $friend->profile_picture,
                'friend_request_status' => $status,
            ]);
        }
    
        return response()->json([
            'status_code' => 1,
            'data' => ['friends' => $friendsWithStatus],
            'message' => 'Friends fetched successfully'
        ]);
    }
    
} 

?>