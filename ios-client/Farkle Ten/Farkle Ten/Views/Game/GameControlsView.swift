//
//  GameControlsView.swift
//  Farkle Ten
//
//  Roll and Bank button controls
//

import SwiftUI

struct GameControlsView: View {
    @ObservedObject var gameState: GameState

    var body: some View {
        VStack(spacing: 12) {
            // Selection score preview
            if gameState.turnScore > 0 && gameState.currentState == .rolled {
                Text("+\(gameState.turnScore) points")
                    .font(FarkleTypography.subtitle)
                    .foregroundColor(FarkleColors.gold)
                    .padding(.horizontal, 16)
                    .padding(.vertical, 8)
                    .background(Color.black.opacity(0.3))
                    .cornerRadius(8)
            }

            // Button row
            HStack(spacing: 16) {
                // Roll button
                if shouldShowRollButton {
                    Button(action: {
                        Task {
                            if gameState.currentSet == 0 {
                                await gameState.initialRoll()
                            } else {
                                await gameState.rollDice()
                            }
                        }
                    }) {
                        HStack(spacing: 8) {
                            Image(systemName: "dice.fill")
                                .font(.system(size: 20))

                            Text(rollButtonText)
                                .font(FarkleTypography.button)
                        }
                        .foregroundColor(.white)
                        .frame(maxWidth: .infinity)
                        .frame(height: 50)
                        .background(rollButtonColor)
                        .cornerRadius(12)
                        .shadow(color: rollButtonColor.opacity(0.5), radius: 4, x: 0, y: 2)
                    }
                    .disabled(!isRollEnabled)
                    .opacity(isRollEnabled ? 1.0 : 0.5)
                }

                // Bank button
                if shouldShowBankButton {
                    Button(action: {
                        Task {
                            await gameState.bankScore()
                        }
                    }) {
                        HStack(spacing: 8) {
                            Image(systemName: "banknote.fill")
                                .font(.system(size: 20))

                            Text("Bank \(gameState.roundScore + gameState.turnScore)")
                                .font(FarkleTypography.button)
                        }
                        .foregroundColor(.white)
                        .frame(maxWidth: .infinity)
                        .frame(height: 50)
                        .background(FarkleColors.buttonGreen)
                        .cornerRadius(12)
                        .shadow(color: FarkleColors.buttonGreen.opacity(0.5), radius: 4, x: 0, y: 2)
                    }
                    .disabled(!gameState.canBank)
                    .opacity(gameState.canBank ? 1.0 : 0.5)
                }
            }
            .padding(.horizontal, 4)

            // Status message
            if !gameState.isMyTurn && !gameState.isGameFinished {
                Text("Waiting for opponent...")
                    .font(FarkleTypography.caption)
                    .foregroundColor(FarkleColors.textSecondary)
            }
        }
        .padding(.vertical, 8)
    }

    // MARK: - Computed Properties

    private var shouldShowRollButton: Bool {
        gameState.isMyTurn && !gameState.isGameFinished
    }

    private var shouldShowBankButton: Bool {
        gameState.isMyTurn &&
        !gameState.isGameFinished &&
        gameState.currentState == .rolled &&
        (gameState.roundScore + gameState.turnScore) > 0
    }

    private var isRollEnabled: Bool {
        if gameState.isRolling {
            return false
        }

        if gameState.currentSet == 0 {
            // First roll of round - always enabled
            return gameState.isMyTurn
        }

        // Must have selected scoring dice to roll again
        return gameState.canRoll || (gameState.isHotDice && gameState.isMyTurn)
    }

    private var rollButtonText: String {
        if gameState.isRolling {
            return "Rolling..."
        }

        if gameState.currentSet == 0 {
            return "Roll Dice"
        }

        if gameState.isHotDice {
            return "Hot Dice! Roll"
        }

        if gameState.dice.savedCount > 0 {
            return "Roll \(gameState.diceRemaining)"
        }

        return "Select dice to roll"
    }

    private var rollButtonColor: Color {
        if gameState.isHotDice {
            return FarkleColors.gameYourTurn
        }

        if gameState.currentSet == 0 {
            return FarkleColors.buttonBlue
        }

        return FarkleColors.buttonBlue
    }
}

#Preview {
    ZStack {
        FeltBackground()

        VStack {
            Spacer()
            GameControlsView(gameState: GameState())
                .padding()
        }
    }
}
