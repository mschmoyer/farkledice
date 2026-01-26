//
//  LobbyView.swift
//  Farkle Ten
//
//  Main lobby screen
//

import SwiftUI

struct LobbyView: View {
    @EnvironmentObject var appState: AppState

    @State private var showNewGameSheet: Bool = false
    @State private var selectedGame: Game?
    @State private var navigateToGame: Bool = false

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Player info card
                    if let player = appState.currentPlayer {
                        PlayerCardView(player: player)

                        // XP progress bar
                        XPProgressBar(
                            currentXP: player.xp,
                            xpToLevel: player.xpToLevel
                        )
                    }

                    // Menu buttons
                    LobbyMenuView(
                        onNewGame: { showNewGameSheet = true },
                        onTournament: { /* TODO */ },
                        onProfile: { /* TODO */ },
                        onFriends: { /* TODO */ },
                        onLeaderboard: { /* TODO */ },
                        onInstructions: { /* TODO */ },
                        onLogout: { appState.logout() }
                    )

                    Divider()
                        .background(Color.white.opacity(0.2))
                        .padding(.vertical, 8)

                    // Games list
                    if let lobby = appState.lobbyData {
                        GamesListView(games: lobby.games) { game in
                            selectedGame = game
                            navigateToGame = true
                        }

                        // Active friends section
                        if !lobby.activeFriends.isEmpty {
                            ActiveFriendsSection(friends: lobby.activeFriends)
                        }
                    }

                    Spacer()
                        .frame(height: 40)
                }
                .padding(.horizontal, 16)
                .padding(.top, 16)
            }
            .feltBackground()
            .navigationBarHidden(true)
            .refreshable {
                appState.refreshLobby()
            }
            .navigationDestination(isPresented: $navigateToGame) {
                if let game = selectedGame {
                    GameView(gameId: game.gameid)
                }
            }
        }
        .onAppear {
            appState.startPolling()
        }
        .onDisappear {
            appState.stopPolling()
        }
        .sheet(isPresented: $showNewGameSheet) {
            NewGameSheet()
        }
    }
}

// MARK: - Active Friends Section

struct ActiveFriendsSection: View {
    let friends: [Friend]

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Image(systemName: "person.2.fill")
                    .foregroundColor(FarkleColors.gold)

                Text("Friends Online")
                    .font(FarkleTypography.subtitle)
                    .foregroundColor(.white)

                Spacer()

                Text("\(friends.count)")
                    .font(FarkleTypography.caption)
                    .foregroundColor(FarkleColors.textMuted)
            }
            .padding(.horizontal, 4)

            ScrollView(.horizontal, showsIndicators: false) {
                HStack(spacing: 12) {
                    ForEach(friends) { friend in
                        FriendCard(friend: friend)
                    }
                }
            }
        }
    }
}

// MARK: - Friend Card

struct FriendCard: View {
    let friend: Friend

    var body: some View {
        VStack(spacing: 8) {
            // Avatar placeholder
            Circle()
                .fill(Color.gray.opacity(0.3))
                .frame(width: 50, height: 50)
                .overlay(
                    Text(String(friend.username.prefix(1)).uppercased())
                        .font(FarkleTypography.subtitle)
                        .foregroundColor(.white)
                )

            Text(friend.username)
                .font(FarkleTypography.caption)
                .foregroundColor(.white)
                .lineLimit(1)

            if friend.isInGame {
                Text("In Game")
                    .font(FarkleTypography.micro)
                    .foregroundColor(FarkleColors.gold)
            } else {
                FarkleSmallButton("Play", style: .primary) {
                    // TODO: Start game with friend
                }
            }
        }
        .padding(12)
        .background(Color.black.opacity(0.3))
        .cornerRadius(12)
        .frame(width: 100)
    }
}

// MARK: - New Game Sheet (Placeholder)

struct NewGameSheet: View {
    @Environment(\.dismiss) var dismiss

    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                Text("Start New Game")
                    .font(FarkleTypography.title)
                    .foregroundColor(.white)

                Text("Game creation coming soon...")
                    .foregroundColor(FarkleColors.textSecondary)

                Spacer()

                FarkleButton("Close", style: .outline) {
                    dismiss()
                }
            }
            .padding()
            .feltBackground()
            .navigationBarHidden(true)
        }
    }
}

#Preview {
    LobbyView()
        .environmentObject({
            let state = AppState()
            // Set up mock data for preview
            return state
        }())
}
