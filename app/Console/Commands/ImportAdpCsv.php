<?php

namespace App\Console\Commands;

use App\Services\FantasyProsImporter;
use Illuminate\Console\Command;

class ImportAdpCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:adp-csv 
                            {file : Path to the ADP CSV file}
                            {--source=fantasypros_adp : Source name for these ADP rankings}
                            {--season=2025 : Season year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ADP (Average Draft Position) from a CSV file';

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

        $this->info("Importing ADP from {$file} (source: {$source})...");
        $this->info("Expected CSV format:");
        $this->line('  "RK","PLAYER NAME",TEAM,"POS","BEST","WORST","AVG.","STD.DEV","ECR VS. ADP"');

        try {
            $result = $importer->importAdpFromCsv($file, $source, $season);

            $this->newLine();
            $this->info("✓ Import complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Imported (new)', $result['imported']],
                    ['Updated (existing)', $result['updated']],
                    ['Total processed', $result['imported'] + $result['updated']],
                ]
            );

            if (!empty($result['not_found'])) {
                $this->newLine();
                $this->warn("Players not found: " . count($result['not_found']));
                if ($this->option('verbose') || count($result['not_found']) <= 10) {
                    $this->table(
                        ['Player Name'],
                        array_map(fn($n) => [$n], $result['not_found'])
                    );
                } else {
                    $this->line("  (Use -v flag to see full list)");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

