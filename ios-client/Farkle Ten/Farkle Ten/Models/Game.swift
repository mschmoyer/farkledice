//
//  Game.swift
//  Farkle Ten
//
//  Game card model from API
//

import Foundation
import SwiftUI

enum GameMode: Int, Codable {
    case standard = 1
    case tenRound = 2

    var displayName: String {
        switch self {
        case .standard:
            return "Standard"
        case .tenRound:
            return "10 Rounds"
        }
    }
}

struct Game: Codable, Identifiable {
    let gameid: Int
    let currentturn: Int
    let maxturns: Int
    let winningplayer: Int
    let playerturn: Int
    let playerround: Int
    let gamemode: GameMode
    let gamefinish: String?
    let playerstring: String
    let yourturn: Bool
    let finishedplayers: Int?

    var id: Int { gameid }

    enum CodingKeys: String, CodingKey {
        case gameid, currentturn, maxturns, winningplayer, playerturn, playerround
        case gamemode, gamefinish, playerstring, yourturn, finishedplayers
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)

        // Handle both string and int for gameid
        if let gid = try? container.decode(Int.self, forKey: .gameid) {
            gameid = gid
        } else if let gidStr = try? container.decode(String.self, forKey: .gameid),
                  let gid = Int(gidStr) {
            gameid = gid
        } else {
            gameid = 0
        }

        // Decode integers (may come as strings from PHP)
        currentturn = Self.decodeInt(from: container, key: .currentturn) ?? 0
        maxturns = Self.decodeInt(from: container, key: .maxturns) ?? 1
        winningplayer = Self.decodeInt(from: container, key: .winningplayer) ?? 0
        playerturn = Self.decodeInt(from: container, key: .playerturn) ?? 0
        playerround = Self.decodeInt(from: container, key: .playerround) ?? 0
        finishedplayers = Self.decodeInt(from: container, key: .finishedplayers)

        // Decode game mode (may be int or string)
        if let mode = try? container.decode(Int.self, forKey: .gamemode),
           let gm = GameMode(rawValue: mode) {
            gamemode = gm
        } else if let modeStr = try? container.decode(String.self, forKey: .gamemode),
                  let mode = Int(modeStr),
                  let gm = GameMode(rawValue: mode) {
            gamemode = gm
        } else {
            gamemode = .standard
        }

        gamefinish = try? container.decode(String.self, forKey: .gamefinish)
        playerstring = (try? container.decode(String.self, forKey: .playerstring)) ?? ""

        // Handle yourturn as bool or string "t"/"f" or int 0/1
        if let yt = try? container.decode(Bool.self, forKey: .yourturn) {
            yourturn = yt
        } else if let ytStr = try? container.decode(String.self, forKey: .yourturn) {
            yourturn = ytStr == "t" || ytStr == "true" || ytStr == "1"
        } else if let ytInt = try? container.decode(Int.self, forKey: .yourturn) {
            yourturn = ytInt != 0
        } else {
            yourturn = false
        }
    }

    private static func decodeInt(from container: KeyedDecodingContainer<CodingKeys>, key: CodingKeys) -> Int? {
        if let val = try? container.decode(Int.self, forKey: key) {
            return val
        } else if let str = try? container.decode(String.self, forKey: key),
                  let val = Int(str) {
            return val
        }
        return nil
    }

    // Convenience initializer for previews
    init(gameid: Int, playerstring: String, yourturn: Bool, winningplayer: Int, gamemode: GameMode, currentturn: Int = 0, maxturns: Int = 2, playerturn: Int = 0, playerround: Int = 1, gamefinish: String? = nil, finishedplayers: Int? = nil) {
        self.gameid = gameid
        self.currentturn = currentturn
        self.maxturns = maxturns
        self.winningplayer = winningplayer
        self.playerturn = playerturn
        self.playerround = playerround
        self.gamemode = gamemode
        self.gamefinish = gamefinish
        self.playerstring = playerstring
        self.yourturn = yourturn
        self.finishedplayers = finishedplayers
    }

    // MARK: - Computed Properties

    var isFinished: Bool {
        winningplayer > 0
    }

    var isSoloGame: Bool {
        maxturns == 1
    }

    var status: GameStatus {
        if isFinished {
            return .finished
        } else if yourturn {
            return .yourTurn
        } else {
            return .waiting
        }
    }

    var statusColor: Color {
        switch status {
        case .yourTurn:
            return FarkleColors.gameYourTurn
        case .finished:
            return FarkleColors.gameFinished
        case .waiting:
            return FarkleColors.gameWaiting
        }
    }

    var statusText: String {
        switch status {
        case .yourTurn:
            return "Your Turn"
        case .finished:
            return "Finished"
        case .waiting:
            return "Waiting..."
        }
    }

    var displayName: String {
        // Solo game
        if isSoloGame {
            return "Solo Game"
        }

        // Multiplayer (3+ players): show player count
        if maxturns > 2 {
            return "\(maxturns) players"
        }

        // 2-player game: show just the opponent name
        // playerstring format is typically "You vs Opponent" or "Opponent vs You"
        let parts = playerstring.components(separatedBy: " vs ")
        if parts.count == 2 {
            // Find the part that isn't "You"
            let opponent = parts[0].lowercased() == "you" ? parts[1] : parts[0]
            return opponent
        }

        // Fallback: just show the raw string
        return playerstring
    }
}

enum GameStatus {
    case yourTurn
    case finished
    case waiting
}
