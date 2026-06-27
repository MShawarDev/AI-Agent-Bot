<?php

namespace App\Services;

use App\Models\SalesReport;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ReportIngestionService
{
    /**
     * Parse a file and upsert a SalesReport row for the given client.
     *
     * @param  string  $filePath  Absolute path to the file
     * @param  string  $fileName  Original filename (used for the unique key)
     *
     * @throws \RuntimeException if the file type is unsupported or parsing fails
     */
    public function ingest(string $filePath, string $fileName, ?int $clientId = null): SalesReport
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $text = match ($ext) {
            'pdf' => $this->parsePdf($filePath),
            'docx' => $this->parseDocx($filePath),
            'doc' => $this->parseDoc($filePath),
            'xlsx', 'xls' => $this->parseSpreadsheet($filePath),
            'csv' => $this->parseCsv($filePath),
            default => throw new \RuntimeException("Unsupported file type: {$ext}"),
        };

        [$label, $date] = $this->labelAndDate($fileName);

        return SalesReport::updateOrCreate(
            ['client_id' => $clientId, 'source_file' => $fileName],
            ['label' => $label, 'report_date' => $date, 'content' => $text],
        );
    }

    private function parsePdf(string $path): string
    {
        return trim((new PdfParser)->parseFile($path)->getText());
    }

    private function parseDocx(string $path): string
    {
        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException('phpoffice/phpword is required for .docx files. Run: composer require phpoffice/phpword');
        }

        $word = IOFactory::load($path);
        $lines = [];

        foreach ($word->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    $lines[] = $element->getText();
                } elseif ($element instanceof Table) {
                    foreach ($element->getRows() as $row) {
                        $cells = [];
                        foreach ($row->getCells() as $cell) {
                            $cells[] = strip_tags($cell->getTextContent());
                        }
                        $lines[] = implode("\t", $cells);
                    }
                } elseif (method_exists($element, 'getText')) {
                    $lines[] = $element->getText();
                }
            }
        }

        return trim(implode("\n", $lines));
    }

    private function parseDoc(string $path): string
    {
        // Legacy binary .doc — best-effort text extraction
        $content = file_get_contents($path);
        // Strip binary noise; extract readable ASCII/UTF-8 runs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{00A0}-\x{FFFF}]/u', ' ', $text ?? '');
        $text = preg_replace('/  +/', ' ', $text ?? '');

        return trim($text ?? '');
    }

    private function parseSpreadsheet(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('phpoffice/phpspreadsheet is required for .xlsx/.xls files. Run: composer require phpoffice/phpspreadsheet');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $lines = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $lines[] = "=== Sheet: {$sheet->getTitle()} ===";
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = (string) $cell->getFormattedValue();
                }
                $cells = array_map('trim', $cells);
                // Skip entirely empty rows
                if (array_filter($cells) !== []) {
                    $lines[] = implode("\t", $cells);
                }
            }
        }

        return trim(implode("\n", $lines));
    }

    private function parseCsv(string $path): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not open CSV file for reading.');
        }

        $lines = [];
        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $row;
                $lines[] = implode("\t", $row);

                continue;
            }
            $cells = array_map('trim', $row);
            if (array_filter($cells) !== []) {
                $lines[] = implode("\t", $cells);
            }
        }

        fclose($handle);

        return trim(implode("\n", $lines));
    }

    /**
     * Derive a human label and Carbon date from a filename like
     * "DailyCloseout_6-19-2026_to_6-19-2026.pdf".
     *
     * @return array{0: string, 1: ?Carbon}
     */
    public function labelAndDate(string $name): array
    {
        if (preg_match('/(\d{1,2})-(\d{1,2})-(\d{4})/', $name, $m)) {
            $date = Carbon::createFromDate((int) $m[3], (int) $m[1], (int) $m[2])->startOfDay();

            return [$date->format('j M Y'), $date];
        }

        return [pathinfo($name, PATHINFO_FILENAME), null];
    }
}
