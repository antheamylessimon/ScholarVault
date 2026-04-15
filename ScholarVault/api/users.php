<?php
// api/users.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_login();

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role FROM users 
                    WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) 
                    AND role IN ('Proponent', 'Evaluator') 
                    LIMIT 10");
$search = "%$query%";
$stmt->execute([$search, $search, $search]);

echo json_encode($stmt->fetchAll());
?>
