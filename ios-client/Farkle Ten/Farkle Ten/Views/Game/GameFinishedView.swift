//
//  GameFinishedView.swift
//  Farkle Ten
//
//  End game summary showing winner and final scores
//

import SwiftUI

struct GameFinishedView: View {
    @ObservedObject var gameState: GameState
    let onDismiss: () -> Void

    @State private var showContent: Bool = false
    @State private var trophyScale: CGFloat = 0.1
    @State private var confettiOpacity: Double = 0

    private var isWinner: Bool {
        gameState.winningPlayer == gameState.myPlayerId
    }

    private var winnerPlayer: GamePlayer? {
        gameState.players.first { $0.playerId == gameState.winningPlayer }
    }

    var body: some View {
        VStack(spacing: 24) {
            Spacer()

            // Trophy or result icon
            ZStack {
                // Glow effect
                if isWinner {
                    Circle()
                        .fill(
                            RadialGradient(
                                colors: [FarkleColors.gold.opacity(0.3), .clear],
                                center: .center,
                                startRadius: 20,
                                endRadius: 100
                            )
                        )
                        .frame(width: 200, height: 200)
                        .opacity(confettiOpacity)
                }

                Image(systemName: isWinner ? "trophy.fill" : "flag.fill")
                    .font(.system(size: 80))
                    .foregroundColor(isWinner ? FarkleColors.gold : FarkleColors.textSecondary)
                    .scaleEffect(trophyScale)
                    .shadow(color: isWinner ? FarkleColors.gold.opacity(0.5) : .clear, radius: 10)
            }

            // Result text
            VStack(spacing: 8) {
                Text(isWinner ? "Victory!" : "Game Over")
                    .font(.system(size: 40, weight: .bold, design: .rounded))
                    .foregroundColor(isWinner ? FarkleColors.gold : .white)

                if let winner = winnerPlayer {
                    Text("\(winner.username) wins!")
                        .font(FarkleTypography.subtitle)
                        .foregroundColor(FarkleColors.textSecondary)
                }
            }
            .opacity(showContent ? 1 : 0)

            // Final scores
            VStack(spacing: 12) {
                Text("Final Scores")
                    .font(FarkleTypography.caption)
                    .foregroundColor(FarkleColors.textMuted)
                    .textCase(.uppercase)
                    .tracking(2)

                ForEach(sortedPlayers) { player in
                    PlayerScoreRow(
                        player: player,
                        isWinner: player.playerId == gameState.winningPlayer,
                        isMyPlayer: player.playerId == gameState.myPlayerId,
                        gameMode: gameState.gameMode
                    )
                }
            }
            .padding()
            .background(Color.black.opacity(0.3))
            .cornerRadius(16)
            .opacity(showContent ? 1 : 0)

            Spacer()

            // Return button
            FarkleButton("Return to Lobby", style: .primary) {
                onDismiss()
            }
            .opacity(showContent ? 1 : 0)

            Spacer()
                .frame(height: 32)
        }
        .padding(.horizontal, 24)
        .onAppear {
            animateIn()
        }
    }

    private var sortedPlayers: [GamePlayer] {
        gameState.players.sorted { p1, p2 in
            // Winner first
            if p1.playerId == gameState.winningPlayer { return true }
            if p2.playerId == gameState.winningPlayer { return false }
            // Then by score
            return p1.totalScore > p2.totalScore
        }
    }

    private func animateIn() {
        // Trophy animation
        withAnimation(.spring(response: 0.5, dampingFraction: 0.6).delay(0.2)) {
            trophyScale = 1.0
        }

        // Glow animation
        withAnimation(.easeIn(duration: 0.5).delay(0.4)) {
            confettiOpacity = 1.0
        }

        // Content fade in
        withAnimation(.easeIn(duration: 0.4).delay(0.5)) {
            showContent = true
        }
    }
}

// MARK: - Player Score Row

struct PlayerScoreRow: View {
    let player: GamePlayer
    let isWinner: Bool
    let isMyPlayer: Bool
    let gameMode: GameMode

    var body: some View {
        HStack {
            // Rank indicator
            if isWinner {
                Image(systemName: "crown.fill")
                    .foregroundColor(FarkleColors.gold)
                    .frame(width: 24)
            } else {
                Text("")
                    .frame(width: 24)
            }

            // Player info
            HStack(spacing: 8) {
                // Level badge
                Text("\(player.playerLevel)")
                    .font(.system(size: 10, weight: .bold))
                    .foregroundColor(.white)
                    .frame(width: 20, height: 20)
                    .background(player.levelBadgeColor)
                    .clipShape(Circle())

                Text(player.username)
                    .font(FarkleTypography.body)
                    .fontWeight(isMyPlayer ? .semibold : .regular)
                    .foregroundColor(isMyPlayer ? FarkleColors.gold : .white)

                if player.isBot {
                    Image(systemName: "cpu")
                        .font(.system(size: 12))
                        .foregroundColor(FarkleColors.textMuted)
                }
            }

            Spacer()

            // Score
            Text("\(displayScore)")
                .font(FarkleTypography.subtitle)
                .fontWeight(.bold)
                .foregroundColor(isWinner ? FarkleColors.gold : .white)
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 10)
        .background(
            isWinner ? FarkleColors.gold.opacity(0.1) : Color.clear
        )
        .cornerRadius(8)
    }

    private var displayScore: Int {
        if gameMode == .tenRound {
            return player.totalScore
        }
        return player.playerscore
    }
}

#Preview {
    ZStack {
        FeltBackground()

        GameFinishedView(gameState: {
            let state = GameState()
            // Mock finished state
            return state
        }()) {
            // Dismiss
        }
    }
}
