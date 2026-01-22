# MLB Fantasy Draft Helper

A Laravel-based web application that helps users draft fantasy MLB teams with AI-powered recommendations using Google Gemini.

## Features

- **League Configuration**: Define custom league settings with flexible roster positions
- **Draft Simulation**: Snake draft with intelligent pick tracking
- **AI Recommendations**: Get top 5 player recommendations with detailed explanations for each pick
- **FantasyPros Integration**: Import player rankings and projections
- **Strategic Analysis**: AI considers positional scarcity, team needs, and draft dynamics

## Tech Stack

- **Backend**: Laravel 11.x (LTS) with PHP 8.3
- **Database**: MariaDB 11.2
- **AI**: Google Gemini API
- **Docker**: Containerized development environment

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git
- Google Gemini API key ([Get one here](https://makersuite.google.com/app/apikey))

### Installation

**Option 1: Automated Setup (Recommended)**

```bash
# Make setup script executable
chmod +x setup.sh

# Run setup
./setup.sh
```

**Option 2: Manual Setup**

1. **Install Laravel (if not already present)**
   ```bash
   composer create-project laravel/laravel:^11.0 .
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Configure your Gemini API key**
   Edit `.env` and add your Google Gemini API key:
   ```
   GEMINI_API_KEY=your_api_key_here
   GEMINI_MODEL=gemini-1.5-pro
   ```

4. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

5. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   ```

6. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

7. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

8. **Import player data**
   ```bash
   # Import from existing CSV files
   docker-compose exec app php artisan import:players-csv players.csv
   docker-compose exec app php artisan import:rankings-csv my_rank.csv --source=my_rankings
   docker-compose exec app php artisan import:rankings-csv third_rank.csv --source=third_party
   ```

9. **Access the application**
   Open your browser to: http://localhost:8090

## Usage

### Creating a League

1. Navigate to `/leagues/create`
2. Define league settings:
   - League name
   - Number of teams
   - Roster positions (C, 1B, 2B, SS, 3B, OF, UTIL, P, etc.)

### Running a Draft

1. Create a draft from your league
2. Click "Start Draft" to begin
3. For each pick:
   - View AI's top 5 recommendations
   - Read detailed explanations for each player
   - Select a player to draft
4. Continue until all roster spots are filled

### AI Recommendations

The AI considers:
- **Positional Needs**: What positions your team still needs to fill
- **Draft Dynamics**: How the room is drafting (pitcher-heavy vs hitter-heavy)
- **Scarcity**: Positional cliffs and availability
- **Team Balance**: Speed, power, ratios, saves distribution
- **Risk/Upside**: Injury history and breakout potential
- **Expert Strategy**: Common fantasy baseball best practices

## Development

### Useful Commands

```bash
# Access the app container
docker-compose exec app bash

# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Run tests (when implemented)
docker-compose exec app php artisan test
```

### Project Structure

```
app/
├── Models/              # Eloquent models
│   ├── League.php
│   ├── Team.php
│   ├── Player.php
│   ├── Draft.php
│   └── DraftPick.php
├── Services/            # Business logic
│   ├── FantasyProsImporter.php
│   ├── DraftSimulator.php
│   └── DraftAIService.php
├── Http/
│   └── Controllers/     # API & web controllers
database/
├── migrations/          # Database schema
└── seeders/            # Sample data
```

## Database Schema

See migrations in `database/migrations/` for complete schema.

Key tables:
- `leagues` - League configurations
- `league_positions` - Roster slot definitions per league
- `teams` - Teams within leagues
- `players` - MLB player database
- `player_rankings` - Rankings from various sources
- `player_projections` - Statistical projections
- `drafts` - Draft instances
- `draft_picks` - Individual picks with AI recommendations

## API Endpoints (Future)

The application is designed to support a JSON API for rich front-end integration:

- `GET /api/leagues` - List leagues
- `POST /api/leagues` - Create league
- `GET /api/drafts/{id}` - Get draft state
- `POST /api/drafts/{id}/next-pick` - Advance to next pick
- `GET /api/drafts/{id}/recommendations` - Get AI recommendations

## Extending the System

### Adding New Data Sources

Implement the `PlayerDataImporter` interface and register in `AppServiceProvider`.

### Custom Scoring Formats

Extend `DraftAIService` to accept scoring format parameters (roto, H2H, points).

### Keeper/Dynasty Logic

Add `keeper_settings` to leagues table and modify draft initialization.

## License

MIT License

