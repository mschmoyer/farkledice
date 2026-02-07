# Game Design Proposal: Stakes-Based Economy & Contextual Accolades

**Date:** 2026-02-04
**Status:** Draft

---

## Topic

We have a feature that lets you select an emoji at the end of the game. It feels hollow—no stakes, no meaning, no reason to care. Additionally, leveling and XP are outdated. Could we replace both with a money-based stakes system where players wager on games, track lifetime earnings (including going deeply negative with no restrictions), and make every roll matter more?

---

## Committee Perspectives

### The Player Advocate
- **Emoji reactions lack narrative**: Generic emojis don't capture what just happened in a specific game
- **Contextual badges excite**: Award badges tied to the match story ("Clutch God," "Farkle Magnet") that become inside jokes and shared memories
- **Money creates stakes**: A poker-style bankroll transforms push-your-luck into push-your-money, making every roll matter more emotionally
- **Lifetime earnings tell stories**: Tracking career bankroll (even when deeply negative) creates narrative arc and trash-talk fuel
- **Key worry**: Buy-in friction and social pressure (need clear play-money framing)

### The Systems Designer
- **Current systems are mechanically inert**: Emoji and XP/leveling have no consequence, no feedback loop, no teeth
- **Economy sharpens the core loop**: Money-based stakes double the dopamine of hot streaks and make farkles sting
- **Natural scaling**: Different stake levels create organic skill tiers without visible MMR
- **Critical risks**: Perceived gambling (legal review required), runaway inflation (needs careful tuning), status anxiety (negative balances must feel okay), onboarding friction (new players need risk-free ramp)
- **Simplest version**: Entry fees → winner takes pot → lifetime net winnings leaderboard
- **Fallback**: Trophy case system with categories that unlock cosmetic rewards at milestones

### The Social Designer
- **Current emoji is decorative, not connective**: One-way gesture that doesn't create conversation or memorable moments in async play
- **Money creates rivalry narratives**: Lifetime win/loss tracking between friends becomes kitchen-table score-keeping
- **Social Risk 1 - Unbalanced friendships**: Perpetually losing players (kids vs. parents) may feel demoralized; need easy stakes reset or $0 casual games
- **Social Risk 2 - Stranger toxicity**: Random opponent stakes could attract griefers or rage-quitters; keep high-stakes for friends only
- **Social Risk 3 - Skill ceiling**: If skilled players crush weaker ones consistently, money becomes humiliation marker
- **Alternative idea**: Voice clip reactions or turn-specific reactions for presence in async play

---

## Consensus & Tension

**Where the committee agrees:**
- The current emoji system is meaningless and should be replaced
- XP/leveling is outdated and doesn't create meaningful progression
- A money-based stakes system would sharpen the core push-your-luck loop
- Lifetime bankroll tracking creates better narrative and rivalry fuel than levels
- Any system must respect async play (no synchronous requirements)
- New players must never be locked out (infinite borrowing is crucial)

**Where the committee disagrees:**
- **Legal/regulatory risk**: Systems Designer flags this as a showstopper requiring legal review; Player Advocate less concerned about it
- **Social safety**: Social Designer worried about skill imbalance and stranger toxicity; Player Advocate focused more on friend-group fun
- **Fallback option**: Systems Designer proposes trophy case as safer alternative; other perspectives want to push for full economy
- **Complexity**: Player Advocate wants simple buy-in flow; Systems Designer wants phase-rolled deployment

---

## Recommendation

### Feature Name
**Bankroll & Accolades System**

### Description
Replace XP/leveling with a lifetime bankroll system where players wager on games. Replace generic emoji reactions with contextual accolades awarded post-game that reference what actually happened. Track lifetime earnings (including negative balances) and display top accolades on player profiles.

### How It Works

**Phase 1: Stakes Foundation**
1. All players start with 10,000 coins
2. Creating a game requires setting stake level (default: 100 coins per player)
3. Winner takes the pot (entry fees × players), minus 10% "house fee" that vanishes (deflationary pressure)
4. Players can go infinitely negative—no restrictions, no lockouts
5. Profile displays lifetime net winnings (green if positive, red if negative) as badge of honor/shame
6. Leaderboard tracks monthly and lifetime net winnings

**Phase 2: Contextual Accolades**
1. After each game, winner grants opponent an accolade from contextual categories:
   - **Clutch God**: Won by narrow margin in final round
   - **Comeback Kid**: Won after being down 3,000+ points
   - **Hot Streak**: Scored 2,500+ in a single turn
   - **Farkle Magnet**: Farkled 3+ times in the game
   - **Stone Cold**: Passed on risky roll that would have paid off
   - **Lucky Devil**: Won despite lower average score per turn
2. Profile displays top 3 accolade types and counts
3. Accolades become inside jokes and conversation starters

**Phase 3: Stake Tiers & Matchmaking**
1. Games organized by stake levels: Penny ($10), Standard ($100), High-Roller ($1,000)
2. Quick match uses your current bankroll to suggest appropriate stakes
3. Friend invites let you set custom stakes (including $0 for casual play)

**Phase 4: Cosmetic Sinks**
1. Spend coins on profile cosmetics, dice skins, table backgrounds
2. No gameplay advantages—purely personalization
3. Creates money sinks to balance economy

**Phase 5: Tournament Prize Pools**
1. Tournament entry fees fund prize pools
2. Top finishers split the pot (e.g., 50% / 30% / 20% for top 3)
3. Adds structure and event-driven stakes

### Why It Fits

**Fun First**: Money stakes amplify the core push-your-luck dopamine loop. Every roll after the first is "do I risk my coins?" instead of "do I risk my turn score?"

**Replayability**: Lifetime bankroll creates long-term narrative. "I need to win my money back" is a powerful return driver. Different stake tiers provide natural progression without grinding.

**Social Connection**: Lifetime net winnings between specific friends becomes kitchen-table score-keeping across games. Contextual accolades create shared memories and inside jokes that strengthen bonds.

**Async-First**: No synchronous requirements. Stakes set at game creation, resolved at game end. All tracking happens in background.

**Tone**: Play-money framing keeps it playful, not predatory. Going deeply negative becomes a badge of honor, not shame. "I'm down $50K lifetime but I've played 500 games with my dad" tells a warm story.

---

## Risks & Trade-offs

**Legal/Regulatory Risk (HIGH)**: Even with monopoly money, app stores and regulators may treat wagering mechanics as gambling. **Required action**: Legal review before building anything.

**Economic Balance**: If money is earned faster than lost, stakes become meaningless. Requires careful tuning of house fees, starting balances, and inflation pressure. May need seasonal resets.

**Social Imbalance**: Perpetually losing players may feel demoralized. **Mitigation**: Easy stakes reset between specific players, $0 casual game option, celebrate participation ("You've played 100 games!") not just winning.

**Stranger Toxicity**: Random opponent stakes could attract griefers or rage-quitters. **Mitigation**: Disable stakes (or very low defaults) for random matchmaking. High-stakes reserved for friend invites.

**Onboarding Friction**: New players may not understand stakes system immediately. **Mitigation**: Generous starting bankroll (10,000 coins), tutorial game, clear "you can never run out" messaging.

**Development Complexity**: Full economy system is 5+ phases of work. **Trade-off**: Start with Phase 1 (stakes + lifetime tracking) only, validate before building rest.

---

## Priority

**Suggested priority:** Must-have (pending legal clearance)

**Rationale:** This transforms two weak systems (emoji reactions, XP/leveling) into a single compelling feature that strengthens all four design pillars and creates the stakes that push-your-luck games deserve.

---

## Open Questions

**Legal**: Can we implement wagering mechanics with play money without triggering gambling regulations? What app store restrictions apply?

**Economic tuning**: What's the right starting balance, house fee percentage, and stake tier progression to prevent inflation while keeping stakes meaningful?

**Social safety**: Do we need a "soft reset" option where players between specific pairs can zero out their rivalry balance without affecting lifetime stats?

**Onboarding**: How do we communicate "you can always borrow" without making bankruptcy feel bad?

**Phasing**: Do we ship Phase 1 (stakes) alone and validate before building accolades, or bundle them together as the emoji replacement?

**Voice reactions**: Is there appetite for voice clip reactions as an alternative/supplement to text accolades?
