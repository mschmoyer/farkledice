/**
 * Mock data service for development
 * This will be replaced by real API calls in task-012
 */

import { PlayerCardProps } from '@/components/game/PlayerCard';
import { GameCardProps } from '@/components/game/GameCard';

export function getMockPlayerData(): PlayerCardProps {
  return {
    username: 'FarkleChampion',
    level: 12,
    title: 'Dice Master',
    achievementScore: 2450,
    xp: 3200,
    xpToLevel: 5000,
    picture: undefined, // will use stock image
    cardColor: 'green'
  };
}

export interface MockGameData extends Omit<GameCardProps, 'onClick'> {
  id: string | number;
}

export function getMockGames(): MockGameData[] {
  return [
    {
      id: 1,
      gameId: 1,
      opponents: ['Alice', 'Bob'],
      status: 'your-turn',
      mode: '10-round',
      currentPlayer: undefined,
      statusMessage: undefined
    },
    {
      id: 2,
      gameId: 2,
      opponents: ['Charlie'],
      status: 'not-started',
      mode: 'standard',
      currentPlayer: undefined,
      statusMessage: undefined
    },
    {
      id: 3,
      gameId: 3,
      opponents: ['Dave', 'Eve', 'Frank'],
      status: 'waiting',
      mode: '10-round',
      currentPlayer: 'Dave',
      statusMessage: undefined
    },
    {
      id: 4,
      gameId: 4,
      opponents: ['Grace'],
      status: 'finished',
      mode: 'standard',
      currentPlayer: undefined,
      statusMessage: undefined
    },
    {
      id: 5,
      gameId: 5,
      opponents: ['Henry', 'Ivy'],
      status: 'waiting',
      mode: '10-round',
      currentPlayer: 'Ivy',
      statusMessage: undefined
    }
  ];
}

export interface MockLobbyData {
  player: PlayerCardProps;
  games: MockGameData[];
  hasActiveTournament: boolean;
  isDoubleXP: boolean;
}

export function getMockLobbyData(): MockLobbyData {
  return {
    player: getMockPlayerData(),
    games: getMockGames(),
    hasActiveTournament: false,
    isDoubleXP: true // Show the double XP badge
  };
}
