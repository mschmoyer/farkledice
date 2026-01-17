import { create } from 'zustand';

interface Achievement {
  id: string;
  name: string;
  description: string;
  icon: string;
  unlockedAt?: string;
}

interface Stats {
  gamesPlayed: number;
  gamesWon: number;
  winRate: number;
  highScore: number;
  totalScore: number;
  averageScore: number;
  longestWinStreak: number;
  currentWinStreak: number;
}

interface ProfileState {
  username: string | null;
  level: number;
  xp: number;
  xpRequired: number;
  title: string | null;
  achievementScore: number;
  stats: Stats | null;
  achievements: Achievement[];
  isLoading: boolean;
  actions: {
    setProfile: (profile: {
      username: string;
      level: number;
      xp: number;
      xpRequired: number;
      title?: string;
      achievementScore: number;
    }) => void;
    setStats: (stats: Stats) => void;
    setAchievements: (achievements: Achievement[]) => void;
    addXp: (amount: number) => void;
    levelUp: (newLevel: number, newXpRequired: number) => void;
    setLoading: (loading: boolean) => void;
  };
}

export const useProfileStore = create<ProfileState>((set) => ({
  username: null,
  level: 1,
  xp: 0,
  xpRequired: 100,
  title: null,
  achievementScore: 0,
  stats: null,
  achievements: [],
  isLoading: false,
  actions: {
    setProfile: (profile) => set({
      username: profile.username,
      level: profile.level,
      xp: profile.xp,
      xpRequired: profile.xpRequired,
      title: profile.title || null,
      achievementScore: profile.achievementScore,
    }),
    setStats: (stats) => set({ stats }),
    setAchievements: (achievements) => set({ achievements }),
    addXp: (amount) => set((state) => ({ xp: state.xp + amount })),
    levelUp: (newLevel, newXpRequired) => set({
      level: newLevel,
      xp: 0,
      xpRequired: newXpRequired
    }),
    setLoading: (loading) => set({ isLoading: loading }),
  },
}));
