//
//  GameResponse.swift
//  Farkle Ten
//
//  Parser for game update API response
//

import Foundation

/// Response from farklegetupdate action
/// API returns: [turnData, playerData, setData, diceOnTable, achievement, gameid, levelUp, activityLog]
///
/// setData[i] = current roll's die value (0 means die was saved/scored in previous roll)
/// diceOnTable[i] = extrapolated value for display (includes saved dice from previous rolls)
struct GameUpdateResponse {
    let turnData: TurnData
    let players: [GamePlayer]
    let setData: [Int]?      // Raw dice from current set (0 = already scored)
    let diceOnTable: [Int]?  // Extrapolated dice for display
    let achievement: AchievementData?
    let gameId: Int
    let levelUp: LevelUpData?
    let activityLog: [ActivityLogEntry]
    let botMessage: String?

    init(from jsonArray: [Any]) throws {
        print("[GameUpdateResponse] Parsing array with \(jsonArray.count) elements")

        guard jsonArray.count >= 6 else {
            print("[GameUpdateResponse] ERROR: Array too short, expected >= 6, got \(jsonArray.count)")
            print("[GameUpdateResponse] Array contents: \(jsonArray)")
            throw APIError.invalidResponse
        }

        // [0] Turn data
        guard let turnDict = jsonArray[0] as? [String: Any] else {
            print("[GameUpdateResponse] ERROR: Element [0] is not a dictionary")
            print("[GameUpdateResponse] Element [0] type: \(type(of: jsonArray[0]))")
            print("[GameUpdateResponse] Element [0] value: \(jsonArray[0])")
            throw APIError.invalidResponse
        }
        print("[GameUpdateResponse] Parsed turnDict with keys: \(turnDict.keys.joined(separator: ", "))")
        self.turnData = TurnData(from: turnDict)

        // [1] Player data array
        if let playerArray = jsonArray[1] as? [[String: Any]] {
            self.players = playerArray.map { GamePlayer(from: $0) }
            print("[GameUpdateResponse] Parsed \(players.count) players")
        } else {
            print("[GameUpdateResponse] WARNING: Element [1] is not a player array, type: \(type(of: jsonArray[1]))")
            self.players = []
        }

        // [2] Set data - array of 6 die values from current roll
        // Value > 0 means die is on table (not scored); value == 0 means die was scored in previous roll
        if let setArray = jsonArray[2] as? [Any] {
            self.setData = setArray.compactMap { Self.parseIntValue($0) }
            print("[GameUpdateResponse] Parsed setData: \(self.setData ?? [])")
        } else {
            print("[GameUpdateResponse] WARNING: Element [2] is not an array, type: \(type(of: jsonArray[2]))")
            self.setData = nil
        }

        // [3] Dice on table - extrapolated values for display (includes saved dice)
        if let diceArray = jsonArray[3] as? [Any] {
            self.diceOnTable = diceArray.compactMap { Self.parseIntValue($0) }
            print("[GameUpdateResponse] Parsed diceOnTable: \(self.diceOnTable ?? [])")
        } else {
            self.diceOnTable = nil
        }

        // [4] Achievement (can be nil)
        if let achDict = jsonArray[4] as? [String: Any] {
            self.achievement = AchievementData(from: achDict)
        } else {
            self.achievement = nil
        }

        // [5] Game ID
        self.gameId = Self.parseIntValue(jsonArray[5]) ?? 0

        // [6] Level up (can be nil)
        if jsonArray.count > 6, let levelDict = jsonArray[6] as? [String: Any] {
            self.levelUp = LevelUpData(from: levelDict)
        } else {
            self.levelUp = nil
        }

        // [7] Activity log (can be nil)
        if jsonArray.count > 7, let logArray = jsonArray[7] as? [[String: Any]] {
            self.activityLog = logArray.enumerated().map { ActivityLogEntry(from: $1, index: $0) }
        } else {
            self.activityLog = []
        }

        // Bot message may be in turn data or separate
        self.botMessage = turnDict["bot_message"] as? String

    }

    /// Memberwise initializer for creating modified responses
    init(
        turnData: TurnData,
        players: [GamePlayer],
        setData: [Int]?,
        diceOnTable: [Int]?,
        achievement: AchievementData?,
        gameId: Int,
        levelUp: LevelUpData?,
        activityLog: [ActivityLogEntry],
        botMessage: String?
    ) {
        self.turnData = turnData
        self.players = players
        self.setData = setData
        self.diceOnTable = diceOnTable
        self.achievement = achievement
        self.gameId = gameId
        self.levelUp = levelUp
        self.activityLog = activityLog
        self.botMessage = botMessage
    }

    private static func parseIntValue(_ value: Any?) -> Int? {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return nil
    }
}

/// Turn/game state data from response
struct TurnData {
    let currentTurn: Int  // Which player's turn (1-based)
    let currentRound: Int
    let maxTurns: Int  // Number of players
    let winningPlayer: Int  // 0 if game ongoing
    let minToStart: Int
    let pointsToWin: Int
    let gameId: Int
    let gameMode: GameMode
    let gameWith: Int
    let playerRound: Int
    let gameExpire: String?
    let gameFinish: String?
    let currentPlayer: Int  // Player ID whose turn it is
    let lastTurn: Int
    let titleRedeemed: Int
    let winAcknowledged: Bool
    let currentSet: Int  // Current set number in this round

    init(from dict: [String: Any]) {
        self.currentTurn = Self.parseIntValue(dict["currentturn"])
        self.currentRound = Self.parseIntValue(dict["currentround"])
        self.maxTurns = Self.parseIntValue(dict["maxturns"])
        self.winningPlayer = Self.parseIntValue(dict["winningplayer"])
        self.minToStart = Self.parseIntValue(dict["mintostart"])
        self.pointsToWin = Self.parseIntValue(dict["pointstowin"])
        self.gameId = Self.parseIntValue(dict["gameid"])

        let modeRaw = Self.parseIntValue(dict["gamemode"])
        self.gameMode = GameMode(rawValue: modeRaw) ?? .tenRound

        self.gameWith = Self.parseIntValue(dict["gamewith"])
        self.playerRound = Self.parseIntValue(dict["playerround"])
        self.gameExpire = dict["gameexpire"] as? String
        self.gameFinish = dict["gamefinish"] as? String
        self.currentPlayer = Self.parseIntValue(dict["currentplayer"])
        self.lastTurn = Self.parseIntValue(dict["lastturn"])
        self.titleRedeemed = Self.parseIntValue(dict["titleredeemed"])
        self.winAcknowledged = Self.parseBoolValue(dict["winacknowledged"])
        self.currentSet = Self.parseIntValue(dict["currentset"])
    }

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

/// Dice state data (d1-d6 values and saved status)
struct DiceData {
    let values: [Int]  // d1-d6 values
    let saved: [Bool]  // d1save-d6save status

    init(from dict: [String: Any]) {
        var vals: [Int] = []
        var savs: [Bool] = []

        for i in 1...6 {
            vals.append(Self.parseIntValue(dict["d\(i)"]))
            savs.append(Self.parseIntValue(dict["d\(i)save"]) > 0)
        }

        self.values = vals
        self.saved = savs
    }

    private static func parseIntValue(_ value: Any?) -> Int {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return 0
    }
}

/// Achievement notification data
struct AchievementData {
    let achievementId: Int
    let name: String
    let description: String
    let xpReward: Int
    let imageFile: String?

    init(from dict: [String: Any]) {
        self.achievementId = Self.parseIntValue(dict["achievementid"])
        self.name = dict["name"] as? String ?? ""
        self.description = dict["description"] as? String ?? ""
        self.xpReward = Self.parseIntValue(dict["xp_reward"])
        self.imageFile = dict["imagefile"] as? String
    }

    private static func parseIntValue(_ value: Any?) -> Int {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return 0
    }
}

/// Level up notification data
struct LevelUpData {
    let newLevel: Int
    let xpToNext: Int

    init(from dict: [String: Any]) {
        self.newLevel = Self.parseIntValue(dict["newlevel"])
        self.xpToNext = Self.parseIntValue(dict["xp_to_level"])
    }

    private static func parseIntValue(_ value: Any?) -> Int {
        if let intVal = value as? Int {
            return intVal
        } else if let strVal = value as? String, let parsed = Int(strVal) {
            return parsed
        }
        return 0
    }
}

/// Response from farkleroll action
struct RollResponse {
    let turnData: TurnData
    let players: [GamePlayer]
    let setData: [Int]?
    let diceOnTable: [Int]?
    let achievement: AchievementData?
    let gameId: Int
    let levelUp: LevelUpData?
    let activityLog: [ActivityLogEntry]
    let isFarkle: Bool
    let botMessage: String?

    init(from jsonArray: [Any]) throws {
        // Roll response has same structure as update, plus farkle indicator
        let updateResponse = try GameUpdateResponse(from: jsonArray)

        self.turnData = updateResponse.turnData
        self.players = updateResponse.players
        self.setData = updateResponse.setData
        self.diceOnTable = updateResponse.diceOnTable
        self.achievement = updateResponse.achievement
        self.gameId = updateResponse.gameId
        self.levelUp = updateResponse.levelUp
        self.activityLog = updateResponse.activityLog
        self.botMessage = updateResponse.botMessage

        // Check if this was a farkle (set score would be 0 with dice showing)
        // Farkle is detected when we have dice on table but no scoring options
        self.isFarkle = false  // Will be determined by game state
    }
}

/// Response from bot step execution
struct BotStepResponse {
    let action: String  // "roll", "keep", "bank", "farkle"
    let diceKept: [Int]?
    let turnScore: Int
    let roundScore: Int
    let message: String?
    let turnComplete: Bool
    let diceOnTable: [Int]?

    init(from dict: [String: Any]) {
        self.action = dict["action"] as? String ?? ""
        if let kept = dict["dice_kept"] as? [Any] {
            self.diceKept = kept.compactMap { Self.parseIntValue($0) }
        } else {
            self.diceKept = nil
        }
        self.turnScore = Self.parseIntValue(dict["turn_score"]) ?? 0
        self.roundScore = Self.parseIntValue(dict["round_score"]) ?? 0
        self.message = dict["message"] as? String
        self.turnComplete = Self.parseBoolValue(dict["turn_complete"])
        if let dice = dict["dice_on_table"] as? [Any] {
            self.diceOnTable = dice.compactMap { Self.parseIntValue($0) }
        } else {
            self.diceOnTable = nil
        }
    }

    private static func parseIntValue(_ value: Any?) -> Int? {
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
