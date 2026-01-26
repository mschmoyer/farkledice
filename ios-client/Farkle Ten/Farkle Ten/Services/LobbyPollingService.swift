//
//  LobbyPollingService.swift
//  Farkle Ten
//
//  Polling service that matches web behavior:
//  - Ticks 1-20: Poll every 10 seconds
//  - Ticks 21-40: Poll every 20 seconds
//  - After 40 ticks: Go idle, require manual refresh
//

import Foundation

class LobbyPollingService {
    private let sessionId: String
    private let apiClient: APIClient

    private var timer: Timer?
    private var tickCount: Int = 0
    private var isPolling: Bool = false

    // Callbacks
    var onUpdate: ((LobbyResponse) -> Void)?
    var onError: ((Error) -> Void)?
    var onIdle: (() -> Void)?

    // Configuration
    private let fastInterval: TimeInterval = 10.0  // First 20 ticks
    private let slowInterval: TimeInterval = 20.0  // Ticks 21-40
    private let maxTicks: Int = 40                  // Go idle after this

    init(sessionId: String, apiClient: APIClient) {
        self.sessionId = sessionId
        self.apiClient = apiClient
    }

    deinit {
        stop()
    }

    // MARK: - Public Interface

    func start() {
        guard !isPolling else { return }

        isPolling = true
        tickCount = 0

        // Immediate first poll
        poll()

        // Schedule timer
        scheduleNextPoll()
    }

    func stop() {
        isPolling = false
        timer?.invalidate()
        timer = nil
    }

    func restart() {
        stop()
        start()
    }

    // MARK: - Private

    private func scheduleNextPoll() {
        guard isPolling, tickCount < maxTicks else {
            if tickCount >= maxTicks {
                isPolling = false
                onIdle?()
            }
            return
        }

        let interval = tickCount < 20 ? fastInterval : slowInterval

        timer = Timer.scheduledTimer(withTimeInterval: interval, repeats: false) { [weak self] _ in
            self?.poll()
        }
    }

    private func poll() {
        guard isPolling else { return }

        tickCount += 1

        Task {
            do {
                let lobby = try await apiClient.getLobbyInfo(sessionId: sessionId)
                await MainActor.run {
                    self.onUpdate?(lobby)
                }
            } catch {
                await MainActor.run {
                    self.onError?(error)
                }
            }

            await MainActor.run {
                self.scheduleNextPoll()
            }
        }
    }

    var isActive: Bool {
        return isPolling && tickCount < maxTicks
    }

    var currentTick: Int {
        return tickCount
    }
}
