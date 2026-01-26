//
//  LoginView.swift
//  Farkle Ten
//
//  Username/password login screen
//

import SwiftUI

struct LoginView: View {
    @EnvironmentObject var appState: AppState

    @State private var username: String = ""
    @State private var password: String = ""
    @State private var isLoading: Bool = false
    @State private var errorMessage: String?
    @State private var showEnvironmentPicker: Bool = false

    var body: some View {
        GeometryReader { geometry in
            ScrollView {
                VStack(spacing: 32) {
                    Spacer()
                        .frame(height: geometry.size.height * 0.1)

                    // Logo/Title
                    VStack(spacing: 8) {
                        Text("FARKLE")
                            .font(.system(size: 48, weight: .bold))
                            .foregroundColor(FarkleColors.gold)

                        Text("TEN")
                            .font(.system(size: 32, weight: .semibold))
                            .foregroundColor(.white)

                        Text("The Classic Dice Game")
                            .font(FarkleTypography.caption)
                            .foregroundColor(FarkleColors.textSecondary)
                    }

                    Spacer()
                        .frame(height: 20)

                    // Login Form
                    VStack(spacing: 16) {
                        // Username field
                        VStack(alignment: .leading, spacing: 6) {
                            Text("Username")
                                .font(FarkleTypography.caption)
                                .foregroundColor(FarkleColors.textSecondary)

                            TextField("", text: $username)
                                .textFieldStyle(FarkleTextFieldStyle())
                                .textContentType(.username)
                                .autocapitalization(.none)
                                .disableAutocorrection(true)
                        }

                        // Password field
                        VStack(alignment: .leading, spacing: 6) {
                            Text("Password")
                                .font(FarkleTypography.caption)
                                .foregroundColor(FarkleColors.textSecondary)

                            SecureField("", text: $password)
                                .textFieldStyle(FarkleTextFieldStyle())
                                .textContentType(.password)
                        }

                        // Error message
                        if let error = errorMessage {
                            Text(error)
                                .font(FarkleTypography.caption)
                                .foregroundColor(FarkleColors.gameLost)
                                .multilineTextAlignment(.center)
                        }

                        // Login button
                        FarkleButton("Sign In", style: .primary, isLoading: isLoading) {
                            login()
                        }
                        .disabled(username.isEmpty || password.isEmpty)
                    }
                    .padding(.horizontal, 32)

                    Spacer()

                    // Environment indicator (debug only)
                    #if DEBUG
                    VStack(spacing: 8) {
                        Button(action: { showEnvironmentPicker.toggle() }) {
                            HStack {
                                Circle()
                                    .fill(Config.environment == .localhost ? Color.orange : Color.green)
                                    .frame(width: 8, height: 8)

                                Text(Config.environment.displayName)
                                    .font(FarkleTypography.micro)
                                    .foregroundColor(FarkleColors.textMuted)
                            }
                        }

                        if showEnvironmentPicker {
                            HStack(spacing: 16) {
                                ForEach(APIEnvironment.allCases, id: \.rawValue) { env in
                                    Button(action: {
                                        Config.environment = env
                                        showEnvironmentPicker = false
                                    }) {
                                        Text(env.displayName)
                                            .font(FarkleTypography.micro)
                                            .foregroundColor(Config.environment == env ? FarkleColors.gold : FarkleColors.textSecondary)
                                    }
                                }
                            }
                        }
                    }
                    .padding(.bottom, 20)
                    #endif
                }
                .frame(minHeight: geometry.size.height)
            }
        }
        .feltBackground()
    }

    private func login() {
        guard !username.isEmpty, !password.isEmpty else { return }

        isLoading = true
        errorMessage = nil

        Task {
            do {
                try await appState.login(username: username, password: password)
            } catch let error as APIError {
                await MainActor.run {
                    errorMessage = error.localizedDescription
                    isLoading = false
                }
            } catch {
                await MainActor.run {
                    errorMessage = "An unexpected error occurred"
                    isLoading = false
                }
            }
        }
    }
}

// MARK: - Custom TextField Style

struct FarkleTextFieldStyle: TextFieldStyle {
    func _body(configuration: TextField<Self._Label>) -> some View {
        configuration
            .padding(.horizontal, 16)
            .padding(.vertical, 14)
            .background(Color.black.opacity(0.3))
            .foregroundColor(.white)
            .cornerRadius(10)
            .overlay(
                RoundedRectangle(cornerRadius: 10)
                    .stroke(FarkleColors.cardBorder, lineWidth: 1)
            )
    }
}

#Preview {
    LoginView()
        .environmentObject(AppState())
}
