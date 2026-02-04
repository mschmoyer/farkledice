# Plan: Create `/game-design` Skill

## What We're Building

1. **A Game Design Bible** at `planning/game-design/design-bible.md` — the core source of truth for what Farkle Ten is, who it's for, and what makes it fun
2. **A Claude Code skill** at `.claude/commands/game-design.md` — invoked with `/game-design [topic]`, spawns a committee of game design subagents who debate a topic and produce actionable feature proposals grounded in the design bible

## Files to Create

### 1. `planning/game-design/design-bible.md`

The foundational design document containing:
- **Core Identity**: Farkle Ten replaces kitchen-table dice sessions for families and friend groups. Async multiplayer at its core.
- **Target Audience**: Families and friend groups who know each other. Ages vary. Casual but competitive.
- **Design Pillars**: Fun, replayability, social connection, async-first
- **Current Feature Landscape**: Leaderboards, achievements, friend system, game modes, bot opponents, emoji reactions
- **What Makes It Sticky**: Leaderboard rivalry between people who know each other, low friction async play, the push-your-luck dopamine loop

### 2. `.claude/commands/game-design.md`

Skill that:
- Takes a topic/question as `$ARGUMENTS` (e.g., "streak bonuses" or "what keeps casual players coming back")
- Spawns 3 subagents as the "Game Design Committee":
  - **The Player Advocate** — thinks from the player's POV, focuses on fun, friction, and emotion
  - **The Systems Designer** — thinks about game loops, balance, progression, and replayability mechanics
  - **The Social Designer** — thinks about multiplayer dynamics, rivalry, family/friend engagement, and social hooks
- Each agent gets the design bible as context, debates the topic, and returns a position
- The orchestrator synthesizes the 3 perspectives into a structured feature proposal
- Writes the output to `planning/game-design/proposals/[topic-slug].md`

### Proposal Output Format

Each proposal file will contain:
- Topic and date
- Summary of each committee member's perspective
- Consensus recommendation
- Proposed feature spec (name, description, how it works, why it fits the design)
- Risks and trade-offs
- Priority suggestion (nice-to-have / should-have / must-have)

## Verification

- Run `/game-design "test topic"` and confirm it spawns agents, reads the bible, and produces a proposal file in the correct location
