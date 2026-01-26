//
//  Typography.swift
//  Farkle Ten
//
//  Font styles
//

import SwiftUI

struct FarkleTypography {
    // Headings
    static let title = Font.system(size: 24, weight: .bold)
    static let subtitle = Font.system(size: 18, weight: .semibold)

    // Body text
    static let body = Font.system(size: 16, weight: .regular)
    static let bodyBold = Font.system(size: 16, weight: .semibold)

    // Small text
    static let caption = Font.system(size: 14, weight: .regular)
    static let captionBold = Font.system(size: 14, weight: .medium)

    // Micro text
    static let micro = Font.system(size: 12, weight: .regular)

    // Numbers/Stats
    static let stat = Font.system(size: 20, weight: .bold, design: .rounded)
    static let statSmall = Font.system(size: 16, weight: .bold, design: .rounded)

    // Level badge
    static let levelBadge = Font.system(size: 14, weight: .bold, design: .rounded)

    // Button text
    static let button = Font.system(size: 16, weight: .semibold)
    static let buttonSmall = Font.system(size: 14, weight: .medium)
}

// MARK: - Text Style Modifiers

extension View {
    func farkleTitle() -> some View {
        self
            .font(FarkleTypography.title)
            .foregroundColor(FarkleColors.textPrimary)
    }

    func farkleSubtitle() -> some View {
        self
            .font(FarkleTypography.subtitle)
            .foregroundColor(FarkleColors.textPrimary)
    }

    func farkleBody() -> some View {
        self
            .font(FarkleTypography.body)
            .foregroundColor(FarkleColors.textPrimary)
    }

    func farkleCaption() -> some View {
        self
            .font(FarkleTypography.caption)
            .foregroundColor(FarkleColors.textSecondary)
    }

    func farkleGold() -> some View {
        self.foregroundColor(FarkleColors.gold)
    }
}
