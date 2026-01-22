<?php

namespace App\Console\Commands;

use App\Services\FantasyProsImporter;
use Illuminate\Console\Command;

class ImportRankingsCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:rankings-csv 
                            {file : Path to the CSV file}
                            {--source=custom : Source name for these rankings}
                            {--season=2025 : Season year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import player rankings from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle(FantasyProsImporter $importer): int
    {
        $file = $this->argument('file');
        $source = $this->option('source');
        $season = $this->option('season');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Importing rankings from {$file} (source: {$source})...");

        try {
            $result = $importer->importRankingsFromCsv($file, $source, $season);

            $this->info("✓ Import complete!");
            $this->info("Imported: {$result['imported']} rankings");

            if (!empty($result['not_found'])) {
                $this->warn("Players not found: " . count($result['not_found']));
                if ($this->option('verbose')) {
                    $this->table(['Player Name'], array_map(fn($n) => [$n], $result['not_found']));
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

