<?php
include_once 'config/db.php';
include_once 'includes/auth_helper.php';

require_login();
$user = get_current_user_session();

// Refresh profile picture from DB (catches admin approvals since last login)
$picStmt = $pdo->prepare("SELECT file_path FROM profile_pictures WHERE user_id = ? AND status = 'approved' ORDER BY uploaded_at DESC LIMIT 1");
$picStmt->execute([$user['user_id']]);
$pic = $picStmt->fetch();
$_SESSION['profile_pic'] = $pic ? $pic['file_path'] : null;
$profilePic = $_SESSION['profile_pic'];

// Check if blocked again for safety
if (is_user_blocked($pdo, $user['user_id'])) {
    session_destroy();
    header("Location: index.php?error=blocked");
    exit();
}

$active_page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ScholarVault</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/google_sans.css">
    <link rel="stylesheet" href="assets/css/material_icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,600,1,0&icon_names=assured_workload" />
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script>
        const currentUserRole = "<?php echo $user['role']; ?>";
        const currentUserPosition = "<?php echo $user['position']; ?>";
        const currentUserId = <?php echo (int)$user['user_id']; ?>;
        const currentUserName = "<?php echo addslashes($user['full_name']); ?>";
    </script>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : 'light'; ?>">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar glass">
            <div class="sidebar-header" onclick="toggleSidebar()">
                <div class="logo">
                    <img src="assets/images/favicon.svg" alt="ScholarVault Logo" style="width: 28px; height: 28px; vertical-align: middle;">
                    <span class="system-name"><span class="text-scholar">Scholar</span><span class="text-vault">Vault</span></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
                <a href="?page=published" class="nav-item <?php echo $active_page === 'published' ? 'active' : ''; ?>" title="Approved">
                    <i class="fas fa-book"></i> <span>Approved</span>
                </a>
                <?php if ($user['role'] !== 'Proponent'): ?>
                <a href="?page=ongoing" class="nav-item <?php echo $active_page === 'ongoing' ? 'active' : ''; ?>" title="Ongoing">
                    <i class="fas fa-tasks"></i> <span>Ongoing</span>
                </a>
                <?php
endif; ?>
                <?php if ($user['role'] === 'Proponent'): ?>
                <a href="?page=my-works" class="nav-item <?php echo $active_page === 'my-works' ? 'active' : ''; ?>" title="My Works">
                    <i class="fas fa-folder-open"></i> <span>My Works</span>
                </a>
                <?php
endif; ?>

                <?php if ($user['role'] === 'Admin'): ?>
                <a href="?page=users" class="nav-item <?php echo $active_page === 'users' ? 'active' : ''; ?>" title="User Management">
                    <i class="fas fa-users"></i> <span>Users</span>
                </a>
                <a href="?page=logs" class="nav-item <?php echo $active_page === 'logs' ? 'active' : ''; ?>" title="Activity Logs">
                    <i class="fas fa-history"></i> <span>Logs</span>
                </a>
                <?php
endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <?php if ($user['role'] !== 'Admin'): ?>
                    <div class="avatar-wrapper clickable" onclick="document.getElementById('profilePicInput').click()" title="Click to upload profile picture">
                    <?php else: ?>
                    <div class="avatar-wrapper">
                    <?php endif; ?>
                        <?php if ($profilePic): ?>
                            <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Avatar" class="avatar">
                        <?php else: ?>
                            <div class="avatar avatar-default">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($user['role'] !== 'Admin'): ?>
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                </div>
                <a href="#" onclick="confirmLogout(event)" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>

            <!-- Hidden file input for profile picture upload -->
            <?php if ($user['role'] !== 'Admin'): ?>
            <input type="file" id="profilePicInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadProfilePicture(this)">
            <?php endif; ?>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="top-header glass">
                <?php /*
  <button id="sidebarToggle" onclick="toggleSidebar()" class="btn-icon">
  <i class="fas fa-bars"></i>
  </button>
  */?>
                <div class="header-right" style="margin-left: auto;">
                    <button class="btn btn-outline" id="themeToggle" style="display: flex; align-items: center; justify-content: center; width: 45px; height: 42px; padding: 0;">
                        <i class="fas <?php echo($_COOKIE['theme'] ?? 'light') === 'dark' ? 'fa-sun' : 'fa-moon'; ?>" style="font-size: 1.30rem;"></i>
                    </button>
                    <a href="#" class="notification-bell btn btn-outline" id="notificationBell" onclick="openNotifications(event)" aria-label="Notifications" style="display: flex; align-items: center; justify-content: center; width: 45px; height: 42px; padding: 0;">
                        <i class="fas fa-bell"></i>
                        <span class="badge" id="notificationBadge" style="display:none;"></span>
                    </a>
                </div>
            </header>

            <main class="content-area">
                <div id="content-container">
                    <?php
// Route to dynamic includes based on role and page
$page_file = 'views/' . $active_page . '.php';
if (file_exists($page_file)) {
    include $page_file;
}
else {
    include 'views/dashboard.php';
}
?>
                </div>
            </main>

            <footer class="dashboard-footer">
                <p>&copy; 2024 ScholarVault RDIE Repository System. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <!-- Global Work Details Modal -->
    <div id="workDetailsModal" class="modal-overlay">
        <div class="modal glass" style="width: 900px; max-width: 95%; max-height: 90vh; overflow-y: auto; display: flex; flex-direction: column;">
            <div class="modal-header" style="flex-direction: column; align-items: center; text-align: center; gap: 0.75rem; padding-bottom: 1.5rem;">
                <div class="header-center-col" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; width: 100%;">
                    <h2 id="detailTitle" class="modal-title" style="margin: 0; font-size: 1.8rem;">Work Title</h2>
                    <div id="detailWorkTypeContainer"></div>
                    <div id="workProgress" class="multi-step-progress" style="margin-top: 0.5rem; justify-content: center; gap: 45px;">
                        <!-- 4 levels injected by JS -->
                    </div>
                </div>
                <button class="close-btn" onclick="closeModal('workDetailsModal')" style="top: 1.5rem; right: 1.5rem;">&times;</button>
            </div>
            
            <div class="details-content">
                <div class="details-sidebar">
                    <div class="detail-item">
                        <label>College</label>
                        <p id="detailCollege">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Authors</label>
                        <p id="detailAuthors">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Date Created</label>
                        <p id="detailDate">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <p id="detailStatus">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Proposed Budget</label>
                        <p id="detailBudget">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Starting Date</label>
                        <p id="detailStartingDate">-</p>
                    </div>
                    <div class="detail-item">
                        <label>Completion Date</label>
                        <p id="detailCompletionDate">-</p>
                    </div>
                </div>
                <div class="details-main">
                    <h3>Uploaded Files</h3>
                    <div id="detailFiles" class="file-list"></div>
                    
                    <h3 style="margin-top: 2rem;">Evaluation Comments</h3>
                    <div id="detailComments" class="comments-list"></div>
                </div>
            </div>

            <div class="modal-footer" id="detailActions">
                <!-- Actions will be injected based on role -->
            </div>
            <button class="close-btn" onclick="closeModal('workDetailsModal')">&times;</button>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal glass" style="width: 400px; text-align: center;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Logout</h2>
            </div>
            <p style="margin-bottom: 2rem;">Are you sure you want to log out of ScholarVault?</p>
            <div style="display: flex; gap: 1rem;">
                <a href="api/logout.php" class="btn btn-primary" style="flex: 1;">Yes, Logout</a>
                <button class="btn btn-outline" onclick="closeModal('logoutModal')" style="flex: 1;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmationModal" class="modal-overlay">
        <div class="modal glass" style="width: 400px; text-align: center;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
            </div>
            <p style="margin-bottom: 2rem;">Are you sure you want to delete this work? This action cannot be undone.</p>
            <div style="display: flex; gap: 1rem;">
                <button id="finalDeleteBtn" class="btn btn-primary" style="flex: 1; background-color: #ef4444;">Delete</button>
                <button class="btn btn-outline" onclick="closeModal('deleteConfirmationModal')" style="flex: 1;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Notifications Dropdown (Premium Floating Modal style) -->
    <div id="notificationDropdown" class="notif-dropdown glass">
        <div class="notif-dropdown-header">
            <h3>Notifications</h3>
        </div>
        <div id="notificationBody" class="notif-dropdown-body">
            <!-- Items injected by JS -->
            <div class="notif-skeleton">
                <div class="skeleton-avatar"></div>
                <div class="skeleton-lines">
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line short"></div>
                </div>
            </div>
            <div class="notif-skeleton">
                <div class="skeleton-avatar"></div>
                <div class="skeleton-lines">
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line short"></div>
                </div>
            </div>
        </div>
        <div class="notif-dropdown-footer">
            <a href="#" id="seeAllNotifLink" onclick="toggleExpandNotifications(event)">See all notifications</a>
        </div>
    </div>

    <style>
    .multi-step-progress {
        display: flex;
        gap: 40px;
        position: relative;
        width: fit-content;
    }
    .multi-step-progress::before {
        content: '';
        position: absolute;
        top: 16px;
        left: 30px;
        right: 30px;
        height: 2px;
        background: var(--border-color);
        z-index: 0;
    }
    .progress-step {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 60px;
    }
    .step-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #9ca3af; /* Gray default */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
        transition: var(--transition);
        border: 3px solid var(--card-bg);
        box-shadow: 0 0 0 1px var(--border-color);
    }
    .progress-step.current .step-icon { background: #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.4); }
    .progress-step.approved .step-icon { background: #10b981; }
    .progress-step.rejected .step-icon { background: #ef4444; }
    
    .step-label {
        font-size: 10px;
        margin-top: 6px;
        opacity: 0.8;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .details-content {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 2rem;
        margin-top: 0.5rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1.5rem;
        overflow-y: auto;
        flex: 1;
    }
    .details-sidebar .detail-item { margin-bottom: 1.5rem; }
    .details-sidebar label { font-size: 0.8rem; opacity: 0.6; display: block; margin-bottom: 5px; }
    .details-sidebar p { font-weight: 600; }
    
    .comment-card {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        border-left: 5px solid var(--border-color);
        background: rgba(156, 163, 175, 0.18);
    }

    /* Remove colored left borders/lines (use status text only) */
    .comment-card.status-Green { border-left-color: var(--border-color); }
    .comment-card.status-Red { border-left-color: var(--border-color); }
    .comment-card.status-Yellow { border-left-color: var(--border-color); }
    .comment-card.status-Gray { border-left-color: var(--border-color); }

    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        font-size: 0.85rem;
        color: var(--text-light);
    }

    .comment-author { font-weight: 700; }

    .comment-text {
        font-size: 0.95rem;
        line-height: 1.5;
        color: var(--text-light);
        opacity: 0.9;
        margin-bottom: 6px;
    }

    /* Status label inside the comment (we style the inline status div below) */
    .comment-status {
        font-weight: 800;
        font-size: 0.9rem;
    }

    .comment-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: rgba(156, 163, 175, 0.15);
        font-weight: 800;
        font-size: 0.8rem;
        width: fit-content;
    }

    .comment-status.status-Green { background-color: #10b981; color: #ffffff; border-color: #10b981; }
    .comment-status.status-Red { background-color: #ef4444; color: #ffffff; border-color: #ef4444; }
    .comment-status.status-Yellow { background-color: #f59e0b; color: #ffffff; border-color: #f59e0b; }
    .comment-status.status-Gray { background-color: #9ca3af; color: #ffffff; border-color: #9ca3af; }

    body.dark .comment-card {
        background: rgba(156, 163, 175, 0.12);
    }

    /* Notification Dropdown Styles */
    .notif-dropdown {
        position: absolute;
        top: 75px;
        right: 2rem;
        width: 360px;
        max-height: 480px;
        border-radius: 16px;
        display: none;
        flex-direction: column;
        z-index: 1100;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        animation: slideDown 0.3s ease-out;
        transition: max-height 0.35s ease, width 0.35s ease;
    }

    .notif-dropdown.expanded {
        max-height: 80vh;
    }

    .notif-dropdown.active {
        display: flex;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .notif-dropdown-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notif-dropdown-header h3 { margin: 0; font-size: 1.1rem; }
    
    .mark-all-btn {
        background: none;
        border: none;
        color: var(--primary);
        font-size: 0.85rem;
        cursor: pointer;
        font-weight: 600;
    }

    .notif-dropdown-body {
        padding: 0.5rem;
        overflow-y: auto;
        flex: 1;
        scroll-behavior: smooth;
    }

    .notif-item {
        padding: 0.75rem 0.75rem;
        border-radius: 12px;
        display: flex;
        gap: 12px;
        cursor: pointer;
        transition: background 0.2s;
        margin-bottom: 4px;
    }

    .notif-item:hover {
        background: rgba(16, 185, 129, 0.1);
    }

    .notif-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #4b5563;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: white;
        font-size: 1.2rem;
    }

    .notif-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
    }

    .notif-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0;
        line-height: 1.3;
    }

    .notif-time {
        font-size: 0.75rem;
        opacity: 0.6;
    }

    .notif-dropdown-footer {
        padding: 0.75rem;
        text-align: center;
        border-top: 1px solid var(--border-color);
    }

    .notif-dropdown-footer a {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: opacity 0.2s;
    }

    .notif-dropdown-footer a:hover {
        opacity: 0.75;
    }

    /* Skeleton Loading */
    .notif-skeleton {
        display: flex;
        gap: 12px;
        padding: 0.75rem;
    }

    .skeleton-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(156, 163, 175, 0.2);
    }

    .skeleton-lines {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        justify-content: center;
    }

    .skeleton-line {
        height: 10px;
        background: rgba(156, 163, 175, 0.2);
        border-radius: 4px;
        width: 90%;
    }

    .skeleton-line.short {
        width: 60%;
    }

    /* Unread notification highlight */
    .notif-item.unread {
        background: rgba(16, 185, 129, 0.08);
        border-left: 3px solid var(--primary);
        position: relative;
    }

    .notif-item.unread .notif-title::before {
        content: '';
        display: inline-block;
        width: 7px;
        height: 7px;
        background: #10b981;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
        flex-shrink: 0;
    }

    /* Red dot pulse on bell */
    .notification-bell .badge {
        animation: badgePulse 2s infinite;
    }

    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
        50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
    }

    /* Author role badges in detail modal */
    #detailAuthors {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
    }
    .detail-author-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .leader-detail-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
    }
    .leader-detail-badge i { font-size: 0.75rem; }
    .member-detail-badge {
        background: rgba(156, 163, 175, 0.25);
        color: var(--text-light);
        border: 1px solid var(--border-color);
    }
    /* ── Toast Notification ──────────────────────────────────────────────────── */
    .sv-toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        color: #fff;
        font-weight: 600;
        font-size: 0.9rem;
        z-index: 9999;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s, transform 0.3s;
        pointer-events: none;
        max-width: 380px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.25);
    }
    .sv-toast.show {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .sv-toast.success { background: linear-gradient(135deg, #10b981, #059669); }
    .sv-toast.error   { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .sv-toast.info    { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    </style>

    <!-- Toast container -->
    <div id="svToast" class="sv-toast"></div>

    <script src="assets/js/dashboard.js" defer></script>
    <script src="assets/js/security.js"></script>
</body>
</html>
