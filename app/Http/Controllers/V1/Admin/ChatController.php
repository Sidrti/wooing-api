<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUsers;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function fetchMessages(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:users,id',
        ]);

        $groupMessages = Message::with(['sender'])
        ->where('group_id', $request->input('group_id'))
        ->orderBy('id', 'desc')
        ->paginate(20);
    
        return response()->json(['status_code' => 1, 'data' => ['chats' => $groupMessages], 'message' => 'Chat fetched']);
    }
    public function fetchGroups(Request $request)
    {
        $request->validate([
            'primary_user_id' => 'required|exists:users,id',
            'secondary_user_id' => 'required|exists:users,id',
        ]);
    
        $primaryUserId = $request->input('primary_user_id');
        $secondaryUserId = $request->input('secondary_user_id');
    
        $groupIds = GroupUsers::whereIn('user_id', [$primaryUserId, $secondaryUserId])
            ->groupBy('group_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('group_id');
    
    
            $groupedMessages = [];
            foreach ($groupIds as $groupId) {

                $group = Group::where('id',$groupId)->first();

                $groupData = [
                    'group_id' => $groupId,
                    'group_name' => $group->name, // Assuming the group name is not directly available in the message model
                    'group_type' => $group->type, // Assuming the group type is not directly available in the message model
                ];
        
                $groupedMessages[] = $groupData;
            }
    
        return response()->json(['status_code' => 1, 'data' => ['groups' => $groupedMessages], 'message' => 'Group fetched']);
    }
    
}
