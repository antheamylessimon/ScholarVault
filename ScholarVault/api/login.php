<?php
// api/login.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_as = $_POST['login_as'] ?? 'proponent_evaluator'; // admin or proponent_evaluator

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        exit();
    }

    // Special case for Admin (as requested: admin/admin123)
    if ($login_as === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'Admin'");
        $stmt->execute([$username]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role IN ('Proponent', 'Evaluator')");
        $stmt->execute([$username]);
    }

    $user = $stmt->fetch();

    if ($user && verify_password($password, $user['password'])) {
        if ($user['is_blocked']) {
            echo json_encode(['success' => false, 'message' => 'Your account has been blocked. Please contact the administrator.']);
            exit();
        }

        if ($user['is_approved'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Your account is pending administrator approval.']);
            exit();
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['college'] = $user['college'];
        $_SESSION['position'] = $user['position'];

        // Load approved profile picture
        $picStmt = $pdo->prepare("SELECT file_path FROM profile_pictures WHERE user_id = ? AND status = 'approved' ORDER BY uploaded_at DESC LIMIT 1");
        $picStmt->execute([$user['id']]);
        $pic = $picStmt->fetch();
        $_SESSION['profile_pic'] = $pic ? $pic['file_path'] : null;

        log_activity($pdo, $user['id'], 'Login', "User logged in as " . $user['role']);

        echo json_encode([
            'success' => true, 
            'message' => 'Login successful!',
            'role' => $user['role'],
            'redirect' => 'dashboard.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
