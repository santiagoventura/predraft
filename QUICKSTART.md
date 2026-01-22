# Quick Start Guide

This guide will get you up and running with the MLB Fantasy Draft Helper in under 10 minutes.

## Prerequisites

1. **Docker & Docker Compose** installed on your system
2. **Google Gemini API Key** - Get one free at https://makersuite.google.com/app/apikey

## Step-by-Step Setup

### 1. Get a Gemini API Key

1. Visit https://makersuite.google.com/app/apikey
2. Sign in with your Google account
3. Click "Create API Key"
4. Copy the key (you'll need it in step 4)

### 2. Prepare the Environment

Since this is a fresh Laravel project, you'll need to install Laravel first:

```bash
# Install Laravel (if not already present)
composer create-project laravel/laravel:^11.0 temp-laravel
mv temp-laravel/* .
mv temp-laravel/.* . 2>/dev/null || true
rm -rf temp-laravel

# Or if Laravel is already installed, just copy the .env file
cp .env.example .env
```

### 3. Configure Environment

Edit `.env` and update these values:

```env
APP_NAME="MLB Fantasy Draft Helper"
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=mlb_draft
DB_USERNAME=mlb_draft_user
DB_PASSWORD=secret

GEMINI_API_KEY=your_actual_api_key_here
GEMINI_MODEL=gemini-1.5-pro
```

### 4. Start Docker

```bash
docker-compose up -d
```

Wait about 30 seconds for the database to initialize.

### 5. Install Dependencies & Setup Database

```bash
# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate
```

### 6. Import Player Data

```bash
# Import players from the existing CSV
docker-compose exec app php artisan import:players-csv players.csv

# Import your custom rankings
docker-compose exec app php artisan import:rankings-csv my_rank.csv --source=my_rankings

# Import third-party rankings
docker-compose exec app php artisan import:rankings-csv third_rank.csv --source=third_party
```

### 7. Access the Application

Open your browser to: **http://localhost:8090**

## First Draft Walkthrough

### Create Your First League

1. Click **"Create New League"**
2. Enter a league name (e.g., "2025 Mock League")
3. Set number of teams (default: 12)
4. Choose scoring format (Rotisserie recommended)
5. Review roster positions (defaults are standard 5x5 roto)
6. Click **"Create League"**

### Start a Draft

1. From your league page, click **"Start New Draft"**
2. Optionally name your draft
3. Click **"Create Draft"**
4. Click **"Start Draft"** to begin

### Use AI Recommendations

1. Click **"🤖 Get AI Recommendations"**
2. Wait 2-5 seconds for Gemini to analyze the draft
3. Review the top 5 recommendations with explanations
4. Click **"Draft"** next to your preferred player
5. Repeat for each pick!

## Troubleshooting

### "Connection refused" when accessing localhost:8090

**Solution**: Wait 30 more seconds for containers to fully start, then try again.

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs app
docker-compose logs nginx
```

### "SQLSTATE[HY000] [2002] Connection refused"

**Solution**: Database isn't ready yet. Wait and retry.

```bash
# Check database logs
docker-compose logs db

# Restart containers
docker-compose restart
```

### "Gemini API request failed"

**Solutions**:
1. Verify your API key is correct in `.env`
2. Check you have API quota remaining
3. Ensure you're connected to the internet
4. Try the fallback: The app will automatically use ranking-based recommendations

### "Player not found" during ranking import

**Solution**: This is normal for name mismatches. The import will show which players couldn't be matched. You can:
1. Manually update player names in the CSV
2. Add players to the database first
3. Ignore - the app will work with matched players

### Docker containers won't start

**Solution**: Check port conflicts

```bash
# Check if ports 8090 or 3307 are in use
lsof -i :8090
lsof -i :3307

# Change ports in docker-compose.yml if needed
```

## Useful Commands

```bash
# View application logs
docker-compose logs -f app

# Access the app container shell
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]

# Stop containers
docker-compose down

# Stop and remove all data
docker-compose down -v

# Rebuild containers
docker-compose up -d --build
```

## Next Steps

1. **Customize Roster Positions**: Edit league settings to match your league format
2. **Import More Data**: Add additional ranking sources or projection data
3. **Run Multiple Drafts**: Test different draft strategies
4. **Explore the Code**: Check out `IMPLEMENTATION.md` for architecture details

## Getting Help

- Check `README.md` for detailed documentation
- Review `IMPLEMENTATION.md` for technical details
- Check Docker logs for error messages
- Verify all environment variables are set correctly

## Sample Data

The included CSV files contain:
- **players.csv**: 277 MLB players with positions
- **my_rank.csv**: Custom rankings (134 players)
- **third_rank.csv**: Third-party rankings (400 players)

You can replace these with your own data or add additional sources.

Enjoy your draft! 🎉⚾

