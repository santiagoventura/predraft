<?php

namespace Database\Seeders;

use App\Services\FantasyProsImporter;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $importer = app(FantasyProsImporter::class);
        
        $this->command->info('Importing MLB players from CSV...');
        
        // Import players from CSV
        $playersFile = base_path('players.csv');
        if (file_exists($playersFile)) {
            $result = $importer->importPlayersFromCsv($playersFile, 2025);
            $this->command->info("✓ Players imported: {$result['imported']}, updated: {$result['updated']}");
        } else {
            $this->command->warn("⚠ players.csv not found at {$playersFile}");
        }
        
        // Import rankings from my_rank.csv
        $myRankFile = base_path('my_rank.csv');
        if (file_exists($myRankFile)) {
            $result = $importer->importRankingsFromCsv($myRankFile, 'my_rankings', 2025);
            $this->command->info("✓ My rankings imported: {$result['imported']}");
        } else {
            $this->command->warn("⚠ my_rank.csv not found at {$myRankFile}");
        }
        
        // Import rankings from third_rank.csv
        $thirdRankFile = base_path('third_rank.csv');
        if (file_exists($thirdRankFile)) {
            $result = $importer->importRankingsFromCsv($thirdRankFile, 'third_party_rankings', 2025);
            $this->command->info("✓ Third-party rankings imported: {$result['imported']}");
        } else {
            $this->command->warn("⚠ third_rank.csv not found at {$thirdRankFile}");
        }
        
        $this->command->info('✓ Player seeding complete!');
    }
}

