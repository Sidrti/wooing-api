<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUsers;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required_without:group_id|exists:users,id',
            'group_id' => 'required_without:receiver_id|exists:groups,id',
            'content' => 'nullable',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
            'type' => 'required|in:EMOJI,STICKER,PHOTO,VIDEO,TEXT,DIVIDER',
        ]);

        $user = auth()->user();

        $path = '';
        if($request->has('media'))
        {
            $file = $request->file('media');
            $dir = '/uploads/chat/';
            $path = Helper::saveImageToServer($file,$dir);
        }

        if($request->input('receiver_id')) {
        
            $receiver = User::find($request->input('receiver_id'));
            $user1Id = $user->id;
            $user2Id = $receiver->id;

            $exists = Group::whereHas('groupUsers', function ($q) use ($user1Id, $user2Id) {
                $q->where('user_id', $user1Id);
              })->whereHas('groupUsers', function ($q) use ($user2Id) {
                $q->where('user_id', $user2Id);  
              })->exists();
              
              if(!$exists) {

                $conversation = Group::create([
                    'type' => 'PRIVATE',
                    'admin_id' => $user1Id,
                    'name' => null,
                ]);

                GroupUsers::firstOrCreate([
                    'group_id' => $conversation->id,
                    'user_id' => $user->id
                  ]);
                GroupUsers::firstOrCreate([
                    'group_id' => $conversation->id,
                    'user_id' => $receiver->id
                ]);
              
              } else {
                $conversation = Group::whereHas('groupUsers', function ($q) use ($user1Id, $user2Id) {
                    $q->where('user_id', $user1Id);
                  })->whereHas('groupUsers', function ($q) use ($user2Id) {
                    $q->where('user_id', $user2Id);  
                  })->first();
              
              }
        
        
          } else if($request->input('group_id')) {
            $conversation = Group::find($request->input('group_id'));
          }

        $message = Message::create([
            'sender_id' => $user->id,
            'group_id' => $conversation->id,
            'content' => $request->input('content'),
            'media_path' => $path,
            'type' => $request->input('type','TEXT'),
        ]);

        return response()->json(['status_code' => 1, 'data' => ['message' => $message], 'message' => 'Message saved']);
    }
    public function fetchMessages(Request $request)
    {
        $request->validate([
            // 'receiver_id' => 'required_without:group_id|exists:users,id',
            'group_id' => 'required|exists:groups,id',
        ]);

        $user = auth()->user();
       // $receiverId = $request->input('receiver_id');
        $groupId = $request->input('group_id');

        $query = Message::with(['sender'])
            ->where(function ($query) use ($user, $groupId) {
                $query->where('group_id', $groupId);
            })
            ->orderBy('messages.id', 'desc')
            ->paginate(20);

        return response()->json(['status_code' => 1, 'data' => ['chats' => $query], 'message' => 'Chat fetched']);
    }

    public function fetchChatList()
    {
        $userId = auth()->user()->id;
    
        $groups = Group::whereHas('groupUsers', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['users' => function($q) use($userId) {
            $q->where('users.id', '!=', $userId);
        }])
        ->get();

        return response()->json([
            'status_code' => 1,
            'data' => $groups,
            'message' => 'Friends and groups fetched with messages successfully'
        ]);
    }
    
    
}

?>