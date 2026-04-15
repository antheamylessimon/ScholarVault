<?php
// api/works.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_login();

$user = get_current_user_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $authors = $_POST['authors'] ?? [];
        $quarter = $_POST['quarter'] ?? '';
        $total_proposed_budget = $_POST['total_proposed_budget'] ?? null;
        $proposed_starting_date = $_POST['proposed_starting_date'] ?? null;
        $proposed_completion_date = $_POST['proposed_completion_date'] ?? null;
        $year = $_POST['year'] ?? date('Y');

        if (empty($title) || empty($authors) || empty($quarter) || empty($total_proposed_budget) || empty($proposed_starting_date) || empty($proposed_completion_date)) {
            echo json_encode(['success' => false, 'message' => 'Please provide title, authors, quarter, budget, start date, and completion date.']);
            exit();
        }

        // Check for duplicate title among pending or approved works
        $dupCheck = $pdo->prepare("SELECT id FROM works WHERE LOWER(TRIM(title)) = LOWER(?) AND (status IN ('Yellow', 'Green') OR current_step = 'Published') LIMIT 1");
        $dupCheck->execute([$title]);
        if ($dupCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A work with this title already exists. Duplicate submissions are not allowed.']);
            exit();
        }

        try {
            $pdo->beginTransaction();
            $work_type = $_POST['work_type'] ?? 'Research';
            $initial_step = ($work_type === 'Extension') ? 'Extension Coordinator' : 'Research Coordinator';

            $stmt = $pdo->prepare("INSERT INTO works (title, quarter, total_proposed_budget, proposed_starting_date, proposed_completion_date, year, work_type, created_by, current_step) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $quarter, $total_proposed_budget, $proposed_starting_date, $proposed_completion_date, $year, $work_type, $user['user_id'], $initial_step]);
            $work_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO work_authors (work_id, user_id, role) VALUES (?, ?, ?)");
            $authorRoles = $_POST['author_roles'] ?? [];
            foreach ($authors as $idx => $author_id) {
                $role = ($authorRoles[$idx] ?? 'member') === 'leader' ? 'leader' : 'member';
                $stmt->execute([$work_id, $author_id, $role]);
            }

            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $upload_dir = '../uploads/' . $work_id . '/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                foreach ($_FILES['files']['name'] as $key => $name) {
                    $tmp_name = $_FILES['files']['tmp_name'][$key];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $file_name = bin2hex(random_bytes(8)) . '.' . $ext;
                    $target = $upload_dir . $file_name;
                    $web_path = 'uploads/' . $work_id . '/' . $file_name;
                    if (move_uploaded_file($tmp_name, $target)) {
                        $stmt_file = $pdo->prepare("INSERT INTO work_files (work_id, file_path, file_name, file_type) VALUES (?, ?, ?, ?)");
                        $stmt_file->execute([$work_id, $web_path, $name, $ext]);
                    }
                }
            }

            if (isset($_POST['links'])) {
                $links = is_array($_POST['links']) ? $_POST['links'] : [$_POST['links']];
                $stmt_link = $pdo->prepare("INSERT INTO work_links (work_id, link_url) VALUES (?, ?)");
                foreach ($links as $link) {
                    $link = trim($link);
                    if (!empty($link)) {
                        $stmt_link->execute([$work_id, $link]);
                    }
                }
            }
            log_activity($pdo, $user['user_id'], 'Submission', "New work submitted: $title");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Work created successfully!']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();

    }
    elseif ($action === 'details') {
        $id = $_POST['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("SELECT w.*, 
                                  (SELECT COALESCE(GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name)), '') FROM users u JOIN work_authors wa ON u.id = wa.user_id WHERE wa.work_id = w.id) as author_names,
                                  (SELECT COALESCE(GROUP_CONCAT(DISTINCT college), '') FROM users u2 JOIN work_authors wa2 ON u2.id = wa2.user_id WHERE wa2.work_id = w.id) as colleges
                                  FROM works w WHERE w.id = ?");
            $stmt->execute([$id]);
            $work = $stmt->fetch();
            if (!$work) {
                echo json_encode(['success' => false, 'message' => 'Work not found.']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT * FROM work_files WHERE work_id = ?");
            $stmt->execute([$id]);
            $work['files'] = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT link_url FROM work_links WHERE work_id = ?");
            $stmt->execute([$id]);
            $work['links'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare("SELECT e.*, u.first_name, u.last_name, u.position FROM evaluations e JOIN users u ON e.evaluator_id = u.id WHERE e.work_id = ? ORDER BY e.created_at DESC");
            $stmt->execute([$id]);
            $work['evaluations'] = $stmt->fetchAll();

            // Get authors as objects (include role)
            $stmt = $pdo->prepare("SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, wa.role FROM users u JOIN work_authors wa ON u.id = wa.user_id WHERE wa.work_id = ? ORDER BY FIELD(wa.role,'leader','member')");
            $stmt->execute([$id]);
            $work['authors_list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Leader name for display
            $leaderRow = array_filter($work['authors_list'], fn($a) => $a['role'] === 'leader');
            $work['leader_name'] = $leaderRow ? array_values($leaderRow)[0]['name'] : null;

            // Specific for editing
            $stmt = $pdo->prepare("SELECT user_id FROM work_authors WHERE work_id = ?");
            $stmt->execute([$id]);
            $work['author_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['success' => true, 'work' => $work]);
        }
        catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching details: ' . $e->getMessage()]);
        }
        exit();

    }
    elseif ($action === 'evaluate') {
        require_role('Evaluator');
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $comment = $_POST['comment'] ?? '';
        if (!$id || !$status || !$comment) {
            echo json_encode(['success' => false, 'message' => 'Missing data.']);
            exit();
        }

        try {
            $pdo->beginTransaction();
            // Add evaluation record
            $stmt_work = $pdo->prepare("SELECT * FROM works WHERE id = ?");
            $stmt_work->execute([$id]);
            $work = $stmt_work->fetch();
            
            $stmt = $pdo->prepare("INSERT INTO evaluations (work_id, evaluator_id, step, status, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $user['user_id'], $work['current_step'], $status, $comment]);

            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $upload_dir = '../uploads/' . $id . '/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                foreach ($_FILES['files']['name'] as $key => $name) {
                    if ($_FILES['files']['error'][$key] === 0) {
                        $tmp_name = $_FILES['files']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $file_name = bin2hex(random_bytes(8)) . '.' . $ext;
                        $target = $upload_dir . $file_name;
                        $web_path = 'uploads/' . $id . '/' . $file_name;
                        if (move_uploaded_file($tmp_name, $target)) {
                            $stmt_file = $pdo->prepare("INSERT INTO work_files (work_id, file_path, file_name, file_type) VALUES (?, ?, ?, ?)");
                            $stmt_file->execute([$id, $web_path, $name, $ext]);
                        }
                    }
                }
            }

            $next_step = $work['current_step'];
            $work_status = ($status === 'Green') ? 'Green' : 'Red';

            if ($status === 'Green') {
                $pipelines = [
                    'Research' => ['Research Coordinator', 'College Dean', 'Head', 'Division Chief', 'Published'],
                    'Extension' => ['Extension Coordinator', 'College Dean', 'Head', 'Division Chief', 'Published']
                ];
                
                $wType = $work['work_type'] ?? 'Research';
                $steps = $pipelines[$wType] ?? $pipelines['Research'];

                $currentIndex = array_search($work['current_step'], $steps);
                
                if ($currentIndex !== false && $currentIndex < count($steps) - 1) {
                    $next_step = $steps[$currentIndex + 1];
                    $work_status = ($next_step === 'Published') ? 'Green' : 'Yellow';
                }
            }

            $stmt_update = $pdo->prepare("UPDATE works SET status = ?, current_step = ? WHERE id = ?");
            $stmt_update->execute([$work_status, $next_step, $id]);

            log_activity($pdo, $user['user_id'], 'Evaluation', "Evaluated work '{$work['title']}' as $status: $comment");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Evaluation submitted!']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();

    }
    elseif ($action === 'update') {
        $workId = $_POST['work_id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $quarter = $_POST['quarter'] ?? null;
        $total_proposed_budget = $_POST['total_proposed_budget'] ?? null;
        $proposed_starting_date = $_POST['proposed_starting_date'] ?? null;
        $proposed_completion_date = $_POST['proposed_completion_date'] ?? null;
        $authors = $_POST['authors'] ?? [];
        if (!$workId || !$title || !$quarter || !$total_proposed_budget || !$proposed_starting_date || !$proposed_completion_date) {
            echo json_encode(['success' => false, 'message' => 'Missing fields.']);
            exit();
        }

        // Check for duplicate title among other pending or approved works (exclude current work)
        $dupCheck = $pdo->prepare("SELECT id FROM works WHERE LOWER(TRIM(title)) = LOWER(?) AND id != ? AND (status IN ('Yellow', 'Green') OR current_step = 'Published') LIMIT 1");
        $dupCheck->execute([$title, $workId]);
        if ($dupCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Another work with this title already exists in pending or approved status. Duplicate titles are not allowed.']);
            exit();
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE works SET title = ?, quarter = ?, total_proposed_budget = ?, proposed_starting_date = ?, proposed_completion_date = ? WHERE id = ?")->execute([$title, $quarter, $total_proposed_budget, $proposed_starting_date, $proposed_completion_date, $workId]);
            $pdo->prepare("DELETE FROM work_authors WHERE work_id = ?")->execute([$workId]);
            $stmt = $pdo->prepare("INSERT INTO work_authors (work_id, user_id, role) VALUES (?, ?, ?)");
            $authorRoles = $_POST['author_roles'] ?? [];
            foreach ($authors as $idx => $aid) {
                $role = ($authorRoles[$idx] ?? 'member') === 'leader' ? 'leader' : 'member';
                $stmt->execute([$workId, $aid, $role]);
            }

            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $upload_dir = '../uploads/' . $workId . '/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                foreach ($_FILES['files']['name'] as $key => $name) {
                    if ($_FILES['files']['error'][$key] === 0) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $file_name = bin2hex(random_bytes(8)) . '.' . $ext;
                        $target = $upload_dir . $file_name;
                        $web_path = 'uploads/' . $workId . '/' . $file_name;
                        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target)) {
                            $pdo->prepare("INSERT INTO work_files (work_id, file_path, file_name, file_type) VALUES (?, ?, ?, ?)")->execute([$workId, $web_path, $name, $ext]);
                        }
                    }
                }
            }

            if (isset($_POST['links'])) {
                $pdo->prepare("DELETE FROM work_links WHERE work_id = ?")->execute([$workId]);
                $links = is_array($_POST['links']) ? $_POST['links'] : [$_POST['links']];
                $stmt_link = $pdo->prepare("INSERT INTO work_links (work_id, link_url) VALUES (?, ?)");
                foreach ($links as $link) {
                    $link = trim($link);
                    if (!empty($link)) {
                        $stmt_link->execute([$workId, $link]);
                    }
                }
            }
            log_activity($pdo, $user['user_id'], 'Update Work', "Updated work ID $workId");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Work updated successfully.']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();

    }
    elseif ($action === 'delete') {
        $workId = $_POST['id'] ?? null;
        if (!$workId) {
            echo json_encode(['success' => false, 'message' => 'Missing ID.']);
            exit();
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT file_path FROM work_files WHERE work_id = ?");
            $stmt->execute([$workId]);
            foreach ($stmt->fetchAll() as $file) {
                // file_path is stored as web-relative (uploads/id/file), resolve to server path
                $server_path = '../' . ltrim($file['file_path'], './');
                if (file_exists($server_path))
                    unlink($server_path);
            }
            $uploadDir = '../uploads/' . $workId . '/';
            if (is_dir($uploadDir)) {
                $remains = array_diff(scandir($uploadDir), array('.', '..'));
                if (empty($remains))
                    rmdir($uploadDir);
            }
            $pdo->prepare("DELETE FROM evaluations WHERE work_id = ?")->execute([$workId]);
            $pdo->prepare("DELETE FROM work_files WHERE work_id = ?")->execute([$workId]);
            $pdo->prepare("DELETE FROM work_links WHERE work_id = ?")->execute([$workId]);
            $pdo->prepare("DELETE FROM work_authors WHERE work_id = ?")->execute([$workId]);
            $pdo->prepare("DELETE FROM works WHERE id = ?")->execute([$workId]);
            log_activity($pdo, $user['user_id'], 'Delete Work', "Deleted work ID $workId");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Work deleted successfully.']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    elseif ($action === 'notifications') {
        try {
            if ($user['role'] === 'Evaluator') {
                $stmt = $pdo->prepare("SELECT w.*, 
                                      (SELECT COUNT(*) FROM evaluations e WHERE e.work_id = w.id) as evaluations_count
                                      FROM works w 
                                      WHERE w.current_step = ? AND w.current_step != 'Published'
                                      ORDER BY w.created_at DESC");
                $stmt->execute([$user['position']]);
            }
            elseif ($user['role'] === 'Proponent') {
                $stmt = $pdo->prepare("SELECT DISTINCT w.*, 
                                      (SELECT COUNT(*) FROM evaluations e WHERE e.work_id = w.id) as evaluations_count
                                      FROM works w 
                                      JOIN work_authors wa ON w.id = wa.work_id
                                      WHERE wa.user_id = ? 
                                      AND (w.current_step != 'Research Coordinator' OR (SELECT COUNT(*) FROM evaluations e2 WHERE e2.work_id = w.id) > 0)
                                      ORDER BY w.created_at DESC LIMIT 10");
                $stmt->execute([$user['user_id']]);
            }
            else {
                echo json_encode(['success' => true, 'notifications' => []]);
                exit();
            }
            echo json_encode(['success' => true, 'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

// GET Requests
$type = $_GET['type'] ?? 'my';
try {
    if ($type === 'my') {
        $query = "SELECT DISTINCT w.*, 
                    (SELECT COALESCE(GROUP_CONCAT(DISTINCT CONCAT_WS(' ', u.first_name, u.last_name)), '') FROM users u JOIN work_authors wa2 ON u.id = wa2.user_id WHERE wa2.work_id = w.id) as author_names,
                    (SELECT COALESCE(GROUP_CONCAT(DISTINCT u2.college), '') FROM users u2 JOIN work_authors wa3 ON u2.id = wa3.user_id WHERE wa3.work_id = w.id) as colleges,
                    (SELECT CONCAT(ul.first_name, ' ', ul.last_name) FROM users ul JOIN work_authors wal ON ul.id = wal.user_id WHERE wal.work_id = w.id AND wal.role = 'leader' LIMIT 1) as leader_name,
                    (SELECT COALESCE(GROUP_CONCAT(CONCAT(um.first_name, ' ', um.last_name) SEPARATOR ', '), '') FROM users um JOIN work_authors wam ON um.id = wam.user_id WHERE wam.work_id = w.id AND wam.role = 'member') as member_names
                  FROM works w JOIN work_authors wa ON w.id = wa.work_id
                  WHERE wa.user_id = ? AND w.current_step != 'Published'
                  ORDER BY w.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user['user_id']]);
    }
    else {
        $params = [];
        $query = "SELECT w.*, 
                    (SELECT COALESCE(GROUP_CONCAT(DISTINCT CONCAT_WS(' ', u.first_name, u.last_name)), '') FROM users u JOIN work_authors wa2 ON u.id = wa2.user_id WHERE wa2.work_id = w.id) as author_names,
                    (SELECT COALESCE(GROUP_CONCAT(DISTINCT u2.college), '') FROM users u2 JOIN work_authors wa3 ON u2.id = wa3.user_id WHERE wa3.work_id = w.id) as colleges,
                    (SELECT CONCAT(ul.first_name, ' ', ul.last_name) FROM users ul JOIN work_authors wal ON ul.id = wal.user_id WHERE wal.work_id = w.id AND wal.role = 'leader' LIMIT 1) as leader_name,
                    (SELECT COALESCE(GROUP_CONCAT(CONCAT(um.first_name, ' ', um.last_name) SEPARATOR ', '), '') FROM users um JOIN work_authors wam ON um.id = wam.user_id WHERE wam.work_id = w.id AND wam.role = 'member') as member_names,
                    (SELECT MAX(e.created_at) FROM evaluations e WHERE e.work_id = w.id AND e.status = 'Green') as approved_at";
        
        if ($user['role'] === 'Evaluator') {
            $query .= ", (SELECT COUNT(*) FROM evaluations e WHERE e.work_id = w.id AND e.evaluator_id = ?) as has_evaluated";
            $params[] = $user['user_id'];
        }

        $query .= " FROM works w WHERE 1=1";
        
        if ($type === 'published') {
            $query .= " AND w.current_step = 'Published'";
        } elseif ($type === 'ongoing') {
            $query .= " AND w.current_step != 'Published'";
            
            // Evaluator-side filtering logic
            if ($user['role'] === 'Evaluator') {
                // College Dean, Head, Division Chief, Research Coordinator, Extension Coordinator see all types and can swap via tabs
            }
        }
        
        $query .= " ORDER BY w.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    }
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
