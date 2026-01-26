//
//  APIClient.swift
//  Farkle Ten
//
//  HTTP client for farkle_fetch.php API
//

import Foundation
import CryptoKit

enum APIError: Error, LocalizedError {
    case invalidURL
    case invalidResponse
    case networkError(Error)
    case serverError(String)
    case unauthorized
    case decodingError(Error)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid API URL"
        case .invalidResponse:
            return "Invalid server response"
        case .networkError(let error):
            return "Network error: \(error.localizedDescription)"
        case .serverError(let message):
            return message
        case .unauthorized:
            return "Session expired. Please login again."
        case .decodingError(let error):
            return "Failed to parse response: \(error.localizedDescription)"
        }
    }
}

struct LoginResponse {
    let sessionId: String
    let playerId: Int
    let username: String
}

class APIClient {
    private let session: URLSession

    init() {
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 30
        config.timeoutIntervalForResource = 60
        self.session = URLSession(configuration: config)
    }

    // MARK: - Login

    func login(username: String, password: String) async throws -> LoginResponse {
        // The API expects MD5 hash of username and password
        let userHash = md5(username)
        let passHash = md5(password)

        let params: [String: String] = [
            "action": "login",
            "user": userHash,
            "pass": passHash,
            "remember": "1"
        ]

        let data = try await postRequest(params: params)

        // Parse response
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw APIError.invalidResponse
        }

        // Check for error
        if let error = json["Error"] as? String {
            throw APIError.serverError(error)
        }

        // The login response contains player info
        // We need to get the session ID from cookies or response
        // Since PHP sets a cookie, we need to extract it
        guard let playerId = (json["playerid"] as? Int) ?? Int(json["playerid"] as? String ?? "") else {
            throw APIError.invalidResponse
        }

        guard let username = json["username"] as? String else {
            throw APIError.invalidResponse
        }

        // Get session from response or generate one
        // The backend sets farklesession cookie which we need to capture
        // For now, we'll generate a local session and use it
        // The actual session comes from cookies set by the server

        // Extract session from cookies
        if let sessionId = extractSessionFromCookies() {
            return LoginResponse(sessionId: sessionId, playerId: playerId, username: username)
        }

        // Fallback: Check if sessionid is in response
        if let sessionId = json["sessionid"] as? String {
            return LoginResponse(sessionId: sessionId, playerId: playerId, username: username)
        }

        throw APIError.invalidResponse
    }

    // MARK: - Lobby Info

    func getLobbyInfo(sessionId: String) async throws -> LobbyResponse {
        let params: [String: String] = [
            "action": "getlobbyinfo",
            "iossessionid": sessionId
        ]

        let data = try await postRequest(params: params)

        // Check if response is "0" (unauthorized)
        if let responseStr = String(data: data, encoding: .utf8), responseStr.trimmingCharacters(in: .whitespacesAndNewlines) == "0" {
            throw APIError.unauthorized
        }

        // Parse as JSON array
        guard let jsonArray = try? JSONSerialization.jsonObject(with: data) as? [Any] else {
            // Try as dictionary (error response)
            if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
               let error = json["Error"] as? String {
                throw APIError.serverError(error)
            }
            throw APIError.invalidResponse
        }

        do {
            return try LobbyResponse(from: jsonArray)
        } catch {
            throw APIError.decodingError(error)
        }
    }

    // MARK: - Push Notification Token

    /// Register device token for push notifications with the backend
    /// Uses the existing action=iphonetoken endpoint
    func registerDeviceToken(token: String, sessionId: String) async throws {
        let params: [String: String] = [
            "action": "iphonetoken",
            "iossessionid": sessionId,
            "devicetoken": token,
            "device": "ios_app"
        ]

        let data = try await postRequest(params: params)

        // Check response - server returns "1" on success, "0" on failure
        if let responseStr = String(data: data, encoding: .utf8) {
            let trimmed = responseStr.trimmingCharacters(in: .whitespacesAndNewlines)
            if trimmed == "0" {
                throw APIError.serverError("Failed to register device token")
            }
            // "1" or any other positive response is success
            print("[API] Device token registered successfully")
        }
    }

    // MARK: - Internal Helpers

    func postRequest(params: [String: String]) async throws -> Data {
        guard let url = URL(string: Config.apiURL) else {
            print("[API] ERROR: Invalid URL")
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        request.setValue("FarkleTen-iOS/1.0", forHTTPHeaderField: "User-Agent")

        // Encode parameters
        let bodyString = params.map { key, value in
            let escapedKey = key.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? key
            let escapedValue = value.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? value
            return "\(escapedKey)=\(escapedValue)"
        }.joined(separator: "&")

        request.httpBody = bodyString.data(using: .utf8)

        print("[API] Request: \(params["action"] ?? "unknown") to \(url)")

        do {
            let (data, response) = try await session.data(for: request)

            // Log full response for debugging
            if let responseStr = String(data: data, encoding: .utf8) {
                print("[API] Response (\(data.count) bytes):\n\(responseStr)")
            }

            guard let httpResponse = response as? HTTPURLResponse else {
                throw APIError.invalidResponse
            }

            // Store cookies for session extraction
            if let headerFields = httpResponse.allHeaderFields as? [String: String],
               let url = httpResponse.url {
                let cookies = HTTPCookie.cookies(withResponseHeaderFields: headerFields, for: url)
                HTTPCookieStorage.shared.setCookies(cookies, for: url, mainDocumentURL: nil)
            }

            guard (200...299).contains(httpResponse.statusCode) else {
                print("[API] ERROR: Server returned status \(httpResponse.statusCode)")
                throw APIError.serverError("Server returned status \(httpResponse.statusCode)")
            }

            return data
        } catch let error as APIError {
            print("[API] ERROR: \(error.localizedDescription ?? "unknown")")
            throw error
        } catch {
            print("[API] ERROR: Network error - \(error.localizedDescription)")
            throw APIError.networkError(error)
        }
    }

    private func extractSessionFromCookies() -> String? {
        guard let url = URL(string: Config.apiURL) else { return nil }

        if let cookies = HTTPCookieStorage.shared.cookies(for: url) {
            for cookie in cookies {
                if cookie.name == "farklesession" {
                    return cookie.value
                }
            }
        }
        return nil
    }

    private func md5(_ string: String) -> String {
        let data = Data(string.utf8)
        let hash = Insecure.MD5.hash(data: data)
        return hash.map { String(format: "%02x", $0) }.joined()
    }
}
