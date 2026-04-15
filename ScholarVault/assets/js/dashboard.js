// Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
}

// Global Modal handlers
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function confirmLogout(e) {
    e.preventDefault();
    openModal('logoutModal');
}

// ─── Unread Notification Helpers ────────────────────────────────────────────
function _seenKey() {
    // Unique per user so multiple users on the same device don't share state
    return `sv_seen_notifs_${currentUserId}`;
}

function getSeenIds() {
    try {
        return new Set(JSON.parse(localStorage.getItem(_seenKey()) || '[]'));
    } catch { return new Set(); }
}

function markIdsAsSeen(ids) {
    const seen = getSeenIds();
    ids.forEach(id => seen.add(String(id)));
    localStorage.setItem(_seenKey(), JSON.stringify([...seen]));
}

function showBadge(show) {
    const badge = document.getElementById('notificationBadge');
    if (badge) badge.style.display = show ? 'block' : 'none';
}

// Track last known notification count so we can show a count on the badge
let _lastUnseenCount = 0;

// Silent background poll – shows red dot if there are unseen notifications
async function pollNotificationBadge() {
    try {
        const formData = new FormData();
        formData.append('action', 'notifications');
        const endpoint = currentUserRole === 'Admin' ? 'api/admin_users.php' : 'api/works.php';
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        const notifications = data.notifications || [];
        const seen = getSeenIds();
        const unseenCount = notifications.filter(n => !seen.has(String(n.id))).length;
        _lastUnseenCount = unseenCount;
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (unseenCount > 0) {
                badge.style.display = 'block';
                badge.textContent = ''; // Just show the red dot
            } else {
                badge.style.display = 'none';
                badge.textContent = '';
            }
        }
    } catch { /* silent */ }
}

// ─── Notifications (Dropdown style) ─────────────────────────────────────────
async function openNotifications(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const dropdown = document.getElementById('notificationDropdown');
    const body = document.getElementById('notificationBody');
    
    if (!dropdown || !body) return;

    // Toggle dropdown
    const isActive = dropdown.classList.contains('active');
    
    if (isActive) {
        dropdown.classList.remove('active', 'expanded');
        const link = document.getElementById('seeAllNotifLink');
        if (link) link.textContent = 'See all notifications';
        return;
    }

    dropdown.classList.add('active');
    body.innerHTML = '<div style="opacity:0.7; padding: 1rem;">Loading…</div>';

    // Update the header to include a "Mark all read" button
    const header = document.querySelector('.notif-dropdown-header');
    if (header && !document.getElementById('markAllReadBtn')) {
        const markBtn = document.createElement('button');
        markBtn.id = 'markAllReadBtn';
        markBtn.className = 'mark-all-btn';
        markBtn.textContent = 'Mark all read';
        markBtn.onclick = markAllNotificationsRead;
        header.appendChild(markBtn);
    }

    // Evaluator: show newly arrived works pending for their step
    if (currentUserRole === 'Evaluator') {
        await renderEvaluatorNotifications(body);
    } 
    // Proponent: show works with evaluations / status updates
    else if (currentUserRole === 'Proponent') {
        await renderProponentNotifications(body);
    } 
    // Admin: show pending user accounts and profile pictures
    else if (currentUserRole === 'Admin') {
        await renderAdminNotifications(body);
    } else {
        body.innerHTML = '<div style="opacity:0.7; padding: 1.5rem; text-align:center;">No notifications for your role.</div>';
    }
}

function closeNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.classList.remove('active', 'expanded');
    }
    const link = document.getElementById('seeAllNotifLink');
    if (link) link.textContent = 'See all notifications';
}

function toggleExpandNotifications(e) {
    if (e) e.preventDefault();
    const dropdown = document.getElementById('notificationDropdown');
    const link = document.getElementById('seeAllNotifLink');
    if (!dropdown) return;

    const isExpanded = dropdown.classList.toggle('expanded');
    if (link) {
        link.textContent = isExpanded ? 'Collapse ▲' : 'See all notifications';
    }
    if (isExpanded) {
        const body = document.getElementById('notificationBody');
        if (body) body.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Global click to close dropdown when clicking outside
window.addEventListener('click', (e) => {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (dropdown && dropdown.classList.contains('active')) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.classList.remove('active', 'expanded');
            const link = document.getElementById('seeAllNotifLink');
            if (link) link.textContent = 'See all notifications';
        }
    }
});

async function renderEvaluatorNotifications(container) {
    try {
        const formData = new FormData();
        formData.append('action', 'notifications');

        const res = await fetch('api/works.php', { method: 'POST', body: formData });
        const data = await res.json();
        const notifications = (data.notifications || []);

        if (!notifications.length) {
            container.innerHTML = '<div style="opacity:0.7; padding: 1.5rem; text-align:center;">No new works pending.</div>';
            return;
        }

        const seen = getSeenIds();

        container.innerHTML = notifications.map(w => {
            const isUnread = !seen.has(String(w.id));
            return `
                <div class="notif-item${isUnread ? ' unread' : ''}" data-notif-id="${w.id}" onclick="markNotifRead(${w.id}); viewWorkDetails(${w.id}); closeNotifications();">
                    <div class="notif-avatar" style="background: var(--primary);">
                        <i class="fas fa-file-import"></i>
                    </div>
                    <div class="notif-content">
                        <p class="notif-title">New submission: <strong>${escapeHtml(w.title)}</strong> needs your evaluation.</p>
                        <span class="notif-time"><i class="fas fa-clock"></i> ${escapeHtml(w.created_at || 'Just now')}</span>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error(err);
        container.innerHTML = '<div style="opacity:0.7; padding: 1rem;">Failed to load notifications.</div>';
    }
}

async function renderProponentNotifications(container) {
    try {
        const formData = new FormData();
        formData.append('action', 'notifications');

        const res = await fetch('api/works.php', { method: 'POST', body: formData });
        const data = await res.json();
        const notifications = (data.notifications || []);

        if (!notifications.length) {
            container.innerHTML = '<div style="opacity:0.7; padding: 1.5rem; text-align:center;">No updates yet.</div>';
            return;
        }

        const seen = getSeenIds();

        container.innerHTML = notifications.map(w => {
            let icon = 'fa-info-circle';
            let color = '#4b5563';
            let message = `Update for "${w.title}"`;

            if (w.status === 'Green') {
                icon = 'fa-check-circle';
                color = '#10b981';
                message = `Your work "<strong>${escapeHtml(w.title)}</strong>" was <strong>approved</strong> by ${escapeHtml(w.current_step)}.`;
                if (w.current_step === 'Published') {
                    message = `Congratulations! Your work "<strong>${escapeHtml(w.title)}</strong>" is now <strong>Approved</strong>.`;
                }
            } else if (w.status === 'Red') {
                icon = 'fa-exclamation-triangle';
                color = '#ef4444';
                message = `Revision requested for "<strong>${escapeHtml(w.title)}</strong>" at ${escapeHtml(w.current_step)}.`;
            } else if (w.status === 'Yellow') {
                icon = 'fa-clock';
                color = '#f59e0b';
                message = `Your work "<strong>${escapeHtml(w.title)}</strong>" moved to ${escapeHtml(w.current_step)}.`;
            }

            const isUnread = !seen.has(String(w.id));
            return `
                <div class="notif-item${isUnread ? ' unread' : ''}" data-notif-id="${w.id}" onclick="markNotifRead(${w.id}); viewWorkDetails(${w.id}); closeNotifications();">
                    <div class="notif-avatar" style="background: ${color};">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="notif-content">
                        <p class="notif-title">${message}</p>
                        <span class="notif-time"><i class="fas fa-clock"></i> ${escapeHtml(w.created_at || 'Recent')}</span>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error(err);
        container.innerHTML = '<div style="opacity:0.7; padding: 1rem;">Failed to load notifications.</div>';
    }
}

async function renderAdminNotifications(container) {
    try {
        const formData = new FormData();
        formData.append('action', 'notifications');

        const res = await fetch('api/admin_users.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (!data.success || !data.notifications || data.notifications.length === 0) {
            container.innerHTML = '<div style="opacity:0.7; padding: 1.5rem; text-align:center;">No notifications right now.</div>';
            return;
        }

        const notifications = data.notifications;
        const seen = getSeenIds();
        
        container.innerHTML = notifications.map(n => {
            const isUnread = !seen.has(String(n.id));
            const timeObj = new Date(n.timestamp);
            const timeStr = timeObj.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            const isDone = n.status === 'done';
            
            let icon = 'fa-user-plus';
            
            if (n.type === 'pending_user') {
                icon = 'fa-user-plus';
            } else if (n.type === 'approved_user') {
                icon = 'fa-user-check';
            } else if (n.type === 'pending_pic') {
                icon = 'fa-camera';
            } else if (n.type === 'done_pic') {
                icon = 'fa-image';
            }

            const color = 'var(--primary)';
            const clickAction = `markNotifRead('${n.id}'); window.location.href='?page=users&tab=${n.target_tab}&search=${encodeURIComponent(n.target_name)}';`;

            return `
                <div class="notif-item${isUnread ? ' unread' : ''}" data-notif-id="${n.id}" onclick="${clickAction}">
                    <div class="notif-avatar" style="background: ${color};">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="notif-content">
                        <p class="notif-title">${n.message}</p>
                        <span class="notif-time"><i class="fas fa-clock"></i> ${timeStr}</span>
                    </div>
                </div>
            `;
        }).join('');
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div style="color:#ef4444; padding:1.5rem; text-align:center;">Error loading admin notifications.</div>';
    }
}

// Mark a single notification as read immediately on click (removes highlight)
function markNotifRead(id) {
    markIdsAsSeen([String(id)]);
    // Remove unread class from the clicked item visually
    const el = document.querySelector(`.notif-item[data-notif-id="${id}"]`);
    if (el) el.classList.remove('unread');
    // Re-poll badge immediately to update count
    pollNotificationBadge();
}

// Mark ALL notifications as read – triggered by "Mark all read" button
async function markAllNotificationsRead() {
    const items = document.querySelectorAll('.notif-item[data-notif-id]');
    const ids = [];
    items.forEach(el => {
        ids.push(el.getAttribute('data-notif-id'));
        el.classList.remove('unread');
    });
    markIdsAsSeen(ids);
    // Immediately hide badge and update count
    const badge = document.getElementById('notificationBadge');
    if (badge) { badge.style.display = 'none'; badge.textContent = ''; }
    // Show feedback
    if (typeof showToast === 'function') showToast('All notifications marked as read', 'success');
}

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function mapEvalStatusLabel(status) {
    switch (String(status || '').toLowerCase()) {
        case 'green':
            return 'Approved';
        case 'red':
            return 'Need Revision';
        case 'yellow':
            return 'Pending';
        case 'gray':
        case 'grey':
            return 'Unread';
        default:
            return status || '';
    }
}

// Close modal on outside click
window.onclick = function (event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}

// Mobile sidebar toggle element was removed in dashboard.php

// Theme Toggle (Shared with index.php)
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    function setTheme(theme) {
        if (theme === 'dark') {
            body.classList.remove('light');
            body.classList.add('dark');
            if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun" style="font-size: 1.30rem;"></i>';
        } else {
            body.classList.remove('dark');
            body.classList.add('light');
            if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-moon" style="font-size: 1.30rem;"></i>';
        }
        document.cookie = `theme=${theme}; path=/`;
        localStorage.setItem('theme', theme);
        window.dispatchEvent(new CustomEvent('svThemeChanged', { detail: { theme } }));
    }

    // Check localStorage for theme consistency across logins
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = body.classList.contains('dark');
            setTheme(isDark ? 'light' : 'dark');
        });
    }

    // Poll for unseen notifications and auto-refresh lists for live feel
    if (currentUserRole !== '') { // If logged in
        pollNotificationBadge();
        setInterval(() => {
            pollNotificationBadge(true); // pass true for silent/background check if applicable

            // Auto reload lists if no modal is active
            const isModalOpen = document.querySelector('.modal-overlay.active');
            if (!isModalOpen) {
                if (typeof window.loadWorks === 'function') window.loadWorks();
                if (typeof loadOngoingWorks === 'function') loadOngoingWorks();
                if (typeof loadPublishedWorks === 'function') loadPublishedWorks();
                if (typeof loadUsers === 'function') loadUsers();
                if (typeof loadLogs === 'function') loadLogs();
            }

            // Re-render dropdown if open for real-time notifications feel
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && dropdown.classList.contains('active')) {
                const body = document.getElementById('notificationBody');
                if (currentUserRole === 'Evaluator' && typeof renderEvaluatorNotifications === 'function') renderEvaluatorNotifications(body);
                if (currentUserRole === 'Proponent' && typeof renderProponentNotifications === 'function') renderProponentNotifications(body);
                if (currentUserRole === 'Admin' && typeof renderAdminNotifications === 'function') renderAdminNotifications(body);
            }
        }, 10000); // re-check every 10 seconds for live feel
    }
});
// Active Nav Item handling (if not handled by PHP)
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
    });
});

// Close sidebar on mobile when clicking nav items
let evalFiles = []; // Track evaluator files

// Global Work View Details
async function viewWorkDetails(id) {
    evalFiles = []; // Reset evaluating files
    try {
        const formData = new FormData();
        formData.append('action', 'details');
        formData.append('id', id);

        const response = await fetch('api/works.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            const work = data.work;
            // Set title and type badge
            document.getElementById('detailTitle').textContent = work.title;
            const typeContainer = document.getElementById('detailWorkTypeContainer');
            if (typeContainer) {
                const typeClass = work.work_type === 'Extension' ? 'type-extension' : 'type-research';
                const typeIcon = work.work_type === 'Extension' ? 'hand-holding-heart' : 'microscope';
                typeContainer.innerHTML = `
                    <span class="list-type-badge ${typeClass}" style="padding: 4px 12px; font-size: 0.85rem;">
                        <i class="fas fa-${typeIcon}"></i> ${work.work_type}
                    </span>
                `;
            }

            document.getElementById('detailCollege').textContent = work.college || (work.colleges ? work.colleges.split(',')[0] : 'N/A');
            // Build author display with leader/member labels
            const authorsList = work.authors_list || [];
            const leaderEntry = authorsList.find(a => a.role === 'leader');
            const members = authorsList.filter(a => a.role !== 'leader');
            let authorsHtml = '';
            if (leaderEntry) {
                authorsHtml += `<span class="detail-author-badge leader-detail-badge"><i class="fas fa-crown"></i> ${escapeHtml(leaderEntry.name)}</span>`;
            }
            members.forEach(m => {
                authorsHtml += `<span class="detail-author-badge member-detail-badge"><i class="fas fa-user"></i> ${escapeHtml(m.name)}</span>`;
            });
            if (!authorsHtml) authorsHtml = escapeHtml(work.author_names || '-');
            document.getElementById('detailAuthors').innerHTML = authorsHtml;
            document.getElementById('detailDate').textContent = work.created_at ? new Date(work.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
            document.getElementById('detailStatus').textContent = mapEvalStatusLabel(work.status);

            const budgetElem = document.getElementById('detailBudget');
            if (budgetElem) {
                budgetElem.textContent = work.total_proposed_budget ? '₱' + parseInt(work.total_proposed_budget).toLocaleString() : 'N/A';
            }
            
            const startingDateElem = document.getElementById('detailStartingDate');
            if (startingDateElem) {
                startingDateElem.textContent = work.proposed_starting_date ? new Date(work.proposed_starting_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
            }

            const completionDateElem = document.getElementById('detailCompletionDate');
            if (completionDateElem) {
                // Adjusting format out of YYYY-MM-DD
                completionDateElem.textContent = work.proposed_completion_date ? new Date(work.proposed_completion_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
            }

            // Files and Links
            const filesDiv = document.getElementById('detailFiles');
            const filesHtml = work.files && work.files.length > 0
                ? work.files.map(f => `
                    <div class="file-item">
                        <a href="${f.file_path}" target="_blank"><i class="fas fa-file-download"></i> ${f.file_name}</a>
                    </div>
                `).join('')
                : '';

            const linksHtml = work.links && work.links.length > 0
                ? work.links.map(l => `
                    <div class="file-item">
                        <a href="${escapeHtml(l)}" target="_blank"><i class="fas fa-external-link-alt"></i> ${escapeHtml(l)}</a>
                    </div>
                `).join('')
                : '';

            if (!filesHtml && !linksHtml) {
                filesDiv.innerHTML = '<p style="opacity:0.6;">No files or links provided.</p>';
            } else {
                filesDiv.innerHTML = filesHtml + linksHtml;
            }

            // Comments
            const commentsDiv = document.getElementById('detailComments');
            commentsDiv.innerHTML = work.evaluations.map(e => {
                const statusLabel = mapEvalStatusLabel(e.status);
                return `
                    <div class="comment-card status-${e.status}">
                        <div class="comment-header">
                            <span class="comment-author">${e.first_name} ${e.last_name} (${e.position})</span>
                            <span class="comment-date">${e.created_at}</span>
                        </div>
                        <div class="comment-text">${e.comment}</div>
                        <div class="comment-status status-${e.status}">${statusLabel}</div>
                    </div>
                `;
            }).join('') || '<p>No comments yet.</p>';

            // Dynamic Progress Bar based on type
            const pipelines = {
                'Research': ['Research Coordinator', 'College Dean', 'Head', 'Division Chief'],
                'Extension': ['Extension Coordinator', 'College Dean', 'Head', 'Division Chief']
            };
            const wType = work.work_type || 'Research';
            const steps = pipelines[wType] || pipelines['Research'];
            
            const progressContainer = document.getElementById('workProgress');
            if (progressContainer) {
                progressContainer.innerHTML = '';
                let reachedCurrent = false;
                steps.forEach((stepName, index) => {
                    const stepEl = document.createElement('div');
                    stepEl.className = 'progress-step';
                    
                    let statusClass = '';
                    if (work.current_step === 'Published') {
                        statusClass = 'approved';
                    } else if (work.current_step === stepName) {
                        statusClass = (work.status === 'Red') ? 'rejected' : 'current';
                        reachedCurrent = true;
                    } else if (!reachedCurrent) {
                        statusClass = 'approved';
                    }

                    if (statusClass) stepEl.classList.add(statusClass);
                    
                    stepEl.innerHTML = `
                        <div class="step-icon">${index + 1}</div>
                        <div class="step-label">${stepName}</div>
                    `;
                    progressContainer.appendChild(stepEl);
                });
            }

            // Replace the inline status text
            const statusEl = document.getElementById('detailStatus');
            if (statusEl) statusEl.textContent = mapEvalStatusLabel(work.status);

            // Action Buttons
            const actionsDiv = document.getElementById('detailActions');
            actionsDiv.innerHTML = '';

            // If User is Proponent and is an author (or creator) and it's NOT Published
            if (currentUserRole === 'Proponent' && work.current_step !== 'Published') {
                // Technically we should check if currentUserId is in work.author_ids
                // Assuming proponents only see their own works in My Works, but viewWorkDetails is global
                actionsDiv.innerHTML = `
                    <div style="display:flex; gap:10px; margin-left:auto;">
                        <button class="btn btn-outline" onclick="editWork(${work.id})" style="border-color:#f59e0b; color:#f59e0b;">
                            <i class="fas fa-edit"></i> Edit Work
                        </button>
                    </div>
                `;
            } else if (currentUserRole === 'Evaluator' && currentUserPosition === work.current_step && work.current_step !== 'Published') {
                actionsDiv.innerHTML = `
                    <div style="flex:1;">
                        <select id="evalStatus" class="form-input" style="margin-bottom:10px;">
                            <option value="Green">Approve (Green)</option>
                            <option value="Red">Need Revision (Red)</option>
                        </select>
                        <textarea id="evalComment" class="form-input" placeholder="Detailed comment..."></textarea>
                        
                        <div style="margin-top:10px;">
                            <input type="file" id="evalFileInput" multiple style="display:none;" onchange="handleEvalFiles(event)">
                            <button class="btn btn-outline" onclick="document.getElementById('evalFileInput').click();" style="font-size: 0.9rem; padding: 5px 10px;">
                                <i class="fas fa-paperclip"></i> Attach Files
                            </button>
                            <div id="evalFileList" style="margin-top: 10px; font-size: 0.85rem; color: #555;"></div>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="submitEvaluation(${work.id})" style="margin-top:10px;">Submit Evaluation</button>
                `;
            }

            openModal('workDetailsModal');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Delete Logic
function confirmDeleteWork(id) {
    const finalDeleteBtn = document.getElementById('finalDeleteBtn');
    finalDeleteBtn.onclick = () => deleteWork(id);
    openModal('deleteConfirmationModal');
}

async function deleteWork(id) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const response = await fetch('api/works.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Work deleted successfully.');
            closeModal('deleteConfirmationModal');
            closeModal('workDetailsModal');
            if (typeof loadWorks === 'function') loadWorks();
            else location.reload(); // Fallback
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Delete error:', error);
    }
}

// Edit Logic (Redirects to works.js handler)
function editWork(id) {
    closeModal('workDetailsModal');
    if (typeof openEditWorkModal === 'function') {
        openEditWorkModal(id);
    } else {
        console.error('openEditWorkModal not found in works.js');
    }
}

// Evaluation Logic
async function submitEvaluation(id) {
    const status = document.getElementById('evalStatus').value;
    const comment = document.getElementById('evalComment').value;

    if (!comment) {
        alert('Please provide a comment.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'evaluate');
    formData.append('id', id);
    formData.append('status', status);
    formData.append('comment', comment);
    for (let i = 0; i < evalFiles.length; i++) {
        formData.append('files[]', evalFiles[i]);
    }

    const response = await fetch('api/works.php', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        alert('Evaluation submitted successfully.');
        closeModal('workDetailsModal');
        if (typeof loadWorks === 'function') loadWorks(); // Refresh list if on works page
    } else {
        alert('Error: ' + data.message);
    }
}

// Evaluation Logic
function handleEvalFiles(event) {
    const files = event.target.files;
    for (let i = 0; i < files.length; i++) {
        evalFiles.push(files[i]);
    }
    renderEvalFiles();
    event.target.value = '';
}

function removeEvalFile(index) {
    evalFiles.splice(index, 1);
    renderEvalFiles();
}

function renderEvalFiles() {
    const list = document.getElementById('evalFileList');
    if (!list) return;
    if (evalFiles.length === 0) {
        list.innerHTML = '';
        return;
    }
    list.innerHTML = evalFiles.map((f, i) => `
        <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-lighter); padding:6px 10px; border-radius:4px; margin-bottom:5px; border: 1px solid var(--border);">
            <span><i class="fas fa-file"></i> ${escapeHtml(f.name)} (${(f.size/1024).toFixed(1)} KB)</span>
            <button class="btn btn-outline" style="padding:2px 6px; border:none; color:var(--danger);" onclick="removeEvalFile(${i})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

// ─── Toast Notification ─────────────────────────────────────────────────────
function showToast(message, type = 'success', duration = 3500) {
    const toast = document.getElementById('svToast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'sv-toast ' + type + ' show';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// ─── Profile Picture Upload ─────────────────────────────────────────────────
async function uploadProfilePicture(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    // Client-side validation
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        showToast('File too large. Maximum size is 2 MB.', 'error');
        input.value = '';
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Invalid file type. Only JPG, PNG, and WebP are allowed.', 'error');
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('picture', file);

    try {
        showToast('Uploading…', 'info', 10000);

        const response = await fetch('api/profile_picture.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            // Avatar stays as default icon — picture only shows after admin approval
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast('Upload failed. Please try again.', 'error');
    }

    input.value = '';
}
