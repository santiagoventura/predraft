# Player Projections & AI Recommendations Guide

## 📊 Overview

This system allows you to import player projections and calculate league-specific fantasy points. The AI recommendations will use this data instead of generic rankings, providing personalized suggestions based on YOUR league's scoring system.

## 🎯 How It Works

### 1. Import Player Projections
Upload CSV files with statistical projections for batters and pitchers.

### 2. Calculate League-Specific Scores
The system calculates fantasy points for each player based on each league's unique scoring categories.

### 3. AI Uses Your Data
When you request AI recommendations during a draft, the system uses:
- **League-specific projected points** (not generic rankings)
- **Your league's scoring categories**
- **Pitcher-aware analysis** based on your roster requirements

## 📥 Importing Projections

### Step 1: Access Player Data Management
1. Go to the Leagues page
2. Click **"📊 Player Data"** button in the top right
3. You'll see the Player Data Management page

### Step 2: Prepare Your CSV File

#### For Batters:
```csv
player_name,team,pa,ab,h,2b,3b,hr,r,rbi,sb,cs,bb,avg,obp,slg,ops
Shohei Ohtani,LAD,650,580,165,30,2,45,100,110,15,3,70,0.284,0.372,0.587,0.959
Aaron Judge,NYY,620,540,150,25,1,50,105,120,8,2,80,0.278,0.385,0.611,0.996
```

**Required columns:**
- `player_name` - Must match player names in database
- `team` - MLB team abbreviation
- Counting stats: `pa`, `ab`, `h`, `2b`, `3b`, `hr`, `r`, `rbi`, `sb`, `cs`, `bb`
- Rate stats: `avg`, `obp`, `slg`, `ops`

#### For Pitchers:
```csv
player_name,team,ip,w,l,sv,hld,k,bb,era,whip,k_per_9,bb_per_9,h,er,cg
Gerrit Cole,NYY,200,15,8,0,0,250,45,3.20,1.05,11.25,2.03,165,71,2
Spencer Strider,ATL,180,14,6,0,0,260,55,2.85,0.98,13.00,2.75,130,57,1
```

**Required columns:**
- `player_name` - Must match player names in database
- `team` - MLB team abbreviation
- Counting stats: `ip`, `w`, `l`, `sv`, `hld`, `k`, `bb`, `h`, `er`, `cg`
- Rate stats: `era`, `whip`, `k_per_9`, `bb_per_9`

### Step 3: Upload the CSV
1. Click **"Choose File"** and select your CSV
2. Select the **Season** (e.g., 2025)
3. Select the **Source** (FantasyPros, Steamer, ZiPS, THE BAT, or Custom)
4. Click **"📥 Import Projections"**

### Step 4: Verify Import
- Check the success message showing how many projections were imported
- The statistics panel will update to show total projections

## 🧮 Calculating Fantasy Points

After importing projections, you need to calculate fantasy points for each league.

### Option 1: Calculate for One League
1. Select a league from the dropdown
2. Choose the season and source (must match your import)
3. Click **"🧮 Calculate Scores for Selected League"**

### Option 2: Calculate for All Leagues
1. Choose the season and source
2. Click **"🧮 Calculate Scores for ALL Leagues"**
3. This will calculate scores for every league in your system

### What Happens During Calculation:
1. System loads projections for each player
2. For each league, it applies that league's scoring categories
3. Calculates total fantasy points per player
4. Stores the results in the database

**Example:**
- Player: Shohei Ohtani
- Projection: 45 HR, 110 RBI, 100 R, 15 SB, .284 AVG
- League A scoring: HR=4, RBI=1, R=1, SB=2, AVG=0
- League A points: (45×4) + (110×1) + (100×1) + (15×2) = **420 points**
- League B scoring: HR=5, RBI=1, R=1, SB=3, AVG=100
- League B points: (45×5) + (110×1) + (100×1) + (15×3) + (.284×100) = **568 points**

## 🤖 How AI Uses This Data

### Before (Without Projections):
```
AI Recommendation:
Bobby Witt Jr (SS)
💡 Why This Pick: Best available player by ranking.
```

### After (With Projections):
```
AI Recommendation:
Bobby Witt Jr (SS)
📊 Projected Points: 485 pts

🏥 Health: Healthy - no concerns

✅ Pros:
• Elite speed and power combination (30 HR, 45 SB projected)
• High batting average potential (.300 projected)
• Plays shortstop, a scarce position

⚠️ Cons:
• Can be streaky at times
• Still developing plate discipline

📊 Position Analysis: Top SS available. Other options include 
Lindor (420 pts), Henderson (445 pts), and Trea Turner (410 pts), 
but Witt offers the highest upside.

💡 Why This Pick: Witt is a foundational player who can contribute 
across multiple categories. His 485 projected points lead all 
shortstops in YOUR league's scoring system. Given the scarcity of 
reliable shortstops, securing him now provides a significant advantage.
```

## 📁 Sample Files

Two sample CSV files are included in the project root:
- `sample_projections_batters.csv` - 20 top batters with projections
- `sample_projections_pitchers.csv` - 20 top pitchers with projections

You can use these as templates or for testing.

## 🔄 Updating Projections

### During the Season:
1. Export updated projections from your source (FantasyPros, Steamer, etc.)
2. Import the new CSV file
3. The system will **update** existing projections (not duplicate)
4. Recalculate scores for your leagues
5. AI recommendations will use the new data

### Multiple Sources:
You can import from multiple sources:
- Import FantasyPros projections as "fantasypros"
- Import Steamer projections as "steamer"
- When calculating scores, choose which source to use

## 🎯 Best Practices

### 1. Import Before Draft
- Import projections at least a day before your draft
- Calculate scores for all leagues
- Verify the data looks correct

### 2. Use Reliable Sources
- FantasyPros (consensus projections)
- Steamer (statistical model)
- ZiPS (statistical model)
- THE BAT (advanced model)

### 3. Update Regularly
- Re-import weekly during the season
- Recalculate scores after major trades/injuries
- Keep your data fresh

### 4. League-Specific Scoring
- Each league can have different scoring
- Always calculate scores for each league separately
- AI will use the correct league's scoring during drafts

## ⚠️ Troubleshooting

### "Player not found" errors:
- Player names in CSV must match database names exactly
- Check for extra spaces or special characters
- Use the exact name format from the players table

### No projected points in AI recommendations:
- Make sure you've imported projections
- Verify you've calculated scores for the league
- Check that season and source match

### Scores seem wrong:
- Verify your league's scoring categories are set correctly
- Check the projection data for accuracy
- Recalculate scores after fixing scoring settings

## 🗑️ Danger Zone

### Clear All Projections
- Deletes ALL player projections from the database
- Use this to start fresh with new data
- **Cannot be undone!**

### Clear All Calculated Scores
- Deletes ALL calculated fantasy points for all leagues
- Use this before recalculating with new projections
- **Cannot be undone!**

## 📊 Where to Get Projection Data

### Free Sources:
1. **FantasyPros** - fantasypros.com (consensus projections)
2. **Steamer** - fangraphs.com (free projections)
3. **ZiPS** - fangraphs.com (free projections)

### Paid Sources:
1. **THE BAT** - fangraphs.com (subscription required)
2. **ATC** - available on various sites
3. **Rotowire** - rotowire.com (subscription)

### How to Export:
1. Go to the projection page on the source website
2. Look for "Export to CSV" or "Download" button
3. Save the CSV file
4. Upload to this system

## 🎉 Benefits

✅ **Personalized Recommendations** - AI uses YOUR league's scoring
✅ **Accurate Projections** - Based on professional projection systems
✅ **League-Specific** - Different points for different leagues
✅ **Pitcher-Aware** - Properly values pitchers in your format
✅ **Up-to-Date** - Update anytime with fresh data
✅ **Strategic Insights** - AI explains WHY each pick makes sense

