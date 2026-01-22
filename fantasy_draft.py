import csv
import json
import os
import re
import random
import sys
from typing import Dict, List, Optional, Tuple, Any, Union

# Global configuration variables
MY_TEAM_ID = 1  # Change this to select which team is yours (0-7)

# Define which teams use ranking lists
# Teams not in either list will use the default best available player strategy
TEAMS_USING_MY_RANK = [MY_TEAM_ID]  # Teams using my_rank.csv
TEAMS_USING_THIRD_RANK = [5,6,7]     # Teams using third_rank.csv

# Make sure a team isn't in both lists
for team_id in TEAMS_USING_MY_RANK[:]:
    if team_id in TEAMS_USING_THIRD_RANK:
        TEAMS_USING_THIRD_RANK.remove(team_id)
        print(f"Warning: Team {team_id+1} was in both ranking lists. Using my_rank for this team.")


class FantasyBaseballDraft:
    def __init__(self, my_team_id: int = MY_TEAM_ID):
        self.my_team_id = my_team_id
        self.position_slots = [
            'C', '1B', '2B', 'SS', '3B', 'OF1', 'OF2', 'OF3',
            'UTIL1', 'UTIL2', 'SP1', 'SP2', 'SP3', 'SP4', 'SP5',
            'SP6', 'SP7', 'P1', 'P2', 'P3', 'P4', 'P5'
        ]
        
        # Check for file existence before initializing
        self._check_required_files()
        
        self.state = self.initialize_draft()
        
    def _check_required_files(self):
        """Check that all required files exist before starting."""
        missing_files = []

        for filename in ["players.csv", "my_rank.csv", "third_rank.csv"]:
            if not os.path.exists(filename):
                missing_files.append(filename)

        if missing_files:
            print("ERROR: The following required files are missing:")
            for filename in missing_files:
                print(f"  - {filename}")
            print("\nPlease ensure all required CSV files are in the current directory.")
            sys.exit(1)

        # Check for optional ADP file
        if not os.path.exists("FantasyPros_adp.csv"):
            print("Note: FantasyPros_adp.csv not found. ADP data will not be available.")

    def initialize_draft(self) -> Dict[str, Any]:
        """Initialize the draft state with empty teams and loaded player data."""
        players = self.load_players()
        my_rank = self.load_my_rank()
        third_rank = self.load_third_rank()
        adp_data = self.load_adp()

        # Create empty teams array with position slots
        teams = []
        for i in range(8):
            team = {}
            for pos in self.position_slots:
                team[pos] = None
            teams.append(team)

        # Create empty draft grid
        draft_grid = []
        for round_num in range(22):
            round_picks = []
            for team in range(8):
                round_picks.append(None)
            draft_grid.append(round_picks)

        return {
            'all_players': players,
            'my_rank': my_rank,
            'third_rank': third_rank,
            'adp': adp_data,
            'draft_grid': draft_grid,
            'teams': teams,
            'round': 0,
            'pick': 0,
            'completed': False
        }

    def load_players(self) -> Dict[str, Dict[str, Any]]:
        """Load player data from CSV file."""
        data = {}
        try:
            with open("players.csv", "r", newline='', encoding='utf-8-sig') as f:
                reader = csv.reader(f)
                for row in reader:
                    try:
                        # Extract player name without team
                        full_name = row[1]
                        name = re.sub(r'\s+[A-Z]{2,3}$', '', full_name)
                        
                        # Handle positions
                        positions = []
                        if len(row) > 2 and row[2]:
                            positions_str = row[2].replace('"', '')
                            positions = positions_str.split(',')
                        
                        data[name] = {
                            'name': name,
                            'full_name': full_name,
                            'positions': positions
                        }
                    except (IndexError, ValueError) as e:
                        print(f"Warning: Error processing player row {row}: {e}")
                        continue
        except FileNotFoundError:
            print("ERROR: players.csv not found. This file is required.")
            sys.exit(1)
        
        if not data:
            print("ERROR: No valid player data found in players.csv.")
            sys.exit(1)
            
        return data

    def load_my_rank(self) -> List[str]:
        """Load player rankings from CSV file."""
        ranks_by_name = {}
        try:
            with open("my_rank.csv", "r", newline='', encoding='utf-8-sig') as f:
                reader = csv.reader(f)
                for row in reader:
                    if len(row) >= 2:
                        try:
                            full_name = row[1]
                            name = re.sub(r'\s+[A-Z]{2,3}$', '', full_name)
                            # Clean up any potential BOM or whitespace from rank value
                            rank_value = row[0].strip().lstrip('\ufeff')
                            ranks_by_name[name] = int(rank_value)
                        except (ValueError, IndexError) as e:
                            print(f"Warning: Error processing row {row} in my_rank.csv: {e}")
                            continue
        except FileNotFoundError:
            print("ERROR: my_rank.csv not found. This file is required.")
            sys.exit(1)
        
        if not ranks_by_name:
            print("ERROR: No valid ranking data found in my_rank.csv.")
            sys.exit(1)
            
        # Sort by rank value
        rank_pairs = []
        for player_name, rank in ranks_by_name.items():
            rank_pairs.append((player_name, rank))
        
        rank_pairs.sort(key=lambda x: x[1])
        
        # Extract just the player names in rank order
        ordered_rank = [pair[0] for pair in rank_pairs]
        
        return ordered_rank

    def load_third_rank(self) -> List[str]:
        """Load third-party player rankings from CSV file."""
        ranks_by_name = {}
        try:
            with open("third_rank.csv", "r", newline='', encoding='utf-8-sig') as f:
                reader = csv.reader(f)
                for row in reader:
                    if len(row) >= 2:
                        try:
                            full_name = row[1]
                            name = re.sub(r'\s+[A-Z]{2,3}$', '', full_name)
                            # Clean up any potential BOM or whitespace from rank value
                            rank_value = row[0].strip().lstrip('\ufeff')
                            ranks_by_name[name] = int(rank_value)
                        except (ValueError, IndexError) as e:
                            print(f"Warning: Error processing row {row} in third_rank.csv: {e}")
                            continue
        except FileNotFoundError:
            print("ERROR: third_rank.csv not found. This file is required.")
            sys.exit(1)
        
        if not ranks_by_name:
            print("ERROR: No valid ranking data found in third_rank.csv.")
            sys.exit(1)
            
        # Sort by rank value
        rank_pairs = []
        for player_name, rank in ranks_by_name.items():
            rank_pairs.append((player_name, rank))
        
        rank_pairs.sort(key=lambda x: x[1])
        
        # Extract just the player names in rank order
        ordered_rank = [pair[0] for pair in rank_pairs]
        
        return ordered_rank

    def load_adp(self) -> Dict[str, Dict[str, Any]]:
        """Load ADP (Average Draft Position) data from FantasyPros_adp.csv.

        Expected CSV format:
        "RK","PLAYER NAME",TEAM,"POS","BEST","WORST","AVG.","STD.DEV","ECR VS. ADP"

        Returns a dictionary keyed by player name with ADP data.
        """
        adp_data = {}
        try:
            with open("FantasyPros_adp.csv", "r", newline='', encoding='utf-8-sig') as f:
                reader = csv.reader(f)
                # Read header
                header = next(reader, None)
                if not header:
                    print("Warning: FantasyPros_adp.csv is empty.")
                    return adp_data

                # Normalize header names
                header = [h.strip().strip('"').upper().replace('.', '') for h in header]

                # Find column indices
                try:
                    rk_idx = header.index('RK')
                    name_idx = header.index('PLAYER NAME')
                    team_idx = header.index('TEAM')
                    pos_idx = header.index('POS')
                    best_idx = header.index('BEST')
                    worst_idx = header.index('WORST')
                    avg_idx = header.index('AVG')
                    stddev_idx = header.index('STDDEV')
                    ecr_idx = header.index('ECR VS ADP')
                except ValueError as e:
                    print(f"Warning: Missing expected column in ADP file: {e}")
                    return adp_data

                for row in reader:
                    if len(row) <= max(name_idx, avg_idx):
                        continue

                    try:
                        player_name = row[name_idx].strip().strip('"')
                        # Clean player name for matching (remove Jr., etc.)
                        clean_name = re.sub(r'\s+(Jr\.?|Sr\.?|II|III|IV)$', '', player_name)
                        clean_name = re.sub(r'\s+[A-Z]{2,3}$', '', clean_name)  # Remove team abbrev if present

                        adp_value = float(row[avg_idx].strip().strip('"'))
                        rank = int(row[rk_idx].strip().strip('"'))
                        team = row[team_idx].strip().strip('"') if team_idx < len(row) else ''
                        pos = row[pos_idx].strip().strip('"') if pos_idx < len(row) else ''
                        best = int(row[best_idx].strip().strip('"')) if best_idx < len(row) else None
                        worst = int(row[worst_idx].strip().strip('"')) if worst_idx < len(row) else None
                        stddev = float(row[stddev_idx].strip().strip('"')) if stddev_idx < len(row) else None
                        ecr_vs_adp = row[ecr_idx].strip().strip('"') if ecr_idx < len(row) else ''

                        adp_data[clean_name] = {
                            'adp': adp_value,
                            'rank': rank,
                            'team': team,
                            'pos': pos,
                            'best': best,
                            'worst': worst,
                            'stddev': stddev,
                            'ecr_vs_adp': ecr_vs_adp,
                            'original_name': player_name
                        }

                        # Also store with original name as key for exact matches
                        if player_name != clean_name:
                            adp_data[player_name] = adp_data[clean_name]

                    except (ValueError, IndexError) as e:
                        continue  # Skip malformed rows silently

        except FileNotFoundError:
            # ADP file is optional, return empty dict
            return adp_data
        except Exception as e:
            print(f"Warning: Error loading ADP data: {e}")
            return adp_data

        print(f"Loaded ADP data for {len(adp_data)} players.")
        return adp_data

    def get_player_adp(self, player_name: str) -> Optional[Dict[str, Any]]:
        """Get ADP data for a player by name with fuzzy matching."""
        if not self.state.get('adp'):
            return None

        # Try exact match first
        if player_name in self.state['adp']:
            return self.state['adp'][player_name]

        # Try cleaned name
        clean_name = re.sub(r'\s+(Jr\.?|Sr\.?|II|III|IV)$', '', player_name)
        clean_name = re.sub(r'\s+\([^)]*\)', '', clean_name)  # Remove parentheses content
        clean_name = clean_name.strip()

        if clean_name in self.state['adp']:
            return self.state['adp'][clean_name]

        # Try fuzzy matching
        for adp_name, adp_info in self.state['adp'].items():
            if self.smarter_name_match(player_name, adp_name):
                return adp_info
            if self.smarter_name_match(player_name, adp_info.get('original_name', '')):
                return adp_info

        return None

    def format_adp(self, player_name: str) -> str:
        """Format ADP info for display."""
        adp_info = self.get_player_adp(player_name)
        if adp_info:
            return f"ADP: {adp_info['adp']:.1f}"
        return "ADP: N/A"

    def create_sample_players_file(self):
        """This function is no longer used but is kept for reference."""
        pass

    def create_sample_rank_file(self):
        """This function is no longer used but is kept for reference."""
        pass

    def create_sample_third_rank_file(self):
        """This function is no longer used but is kept for reference."""
        pass

    def create_sample_rank_file(self):
        """Create a sample my_rank.csv file if one doesn't exist."""
        try:
            player_names = []
            with open("players.csv", "r", newline='', encoding='utf-8') as f:
                reader = csv.reader(f)
                for row in reader:
                    if len(row) >= 2:
                        player_names.append(row[1])
            
            # Randomize the order for sample rankings
            random.shuffle(player_names)
            
            with open("my_rank.csv", "w", newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                for i, name in enumerate(player_names):
                    writer.writerow([i+1, name])
            
            print("Created sample my_rank.csv file.")
        except Exception as e:
            print(f"Error creating sample rank file: {e}")

    def generate_snake_order(self) -> List[List[int]]:
        """Generate a snake draft order."""
        forward = list(range(8))
        reverse = list(range(7, -1, -1))
        return [forward, reverse]

    def smarter_name_match(self, player_name: str, rank_name: str) -> bool:
        """Advanced player name matching to handle variations."""
        # Handle None values
        if player_name is None or rank_name is None:
            return False
            
        # Clean up player name
        clean_player_name = player_name.strip()
        clean_rank_name = rank_name.strip()
        
        # Direct match
        if clean_player_name == clean_rank_name:
            return True
        
        # Remove parentheses and their contents for comparison
        player_no_parens = re.sub(r'\s*\([^)]*\)', '', clean_player_name)
        rank_no_parens = re.sub(r'\s*\([^)]*\)', '', clean_rank_name)
        
        if player_no_parens.strip() == rank_no_parens.strip():
            return True
        
        # Special case for Jr/II/III suffixes
        player_base = re.sub(r'\s+(Jr|II|III)(\s+|$)', '', clean_player_name)
        rank_base = re.sub(r'\s+(Jr|II|III)(\s+|$)', '', clean_rank_name)
        
        if player_base.strip() == rank_base.strip():
            return True
        
        return False

    def is_eligible(self, team_id: int, player: Dict[str, Any]) -> bool:
        """Check if a player is eligible for assignment to a team."""
        team = self.state['teams'][team_id]
        
        # Check if player is UTIL/DH only
        util_only = len(player['positions']) == 1 and (
            player['positions'][0] == 'UTIL' or player['positions'][0] == 'DH'
        )
        
        # For UTIL-only players, check only UTIL slots
        if util_only:
            for i in range(1, 3):
                position_key = f'UTIL{i}'
                if team[position_key] is None:
                    return True
            return False
        
        # For other players, check their eligible positions
        for pos in player['positions']:
            if pos == 'UTIL' or pos == 'DH':
                continue  # Skip UTIL/DH in position checks, only use primary positions
            
            if pos == 'OF':
                # Check all OF positions
                for i in range(1, 4):
                    position_key = f'OF{i}'
                    if team[position_key] is None:
                        return True
            elif pos == 'SP':
                # Check all SP positions
                for i in range(1, 8):
                    position_key = f'SP{i}'
                    if team[position_key] is None:
                        return True
                # Check all P positions
                for i in range(1, 6):
                    position_key = f'P{i}'
                    if team[position_key] is None:
                        return True
            elif pos == 'P' or pos == 'RP':
                # Check all P positions
                for i in range(1, 6):
                    position_key = f'P{i}'
                    if team[position_key] is None:
                        return True
            elif pos in team and team[pos] is None:
                return True
        
        # If no primary positions are available but player is a batter, check UTIL slots
        if 'P' not in player['positions'] and 'SP' not in player['positions']:
            for i in range(1, 3):
                position_key = f'UTIL{i}'
                if team[position_key] is None:
                    return True
        
        return False

    def assign_player(self, team_id: int, player: Dict[str, Any], round_idx: int, pick: int):
        """Assign a player to a team and update the draft grid."""
        # Add player to draft grid
        self.state['draft_grid'][round_idx][team_id] = {
            'name': player['name'],
            'team_id': team_id
        }
        
        # Flag to track if player was assigned
        assigned = False
        
        # STEP 1: Handle UTIL/DH only players first - they can only go to UTIL
        util_only = len(player['positions']) == 1 and (
            player['positions'][0] == 'UTIL' or player['positions'][0] == 'DH'
        )
        
        if util_only:
            for i in range(1, 3):
                position_key = f'UTIL{i}'
                if self.state['teams'][team_id][position_key] is None:
                    self.state['teams'][team_id][position_key] = player
                    assigned = True
                    break
            return  # Return early for UTIL-only players
        
        # STEP 2: Try standard positions first (C, 1B, 2B, SS, 3B)
        for pos in player['positions']:
            # Skip OF, UTIL, DH, SP, P positions for now
            if pos in ['OF', 'UTIL', 'DH', 'SP', 'P']:
                continue
            
            # Standard position check - direct assignment to matching position
            if pos in self.state['teams'][team_id] and self.state['teams'][team_id][pos] is None:
                self.state['teams'][team_id][pos] = player
                assigned = True
                break
        
        # STEP 3: Try OF positions if not yet assigned and player can play OF
        if not assigned and 'OF' in player['positions']:
            for i in range(1, 4):
                position_key = f'OF{i}'
                if self.state['teams'][team_id][position_key] is None:
                    self.state['teams'][team_id][position_key] = player
                    assigned = True
                    break
        
        # STEP 4: Try pitching positions if not yet assigned
        if not assigned:
            is_pitcher = False
            
            # Check if player is a pitcher
            for pos in player['positions']:
                if pos == 'SP' or pos == 'P':
                    is_pitcher = True
                    break
            
            if is_pitcher:
                # First try SP slots if player is an SP
                if 'SP' in player['positions']:
                    for i in range(1, 8):
                        position_key = f'SP{i}'
                        if self.state['teams'][team_id][position_key] is None:
                            self.state['teams'][team_id][position_key] = player
                            assigned = True
                            break
                
                # If still not assigned, try P slots
                if not assigned:
                    for i in range(1, 6):
                        position_key = f'P{i}'
                        if self.state['teams'][team_id][position_key] is None:
                            self.state['teams'][team_id][position_key] = player
                            assigned = True
                            break
        
        # STEP 5: Last resort - assign to UTIL if player is a batter
        if not assigned and 'P' not in player['positions'] and 'SP' not in player['positions']:
            for i in range(1, 3):
                position_key = f'UTIL{i}'
                if self.state['teams'][team_id][position_key] is None:
                    self.state['teams'][team_id][position_key] = player
                    assigned = True
                    break

    def draft_player(self):
        """Process one draft pick."""
        # Check if draft is already completed
        if self.state['completed']:
            return
        
        snake_order = self.generate_snake_order()
        current_round = self.state['round']
        current_pick = self.state['pick']
        
        # Use snake order to determine team picking
        is_even_round = (current_round % 2 == 0)
        team_order = snake_order[0] if is_even_round else snake_order[1]
        team_id = team_order[current_pick]
        
        # Determine which strategy to use based on team
        if team_id in TEAMS_USING_MY_RANK:
            # Use my ranking list
            self.draft_using_rank_list(team_id, self.state['my_rank'], "my ranking list")
        elif team_id in TEAMS_USING_THIRD_RANK:
            # Use third-party ranking list
            self.draft_using_rank_list(team_id, self.state['third_rank'], "third-party ranking list")
        else:
            # For other teams, take best available player
            self.draft_best_available(team_id)
        
        # Move to next pick
        self.state['pick'] += 1
        if self.state['pick'] >= 8:
            self.state['pick'] = 0
            self.state['round'] += 1
            
            # Check if draft is complete
            if self.state['round'] >= 22:
                self.state['completed'] = True
    
    def draft_using_rank_list(self, team_id: int, rank_list: List[str], list_name: str):
        """Draft a player using a specific ranking list."""
        if self.state['completed']:
            return
                
        selected_player = None
        print(f"\nDEBUG: Team {team_id+1} attempting to draft from {list_name}...")  # Debug line
        print(f"DEBUG: Available players in list: {rank_list[:5]} (showing top 5)")  # Debug line
        
        # Try to find eligible player from the rank list
        for rank_player_name in rank_list:
            found_player = None
            for player_name, player in list(self.state['all_players'].items()):
                if self.smarter_name_match(player_name, rank_player_name):
                    found_player = player
                    break
            
            if found_player and self.is_eligible(team_id, found_player):
                selected_player = found_player
                for player_name, player in list(self.state['all_players'].items()):
                    if self.smarter_name_match(player_name, rank_player_name):
                        del self.state['all_players'][player_name]
                        break
                break
        
        if not selected_player:
            print(f"Warning: No players from {list_name} are eligible for Team {team_id + 1}. Taking best available player.")
            team_name = f"Team {team_id + 1}"
            if team_id == self.my_team_id:
                team_name += " (Your Team)"
            print(f"Warning: No players from {list_name} are eligible for {team_name}. Taking best available player.")
            # Select best available player
            for player_name, player in list(self.state['all_players'].items()):
                if self.is_eligible(team_id, player):
                    selected_player = player
                    del self.state['all_players'][player_name]
                    break
        
        # Assign the selected player
        if selected_player:
            self.assign_player(team_id, selected_player, self.state['round'], self.state['pick'])
        else:
            print(f"Warning: No eligible players available for {team_name} at all! This is unusual.")
    
    def draft_best_available(self, team_id: int):
        """Draft the best available player for a team."""
        if self.state['completed']:
            return
            
        selected_player = None
        # For teams using best available strategy, take best available player
        for player_name, player in list(self.state['all_players'].items()):
            if self.is_eligible(team_id, player):
                # Found eligible player, assign them
                selected_player = player
                self.assign_player(team_id, player, self.state['round'], self.state['pick'])
                del self.state['all_players'][player_name]
                break
                
        if not selected_player:
            team_name = f"Team {team_id + 1}"
            if team_id == self.my_team_id:
                team_name += " (Your Team)"
            print(f"Warning: No eligible players available for {team_name} at all! This is unusual.")
        

    def display_draft_grid(self):
        """Display the current draft grid in the console."""
        print("\n" + "=" * 80)
        print("FANTASY BASEBALL DRAFT BOARD")
        print("=" * 80)
        
        # Header row
        header = "Round"
        for i in range(8):
            team_name = f"Team {i+1}"
            
            # Add indicators for which ranking list each team uses
            if i == self.my_team_id:
                team_name += " (You)"
            if i in TEAMS_USING_MY_RANK:
                team_name += "*"  # Mark teams using my rank
            elif i in TEAMS_USING_THIRD_RANK:
                team_name += "^"  # Mark teams using third rank
                
            header += f" | {team_name:12}"
        print(header)
        print("-" * 120)
        
        # Draft grid
        for round_idx, round_picks in enumerate(self.state['draft_grid']):
            row = f"Round {round_idx+1:2d}"
            
            for team_idx, pick in enumerate(round_picks):
                if pick:
                    player_name = pick['name']
                    # Truncate long names
                    if len(player_name) > 12:
                        player_name = player_name[:10] + ".."
                    
                    # Highlight your team picks
                    if team_idx == self.my_team_id:
                        player_name = f"*{player_name}*"
                    
                    row += f" | {player_name:12}"
                else:
                    row += f" | {'-':12}"
            
            print(row)
        
        # Draft status
        print("\n" + "-" * 80)
        print("Legend: * = Using your ranking list  ^ = Using third-party ranking list")
        
        if self.state['completed']:
            print("Draft Status: COMPLETED")
        else:
            current_round = self.state['round'] + 1
            
            # Determine which team is drafting next
            is_even_round = (self.state['round'] % 2 == 0)
            team_order = self.generate_snake_order()[0 if is_even_round else 1]
            next_team = team_order[self.state['pick']] + 1  # +1 for display (1-based)
            
            status = f"Current: Round {current_round}, Team {next_team}"
            if next_team - 1 == self.my_team_id:
                status += " (You)"
                
            # Add indicator for which ranking list the drafting team uses
            if next_team - 1 in TEAMS_USING_MY_RANK:
                status += " - Using your ranking list"
            elif next_team - 1 in TEAMS_USING_THIRD_RANK:
                status += " - Using third-party ranking list"
            else:
                status += " - Using best available player strategy"
                
            print(f"Draft Status: {status}")
        
        print("=" * 80 + "\n")

    def display_team_roster(self, team_id: int):
        """Display a specific team's roster with ADP information."""
        team = self.state['teams'][team_id]

        title = f"TEAM {team_id + 1} ROSTER"

        # Add indicators for which team this is
        indicators = []
        if team_id == self.my_team_id:
            indicators.append("YOUR TEAM")
        if team_id in TEAMS_USING_MY_RANK:
            indicators.append("Using your rank list")
        elif team_id in TEAMS_USING_THIRD_RANK:
            indicators.append("Using third-party rank list")
        else:
            indicators.append("Using best available strategy")

        if indicators:
            title += f" ({', '.join(indicators)})"

        print("\n" + "=" * 70)
        print(title)
        print("=" * 70)

        print(f"{'Position':<10} | {'Player':<25} | {'ADP':>8} | {'Team':>5}")
        print("-" * 70)

        for position, player in sorted(team.items()):
            if player:
                player_name = player['name']
                adp_info = self.get_player_adp(player_name)
                if adp_info:
                    adp_str = f"{adp_info['adp']:.1f}"
                    team_str = adp_info.get('team', '')
                else:
                    adp_str = "N/A"
                    team_str = ""
                print(f"{position:<10} | {player_name:<25} | {adp_str:>8} | {team_str:>5}")
            else:
                print(f"{position:<10} | {'-':<25} | {'-':>8} | {'-':>5}")

        print("=" * 70 + "\n")

    def display_all_team_rosters(self):
        """Display all team rosters."""
        for team_id in range(8):
            self.display_team_roster(team_id)

    def save_draft_state(self, filename: str = "draft_state.json"):
        """Save the current draft state to a file."""
        try:
            # Create a copy of the state that's safe to serialize
            state_copy = {
                'draft_grid': self.state['draft_grid'],
                'round': self.state['round'],
                'pick': self.state['pick'],
                'completed': self.state['completed'],
                # We need to handle the special serialization of team data
                'teams': []
            }
            
            # Handle teams data - convert player objects to names for JSON serialization
            for team in self.state['teams']:
                team_copy = {}
                for pos, player in team.items():
                    if player:
                        team_copy[pos] = {
                            'name': player['name'],
                            'full_name': player.get('full_name', player['name']),
                            'positions': player['positions']
                        }
                    else:
                        team_copy[pos] = None
                state_copy['teams'].append(team_copy)
            
            # Don't save all_players (we'll reload from source files)
            # Save rank lists as ordered arrays of names
            state_copy['my_rank'] = self.state['my_rank']
            state_copy['third_rank'] = self.state['third_rank']
            
            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(state_copy, f, indent=2, default=lambda o: str(o) if isinstance(o, object) else o)
            print(f"Draft state saved to {filename}")
        except Exception as e:
            print(f"Error saving draft state: {e}")

    def load_draft_state(self, filename: str = "draft_state.json"):
        """Load draft state from a file."""
        try:
            with open(filename, 'r', encoding='utf-8') as f:
                loaded_state = json.load(f)
            
            # Reload players from source files
            players = self.load_players()
            
            # Reconstruct state with fresh player data but loaded draft progress
            self.state = {
                'all_players': players,
                'my_rank': loaded_state.get('my_rank', self.load_my_rank()),
                'third_rank': loaded_state.get('third_rank', self.load_third_rank()),
                'draft_grid': loaded_state['draft_grid'],
                'teams': loaded_state['teams'],
                'round': loaded_state['round'],
                'pick': loaded_state['pick'],
                'completed': loaded_state['completed']
            }
            
            print(f"Draft state loaded from {filename}")
        except FileNotFoundError:
            print(f"No saved draft state found at {filename}")
        except Exception as e:
            print(f"Error loading draft state: {e}")


    def display_top_available_by_adp(self, count: int = 20):
        """Display top available players sorted by ADP."""
        print("\n" + "=" * 85)
        print("TOP AVAILABLE PLAYERS BY ADP")
        print("=" * 85)

        # Get available players with ADP
        players_with_adp = []
        for player_name, player in self.state['all_players'].items():
            adp_info = self.get_player_adp(player_name)
            if adp_info:
                players_with_adp.append({
                    'name': player_name,
                    'player': player,
                    'adp': adp_info['adp'],
                    'rank': adp_info['rank'],
                    'team': adp_info.get('team', ''),
                    'pos': adp_info.get('pos', ''),
                    'best': adp_info.get('best'),
                    'worst': adp_info.get('worst'),
                })

        # Sort by ADP
        players_with_adp.sort(key=lambda x: x['adp'])

        # Display header
        print(f"{'#':<4} | {'Player':<25} | {'ADP':>7} | {'Rank':>5} | {'Team':>5} | {'Pos':<8} | {'Best-Worst':<10}")
        print("-" * 85)

        # Display top N players
        for i, p in enumerate(players_with_adp[:count], 1):
            best_worst = f"{p['best']}-{p['worst']}" if p['best'] and p['worst'] else "N/A"
            print(f"{i:<4} | {p['name']:<25} | {p['adp']:>7.1f} | {p['rank']:>5} | {p['team']:>5} | {p['pos']:<8} | {best_worst:<10}")

        # Show count of players without ADP
        players_without_adp = len(self.state['all_players']) - len(players_with_adp)
        print("-" * 85)
        print(f"Total available: {len(self.state['all_players'])} | With ADP: {len(players_with_adp)} | Without ADP: {players_without_adp}")
        print("=" * 85 + "\n")

    def display_adp_recommendations(self):
        """Display draft recommendations based on ADP value."""
        print("\n" + "=" * 85)
        print("ADP VALUE ANALYSIS - Best Available Picks")
        print("=" * 85)

        current_overall_pick = (self.state['round'] * 8) + self.state['pick'] + 1

        # Get available players with ADP
        players_with_adp = []
        for player_name, player in self.state['all_players'].items():
            adp_info = self.get_player_adp(player_name)
            if adp_info:
                # Calculate value (positive = good value, negative = reach)
                value = adp_info['adp'] - current_overall_pick
                players_with_adp.append({
                    'name': player_name,
                    'adp': adp_info['adp'],
                    'value': value,
                    'team': adp_info.get('team', ''),
                    'pos': adp_info.get('pos', ''),
                })

        # Sort by value (best values first - players with ADP higher than pick)
        players_with_adp.sort(key=lambda x: -x['value'])

        print(f"Current Pick: #{current_overall_pick}")
        print(f"\n{'BEST VALUE PICKS (ADP > Current Pick)':^85}")
        print(f"{'#':<4} | {'Player':<25} | {'ADP':>7} | {'Value':>7} | {'Team':>5} | {'Pos':<8}")
        print("-" * 85)

        # Show top value picks
        value_picks = [p for p in players_with_adp if p['value'] > 0][:10]
        for i, p in enumerate(value_picks, 1):
            print(f"{i:<4} | {p['name']:<25} | {p['adp']:>7.1f} | {'+' if p['value'] > 0 else ''}{p['value']:>6.1f} | {p['team']:>5} | {p['pos']:<8}")

        if not value_picks:
            print("  No players available with ADP above current pick.")

        print(f"\n{'REACH PICKS (ADP < Current Pick)':^85}")
        print(f"{'#':<4} | {'Player':<25} | {'ADP':>7} | {'Value':>7} | {'Team':>5} | {'Pos':<8}")
        print("-" * 85)

        # Show potential reach picks (drafting earlier than ADP suggests)
        reach_picks = [p for p in reversed(players_with_adp) if p['value'] < 0][:5]
        for i, p in enumerate(reach_picks, 1):
            print(f"{i:<4} | {p['name']:<25} | {p['adp']:>7.1f} | {p['value']:>7.1f} | {p['team']:>5} | {p['pos']:<8}")

        if not reach_picks:
            print("  All available players are good value at this pick!")

        print("=" * 85 + "\n")


def auto_complete_draft(draft):
    """Automatically complete the entire draft."""
    print("Auto-completing draft...")

    # Continue drafting until complete
    while not draft.state['completed']:
        draft.draft_player()
        # Optional: Add a small delay to make it visible if desired
        # import time
        # time.sleep(0.1)

    print("Draft completed!")

def run_draft_cli():
    """Run the fantasy baseball draft simulator as a command-line interface."""
    draft = FantasyBaseballDraft(my_team_id=MY_TEAM_ID)  # Use the global MY_TEAM_ID

    while True:
        os.system('cls' if os.name == 'nt' else 'clear')
        draft.display_draft_grid()

        print("Fantasy Baseball Draft Simulator")
        print("1. Draft next player")
        print("2. View team roster")
        print("3. View all rosters")
        print("4. Reset draft")
        print("5. Save draft state")
        print("6. Load draft state")
        print("7. Auto-complete draft")
        print("8. Configure team rankings")
        print("9. View top available by ADP")
        print("A. View ADP value recommendations")
        print("0. Exit")

        choice = input("\nEnter your choice: ").strip().upper()

        if choice == '1':
            draft.draft_player()
        elif choice == '2':
            team_id = int(input("Enter team ID (1-8): ")) - 1
            if 0 <= team_id < 8:
                draft.display_team_roster(team_id)
                input("Press Enter to continue...")
            else:
                print("Invalid team ID!")
                input("Press Enter to continue...")
        elif choice == '3':
            draft.display_all_team_rosters()
            input("Press Enter to continue...")
        elif choice == '4':
            confirm = input("Are you sure you want to reset the draft? (y/n): ")
            if confirm.lower() == 'y':
                draft = FantasyBaseballDraft(my_team_id=MY_TEAM_ID)
        elif choice == '5':
            filename = input("Enter filename (default: draft_state.json): ") or "draft_state.json"
            draft.save_draft_state(filename)
            input("Press Enter to continue...")
        elif choice == '6':
            filename = input("Enter filename (default: draft_state.json): ") or "draft_state.json"
            draft.load_draft_state(filename)
            input("Press Enter to continue...")
        elif choice == '7':
            # Auto-complete the draft
            confirm = input("Are you sure you want to auto-complete the entire draft? (y/n): ")
            if confirm.lower() == 'y':
                auto_complete_draft(draft)
                input("Press Enter to continue...")
        elif choice == '8':
            # Configure which teams use which ranking lists
            configure_team_rankings()
            # Need to reload the draft to apply changes
            draft = FantasyBaseballDraft(my_team_id=MY_TEAM_ID)
            input("Team ranking configuration updated. Press Enter to continue...")
        elif choice == '9':
            # View top available players by ADP
            try:
                count = input("How many players to show? (default: 20): ").strip()
                count = int(count) if count else 20
            except ValueError:
                count = 20
            draft.display_top_available_by_adp(count)
            input("Press Enter to continue...")
        elif choice == 'A':
            # View ADP value recommendations
            draft.display_adp_recommendations()
            input("Press Enter to continue...")
        elif choice == '0':
            print("Exiting Fantasy Baseball Draft Simulator. Goodbye!")
            sys.exit()
        else:
            print("Invalid choice!")
            input("Press Enter to continue...")


def configure_team_rankings():
    """Configure which teams use which ranking lists."""
    global TEAMS_USING_MY_RANK, TEAMS_USING_THIRD_RANK
    
    print("\n==== TEAM RANKING CONFIGURATION ====")
    print("Current settings:")
    print(f"Teams using your ranking list (my_rank.csv): {', '.join(f'Team {t+1}' for t in TEAMS_USING_MY_RANK)}")
    print(f"Teams using third-party ranking list (third_rank.csv): {', '.join(f'Team {t+1}' for t in TEAMS_USING_THIRD_RANK)}")
    print("All other teams use best available player strategy")
    print("\nNote: Teams can only use one ranking list. If you assign a team to both lists,")
    print("it will only use the first list (your ranking list).")
    
    # Configure teams using my_rank
    my_rank_input = input("\nEnter team numbers to use your ranking list (comma-separated, e.g. 1,3,5): ")
    if my_rank_input.strip():
        try:
            # Convert 1-based team numbers to 0-based team IDs
            TEAMS_USING_MY_RANK = []
            for t in my_rank_input.split(','):
                t = t.strip()
                if t:
                    team_id = int(t) - 1
                    if 0 <= team_id < 8:
                        TEAMS_USING_MY_RANK.append(team_id)
        except ValueError:
            print("Invalid input. Using previous configuration.")
    
    # Configure teams using third_rank
    third_rank_input = input("\nEnter team numbers to use third-party ranking list (comma-separated, e.g. 2,4,6): ")
    if third_rank_input.strip():
        try:
            # Convert 1-based team numbers to 0-based team IDs
            TEAMS_USING_THIRD_RANK = []
            for t in third_rank_input.split(','):
                t = t.strip()
                if t:
                    team_id = int(t) - 1
                    if 0 <= team_id < 8 and team_id not in TEAMS_USING_MY_RANK:
                        TEAMS_USING_THIRD_RANK.append(team_id)
        except ValueError:
            print("Invalid input. Using previous configuration.")
    else:
        # Remove any teams that are now in my_rank list
        TEAMS_USING_THIRD_RANK = [t for t in TEAMS_USING_THIRD_RANK if t not in TEAMS_USING_MY_RANK]
    
    print("\nUpdated configuration:")
    print(f"Teams using your ranking list: {', '.join(f'Team {t+1}' for t in TEAMS_USING_MY_RANK)}")
    print(f"Teams using third-party ranking list: {', '.join(f'Team {t+1}' for t in TEAMS_USING_THIRD_RANK)}")
    print("All other teams use best available player strategy")


if __name__ == "__main__":
    run_draft_cli()