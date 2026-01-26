//
//  FarkleOverlay.swift
//  Farkle Ten
//
//  Animated overlay displayed when player farkles
//

import SwiftUI

struct FarkleOverlay: View {
    @State private var scale: CGFloat = 0.1
    @State private var opacity: Double = 0
    @State private var rotation: Double = -15
    @State private var shakeOffset: CGFloat = 0

    var body: some View {
        ZStack {
            // Dim background
            Color.black.opacity(0.7)
                .ignoresSafeArea()
                .opacity(opacity)

            // Farkle text
            VStack(spacing: 16) {
                Text("FARKLE!")
                    .font(.system(size: 72, weight: .black, design: .rounded))
                    .foregroundColor(FarkleColors.gameLost)
                    .shadow(color: .black, radius: 4, x: 2, y: 2)
                    .shadow(color: FarkleColors.gameLost.opacity(0.5), radius: 20, x: 0, y: 0)
                    .rotationEffect(.degrees(rotation))
                    .offset(x: shakeOffset)
                    .scaleEffect(scale)

                Text("No scoring dice!")
                    .font(FarkleTypography.subtitle)
                    .foregroundColor(.white)
                    .opacity(opacity)
            }
        }
        .onAppear {
            animateIn()
        }
    }

    private func animateIn() {
        // Scale and fade in
        withAnimation(.spring(response: 0.4, dampingFraction: 0.5)) {
            scale = 1.0
            opacity = 1.0
            rotation = 0
        }

        // Shake effect
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.3) {
            withAnimation(.easeInOut(duration: 0.08).repeatCount(6, autoreverses: true)) {
                shakeOffset = 10
            }

            DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) {
                withAnimation(.easeOut) {
                    shakeOffset = 0
                }
            }
        }
    }
}

#Preview {
    FarkleOverlay()
}
