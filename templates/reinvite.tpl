<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Farkle Ten</title>
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon"/>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .reinvite-container {
            max-width: 450px;
            width: 100%;
        }
        .reinvite-box {
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 180px;
            height: auto;
        }
        .reinvite-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #fff;
        }
        .reinvite-subtitle {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .username-display {
            background: rgba(26, 122, 26, 0.3);
            border: 1px solid #1a7a1a;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            font-size: 18px;
            color: #8fbc8f;
        }
        .username-label {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #ccc;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #444;
            border-radius: 6px;
            background: #222;
            color: #fff;
        }
        .form-input:focus {
            outline: none;
            border-color: #1a7a1a;
        }
        .submit-button {
            width: 100%;
            padding: 14px 20px;
            font-size: 18px;
            background: linear-gradient(135deg, #228b22 0%, #1a7a1a 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 122, 26, 0.4);
        }
        .submit-button:active {
            transform: translateY(0);
        }
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #ff6b6b;
            font-size: 14px;
        }
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #6fcf97;
            font-size: 14px;
        }
        .home-link {
            display: inline-block;
            margin-top: 20px;
            color: #7db9e8;
            text-decoration: none;
            font-size: 14px;
        }
        .home-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reinvite-container">
        <div class="reinvite-box">
            <div class="logo">
                <img src="/images/farkle_ten_logo.png" alt="Farkle Ten">
            </div>

            {if $error}
                <div class="error-message">
                    {$error}
                </div>
                <a href="/farkle.php" class="home-link">Go to Farkle Ten</a>
            {elseif $success}
                <div class="success-message">
                    {$success}
                </div>
                <a href="/farkle.php" class="home-link">Go to Farkle Ten</a>
            {elseif $tokenValid}
                <h1 class="reinvite-title">Reset Your Password</h1>
                <p class="reinvite-subtitle">Welcome back! Set a new password to access your account.</p>

                <div class="username-label">Resetting password for:</div>
                <div class="username-display">{$username}</div>

                <form method="post" action="reinvite.php?token={$token|escape:'url'}">
                    <input type="hidden" name="token" value="{$token}">

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password"
                               id="new_password"
                               name="new_password"
                               class="form-input"
                               placeholder="Enter new password"
                               required
                               minlength="4"
                               autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-input"
                               placeholder="Confirm new password"
                               required
                               minlength="4">
                    </div>

                    <button type="submit" class="submit-button">Set New Password</button>
                </form>

                <a href="/farkle.php" class="home-link">Cancel and return to Farkle</a>
            {else}
                <div class="error-message">
                    This password reset link is invalid or has expired.
                </div>
                <a href="/farkle.php" class="home-link">Go to Farkle Ten</a>
            {/if}
        </div>
    </div>
</body>
</html>
