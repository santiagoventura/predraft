# Draft Player Selection - Documentation

## Overview

The draft interface now supports **two ways to make picks**:

1. **🤖 AI Recommendations** - Get intelligent player suggestions powered by Google Gemini
2. **🔍 Manual Selection** - Search and select any available player using Select2 dropdown

This gives you flexibility to either trust the AI or make your own strategic decisions.

## Features

### 1. AI Recommendations (Existing)

Click the **"🤖 Get AI Recommendations"** button to receive:
- Top 5-10 player recommendations
- AI-generated explanations for each pick
- Contextual analysis based on:
  - Team needs (positions to fill)
  - Available players
  - Draft strategy
  - League scoring configuration

**How to use:**
1. Click "🤖 Get AI Recommendations"
2. Review the suggested players and explanations
3. Click "Draft" on any recommended player
4. Confirm the pick

### 2. Manual Player Selection (NEW)

Use the **Select2 searchable dropdown** to find and draft any available player.

**Features:**
- ✅ **Real-time search** - Type player name, team, or position
- ✅ **Rich display** - Shows player name, MLB team, positions, and type (Batter/Pitcher)
- ✅ **Smart filtering** - Instantly filters hundreds of players
- ✅ **Auto-position selection** - Automatically suggests eligible positions
- ✅ **Visual indicators** - Color-coded badges for batters vs pitchers

**How to use:**
1. Click the "Search Player" dropdown
2. Type to search (e.g., "Ohtani", "LAD", "SS")
3. Select a player from the results
4. Choose which position to fill (auto-selected to first eligible position)
5. Click "Draft Selected Player"
6. Confirm the pick

## Select2 Search Examples

### Search by Player Name
```
Type: "Shohei"
Results: Shohei Ohtani (LAA) - P,DH
```

### Search by Team
```
Type: "LAD"
Results: All Los Angeles Dodgers players
```

### Search by Position
```
Type: "SS"
Results: All shortstops
```

### Partial Match
```
Type: "Judge"
Results: Aaron Judge (NYY) - OF
```

## Position Selection

When you select a player, the system automatically determines eligible positions:

### For Batters:
- All positions the player is eligible for (e.g., SS, 2B, 3B)
- Plus "UTIL" (utility) position

**Example:**
- Player: Francisco Lindor (NYM) - SS,2B
- Available positions: SS, 2B, UTIL

### For Pitchers:
- "P" (pitcher)
- Any specific pitcher positions if applicable (SP, RP)

**Example:**
- Player: Gerrit Cole (NYY) - P
- Available positions: P

## User Interface

### Draft Board Layout

```
┌─────────────────────────────────────────────────────────┐
│ On the Clock                                            │
│ Team Name (Pick #X)                                     │
│                                                         │
│ [🤖 Get AI Recommendations]                             │
│                                                         │
│ ─────────────────────────────────────────────────────  │
│ Or Select Player Manually:                              │
│                                                         │
│ Search Player: [Dropdown with search]                   │
│ Position to Fill: [SS ▼]                                │
│ [Draft Selected Player]                                 │
└─────────────────────────────────────────────────────────┘
```

### Select2 Dropdown Display

When you click the dropdown, you'll see:

```
┌─────────────────────────────────────────────────────────┐
│ Type player name, team, or position...                  │
├─────────────────────────────────────────────────────────┤
│ Shohei Ohtani                                           │
│ LAA | P,DH                                    [P]       │
├─────────────────────────────────────────────────────────┤
│ Aaron Judge                                             │
│ NYY | OF                                    [Batter]    │
├─────────────────────────────────────────────────────────┤
│ Mookie Betts                                            │
│ LAD | OF,2B                                 [Batter]    │
└─────────────────────────────────────────────────────────┘
```

## Technical Implementation

### Select2 Configuration

```javascript
$('#player-select').select2({
    placeholder: 'Type player name, team, or position...',
    allowClear: true,
    width: '100%',
    data: availablePlayersData,
    templateResult: customPlayerTemplate,
    templateSelection: customSelectionTemplate
});
```

### Available Players Data

Players are filtered to show only **undrafted players**:

```php
$availablePlayers = $draftSimulator->getAvailablePlayers($draft);
```

This excludes:
- Players already drafted in this draft
- No other restrictions (all positions, all teams)

### Data Structure

Each player in the dropdown has:
```json
{
    "id": 123,
    "name": "Shohei Ohtani",
    "team": "LAA",
    "positions": "P,DH",
    "is_pitcher": true
}
```

## Workflow Comparison

### AI Recommendations Workflow
1. Click "Get AI Recommendations"
2. Wait for AI analysis (~2-5 seconds)
3. Review 5-10 suggestions with explanations
4. Click "Draft" on preferred player
5. Confirm

**Best for:** Trusting AI strategy, learning, getting ideas

### Manual Selection Workflow
1. Click dropdown
2. Type search term
3. Select player (instant)
4. Confirm position
5. Click "Draft Selected Player"
6. Confirm

**Best for:** Specific player targets, quick picks, overriding AI

## Use Cases

### When to Use AI Recommendations:
- ✅ You're unsure who to pick
- ✅ You want strategic advice
- ✅ You're learning about players
- ✅ You want to see multiple options
- ✅ You trust the AI's analysis

### When to Use Manual Selection:
- ✅ You have a specific player in mind
- ✅ You want to make a quick pick
- ✅ You disagree with AI recommendations
- ✅ You're following a pre-planned strategy
- ✅ You know the player pool well

## Tips

1. **Combine Both Methods**: Get AI recommendations first, then use manual search if you want someone else
2. **Search Efficiently**: Use team abbreviations (LAD, NYY) or positions (SS, OF) for faster results
3. **Check Positions**: Make sure to select the right position to fill based on your team needs
4. **Trust the Auto-Select**: The system auto-selects the first eligible position, which is usually correct

## Browser Compatibility

Select2 works on:
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (with touch support)

## Performance

- **Player data**: Loaded once on page load
- **Search**: Client-side filtering (instant)
- **No API calls**: Until you make the pick
- **Handles**: 500+ players without lag

## Future Enhancements

Potential improvements:
- Filter by position before searching
- Sort by rankings or projections
- Show player stats in dropdown
- Favorite/bookmark players
- Recent searches
- Keyboard shortcuts

---

**Ready to draft!** 🎉 You now have full control over your picks with both AI assistance and manual selection.

