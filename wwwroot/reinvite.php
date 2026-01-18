<?php
/**
 * Re-Invite Password Reset Page
 *
 * This page handles password reset for users who receive a re-invite link from an admin.
 * The link contains a token that validates the user's identity.
 *
 * URL: /reinvite.php?token=[guid]
 *
 * This is a PUBLIC page - no login required.
 */

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleLogin.php');

// Initialize session (needed for auto-login after reset)
BaseUtil_SessSet();

$error = '';
$success = '';
$username = '';
$tokenValid = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Also check POST in case form submits back to itself
if (empty($token) && isset($_POST['token'])) {
    $token = trim($_POST['token']);
}

// Token is required
if (empty($token)) {
    $error = 'No reset token provided. Please use the link from your email.';
} else {
    // Validate the token - must exist and not be expired
    $escapedToken = db_escape_string($token);
    $sql = "SELECT playerid, username
            FROM farkle_players
            WHERE reinvite_token = '{$escapedToken}'
            AND reinvite_expires > NOW()";

    $player = db_select_query($sql, SQL_SINGLE_ROW);

    if ($player) {
        $tokenValid = true;
        $username = $player['username'];

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

            // Validate passwords
            if (empty($newPassword)) {
                $error = 'Please enter a new password.';
            } elseif (strlen($newPassword) < 4) {
                $error = 'Password must be at least 4 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                // Passwords are valid - update password and auto-login
                $salt = "35td2c";
                $playerid = $player['playerid'];

                // Hash password the same way as farkleLogin.php:
                // password = CONCAT(MD5(plain_password), MD5(salt))
                $hashedPassword = md5($newPassword);

                // Update password and clear the reinvite token
                $sql = "UPDATE farkle_players
                        SET password = CONCAT('{$hashedPassword}', MD5('{$salt}')),
                            reinvite_token = NULL,
                            reinvite_expires = NULL
                        WHERE playerid = {$playerid}";

                if (db_command($sql)) {
                    // Create login session (same as LoginGenerateSession)
                    LoginGenerateSession($playerid, 1, 'web');

                    // Set session variables for login success
                    $pInfo = array(
                        'username' => $player['username'],
                        'playerid' => $playerid,
                        'adminlevel' => isset($player['adminlevel']) ? $player['adminlevel'] : 0
                    );
                    LoginSuccess($pInfo, 1);

                    // Redirect to main game page
                    header('Location: /farkle.php');
                    exit;
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        }
    } else {
        // Check if token exists but is expired
        $sql = "SELECT playerid FROM farkle_players WHERE reinvite_token = '{$escapedToken}'";
        $expiredPlayer = db_select_query($sql, SQL_SINGLE_ROW);

        if ($expiredPlayer) {
            $error = 'This password reset link has expired. Please contact an administrator for a new link.';
        } else {
            $error = 'This password reset link is invalid. Please check the link or contact an administrator.';
        }
    }
}

// Assign template variables
$smarty->assign('error', $error);
$smarty->assign('success', $success);
$smarty->assign('username', htmlspecialchars($username));
$smarty->assign('tokenValid', $tokenValid);
$smarty->assign('token', htmlspecialchars($token));

// Display the template
$smarty->display('reinvite.tpl');
?>
