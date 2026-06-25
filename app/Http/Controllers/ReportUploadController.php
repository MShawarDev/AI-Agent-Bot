<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadReportRequest;
use App\Models\SalesReport;
use App\Services\ReportIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportUploadController extends Controller
{
    public function __construct(private ReportIngestionService $ingestion) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $clientId = $user->client_id;

        $reports = SalesReport::where('client_id', $clientId)
            ->orderByDesc('report_date')
            ->get(['id', 'label', 'report_date', 'source_file', 'created_at']);

        $max    = config('uploads.max_files_per_client', 50);
        $count  = $reports->count();

        return view('reports.index', compact('reports', 'max', 'count'));
    }

    public function store(UploadReportRequest $request)
    {
        $user     = $request->user();
        $clientId = $user->client_id;

        // Enforce per-client file count limit
        $max   = config('uploads.max_files_per_client', 50);
        $count = SalesReport::where('client_id', $clientId)->count();

        if ($count >= $max) {
            return back()->withErrors(['file' => "Upload limit reached ({$max} files per account). Delete a report before uploading more."]);
        }

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();

        // Store privately under storage/app/reports/{clientId}/
        $storagePath = $file->storeAs("reports/{$clientId}", $fileName, 'local');

        if ($storagePath === false) {
            return back()->withErrors(['file' => 'Failed to save the uploaded file. Check storage permissions.']);
        }

        $fullPath = Storage::disk('local')->path($storagePath);

        try {
            $this->ingestion->ingest($fullPath, $fileName, $clientId);
        } catch (\Throwable $e) {
            \Log::error('Report ingestion failed', ['error' => $e->getMessage(), 'file' => $fileName]);

            return back()->withErrors(['file' => 'Could not parse the uploaded file: '.$e->getMessage()]);
        }

        return redirect()->route('reports.index')->with('status', "\"{$fileName}\" uploaded and processed.");
    }

    public function destroy(Request $request, SalesReport $report)
    {
        // Only allow users to delete their own client's reports
        if ($report->client_id !== $request->user()->client_id && ! $request->user()->is_admin) {
            abort(403);
        }

        $report->delete();

        return redirect()->route('reports.index')->with('status', 'Report deleted.');
    }
}
