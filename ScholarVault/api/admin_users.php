<?php
// api/admin_users.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.first_name, u.middle_name, u.last_name, u.role, u.position, u.college, u.email, u.is_blocked, u.is_approved, u.created_at,
               pp_pending.id AS pending_pic_id, pp_pending.file_path AS pending_pic_path,
               pp_approved.file_path AS approved_pic_path
        FROM users u
        LEFT JOIN profile_pictures pp_pending ON pp_pending.user_id = u.id AND pp_pending.status = 'pending'
        LEFT JOIN profile_pictures pp_approved ON pp_approved.user_id = u.id AND pp_approved.status = 'approved'
            AND pp_approved.uploaded_at = (SELECT MAX(pp2.uploaded_at) FROM profile_pictures pp2 WHERE pp2.user_id = u.id AND pp2.status = 'approved')
        WHERE u.role != 'Admin'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    echo json_encode($stmt->fetchAll());
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($action === 'get_user') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'user' => $stmt->fetch()]);
    } elseif ($action === 'toggle_block') {
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = NOT is_blocked WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User status updated.']);
    } elseif ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User approved successfully.']);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User deleted.']);
    } elseif ($action === 'edit') {
        $username = $_POST['username'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $college = $_POST['college'] ?? null;
        $position = $_POST['position'] ?? null;
        $password = $_POST['password'] ?? '';

        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, college = ?, position = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $first_name, $middle_name, $last_name, $email, $college, $position, $hashed, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, college = ?, position = ? WHERE id = ?");
            $stmt->execute([$username, $first_name, $middle_name, $last_name, $email, $college, $position, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } elseif ($action === 'notifications') {
        $notifications = [];
        
        // 1. Pending Users
        $stmtUsers = $pdo->query("SELECT id, first_name, last_name, role, created_at FROM users WHERE is_approved = 0 ORDER BY created_at DESC");
        while ($row = $stmtUsers->fetch()) {
            $target_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $tab = strcasecmp($row['role'], 'Proponent') === 0 ? 'proponents' : 'evaluators';
            $notifications[] = [
                'id' => 'u_' . $row['id'],
                'type' => 'pending_user',
                'status' => 'pending',
                'timestamp' => $row['created_at'],
                'message' => "New {$row['role']} registration: <strong>" . htmlspecialchars($target_name) . "</strong> needs approval.",
                'target_name' => $target_name,
                'target_tab' => $tab
            ];
        }

        // 2. Recently Approved Users (last 30 days)
        $stmtApproved = $pdo->query("SELECT id, first_name, last_name, role, created_at FROM users WHERE is_approved = 1 AND role != 'Admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY created_at DESC");
        while ($row = $stmtApproved->fetch()) {
            $target_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $tab = strcasecmp($row['role'], 'Proponent') === 0 ? 'proponents' : 'evaluators';
            $notifications[] = [
                'id' => 'ua_' . $row['id'],
                'type' => 'approved_user',
                'status' => 'done',
                'timestamp' => $row['created_at'],
                'message' => "{$row['role']} <strong>" . htmlspecialchars($target_name) . "</strong> has been approved.",
                'target_name' => $target_name,
                'target_tab' => $tab
            ];
        }
        
        // 3. Pending Profile Pictures
        $stmtPics = $pdo->query("
            SELECT p.id, u.first_name, u.last_name, u.role, p.uploaded_at 
            FROM profile_pictures p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.status = 'pending' 
            ORDER BY p.uploaded_at DESC
        ");
        while ($row = $stmtPics->fetch()) {
            $target_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $tab = strcasecmp($row['role'], 'Proponent') === 0 ? 'proponents' : 'evaluators';
            $notifications[] = [
                'id' => 'p_' . $row['id'],
                'type' => 'pending_pic',
                'status' => 'pending',
                'timestamp' => $row['uploaded_at'],
                'message' => "Pending picture: <strong>" . htmlspecialchars($target_name) . "</strong> uploaded a new profile picture.",
                'target_name' => $target_name,
                'target_tab' => $tab
            ];
        }

        // 4. Recently Processed Profile Pictures (last 30 days)
        $stmtDonePics = $pdo->query("
            SELECT p.id, u.first_name, u.last_name, u.role, p.uploaded_at, p.status as pic_status
            FROM profile_pictures p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.status IN ('approved', 'rejected') 
            AND p.uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY p.uploaded_at DESC
        ");
        while ($row = $stmtDonePics->fetch()) {
            $label = $row['pic_status'] === 'approved' ? 'approved' : 'rejected';
            $target_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $tab = strcasecmp($row['role'], 'Proponent') === 0 ? 'proponents' : 'evaluators';
            $notifications[] = [
                'id' => 'pd_' . $row['id'],
                'type' => 'done_pic',
                'status' => 'done',
                'timestamp' => $row['uploaded_at'],
                'message' => "Profile picture for <strong>" . htmlspecialchars($target_name) . "</strong> was {$label}.",
                'target_name' => $target_name,
                'target_tab' => $tab
            ];
        }

        // Sort by timestamp descending
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        echo json_encode(['success' => true, 'notifications' => $notifications]);

    }
}
?>
