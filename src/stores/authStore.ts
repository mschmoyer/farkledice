import { create } from 'zustand';

interface AuthState {
  userId: string | null;
  username: string | null;
  email: string | null;
  picture: string | null;
  isAuthenticated: boolean;
  actions: {
    setUser: (user: { id: string; username: string; email?: string; picture?: string }) => void;
    syncAuth0User: (auth0User: any) => void;
    clearUser: () => void;
  };
}

export const useAuthStore = create<AuthState>((set) => ({
  userId: null,
  username: null,
  email: null,
  picture: null,
  isAuthenticated: false,
  actions: {
    setUser: (user) => set({
      userId: user.id,
      username: user.username,
      email: user.email || null,
      picture: user.picture || null,
      isAuthenticated: true,
    }),
    syncAuth0User: (auth0User) => {
      if (auth0User) {
        set({
          userId: auth0User.sub,
          username: auth0User.name || auth0User.nickname || auth0User.email?.split('@')[0] || 'User',
          email: auth0User.email || null,
          picture: auth0User.picture || null,
          isAuthenticated: true,
        });
      }
    },
    clearUser: () => set({
      userId: null,
      username: null,
      email: null,
      picture: null,
      isAuthenticated: false,
    }),
  },
}));
