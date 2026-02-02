<?php
namespace Tests\Unit;

use Tests\TestCase;

// Include the dice scoring file
require_once __DIR__ . '/../../wwwroot/farkleDiceScoring.php';

/**
 * Tests for the dice scoring function in farkleDiceScoring.php
 *
 * Scoring Rules (as implemented):
 * - Single 1: 100 points each
 * - Single 5: 50 points each
 * - Three of a kind: Face value x 100 (except 1s = 1000)
 * - Four of a kind: Face value x 200 (except 1s = 2000)
 * - Five of a kind: Face value x 300 (except 1s = 3000)
 * - Six of a kind: Face value x 400 (except 1s = 4000)
 *   BUT: Six of a kind triggers "two triplets" bonus (2500) if raw score < 2500
 * - Straight (1-2-3-4-5-6): 1000 points
 * - Three pairs: 750 points (if raw score < 750)
 * - Two triplets: 2500 points (if raw score < 2500)
 *
 * Special bonuses only apply when they increase the score.
 */
class DiceScoringTest extends TestCase
{
    /**
     * @dataProvider singleDiceProvider
     */
    public function testSingleScoringDice(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function singleDiceProvider(): array
    {
        return [
            'single 1' => [[1, 0, 0, 0, 0, 0], 100],
            'two 1s' => [[1, 1, 0, 0, 0, 0], 200],
            'single 5' => [[5, 0, 0, 0, 0, 0], 50],
            'two 5s' => [[5, 5, 0, 0, 0, 0], 100],
            'one 1 and one 5' => [[1, 5, 0, 0, 0, 0], 150],
            'two 1s and one 5' => [[1, 1, 5, 0, 0, 0], 250],
            'one 1 and two 5s' => [[1, 5, 5, 0, 0, 0], 200],
        ];
    }

    /**
     * @dataProvider threeOfAKindProvider
     */
    public function testThreeOfAKind(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function threeOfAKindProvider(): array
    {
        return [
            'three 1s' => [[1, 1, 1, 0, 0, 0], 1000],
            'three 2s' => [[2, 2, 2, 0, 0, 0], 200],
            'three 3s' => [[3, 3, 3, 0, 0, 0], 300],
            'three 4s' => [[4, 4, 4, 0, 0, 0], 400],
            'three 5s' => [[5, 5, 5, 0, 0, 0], 500],
            'three 6s' => [[6, 6, 6, 0, 0, 0], 600],
        ];
    }

    /**
     * @dataProvider fourOfAKindProvider
     */
    public function testFourOfAKind(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function fourOfAKindProvider(): array
    {
        return [
            'four 1s' => [[1, 1, 1, 1, 0, 0], 2000],
            'four 2s' => [[2, 2, 2, 2, 0, 0], 400],
            'four 3s' => [[3, 3, 3, 3, 0, 0], 600],
            'four 4s' => [[4, 4, 4, 4, 0, 0], 800],
            'four 5s' => [[5, 5, 5, 5, 0, 0], 1000],
            'four 6s' => [[6, 6, 6, 6, 0, 0], 1200],
        ];
    }

    /**
     * @dataProvider fiveOfAKindProvider
     */
    public function testFiveOfAKind(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function fiveOfAKindProvider(): array
    {
        return [
            'five 1s' => [[1, 1, 1, 1, 1, 0], 3000],
            'five 2s' => [[2, 2, 2, 2, 2, 0], 600],
            'five 3s' => [[3, 3, 3, 3, 3, 0], 900],
            'five 4s' => [[4, 4, 4, 4, 4, 0], 1200],
            'five 5s' => [[5, 5, 5, 5, 5, 0], 1500],
            'five 6s' => [[6, 6, 6, 6, 6, 0], 1800],
        ];
    }

    /**
     * @dataProvider sixOfAKindProvider
     */
    public function testSixOfAKind(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function sixOfAKindProvider(): array
    {
        // NOTE: Six of a kind (except 1s) is treated as "two triplets" = 2500
        // when the raw score would be less than 2500.
        // Six 1s = 4000 (raw score), stays 4000 since 4000 > 2500
        // Six 2s = 800 (raw), becomes 2500 (two triplets)
        // Six 5s = 2000 (raw), becomes 2500 (two triplets)
        // Six 6s = 2400 (raw), becomes 2500 (two triplets)
        return [
            'six 1s' => [[1, 1, 1, 1, 1, 1], 4000],
            'six 2s (two triplets bonus)' => [[2, 2, 2, 2, 2, 2], 2500],
            'six 3s (two triplets bonus)' => [[3, 3, 3, 3, 3, 3], 2500],
            'six 4s (two triplets bonus)' => [[4, 4, 4, 4, 4, 4], 2500],
            'six 5s (two triplets bonus)' => [[5, 5, 5, 5, 5, 5], 2500],
            'six 6s (two triplets bonus)' => [[6, 6, 6, 6, 6, 6], 2500],
        ];
    }

    public function testStraight(): void
    {
        $dice = [1, 2, 3, 4, 5, 6];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1000, $score);
    }

    public function testStraightDifferentOrder(): void
    {
        $dice = [6, 5, 4, 3, 2, 1];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1000, $score);
    }

    public function testStraightRandomOrder(): void
    {
        $dice = [3, 1, 4, 6, 2, 5];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1000, $score);
    }

    public function testThreePairs(): void
    {
        $dice = [2, 2, 3, 3, 4, 4];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(750, $score);
    }

    public function testThreePairsWithOnes(): void
    {
        $dice = [1, 1, 3, 3, 6, 6];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(750, $score);
    }

    public function testThreePairsWithFives(): void
    {
        $dice = [2, 2, 5, 5, 6, 6];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(750, $score);
    }

    public function testThreePairsWithOnesAndFives(): void
    {
        $dice = [1, 1, 5, 5, 6, 6];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(750, $score);
    }

    public function testTwoTriplets(): void
    {
        $dice = [2, 2, 2, 3, 3, 3];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2500, $score);
    }

    public function testTwoTripletsWithOnes(): void
    {
        $dice = [1, 1, 1, 4, 4, 4];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2500, $score);
    }

    public function testTwoTripletsWithFives(): void
    {
        $dice = [5, 5, 5, 3, 3, 3];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2500, $score);
    }

    public function testTwoTripletsOnesAndFives(): void
    {
        // Three 1s (1000) + Three 5s (500) = 1500, but two triplets = 2500
        $dice = [1, 1, 1, 5, 5, 5];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2500, $score);
    }

    /**
     * Test combinations of scoring dice
     */
    public function testThreeOfAKindPlusOne(): void
    {
        // Three 2s (200) plus a 1 (100) = 300
        $dice = [2, 2, 2, 1, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(300, $score);
    }

    public function testThreeOfAKindPlusFive(): void
    {
        // Three 3s (300) plus a 5 (50) = 350
        $dice = [3, 3, 3, 5, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(350, $score);
    }

    public function testThreeOnesPlus5(): void
    {
        // Three 1s (1000) plus a 5 (50) = 1050
        $dice = [1, 1, 1, 5, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1050, $score);
    }

    public function testFourOfAKindPlusOne(): void
    {
        // Four 2s (400) plus a 1 (100) = 500
        $dice = [2, 2, 2, 2, 1, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(500, $score);
    }

    public function testFourOfAKindPlusFive(): void
    {
        // Four 3s (600) plus a 5 (50) = 650
        $dice = [3, 3, 3, 3, 5, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(650, $score);
    }

    public function testFourOfAKindPlusOneAndFive(): void
    {
        // Four 2s (400) plus a 1 (100) plus a 5 (50) = 550
        $dice = [2, 2, 2, 2, 1, 5];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(550, $score);
    }

    public function testFiveOfAKindPlusOne(): void
    {
        // Five 2s (600) plus a 1 (100) = 700
        $dice = [2, 2, 2, 2, 2, 1];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(700, $score);
    }

    public function testFiveOfAKindPlusFive(): void
    {
        // Five 3s (900) plus a 5 (50) = 950
        $dice = [3, 3, 3, 3, 3, 5];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(950, $score);
    }

    /**
     * Test special case: four of a kind vs three pairs
     * Per the code comment: [3][5][5][5][5][3] can be 4x5s (1000) or 3 pairs (750)
     * The code prefers three pairs when it enables a reroll, but only if score < 750
     * In this case 4x5s = 1000 > 750, so four of a kind wins
     */
    public function testFourOfAKindVsThreePairs(): void
    {
        // Four 5s (1000) + pair of 3s (0) = 1000
        // Three pairs would be 750, but 1000 > 750
        $dice = [3, 5, 5, 5, 5, 3];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1000, $score);
    }

    /**
     * Test edge case: four 1s plus pair (should be evaluated correctly)
     * Four 1s = 2000, three pairs = 750, so 2000 wins
     */
    public function testFourOnesPlusPair(): void
    {
        // Four 1s (2000) + pair of 3s (0) = 2000
        $dice = [1, 1, 1, 1, 3, 3];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2000, $score);
    }

    /**
     * Special case from code comment: [1][1][1][1][5][5]
     * Four 1s (2000) + two 5s (100) = 2100
     * Three pairs would only be 750
     */
    public function testFourOnesPlusTwoFives(): void
    {
        $dice = [1, 1, 1, 1, 5, 5];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(2100, $score);
    }

    /**
     * Test farkle (no scoring dice) - these return 0
     */
    public function testNoScoringDiceReturnsZero(): void
    {
        // All non-scoring dice (2,3,4,6 individually don't score)
        $dice = [2, 3, 4, 6, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(0, $score);
    }

    public function testTwoNonScoringDiceReturnsZero(): void
    {
        $dice = [2, 2, 0, 0, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(0, $score);
    }

    /**
     * Test empty/zeroed array
     */
    public function testAllZerosReturnsZero(): void
    {
        $dice = [0, 0, 0, 0, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(0, $score);
    }

    /**
     * Test dice position independence - same dice in different positions should score the same
     */
    public function testDicePositionIndependence(): void
    {
        // Three 1s in different positions should all score 1000
        $this->assertEquals(farkleScoreDice([1, 1, 1, 0, 0, 0], 0), 1000);
        $this->assertEquals(farkleScoreDice([0, 1, 1, 1, 0, 0], 0), 1000);
        $this->assertEquals(farkleScoreDice([0, 0, 0, 1, 1, 1], 0), 1000);
        $this->assertEquals(farkleScoreDice([1, 0, 1, 0, 1, 0], 0), 1000);
    }

    /**
     * Test invalid input handling
     */
    public function testEmptyArrayReturnsZero(): void
    {
        $score = farkleScoreDice([], 0);
        $this->assertEquals(0, $score);
    }
}
