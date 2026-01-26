//
//  DiceScoringEngine.swift
//  Farkle Ten
//
//  Client-side dice scoring logic matching PHP farkleDiceScoring.php
//

import Foundation

/// Calculates scores for Farkle dice combinations
struct DiceScoringEngine {

    /// Scores a set of dice values
    /// - Parameter dice: Array of dice values (1-6), only non-zero values are counted
    /// - Returns: Score value, or 0 if invalid combination
    static func scoreDice(_ dice: [Int]) -> Int {
        // Filter to only scoring dice (1-6)
        let validDice = dice.filter { $0 >= 1 && $0 <= 6 }

        if validDice.isEmpty {
            return 0
        }

        var scoreValue = 0
        var numSingleMatches = 0
        var threePair = 0
        var twoTriplets = 0
        var prevThreePairMatches = 0
        var forceThreePair = false

        // Create a mutable copy for counting
        var remaining = validDice

        // Count matches for each unique value
        var index = 0
        while index < remaining.count {
            let number = remaining[index]
            if number == 0 {
                index += 1
                continue
            }

            // Count how many of this number
            var matches = 0
            for j in 0..<remaining.count {
                if remaining[j] == number {
                    matches += 1
                    remaining[j] = 0  // Mark as counted
                }
            }

            if matches == 1 {
                numSingleMatches += 1  // Tracking for straight
            }

            // Score based on number and count
            if number == 1 {
                if matches < 3 {
                    scoreValue += matches * 100
                } else {
                    // 1000 for 3 ones, 2000 for 4, 3000 for 5, 4000 for 6
                    scoreValue += 1000 * (matches - 2)
                }
            } else if number == 5 {
                if matches < 3 {
                    scoreValue += matches * 50
                } else {
                    // 500 for 3 fives, 1000 for 4, 1500 for 5, 2000 for 6
                    scoreValue += 500 * (matches - 2)
                }
            } else {
                // Other numbers only score with 3+
                if matches >= 3 {
                    // Number * 100 for triple, double for quad, triple for quint
                    scoreValue += (number * 100) * (matches - 2)
                }
            }

            // Track pairs for special combos
            let pairs = matches / 2
            if matches == 2 || matches == 4 || matches == 6 {
                threePair += pairs
            }
            if matches == 3 || matches == 6 {
                twoTriplets += matches / 3
            }

            // Check for three pair vs four of a kind preference
            if (prevThreePairMatches == 2 && pairs == 1) || (prevThreePairMatches == 1 && pairs == 2) {
                forceThreePair = true
            }
            prevThreePairMatches = threePair

            index += 1
        }

        // Special combinations

        // Three pair: 750 points (if not already scoring higher)
        if (threePair == 3 && scoreValue < 750) || forceThreePair {
            if scoreValue < 750 {
                scoreValue = 750
            }
        }

        // Straight (1-2-3-4-5-6): 1000 points
        if numSingleMatches == 6 && scoreValue < 1000 {
            scoreValue = 1000
        }

        // Two triplets: 2500 points
        if twoTriplets == 2 && scoreValue < 2500 {
            scoreValue = 2500
        }

        return scoreValue
    }

    /// Checks if a set of dice has any scoring combination
    /// - Parameter dice: Array of dice values
    /// - Returns: true if at least one scoring combination exists
    static func hasScoringDice(_ dice: [Int]) -> Bool {
        let validDice = dice.filter { $0 >= 1 && $0 <= 6 }

        if validDice.isEmpty {
            return false
        }

        // Check for ones or fives (always score)
        if validDice.contains(1) || validDice.contains(5) {
            return true
        }

        // Check for three or more of any number
        let counts = Dictionary(grouping: validDice, by: { $0 }).mapValues { $0.count }
        for (_, count) in counts {
            if count >= 3 {
                return true
            }
        }

        // Check for straight (1-2-3-4-5-6)
        if Set(validDice).count == 6 && validDice.count == 6 {
            return true
        }

        // Check for three pairs
        let pairCount = counts.values.filter { $0 == 2 }.count
        if pairCount == 3 {
            return true
        }

        return false
    }

    /// Validates that the selected dice form a valid scoring combination
    /// - Parameter savedDice: Array of dice values selected by player
    /// - Returns: true if valid selection
    static func isValidSelection(_ savedDice: [Int]) -> Bool {
        let validDice = savedDice.filter { $0 >= 1 && $0 <= 6 }

        if validDice.isEmpty {
            return false
        }

        // Score the selection - if score > 0, it's valid
        return scoreDice(validDice) > 0
    }

    /// Returns all valid scoring combinations from a roll
    /// - Parameter dice: Array of all dice values on table
    /// - Returns: Array of possible scoring selections with their values
    static func findScoringOptions(_ dice: [Int]) -> [(dice: [Int], score: Int)] {
        let validDice = dice.filter { $0 >= 1 && $0 <= 6 }
        var options: [(dice: [Int], score: Int)] = []

        // Find individual 1s and 5s
        for (index, value) in validDice.enumerated() {
            if value == 1 {
                options.append((dice: [1], score: 100))
            } else if value == 5 {
                options.append((dice: [5], score: 50))
            }
        }

        // Find three+ of a kind for each number
        let counts = Dictionary(grouping: validDice, by: { $0 }).mapValues { $0.count }
        for (number, count) in counts {
            if count >= 3 {
                let tripleDice = Array(repeating: number, count: 3)
                let tripleScore = number == 1 ? 1000 : number * 100
                options.append((dice: tripleDice, score: tripleScore))

                // Four of a kind
                if count >= 4 {
                    let quadDice = Array(repeating: number, count: 4)
                    let quadScore = number == 1 ? 2000 : number * 100 * 2
                    options.append((dice: quadDice, score: quadScore))
                }

                // Five of a kind
                if count >= 5 {
                    let quintDice = Array(repeating: number, count: 5)
                    let quintScore = number == 1 ? 3000 : number * 100 * 3
                    options.append((dice: quintDice, score: quintScore))
                }

                // Six of a kind
                if count >= 6 {
                    let hexDice = Array(repeating: number, count: 6)
                    let hexScore = number == 1 ? 4000 : number * 100 * 4
                    options.append((dice: hexDice, score: hexScore))
                }
            }
        }

        // Check for straight
        if validDice.count == 6 && Set(validDice).count == 6 {
            options.append((dice: validDice.sorted(), score: 1000))
        }

        // Check for three pairs
        let pairValues = counts.filter { $0.value == 2 }.map { $0.key }
        if pairValues.count == 3 {
            var threePairDice: [Int] = []
            for val in pairValues {
                threePairDice.append(contentsOf: [val, val])
            }
            options.append((dice: threePairDice, score: 750))
        }

        // Check for two triplets
        let tripletValues = counts.filter { $0.value >= 3 }.map { $0.key }
        if tripletValues.count >= 2 {
            var twoTripletsDice: [Int] = []
            for val in tripletValues.prefix(2) {
                twoTripletsDice.append(contentsOf: [val, val, val])
            }
            options.append((dice: twoTripletsDice, score: 2500))
        }

        return options
    }
}
