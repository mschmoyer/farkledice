//
//  ActivityLogEntry.swift
//  Farkle Ten
//
//  Model for activity log entries showing round history
//

import Foundation
import SwiftUI

/// Represents a single entry in the game activity log
struct ActivityLogEntry: Identifiable, Equatable {
    let id: String
    let playerId: Int
    let username: String
    let roundNum: Int
    let roundScore: Int
    let diceValues: [Int]  // Dice values for this roll/set
    let isFarkle: Bool
    let isHotDice: Bool  // Rolled all 6 dice again

    /// Creates an entry from API response dictionary
    init(from dict: [String: Any], index: Int) {
        self.id = "\(dict["playerid"] ?? 0)-\(dict["roundnum"] ?? 0)-\(index)"
        self.playerId = Self.parseIntValue(dict["playerid"])
        self.username = dict["username"] as? String ?? "Unknown"
        self.roundNum = Self.parseIntValue(dict["roundnum"])
        self.roundScore = Self.parseIntValue(dict["roundscore"])
        self.isFarkle = Self.parseBoolValue(dict["farkle"])
        self.isHotDice = Self.parseBoolValue(dict["hotdice"])

        // Parse dice values from d1-d6 fields
        var dice: [Int] = []
        for i in 1...6 {
            if let val = Self.parseIntValue(dict["d\(i)"]) as Int?, val > 0 {
                dice.append(val)
            }
        }
        self.diceValues = dice
    }

    /// Convenience initializer for previews
    init(
        id: String = UUID().uuidString,
        playerId: Int,
        username: String,
        roundNum: Int,
        roundScore: Int,
        diceValues: [Int] = [],
        isFarkle: Bool = false,
        isHotDice: Bool = false
    ) {
        self.id = id
        self.playerId = playerId
        self.username = username
        self.roundNum = roundNum
        self.roundScore = roundScore
        self.diceValues = diceValues
        self.isFarkle = isFarkle
        self.isHotDice = isHotDice
    }

    /// Unicode dice faces for display
    var unicodeDice: String {
        diceValues.map { value in
            switch value {
            case 1: return "\u{2680}"
            case 2: return "\u{2681}"
            case 3: return "\u{2682}"
            case 4: return "\u{2683}"
            case 5: return "\u{2684}"
            case 6: return "\u{2685}"
            default: return ""
            }
        }.joined()
    }

    /// Color for the score display
    var scoreColor: Color {
        if isFarkle {
            return FarkleColors.gameLost
        } else if roundScore > 0 {
            return FarkleColors.gameWon
        }
        return FarkleColors.textPrimary
    }

    // MARK: - Parsing Helpers

    private static func parseIntValue(_ value: Any?) -> Int {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return 0
    }

    private static func parseBoolValue(_ value: Any?) -> Bool {
        if let boolVal = value as? Bool {
            return boolVal
        } else if let strVal = value as? String {
            return strVal == "t" || strVal == "true" || strVal == "1"
        } else if let intVal = value as? Int {
            return intVal != 0
        }
        return false
    }
}

/// Groups log entries by round number
struct RoundLogGroup: Identifiable {
    let roundNum: Int
    let entries: [ActivityLogEntry]

    var id: Int { roundNum }

    /// Total score for this round (last entry's score or 0 if farkle)
    var totalScore: Int {
        entries.last?.roundScore ?? 0
    }

    /// Did this round end in a farkle?
    var isFarkle: Bool {
        entries.last?.isFarkle ?? false
    }
}
