<?php
// api/check_name.php
include_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['available' => true]); // Don't block if incomplete
        exit();
    }

    // Generate potential username (consistent with register.php)
    $clean_first = strtolower(str_replace(' ', '', $first_name));
    $clean_last = strtolower(str_replace(' ', '', $last_name));
    $username = $clean_first . "." . $clean_last;
    $email = $username . "@csucc.edu.ph";

    // Check if user exists with this username or email or name combo
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR (LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?))");
    $stmt->execute([$email, $username, $first_name, $last_name]);

    if ($stmt->fetch()) {
        echo json_encode([
            'available' => false, 
            'message' => 'This name combination is already registered. Please login or contact admin if you think this is a mistake.'
        ]);
    } else {
        echo json_encode(['available' => true]);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
}
?>
