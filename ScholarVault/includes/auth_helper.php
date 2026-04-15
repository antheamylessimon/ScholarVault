<?php
// includes/auth_helper.php
session_start();

/**
 * Hash password using BCRYPT
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Redirect if not specific role
 */
function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

/**
 * Get current user data from session
 */
function get_current_user_session() {
    return is_logged_in() ? $_SESSION : null;
}

/**
 * Log activity to the database
 */
function log_activity($pdo, $user_id, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        // Silently fail or log to error log
        error_log("Logging failed: " . $e->getMessage());
    }
}

/**
 * Check if user is blocked
 */
function is_user_blocked($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT is_blocked FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user && $user['is_blocked'] == 1;
}
?>
