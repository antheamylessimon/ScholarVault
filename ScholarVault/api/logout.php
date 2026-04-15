<?php
// api/logout.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

session_start();
if (isset($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'Logout', "User logged out");
}
session_unset();
session_destroy();
header("Location: ../index.php");
exit();
?>
