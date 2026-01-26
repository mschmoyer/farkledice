//
//  LobbyResponse.swift
//  Farkle Ten
//
//  Full lobby API response model
//
//  API returns a JSON array with indices:
//  [0] Player info
//  [1] Active games array
//  [2] New achievement (or null)
//  [3] Level up info (or null)
//  [4] Active tournament (or null)
//  [5] Active friends array
//

import Foundation

struct LobbyResponse {
    let player: Player
    let games: [Game]
    let newAchievement: Achievement?
    let levelUp: LevelUp?
    let activeTournament: Tournament?
    let activeFriends: [Friend]
}

struct Achievement: Codable {
    let achievementid: Int
    let name: String
    let description: String?
    let xpReward: Int?
    let worth: Int?
    let title: String?
    let imagefile: String?

    enum CodingKeys: String, CodingKey {
        case achievementid, name, description
        case xpReward = "xp_reward"
        case worth, title, imagefile
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        if let aid = try? container.decode(Int.self, forKey: .achievementid) {
            achievementid = aid
        } else if let aidStr = try? container.decode(String.self, forKey: .achievementid),
                  let aid = Int(aidStr) {
            achievementid = aid
        } else {
            achievementid = 0
        }

        name = (try? container.decode(String.self, forKey: .name)) ?? "Achievement"
        description = try? container.decode(String.self, forKey: .description)

        if let xp = try? container.decode(Int.self, forKey: .xpReward) {
            xpReward = xp
        } else if let xpStr = try? container.decode(String.self, forKey: .xpReward),
                  let xp = Int(xpStr) {
            xpReward = xp
        } else {
            xpReward = nil
        }

        if let w = try? container.decode(Int.self, forKey: .worth) {
            worth = w
        } else if let wStr = try? container.decode(String.self, forKey: .worth),
                  let w = Int(wStr) {
            worth = w
        } else {
            worth = nil
        }

        title = try? container.decode(String.self, forKey: .title)
        imagefile = try? container.decode(String.self, forKey: .imagefile)
    }
}

struct LevelUp: Codable {
    let newLevel: Int
    let message: String?

    enum CodingKeys: String, CodingKey {
        case newLevel = "new_level"
        case message
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        if let level = try? container.decode(Int.self, forKey: .newLevel) {
            newLevel = level
        } else if let levelStr = try? container.decode(String.self, forKey: .newLevel),
                  let level = Int(levelStr) {
            newLevel = level
        } else {
            newLevel = 1
        }

        message = try? container.decode(String.self, forKey: .message)
    }
}

struct Tournament: Codable, Identifiable {
    let tournamentid: Int
    let name: String
    let status: String?
    let startDate: String?
    let endDate: String?

    var id: Int { tournamentid }

    enum CodingKeys: String, CodingKey {
        case tournamentid, name, status
        case startDate = "start_date"
        case endDate = "end_date"
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        if let tid = try? container.decode(Int.self, forKey: .tournamentid) {
            tournamentid = tid
        } else if let tidStr = try? container.decode(String.self, forKey: .tournamentid),
                  let tid = Int(tidStr) {
            tournamentid = tid
        } else {
            tournamentid = 0
        }

        name = (try? container.decode(String.self, forKey: .name)) ?? "Tournament"
        status = try? container.decode(String.self, forKey: .status)
        startDate = try? container.decode(String.self, forKey: .startDate)
        endDate = try? container.decode(String.self, forKey: .endDate)
    }
}

// MARK: - Custom Decoder for Array-based Response

extension LobbyResponse {
    init(from jsonArray: [Any]) throws {
        // Index 0: Player info
        guard let playerDict = jsonArray[safe: 0] as? [String: Any] else {
            throw APIError.invalidResponse
        }
        let playerData = try JSONSerialization.data(withJSONObject: playerDict)
        player = try JSONDecoder().decode(Player.self, from: playerData)

        // Index 1: Games array
        if let gamesArray = jsonArray[safe: 1] as? [[String: Any]] {
            let gamesData = try JSONSerialization.data(withJSONObject: gamesArray)
            games = try JSONDecoder().decode([Game].self, from: gamesData)
        } else {
            games = []
        }

        // Index 2: New achievement (or null)
        if let achDict = jsonArray[safe: 2] as? [String: Any], !achDict.isEmpty {
            let achData = try JSONSerialization.data(withJSONObject: achDict)
            newAchievement = try JSONDecoder().decode(Achievement.self, from: achData)
        } else {
            newAchievement = nil
        }

        // Index 3: Level up info (or null)
        if let levelDict = jsonArray[safe: 3] as? [String: Any], !levelDict.isEmpty {
            let levelData = try JSONSerialization.data(withJSONObject: levelDict)
            levelUp = try JSONDecoder().decode(LevelUp.self, from: levelData)
        } else {
            levelUp = nil
        }

        // Index 4: Active tournament (or null)
        if let tournamentDict = jsonArray[safe: 4] as? [String: Any], !tournamentDict.isEmpty {
            let tournamentData = try JSONSerialization.data(withJSONObject: tournamentDict)
            activeTournament = try JSONDecoder().decode(Tournament.self, from: tournamentData)
        } else {
            activeTournament = nil
        }

        // Index 5: Active friends array
        if let friendsArray = jsonArray[safe: 5] as? [[String: Any]] {
            let friendsData = try JSONSerialization.data(withJSONObject: friendsArray)
            activeFriends = try JSONDecoder().decode([Friend].self, from: friendsData)
        } else {
            activeFriends = []
        }
    }
}

// MARK: - Safe Array Access

private extension Array {
    subscript(safe index: Int) -> Element? {
        return indices.contains(index) ? self[index] : nil
    }
}
