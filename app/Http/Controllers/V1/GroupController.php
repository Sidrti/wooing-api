<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUsers;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $request->input('name'),
            'admin_id' => auth()->user()->id
        ]);

        GroupUsers::create([
            'user_id' => auth()->user()->id,
            'group_id' => $group->id,
        ]);
        $this->addUserToGroupId($request->input('user_ids'),$group->id,auth()->user()->id);

        return response()->json(['status_code' => 1, 'message' => 'Group created successfully']);
    }
    public function leaveGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);
        $user = auth()->user();

        GroupUsers::where('user_id',$user->id)
            ->where('group_id',$request->input('group_id'))
            ->delete();

        return response()->json(['status_code' => 1, 'message' => 'Group left']);
    }
    public function addAdditionalUserIdToGroupId(Request $request) 
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        $this->addUserToGroupId($request->input('user_ids'),$request->input('group_id'),auth()->user()->id);

        return response()->json(['status_code' => 1, 'message' => 'User added to group']);
    }
    public function fetchGroupInfo(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);
        $user = auth()->user();
        $groupId = $request->input('group_id');
    
        // Check if the authenticated user is a member of the specified group
        $isMember = GroupUsers::where('user_id', $user->id)
            ->where('group_id', $groupId)
            ->exists();
    
        if (!$isMember) {
            return response()->json(['status_code' => 0, 'message' => 'User is not a member of the specified group'], 403);
        }

        $group = Group::findOrFail($groupId);
        $groupName = $group->name;
        // Fetch group users
        $groupUsers = GroupUsers::where('group_id', $groupId)
            ->with('user:id,name,profile_picture')
            ->get();
    
        $groupInfo = [ 
            'id' => $groupId,
            'name' => $groupName,
            'users' => $groupUsers
        ];

        return response()->json([
            'status_code' => 1,
            'data' => [
                'group' => $groupInfo,
            ],
            'message' => 'Group information fetched successfully'
        ]);
    }
    private function addUserToGroupId($userIds,$groupId,$authUserId)
    {
        foreach ($userIds as $userId) {
            if($userId != $authUserId) {
                $existingEntry = GroupUsers::where('user_id', $userId)
                    ->where('group_id', $groupId)
                    ->exists();

                if (!$existingEntry) { 
                    GroupUsers::create([
                        'user_id' => $userId,
                        'group_id' => $groupId,
                    ]);
                }
            }
        }
    }
}
