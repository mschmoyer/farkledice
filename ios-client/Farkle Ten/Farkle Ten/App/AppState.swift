//
//  AppState.swift
//  Farkle Ten
//
//  Global app state for session and user info
//

import Foundation
import SwiftUI
import Combine
import UIKit


@MainActor
class AppState: ObservableObject {
    @Published var isLoggedIn: Bool = false
    @Published var isLoading: Bool = true
    @Published var currentPlayer: Player?
    @Published var lobbyData: LobbyResponse?
    @Published var errorMessage: String?

    private let sessionManager = SessionManager()
    private let apiClient = APIClient()
    private var pollingService: LobbyPollingService?

    init() {
        checkExistingSession()
    }

    // MARK: - Session Management

    func checkExistingSession() {
        isLoading = true

        Task {
            if let sessionId = sessionManager.getSessionId() {
                // Try to fetch lobby with existing session
                do {
                    let lobby = try await apiClient.getLobbyInfo(sessionId: sessionId)
                    self.lobbyData = lobby
                    self.currentPlayer = lobby.player
                    self.isLoggedIn = true
                    self.startPolling()

                    // Re-register push token if we have permission
                    self.checkAndRegisterPushToken()
                } catch {
                    // Session expired or invalid, clear it
                    sessionManager.clearSession()
                    self.isLoggedIn = false
                }
            } else {
                self.isLoggedIn = false
            }
            self.isLoading = false
        }
    }

    func login(username: String, password: String) async throws {
        let response = try await apiClient.login(username: username, password: password)

        // Save session
        sessionManager.saveSession(sessionId: response.sessionId, playerId: response.playerId)

        // Fetch initial lobby data
        let lobby = try await apiClient.getLobbyInfo(sessionId: response.sessionId)

        await MainActor.run {
            self.lobbyData = lobby
            self.currentPlayer = lobby.player
            self.isLoggedIn = true
            self.startPolling()
        }

        // Request push notification permission after successful login
        await requestPushNotificationPermission()
    }

    // MARK: - Push Notifications

    /// Request push notification permission and register token with server
    func requestPushNotificationPermission() async {
        let granted = await PushNotificationManager.shared.requestPermission()
        if granted {
            print("[AppState] Push notification permission granted")
            // Token registration happens automatically in PushNotificationManager
            // when didRegisterForRemoteNotificationsWithDeviceToken is called
        } else {
            print("[AppState] Push notification permission denied")
        }
    }

    /// Re-register push token if we already have permission (called on session restore)
    private func checkAndRegisterPushToken() {
        Task {
            let status = await PushNotificationManager.shared.checkPermissionStatus()
            if status == .authorized {
                // Re-register for remote notifications to get token
                await MainActor.run {
                    UIApplication.shared.registerForRemoteNotifications()
                }
            }
        }
    }

    func logout() {
        pollingService?.stop()
        pollingService = nil
        sessionManager.clearSession()
        lobbyData = nil
        currentPlayer = nil
        isLoggedIn = false
    }

    // MARK: - Polling

    func startPolling() {
        guard let sessionId = sessionManager.getSessionId() else { return }

        pollingService = LobbyPollingService(sessionId: sessionId, apiClient: apiClient)
        pollingService?.onUpdate = { [weak self] lobby in
            Task { @MainActor in
                self?.lobbyData = lobby
                self?.currentPlayer = lobby.player
            }
        }
        pollingService?.onError = { [weak self] error in
            Task { @MainActor in
                // If session expired, log out
                if case APIError.unauthorized = error {
                    self?.logout()
                }
            }
        }
        pollingService?.start()
    }

    func stopPolling() {
        pollingService?.stop()
    }

    func refreshLobby() {
        guard let sessionId = sessionManager.getSessionId() else { return }

        Task {
            do {
                let lobby = try await apiClient.getLobbyInfo(sessionId: sessionId)
                await MainActor.run {
                    self.lobbyData = lobby
                    self.currentPlayer = lobby.player
                }
                // Restart polling after manual refresh
                pollingService?.restart()
            } catch {
                await MainActor.run {
                    self.errorMessage = error.localizedDescription
                }
            }
        }
    }
}
