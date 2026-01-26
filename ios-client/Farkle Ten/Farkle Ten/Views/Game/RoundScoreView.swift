//
//  RoundScoreView.swift
//  Farkle Ten
//
//  Score display with fire effect tiers based on accumulated points
//

import SwiftUI

struct RoundScoreView: View {
    let roundScore: Int
    let turnScore: Int
    let fireLevel: FireEffectLevel

    @State private var pulseScale: CGFloat = 1.0
    @State private var glowOpacity: Double = 0.5

    private var totalScore: Int {
        roundScore + turnScore
    }

    var body: some View {
        VStack(spacing: 8) {
            // Main score display
            ZStack {
                // Fire glow background
                if fireLevel != .none {
                    fireGlowBackground
                }

                // Score text
                VStack(spacing: 2) {
                    Text("\(totalScore)")
                        .font(.system(size: 56, weight: .bold, design: .rounded))
                        .foregroundColor(scoreTextColor)
                        .shadow(color: shadowColor, radius: shadowRadius, x: 0, y: 0)

                    Text("POINTS")
                        .font(FarkleTypography.micro)
                        .foregroundColor(FarkleColors.textSecondary)
                        .tracking(2)
                }
                .scaleEffect(pulseScale)
            }
            .frame(height: 100)

            // Score breakdown
            if turnScore > 0 || roundScore > 0 {
                HStack(spacing: 16) {
                    if roundScore > 0 {
                        VStack(spacing: 2) {
                            Text("\(roundScore)")
                                .font(FarkleTypography.subtitle)
                                .foregroundColor(FarkleColors.textPrimary)
                            Text("banked")
                                .font(FarkleTypography.micro)
                                .foregroundColor(FarkleColors.textMuted)
                        }
                    }

                    if turnScore > 0 {
                        VStack(spacing: 2) {
                            Text("+\(turnScore)")
                                .font(FarkleTypography.subtitle)
                                .foregroundColor(FarkleColors.gold)
                            Text("selected")
                                .font(FarkleTypography.micro)
                                .foregroundColor(FarkleColors.textMuted)
                        }
                    }
                }
            }
        }
        .padding(.vertical, 12)
        .onChange(of: fireLevel) { newLevel in
            updateAnimation(for: newLevel)
        }
        .onAppear {
            updateAnimation(for: fireLevel)
        }
    }

    // MARK: - Fire Glow Background

    @ViewBuilder
    private var fireGlowBackground: some View {
        ZStack {
            // Outer glow
            Circle()
                .fill(
                    RadialGradient(
                        colors: fireGradientColors,
                        center: .center,
                        startRadius: 20,
                        endRadius: 80
                    )
                )
                .frame(width: 160, height: 160)
                .opacity(glowOpacity)
                .blur(radius: 20)

            // Inner glow
            if fireLevel == .intense {
                Circle()
                    .fill(Color.white.opacity(0.3))
                    .frame(width: 60, height: 60)
                    .blur(radius: 10)
            }
        }
    }

    // MARK: - Color Properties

    private var scoreTextColor: Color {
        switch fireLevel {
        case .none:
            return FarkleColors.textPrimary
        case .low:
            return Color(hex: "#FF9500")  // Orange
        case .medium:
            return Color(hex: "#FF6B35")  // Orange-red
        case .high:
            return Color(hex: "#FF3B30")  // Red
        case .intense:
            return Color.white
        }
    }

    private var shadowColor: Color {
        switch fireLevel {
        case .none:
            return .clear
        case .low:
            return Color(hex: "#FF9500").opacity(0.5)
        case .medium:
            return Color(hex: "#FF6B35").opacity(0.6)
        case .high:
            return Color(hex: "#FF3B30").opacity(0.7)
        case .intense:
            return Color(hex: "#FFD700").opacity(0.8)
        }
    }

    private var shadowRadius: CGFloat {
        switch fireLevel {
        case .none: return 0
        case .low: return 5
        case .medium: return 8
        case .high: return 12
        case .intense: return 16
        }
    }

    private var fireGradientColors: [Color] {
        switch fireLevel {
        case .none:
            return [.clear]
        case .low:
            return [
                Color(hex: "#FF9500").opacity(0.4),
                Color(hex: "#FF9500").opacity(0.1),
                .clear
            ]
        case .medium:
            return [
                Color(hex: "#FF6B35").opacity(0.5),
                Color(hex: "#FF9500").opacity(0.2),
                .clear
            ]
        case .high:
            return [
                Color(hex: "#FF3B30").opacity(0.6),
                Color(hex: "#FF6B35").opacity(0.3),
                Color(hex: "#FF9500").opacity(0.1),
                .clear
            ]
        case .intense:
            return [
                Color.white.opacity(0.3),
                Color(hex: "#FFD700").opacity(0.5),
                Color(hex: "#FF6B35").opacity(0.3),
                Color(hex: "#FF3B30").opacity(0.1),
                .clear
            ]
        }
    }

    // MARK: - Animations

    private func updateAnimation(for level: FireEffectLevel) {
        // Stop any existing animation
        pulseScale = 1.0
        glowOpacity = 0.5

        switch level {
        case .none:
            break

        case .low:
            // Slow pulse
            withAnimation(.easeInOut(duration: 1.5).repeatForever(autoreverses: true)) {
                pulseScale = 1.02
                glowOpacity = 0.7
            }

        case .medium:
            // Medium pulse
            withAnimation(.easeInOut(duration: 1.0).repeatForever(autoreverses: true)) {
                pulseScale = 1.03
                glowOpacity = 0.8
            }

        case .high:
            // Fast flicker
            withAnimation(.easeInOut(duration: 0.5).repeatForever(autoreverses: true)) {
                pulseScale = 1.04
                glowOpacity = 0.9
            }

        case .intense:
            // Intense glow
            withAnimation(.easeInOut(duration: 0.3).repeatForever(autoreverses: true)) {
                pulseScale = 1.05
                glowOpacity = 1.0
            }
        }
    }
}

#Preview {
    VStack(spacing: 40) {
        RoundScoreView(roundScore: 0, turnScore: 250, fireLevel: .none)
        RoundScoreView(roundScore: 300, turnScore: 150, fireLevel: .low)
        RoundScoreView(roundScore: 500, turnScore: 200, fireLevel: .medium)
        RoundScoreView(roundScore: 700, turnScore: 200, fireLevel: .high)
        RoundScoreView(roundScore: 900, turnScore: 200, fireLevel: .intense)
    }
    .padding()
    .background(FarkleColors.feltGreen)
}
