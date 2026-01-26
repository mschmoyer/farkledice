//
//  DieView.swift
//  Farkle Ten
//
//  Single die view with tap and animation support
//

import SwiftUI

struct DieView: View {
    let die: Dice
    let isRolling: Bool
    let isSelectable: Bool
    let onTap: () -> Void

    @State private var rotationDegrees: Double = 0
    @State private var scale: CGFloat = 1.0

    // Die size
    private let dieSize: CGFloat = 60

    var body: some View {
        Button(action: {
            if isSelectable && !die.isScored {
                onTap()
            }
        }) {
            ZStack {
                // Die background
                RoundedRectangle(cornerRadius: 10)
                    .fill(dieBackgroundColor)
                    .shadow(color: .black.opacity(0.3), radius: 3, x: 0, y: 2)

                // Die border
                RoundedRectangle(cornerRadius: 10)
                    .strokeBorder(dieBorderColor, lineWidth: die.isSaved ? 3 : 1)

                // Die face (dots or value)
                if die.value > 0 {
                    DieFaceView(value: die.value, color: dieFaceColor)
                }
            }
            .frame(width: dieSize, height: dieSize)
            .rotation3DEffect(
                .degrees(rotationDegrees),
                axis: (x: 1, y: 1, z: 0)
            )
            .scaleEffect(scale)
            .offset(y: die.isSaved || die.isScored ? -10 : 0)
        }
        .buttonStyle(PlainButtonStyle())
        .disabled(!isSelectable || die.isScored)
        .onChange(of: isRolling) { rolling in
            if rolling {
                startRollingAnimation()
            } else {
                stopRollingAnimation()
            }
        }
        .animation(.spring(response: 0.3, dampingFraction: 0.7), value: die.isSaved)
    }

    // MARK: - Colors

    private var dieBackgroundColor: Color {
        if die.isScored {
            return Color.gray.opacity(0.5)
        } else if die.isSaved {
            return Color(hex: "#9B59B6")  // Purple for saved
        } else {
            return Color.white
        }
    }

    private var dieBorderColor: Color {
        if die.isSaved {
            return Color(hex: "#7D3C98")  // Darker purple border
        } else if isSelectable && !die.isScored {
            return FarkleColors.gold.opacity(0.5)
        } else {
            return Color.gray.opacity(0.3)
        }
    }

    private var dieFaceColor: Color {
        if die.isScored {
            return Color.gray
        } else if die.isSaved {
            return Color.white
        } else {
            return Color.black
        }
    }

    // MARK: - Animations

    private func startRollingAnimation() {
        withAnimation(.linear(duration: 0.1).repeatForever(autoreverses: false)) {
            rotationDegrees = 360
        }
        withAnimation(.easeInOut(duration: 0.15).repeatForever(autoreverses: true)) {
            scale = 1.1
        }
    }

    private func stopRollingAnimation() {
        withAnimation(.spring(response: 0.3, dampingFraction: 0.6)) {
            rotationDegrees = 0
            scale = 1.0
        }
    }
}

// MARK: - Die Face View (shows dots)

struct DieFaceView: View {
    let value: Int
    let color: Color

    private let dotSize: CGFloat = 10

    var body: some View {
        GeometryReader { geometry in
            let size = geometry.size
            let centerX = size.width / 2
            let centerY = size.height / 2
            let offset: CGFloat = 14

            ZStack {
                switch value {
                case 1:
                    // Center dot
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX, y: centerY)

                case 2:
                    // Top-left and bottom-right
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX - offset, y: centerY - offset)
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX + offset, y: centerY + offset)

                case 3:
                    // Diagonal line
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX - offset, y: centerY - offset)
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX, y: centerY)
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX + offset, y: centerY + offset)

                case 4:
                    // Four corners
                    ForEach(Array(0..<4), id: \.self) { i in
                        Circle()
                            .fill(color)
                            .frame(width: dotSize, height: dotSize)
                            .position(
                                x: i % 2 == 0 ? centerX - offset : centerX + offset,
                                y: i < 2 ? centerY - offset : centerY + offset
                            )
                    }

                case 5:
                    // Four corners + center
                    ForEach(Array(0..<4), id: \.self) { i in
                        Circle()
                            .fill(color)
                            .frame(width: dotSize, height: dotSize)
                            .position(
                                x: i % 2 == 0 ? centerX - offset : centerX + offset,
                                y: i < 2 ? centerY - offset : centerY + offset
                            )
                    }
                    Circle()
                        .fill(color)
                        .frame(width: dotSize, height: dotSize)
                        .position(x: centerX, y: centerY)

                case 6:
                    // Two columns of 3
                    ForEach(Array(0..<6), id: \.self) { i in
                        Circle()
                            .fill(color)
                            .frame(width: dotSize, height: dotSize)
                            .position(
                                x: i % 2 == 0 ? centerX - offset : centerX + offset,
                                y: i / 2 == 0 ? centerY - offset : (i / 2 == 1 ? centerY : centerY + offset)
                            )
                    }

                default:
                    EmptyView()
                }
            }
        }
    }
}

#Preview {
    HStack(spacing: 12) {
        DieView(die: Dice(id: 0, value: 1), isRolling: false, isSelectable: true, onTap: {})
        DieView(die: Dice(id: 1, value: 5, isSaved: true), isRolling: false, isSelectable: true, onTap: {})
        DieView(die: Dice(id: 2, value: 3, isScored: true), isRolling: false, isSelectable: false, onTap: {})
    }
    .padding()
    .background(FarkleColors.feltGreen)
}
