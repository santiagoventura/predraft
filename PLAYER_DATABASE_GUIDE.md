# MLB Player Database Management Guide

## 📊 Overview

The MLB Fantasy Draft Helper uses a **pre-populated database table** with MLB players. This list only needs to be updated when new players are added or player information changes (trades, position changes, etc.).

## ✅ Current Status

- **Total Players**: 277
- **Batters**: 127
- **Pitchers**: 150
- **Season**: 2025
- **Source**: CSV files (players.csv, my_rank.csv, third_rank.csv)

## 📁 Database Structure

### Players Table
```sql
players
├── id (primary key)
├── name (string)
├── mlb_team (string) - Team abbreviation (LAD, NYY, etc.)
├── positions (string) - Comma-separated positions (SS, OF, 1B,3B, etc.)
├── primary_position (string) - Main position
├── is_pitcher (boolean) - True for pitchers, false for batters
├── bats (enum: R/L/S) - Batting hand
├── throws (enum: R/L) - Throwing hand
├── age (integer)
├── external_id (string) - ID from external source
├── metadata (json) - Additional data
├── created_at, updated_at, deleted_at
```

### Related Tables
- **player_rankings** - Player rankings from various sources
- **player_projections** - Statistical projections
- **player_scores** - Calculated fantasy points per league
- **player_notes** - User notes on players

## 🔧 Import Methods

### Method 1: Using Artisan Commands (Recommended)

**Import Players:**
```bash
docker-compose exec app php artisan import:players-csv players.csv --season=2025
```

**Import Rankings:**
```bash
docker-compose exec app php artisan import:rankings-csv my_rank.csv --source=my_rankings --season=2025
docker-compose exec app php artisan import:rankings-csv third_rank.csv --source=third_party --season=2025
```

### Method 2: Using Database Seeder

**Run all seeders:**
```bash
docker-compose exec app php artisan db:seed
```

**Run only player seeder:**
```bash
docker-compose exec app php artisan db:seed --class=PlayerSeeder
```

### Method 3: Fresh Migration + Seed

**Reset database and import all data:**
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

## 📝 CSV File Formats

### players.csv
```csv
id,name team,positions
1,Shohei Ohtani (Batter) LAD,UTIL
2,Aaron Judge NYY,OF
3,Bobby Witt Jr KC,SS
6,Vladimir Guerrero Jr TOR,"1B,3B"
8,Mookie Betts LAD,"2B,SS,OF"
```

**Format:**
- Column 1: External ID (unique identifier)
- Column 2: Player name + team (space-separated, team is last word)
- Column 3: Positions (comma-separated if multiple)

**Notes:**
- `(Batter)` or `(Pitcher)` designation is optional and will be removed
- Team abbreviations should be 2-3 letters (LAD, NYY, KC, etc.)
- Positions determine if player is pitcher (P, SP, RP) or batter

### my_rank.csv / third_rank.csv
```csv
rank,name
1,Shohei Ohtani (Batter)
2,Aaron Judge
3,Bobby Witt Jr
```

**Format:**
- Column 1: Rank (integer)
- Column 2: Player name (must match name in players table)

## 🔄 Updating Player Data

### When to Update

Update the player database when:
- ✅ **New players** are called up or become fantasy-relevant
- ✅ **Trades** happen (update mlb_team)
- ✅ **Position changes** occur (update positions)
- ✅ **Season changes** (new year, new player pool)
- ✅ **Injuries** affect availability (use soft deletes)

### How to Update

**Option 1: Update CSV and Re-import**
1. Edit `players.csv` with new/updated player data
2. Run import command:
   ```bash
   docker-compose exec app php artisan import:players-csv players.csv --season=2025
   ```
3. The importer uses `updateOrCreate()` so existing players will be updated

**Option 2: Manual Database Update**
```bash
docker-compose exec app php artisan tinker
```
```php
// Update a player's team
$player = App\Models\Player::where('name', 'Juan Soto')->first();
$player->update(['mlb_team' => 'NYM']);

// Add a new player
App\Models\Player::create([
    'name' => 'New Player',
    'mlb_team' => 'LAD',
    'positions' => 'SS',
    'primary_position' => 'SS',
    'is_pitcher' => false,
    'external_id' => '999',
]);

// Soft delete a player (injured/retired)
$player->delete();
```

**Option 3: Create Migration for Bulk Updates**
For major updates (like start of new season), create a migration:
```bash
docker-compose exec app php artisan make:migration update_player_teams_2025
```

## 📊 Querying Players

### Common Queries

**Get all available batters:**
```php
$batters = Player::where('is_pitcher', false)->get();
```

**Get all available pitchers:**
```php
$pitchers = Player::where('is_pitcher', true)->get();
```

**Get players by team:**
```php
$dodgers = Player::where('mlb_team', 'LAD')->get();
```

**Get players by position:**
```php
$shortstops = Player::where('positions', 'like', '%SS%')->get();
```

**Get top-ranked players:**
```php
$topPlayers = Player::with(['rankings' => function($q) {
    $q->where('source', 'my_rankings')->orderBy('overall_rank');
}])->get();
```

## 🎯 Integration with Draft System

### How Players Are Used

1. **Draft Setup**: When creating a draft, all active players are available
2. **Player Selection**: Draft board shows available players (not yet drafted)
3. **AI Recommendations**: Uses player rankings and projections
4. **Scoring**: Player projections + league scoring = fantasy points
5. **Team Rosters**: Tracks which players are on which team

### Available Players Logic

```php
// In DraftSimulator service
public function getAvailablePlayers(Draft $draft)
{
    $draftedPlayerIds = $draft->picks()
        ->whereNotNull('player_id')
        ->pluck('player_id');
    
    return Player::whereNotIn('id', $draftedPlayerIds)
        ->orderBy('name')
        ->get();
}
```

## 🔍 Verification Commands

**Check player count:**
```bash
docker-compose exec app php artisan tinker --execute="echo App\Models\Player::count();"
```

**Check batters vs pitchers:**
```bash
docker-compose exec app php artisan tinker --execute="
echo 'Batters: ' . App\Models\Player::where('is_pitcher', false)->count() . PHP_EOL;
echo 'Pitchers: ' . App\Models\Player::where('is_pitcher', true)->count() . PHP_EOL;
"
```

**View sample players:**
```bash
docker-compose exec app php artisan tinker --execute="
App\Models\Player::take(10)->get()->each(function(\$p) {
    echo \$p->name . ' (' . \$p->mlb_team . ') - ' . \$p->positions . PHP_EOL;
});
"
```

**Check for duplicates:**
```bash
docker-compose exec app php artisan tinker --execute="
\$duplicates = App\Models\Player::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(*) > 1')
    ->get();
echo 'Duplicates: ' . \$duplicates->count() . PHP_EOL;
"
```

## 📦 Backup and Restore

### Export Players to CSV
```bash
docker-compose exec app php artisan tinker --execute="
\$file = fopen('players_backup.csv', 'w');
App\Models\Player::all()->each(function(\$p) use (\$file) {
    fputcsv(\$file, [\$p->external_id, \$p->name . ' ' . \$p->mlb_team, \$p->positions]);
});
fclose(\$file);
echo 'Exported to players_backup.csv' . PHP_EOL;
"
```

### Database Backup
```bash
docker-compose exec db mysqldump -u root -proot mlb_draft_helper players > players_backup.sql
```

### Restore from Backup
```bash
docker-compose exec -T db mysql -u root -proot mlb_draft_helper < players_backup.sql
```

## 🚀 Best Practices

1. **Keep CSV Files Updated**: Maintain `players.csv` as the source of truth
2. **Version Control**: Commit CSV files to git for history tracking
3. **Test Imports**: Test on development before production
4. **Backup Before Updates**: Always backup before major changes
5. **Use Soft Deletes**: Don't hard delete players, use soft deletes
6. **Document Changes**: Add comments in CSV or migration for major updates
7. **Validate Data**: Check for duplicates and missing required fields

## 📅 Seasonal Workflow

### Start of New Season (Example: 2026)

1. **Update CSV files** with new player data
2. **Update season in commands**:
   ```bash
   docker-compose exec app php artisan import:players-csv players.csv --season=2026
   ```
3. **Archive old projections** (optional)
4. **Import new rankings** for new season
5. **Test draft with new data**

---

**Status**: ✅ **277 MLB Players Loaded and Ready**

The player database is fully populated and ready for drafts. Players only need to be updated when roster changes occur or at the start of a new season! 🎉

