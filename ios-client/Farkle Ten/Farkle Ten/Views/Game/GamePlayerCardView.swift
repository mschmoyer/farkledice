//
//  GamePlayerCardView.swift
//  Farkle Ten
//
//  Player card showing avatar, name, level, and score in game context
//

import SwiftUI

struct GamePlayerCardView: View {
    let player: GamePlayer
    let isCurrentTurn: Bool
    let isMyPlayer: Bool
    let gameMode: GameMode
    let currentRound: Int

    var body: some View {
        VStack(spacing: 8) {
            // Avatar with level badge
            ZStack(alignment: .bottomTrailing) {
                // Avatar circle
                Circle()
                    .fill(avatarBackgroundColor)
                    .frame(width: 50, height: 50)
                    .overlay(
                        Text(String(player.username.prefix(1)).uppercased())
                            .font(FarkleTypography.subtitle)
                            .fontWeight(.bold)
                            .foregroundColor(.white)
                    )
                    .overlay(
                        Circle()
                            .strokeBorder(borderColor, lineWidth: isCurrentTurn ? 3 : 1)
                    )
                    .shadow(color: isCurrentTurn ? FarkleColors.gold.opacity(0.5) : .clear, radius: 8)

                // Level badge
                Text("\(player.playerLevel)")
                    .font(.system(size: 10, weight: .bold))
                    .foregroundColor(.white)
                    .frame(width: 18, height: 18)
                    .background(player.levelBadgeColor)
                    .clipShape(Circle())
                    .overlay(
                        Circle()
                            .strokeBorder(Color.white, lineWidth: 1)
                    )
                    .offset(x: 4, y: 4)
            }

            // Username
            Text(player.username)
                .font(FarkleTypography.caption)
                .fontWeight(isMyPlayer ? .semibold : .regular)
                .foregroundColor(isMyPlayer ? FarkleColors.gold : .white)
                .lineLimit(1)

            // Score
            VStack(spacing: 2) {
                Text("\(displayScore)")
                    .font(FarkleTypography.subtitle)
                    .fontWeight(.bold)
                    .foregroundColor(.white)

                // Round indicator (10-round mode)
                if gameMode == .tenRound {
                    Text("R\(player.playerround)")
                        .font(FarkleTypography.micro)
                        .foregroundColor(FarkleColors.textMuted)
                }
            }

            // Bot indicator
            if player.isBot {
                HStack(spacing: 4) {
                    Image(systemName: "cpu")
                        .font(.system(size: 10))
                    Text("BOT")
                        .font(.system(size: 9, weight: .medium))
                }
                .foregroundColor(FarkleColors.textMuted)
                .padding(.horizontal, 6)
                .padding(.vertical, 2)
                .background(Color.black.opacity(0.3))
                .cornerRadius(4)
            }
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 12)
        .padding(.horizontal, 8)
        .background(cardBackground)
        .cornerRadius(12)
        .overlay(
            RoundedRectangle(cornerRadius: 12)
                .strokeBorder(borderColor, lineWidth: isCurrentTurn ? 2 : 0)
        )
    }

    // MARK: - Computed Properties

    private var displayScore: Int {
        // In 10-round mode, show total score (rolling + round)
        if gameMode == .tenRound {
            return player.totalScore
        }
        return player.playerscore
    }

    private var avatarBackgroundColor: Color {
        if let colorHex = player.cardColor {
            return Color(hex: colorHex)
        }
        return player.levelBadgeColor.opacity(0.8)
    }

    private var borderColor: Color {
        if isCurrentTurn {
            return FarkleColors.gold
        }
        return FarkleColors.cardBorder
    }

    private var cardBackground: some View {
        Group {
            if isCurrentTurn {
                LinearGradient(
                    colors: [
                        FarkleColors.gold.opacity(0.2),
                        FarkleColors.cardBackground
                    ],
                    startPoint: .top,
                    endPoint: .bottom
                )
            } else {
                FarkleColors.cardBackground
            }
        }
    }
}

#Preview {
    HStack(spacing: 12) {
        GamePlayerCardView(
            player: GamePlayer(
                playerId: 1,
                username: "Player1",
                playerLevel: 15,
                playerturn: 1,
                playerround: 5,
                playerscore: 2500,
                roundscore: 350,
                rollingscore: 2150
            ),
            isCurrentTurn: true,
            isMyPlayer: true,
            gameMode: .tenRound,
            currentRound: 5
        )

        GamePlayerCardView(
            player: GamePlayer(
                playerId: 2,
                username: "BotPlayer",
                playerLevel: 10,
                playerturn: 2,
                playerround: 5,
                playerscore: 2200,
                roundscore: 0,
                rollingscore: 2200,
                isBot: true
            ),
            isCurrentTurn: false,
            isMyPlayer: false,
            gameMode: .tenRound,
            currentRound: 5
        )
    }
    .padding()
    .background(FarkleColors.feltGreen)
}
