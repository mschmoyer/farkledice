//
//  Dice.swift
//  Farkle Ten
//
//  Model for individual dice in the game
//

import Foundation

/// Represents the state of a single die
struct Dice: Identifiable, Equatable {
    let id: Int  // 0-5 position
    var value: Int  // 1-6 face value
    var isSaved: Bool  // Selected by player for scoring
    var isScored: Bool  // Already scored in previous set

    /// Creates a die with default state
    init(id: Int, value: Int = 0, isSaved: Bool = false, isScored: Bool = false) {
        self.id = id
        self.value = value
        self.isSaved = isSaved
        self.isScored = isScored
    }

    /// Die is available to be selected (not already scored)
    var isSelectable: Bool {
        !isScored && value > 0
    }

    /// Die is currently on the table (has a value and not scored)
    var isOnTable: Bool {
        value > 0 && !isScored
    }

    /// Unicode dice face for this die value
    var unicodeFace: String {
        switch value {
        case 1: return "\u{2680}" // ⚀
        case 2: return "\u{2681}" // ⚁
        case 3: return "\u{2682}" // ⚂
        case 4: return "\u{2683}" // ⚃
        case 5: return "\u{2684}" // ⚄
        case 6: return "\u{2685}" // ⚅
        default: return ""
        }
    }
}

/// Convenience array extension for dice operations
extension Array where Element == Dice {
    /// Returns array of saved dice values
    var savedValues: [Int] {
        filter { $0.isSaved }.map { $0.value }
    }

    /// Returns count of saved dice
    var savedCount: Int {
        filter { $0.isSaved }.count
    }

    /// Returns count of dice still on table (not scored)
    var onTableCount: Int {
        filter { $0.isOnTable }.count
    }

    /// Returns array of values for dice on table
    var onTableValues: [Int] {
        filter { $0.isOnTable }.map { $0.value }
    }

    /// Creates initial 6 dice with zeroed values
    static func initial() -> [Dice] {
        (0..<6).map { Dice(id: $0) }
    }
}
