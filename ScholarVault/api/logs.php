<?php
// api/logs.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_role('Admin');

$stmt = $pdo->query("SELECT l.*, u.first_name, u.last_name, u.role, u.position, u.college 
                    FROM logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    ORDER BY l.created_at DESC 
                    LIMIT 500");
echo json_encode($stmt->fetchAll());
?>
