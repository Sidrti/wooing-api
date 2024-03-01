<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'receiver_id' => 'exists:users,id',
            'group_id' => 'exists:groups,id',
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

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $request->input('receiver_id'),
            'group_id' => $request->input('group_id'),
            'content' => $request->input('content'),
            'media_path' => $path,
            'type' => $request->input('type','TEXT'),
        ]);

        return response()->json(['status_code' => 1, 'data' => ['message' => $message], 'message' => 'Message saved']);
    }
    public function fetchMessages(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required_without:group_id|exists:users,id',
            'group_id' => 'required_without:receiver_id|exists:groups,id',
        ]);

        $user = auth()->user();
        $receiverId = $request->input('receiver_id');
        $groupId = $request->input('group_id');

        $query = Message::with(['sender', 'receiver'])
            ->where(function ($query) use ($user, $receiverId, $groupId) {
                if ($groupId) {
                    // Fetch messages for the specified group
                    $query->where('group_id', $groupId);
                } else {
                    // Fetch messages for the specified receiver (one-on-one chat)
                    $query->where(function ($query) use ($user, $receiverId) {
                        $query->where('sender_id', $user->id)
                            ->where('receiver_id', $receiverId)
                            ->orWhere('sender_id', $receiverId)
                            ->where('receiver_id', $user->id);
                    });
                }
            })
            ->orderBy('messages.id', 'desc')
            ->paginate(20);

        return response()->json(['status_code' => 1, 'data' => ['chats' => $query], 'message' => 'Chat fetched']);
    }

    public function fetchChatList()
    {
        $user = auth()->user();
    
        $users = User::select(
            "users.id as user_id",
            "users.email",
            "users.name",
            DB::raw("(SELECT content FROM messages WHERE (sender_id = users.id OR receiver_id = users.id) AND (sender_id = " . $user->id  . " OR receiver_id = " .  $user->id  . ") ORDER BY created_at DESC LIMIT 1) as last_message"),
            DB::raw("(SELECT 
                CASE 
                    WHEN sender_id = " .  $user->id  . " THEN 'sent'
                    WHEN receiver_id = " .  $user->id  . " THEN 'received'
                END AS message_direction 
            FROM messages 
            WHERE (sender_id = users.id OR receiver_id = users.id) 
                AND (sender_id = " . $user->id . " OR receiver_id = " . $user->id . ") 
            ORDER BY created_at DESC LIMIT 1) as type"),
        )
        ->orderBy('users.id')
        ->get();

    return $users;
    
        return response()->json([
            'status_code' => 1,
            'data' => $users,
            'message' => 'Friends and groups fetched with messages successfully'
        ]);
    }
    
    
}

?>