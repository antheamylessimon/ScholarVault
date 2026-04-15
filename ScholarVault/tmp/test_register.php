<?php
// /tmp/test_register.php
include 'config/db.php';
include 'includes/auth_helper.php';

$_POST = [
    'first_name' => 'Anthea Myles',
    'middle_name' => 'A.',
    'last_name' => 'Simon',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'Proponent',
    'college' => 'CEIT'
];

ob_start();
include 'api/register.php';
$output = ob_get_clean();

echo "API Output: " . $output . "\n";

// Verify database
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE first_name = 'Anthea Myles' AND last_name = 'Simon'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    echo "User Found in DB:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
} else {
    echo "User NOT found in DB.\n";
}
?>
