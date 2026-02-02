<?php
namespace Tests\Fixtures;

/**
 * Common dice roll scenarios for testing
 */
class DiceScenarios
{
    /**
     * Dice combinations that score (not farkles)
     */
    public static function scoringRolls(): array
    {
        return [
            'single_one' => [
                'dice' => [1, 2, 3, 4, 6, 6],
                'scoring_dice' => [1, 0, 0, 0, 0, 0],
                'score' => 100,
            ],
            'single_five' => [
                'dice' => [5, 2, 3, 4, 6, 6],
                'scoring_dice' => [5, 0, 0, 0, 0, 0],
                'score' => 50,
            ],
            'three_twos' => [
                'dice' => [2, 2, 2, 3, 4, 6],
                'scoring_dice' => [2, 2, 2, 0, 0, 0],
                'score' => 200,
            ],
            'straight' => [
                'dice' => [1, 2, 3, 4, 5, 6],
                'scoring_dice' => [1, 2, 3, 4, 5, 6],
                'score' => 1000,
            ],
        ];
    }

    /**
     * Dice combinations that don't score (farkles)
     */
    public static function farkleRolls(): array
    {
        return [
            'no_scoring' => [2, 3, 4, 6, 2, 4],
            'pairs_only' => [2, 2, 3, 3, 4, 6],
            'all_same_non_scoring' => [2, 3, 4, 4, 6, 3],
        ];
    }
}
