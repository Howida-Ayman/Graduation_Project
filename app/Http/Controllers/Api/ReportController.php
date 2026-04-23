<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Submit a new report
    public function store(StoreReportRequest $request)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            $data = [
                'user_id' => $user->id,
                'subject' => $request->subject,
                'description' => $request->description,
                'status' => 'pending',
            ];

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/reports'), $fileName);
                $data['attachment'] = 'uploads/reports/' . $fileName;
            }

            $report = Report::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully',
                'data' => new ReportResource($report)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get user's reports
    public function myReports(Request $request)
    {
        $reports = $request->user()
            ->reports()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ReportResource::collection($reports)
        ]);
    }


 
}