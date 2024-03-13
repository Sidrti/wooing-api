<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\Notification;
use App\Models\Streaming;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function fetchNotications()
    {
        $user = auth()->user();
        $notification = Notification::where('user_id',$user->id)
            ->get();

        return response()->json(['status_code' => 1, 'data' => ['notifications' => $notification], 'message' => 'Friends fetched successfully']);
    }
}
