# Farkle Ten — Game Design Bible

This is the foundational design document for Farkle Ten. It defines what the game is, who it's for, and what makes it worth playing. All feature proposals and design decisions should be grounded in this document.

---

## Core Identity

Farkle Ten replaces the kitchen-table dice session for families and friend groups who can't always be in the same room. It's the game you play with your dad across state lines, your college friends across time zones, and your coworkers between meetings.

Async multiplayer is the backbone. You roll when you have a minute, your opponent rolls when they have a minute. The game lives in the gaps of your day.

---

## Target Audience

- **Primary:** Families and friend groups who already know each other. Parents and kids, siblings, old friends, coworkers.
- **Age range:** Wide. The game is simple enough for teens, strategic enough for adults.
- **Player profile:** Casual but competitive. They don't want to read a manual, but they do want to win. They check the leaderboard. They talk trash.
- **Platform:** Mobile-first in practice (people play from their phones), but desktop is fully supported.

---

## Design Pillars

### 1. Fun First
The push-your-luck mechanic is inherently fun. Every roll is a micro-decision with real stakes. The dopamine hit of a hot streak and the groan of a farkle — that's the core loop. Never bury it under complexity.

### 2. Replayability
Games are short enough to play several in a row. Varied outcomes from the same simple rules. Tournaments and leaderboards give long-term structure. Players should always have a reason to start another game.

### 3. Social Connection
This is a game you play *with people*, not just *against opponents*. The friend system, leaderboards, and emoji reactions exist to make it feel like you're at a table together. Rivalry between people who know each other is the engine.

### 4. Async-First
Respect the player's time. They should be able to take their turn in under a minute, put the phone down, and come back later. No waiting rooms, no synchronous requirements. The game fits into life, not the other way around.

---

## Current Feature Landscape

| Category | Features |
|----------|----------|
| **Game Modes** | Standard (to 10,000 points), 10-round tournaments |
| **Multiplayer** | Friends, random opponents, solo practice |
| **Social** | Friend system, emoji reactions, player profiles |
| **Progression** | Leaderboards, achievements, XP/levels, titles |
| **AI** | Bot opponents with Claude-powered personalities |
| **Platform** | Responsive web (mobile, tablet, desktop) |

---

## What Makes It Sticky

1. **Leaderboard rivalry between people who know each other.** Seeing your friend ahead of you on the leaderboard is the strongest retention driver. It's personal.

2. **Low-friction async play.** No app install, no account required to start. Take your turn in 30 seconds. The barrier to play is nearly zero.

3. **The push-your-luck dopamine loop.** Every roll after the first is a genuine decision. Keep going or bank? The tension is built into the dice math. It never gets old.

4. **Short games, long rivalries.** Individual games end quickly, but the win/loss record and leaderboard position persist. The meta-game is the relationship.

5. **Familiar game, new context.** Most players already know Farkle (or a variant). There's almost no learning curve. The value proposition is playing it with *their* people, *any time*.

---

## Design Principles for New Features

When evaluating any new feature, ask:

1. **Does it make the core loop more fun?** If it doesn't touch rolling dice or the push-your-luck decision, it better strongly serve another pillar.

2. **Does it strengthen social bonds?** Features that create shared moments, rivalry, or conversation between players are high value.

3. **Does it respect async play?** Anything that requires both players to be online simultaneously is a red flag. Async is sacred.

4. **Is it simple enough to discover without instructions?** If it needs a tutorial, it's probably too complex. The game's strength is accessibility.

5. **Does it give players a reason to come back tomorrow?** Retention features (streaks, daily challenges, ongoing tournaments) should feel rewarding, not punishing.

6. **Does it avoid pay-to-win?** Competitive integrity matters. Cosmetic personalization is fine. Gameplay advantages for money are not.

---

## Anti-Patterns to Avoid

- **Synchronous requirements** — Don't make players wait for each other in real-time
- **Feature bloat** — Don't add complexity that obscures the core dice game
- **Grind mechanics** — Progression should feel like a bonus, not a job
- **Isolation features** — Don't build things that make the game feel like a solo experience
- **Notification spam** — Respect the player's attention; notify only when it's their turn or something meaningful happened
- **Copied meta-game trends** — Battle passes, loot boxes, and energy systems don't fit this game's identity

---

## Tone and Personality

Farkle Ten is **warm, playful, and lightly competitive**. It's kitchen-table energy, not esports energy. The UI should feel friendly and approachable. Humor is welcome. Intimidation is not.

Think: game night with people you like, not a casino floor.
