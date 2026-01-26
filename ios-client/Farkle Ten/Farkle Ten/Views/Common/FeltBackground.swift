//
//  FeltBackground.swift
//  Farkle Ten
//
//  Green felt texture background
//

import SwiftUI

struct FeltBackground: View {
    var color: Color = FarkleColors.feltGreen

    var body: some View {
        ZStack {
            // Base color
            color

            // Subtle noise overlay for texture effect
            // In production, this would use an actual texture image
            Rectangle()
                .fill(
                    LinearGradient(
                        colors: [
                            color.opacity(0.9),
                            color,
                            color.opacity(0.95)
                        ],
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    )
                )

            // Vignette effect
            RadialGradient(
                colors: [
                    Color.clear,
                    Color.black.opacity(0.3)
                ],
                center: .center,
                startRadius: UIScreen.main.bounds.width * 0.3,
                endRadius: UIScreen.main.bounds.width * 0.8
            )
        }
        .ignoresSafeArea()
    }
}

// MARK: - Background Modifier

extension View {
    func feltBackground(color: Color = FarkleColors.feltGreen) -> some View {
        self.background(FeltBackground(color: color))
    }
}

#Preview {
    VStack {
        Text("Farkle Ten")
            .font(.largeTitle)
            .fontWeight(.bold)
            .foregroundColor(.white)
    }
    .frame(maxWidth: .infinity, maxHeight: .infinity)
    .feltBackground()
}
