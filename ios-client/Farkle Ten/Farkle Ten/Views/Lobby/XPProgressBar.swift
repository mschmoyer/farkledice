//
//  XPProgressBar.swift
//  Farkle Ten
//
//  XP progress bar component
//

import SwiftUI

struct XPProgressBar: View {
    let currentXP: Int
    let xpToLevel: Int

    private var progress: CGFloat {
        guard xpToLevel > 0 else { return 0 }
        return CGFloat(currentXP) / CGFloat(xpToLevel)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            // XP text
            HStack {
                Text("XP")
                    .font(FarkleTypography.micro)
                    .foregroundColor(FarkleColors.textMuted)

                Spacer()

                Text("\(currentXP) / \(xpToLevel)")
                    .font(FarkleTypography.micro)
                    .foregroundColor(FarkleColors.textSecondary)
            }

            // Progress bar
            GeometryReader { geometry in
                ZStack(alignment: .leading) {
                    // Background (unfilled)
                    RoundedRectangle(cornerRadius: 4)
                        .fill(FarkleColors.xpBarEmpty)
                        .frame(height: 8)

                    // Filled portion
                    RoundedRectangle(cornerRadius: 4)
                        .fill(FarkleColors.xpBarFilled)
                        .frame(width: max(0, geometry.size.width * progress), height: 8)
                }
            }
            .frame(height: 8)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 8)
        .background(Color.black.opacity(0.2))
        .cornerRadius(8)
    }
}

#Preview {
    VStack(spacing: 20) {
        XPProgressBar(currentXP: 750, xpToLevel: 1000)
        XPProgressBar(currentXP: 250, xpToLevel: 1000)
        XPProgressBar(currentXP: 0, xpToLevel: 1000)
        XPProgressBar(currentXP: 1000, xpToLevel: 1000)
    }
    .padding()
    .feltBackground()
}
