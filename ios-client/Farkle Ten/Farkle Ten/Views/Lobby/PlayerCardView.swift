//
//  PlayerCardView.swift
//  Farkle Ten
//
//  Player info header card
//

import SwiftUI

struct PlayerCardView: View {
    let player: Player

    var body: some View {
        HStack(spacing: 12) {
            // Profile image placeholder / Level badge
            ZStack {
                Circle()
                    .fill(FarkleColors.levelBadgeColor(for: player.playerlevel))
                    .frame(width: 50, height: 50)

                Text("\(player.playerlevel)")
                    .font(FarkleTypography.levelBadge)
                    .foregroundColor(.white)
                    .fontWeight(.bold)
            }

            // Player info
            VStack(alignment: .leading, spacing: 4) {
                Text(player.username)
                    .font(FarkleTypography.subtitle)
                    .foregroundColor(.white)
                    .fontWeight(.bold)

                Text(player.displayTitle)
                    .font(FarkleTypography.caption)
                    .foregroundColor(FarkleColors.textSecondary)
            }

            Spacer()

            // Achievement score
            if let achscore = player.achscore, achscore > 0 {
                VStack(alignment: .trailing, spacing: 2) {
                    Text("\(achscore)")
                        .font(FarkleTypography.stat)
                        .foregroundColor(FarkleColors.gold)

                    Text("Points")
                        .font(FarkleTypography.micro)
                        .foregroundColor(FarkleColors.textMuted)
                }
            }
        }
        .padding(16)
        .background(Color.black.opacity(0.3))
        .cornerRadius(12)
    }
}

#Preview {
    VStack {
        PlayerCardView(player: Player(
            username: "TestPlayer",
            playerlevel: 15,
            xp: 750,
            xpToLevel: 1000,
            achscore: 2450,
            playertitle: "Dice Master"
        ))
    }
    .padding()
    .feltBackground()
}
