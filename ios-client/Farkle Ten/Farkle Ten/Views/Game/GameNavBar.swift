//
//  GameNavBar.swift
//  Farkle Ten
//
//  Reusable navigation bar with home icon and logo
//

import SwiftUI

struct GameNavBar: View {
    let onHome: () -> Void

    var body: some View {
        HStack {
            // Home button
            Button(action: onHome) {
                Image(systemName: "house.fill")
                    .font(.system(size: 22))
                    .foregroundColor(FarkleColors.gold)
                    .frame(width: 44, height: 44)
                    .background(Color.black.opacity(0.3))
                    .cornerRadius(12)
            }

            Spacer()

            // Logo
            Text("FARKLE TEN")
                .font(FarkleTypography.title)
                .foregroundColor(FarkleColors.gold)
                .shadow(color: .black.opacity(0.5), radius: 2, x: 0, y: 2)

            Spacer()

            // Placeholder for balance (optional right side content)
            Color.clear
                .frame(width: 44, height: 44)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 8)
        .background(
            LinearGradient(
                colors: [
                    Color.black.opacity(0.4),
                    Color.black.opacity(0.2)
                ],
                startPoint: .top,
                endPoint: .bottom
            )
        )
    }
}

#Preview {
    ZStack {
        FeltBackground()
        VStack {
            GameNavBar(onHome: {})
            Spacer()
        }
    }
}
