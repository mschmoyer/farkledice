//
//  FarkleButton.swift
//  Farkle Ten
//
//  Styled button component
//

import SwiftUI

enum FarkleButtonStyle {
    case primary    // Green
    case secondary  // Blue
    case danger     // Red
    case outline    // Transparent with border

    var backgroundColor: Color {
        switch self {
        case .primary:
            return FarkleColors.buttonGreen
        case .secondary:
            return FarkleColors.buttonBlue
        case .danger:
            return FarkleColors.buttonRed
        case .outline:
            return Color.clear
        }
    }

    var foregroundColor: Color {
        switch self {
        case .outline:
            return FarkleColors.textPrimary
        default:
            return .white
        }
    }
}

struct FarkleButton: View {
    let title: String
    let style: FarkleButtonStyle
    let isLoading: Bool
    let action: () -> Void

    init(
        _ title: String,
        style: FarkleButtonStyle = .primary,
        isLoading: Bool = false,
        action: @escaping () -> Void
    ) {
        self.title = title
        self.style = style
        self.isLoading = isLoading
        self.action = action
    }

    var body: some View {
        Button(action: action) {
            HStack(spacing: 8) {
                if isLoading {
                    ProgressView()
                        .progressViewStyle(CircularProgressViewStyle(tint: style.foregroundColor))
                        .scaleEffect(0.8)
                }

                Text(title)
                    .font(FarkleTypography.button)
                    .fontWeight(.semibold)
            }
            .frame(maxWidth: .infinity)
            .padding(.vertical, 14)
            .padding(.horizontal, 20)
            .background(style.backgroundColor)
            .foregroundColor(style.foregroundColor)
            .cornerRadius(10)
            .overlay(
                RoundedRectangle(cornerRadius: 10)
                    .stroke(
                        style == .outline ? FarkleColors.textSecondary : Color.clear,
                        lineWidth: 1
                    )
            )
        }
        .disabled(isLoading)
        .opacity(isLoading ? 0.7 : 1.0)
    }
}

// MARK: - Small Button Variant

struct FarkleSmallButton: View {
    let title: String
    let style: FarkleButtonStyle
    let action: () -> Void

    init(
        _ title: String,
        style: FarkleButtonStyle = .primary,
        action: @escaping () -> Void
    ) {
        self.title = title
        self.style = style
        self.action = action
    }

    var body: some View {
        Button(action: action) {
            Text(title)
                .font(FarkleTypography.buttonSmall)
                .fontWeight(.medium)
                .padding(.vertical, 8)
                .padding(.horizontal, 16)
                .background(style.backgroundColor)
                .foregroundColor(style.foregroundColor)
                .cornerRadius(6)
                .overlay(
                    RoundedRectangle(cornerRadius: 6)
                        .stroke(
                            style == .outline ? FarkleColors.textSecondary : Color.clear,
                            lineWidth: 1
                        )
                )
        }
    }
}

#Preview {
    VStack(spacing: 16) {
        FarkleButton("New Game", style: .primary) {}
        FarkleButton("Tournament", style: .secondary) {}
        FarkleButton("Logout", style: .danger) {}
        FarkleButton("Settings", style: .outline) {}
        FarkleButton("Loading...", style: .primary, isLoading: true) {}

        HStack {
            FarkleSmallButton("Play", style: .primary) {}
            FarkleSmallButton("View", style: .secondary) {}
        }
    }
    .padding()
    .feltBackground()
}
