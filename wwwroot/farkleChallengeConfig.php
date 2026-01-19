<?php
/**
 * farkleChallengeConfig.php
 *
 * Hardcoded configuration for Challenge Mode's 20 bot opponents.
 * Difficulty scales by increasing the point target from 300 to 10,000.
 */

function Challenge_GetBotConfig($botNumber = null) {
    $bots = [
        // EASY TIER (Bots 1-5)
        1 => [
            'name' => 'Novice Nim',
            'title' => 'The Welcome Guide',
            'difficulty' => 'Easy',
            'description' => 'A gentle guide who welcomes newcomers.',
            'point_target' => 300,
        ],
        2 => [
            'name' => 'Pip the Eager',
            'title' => 'The Enthusiast',
            'difficulty' => 'Easy',
            'description' => 'An enthusiastic rookie excited to play!',
            'point_target' => 500,
        ],
        3 => [
            'name' => 'Copper Clara',
            'title' => 'The Friendly Rival',
            'difficulty' => 'Easy',
            'description' => 'A friendly rival who makes the journey fun.',
            'point_target' => 750,
        ],
        4 => [
            'name' => 'Stumbling Stan',
            'title' => 'The Lucky Fool',
            'difficulty' => 'Easy',
            'description' => 'The luckiest fool in the gauntlet.',
            'point_target' => 1000,
        ],
        5 => [
            'name' => 'Warden Willow',
            'title' => 'The First Gatekeeper',
            'difficulty' => 'Easy',
            'description' => 'Tests if you\'re ready for what lies ahead.',
            'point_target' => 1500,
        ],

        // MEDIUM TIER (Bots 6-10)
        6 => [
            'name' => 'Baron Bones',
            'title' => 'The Skeletal Strategist',
            'difficulty' => 'Medium',
            'description' => 'A calculating rival who\'s played for centuries.',
            'point_target' => 2000,
        ],
        7 => [
            'name' => 'Jinx the Jester',
            'title' => 'The Chaos Trickster',
            'difficulty' => 'Medium',
            'description' => 'Delights in unpredictability and chaos.',
            'point_target' => 2500,
        ],
        8 => [
            'name' => 'Steel Sergeant',
            'title' => 'The Tactical Commander',
            'difficulty' => 'Medium',
            'description' => 'Approaches every roll with military precision.',
            'point_target' => 3000,
        ],
        9 => [
            'name' => 'Mystic Maven',
            'title' => 'The Fortune Teller',
            'difficulty' => 'Medium',
            'description' => 'Claims to see the dice before they land.',
            'point_target' => 3500,
        ],
        10 => [
            'name' => 'Old Guard Garrison',
            'title' => 'The Retired Champion',
            'difficulty' => 'Medium',
            'description' => 'A legend from the gauntlet\'s golden age.',
            'point_target' => 4000,
        ],

        // HARD TIER (Bots 11-15)
        11 => [
            'name' => 'Shadow Sable',
            'title' => 'The Silent Assassin',
            'difficulty' => 'Hard',
            'description' => 'Dispatches challengers with cold efficiency.',
            'point_target' => 4500,
        ],
        12 => [
            'name' => 'Rage Ravager',
            'title' => 'The Berserker',
            'difficulty' => 'Hard',
            'description' => 'Channels fury into aggressive play.',
            'point_target' => 5000,
        ],
        13 => [
            'name' => 'Archon Aurelius',
            'title' => 'The Noble Tactician',
            'difficulty' => 'Hard',
            'description' => 'Treats Farkle as an art form.',
            'point_target' => 5500,
        ],
        14 => [
            'name' => 'Hex Harrow',
            'title' => 'The Dice Witch',
            'difficulty' => 'Hard',
            'description' => 'Seems to curse your dice with bad luck.',
            'point_target' => 6000,
        ],
        15 => [
            'name' => 'Doom Dealer',
            'title' => 'The Executioner',
            'difficulty' => 'Hard',
            'description' => 'The last test before legendary tier.',
            'point_target' => 6500,
        ],

        // BOSS TIER (Bots 16-20)
        16 => [
            'name' => 'Cindermaw',
            'title' => 'The Ancient Dragon',
            'difficulty' => 'Boss',
            'description' => 'An ancient dragon who hoards dice.',
            'point_target' => 7000,
        ],
        17 => [
            'name' => 'Colossus Rex',
            'title' => 'The Unstoppable Titan',
            'difficulty' => 'Boss',
            'description' => 'Inevitable. Unstoppable. Patient.',
            'point_target' => 7500,
        ],
        18 => [
            'name' => 'The Recursion',
            'title' => 'The Infinite Champion',
            'difficulty' => 'Boss',
            'description' => 'Has won this gauntlet a million times.',
            'point_target' => 8000,
        ],
        19 => [
            'name' => 'Fortuna\'s Chosen',
            'title' => 'The Luck Demigod',
            'difficulty' => 'Boss',
            'description' => 'Blessed by the goddess of luck herself.',
            'point_target' => 9000,
        ],
        20 => [
            'name' => 'OMEGA PRIME',
            'title' => 'THE FINAL BOSS',
            'difficulty' => 'LEGENDARY',
            'description' => 'Perfection incarnate. The ultimate test.',
            'point_target' => 10000,
        ],
    ];

    if ($botNumber !== null) {
        return isset($bots[$botNumber]) ? $bots[$botNumber] : null;
    }
    return $bots;
}

/**
 * Get the point target for a specific bot
 */
function Challenge_GetBotPointTarget($botNumber) {
    $bot = Challenge_GetBotConfig($botNumber);
    return $bot ? $bot['point_target'] : 3000;
}

/**
 * Get bot info formatted for the challenge lobby display
 */
function Challenge_GetBotListForLobby($furthestBotReached = 0) {
    $bots = Challenge_GetBotConfig();
    $result = [];

    foreach ($bots as $num => $bot) {
        // Show full info for bots up to furthest + 3, hide later bots
        if ($num <= $furthestBotReached + 3 || $num <= 5) {
            $result[] = [
                'bot_number' => $num,
                'bot_name' => $bot['name'],
                'title' => $bot['title'],
                'difficulty' => $bot['difficulty'],
                'target_score' => $bot['point_target'],
                'description' => $bot['description'],
            ];
        } else {
            $result[] = [
                'bot_number' => $num,
                'bot_name' => '???',
                'title' => '???',
                'difficulty' => '???',
                'target_score' => '???',
                'description' => 'Unknown challenger awaits...',
            ];
        }
    }

    return $result;
}
