//
//  GameView.swift
//  Farkle Ten
//
//  Main game container view
//

import SwiftUI

struct GameView: View {
    @Environment(\.dismiss) var dismiss
    @StateObject private var gameState = GameState()

    let gameId: Int

    var body: some View {
        ZStack {
            // Background
            FeltBackground()

            VStack(spacing: 0) {
                // Nav bar
                GameNavBar(onHome: {
                    gameState.stopPolling()
                    dismiss()
                })

                if gameState.isLoadingData {
                    // Loading state
                    Spacer()
                    ProgressView()
                        .progressViewStyle(CircularProgressViewStyle(tint: .white))
                        .scaleEffect(1.5)
                    Text("Loading game...")
                        .foregroundColor(FarkleColors.textSecondary)
                        .padding(.top, 16)
                    Spacer()
                } else if gameState.isGameFinished {
                    // Game finished view
                    GameFinishedView(gameState: gameState) {
                        gameState.stopPolling()
                        dismiss()
                    }
                } else {
                    // Main game content
                    ScrollView {
                        VStack(spacing: 16) {
                            // Player cards
                            GamePlayersSection(gameState: gameState)

                            // Round score display with fire effects
                            RoundScoreView(
                                roundScore: gameState.roundScore,
                                turnScore: gameState.turnScore,
                                fireLevel: gameState.fireLevel
                            )

                            // Dice area
                            DiceAreaView(gameState: gameState)

                            // Game controls (Roll/Bank)
                            GameControlsView(gameState: gameState)

                            // Activity log
                            if !gameState.activityLog.isEmpty {
                                ActivityLogView(entries: gameState.activityLog, currentPlayerId: gameState.myPlayerId)
                            }

                            // Bot chat
                            if gameState.isPlayingBot && !gameState.botMessages.isEmpty {
                                BotChatView(messages: gameState.botMessages)
                            }
                        }
                        .padding(.horizontal, 16)
                        .padding(.vertical, 12)
                    }
                }

                // Error message
                if let error = gameState.errorMessage {
                    Text(error)
                        .font(FarkleTypography.caption)
                        .foregroundColor(FarkleColors.gameLost)
                        .padding()
                        .background(Color.black.opacity(0.5))
                        .cornerRadius(8)
                        .padding()
                }
            }

            // Farkle overlay
            if gameState.showFarkleOverlay {
                FarkleOverlay()
            }
        }
        .navigationBarHidden(true)
        .task {
            await gameState.loadGame(gameId: gameId)
        }
        .onDisappear {
            gameState.stopPolling()
        }
    }
}

// MARK: - Game Players Section

struct GamePlayersSection: View {
    @ObservedObject var gameState: GameState

    var body: some View {
        HStack(spacing: 12) {
            ForEach(gameState.players) { player in
                GamePlayerCardView(
                    player: player,
                    isCurrentTurn: player.playerId == gameState.currentPlayerId,
                    isMyPlayer: player.playerId == gameState.myPlayerId,
                    gameMode: gameState.gameMode,
                    currentRound: gameState.currentRound
                )
            }
        }
    }
}

// MARK: - Preview

#Preview {
    GameView(gameId: 1)
}
