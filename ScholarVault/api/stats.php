<?php
// api/stats.php
include_once '../config/db.php';
include_once '../includes/auth_helper.php';

header('Content-Type: application/json');
require_login();

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$isProponent = ($role === 'Proponent');
$isEvaluator = ($role === 'Evaluator');

$userPos = '';
if ($isEvaluator) {
    $st = $pdo->prepare("SELECT position FROM users WHERE id = ?");
    $st->execute([$userId]);
    $userPos = $st->fetchColumn();
}

$stats = [
    'colleges' => [
        'CEIT' => 0,
        'CTHM' => 0,
        'CITTE' => 0,
        'CBA' => 0
    ],
    'years_submitted' => [],
    'years_approved' => [],
    'years_evaluated' => [],
    'colleges_evaluated' => [
        'CEIT' => 0,
        'CTHM' => 0,
        'CITTE' => 0,
        'CBA' => 0
    ],
    'overall' => [
        'ongoing' => 0,
        'published' => 0,
        'total' => 0
    ],
    'users' => [
        'proponents' => 0,
        'evaluators' => 0,
        'total' => 0
    ],
    'users_per_college' => [
        'CEIT' => 0,
        'CTHM' => 0,
        'CITTE' => 0,
        'CBA' => 0
    ]
];

// Workflow weights for Evaluator tracking
$workflow = [
    'Research Coordinator' => 1,
    'Extension Coordinator' => 1,
    'College Dean' => 2,
    'Head' => 3,
    'Division Chief' => 4,
    'Published' => 5
];

// College counts
if ($isProponent) {
    $sql = "SELECT u.college, COUNT(DISTINCT w.id) as count 
            FROM users u 
            JOIN work_authors wa ON u.id = wa.user_id 
            JOIN works w ON wa.work_id = w.id 
            WHERE u.role = 'Proponent' AND w.id IN (SELECT work_id FROM work_authors WHERE user_id = ?)";
    $stmt = $pdo->prepare($sql . " GROUP BY u.college");
    $stmt->execute([$userId]);
} else if ($isEvaluator) {
    // Current assignments
    $sql = "SELECT u.college, COUNT(DISTINCT w.id) as count 
            FROM users u 
            JOIN work_authors wa ON u.id = wa.user_id 
            JOIN works w ON wa.work_id = w.id 
            WHERE w.current_step = ?";
    $stmt = $pdo->prepare($sql . " GROUP BY u.college");
    $stmt->execute([$userPos]);
} else {
    // Admin: Submissions
    $sql = "SELECT u.college, COUNT(DISTINCT w.id) as count 
            FROM users u 
            JOIN work_authors wa ON u.id = wa.user_id 
            JOIN works w ON wa.work_id = w.id 
            WHERE u.role = 'Proponent'";
    $stmt = $pdo->query($sql . " GROUP BY u.college");
}

while ($row = $stmt->fetch()) {
    if ($row['college']) {
        $stats['colleges'][$row['college']] = (int)$row['count'];
    }
}

// Extra college counts for Evaluator (Evaluated works)
if ($isEvaluator) {
    $posWeight = isset($workflow[$userPos]) ? $workflow[$userPos] : 0;
    $evaluatedSteps = [];
    foreach ($workflow as $step => $weight) {
        if ($weight > $posWeight) $evaluatedSteps[] = $step;
    }
    
    if (!empty($evaluatedSteps)) {
        $placeholders = str_repeat('?,', count($evaluatedSteps) - 1) . '?';
        $sql = "SELECT u.college, COUNT(DISTINCT w.id) as count 
                FROM users u 
                JOIN work_authors wa ON u.id = wa.user_id 
                JOIN works w ON wa.work_id = w.id 
                WHERE w.current_step IN ($placeholders) GROUP BY u.college";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($evaluatedSteps);
        while ($row = $stmt->fetch()) {
            if ($row['college']) $stats['colleges_evaluated'][$row['college']] = (int)$row['count'];
        }
    }
}

// Annual Performance Data
if ($isProponent) {
    // Proponent: Dual-track personal analytics
    // Personal Submissions
    $stmt = $pdo->prepare("SELECT year, COUNT(*) as count FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? GROUP BY year ORDER BY year ASC");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch()) $stats['years_submitted'][$row['year']] = (int)$row['count'];
    
    // Personal Approved
    $stmt = $pdo->prepare("SELECT year, COUNT(*) as count FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND current_step = 'Published' GROUP BY year ORDER BY year ASC");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch()) $stats['years_approved'][$row['year']] = (int)$row['count'];
} else if ($isEvaluator) {
    // Evaluator: Submissions (assigned) and Evaluated
    $stmt = $pdo->prepare("SELECT year, COUNT(*) as count FROM works WHERE current_step = ? GROUP BY year ORDER BY year ASC");
    $stmt->execute([$userPos]);
    while ($row = $stmt->fetch()) $stats['years_submitted'][$row['year']] = (int)$row['count'];
    
    $posWeight = isset($workflow[$userPos]) ? $workflow[$userPos] : 0;
    $evaluatedSteps = [];
    foreach ($workflow as $step => $weight) {
        if ($weight > $posWeight) $evaluatedSteps[] = $step;
    }
    
    if (!empty($evaluatedSteps)) {
        $placeholders = str_repeat('?,', count($evaluatedSteps) - 1) . '?';
        $stmt = $pdo->prepare("SELECT year, COUNT(*) as count FROM works WHERE current_step IN ($placeholders) GROUP BY year ORDER BY year ASC");
        $stmt->execute($evaluatedSteps);
        while ($row = $stmt->fetch()) $stats['years_evaluated'][$row['year']] = (int)$row['count'];
    }
} else {
    // Admin: Dual-track
    // Total Submissions
    $stmt = $pdo->query("SELECT year, COUNT(*) as count FROM works GROUP BY year ORDER BY year ASC");
    while ($row = $stmt->fetch()) $stats['years_submitted'][$row['year']] = (int)$row['count'];
    
    // Total Approved
    $stmt = $pdo->query("SELECT year, COUNT(*) as count FROM works WHERE current_step = 'Published' GROUP BY year ORDER BY year ASC");
    while ($row = $stmt->fetch()) $stats['years_approved'][$row['year']] = (int)$row['count'];
}

// Overall stats
if ($isProponent) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND current_step = 'Published'");
    $st->execute([$userId]);
    $stats['overall']['published'] = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ?");
    $st->execute([$userId]);
    $stats['overall']['total'] = (int)$st->fetchColumn();
} else if ($isEvaluator) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM works WHERE current_step = ? AND status = 'Green'");
    $st->execute([$userPos]);
    $stats['overall']['published'] = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM works WHERE current_step = ?");
    $st->execute([$userPos]);
    $stats['overall']['total'] = (int)$st->fetchColumn();
} else {
    $stats['overall']['published'] = (int)$pdo->query("SELECT COUNT(*) FROM works WHERE current_step = 'Published'")->fetchColumn();
    $stats['overall']['total'] = (int)$pdo->query("SELECT COUNT(*) FROM works")->fetchColumn();
}
$stats['overall']['ongoing'] = $stats['overall']['total'] - $stats['overall']['published'];

if (!$isProponent && !$isEvaluator) {
    // User stats (Only relevant for Admin)
    $stats['users']['proponents'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Proponent'")->fetchColumn();
    $stats['users']['evaluators'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Evaluator'")->fetchColumn();
    $stats['users']['total'] = (int)$stats['users']['proponents'] + $stats['users']['evaluators'];

    // Users per college
    $stmt = $pdo->query("SELECT college, COUNT(*) as count FROM users WHERE role = 'Proponent' GROUP BY college");
    while ($row = $stmt->fetch()) {
        if ($row['college'] && isset($stats['users_per_college'][$row['college']])) {
            $stats['users_per_college'][$row['college']] = (int)$row['count'];
        }
    }
}

echo json_encode($stats);
?>
