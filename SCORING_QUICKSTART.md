# Scoring System - Quick Start Guide

## ✅ What's New

Your MLB Fantasy Draft Helper now supports **custom scoring configurations** for each league! This means:

- ✅ Each league can have different point values
- ✅ Yahoo, ESPN, and CBS presets available
- ✅ Fully customizable scoring categories
- ✅ Automatic calculation of projected fantasy points
- ✅ Separate scoring for batters and pitchers

## 🚀 Quick Setup (3 Steps)

### Step 1: Configure Scoring for Your League

**Option A: Use Web Interface**
1. Go to http://localhost:8090/leagues
2. Click on your league
3. Click the **"⚙️ Scoring"** button
4. Click **"Apply Preset"** and select Yahoo/ESPN/CBS
5. Done! ✅

**Option B: Use Command Line**
```bash
# Apply Yahoo scoring (default)
docker-compose exec app php artisan league:setup-scoring 1 --preset=yahoo

# Apply ESPN scoring
docker-compose exec app php artisan league:setup-scoring 1 --preset=espn

# Apply CBS scoring
docker-compose exec app php artisan league:setup-scoring 1 --preset=cbs
```

### Step 2: Customize (Optional)

If you want to modify the point values:

1. Go to **Leagues → [Your League] → ⚙️ Scoring**
2. Click **"Edit Scoring"**
3. Modify point values for any category
4. Add or remove categories
5. Click **"Save Scoring Configuration"**

### Step 3: Calculate Player Scores

Once scoring is configured, calculate fantasy points:

**Option A: Web Interface**
1. Go to **Leagues → [Your League] → ⚙️ Scoring**
2. Scroll to "Calculate Player Scores"
3. Select season and projection source
4. Click **"Calculate Scores"**

**Option B: Command Line**
```bash
# Calculate for a specific league
docker-compose exec app php artisan scores:calculate 1

# Calculate for all leagues
docker-compose exec app php artisan scores:calculate --all

# Specify season and source
docker-compose exec app php artisan scores:calculate 1 --season=2025 --source=fantasypros
```

## 📊 Default Scoring (Yahoo)

### Batters
- **Singles (1B)**: +2.6 pts
- **Doubles (2B)**: +5.2 pts
- **Triples (3B)**: +7.8 pts
- **Home Runs (HR)**: +10.4 pts
- **Runs (R)**: +1.9 pts
- **RBI**: +1.9 pts
- **Stolen Bases (SB)**: +4.2 pts
- **Walks (BB)**: +2.6 pts
- **Strikeouts (K)**: -1.0 pts

### Pitchers
- **Innings Pitched (IP)**: +7.4 pts
- **Wins (W)**: +4.3 pts
- **Saves (SV)**: +5.0 pts
- **Strikeouts (K)**: +2.0 pts
- **Hits Allowed (H)**: -2.6 pts
- **Walks Allowed (BB)**: -2.6 pts
- **Earned Runs (ER)**: -3.2 pts

## 🎯 Example: Custom Scoring

Let's say you want a **power-hitting focused league**:

1. Go to **Edit Scoring**
2. Change these values:
   - HR: **+15** (instead of +10.4)
   - 2B: **+3** (instead of +5.2)
   - SB: **+2** (instead of +4.2)
   - K: **-0.5** (instead of -1.0)
3. Save and calculate scores

Now players like Aaron Judge and Shohei Ohtani will rank higher!

## 🔧 Useful Commands

```bash
# View all leagues
docker-compose exec app php artisan tinker --execute="App\Models\League::all(['id', 'name'])"

# Check scoring for a league
docker-compose exec app php artisan tinker --execute="\$l = App\Models\League::find(1); echo 'Batter cats: ' . \$l->batterScoringCategories->count() . ', Pitcher cats: ' . \$l->pitcherScoringCategories->count();"

# View top 10 players by score
docker-compose exec app php artisan tinker --execute="\$scores = App\Models\PlayerScore::where('league_id', 1)->orderBy('total_points', 'desc')->limit(10)->with('player')->get(); foreach(\$scores as \$s) { echo \$s->player->name . ': ' . \$s->total_points . ' pts' . PHP_EOL; }"
```

## 📝 Notes

- **Each league is independent**: League 1 can use Yahoo scoring while League 2 uses custom scoring
- **Recalculate after changes**: If you modify scoring categories, recalculate player scores
- **Negative points**: Use negative values for penalties (e.g., -3.2 for Earned Runs)
- **Decimal precision**: You can use decimals (e.g., 2.6, 10.4) for fine-tuned scoring

## 🆘 Troubleshooting

**Q: I don't see any scoring categories**
- Run: `docker-compose exec app php artisan league:setup-scoring {league_id} --preset=yahoo`

**Q: Player scores are not showing**
- Make sure you've calculated scores: `docker-compose exec app php artisan scores:calculate {league_id}`

**Q: I want to reset to Yahoo defaults**
- Run: `docker-compose exec app php artisan league:setup-scoring {league_id} --preset=yahoo`
- This will delete existing categories and create Yahoo defaults

**Q: Can I have different scoring for different leagues?**
- Yes! Each league has its own independent scoring configuration

## 📚 Full Documentation

See `SCORING_SYSTEM.md` for complete documentation including:
- Database schema
- API/Service layer details
- Advanced customization
- Future enhancements

---

**Ready to use!** 🎉 Your scoring system is fully configured and ready to calculate fantasy points based on your league's specific rules.

