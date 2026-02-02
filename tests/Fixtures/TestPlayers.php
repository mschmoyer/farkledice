<?php
namespace Tests\Fixtures;

/**
 * Test player fixture data
 */
class TestPlayers
{
    public const DEFAULT_PASSWORD = 'testpass';

    /**
     * Get test player data
     *
     * @return array Array of player data arrays
     */
    public static function getPlayers(): array
    {
        return [
            [
                'username' => 'test_player_1',
                'email' => 'player1@test.com',
                'password' => self::DEFAULT_PASSWORD,
            ],
            [
                'username' => 'test_player_2',
                'email' => 'player2@test.com',
                'password' => self::DEFAULT_PASSWORD,
            ],
            [
                'username' => 'test_player_3',
                'email' => 'player3@test.com',
                'password' => self::DEFAULT_PASSWORD,
            ],
        ];
    }
}
