# Scoring System Documentation

## Overview

The MLB Fantasy Draft Helper now includes a flexible, customizable scoring system that allows each league to define its own point values for different statistical categories. This enables you to:

- Use standard presets (Yahoo, ESPN, CBS)
- Create completely custom scoring configurations
- Have different point values for batters and pitchers
- Calculate projected fantasy points for all players based on your league's scoring

## Features

### 1. **League-Specific Scoring**
Each league can have its own unique scoring configuration. One league can use Yahoo's standard scoring while another uses a completely custom system.

### 2. **Separate Batter and Pitcher Categories**
- **Batter Categories**: Singles, Doubles, Triples, Home Runs, Runs, RBI, Stolen Bases, Walks, Strikeouts, etc.
- **Pitcher Categories**: Innings Pitched, Wins, Losses, Saves, Strikeouts, Earned Runs, etc.

### 3. **Flexible Point Values**
- Positive points for good stats (e.g., +10.4 for Home Runs)
- Negative points for bad stats (e.g., -3.2 for Earned Runs)
- Decimal precision (e.g., +2.6 for Singles)

### 4. **Preset Configurations**
Quick setup with industry-standard scoring:
- **Yahoo Fantasy** (Default)
- **ESPN Fantasy**
- **CBS Sports**

## Yahoo MLB Fantasy Scoring (Default)

### Batter Scoring
| Stat | Description | Points |
|------|-------------|--------|
| 1B | Singles | +2.6 |
| 2B | Doubles | +5.2 |
| 3B | Triples | +7.8 |
| HR | Home Runs | +10.4 |
| R | Runs | +1.9 |
| RBI | RBI | +1.9 |
| SB | Stolen Bases | +4.2 |
| BB | Walks | +2.6 |
| HBP | Hit By Pitch | +2.6 |
| CS | Caught Stealing | -2.8 |
| K | Strikeouts | -1.0 |

### Pitcher Scoring
| Stat | Description | Points |
|------|-------------|--------|
| IP | Innings Pitched | +7.4 |
| W | Wins | +4.3 |
| L | Losses | -2.6 |
| CG | Complete Games | +2.6 |
| SO | Shutouts | +5.0 |
| SV | Saves | +5.0 |
| K | Strikeouts | +2.0 |
| H | Hits Allowed | -2.6 |
| ER | Earned Runs | -3.2 |
| BB | Walks Allowed | -2.6 |
| HBP | Hit Batsmen | -2.6 |
| NH | No Hitters | +25.0 |
| PG | Perfect Games | +25.0 |

## How to Use

### 1. Configure Scoring for a League

**Via Web Interface:**
1. Go to your league page
2. Click the "⚙️ Scoring" button
3. Choose one of three options:
   - **Apply a Preset**: Select Yahoo, ESPN, or CBS and click "Apply Preset"
   - **Edit Manually**: Click "Edit Scoring" to customize each category
   - **Keep Default**: Yahoo scoring is applied by default

**Via Command Line:**
```bash
# Apply Yahoo preset (default)
docker-compose exec app php artisan league:setup-scoring {league_id} --preset=yahoo

# Apply ESPN preset
docker-compose exec app php artisan league:setup-scoring {league_id} --preset=espn

# Apply CBS preset
docker-compose exec app php artisan league:setup-scoring {league_id} --preset=cbs
```

### 2. Customize Scoring Categories

1. Navigate to **Leagues → [Your League] → ⚙️ Scoring → Edit Scoring**
2. For each category:
   - Select the stat from the dropdown
   - Enter the points per unit (can be negative)
   - Check/uncheck "Active" to enable/disable
   - Click "Remove" to delete a category
3. Click "+ Add Batter Category" or "+ Add Pitcher Category" to add new stats
4. Click "Save Scoring Configuration"

### 3. Calculate Player Scores

Once you've configured your scoring categories, calculate projected fantasy points for all players:

**Via Web Interface:**
1. Go to **Leagues → [Your League] → ⚙️ Scoring**
2. In the "Calculate Player Scores" section:
   - Select the season (default: 2025)
   - Select projection source (FantasyPros, Steamer, ZiPS, Custom)
   - Click "Calculate Scores"

**Via Command Line:**
```bash
# Calculate scores for a specific league
docker-compose exec app php artisan scores:calculate {league_id}

# Calculate for all leagues
docker-compose exec app php artisan scores:calculate --all

# Specify season and source
docker-compose exec app php artisan scores:calculate {league_id} --season=2025 --source=fantasypros
```

### 4. View Player Rankings

After calculating scores, players will be ranked by their projected fantasy points based on your league's specific scoring configuration.

## Custom Scoring Example

Let's say you want to create a league that heavily rewards power hitting and strikeouts:

**Custom Batter Scoring:**
- HR: +15 (instead of +10.4)
- 2B: +3 (instead of +5.2)
- 3B: +5 (instead of +7.8)
- R: +2
- RBI: +2
- SB: +3
- K: -0.5 (less penalty)

**Custom Pitcher Scoring:**
- K: +3 (instead of +2)
- W: +5
- SV: +7
- IP: +5
- ER: -4 (more penalty)

## Database Schema

### `league_scoring_categories` Table
Stores the scoring configuration for each league.

| Column | Type | Description |
|--------|------|-------------|
| league_id | FK | The league this category belongs to |
| player_type | enum | 'batter' or 'pitcher' |
| stat_code | string | Short code (e.g., 'HR', 'K', 'W') |
| stat_name | string | Full name (e.g., 'Home Runs') |
| points_per_unit | decimal | Points awarded per unit |
| display_order | int | Display order in UI |
| is_active | boolean | Whether this category is active |

### `player_scores` Table
Stores calculated fantasy points for each player.

| Column | Type | Description |
|--------|------|-------------|
| player_id | FK | The player |
| league_id | FK | The league (scoring config) |
| season | int | Season year |
| projection_source | string | Source of projections |
| total_points | decimal | Total projected points |
| category_breakdown | JSON | Points by category |
| calculated_at | timestamp | When calculated |

## API / Service Layer

### `ScoringCalculator` Service

```php
// Calculate scores for all players in a league
$calculator->calculateLeagueScores($league, $season, $projectionSource);

// Calculate score for a single player
$calculator->calculatePlayerScore($player, $league, $projection);

// Get top players by score
$calculator->getTopPlayers($league, $limit, $season, $projectionSource);
```

## Tips

1. **Start with a Preset**: Use Yahoo, ESPN, or CBS as a starting point, then customize
2. **Test Your Scoring**: Calculate scores and review the top players to ensure your scoring makes sense
3. **Recalculate After Changes**: If you modify scoring categories, recalculate player scores
4. **Different Leagues, Different Scoring**: Each league is independent - experiment with different configurations

## Future Enhancements

- Import scoring configurations from other platforms
- Share scoring configurations between leagues
- Historical scoring analysis
- Scoring impact simulator

