//
//  BotChatView.swift
//  Farkle Ten
//
//  Display for bot personality messages
//

import SwiftUI

struct BotChatView: View {
    let messages: [String]

    @State private var isExpanded: Bool = true

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            // Header with expand/collapse
            Button(action: { isExpanded.toggle() }) {
                HStack {
                    Image(systemName: "cpu")
                        .foregroundColor(FarkleColors.textSecondary)

                    Text("Bot Chat")
                        .font(FarkleTypography.subtitle)
                        .foregroundColor(.white)

                    Spacer()

                    Image(systemName: isExpanded ? "chevron.up" : "chevron.down")
                        .foregroundColor(FarkleColors.textMuted)
                        .font(.system(size: 14))
                }
            }
            .buttonStyle(PlainButtonStyle())

            // Messages
            if isExpanded && !messages.isEmpty {
                VStack(alignment: .leading, spacing: 6) {
                    ForEach(Array(messages.suffix(5).enumerated()), id: \.offset) { _, message in
                        BotMessageView(message: message)
                    }
                }
                .padding(.top, 4)
            }
        }
        .padding(12)
        .background(Color.black.opacity(0.2))
        .cornerRadius(12)
    }
}

// MARK: - Bot Message View

struct BotMessageView: View {
    let message: String

    var body: some View {
        HStack(alignment: .top, spacing: 8) {
            Text(">")
                .font(.system(size: 14, design: .monospaced))
                .foregroundColor(FarkleColors.textMuted)

            Text(message)
                .font(.system(size: 13, design: .monospaced))
                .foregroundColor(FarkleColors.textSecondary)
                .lineLimit(3)
        }
        .padding(.horizontal, 8)
        .padding(.vertical, 6)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.black.opacity(0.15))
        .cornerRadius(6)
    }
}

#Preview {
    ZStack {
        FeltBackground()

        VStack {
            BotChatView(messages: [
                "Hmm, let me think about this roll...",
                "Taking the ones. Can't resist that 300 points!",
                "Rolling the remaining dice...",
                "Nice! Got a triple 4s!",
                "Time to bank these 700 points."
            ])
            .padding()

            Spacer()
        }
    }
}
