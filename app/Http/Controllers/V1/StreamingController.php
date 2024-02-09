<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Streaming;
use Illuminate\Http\Request;

class StreamingController extends Controller
{
   public function create(Request $request)
   {
        $request->validate([
            'meeting_id' => 'required',
            'type' => 'required|in:STREAM,VIDEO,AUDIO',
        ]);

        $user = auth()->user();

        $existingStream = Streaming::where('user_id', $user->id)
        ->where('status', 'ACTIVE')
        ->first();

        // If an active stream is found, update its status to 'ENDED'
        if ($existingStream) {
            $existingStream->update(['status' => 'ENDED']);
        }

        $streaming = Streaming::create([
            'user_id' => $user->id,
            'meeting_id' => $request->input('meeting_id'),
            'type' => $request->input('type'),
        ]);
        return response()->json(['status_code' => 1, 'data' => ['streaming' => $streaming,'previous_active_stream' => $existingStream], 'message' => 'Stream Started']);
   }
   public function endStream()
   {
        $user = auth()->user();

        $stream = Streaming::where('user_id', $user->id)
        ->where('status', 'ACTIVE')
        ->first();

        // If an active stream is found, update its status to 'ENDED'
        if ($stream) {
            $stream->update(['status' => 'ENDED']);
            return response()->json(['status_code' => 1, 'data' => ['meeting_id' => $stream->meeting_id], 'message' => 'Stream ended']);
        }
        return response()->json(['status_code' => 2, 'data' => [], 'message' => 'No active stream found']);
   }
   public function fetchStreams()
   {
        $streams = Streaming::with(['user:id,name,profile_picture']) // Adjust the relationship as needed
        ->orderByDesc('created_at')
        ->where('status','ACTIVE')
        ->paginate(10); // Adjust the number of posts per page as needed

        return response()->json(['status_code' => 1, 'data' => ['streams' => $streams], 'message' => 'Stream fetched']);
   }
   public function fetchStreamsByUserId(Request $request)
   {
        $request->validate([
            'user_id' => 'required',
        ]);

        $stream = Streaming::with(['user:id,name,profile_picture']) 
        ->where('status','ACTIVE')
        ->where('user_id',$request->input('user_id'))
        ->first(); 

        return response()->json(['status_code' => 1, 'data' => ['stream' => $stream], 'message' => 'Stream fetched']);
   }
}
