<?php
// api/profile_picture.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_login();

$user = get_current_user_session();

// GET: fetch approved profile picture for a user
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_GET['user_id'] ?? $user['user_id'];
    
    $stmt = $pdo->prepare("SELECT file_path FROM profile_pictures WHERE user_id = ? AND status = 'approved' ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $pic = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'profile_pic' => $pic ? $pic['file_path'] : null
    ]);
    exit();
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Upload (Proponent / Evaluator only) ─────────────────────────────────
    if ($action === 'upload') {
        if ($user['role'] === 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Admins cannot upload profile pictures.']);
            exit();
        }

        if (!isset($_FILES['picture']) || $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
            exit();
        }

        $file = $_FILES['picture'];
        $maxSize = 2 * 1024 * 1024; // 2 MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 2 MB.']);
            exit();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.']);
            exit();
        }

        // Build filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user['user_id'] . '_' . time() . '.' . $ext;
        $uploadDir = '../uploads/profile_pics/';
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
            exit();
        }

        // Mark any older pending pictures from this user as rejected (only latest pending matters)
        $pdo->prepare("UPDATE profile_pictures SET status = 'rejected', reviewed_at = NOW() WHERE user_id = ? AND status = 'pending'")->execute([$user['user_id']]);

        // Insert new pending picture
        $stmt = $pdo->prepare("INSERT INTO profile_pictures (user_id, file_path) VALUES (?, ?)");
        $stmt->execute([$user['user_id'], 'uploads/profile_pics/' . $filename]);

        log_activity($pdo, $user['user_id'], 'Profile Picture Upload', 'Uploaded a new profile picture (pending approval).');

        echo json_encode(['success' => true, 'message' => 'Profile picture uploaded! It will be visible after admin approval.']);
        exit();
    }

    // ── Approve / Reject (Admin only) ────────────────────────────────────────
    if ($action === 'approve' || $action === 'reject') {
        if ($user['role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit();
        }

        $pic_id = $_POST['pic_id'] ?? 0;
        if (!$pic_id) {
            echo json_encode(['success' => false, 'message' => 'Missing picture ID.']);
            exit();
        }

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        // If approving, reject all other approved pictures for this user first
        if ($newStatus === 'approved') {
            // Get user_id of this picture
            $stmt = $pdo->prepare("SELECT user_id FROM profile_pictures WHERE id = ?");
            $stmt->execute([$pic_id]);
            $pic = $stmt->fetch();

            if ($pic) {
                // Set old approved pictures to rejected
                $pdo->prepare("UPDATE profile_pictures SET status = 'rejected', reviewed_at = NOW() WHERE user_id = ? AND status = 'approved'")->execute([$pic['user_id']]);
            }
        }

        $stmt = $pdo->prepare("UPDATE profile_pictures SET status = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $pic_id]);

        $actionLabel = $newStatus === 'approved' ? 'approved' : 'rejected';
        log_activity($pdo, $user['user_id'], 'Profile Picture ' . ucfirst($actionLabel), "Admin $actionLabel profile picture ID $pic_id.");

        echo json_encode(['success' => true, 'message' => "Profile picture $actionLabel."]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>
