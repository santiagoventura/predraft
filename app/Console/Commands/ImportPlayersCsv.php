<?php

namespace App\Console\Commands;

use App\Services\FantasyProsImporter;
use Illuminate\Console\Command;

class ImportPlayersCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:players-csv 
                            {file : Path to the CSV file}
                            {--season=2025 : Season year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import players from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle(FantasyProsImporter $importer): int
    {
        $file = $this->argument('file');
        $season = $this->option('season');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Importing players from {$file}...");

        try {
            $result = $importer->importPlayersFromCsv($file, $season);

            $this->info("✓ Import complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Imported', $result['imported']],
                    ['Updated', $result['updated']],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

