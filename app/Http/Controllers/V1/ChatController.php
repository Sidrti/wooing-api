<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'nullable',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
            'type' => 'required|in:EMOJI,STICKER,PHOTO,VIDEO,TEXT','DIVIDER',
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
            'content' => $request->input('content'),
            'media_path' => $path,
            'type' => $request->input('type','TEXT'),
        ]);

        return response()->json(['status_code' => 1, 'data' => ['message' => $message], 'message' => 'Message saved']);
    }
    public function fetchMessages(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();
        $receiverId = $request->input('receiver_id');

        $chats = Message::with(['sender', 'receiver'])
            ->where(function ($query) use ($user,$receiverId) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $receiverId)
                    ->orWhere('sender_id', $receiverId)
                    ->where('receiver_id', $user->id);
            })
            ->orderBy('messages.id','desc')
            ->paginate(20);

        return response()->json(['status_code' => 1, 'data' => ['chats' => $chats], 'message' => 'Chat fetched']);
    }
}

?>