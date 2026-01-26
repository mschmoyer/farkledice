//
//  Config.swift
//  Farkle Ten
//
//  Configuration for API environment switching
//

import Foundation

enum APIEnvironment: String, CaseIterable {
    case localhost
    case production

    var baseURL: String {
        switch self {
        case .localhost:
            return "http://localhost:8080/farkle_fetch.php"
        case .production:
            return "https://www.farkledice.com/farkle_fetch.php"
        }
    }

    var displayName: String {
        switch self {
        case .localhost:
            return "Localhost (Docker)"
        case .production:
            return "Production"
        }
    }
}

struct Config {
    // Toggle this to switch between localhost and production
    // For release builds, always use production
    #if DEBUG
    static var environment: APIEnvironment = .localhost
    #else
    static var environment: APIEnvironment = .production
    #endif

    static var apiURL: String {
        return environment.baseURL
    }
}
