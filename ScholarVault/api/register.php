<?php
// api/register.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? ''; // Proponent or Evaluator
    $college = $_POST['college'] ?? null;
    $position = $_POST['position'] ?? null;

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit();
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    // Generate username from names (e.g., antheamyles.simon)
    $clean_first = strtolower(str_replace(' ', '', $first_name));
    $clean_last = strtolower(str_replace(' ', '', $last_name));
    $username = $clean_first . "." . $clean_last;
    $email = $username . "@csucc.edu.ph";

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This name combination is already registered.']);
        exit();
    }

    // Hash password
    $hashed_pw = hash_password($password);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, middle_name, last_name, role, position, college, email, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$username, $hashed_pw, $first_name, $middle_name, $last_name, $role, $position, $college, $email]);
        
        $new_user_id = $pdo->lastInsertId();
        log_activity($pdo, $new_user_id, 'Registration', "Account created as $role");

        echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
