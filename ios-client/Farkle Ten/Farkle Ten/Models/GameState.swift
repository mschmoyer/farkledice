//
//  GameState.swift
//  Farkle Ten
//
//  Core game state management (ObservableObject)
//

import Foundation
import SwiftUI
import Combine

/// Game state enum matching JavaScript constants
enum GameStateType: Int {
    case loading = 0
    case rolling = 1
    case rolled = 2
    case passed = 3
    case watching = 4
}

/// Fire effect levels based on round score
enum FireEffectLevel {
    case none       // 0-399
    case low        // 400-599: Orange glow, slow pulse
    case medium     // 600-799: Orange-red, faster pulse
    case high       // 800-999: Red, flicker animation
    case intense    // 1000+: White core, intense glow

    static func from(score: Int) -> FireEffectLevel {
        switch score {
        case 0..<400: return .none
        case 400..<600: return .low
        case 600..<800: return .medium
        case 800..<1000: return .high
        default: return .intense
        }
    }
}

/// Main game state observable object
@MainActor
class GameState: ObservableObject {
    // MARK: - Published Properties

    @Published var gameId: Int = 0
    @Published var gameMode: GameMode = .tenRound
    @Published var gameWith: Int = 0  // 0=random, 1=friends, 2=solo
    @Published var dice: [Dice] = .initial()
    @Published var isRolling: Bool = false
    @Published var roundScore: Int = 0  // Accumulated this round (from previous hands)
    @Published var turnScore: Int = 0   // Current selection score
    @Published var currentState: GameStateType = .loading
    @Published var players: [GamePlayer] = []
    @Published var myPlayerId: Int = 0
    @Published var currentPlayerId: Int = 0  // Whose turn it is
    @Published var currentRound: Int = 1
    @Published var currentSet: Int = 0
    @Published var winningPlayer: Int = 0
    @Published var activityLog: [ActivityLogEntry] = []
    @Published var botMessages: [String] = []
    @Published var showFarkleOverlay: Bool = false
    @Published var errorMessage: String?
    @Published var isLoadingData: Bool = false

    // MARK: - Private Properties

    private let apiClient = APIClient()
    private let sessionManager = SessionManager()
    private var pollingTimer: Timer?
    private var pollingTicks: Int = 0

    // MARK: - Computed Properties

    /// Current player's index in the players array
    var myPlayerIndex: Int {
        players.firstIndex { $0.playerId == myPlayerId } ?? -1
    }

    /// Is it currently the local player's turn?
    var isMyTurn: Bool {
        // In 10-round mode, it's always your turn for your own round
        if gameMode == .tenRound {
            return currentPlayerId == myPlayerId || winningPlayer == 0
        }
        return currentPlayerId == myPlayerId && winningPlayer == 0
    }

    /// Total score for display (round + current turn)
    var totalDisplayScore: Int {
        roundScore + turnScore
    }

    /// Fire effect level based on total display score
    var fireLevel: FireEffectLevel {
        FireEffectLevel.from(score: totalDisplayScore)
    }

    /// Is the game finished?
    var isGameFinished: Bool {
        winningPlayer > 0
    }

    /// Current player object
    var currentPlayer: GamePlayer? {
        players.first { $0.playerId == currentPlayerId }
    }

    /// My player object
    var myPlayer: GamePlayer? {
        players.first { $0.playerId == myPlayerId }
    }

    /// Opponent player (for 2-player games)
    var opponent: GamePlayer? {
        players.first { $0.playerId != myPlayerId }
    }

    /// Is the opponent a bot?
    var isPlayingBot: Bool {
        opponent?.isBot ?? false
    }

    /// Can the Roll button be pressed?
    var canRoll: Bool {
        isMyTurn && !isRolling && currentState == .rolled && dice.savedCount > 0
    }

    /// Can the Bank button be pressed?
    var canBank: Bool {
        isMyTurn && !isRolling && currentState == .rolled && roundScore + turnScore > 0
    }

    /// Number of dice remaining to roll
    var diceRemaining: Int {
        dice.filter { !$0.isScored && !$0.isSaved }.count
    }

    /// Is this a "hot dice" situation (all 6 dice scored)?
    var isHotDice: Bool {
        dice.filter { !$0.isScored }.count == 0 && roundScore > 0
    }

    // MARK: - Initialization

    init() {
        if let playerId = sessionManager.getPlayerId() {
            self.myPlayerId = playerId
        }
    }

    // MARK: - Public Methods

    /// Load game from API
    func loadGame(gameId: Int) async {
        self.gameId = gameId
        self.currentState = .loading
        self.isLoadingData = true
        self.errorMessage = nil

        // Reset state
        dice = .initial()
        roundScore = 0
        turnScore = 0
        activityLog = []
        botMessages = []
        showFarkleOverlay = false

        guard let sessionId = sessionManager.getSessionId() else {
            errorMessage = "Session expired"
            isLoadingData = false
            return
        }

        do {
            let response = try await getGameUpdate(sessionId: sessionId, gameId: gameId)
            applyGameUpdate(response)
            isLoadingData = false
            startPollingIfNeeded()
        } catch {
            print("[GameState] loadGame error: \(error)")
            errorMessage = error.localizedDescription
            isLoadingData = false
        }
    }

    /// Roll the dice
    func rollDice() async {
        guard canRoll || currentState == .loading || currentSet == 0 else {
            return
        }

        guard let sessionId = sessionManager.getSessionId() else {
            errorMessage = "Session expired"
            return
        }

        isRolling = true
        currentState = .rolling

        // Build saved dice array for API (matches JS GetDiceValArray)
        // Format: 6 elements, position matters
        // - 0 = die not saved
        // - 1-6 = die saved with this value
        // - 10 = die already scored
        let savedDiceArray = dice.map { die -> Int in
            if die.isScored { return 10 }
            if die.isSaved { return die.value }
            return 0
        }

        // Build new dice array (same format - values of dice being kept)
        let newDiceArray = dice.map { die -> Int in
            if die.isScored { return 10 }
            if die.isSaved { return die.value }
            return 0
        }

        print("[GameState] rollDice: savedDice=\(savedDiceArray), newDice=\(newDiceArray)")

        do {
            let response = try await performRoll(
                sessionId: sessionId,
                gameId: gameId,
                savedDice: savedDiceArray,
                newDice: newDiceArray
            )

            // Small delay for animation
            try? await Task.sleep(nanoseconds: 500_000_000)

            applyRollResponse(response)
            isRolling = false

        } catch {
            print("[GameState] rollDice error: \(error)")
            errorMessage = error.localizedDescription
            isRolling = false
            currentState = .rolled
        }
    }

    /// First roll of a new round
    func initialRoll() async {
        guard isMyTurn else { return }

        guard let sessionId = sessionManager.getSessionId() else {
            errorMessage = "Session expired"
            return
        }

        isRolling = true
        currentState = .rolling

        // First roll - no saved dice
        do {
            let response = try await performRoll(
                sessionId: sessionId,
                gameId: gameId,
                savedDice: [],
                newDice: [0, 0, 0, 0, 0, 0]
            )

            // Animation delay
            try? await Task.sleep(nanoseconds: 500_000_000)

            applyRollResponse(response)
            isRolling = false

        } catch {
            print("[GameState] initialRoll error: \(error)")
            errorMessage = error.localizedDescription
            isRolling = false
        }
    }

    /// Bank the current score
    func bankScore() async {
        guard canBank else { return }

        guard let sessionId = sessionManager.getSessionId() else {
            errorMessage = "Session expired"
            return
        }

        // Build saved dice array (same format as roll)
        let savedDiceArray = dice.map { die -> Int in
            if die.isScored { return 10 }
            if die.isSaved { return die.value }
            return 0
        }

        print("[GameState] bankScore: savedDice=\(savedDiceArray)")

        do {
            let response = try await performPass(
                sessionId: sessionId,
                gameId: gameId,
                savedDice: savedDiceArray
            )

            applyGameUpdate(response)
            currentState = .passed

            // If playing against bot, start bot turn
            if isPlayingBot && !isMyTurn {
                startBotTurn()
            }

        } catch {
            print("[GameState] bankScore error: \(error)")
            errorMessage = error.localizedDescription
        }
    }

    /// Toggle saved state of a die
    func toggleDieSaved(at index: Int) {
        guard isMyTurn && !isRolling && currentState == .rolled else { return }
        guard index >= 0 && index < dice.count else { return }
        guard dice[index].isSelectable else { return }

        dice[index].isSaved.toggle()
        recalculateTurnScore()
    }

    /// Recalculate turn score based on saved dice
    func recalculateTurnScore() {
        let savedValues = dice.savedValues
        turnScore = DiceScoringEngine.scoreDice(savedValues)
    }

    /// Stop polling
    func stopPolling() {
        pollingTimer?.invalidate()
        pollingTimer = nil
        pollingTicks = 0
    }

    // MARK: - Private Methods

    private func applyGameUpdate(_ response: GameUpdateResponse) {
        // Update turn data
        gameMode = response.turnData.gameMode
        gameWith = response.turnData.gameWith
        currentPlayerId = response.turnData.currentPlayer
        currentRound = response.turnData.gameMode == .tenRound
            ? response.turnData.playerRound
            : response.turnData.currentRound
        currentSet = response.turnData.currentSet
        winningPlayer = response.turnData.winningPlayer

        // Update players
        players = response.players
        if let myPlayer = players.first(where: { $0.playerId == myPlayerId }) {
            currentRound = myPlayer.playerround
        }

        // Update dice
        // diceOnTable = extrapolated values for display
        // setData = current roll values (0 = die was scored in previous roll)
        if let diceOnTable = response.diceOnTable, !diceOnTable.isEmpty {
            updateDiceFromTable(diceOnTable, setData: response.setData)
        }

        // Update activity log
        activityLog = response.activityLog

        // Update bot message if present
        if let msg = response.botMessage {
            botMessages.append(msg)
        }

        // Determine game state
        if winningPlayer > 0 {
            currentState = .passed
        } else if isMyTurn {
            if currentSet == 0 {
                currentState = .loading  // Ready for first roll
            } else {
                currentState = .rolled
            }
        } else {
            currentState = .watching
        }

        // Calculate round score from player data
        if let myPlayer = players.first(where: { $0.playerId == myPlayerId }) {
            roundScore = myPlayer.roundscore
        }
    }

    private func applyRollResponse(_ response: GameUpdateResponse) {
        // Check for farkle
        let hasScoringOptions = response.diceOnTable.map {
            DiceScoringEngine.hasScoringDice($0)
        } ?? true

        if !hasScoringOptions && currentSet > 0 {
            // FARKLE!
            showFarkleOverlay = true
            roundScore = 0
            turnScore = 0

            // Reset dice after farkle animation
            Task {
                try? await Task.sleep(nanoseconds: 2_000_000_000)
                showFarkleOverlay = false
                applyGameUpdate(response)
            }
        } else {
            applyGameUpdate(response)
            currentState = .rolled
        }
    }

    /// Update dice from API response
    /// - Parameters:
    ///   - diceOnTable: Extrapolated values for display (includes saved dice from previous rolls)
    ///   - setData: Current roll values (0 = die was scored in previous roll, > 0 = die is available)
    ///
    /// Logic matches JS: farkleUpdateDice(i, gDiceOnTable[i], ((gDiceData[i] > 0) ? 0 : 1))
    /// - isScored = true when setData[i] == 0 (die was saved in previous roll)
    /// - value = diceOnTable[i] (the extrapolated value to display)
    private func updateDiceFromTable(_ diceOnTable: [Int], setData: [Int]?) {
        print("[GameState] updateDiceFromTable: diceOnTable=\(diceOnTable), setData=\(setData ?? [])")

        for i in 0..<6 {
            let displayValue = i < diceOnTable.count ? diceOnTable[i] : 0
            let setDataValue = (setData != nil && i < setData!.count) ? setData![i] : displayValue

            // Die is scored (greyed out) when setData value is 0
            // This means it was saved in a previous roll and is no longer part of current set
            let isScored = setDataValue == 0 && displayValue > 0

            dice[i] = Dice(
                id: i,
                value: displayValue,
                isSaved: false,
                isScored: isScored
            )
        }

        print("[GameState] Dice after update: \(dice.map { "v\($0.value)s\($0.isScored ? 1 : 0)" }.joined(separator: ", "))")
        recalculateTurnScore()
    }

    private func startPollingIfNeeded() {
        // Only poll when waiting for opponent
        guard !isMyTurn && winningPlayer == 0 else {
            stopPolling()
            return
        }

        pollingTicks = 0
        pollingTimer = Timer.scheduledTimer(withTimeInterval: 5.0, repeats: true) { [weak self] _ in
            Task { @MainActor in
                await self?.pollForUpdate()
            }
        }
    }

    private func pollForUpdate() async {
        guard let sessionId = sessionManager.getSessionId() else { return }

        pollingTicks += 1

        // Slow down polling after 20 ticks
        if pollingTicks > 20 {
            pollingTimer?.invalidate()
            pollingTimer = Timer.scheduledTimer(withTimeInterval: 10.0, repeats: true) { [weak self] _ in
                Task { @MainActor in
                    await self?.pollForUpdate()
                }
            }
        }

        // Stop polling after 40 ticks
        if pollingTicks > 40 {
            stopPolling()
            return
        }

        do {
            let response = try await getGameUpdate(sessionId: sessionId, gameId: gameId)
            applyGameUpdate(response)

            // If it became our turn, stop polling
            if isMyTurn {
                stopPolling()
            }
        } catch {
            // Ignore polling errors
        }
    }

    private func startBotTurn() {
        // Poll for bot steps
        Task {
            await pollBotSteps()
        }
    }

    private func pollBotSteps() async {
        guard let sessionId = sessionManager.getSessionId(),
              let botPlayer = opponent,
              botPlayer.isBot else { return }

        while !isMyTurn && winningPlayer == 0 {
            do {
                let stepResponse = try await executeBotStep(
                    sessionId: sessionId,
                    gameId: gameId,
                    botPlayerId: botPlayer.playerId
                )

                // Add bot message if present
                if let message = stepResponse.message {
                    botMessages.append(message)
                }

                // Update dice display for bot step
                if let diceOnTable = stepResponse.diceOnTable {
                    for (i, value) in diceOnTable.enumerated() where i < 6 {
                        dice[i].value = value
                    }
                }

                // Animation delay between steps
                try await Task.sleep(nanoseconds: 1_200_000_000)

                // If turn complete, refresh game state
                if stepResponse.turnComplete {
                    let response = try await getGameUpdate(sessionId: sessionId, gameId: gameId)
                    applyGameUpdate(response)
                    break
                }
            } catch {
                // Bot step error, just refresh
                try? await Task.sleep(nanoseconds: 1_000_000_000)
                if let response = try? await getGameUpdate(sessionId: sessionId, gameId: gameId) {
                    applyGameUpdate(response)
                }
                break
            }
        }
    }

    // MARK: - API Calls

    private func getGameUpdate(sessionId: String, gameId: Int) async throws -> GameUpdateResponse {
        let params: [String: String] = [
            "action": "farklegetupdate",
            "gameid": String(gameId),
            "iossessionid": sessionId
        ]

        let data = try await apiClient.postRequest(params: params)

        guard let jsonArray = try? JSONSerialization.jsonObject(with: data) as? [Any] else {
            throw APIError.invalidResponse
        }

        // Handle nested array structure from backend
        let actualArray = unwrapIfNeeded(jsonArray)
        return try GameUpdateResponse(from: actualArray)
    }

    private func performRoll(sessionId: String, gameId: Int, savedDice: [Int], newDice: [Int]) async throws -> GameUpdateResponse {
        let savedJson = try JSONSerialization.data(withJSONObject: savedDice)
        let newJson = try JSONSerialization.data(withJSONObject: newDice)

        let params: [String: String] = [
            "action": "farkleroll",
            "gameid": String(gameId),
            "saveddice": String(data: savedJson, encoding: .utf8) ?? "[]",
            "newdice": String(data: newJson, encoding: .utf8) ?? "[]",
            "iossessionid": sessionId
        ]

        let data = try await apiClient.postRequest(params: params)

        guard let jsonArray = try? JSONSerialization.jsonObject(with: data) as? [Any] else {
            throw APIError.invalidResponse
        }

        // Roll response format: [FarkleSendUpdate, newDiceScore, setScore, numDiceSaved, diceArray]
        // - [0] = FarkleSendUpdate (8-element array with game state)
        // - [4] = Actual new dice values [d1, d2, d3, d4, d5, d6]
        // We need to extract both and use the dice from [4] as the authoritative values

        var response = try GameUpdateResponse(from: unwrapIfNeeded(jsonArray))

        // Extract dice values from outer array index [4] if present
        // This is the authoritative dice values from the roll
        if jsonArray.count >= 5,
           let diceArray = jsonArray[4] as? [Any] {
            let newDiceValues = diceArray.compactMap { value -> Int? in
                if let intVal = value as? Int { return intVal }
                if let strVal = value as? String { return Int(strVal) }
                return nil
            }
            print("[GameState] performRoll: extracted dice from [4]: \(newDiceValues)")

            // Create new response with the actual dice values
            response = GameUpdateResponse(
                turnData: response.turnData,
                players: response.players,
                setData: newDiceValues,  // Use the new dice values as setData
                diceOnTable: newDiceValues,  // And as diceOnTable (all dice are fresh)
                achievement: response.achievement,
                gameId: response.gameId,
                levelUp: response.levelUp,
                activityLog: response.activityLog,
                botMessage: response.botMessage
            )
        }

        return response
    }

    private func performPass(sessionId: String, gameId: Int, savedDice: [Int]) async throws -> GameUpdateResponse {
        let savedJson = try JSONSerialization.data(withJSONObject: savedDice)

        let params: [String: String] = [
            "action": "farklepass",
            "gameid": String(gameId),
            "saveddice": String(data: savedJson, encoding: .utf8) ?? "[]",
            "iossessionid": sessionId
        ]

        let data = try await apiClient.postRequest(params: params)

        guard let jsonArray = try? JSONSerialization.jsonObject(with: data) as? [Any] else {
            throw APIError.invalidResponse
        }

        // Handle double-wrapped response [[...]] if backend returns it that way
        let actualArray = unwrapIfNeeded(jsonArray)
        return try GameUpdateResponse(from: actualArray)
    }

    private func executeBotStep(sessionId: String, gameId: Int, botPlayerId: Int) async throws -> BotStepResponse {
        let params: [String: String] = [
            "action": "executebotstep",
            "gameid": String(gameId),
            "botplayerid": String(botPlayerId),
            "iossessionid": sessionId
        ]

        let data = try await apiClient.postRequest(params: params)

        guard let dict = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw APIError.invalidResponse
        }

        return BotStepResponse(from: dict)
    }

    /// Unwrap nested array structure if the backend returns it that way
    private func unwrapIfNeeded(_ jsonArray: [Any]) -> [Any] {
        // Check if first element is an array (nested structure)
        // Structure: [[turnData, players, ...], 0, 0, 0, diceOnTable]
        if let firstElement = jsonArray.first,
           let innerArray = firstElement as? [Any],
           innerArray.count >= 6,
           innerArray.first is [String: Any] {
            print("[GameState] Detected nested structure, using inner array with \(innerArray.count) elements")
            return innerArray
        }

        // If the array has exactly one element and that element is itself an array,
        // it's double-wrapped - return the inner array
        if jsonArray.count == 1, let inner = jsonArray[0] as? [Any] {
            print("[GameState] Detected double-wrapped array, unwrapping \(jsonArray.count) -> \(inner.count) elements")
            return inner
        }

        print("[GameState] Array has \(jsonArray.count) elements, using as-is")
        return jsonArray
    }
}
