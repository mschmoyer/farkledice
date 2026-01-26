//
//  SessionManager.swift
//  Farkle Ten
//
//  Keychain storage for session credentials
//

import Foundation
import Security

class SessionManager {
    private let service = "com.farkledice.farkle-ten"
    private let sessionKey = "farkle_session_id"
    private let playerIdKey = "farkle_player_id"

    // MARK: - Public Interface

    func saveSession(sessionId: String, playerId: Int) {
        save(key: sessionKey, value: sessionId)
        save(key: playerIdKey, value: String(playerId))
    }

    func getSessionId() -> String? {
        return retrieve(key: sessionKey)
    }

    func getPlayerId() -> Int? {
        guard let value = retrieve(key: playerIdKey) else { return nil }
        return Int(value)
    }

    func clearSession() {
        delete(key: sessionKey)
        delete(key: playerIdKey)
        clearCookies()
    }

    func hasSession() -> Bool {
        return getSessionId() != nil
    }

    // MARK: - Keychain Operations

    private func save(key: String, value: String) {
        guard let data = value.data(using: .utf8) else { return }

        // Delete any existing item first
        delete(key: key)

        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: key,
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlock
        ]

        let status = SecItemAdd(query as CFDictionary, nil)

        if status != errSecSuccess {
            print("SessionManager: Failed to save \(key) to keychain: \(status)")
        }
    }

    private func retrieve(key: String) -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: key,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]

        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)

        guard status == errSecSuccess,
              let data = result as? Data,
              let value = String(data: data, encoding: .utf8) else {
            return nil
        }

        return value
    }

    private func delete(key: String) {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: key
        ]

        SecItemDelete(query as CFDictionary)
    }

    private func clearCookies() {
        // Also clear any stored cookies
        if let url = URL(string: Config.apiURL),
           let cookies = HTTPCookieStorage.shared.cookies(for: url) {
            for cookie in cookies {
                HTTPCookieStorage.shared.deleteCookie(cookie)
            }
        }
    }
}
