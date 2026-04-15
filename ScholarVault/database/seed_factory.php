<?php
// database/seed_factory.php
// Standalone script to seed the database with dummy data.
// Usage: php database/seed_factory.php

require_once __DIR__ . '/../config/db.php';

// Helper for logging to console
function logMsg($msg) {
    echo "[" . date('H:i:s') . "] $msg\n";
}

// ---------------------------------------------------------
// 1. Setup Pools of Dummy Data
// ---------------------------------------------------------

$firstNames = ['Juan', 'Maria', 'Jose', 'Elena', 'Ricardo', 'Sofia', 'Manuel', 'Teresa', 'Antonio', 'Carmen', 'Francisco', 'Isabel', 'Miguel', 'Rosa', 'Pedro', 'Angela', 'Luis', 'Patricia', 'Carlos', 'Dolores', 'Mark', 'Jhonn', 'Analyn', 'Grace', 'Robert', 'Sarah', 'Christine', 'Wilson', 'Emily', 'David'];
$middleNames = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'I.', 'J.', 'L.', 'M.', 'N.', 'O.', 'P.', 'R.', 'S.', 'T.', 'V.'];
$lastNames = ['Dela Cruz', 'Garcia', 'Ramos', 'Reyes', 'Mercado', 'Santos', 'Mendoza', 'Bautista', 'Valdez', 'Aquino', 'Lim', 'Tan', 'Sy', 'Corpuz', 'Flores', 'Rivera', 'Gonzales', 'Castro', 'Ortiz', 'Villa', 'Pascua', 'Dizon', 'Soriano', 'Villanueva', 'Santiago'];
$colleges = ['CEIT', 'CTHM', 'CITTE', 'CBA'];

$researchPrefixes = ['Impact of', 'Developing a Sustainable', 'A Study on the Efficiency of', 'Enhancing the Cybersecurity of', 'The Role of Social Media in', 'Solar-Powered Irrigation System for', 'Implementation of Smart Agriculture in', 'Waste Management Practices in', 'Analyzing the Performance of', 'Optimizing the Process of'];
$researchKeywords = ['Artificial Intelligence', 'Blockchain Technology', 'Renewable Energy', 'Local Governance', 'Data Analytics', 'Mental Health Awareness', 'Distance Learning', 'E-commerce Trends', 'Urban Planning', 'Marine Biology'];

$extensionPrefixes = ['Community-Based Literacy Program for', 'Skill Development Workshop on', 'Environmental Awareness Campaign in', 'Promoting Health and Nutrition in', 'Empowering Women through Micro-entrepreneurship in', 'Disaster Risk Reduction Training for', 'Livelihood Training on', 'Digital Literacy Initiative for'];
$extensionKeywords = ['Rural Areas', 'Out-of-School Youth', 'Local Farmers', 'Small Businesses', 'Coastal Communities', 'Senior Citizens', 'Single Parents', 'Indigenous People'];

// ---------------------------------------------------------
// 2. Generate Proponents (65 accounts)
// ---------------------------------------------------------

logMsg("Starting Seeding Process...");
logMsg("Generating 65 Proponent accounts...");

$newProponentIds = [];
$passwordHash = password_hash('password123', PASSWORD_BCRYPT);

for ($i = 1; $i <= 65; $i++) {
    $fn = $firstNames[array_rand($firstNames)];
    $mn = $middleNames[array_rand($middleNames)];
    $ln = $lastNames[array_rand($lastNames)];
    $college = $colleges[array_rand($colleges)];
    
    $username = strtolower($fn) . "." . strtolower(str_replace(' ', '', $ln)) . "." . rand(100, 999);
    $email = "$username@csucc.edu.ph";

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, middle_name, last_name, role, college, email) VALUES (?, ?, ?, ?, ?, 'Proponent', ?, ?)");
        $stmt->execute([$username, $passwordHash, $fn, $mn, $ln, $college, $email]);
        $newProponentIds[] = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Skip duplicates if random logic hits one
        if ($e->getCode() != 23000) {
            logMsg("Error creating user: " . $e->getMessage());
        } else {
            $i--; // Retry
        }
    }
}

logMsg("Successfully created 65 new proponents.");

// ---------------------------------------------------------
// 3. Get All Proponents (Old + New)
// ---------------------------------------------------------

$stmt = $pdo->query("SELECT id FROM users WHERE role = 'Proponent'");
$allProponentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($allProponentIds)) {
    die("Error: No proponents found to associate with works.\n");
}

// ---------------------------------------------------------
// 4. Generate Approved (Published) Works (567)
// ---------------------------------------------------------

logMsg("Generating 567 Approved (Published) works...");

for ($i = 1; $i <= 567; $i++) {
    $isResearch = rand(0, 1);
    $type = $isResearch ? 'Research' : 'Extension';
    $prefix = $isResearch ? $researchPrefixes[array_rand($researchPrefixes)] : $extensionPrefixes[array_rand($extensionPrefixes)];
    $keyword = $isResearch ? $researchKeywords[array_rand($researchKeywords)] : $extensionKeywords[array_rand($extensionKeywords)];
    $title = "$prefix $keyword (Study #$i)";
    
    $quarter = 'Q' . rand(1, 4);
    $year = rand(2023, 2025);
    $budget = rand(50000, 500000);
    $startDate = "$year-" . str_pad(rand(1,6), 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = "$year-" . str_pad(rand(7,12), 2, '0', STR_PAD_LEFT) . "-28";
    $createdBy = $allProponentIds[array_rand($allProponentIds)];

    try {
        $stmt = $pdo->prepare("INSERT INTO works (title, quarter, total_proposed_budget, proposed_starting_date, proposed_completion_date, year, work_type, status, current_step, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'Green', 'Published', ?)");
        $stmt->execute([$title, $quarter, $budget, $startDate, $endDate, $year, $type, $createdBy]);
        $workId = $pdo->lastInsertId();

        // Assign Authors (Leader + 0-2 Members)
        $numAuthors = rand(1, 3);
        $tempProponents = $allProponentIds;
        shuffle($tempProponents);
        $authors = array_slice($tempProponents, 0, $numAuthors);
        
        $stmtAuth = $pdo->prepare("INSERT INTO work_authors (work_id, user_id, role) VALUES (?, ?, ?)");
        foreach ($authors as $idx => $uid) {
            $role = ($idx === 0) ? 'leader' : 'member';
            $stmtAuth->execute([$workId, $uid, $role]);
        }
    } catch (PDOException $e) {
         logMsg("Error creating approved work #$i: " . $e->getMessage());
    }
}

logMsg("Successfully created 567 approved works.");

// ---------------------------------------------------------
// 5. Generate Ongoing Works (225)
// ---------------------------------------------------------

logMsg("Generating 225 Ongoing works...");

$ongoingSteps = ['College Dean', 'Head', 'Division Chief']; // Research Coordinator and Extension Coordinator are also steps
$statuses = ['Gray', 'Yellow', 'Red'];

for ($i = 1; $i <= 225; $i++) {
    $isResearch = rand(0, 1);
    $type = $isResearch ? 'Research' : 'Extension';
    $prefix = $isResearch ? $researchPrefixes[array_rand($researchPrefixes)] : $extensionPrefixes[array_rand($extensionPrefixes)];
    $keyword = $isResearch ? $researchKeywords[array_rand($researchKeywords)] : $extensionKeywords[array_rand($extensionKeywords)];
    $title = "$prefix $keyword (Ongoing #$i)";
    
    $quarter = 'Q' . rand(1, 4);
    $year = 2026;
    $budget = rand(50000, 500000);
    $startDate = "2026-" . str_pad(rand(1,3), 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = "2026-" . str_pad(rand(10,12), 2, '0', STR_PAD_LEFT) . "-28";
    $createdBy = $allProponentIds[array_rand($allProponentIds)];
    
    // Pick a step
    $allPossibleSteps = ($type === 'Research') 
        ? array_merge(['Research Coordinator'], $ongoingSteps) 
        : array_merge(['Extension Coordinator'], $ongoingSteps);
    $step = $allPossibleSteps[array_rand($allPossibleSteps)];
    $status = $statuses[array_rand($statuses)];

    try {
        $stmt = $pdo->prepare("INSERT INTO works (title, quarter, total_proposed_budget, proposed_starting_date, proposed_completion_date, year, work_type, status, current_step, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $quarter, $budget, $startDate, $endDate, $year, $type, $status, $step, $createdBy]);
        $workId = $pdo->lastInsertId();

        // Assign Authors
        $numAuthors = rand(1, 3);
        $tempProponents = $allProponentIds;
        shuffle($tempProponents);
        $authors = array_slice($tempProponents, 0, $numAuthors);
        
        $stmtAuth = $pdo->prepare("INSERT INTO work_authors (work_id, user_id, role) VALUES (?, ?, ?)");
        foreach ($authors as $idx => $uid) {
            $role = ($idx === 0) ? 'leader' : 'member';
            $stmtAuth->execute([$workId, $uid, $role]);
        }
    } catch (PDOException $e) {
         logMsg("Error creating ongoing work #$i: " . $e->getMessage());
    }
}

logMsg("Successfully created 225 ongoing works.");
logMsg("Seeding process COMPLETED.");
?>
