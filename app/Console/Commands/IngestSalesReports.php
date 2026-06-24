<?php

namespace App\Console\Commands;

use App\Models\SalesReport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Smalot\PdfParser\Parser;

class IngestSalesReports extends Command
{
    protected $signature = 'sales:ingest {--path= : Directory holding the PDF reports (defaults to <base>/data)}';

    protected $description = 'Parse the sales PDF reports locally and store their extracted text in the database';

    public function handle(): int
    {
        $dir = $this->option('path') ?: base_path('data');

        $files = glob(rtrim($dir, '/\\').'/*.pdf');

        if (empty($files)) {
            $this->warn("No PDF files found in {$dir}");

            return self::SUCCESS;
        }

        $parser = new Parser;

        foreach ($files as $path) {
            $name = basename($path);

            try {
                $text = trim($parser->parseFile($path)->getText());
            } catch (\Throwable $e) {
                $this->error("Failed to parse {$name}: {$e->getMessage()}");

                continue;
            }

            [$label, $date] = $this->labelAndDate($name);

            SalesReport::updateOrCreate(
                ['source_file' => $name],
                ['label' => $label, 'report_date' => $date, 'content' => $text],
            );

            $this->info("Ingested {$name}  ->  {$label}");
        }

        return self::SUCCESS;
    }

    /**
     * Derive a human label and date from a filename like
     * "DailyCloseout_6-19-2026_to_6-19-2026.pdf Report.pdf".
     *
     * @return array{0: string, 1: ?Carbon}
     */
    private function labelAndDate(string $name): array
    {
        if (preg_match('/(\d{1,2})-(\d{1,2})-(\d{4})/', $name, $m)) {
            $date = Carbon::createFromDate((int) $m[3], (int) $m[1], (int) $m[2])->startOfDay();

            return [$date->format('j M Y'), $date];
        }

        return [pathinfo($name, PATHINFO_FILENAME), null];
    }
}
