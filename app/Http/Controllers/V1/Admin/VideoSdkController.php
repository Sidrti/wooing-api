<?php

namespace App\Http\Controllers\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Streaming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VideoSdkController extends Controller
{
    public function fetchStreams(Request $request)
    {
        
        $streams = Streaming::with(['user:id,name,profile_picture']) 
        ->orderByDesc('created_at')
        ->paginate(10); 

        foreach ($streams as $stream) {
            $stream->meeting_details = json_decode(Helper::getAllHlsRecordings($stream->meeting_id))->data;
        }
        
        return response()->json(['status_code' => 1, 'data' => ['stream' => $streams], 'message' => 'Stream fetched']);
    }

}