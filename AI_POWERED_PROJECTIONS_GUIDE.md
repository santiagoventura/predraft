# 🤖 AI-Powered FantasyPros Integration Guide

## 🎯 Overview

Your MLB Draft Helper now features **AI-powered automatic data fetching** from FantasyPros! No more manual CSV uploads - the system uses Gemini AI to scrape, parse, and import player projections automatically.

## ✨ How It Works

### The Magic Behind the Scenes:

1. **Fetch** - System retrieves the latest projections page from FantasyPros.com
2. **AI Parse** - Gemini AI reads the HTML and extracts structured player data
3. **Transform** - AI converts the data to match your database format
4. **Import** - System automatically creates/updates players and projections
5. **Calculate** - You can then calculate league-specific fantasy points

### Why This Is Better Than CSV:

✅ **No manual work** - Just click a button  
✅ **Always up-to-date** - Fetch latest data anytime  
✅ **Automatic player creation** - New players are added automatically  
✅ **Position updates** - Player positions are kept current  
✅ **Error handling** - AI intelligently handles format variations  
✅ **Comprehensive** - Gets 150-200 batters and 100-150 pitchers  

## 📊 Using the System

### Step 1: Access Player Data Management

1. Go to your Leagues page
2. Click **"📊 Player Data"** button (top right)
3. You'll see the Player Data Management page

### Step 2: Fetch Projections from FantasyPros

#### For Batters:
1. In the **"🤖 Fetch from FantasyPros"** section (blue/purple gradient box)
2. On the left side (blue border), select the season (default: 2025)
3. Click **"🤖 Fetch Batter Projections"**
4. Wait 30-60 seconds while AI processes the data
5. You'll see a success message with import stats

#### For Pitchers:
1. In the same section, on the right side (purple border)
2. Select the season (default: 2025)
3. Click **"🤖 Fetch Pitcher Projections"**
4. Wait 30-60 seconds while AI processes the data
5. You'll see a success message with import stats

### Step 3: Calculate League-Specific Scores

After fetching projections:

1. Scroll to **"🧮 Calculate Fantasy Points"** section
2. **Option A - Single League:**
   - Select your league from dropdown
   - Choose season (2025)
   - Choose source (fantasypros)
   - Click **"🧮 Calculate Scores for Selected League"**

3. **Option B - All Leagues:**
   - Choose season (2025)
   - Choose source (fantasypros)
   - Click **"🧮 Calculate Scores for ALL Leagues"**

### Step 4: Use in Draft

Now when you start a draft and click **"🤖 Get AI Recommendations"**, the system will use:
- ✅ Real FantasyPros projections
- ✅ Your league's specific scoring system
- ✅ Calculated fantasy points for each player
- ✅ Player positions from FantasyPros

## 🎯 What Data Gets Imported

### Batters (150-200 players):
- **Player Info**: Name, Team, Positions (SS, 2B, OF, etc.)
- **Counting Stats**: PA, AB, H, 2B, 3B, HR, R, RBI, SB, CS, BB
- **Rate Stats**: AVG, OBP, SLG, OPS

### Pitchers (100-150 players):
- **Player Info**: Name, Team, Positions (SP, RP)
- **Counting Stats**: IP, W, L, SV, HLD, K, BB, H, ER, CG
- **Rate Stats**: ERA, WHIP, K/9, BB/9

### Automatic Player Management:
- **Existing players**: Updated with latest positions and projections
- **New players**: Automatically created in database
- **Positions**: Automatically detected and assigned (C, SS, 2B, 3B, 1B, OF, SP, RP, etc.)

## 🔄 Updating Data

### During Draft Season:

**Recommended Schedule:**
- **Weekly**: Fetch new projections to account for injuries, trades, performance
- **Before each draft**: Always fetch fresh data
- **After major trades**: Update to get new team assignments

**How to Update:**
1. Go to Player Data Management
2. Click **"🤖 Fetch Batter Projections"** again
3. Click **"🤖 Fetch Pitcher Projections"** again
4. Recalculate scores for your leagues
5. The system will **update** existing projections (not duplicate)

## 🎨 How AI Recommendations Use This Data

### Before (Without FantasyPros Data):
```
Bobby Witt Jr (SS)
💡 Why This Pick: Best available player by ranking.
```

### After (With FantasyPros Data):
```
Bobby Witt Jr (SS)
📊 Projected Points: 485 pts (based on YOUR league scoring)

🏥 Health: Healthy - no concerns

✅ Pros:
• Elite speed and power combination (30 HR, 45 SB projected)
• High batting average potential (.300 AVG projected)
• Plays shortstop, a scarce position
• Projected for 680 PA, 110 R, 95 RBI

⚠️ Cons:
• Can be streaky at times
• Still developing plate discipline (45 BB projected)

📊 Position Analysis: Top SS available. Other options include 
Lindor (420 pts), Henderson (445 pts), and Trea Turner (410 pts), 
but Witt offers the highest upside in YOUR scoring system.

💡 Why This Pick: Witt's 485 projected points lead all shortstops 
in your league. His combination of power (30 HR), speed (45 SB), 
and batting average (.300) is rare. The next-best SS drops to 445 
points - a 40-point gap. Secure this elite SS now.
```

## 🔧 Technical Details

### AI Parsing Process:

1. **HTTP Request**: Fetches FantasyPros projections page
2. **HTML Extraction**: Isolates the data table from page
3. **Gemini AI Call**: Sends HTML to Gemini with structured prompt
4. **JSON Response**: AI returns structured JSON array of players
5. **Validation**: System validates and cleans the data
6. **Database Import**: Creates/updates players and projections

### Error Handling:

- **Player not found**: AI creates new player automatically
- **Missing stats**: Handles null values gracefully
- **Format variations**: AI adapts to different HTML structures
- **Network errors**: Clear error messages with retry option

### Data Sources:

- **Primary**: FantasyPros.com (consensus projections)
- **Backup**: Manual CSV upload still available
- **Future**: Can add Steamer, ZiPS, THE BAT with same AI approach

## 🚀 Advanced Features

### Multiple Projection Sources:

You can fetch from different sources and compare:
1. Fetch FantasyPros projections (source: "fantasypros")
2. Upload Steamer CSV (source: "steamer")
3. Upload ZiPS CSV (source: "zips")
4. When calculating scores, choose which source to use

### League-Specific Scoring:

Each league can have different scoring:
- **League A**: HR=4, RBI=1, SB=2, AVG=0
- **League B**: HR=5, RBI=1, SB=3, AVG=100

Same player, different points:
- **Shohei Ohtani in League A**: 420 points
- **Shohei Ohtani in League B**: 568 points

AI recommendations will use the correct league's scoring!

### Position Eligibility:

FantasyPros provides multi-position eligibility:
- **Mookie Betts**: 2B, SS, OF
- **Shohei Ohtani**: DH, OF
- **Vladimir Guerrero Jr**: 1B, 3B

The system tracks all eligible positions and uses them in draft recommendations.

## ⚠️ Important Notes

### Rate Limits:
- FantasyPros may rate-limit requests
- If you get errors, wait a few minutes and try again
- Don't fetch more than once per hour

### Data Accuracy:
- FantasyPros updates projections regularly
- Always fetch fresh data before important drafts
- Cross-reference with other sources for critical decisions

### Player Names:
- AI matches players by name (fuzzy matching)
- Most players match automatically
- New players are created if not found

## 🎉 Benefits Summary

✅ **Saves Time** - No manual CSV creation  
✅ **Always Current** - Latest projections on demand  
✅ **Comprehensive** - 250+ players automatically  
✅ **Intelligent** - AI handles format changes  
✅ **Accurate** - League-specific point calculations  
✅ **Strategic** - Better AI recommendations  
✅ **Flexible** - Update anytime during season  

## 🆘 Troubleshooting

### "Fetch failed" error:
- Check internet connection
- FantasyPros may be down - try later
- Rate limit - wait 10 minutes and retry

### "No projections imported":
- Check the error message details
- AI may have failed to parse - report the issue
- Use manual CSV upload as backup

### "Player not found" warnings:
- Normal for new/rookie players
- They'll be created automatically
- Check spelling if important player missing

### Scores not showing in AI recommendations:
- Make sure you calculated scores for the league
- Verify season and source match (both should be 2025, fantasypros)
- Check that projections were imported successfully

---

**You're now ready to use AI-powered FantasyPros integration!** 🎉

Just click the buttons and let AI do the work!

