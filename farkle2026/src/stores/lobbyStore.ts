import { create } from 'zustand';

interface Game {
  id: string;
  mode: 'ten_round' | 'standard';
  type: 'random' | 'friend' | 'practice';
  status: 'waiting' | 'active' | 'finished';
  currentTurn: number;
  opponentName?: string;
  opponentId?: string;
  isMyTurn: boolean;
  createdAt: string;
  updatedAt: string;
}

interface LobbyState {
  games: Game[];
  isLoading: boolean;
  lastRefresh: Date | null;
  actions: {
    setGames: (games: Game[]) => void;
    addGame: (game: Game) => void;
    updateGame: (gameId: string, updates: Partial<Game>) => void;
    removeGame: (gameId: string) => void;
    setLoading: (loading: boolean) => void;
    refresh: () => void;
  };
}

export const useLobbyStore = create<LobbyState>((set) => ({
  games: [],
  isLoading: false,
  lastRefresh: null,
  actions: {
    setGames: (games) => set({ games, lastRefresh: new Date() }),
    addGame: (game) => set((state) => ({ games: [...state.games, game] })),
    updateGame: (gameId, updates) => set((state) => ({
      games: state.games.map((game) =>
        game.id === gameId ? { ...game, ...updates } : game
      ),
    })),
    removeGame: (gameId) => set((state) => ({
      games: state.games.filter((game) => game.id !== gameId),
    })),
    setLoading: (loading) => set({ isLoading: loading }),
    refresh: () => set({ lastRefresh: new Date() }),
  },
}));
