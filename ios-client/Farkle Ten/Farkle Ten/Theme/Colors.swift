//
//  Colors.swift
//  Farkle Ten
//
//  Design tokens from web CSS
//

import SwiftUI

struct FarkleColors {
    // Brand colors
    static let gold = Color(hex: "#FFCC66")
    static let feltGreen = Color(hex: "#1a5f1a")
    static let feltGreenDark = Color(hex: "#0d3d0d")
    static let feltBlue = Color(hex: "#1a3d5f")

    // Game status colors
    static let gameYourTurn = Color(hex: "#FF9500")  // Orange
    static let gameFinished = Color(hex: "#4A90D9")  // Blue
    static let gameWaiting = Color(hex: "#6B7280")   // Gray
    static let gameWon = Color(hex: "#34C759")       // Green
    static let gameLost = Color(hex: "#FF3B30")      // Red

    // UI colors
    static let buttonGreen = Color(hex: "#228B22")
    static let buttonBlue = Color(hex: "#4169E1")
    static let buttonRed = Color(hex: "#DC143C")

    // Card colors
    static let cardBackground = Color(hex: "#2D2D2D")
    static let cardBorder = Color(hex: "#3D3D3D")

    // Text colors
    static let textPrimary = Color.white
    static let textSecondary = Color(hex: "#9CA3AF")
    static let textMuted = Color(hex: "#6B7280")

    // Fire effect colors
    static let fireOrange = Color(hex: "#FF9500")
    static let fireOrangeRed = Color(hex: "#FF6B35")
    static let fireRed = Color(hex: "#FF3B30")
    static let fireGold = Color(hex: "#FFD700")

    // Dice colors
    static let diceSaved = Color(hex: "#9B59B6")  // Purple
    static let diceSavedBorder = Color(hex: "#7D3C98")

    // XP bar gradients
    static let xpBarFilled = LinearGradient(
        colors: [Color(hex: "#22C55E"), Color(hex: "#16A34A")],
        startPoint: .leading,
        endPoint: .trailing
    )

    static let xpBarEmpty = LinearGradient(
        colors: [Color(hex: "#4B5563"), Color(hex: "#374151")],
        startPoint: .leading,
        endPoint: .trailing
    )

    // Level badge colors (by prestige level)
    static func levelBadgeColor(for level: Int) -> Color {
        let prestige = level / 10
        switch prestige {
        case 0:
            return Color(hex: "#6B7280")  // Gray
        case 1:
            return Color(hex: "#10B981")  // Green
        case 2:
            return Color(hex: "#3B82F6")  // Blue
        case 3:
            return Color(hex: "#8B5CF6")  // Purple
        case 4:
            return Color(hex: "#F59E0B")  // Amber
        case 5:
            return Color(hex: "#EF4444")  // Red
        case 6:
            return Color(hex: "#EC4899")  // Pink
        case 7:
            return Color(hex: "#14B8A6")  // Teal
        default:
            return gold
        }
    }
}

// MARK: - Color Extension for Hex

extension Color {
    init(hex: String) {
        let hex = hex.trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        var int: UInt64 = 0
        Scanner(string: hex).scanHexInt64(&int)

        let a, r, g, b: UInt64
        switch hex.count {
        case 3: // RGB (12-bit)
            (a, r, g, b) = (255, (int >> 8) * 17, (int >> 4 & 0xF) * 17, (int & 0xF) * 17)
        case 6: // RGB (24-bit)
            (a, r, g, b) = (255, int >> 16, int >> 8 & 0xFF, int & 0xFF)
        case 8: // ARGB (32-bit)
            (a, r, g, b) = (int >> 24, int >> 16 & 0xFF, int >> 8 & 0xFF, int & 0xFF)
        default:
            (a, r, g, b) = (255, 0, 0, 0)
        }

        self.init(
            .sRGB,
            red: Double(r) / 255,
            green: Double(g) / 255,
            blue: Double(b) / 255,
            opacity: Double(a) / 255
        )
    }
}
