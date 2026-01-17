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
  lastPolled: Date | null;
  isPolling: boolean;
  actions: {
    setGames: (games: Game[]) => void;
    addGame: (game: Game) => void;
    updateGame: (gameId: string, updates: Partial<Game>) => void;
    removeGame: (gameId: string) => void;
    setLoading: (loading: boolean) => void;
    setPolling: (polling: boolean) => void;
    refresh: () => void;
  };
}

export const useLobbyStore = create<LobbyState>((set) => ({
  games: [],
  isLoading: false,
  lastRefresh: null,
  lastPolled: null,
  isPolling: false,
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
    setPolling: (polling) => set({ isPolling: polling, lastPolled: polling ? new Date() : null }),
    refresh: () => set({ lastRefresh: new Date(), lastPolled: new Date() }),
  },
}));
