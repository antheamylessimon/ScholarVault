<?php
// api/check_email.php
include_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['available' => false, 'message' => 'Email is required.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode(['available' => false, 'message' => 'This email is already registered.']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Email is available.']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
}
?>
