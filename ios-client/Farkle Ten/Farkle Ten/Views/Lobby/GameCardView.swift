//
//  GameCardView.swift
//  Farkle Ten
//
//  Individual game row in the games list
//

import SwiftUI

struct GameCardView: View {
    let game: Game
    var onTap: (() -> Void)?

    var body: some View {
        Button(action: { onTap?() }) {
            HStack(spacing: 12) {
                // Status indicator
                Circle()
                    .fill(game.statusColor)
                    .frame(width: 12, height: 12)

                // Game info
                VStack(alignment: .leading, spacing: 4) {
                    Text(game.displayName)
                        .font(FarkleTypography.bodyBold)
                        .foregroundColor(.white)
                        .lineLimit(1)

                    HStack(spacing: 8) {
                        // Game mode badge
                        Text(game.gamemode.displayName)
                            .font(FarkleTypography.micro)
                            .foregroundColor(FarkleColors.textMuted)
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Color.black.opacity(0.3))
                            .cornerRadius(4)

                        // Round info for 10-round mode
                        if game.gamemode == .tenRound && !game.isFinished {
                            Text("Round \(game.playerround)/10")
                                .font(FarkleTypography.micro)
                                .foregroundColor(FarkleColors.textSecondary)
                        }
                    }
                }

                Spacer()

                // Status text / Trophy
                VStack(alignment: .trailing, spacing: 4) {
                    if game.isFinished {
                        Image(systemName: "trophy.fill")
                            .foregroundColor(FarkleColors.gold)
                            .font(.system(size: 20))
                    }

                    Text(game.statusText)
                        .font(FarkleTypography.caption)
                        .foregroundColor(game.statusColor)
                }

                // Chevron
                Image(systemName: "chevron.right")
                    .foregroundColor(FarkleColors.textMuted)
                    .font(.system(size: 14, weight: .semibold))
            }
            .padding(16)
            .background(gameBackground)
            .cornerRadius(12)
        }
        .buttonStyle(PlainButtonStyle())
    }

    private var gameBackground: some View {
        ZStack {
            // Base background color based on status
            switch game.status {
            case .yourTurn:
                Color.orange.opacity(0.2)
            case .finished:
                Color.blue.opacity(0.2)
            case .waiting:
                Color.gray.opacity(0.15)
            }

            // Subtle gradient overlay
            LinearGradient(
                colors: [
                    Color.white.opacity(0.05),
                    Color.clear
                ],
                startPoint: .top,
                endPoint: .bottom
            )
        }
    }
}

// MARK: - Games List

struct GamesListView: View {
    let games: [Game]
    var onGameTap: ((Game) -> Void)?

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            // Section header
            HStack {
                Text("Active Games")
                    .font(FarkleTypography.subtitle)
                    .foregroundColor(.white)

                Spacer()

                Text("\(games.count)")
                    .font(FarkleTypography.caption)
                    .foregroundColor(FarkleColors.textMuted)
            }
            .padding(.horizontal, 4)

            if games.isEmpty {
                // Empty state
                VStack(spacing: 12) {
                    Image(systemName: "dice")
                        .font(.system(size: 40))
                        .foregroundColor(FarkleColors.textMuted)

                    Text("No active games")
                        .font(FarkleTypography.body)
                        .foregroundColor(FarkleColors.textSecondary)

                    Text("Start a new game to play!")
                        .font(FarkleTypography.caption)
                        .foregroundColor(FarkleColors.textMuted)
                }
                .frame(maxWidth: .infinity)
                .padding(.vertical, 40)
            } else {
                // Games list
                LazyVStack(spacing: 8) {
                    ForEach(games) { game in
                        GameCardView(game: game) {
                            onGameTap?(game)
                        }
                    }
                }
            }
        }
    }
}

#Preview {
    ScrollView {
        VStack(spacing: 20) {
            GamesListView(games: [
                // 2-player game, your turn
                Game(gameid: 1, playerstring: "You vs TestPlayer", yourturn: true, winningplayer: 0, gamemode: .tenRound, playerround: 5),
                // 2-player game, waiting
                Game(gameid: 2, playerstring: "You vs AnotherPlayer", yourturn: false, winningplayer: 0, gamemode: .standard),
                // 2-player game, finished
                Game(gameid: 3, playerstring: "You vs Winner", yourturn: false, winningplayer: 123, gamemode: .tenRound),
                // Multiplayer game (4 players)
                Game(gameid: 4, playerstring: "Multiple players", yourturn: true, winningplayer: 0, gamemode: .tenRound, maxturns: 4, playerround: 3),
                // Solo game
                Game(gameid: 5, playerstring: "Solo", yourturn: true, winningplayer: 0, gamemode: .standard, maxturns: 1)
            ])

            Divider()
                .background(Color.white.opacity(0.2))

            GamesListView(games: [])
        }
        .padding()
    }
    .feltBackground()
}
