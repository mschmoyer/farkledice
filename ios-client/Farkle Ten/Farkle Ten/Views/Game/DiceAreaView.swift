//
//  DiceAreaView.swift
//  Farkle Ten
//
//  Displays 6 dice in a 2x3 grid layout
//

import SwiftUI

struct DiceAreaView: View {
    @ObservedObject var gameState: GameState

    // Grid layout: 2 rows of 3 dice
    private let columns = [
        GridItem(.flexible(), spacing: 12),
        GridItem(.flexible(), spacing: 12),
        GridItem(.flexible(), spacing: 12)
    ]

    var body: some View {
        VStack(spacing: 16) {
            // Turn indicator
            HStack {
                if gameState.isMyTurn {
                    Text("Your Turn")
                        .font(FarkleTypography.subtitle)
                        .foregroundColor(FarkleColors.gold)
                } else {
                    Text("Waiting for \(gameState.currentPlayer?.username ?? "opponent")...")
                        .font(FarkleTypography.caption)
                        .foregroundColor(FarkleColors.textSecondary)
                }

                Spacer()

                // Round indicator
                if gameState.gameMode == .tenRound {
                    Text("Round \(gameState.currentRound)/10")
                        .font(FarkleTypography.caption)
                        .foregroundColor(FarkleColors.textSecondary)
                }
            }

            // Dice grid
            LazyVGrid(columns: columns, spacing: 12) {
                ForEach(gameState.dice) { die in
                    DieView(
                        die: die,
                        isRolling: gameState.isRolling,
                        isSelectable: gameState.isMyTurn && gameState.currentState == .rolled,
                        onTap: {
                            gameState.toggleDieSaved(at: die.id)
                        }
                    )
                }
            }
            .padding(.horizontal, 20)
            .padding(.vertical, 16)
            .background(
                RoundedRectangle(cornerRadius: 16)
                    .fill(Color.black.opacity(0.2))
                    .shadow(color: .black.opacity(0.2), radius: 4, x: 0, y: 2)
            )

            // Dice remaining indicator
            if gameState.currentState == .rolled && gameState.isMyTurn {
                HStack(spacing: 8) {
                    if gameState.dice.savedCount > 0 {
                        Text("\(gameState.dice.savedCount) dice saved")
                            .font(FarkleTypography.micro)
                            .foregroundColor(Color(hex: "#9B59B6"))

                        Text("•")
                            .foregroundColor(FarkleColors.textMuted)
                    }

                    Text("\(gameState.diceRemaining) dice to roll")
                        .font(FarkleTypography.micro)
                        .foregroundColor(FarkleColors.textSecondary)

                    if gameState.isHotDice {
                        Text("HOT DICE!")
                            .font(FarkleTypography.micro)
                            .fontWeight(.bold)
                            .foregroundColor(FarkleColors.gameYourTurn)
                    }
                }
            }
        }
    }
}

#Preview {
    ZStack {
        FeltBackground()

        DiceAreaView(gameState: {
            let state = GameState()
            // Set up preview state
            return state
        }())
        .padding()
    }
}
