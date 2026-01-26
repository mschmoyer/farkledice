//
//  PushNotificationManager.swift
//  Farkle Ten
//
//  Manages push notification permissions, device token registration,
//  and notification handling for APNs integration.
//

import Foundation
import UserNotifications
import UIKit

@MainActor
class PushNotificationManager: NSObject, ObservableObject {
    static let shared = PushNotificationManager()

    @Published var isPermissionGranted: Bool = false
    @Published var deviceToken: String?

    private let apiClient = APIClient()

    private override init() {
        super.init()
    }

    // MARK: - Permission Request

    /// Request notification permission from the user
    func requestPermission() async -> Bool {
        let center = UNUserNotificationCenter.current()

        do {
            let granted = try await center.requestAuthorization(options: [.alert, .badge, .sound])

            await MainActor.run {
                self.isPermissionGranted = granted
            }

            if granted {
                // Register for remote notifications on main thread
                await MainActor.run {
                    UIApplication.shared.registerForRemoteNotifications()
                }
                print("[Push] Permission granted, registered for remote notifications")
            } else {
                print("[Push] Permission denied by user")
            }

            return granted
        } catch {
            print("[Push] Error requesting permission: \(error.localizedDescription)")
            return false
        }
    }

    /// Check current notification permission status
    func checkPermissionStatus() async -> UNAuthorizationStatus {
        let center = UNUserNotificationCenter.current()
        let settings = await center.notificationSettings()

        await MainActor.run {
            self.isPermissionGranted = settings.authorizationStatus == .authorized
        }

        return settings.authorizationStatus
    }

    // MARK: - Device Token Handling

    /// Called when APNs registration succeeds
    func didRegisterForRemoteNotifications(deviceToken: Data) {
        // Convert token data to hex string (without spaces or angle brackets)
        let tokenString = deviceToken.map { String(format: "%02x", $0) }.joined()

        self.deviceToken = tokenString
        print("[Push] Device token received: \(tokenString)")

        // Automatically register with server if we have a session
        Task {
            await registerTokenWithServer()
        }
    }

    /// Called when APNs registration fails
    func didFailToRegisterForRemoteNotifications(error: Error) {
        print("[Push] Failed to register for remote notifications: \(error.localizedDescription)")
        self.deviceToken = nil
    }

    // MARK: - Server Registration

    /// Register device token with the backend server
    func registerTokenWithServer() async {
        guard let token = deviceToken else {
            print("[Push] No device token available for server registration")
            return
        }

        // Get session ID from SessionManager
        let sessionManager = SessionManager()
        guard let sessionId = sessionManager.getSessionId() else {
            print("[Push] No session available for server registration")
            return
        }

        do {
            try await apiClient.registerDeviceToken(token: token, sessionId: sessionId)
            print("[Push] Successfully registered device token with server")
        } catch {
            print("[Push] Failed to register device token with server: \(error.localizedDescription)")
        }
    }

    // MARK: - Notification Handling

    /// Handle notification received while app is in foreground
    func handleForegroundNotification(_ notification: UNNotification) -> UNNotificationPresentationOptions {
        let userInfo = notification.request.content.userInfo
        print("[Push] Received foreground notification: \(userInfo)")

        // Show banner and play sound even when in foreground
        return [.banner, .sound, .badge]
    }

    /// Handle user tapping on a notification
    func handleNotificationResponse(_ response: UNNotificationResponse) {
        let userInfo = response.notification.request.content.userInfo
        print("[Push] User tapped notification: \(userInfo)")

        // TODO: Navigate to specific game if notification contains gameid
        // This would post a notification or update app state to navigate
        if let gameId = userInfo["gameid"] as? Int {
            print("[Push] Should navigate to game: \(gameId)")
            // NotificationCenter.default.post(name: .openGame, object: nil, userInfo: ["gameid": gameId])
        }
    }

    // MARK: - Badge Management

    /// Clear the app badge
    func clearBadge() async {
        await MainActor.run {
            UIApplication.shared.applicationIconBadgeNumber = 0
        }

        // Also clear badge on server
        do {
            let center = UNUserNotificationCenter.current()
            try await center.setBadgeCount(0)
        } catch {
            print("[Push] Error clearing badge: \(error.localizedDescription)")
        }
    }
}
