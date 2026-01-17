import { create } from 'zustand';

interface DiceState {
  value: number;
  isSelected: boolean;
  isScored: boolean;
}

interface Player {
  id: string;
  username: string;
  picture?: string;
  score: number;
}

interface GameState {
  gameId: string | null;
  mode: 'ten_round' | 'standard' | null;
  players: Player[];
  currentPlayerId: string | null;
  currentRound: number;
  dice: DiceState[];
  turnScore: number;
  canRoll: boolean;
  canScore: boolean;
  gameStatus: 'waiting' | 'active' | 'finished' | null;
  actions: {
    initializeGame: (gameId: string, mode: 'ten_round' | 'standard', players: Player[]) => void;
    setDice: (dice: DiceState[]) => void;
    selectDie: (index: number) => void;
    setTurnScore: (score: number) => void;
    setCanRoll: (canRoll: boolean) => void;
    setCanScore: (canScore: boolean) => void;
    setCurrentPlayer: (playerId: string) => void;
    updatePlayerScore: (playerId: string, score: number) => void;
    nextRound: () => void;
    resetGame: () => void;
  };
}

export const useGameStore = create<GameState>((set) => ({
  gameId: null,
  mode: null,
  players: [],
  currentPlayerId: null,
  currentRound: 1,
  dice: [],
  turnScore: 0,
  canRoll: true,
  canScore: false,
  gameStatus: null,
  actions: {
    initializeGame: (gameId, mode, players) => set({
      gameId,
      mode,
      players,
      currentPlayerId: players[0]?.id || null,
      currentRound: 1,
      dice: [],
      turnScore: 0,
      canRoll: true,
      canScore: false,
      gameStatus: 'active',
    }),
    setDice: (dice) => set({ dice }),
    selectDie: (index) => set((state) => ({
      dice: state.dice.map((die, i) =>
        i === index ? { ...die, isSelected: !die.isSelected } : die
      ),
    })),
    setTurnScore: (score) => set({ turnScore: score }),
    setCanRoll: (canRoll) => set({ canRoll }),
    setCanScore: (canScore) => set({ canScore }),
    setCurrentPlayer: (playerId) => set({ currentPlayerId: playerId }),
    updatePlayerScore: (playerId, score) => set((state) => ({
      players: state.players.map((player) =>
        player.id === playerId ? { ...player, score } : player
      ),
    })),
    nextRound: () => set((state) => ({ currentRound: state.currentRound + 1 })),
    resetGame: () => set({
      gameId: null,
      mode: null,
      players: [],
      currentPlayerId: null,
      currentRound: 1,
      dice: [],
      turnScore: 0,
      canRoll: true,
      canScore: false,
      gameStatus: null,
    }),
  },
}));
