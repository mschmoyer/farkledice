//
//  LobbyMenuView.swift
//  Farkle Ten
//
//  Navigation buttons for lobby
//

import SwiftUI

struct LobbyMenuView: View {
    var onNewGame: (() -> Void)?
    var onTournament: (() -> Void)?
    var onProfile: (() -> Void)?
    var onFriends: (() -> Void)?
    var onLeaderboard: (() -> Void)?
    var onInstructions: (() -> Void)?
    var onLogout: (() -> Void)?

    var body: some View {
        VStack(spacing: 12) {
            // Primary actions
            HStack(spacing: 12) {
                FarkleButton("New Game", style: .primary) {
                    onNewGame?()
                }

                FarkleButton("Tournament", style: .secondary) {
                    onTournament?()
                }
            }

            // Secondary actions grid
            LazyVGrid(columns: [
                GridItem(.flexible()),
                GridItem(.flexible())
            ], spacing: 8) {
                MenuButton(icon: "person.circle", title: "My Profile") {
                    onProfile?()
                }

                MenuButton(icon: "person.2", title: "Friends") {
                    onFriends?()
                }

                MenuButton(icon: "chart.bar", title: "Leaderboard") {
                    onLeaderboard?()
                }

                MenuButton(icon: "questionmark.circle", title: "How to Play") {
                    onInstructions?()
                }
            }

            // Logout
            Button(action: { onLogout?() }) {
                HStack {
                    Image(systemName: "rectangle.portrait.and.arrow.right")
                        .font(.system(size: 14))
                    Text("Sign Out")
                        .font(FarkleTypography.caption)
                }
                .foregroundColor(FarkleColors.textMuted)
                .padding(.top, 8)
            }
        }
    }
}

// MARK: - Menu Button

struct MenuButton: View {
    let icon: String
    let title: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack(spacing: 8) {
                Image(systemName: icon)
                    .font(.system(size: 16))
                    .foregroundColor(FarkleColors.gold)

                Text(title)
                    .font(FarkleTypography.buttonSmall)
                    .foregroundColor(.white)

                Spacer()
            }
            .padding(.horizontal, 12)
            .padding(.vertical, 12)
            .background(Color.black.opacity(0.3))
            .cornerRadius(8)
        }
    }
}

#Preview {
    VStack {
        LobbyMenuView()
    }
    .padding()
    .feltBackground()
}
