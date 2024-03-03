<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\UserReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'reported_user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
        ]);
        $userId = auth()->user()->id;
        $path = '';
        if($request->has('media'))
        {
            $file = $request->file('media');
            $dir = '/uploads/reports/';
            $path = Helper::saveImageToServer($file,$dir);
        }
        $report = new UserReport([
            'reporter_id' => $userId,
            'reported_user_id' => $request->input('reported_user_id'),
            'reason' => $request->input('reason'),
            'media_path' => $path,
        ]);

        $report->save();
        return response()->json(['status_code' => 1, 'message' => 'Report inserted successfully']);
    }
}

?>