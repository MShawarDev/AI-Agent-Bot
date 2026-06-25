<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\ReportIngestionService;
use Illuminate\Console\Command;

class IngestSalesReports extends Command
{
    protected $signature = 'sales:ingest
        {--path=     : Directory holding the report files (defaults to <base>/data)}
        {--client=   : Client slug or ID to associate the reports with}';

    protected $description = 'Parse sales report files and store their extracted text in the database';

    public function __construct(private ReportIngestionService $ingestion)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dir = $this->option('path') ?: base_path('data');

        $client   = $this->resolveClient();
        $clientId = $client?->id;

        if ($this->option('client') && ! $client) {
            $this->error('Client "'.$this->option('client').'" not found.');

            return self::FAILURE;
        }

        $extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $pattern    = rtrim($dir, '/\\').'/*.{'.implode(',', $extensions).'}';
        $files      = glob($pattern, GLOB_BRACE) ?: [];

        if (empty($files)) {
            $this->warn("No report files found in {$dir}");

            return self::SUCCESS;
        }

        foreach ($files as $path) {
            $name = basename($path);

            try {
                $report = $this->ingestion->ingest($path, $name, $clientId);
                $this->info("Ingested {$name}  ->  {$report->label}");
            } catch (\Throwable $e) {
                $this->error("Failed to parse {$name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveClient(): ?Client
    {
        $value = $this->option('client');

        if (! $value) {
            return null;
        }

        return is_numeric($value)
            ? Client::find($value)
            : Client::where('slug', $value)->first();
    }
}
