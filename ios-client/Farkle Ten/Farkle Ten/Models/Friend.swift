//
//  Friend.swift
//  Farkle Ten
//
//  Active friend model from API
//

import Foundation

struct Friend: Codable, Identifiable {
    let playerid: Int
    let username: String
    let playertitle: String?
    let cardcolor: String?
    let activeGameId: Int?
    let activeGameOpponent: String?

    var id: Int { playerid }

    enum CodingKeys: String, CodingKey {
        case playerid, username, playertitle, cardcolor
        case activeGameId = "active_game_id"
        case activeGameOpponent = "active_game_opponent"
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        // Handle playerid as string or int
        if let pid = try? container.decode(Int.self, forKey: .playerid) {
            playerid = pid
        } else if let pidStr = try? container.decode(String.self, forKey: .playerid),
                  let pid = Int(pidStr) {
            playerid = pid
        } else {
            playerid = 0
        }

        username = (try? container.decode(String.self, forKey: .username)) ?? "Unknown"
        playertitle = try? container.decode(String.self, forKey: .playertitle)
        cardcolor = try? container.decode(String.self, forKey: .cardcolor)

        // Handle active game fields
        if let gameId = try? container.decode(Int.self, forKey: .activeGameId) {
            activeGameId = gameId
        } else if let gameIdStr = try? container.decode(String.self, forKey: .activeGameId),
                  let gameId = Int(gameIdStr) {
            activeGameId = gameId
        } else {
            activeGameId = nil
        }

        activeGameOpponent = try? container.decode(String.self, forKey: .activeGameOpponent)
    }

    // Convenience initializer for previews
    init(playerid: Int, username: String, playertitle: String? = nil, cardcolor: String? = nil, activeGameId: Int? = nil, activeGameOpponent: String? = nil) {
        self.playerid = playerid
        self.username = username
        self.playertitle = playertitle
        self.cardcolor = cardcolor
        self.activeGameId = activeGameId
        self.activeGameOpponent = activeGameOpponent
    }

    var isInGame: Bool {
        activeGameId != nil
    }

    var displayTitle: String {
        playertitle ?? "Farkle Player"
    }
}
