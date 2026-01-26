//
//  GamePlayer.swift
//  Farkle Ten
//
//  Player model for in-game context
//

import Foundation
import SwiftUI

/// Represents a player within a game context
struct GamePlayer: Identifiable, Equatable {
    let playerId: Int
    let username: String
    let cardColor: String?
    let playerLevel: Int
    let playerTitle: String?
    let titleLevel: Int
    var playerturn: Int  // Player's position in turn order
    var playerround: Int  // Current round this player is on
    var playerscore: Int  // Total game score
    var roundscore: Int  // Points accumulated this round
    var rollingscore: Int  // Sum of previous rounds (10-round mode)
    var lastXPGain: Int
    var lastRoundScore: Int
    var isBot: Bool
    var botAlgorithm: String?
    var personalityId: Int?

    var id: Int { playerId }

    /// Total score in 10-round mode (rolling + current round)
    var totalScore: Int {
        rollingscore + roundscore
    }

    /// Color for the player's level badge
    var levelBadgeColor: Color {
        FarkleColors.levelBadgeColor(for: playerLevel)
    }

    /// Creates a GamePlayer from API response dictionary
    init(from dict: [String: Any]) {
        self.playerId = Self.parseIntValue(dict["playerid"])
        self.username = dict["username"] as? String ?? "Unknown"
        self.cardColor = dict["cardcolor"] as? String
        self.playerLevel = Self.parseIntValue(dict["playerlevel"])
        self.playerTitle = dict["playertitle"] as? String
        self.titleLevel = Self.parseIntValue(dict["titlelevel"])
        self.playerturn = Self.parseIntValue(dict["playerturn"])
        self.playerround = Self.parseIntValue(dict["playerround"])
        self.playerscore = Self.parseIntValue(dict["playerscore"])
        self.roundscore = Self.parseIntValue(dict["roundscore"])
        self.rollingscore = Self.parseIntValue(dict["rollingscore"])
        self.lastXPGain = Self.parseIntValue(dict["lastxpgain"])
        self.lastRoundScore = Self.parseIntValue(dict["lastroundscore"])
        self.isBot = Self.parseBoolValue(dict["is_bot"])
        self.botAlgorithm = dict["bot_algorithm"] as? String
        self.personalityId = Self.parseOptionalInt(dict["personality_id"])
    }

    /// Convenience initializer for previews/testing
    init(
        playerId: Int,
        username: String,
        playerLevel: Int = 1,
        playerturn: Int = 1,
        playerround: Int = 1,
        playerscore: Int = 0,
        roundscore: Int = 0,
        rollingscore: Int = 0,
        isBot: Bool = false
    ) {
        self.playerId = playerId
        self.username = username
        self.cardColor = nil
        self.playerLevel = playerLevel
        self.playerTitle = nil
        self.titleLevel = 0
        self.playerturn = playerturn
        self.playerround = playerround
        self.playerscore = playerscore
        self.roundscore = roundscore
        self.rollingscore = rollingscore
        self.lastXPGain = 0
        self.lastRoundScore = 0
        self.isBot = isBot
        self.botAlgorithm = nil
        self.personalityId = nil
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

    private static func parseOptionalInt(_ value: Any?) -> Int? {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return nil
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
