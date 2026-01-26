//
//  Player.swift
//  Farkle Ten
//
//  Player info model from API
//

import Foundation

struct Player: Codable, Identifiable {
    var id: String { username }

    let username: String
    let playertitle: String?
    let cardcolor: String?
    let cardbg: String?
    let playerlevel: Int
    let xp: Int
    let xpToLevel: Int
    let achscore: Int?

    enum CodingKeys: String, CodingKey {
        case username
        case playertitle
        case cardcolor
        case cardbg
        case playerlevel
        case xp
        case xpToLevel = "xp_to_level"
        case achscore
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        username = try container.decode(String.self, forKey: .username)
        playertitle = try container.decodeIfPresent(String.self, forKey: .playertitle)
        cardcolor = try container.decodeIfPresent(String.self, forKey: .cardcolor)
        cardbg = try container.decodeIfPresent(String.self, forKey: .cardbg)

        // Handle potential string or int values
        if let level = try? container.decode(Int.self, forKey: .playerlevel) {
            playerlevel = level
        } else if let levelStr = try? container.decode(String.self, forKey: .playerlevel),
                  let level = Int(levelStr) {
            playerlevel = level
        } else {
            playerlevel = 1
        }

        if let xpVal = try? container.decode(Int.self, forKey: .xp) {
            xp = xpVal
        } else if let xpStr = try? container.decode(String.self, forKey: .xp),
                  let xpVal = Int(xpStr) {
            xp = xpVal
        } else {
            xp = 0
        }

        if let xpToLevelVal = try? container.decode(Int.self, forKey: .xpToLevel) {
            xpToLevel = xpToLevelVal
        } else if let xpToLevelStr = try? container.decode(String.self, forKey: .xpToLevel),
                  let xpToLevelVal = Int(xpToLevelStr) {
            xpToLevel = xpToLevelVal
        } else {
            xpToLevel = 100
        }

        if let score = try? container.decode(Int.self, forKey: .achscore) {
            achscore = score
        } else if let scoreStr = try? container.decode(String.self, forKey: .achscore),
                  let score = Int(scoreStr) {
            achscore = score
        } else {
            achscore = nil
        }
    }

    // Convenience initializer for previews
    init(username: String, playerlevel: Int, xp: Int, xpToLevel: Int, achscore: Int? = nil, playertitle: String? = nil, cardcolor: String? = nil, cardbg: String? = nil) {
        self.username = username
        self.playerlevel = playerlevel
        self.xp = xp
        self.xpToLevel = xpToLevel
        self.achscore = achscore
        self.playertitle = playertitle
        self.cardcolor = cardcolor
        self.cardbg = cardbg
    }

    var xpProgress: Double {
        guard xpToLevel > 0 else { return 0 }
        return Double(xp) / Double(xpToLevel)
    }

    var displayTitle: String {
        playertitle ?? "Farkle Player"
    }
}
