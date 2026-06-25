<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalesReport;

class ReportController extends Controller
{
    public function index(Client $client)
    {
        $reports = $client->salesReports()->orderByDesc('report_date')->get();

        return view('admin.reports.index', compact('client', 'reports'));
    }

    public function destroy(SalesReport $report)
    {
        $client = $report->client;
        $report->delete();

        return redirect()->route('admin.clients.reports.index', $client)->with('status', 'Report deleted.');
    }
}
