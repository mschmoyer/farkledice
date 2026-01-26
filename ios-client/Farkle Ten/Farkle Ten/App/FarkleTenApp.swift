//
//  FarkleTenApp.swift
//  Farkle Ten
//
//  SwiftUI App entry point
//

import SwiftUI

@main
struct FarkleTenApp: App {
    // Connect UIKit AppDelegate for push notification handling
    @UIApplicationDelegateAdaptor(AppDelegate.self) var appDelegate

    @StateObject private var appState = AppState()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(appState)
        }
    }
}

// MARK: - Root Content View

struct ContentView: View {
    @EnvironmentObject var appState: AppState

    var body: some View {
        Group {
            if appState.isLoading {
                LoadingView()
            } else if appState.isLoggedIn {
                LobbyView()
            } else {
                LoginView()
            }
        }
        .animation(.easeInOut(duration: 0.3), value: appState.isLoggedIn)
        .animation(.easeInOut(duration: 0.3), value: appState.isLoading)
    }
}

// MARK: - Loading View

struct LoadingView: View {
    var body: some View {
        ZStack {
            FeltBackground()

            VStack(spacing: 20) {
                ProgressView()
                    .progressViewStyle(CircularProgressViewStyle(tint: FarkleColors.gold))
                    .scaleEffect(1.5)

                Text("Loading...")
                    .font(FarkleTypography.body)
                    .foregroundColor(FarkleColors.textSecondary)
            }
        }
    }
}

#Preview("Logged Out") {
    ContentView()
        .environmentObject(AppState())
}
