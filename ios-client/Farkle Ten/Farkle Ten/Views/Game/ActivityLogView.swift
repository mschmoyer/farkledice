//
//  ActivityLogView.swift
//  Farkle Ten
//
//  Round history display with Unicode dice
//

import SwiftUI

struct ActivityLogView: View {
    let entries: [ActivityLogEntry]
    let currentPlayerId: Int

    @State private var isExpanded: Bool = false

    // Group entries by round
    private var groupedByRound: [RoundLogGroup] {
        let grouped = Dictionary(grouping: entries) { $0.roundNum }
        return grouped.map { RoundLogGroup(roundNum: $0.key, entries: $0.value) }
            .sorted { $0.roundNum > $1.roundNum }  // Most recent first
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            // Header with expand/collapse
            Button(action: { isExpanded.toggle() }) {
                HStack {
                    Image(systemName: "list.bullet.clipboard")
                        .foregroundColor(FarkleColors.gold)

                    Text("Activity Log")
                        .font(FarkleTypography.subtitle)
                        .foregroundColor(.white)

                    Spacer()

                    Image(systemName: isExpanded ? "chevron.up" : "chevron.down")
                        .foregroundColor(FarkleColors.textMuted)
                        .font(.system(size: 14))
                }
            }
            .buttonStyle(PlainButtonStyle())

            // Content
            if isExpanded {
                if groupedByRound.isEmpty {
                    Text("No activity yet")
                        .font(FarkleTypography.caption)
                        .foregroundColor(FarkleColors.textMuted)
                        .padding(.vertical, 8)
                } else {
                    VStack(alignment: .leading, spacing: 12) {
                        ForEach(groupedByRound.prefix(10)) { roundGroup in
                            RoundLogGroupView(
                                group: roundGroup,
                                currentPlayerId: currentPlayerId
                            )
                        }
                    }
                    .padding(.top, 8)
                }
            }
        }
        .padding(12)
        .background(Color.black.opacity(0.2))
        .cornerRadius(12)
    }
}

// MARK: - Round Log Group View

struct RoundLogGroupView: View {
    let group: RoundLogGroup
    let currentPlayerId: Int

    private var playerName: String {
        group.entries.first?.username ?? "Unknown"
    }

    private var isMyRound: Bool {
        group.entries.first?.playerId == currentPlayerId
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            // Round header
            HStack {
                Text("Round \(group.roundNum)")
                    .font(FarkleTypography.micro)
                    .foregroundColor(FarkleColors.textMuted)
                    .textCase(.uppercase)

                Text("-")
                    .foregroundColor(FarkleColors.textMuted)

                Text(playerName)
                    .font(FarkleTypography.caption)
                    .foregroundColor(isMyRound ? FarkleColors.gold : .white)

                Spacer()

                // Round total
                if group.isFarkle {
                    Text("FARKLE")
                        .font(FarkleTypography.micro)
                        .fontWeight(.bold)
                        .foregroundColor(FarkleColors.gameLost)
                } else {
                    Text("+\(group.totalScore)")
                        .font(FarkleTypography.caption)
                        .fontWeight(.semibold)
                        .foregroundColor(FarkleColors.gameWon)
                }
            }

            // Dice entries
            HStack(spacing: 4) {
                ForEach(Array(group.entries.enumerated()), id: \.offset) { index, entry in
                    if index > 0 {
                        // Arrow separator for hot dice
                        if entry.isHotDice {
                            Image(systemName: "arrow.right")
                                .font(.system(size: 10))
                                .foregroundColor(FarkleColors.gameYourTurn)
                        } else {
                            Text("-")
                                .font(FarkleTypography.micro)
                                .foregroundColor(FarkleColors.textMuted)
                        }
                    }

                    Text(entry.unicodeDice)
                        .font(.system(size: 16))
                        .foregroundColor(entry.isFarkle ? FarkleColors.gameLost : .white)
                }
            }
        }
        .padding(8)
        .background(Color.black.opacity(0.15))
        .cornerRadius(8)
    }
}

#Preview {
    ZStack {
        FeltBackground()

        VStack {
            ActivityLogView(
                entries: [
                    ActivityLogEntry(
                        id: "1",
                        playerId: 1,
                        username: "Player1",
                        roundNum: 5,
                        roundScore: 350,
                        diceValues: [1, 5, 5]
                    ),
                    ActivityLogEntry(
                        id: "2",
                        playerId: 1,
                        username: "Player1",
                        roundNum: 5,
                        roundScore: 500,
                        diceValues: [1, 1]
                    ),
                    ActivityLogEntry(
                        id: "3",
                        playerId: 2,
                        username: "BotPlayer",
                        roundNum: 4,
                        roundScore: 0,
                        diceValues: [2, 3, 4, 6],
                        isFarkle: true
                    ),
                    ActivityLogEntry(
                        id: "4",
                        playerId: 1,
                        username: "Player1",
                        roundNum: 3,
                        roundScore: 1000,
                        diceValues: [1, 1, 1],
                        isHotDice: true
                    )
                ],
                currentPlayerId: 1
            )
            .padding()

            Spacer()
        }
    }
}
