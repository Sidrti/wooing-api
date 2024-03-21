<?php

namespace App\Http\Controllers\V1\Admin;
use App\Http\Controllers\Controller;
use App\Models\UserReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function fetchReports()
    {
        $reports = UserReport::with(['reporter','reportee'])->paginate(10);
        return response()->json(['status_code' => 1, 'data' => ['reports' => $reports], 'message' => 'Reports fetched']);
    }
    public function updateStatus(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:user_reports,id',
            'status' => 'required|in:RESOLVED,NO_ACTION',
        ]);
        $userReport = UserReport::findOrFail($request->input('report_id'));
        $userReport->status = $request->input('status');
        $userReport->save();

        return response()->json([
            'status_code' => 1,
            'message' => 'User report status updated successfully',
        ]);
    }
}