# Challenge Mode - UI Mockups (Mobile-First)

This document provides visual mockups for all Challenge Mode screens, designed to match the existing Farkle Ten mobile-first codebase.

**Design Compatibility:**
- Uses existing `.mobileButton` with `buttoncolor` attributes
- Uses `.regularBox` and `.tabletop` for containers
- Follows 5px padding, 8px button padding convention
- Font sizes: 20px headers, 16px subheaders, 14px body
- Dice images: 50x50px in shop, 16x16px in header
- Mobile-first: 60 chars wide mockups for iPhone
- Vertical stacking on mobile
- Uses existing `.shadowed` class for text shadows

---

## 1. Main Lobby - Challenge Mode Button

**Location:** farkle_div_lobby.tpl (in lobby button list)

```
┌──────────────────────────────────────────────────┐
│ [Green Felt Background]                          │
│                                                  │
│ Available games:                                 │
│ ┌──────────────────────────────┐                │
│ │ Your Game vs Player123       │                │
│ │ [Your turn]                  │                │
│ └──────────────────────────────┘                │
│                                                  │
│ ┌────────────┐   ┌────────────┐                 │
│ │ New Game   │   │ Tournament │                 │
│ │ [Default]  │   │ [Blue]     │                 │
│ └────────────┘   └────────────┘                 │
│                                                  │
│ ┌────────────┐   ┌────────────┐                 │
│ │ My Profile │   │  Friends   │                 │
│ └────────────┘   └────────────┘                 │
│                                                  │
│ ┌────────────┐   ┌────────────┐                 │
│ │Leaderboard │   │⚔️ Challenge │ ← NEW!         │
│ │            │   │    Mode    │                 │
│ │ [Default]  │   │ [Yellow]   │                 │
│ └────────────┘   └────────────┘                 │
│                                                  │
│ ┌────────────┐   ┌────────────┐                 │
│ │Instructions│   │  Logout    │                 │
│ └────────────┘   └────────────┘                 │
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<input type="button" class="mobileButton lobbyButton" buttoncolor="yellow"
    value="⚔️ Challenge" onClick="ShowChallengeMode()" id="btnLobbyChallengeMode">
```

**CSS Notes:**
- Uses existing `.mobileButton` class
- `buttoncolor="yellow"` for gold/challenge theme
- 120px width (same as other lobby buttons)
- 12pt font size (same as other lobby buttons)

---

## 2. Challenge Lobby - No Active Run

**File:** farkle_div_challengelobby.tpl

```
┌──────────────────────────────────────────────────┐
│ ⚔️ CHALLENGE MODE                    [← Back]   │
│ Beat 20 bots, upgrade dice, earn glory!         │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 🎲 THE GAUNTLET                              ││
│ │                                              ││
│ │ • Beat 20 AI bots in sequence               ││
│ │ • Earn 💰$1 per die you save                ││
│ │ • Buy dice in shop after each win           ││
│ │ • Lose once and start over                  ││
│ │                                              ││
│ │       ┌────────────────────┐                ││
│ │       │ START CHALLENGE    │                ││
│ │       │ [Yellow - Large]   │                ││
│ │       └────────────────────┘                ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 📊 THE 20 BOTS               [Show All >]   ││
│ │                                              ││
│ │ Bot #1  - Byte      [EASY]    2500 pts      ││
│ │ Bot #2  - Chip      [EASY]    2500 pts      ││
│ │ Bot #3  - Beep      [EASY]    2500 pts      ││
│ │ ...                                          ││
│ │ Bot #20 - ??? BOSS  [????]    ???? pts 🔒   ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 🏆 YOUR STATS                                ││
│ │                                              ││
│ │ Total Runs: 0                                ││
│ │ Furthest Bot: --                             ││
│ │ Completion Rate: --                          ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 📋 LEADERBOARD - Top Challengers             ││
│ │                                              ││
│ │ 1. PlayerName  - Bot #20 (Complete!) 🏆     ││
│ │ 2. PlayerName  - Bot #18                    ││
│ │ 3. PlayerName  - Bot #15                    ││
│ └──────────────────────────────────────────────┘│
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div id="divChallengeMode" align="center" style="display: none;">

  <!-- Header -->
  <div style="margin: 5px;">
    <span class="shadowed" style="font-size: 20px;">⚔️ CHALLENGE MODE</span>
    <input type="button" class="mobileButton" buttoncolor="red"
        value="← Back" onClick="ShowLobby()" style="width: 80px; float: right;">
  </div>

  <!-- Gauntlet Info -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 16px;">🎲 THE GAUNTLET</div>
    <p style="font-size: 14px; margin: 5px;">
      • Beat 20 AI bots in sequence<br/>
      • Earn 💰$1 per die you save<br/>
      • Buy dice in shop after each win<br/>
      • Lose once and start over
    </p>
    <input type="button" class="mobileButton" buttoncolor="yellow"
        value="START CHALLENGE" onClick="StartChallengeRun()"
        style="width: 250px; font-size: 20px;">
  </div>

  <!-- Bot List -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 16px;">📊 THE 20 BOTS</div>
    <div id="divBotPreview" style="font-size: 14px; margin: 5px;">
      <!-- Bot list items -->
    </div>
    <a href="#" onClick="ShowFullBotLineup()" style="font-size: 14px;">Show All ></a>
  </div>

  <!-- Stats -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 16px;">🏆 YOUR STATS</div>
    <div style="font-size: 14px; margin: 5px;">
      Total Runs: <span id="totalRuns">0</span><br/>
      Furthest Bot: <span id="furthestBot">--</span><br/>
      Completion Rate: <span id="completionRate">--</span>
    </div>
  </div>

  <!-- Leaderboard -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 16px;">📋 LEADERBOARD</div>
    <div id="divChallengeLeaderboard" style="font-size: 14px; margin: 5px;">
      <!-- Leaderboard entries -->
    </div>
  </div>

</div>
```

**CSS Notes:**
- Uses `.regularBox` (rgba(0,0,255,0.3) background, 8px border-radius, 5px padding)
- Headers use `.shadowed` class (2px 2px 0 #000 text-shadow)
- Font sizes: 20px header, 16px subheaders, 14px body
- 5px margins between sections
- Money uses monospace font: `<span style="font-family: 'Courier New', monospace;">💰$15</span>`

---

## 3. Challenge Lobby - Active Run

**Shows current progress, dice inventory, money**

```
┌──────────────────────────────────────────────────┐
│ ⚔️ CHALLENGE MODE                    [← Back]   │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ ⚡ ACTIVE RUN                                ││
│ │                                              ││
│ │ Bot: #7/20 - Cyber                           ││
│ │ Money: 💰$18                                 ││
│ │                                              ││
│ │ Your Dice:                                   ││
│ │ [⚪][⚪][🌟][🌟][⚪][💎]                      ││
│ │                                              ││
│ │ ┌────────────┐  ┌────────────┐              ││
│ │ │ CONTINUE   │  │ ABANDON    │              ││
│ │ │ [Yellow]   │  │ [Red]      │              ││
│ │ └────────────┘  └────────────┘              ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 📊 RUN STATS                                 ││
│ │                                              ││
│ │ Bots Defeated: 6 / 20                        ││
│ │ Money Earned: $45                            ││
│ │ Money Spent: $27                             ││
│ │ Dice Saved: 45                               ││
│ │                                              ││
│ │ Progress:                                    ││
│ │ ┌──────────────────────────┐                ││
│ │ │████████░░░░░░░░░░  30%   │ ← Uses #xpBar ││
│ │ └──────────────────────────┘                ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 🎯 NEXT OPPONENT                             ││
│ │                                              ││
│ │ Bot #7 - Cyber the Analyst       [MEDIUM]   ││
│ │ Target: 3000 points                          ││
│ │ Plays methodically, takes risks              ││
│ └──────────────────────────────────────────────┘│
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div class="regularBox" style="border: 2px solid #f7ef00;"> <!-- Yellow border -->
  <div class="shadowed" style="font-size: 16px;">⚡ ACTIVE RUN</div>

  <div style="font-size: 14px; margin: 5px;">
    Bot: #<span id="currentBot">7</span>/20 - <span id="currentBotName">Cyber</span><br/>
    Money: <span style="font-family: 'Courier New', monospace;">💰$<span id="currentMoney">18</span></span>
  </div>

  <div style="margin: 5px;">
    Your Dice:<br/>
    <div id="divDiceInventory">
      <!-- 16x16px dice images -->
      <img src="/images/dice_tiny_white.png" class="tinydice">
      <img src="/images/dice_tiny_white.png" class="tinydice">
      <img src="/images/dice_tiny_lucky.png" class="tinydice">
      <img src="/images/dice_tiny_lucky.png" class="tinydice">
      <img src="/images/dice_tiny_white.png" class="tinydice">
      <img src="/images/dice_tiny_double.png" class="tinydice">
    </div>
  </div>

  <input type="button" class="mobileButton" buttoncolor="yellow"
      value="CONTINUE" onClick="ContinueChallengeRun()" style="width: 120px;">
  <input type="button" class="mobileButton" buttoncolor="red"
      value="ABANDON" onClick="AbandonChallengeRun()" style="width: 120px;">
</div>

<!-- Progress bar -->
<div class="regularBox">
  <div class="shadowed" style="font-size: 16px;">📊 RUN STATS</div>
  <div style="font-size: 14px; margin: 5px;">
    Bots Defeated: <span id="botsDefeated">6</span> / 20<br/>
    Money Earned: $<span id="moneyEarned">45</span><br/>
    Money Spent: $<span id="moneySpent">27</span><br/>
    Dice Saved: <span id="diceSaved">45</span>
  </div>

  <div style="margin: 5px;">
    Progress:<br/>
    <table width="100%" height="16px" cellpadding="0" cellspacing="0"
        style="border: 1px solid black; margin: 0px;">
      <tr>
        <td id="xpBar" style="width: 30%;"></td>
        <td id="xpNeg" style="width: 70%;"></td>
      </tr>
    </table>
  </div>
</div>
```

**CSS Notes:**
- Active run box has `border: 2px solid #f7ef00;` (yellow border)
- Uses existing `#xpBar` and `#xpNeg` styles for progress bar
- `.tinydice` class (16x16px) for dice inventory display
- Monospace font for money display

---

## 4. Shop Interface - After Victory

**Mobile-first: Vertical stacking, 50x50px dice**

```
┌──────────────────────────────────────────────────┐
│ 🏆 VICTORY!                                      │
│ You defeated Bot #6 - Logic                     │
│                                                  │
│ Money: 💰$15                                     │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ 🏪 DICE SHOP                                 ││
│ │ Choose a die (or skip to save money)         ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ LUCKY DIE                           [GREEN]  ││
│ │                                              ││
│ │       [  🌟  ]  ← 50x50px dice image        ││
│ │                                              ││
│ │ Higher chance to roll 1s & 5s.               ││
│ │ Great for consistent scoring.                ││
│ │                                              ││
│ │ 💰$5                                         ││
│ │                                              ││
│ │ ┌──────────────┐                             ││
│ │ │     BUY      │                             ││
│ │ │   [Green]    │                             ││
│ │ └──────────────┘                             ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ INSURANCE DIE                        [BLUE]  ││
│ │                                              ││
│ │       [  🛡️  ]                               ││
│ │                                              ││
│ │ Prevents 1st farkle each turn.               ││
│ │ Safety net for risky plays.                  ││
│ │                                              ││
│ │ 💰$8                                         ││
│ │                                              ││
│ │ ┌──────────────┐                             ││
│ │ │     BUY      │                             ││
│ │ │  [Disabled]  │ ← Too expensive             ││
│ │ └──────────────┘                             ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ DOUBLE DIE                        [PURPLE]   ││
│ │                                              ││
│ │       [  💎  ]                               ││
│ │                                              ││
│ │ Doubles points from this die.                ││
│ │ High risk, high reward!                      ││
│ │                                              ││
│ │ 💰$12                                        ││
│ │                                              ││
│ │ ┌──────────────┐                             ││
│ │ │     BUY      │                             ││
│ │ │  [Disabled]  │                             ││
│ │ └──────────────┘                             ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ YOUR DICE:                                   ││
│ │ [⚪][⚪][🌟][🌟][⚪][⚪]                       ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌────────────┐  ┌────────────┐                  │
│ │    SKIP    │  │ CONTINUE → │                  │
│ │  [Orange]  │  │  [Green]   │                  │
│ └────────────┘  └────────────┘                  │
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div id="divChallengeShop" align="center" style="display: none;">

  <!-- Victory Header -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 20px;">🏆 VICTORY!</div>
    <p style="font-size: 14px;">You defeated Bot #6 - Logic</p>
    <div style="font-size: 16px; font-family: 'Courier New', monospace;">
      Money: 💰$<span id="shopMoney">15</span>
    </div>
  </div>

  <!-- Shop Header -->
  <div class="regularBox">
    <div class="shadowed" style="font-size: 16px;">🏪 DICE SHOP</div>
    <p style="font-size: 14px;">Choose a die (or skip to save money)</p>
  </div>

  <!-- Dice Card 1 - Affordable -->
  <div class="loginBox" style="border: 2px solid #1d8711;"> <!-- Green border -->
    <div style="font-size: 16px; font-weight: bold; color: #1d8711;">LUCKY DIE</div>

    <div style="margin: 5px;">
      <img src="/images/dice_lucky_50.png" width="50" height="50">
    </div>

    <p style="font-size: 14px; margin: 5px;">
      Higher chance to roll 1s & 5s.<br/>
      Great for consistent scoring.
    </p>

    <div style="font-size: 16px; font-family: 'Courier New', monospace; margin: 5px;">
      💰$5
    </div>

    <input type="button" class="mobileButton" buttoncolor="green"
        value="BUY" onClick="BuyDie('lucky', 5)" style="width: 150px;">
  </div>

  <!-- Dice Card 2 - Too Expensive -->
  <div class="loginBox" style="opacity: 0.6;"> <!-- Greyed out -->
    <div style="font-size: 16px; font-weight: bold; color: #666;">INSURANCE DIE</div>

    <div style="margin: 5px;">
      <img src="/images/dice_insurance_50.png" width="50" height="50">
    </div>

    <p style="font-size: 14px; margin: 5px;">
      Prevents 1st farkle each turn.<br/>
      Safety net for risky plays.
    </p>

    <div style="font-size: 16px; font-family: 'Courier New', monospace; margin: 5px;">
      💰$8
    </div>

    <input type="button" class="mobileButton" disabled
        value="BUY" style="width: 150px;">
  </div>

  <!-- Dice Card 3 - Too Expensive -->
  <div class="loginBox" style="opacity: 0.6;">
    <div style="font-size: 16px; font-weight: bold; color: #666;">DOUBLE DIE</div>

    <div style="margin: 5px;">
      <img src="/images/dice_double_50.png" width="50" height="50">
    </div>

    <p style="font-size: 14px; margin: 5px;">
      Doubles points from this die.<br/>
      High risk, high reward!
    </p>

    <div style="font-size: 16px; font-family: 'Courier New', monospace; margin: 5px;">
      💰$12
    </div>

    <input type="button" class="mobileButton" disabled
        value="BUY" style="width: 150px;">
  </div>

  <!-- Current Dice Inventory -->
  <div class="regularBox">
    <div style="font-size: 14px;">YOUR DICE:</div>
    <div id="divShopDiceInventory" style="margin: 5px;">
      <!-- 16x16px dice images -->
    </div>
  </div>

  <!-- Navigation -->
  <input type="button" class="mobileButton" buttoncolor="orange"
      value="SKIP" onClick="SkipShop()" style="width: 120px;">
  <input type="button" class="mobileButton" buttoncolor="green"
      value="CONTINUE →" onClick="ContinueToNextBot()"
      style="width: 150px; display: none;" id="btnContinueAfterPurchase">

</div>
```

**CSS Notes:**
- Uses `.loginBox` for dice cards (lightgreenfeltbg.png, 8px border-radius, 5px padding)
- Affordable dice: `border: 2px solid #1d8711;` (green)
- Expensive dice: `opacity: 0.6;` for greyed out effect
- 50x50px dice images in shop
- Vertical stacking (mobile-first)
- Touch target minimum 44px (button height 40px + margin)

---

## 5. Slot Selection Modal

**User clicked BUY, now choosing which die to replace**

```
┌──────────────────────────────────────────────────┐
│ [DIMMED BACKGROUND - Shop visible behind]        │
│                                                  │
│   ┌────────────────────────────────────────┐    │
│   │ SELECT DICE TO REPLACE                 │    │
│   │                                        │    │
│   │ You're purchasing:                     │    │
│   │  [🌟] LUCKY DIE                        │    │
│   │  Cost: 💰$5                            │    │
│   │                                        │    │
│   │ Which die should it replace?           │    │
│   │                                        │    │
│   │ [⚪] [⚪] [🌟] [🌟] [⚪] [⚪]            │    │
│   │ Std  Std Lucky Lucky Std  Std          │    │
│   │                                        │    │
│   │ [Tap a die to select]                  │    │
│   │                                        │    │
│   │ ┌──────────┐  ┌──────────┐             │    │
│   │ │ CANCEL   │  │ CONFIRM  │             │    │
│   │ │ [Grey]   │  │[Disabled]│             │    │
│   │ └──────────┘  └──────────┘             │    │
│   └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘

AFTER SELECTING SLOT 1:

┌──────────────────────────────────────────────────┐
│ [DIMMED BACKGROUND]                              │
│                                                  │
│   ┌────────────────────────────────────────┐    │
│   │ SELECT DICE TO REPLACE                 │    │
│   │                                        │    │
│   │ You're purchasing:                     │    │
│   │  [🌟] LUCKY DIE - Cost: 💰$5          │    │
│   │                                        │    │
│   │ Which die should it replace?           │    │
│   │                                        │    │
│   │ [⚪] [⚪] [🌟] [🌟] [⚪] [⚪]            │    │
│   │ ★    Std Lucky Lucky Std  Std          │    │
│   │ SELECTED                               │    │
│   │                                        │    │
│   │ ┌──────────┐  ┌──────────┐             │    │
│   │ │ CANCEL   │  │ CONFIRM  │             │    │
│   │ │ [Grey]   │  │ [Yellow] │             │    │
│   │ └──────────┘  └──────────┘             │    │
│   └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<!-- Modal overlay -->
<div id="slotSelectionOverlay" style="display: none; position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); z-index: 1000;">

  <!-- Modal content -->
  <div class="bot-select-modal" style="margin: 50px auto; max-width: 90%;">

    <h2 style="color: #333; font-size: 20px; margin: 0 0 8px 0;">
      SELECT DICE TO REPLACE
    </h2>

    <div style="color: #666; font-size: 14px; margin: 5px;">
      You're purchasing:<br/>
      <span id="purchaseDiceType">[🌟] LUCKY DIE</span><br/>
      Cost: <span style="font-family: 'Courier New', monospace;">💰$<span id="purchasePrice">5</span></span>
    </div>

    <div style="color: #333; font-size: 14px; margin: 8px 0;">
      Which die should it replace?
    </div>

    <!-- Dice slots (clickable) -->
    <div id="divSlotSelection" style="margin: 8px 0;">
      <div class="diceSlot" onclick="SelectSlot(0)" style="display: inline-block;
          margin: 5px; padding: 5px; border: 2px solid #ccc; border-radius: 4px;
          cursor: pointer;">
        <img src="/images/dice_white_50.png" width="50" height="50"><br/>
        <span style="font-size: 12px; color: #333;">Std</span>
      </div>
      <div class="diceSlot" onclick="SelectSlot(1)" style="display: inline-block;
          margin: 5px; padding: 5px; border: 2px solid #ccc; border-radius: 4px;
          cursor: pointer;">
        <img src="/images/dice_white_50.png" width="50" height="50"><br/>
        <span style="font-size: 12px; color: #333;">Std</span>
      </div>
      <!-- More slots... -->
    </div>

    <div id="slotSelectedLabel" style="display: none; color: #f7ef00;
        font-size: 14px; margin: 5px;">
      ★ SELECTED
    </div>

    <input type="button" class="mobileButton" buttoncolor="grey"
        value="CANCEL" onClick="CloseSlotModal()" style="width: 110px;">
    <input type="button" class="mobileButton" buttoncolor="yellow" disabled
        value="CONFIRM" onClick="ConfirmPurchase()" id="btnConfirmPurchase"
        style="width: 110px;">
  </div>

</div>
```

**CSS Notes:**
- Uses `.bot-select-modal` styling (white background, 12px border-radius, 24px padding, shadow)
- Modal overlay: `rgba(0,0,0,0.7)` for dimming
- Dice slots: 50x50px images, 5px padding, 2px border
- Selected slot: `border: 2px solid #f7ef00;` (yellow)
- Buttons: grey for cancel, yellow for confirm (disabled until selection)

---

## 6. In-Game Challenge UI

**Playing against Bot #7, mid-turn**

```
┌──────────────────────────────────────────────────┐
│ ⚔️ Challenge Bot #7/20     💰$18  ⚪⚪🌟🌟⚪💎   │
│ vs. Cyber the Analyst                            │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ PLAYERS                                      ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ [Your Card]              Score: 2150     │ ││
│ │ │ YOUR TURN [Orange bg]                    │ ││
│ │ └──────────────────────────────────────────┘ ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ [Bot - Cyber]            Score: 1800     │ ││
│ │ │ Waiting...                               │ ││
│ │ └──────────────────────────────────────────┘ ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ Round: 850 | Turn: 250                       ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ [Green Felt - Dice Area]                     ││
│ │                                              ││
│ │   [6]  [4]  [6]                              ││
│ │   ●●●  ● ●  ●●●                              ││
│ │   ●●●      ●●●   ← Clickable dice           ││
│ │   ●●●      ●●●                               ││
│ │                                              ││
│ │        +$1 💰  ← Floats up when saving die  ││
│ └──────────────────────────────────────────────┘│
│                                                  │
│ ┌────────────┐  ┌────────────┐                  │
│ │ SCORE IT   │  │ ROLL AGAIN │                  │
│ │ [Orange]   │  │  [Green]   │                  │
│ └────────────┘  └────────────┘                  │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ Bot Chat                                     ││
│ │ Cyber: "Calculating probability... you got   ││
│ │ lucky with that roll."                       ││
│ └──────────────────────────────────────────────┘│
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<!-- Challenge header (sticky) -->
<div style="background: rgba(0,0,255,0.3); padding: 5px; margin: 0;
    border-bottom: 1px solid black; position: sticky; top: 0; z-index: 100;">
  <span class="shadowed" style="font-size: 16px;">
    ⚔️ Challenge Bot #<span id="challengeBotNum">7</span>/20
  </span>
  <span style="float: right; font-size: 14px; font-family: 'Courier New', monospace;">
    💰$<span id="challengeMoney">18</span>
  </span>
  <br/>
  <span style="font-size: 14px;">vs. <span id="challengeBotName">Cyber the Analyst</span></span>
  <span style="float: right; font-size: 12px;">
    <!-- Tiny dice inventory -->
    <img src="/images/dice_tiny_white.png" class="tinydice">
    <img src="/images/dice_tiny_white.png" class="tinydice">
    <img src="/images/dice_tiny_lucky.png" class="tinydice">
    <img src="/images/dice_tiny_lucky.png" class="tinydice">
    <img src="/images/dice_tiny_white.png" class="tinydice">
    <img src="/images/dice_tiny_double.png" class="tinydice">
  </span>
</div>

<!-- Regular game UI (existing farkle_div_game.tpl structure) -->
<div class="gamePlayersDiv">
  <!-- Player cards... -->
</div>

<div class="inGameInfo">
  <span class="shadowed">Round: <span id="roundScore">850</span></span>
  <span class="shadowed">| Turn: <span id="turnScore">250</span></span>
</div>

<div class="tabletop">
  <!-- Dice area... -->
</div>

<!-- Money animation -->
<div id="moneyEarned" class="xpgain" style="color: #f7ef00;">
  +$1 💰
</div>
```

**CSS Notes:**
- Challenge header: `rgba(0,0,255,0.3)` background, sticky positioning
- Money display: monospace font, yellow color
- Uses existing `.inGameInfo`, `.tabletop`, `.gamePlayersDiv` classes
- Money animation: uses `.xpgain` class (floats up and fades)
- 16x16px dice in header inventory

---

## 7. Victory Screen - Post-Game

**Player just beat Bot #7**

```
┌──────────────────────────────────────────────────┐
│ [DIMMED BACKGROUND]                              │
│                                                  │
│   ┌────────────────────────────────────────┐    │
│   │ 🏆 VICTORY! 🏆                         │    │
│   │                                        │    │
│   │ You defeated Bot #7 - Cyber!           │    │
│   │                                        │    │
│   │ Final Score: 3150 - 2800               │    │
│   │ Dice Saved: 14                         │    │
│   │ Money Earned: +💰$14                   │    │
│   │                                        │    │
│   │ Total Money: 💰$32                     │    │
│   │                                        │    │
│   │ Progress: 7/20 Bots                    │    │
│   │ ┌──────────────────────┐               │    │
│   │ │████████░░░░░░░░  35% │               │    │
│   │ └──────────────────────┘               │    │
│   │                                        │    │
│   │ Next: Bot #8 - Binary (Medium)         │    │
│   │                                        │    │
│   │ ┌────────────────────────┐             │    │
│   │ │ VISIT SHOP             │             │    │
│   │ │ [Yellow - Large]       │             │    │
│   │ └────────────────────────┘             │    │
│   │                                        │    │
│   │ ┌────────────────────────┐             │    │
│   │ │ Skip Shop - Next Bot   │             │    │
│   │ │ [Green - Smaller]      │             │    │
│   │ └────────────────────────┘             │    │
│   └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div id="challengeVictoryOverlay" style="display: none; position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); z-index: 1000;">

  <div class="bot-select-modal" style="background: rgba(255, 215, 0, 0.2);
      margin: 50px auto; max-width: 90%;">

    <h2 style="color: #f7ef00; font-size: 24px; margin: 0 0 8px 0;" class="shadowed">
      🏆 VICTORY! 🏆
    </h2>

    <p style="font-size: 16px; color: white; margin: 5px;" class="shadowed">
      You defeated Bot #<span id="victoryBotNum">7</span> - <span id="victoryBotName">Cyber</span>!
    </p>

    <div style="font-size: 14px; color: white; margin: 5px;">
      Final Score: <span id="victoryScore">3150 - 2800</span><br/>
      Dice Saved: <span id="victoryDiceSaved">14</span><br/>
      Money Earned: <span style="color: #1d8711; font-weight: bold;">+💰$<span id="victoryMoneyEarned">14</span></span>
    </div>

    <div style="font-size: 16px; font-family: 'Courier New', monospace;
        color: white; margin: 8px 0;" class="shadowed">
      Total Money: 💰$<span id="victoryTotalMoney">32</span>
    </div>

    <div style="font-size: 14px; color: white; margin: 5px;">
      Progress: <span id="victoryProgress">7/20</span> Bots
    </div>

    <table width="200px" height="16px" cellpadding="0" cellspacing="0"
        style="border: 1px solid black; margin: 5px auto;">
      <tr>
        <td id="xpBar" style="width: 35%;"></td>
        <td id="xpNeg" style="width: 65%;"></td>
      </tr>
    </table>

    <p style="font-size: 14px; color: white; margin: 8px 0;">
      Next: Bot #<span id="nextBotNum">8</span> - <span id="nextBotName">Binary</span>
      (<span id="nextBotDiff">Medium</span>)
    </p>

    <input type="button" class="mobileButton" buttoncolor="yellow"
        value="VISIT SHOP" onClick="ShowChallengeShop()"
        style="width: 220px; font-size: 20px; margin: 8px;">

    <input type="button" class="mobileButton" buttoncolor="green"
        value="Skip Shop - Next Bot" onClick="SkipToNextBot()"
        style="width: 200px; font-size: 16px;">
  </div>

</div>
```

**CSS Notes:**
- Modal with gold tint: `background: rgba(255, 215, 0, 0.2);`
- Uses `.shadowed` class for text shadows
- Progress bar uses existing `#xpBar` / `#xpNeg` styles
- Large yellow button (220px, 20px font)
- Smaller green button (200px, 16px font)

---

## 8. Defeat Screen - Game Over

**Player lost to Bot #12**

```
┌──────────────────────────────────────────────────┐
│ [DARKENED BACKGROUND]                            │
│                                                  │
│   ┌────────────────────────────────────────┐    │
│   │ 💀 DEFEATED 💀                         │    │
│   │                                        │    │
│   │ Bot #12 - Quantum defeated you!        │    │
│   │ Final Score: 2750 - 3200               │    │
│   │                                        │    │
│   │ ───────────────────────────            │    │
│   │ RUN SUMMARY                            │    │
│   │                                        │    │
│   │ Bots Defeated: 11/20                   │    │
│   │ Dice Saved: 127                        │    │
│   │ Money Earned: $127                     │    │
│   │ Money Spent: $89                       │    │
│   │                                        │    │
│   │ ───────────────────────────            │    │
│   │ LEADERBOARD UPDATE                     │    │
│   │                                        │    │
│   │ Furthest Bot: #11 → #12 (NEW!)         │    │
│   │ Rank: #47 → #32                        │    │
│   │                                        │    │
│   │ ┌────────────────────────┐             │    │
│   │ │     TRY AGAIN          │             │    │
│   │ │     [Green]            │             │    │
│   │ └────────────────────────┘             │    │
│   │                                        │    │
│   │ ┌────────────────────────┐             │    │
│   │ │ Return to Lobby        │             │    │
│   │ │ [Grey]                 │             │    │
│   │ └────────────────────────┘             │    │
│   └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div id="challengeDefeatOverlay" style="display: none; position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8); z-index: 1000;">

  <div class="bot-select-modal" style="background: rgba(100, 0, 0, 0.3);
      margin: 50px auto; max-width: 90%;">

    <h2 style="color: #ff5154; font-size: 24px; margin: 0 0 8px 0;" class="shadowed">
      💀 DEFEATED 💀
    </h2>

    <p style="font-size: 16px; color: white; margin: 5px;" class="shadowed">
      Bot #<span id="defeatBotNum">12</span> - <span id="defeatBotName">Quantum</span> defeated you!
    </p>

    <p style="font-size: 14px; color: white; margin: 5px;">
      Final Score: <span id="defeatScore">2750 - 3200</span>
    </p>

    <div style="border-top: 1px solid white; margin: 8px 0; padding-top: 8px;">
      <div style="font-size: 16px; color: #f7ef00;" class="shadowed">RUN SUMMARY</div>
      <div style="font-size: 14px; color: white; margin: 5px;">
        Bots Defeated: <span id="defeatBotsDefeated">11</span>/20<br/>
        Dice Saved: <span id="defeatDiceSaved">127</span><br/>
        Money Earned: $<span id="defeatMoneyEarned">127</span><br/>
        Money Spent: $<span id="defeatMoneySpent">89</span>
      </div>
    </div>

    <div style="border-top: 1px solid white; margin: 8px 0; padding-top: 8px;">
      <div style="font-size: 16px; color: #f7ef00;" class="shadowed">LEADERBOARD UPDATE</div>
      <div style="font-size: 14px; color: white; margin: 5px;">
        Furthest Bot: #<span id="oldBest">11</span> → #<span id="newBest">12</span>
        <span style="color: #1d8711; font-weight: bold;">(NEW!)</span><br/>
        Rank: #<span id="oldRank">47</span> → #<span id="newRank">32</span>
      </div>
    </div>

    <input type="button" class="mobileButton" buttoncolor="green"
        value="TRY AGAIN" onClick="StartNewChallengeRun()"
        style="width: 200px; font-size: 20px; margin: 8px;">

    <input type="button" class="mobileButton" buttoncolor="grey"
        value="Return to Lobby" onClick="ShowLobby()"
        style="width: 180px; font-size: 16px;">
  </div>

</div>
```

**CSS Notes:**
- Modal with dark red tint: `background: rgba(100, 0, 0, 0.3);`
- Darker overlay: `rgba(0,0,0,0.8)`
- Red title color: `#ff5154`
- Section dividers: `border-top: 1px solid white;`
- Primary action: Green "Try Again" button
- Secondary action: Grey "Return to Lobby" button

---

## 9. Full Bot Lineup Modal

**Player clicked "Show All"**

```
┌──────────────────────────────────────────────────┐
│ [DIMMED BACKGROUND]                              │
│                                                  │
│ ┌──────────────────────────────────────────────┐│
│ │ THE 20-BOT GAUNTLET              [✕ Close]   ││
│ │                                              ││
│ │ [Scrollable list]                            ││
│ │                                              ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ Bot #1  Byte       [EASY]  2500  ✓ WIN  │ ││
│ │ │ Friendly beginner to warm up.            │ ││
│ │ └──────────────────────────────────────────┘ ││
│ │                                              ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ Bot #2  Chip       [EASY]  2500  ✓ WIN  │ ││
│ │ │ Enthusiastic cheerleader.                │ ││
│ │ └──────────────────────────────────────────┘ ││
│ │                                              ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ Bot #3  Beep       [EASY]  2500  --     │ ││
│ │ │ Confused rookie.                         │ ││
│ │ └──────────────────────────────────────────┘ ││
│ │                                              ││
│ │ ... [More bots]                              ││
│ │                                              ││
│ │ ┌──────────────────────────────────────────┐ ││
│ │ │ Bot #20 ??? BOSS  [????]  ????  🔒      │ ││
│ │ │ ???????????????????????????????          │ ││
│ │ └──────────────────────────────────────────┘ ││
│ │                                              ││
│ └──────────────────────────────────────────────┘│
│                                                  │
└──────────────────────────────────────────────────┘
```

**HTML:**
```html
<div id="botLineupOverlay" style="display: none; position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); z-index: 1000;">

  <div class="bot-select-modal" style="margin: 20px auto; max-width: 90%;
      max-height: 90vh; overflow-y: auto;">

    <h2 style="color: #333; font-size: 20px; margin: 0 0 8px 0;">
      THE 20-BOT GAUNTLET
      <span style="float: right; cursor: pointer; font-size: 24px;"
          onclick="CloseBotLineup()">✕</span>
    </h2>

    <!-- Bot entry - Easy, defeated -->
    <div class="bot-option" style="border-color: #1d8711; background: rgba(29, 135, 17, 0.05);">
      <h3 style="color: #333; font-size: 16px; margin: 0 0 4px 0;">
        Bot #1 - Byte
        <span style="float: right; color: #1d8711; font-size: 14px;">[EASY]</span>
      </h3>
      <p style="font-size: 14px; color: #666; margin: 0;">
        Target: 2500 pts | Your result: ✓ WIN
      </p>
      <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
        Friendly beginner to warm up.
      </p>
    </div>

    <!-- Bot entry - Easy, defeated -->
    <div class="bot-option" style="border-color: #1d8711; background: rgba(29, 135, 17, 0.05);">
      <h3 style="color: #333; font-size: 16px; margin: 0 0 4px 0;">
        Bot #2 - Chip
        <span style="float: right; color: #1d8711; font-size: 14px;">[EASY]</span>
      </h3>
      <p style="font-size: 14px; color: #666; margin: 0;">
        Target: 2500 pts | Your result: ✓ WIN
      </p>
      <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
        Enthusiastic cheerleader.
      </p>
    </div>

    <!-- Bot entry - Easy, locked -->
    <div class="bot-option" style="opacity: 0.6;">
      <h3 style="color: #666; font-size: 16px; margin: 0 0 4px 0;">
        Bot #3 - Beep
        <span style="float: right; color: #1d8711; font-size: 14px;">[EASY]</span>
      </h3>
      <p style="font-size: 14px; color: #666; margin: 0;">
        Target: 2500 pts | Your result: --
      </p>
      <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
        Confused rookie who makes questionable choices.
      </p>
    </div>

    <!-- More bots... -->

    <!-- Bot entry - Boss, locked -->
    <div class="bot-option" style="border-color: #9400D3; opacity: 0.6;">
      <h3 style="color: #666; font-size: 16px; margin: 0 0 4px 0;">
        Bot #20 - ??? BOSS ???
        <span style="float: right; color: #9400D3; font-size: 14px;">[????]</span>
      </h3>
      <p style="font-size: 14px; color: #666; margin: 0;">
        Target: ???? pts | 🔒 Locked
      </p>
      <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
        ???????????????????????????????
      </p>
    </div>

  </div>

</div>
```

**CSS Notes:**
- Uses existing `.bot-option` class
- Color-coded borders:
  - Easy: `#1d8711` (green)
  - Medium: `#FFA500` (orange)
  - Hard: `#cc0000` (red)
  - Boss: `#9400D3` (purple)
- Defeated bots: light color tint background
- Locked bots: `opacity: 0.6`
- Scrollable: `max-height: 90vh; overflow-y: auto;`

---

## 10. Design System Summary

### Colors

**Backgrounds:**
- Green felt: `background-image:url('/images/greenfeltbg.png');`
- Light green felt: `background-image:url('/images/lightgreenfeltbg.png');`
- Section boxes: `background-color: rgba(0,0,255,0.3);` (.regularBox)
- Modal overlay: `rgba(0,0,0,0.7)`
- White modals: `background: white;` (.bot-select-modal)

**Button Colors (buttoncolor attribute):**
- Yellow: `buttoncolor="yellow"` - Challenge mode primary
- Green: `buttoncolor="green"` - Positive actions
- Orange: `buttoncolor="orange"` - Secondary actions
- Red: `buttoncolor="red"` - Destructive/cancel
- Grey: `buttoncolor="grey"` - Disabled/neutral
- Purple: `buttoncolor="purple"` - Bot games
- Blue: `buttoncolor="blue"` - Info/tournament

**Difficulty/Tier Colors:**
- Easy: `#1d8711` (green)
- Medium: `#FFA500` (orange)
- Hard: `#cc0000` (red)
- Boss: `#9400D3` (purple)

### Typography

**Font Family:**
- Primary: `Verdana, Lucida Grande, sans-serif`
- Money/Numbers: `'Courier New', monospace`

**Font Sizes:**
- Headers: 20px (bold)
- Subheaders: 16px (bold)
- Body: 14px
- Small: 12px
- Buttons: 20px (bold)

**Text Effects:**
- Shadowed: `.shadowed` class
  ```css
  text-shadow: 2px 2px 0 #000, -1px -1px 0 #000,
               1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
  ```

### Spacing

- Section padding: 5px (.regularBox, .loginBox)
- Button padding: 8px (.mobileButton)
- Margins: 5px between elements
- Border radius: 8px (containers), 6px (buttons)
- Box shadow: `box-shadow: -8px 8px 6px -6px black;` (.shadow)

### Components

**Buttons:**
```html
<input type="button" class="mobileButton" buttoncolor="yellow"
    value="Button Text" onClick="Function()" style="width: 150px;">
```

**Section Boxes:**
```html
<div class="regularBox">
  <div class="shadowed" style="font-size: 16px;">Section Header</div>
  <p style="font-size: 14px;">Content...</p>
</div>
```

**Progress Bar:**
```html
<table width="100%" height="16px" cellpadding="0" cellspacing="0"
    style="border: 1px solid black; margin: 0px;">
  <tr>
    <td id="xpBar" style="width: 30%;"></td>
    <td id="xpNeg" style="width: 70%;"></td>
  </tr>
</table>
```

**Money Display:**
```html
<span style="font-family: 'Courier New', monospace;">💰$<span id="money">15</span></span>
```

**Dice Images:**
- Large (shop): 50x50px
- Small (game): 40x40px (mobile), 60x60px (desktop)
- Tiny (inventory): 16x16px (.tinydice class)

### Modals

**Pattern:**
```html
<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); z-index: 1000;">
  <div class="bot-select-modal" style="margin: 50px auto; max-width: 90%;">
    <!-- Content -->
  </div>
</div>
```

**Modal Styling:**
- White background
- 12px border-radius
- 24px padding (desktop), 16px (mobile)
- `box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);`
- Max-width: 500px (desktop), 90% (mobile)

### Mobile Breakpoint

```css
@media screen and (max-width: 480px) {
  /* Adjustments */
}
```

### Touch Targets

- Minimum height: 40px (buttons)
- Minimum tap area: 44px (iOS guideline)
- Dice: 40x40px on mobile

---

## Implementation Notes

1. **Reuse existing classes** - Don't create new CSS, use `.mobileButton`, `.regularBox`, `.loginBox`, `.tabletop`
2. **Use buttoncolor attribute** - NOT custom gradients
3. **Follow spacing convention** - 5px padding, 8px button padding
4. **Mobile-first** - Stack vertically, use 40px dice on mobile
5. **Use existing animations** - `.xpgain` for money floats, dice rolling animations
6. **Monospace for money** - Always use Courier New for dollar amounts
7. **Shadowed text** - Use `.shadowed` class for headers
8. **Dice images** - 50x50px in shop, 16x16px in header, 40x40px in game (mobile)

---

**End of UI Mockups**
