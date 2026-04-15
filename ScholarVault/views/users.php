<?php
// views/users.php
require_role('Admin');
?>
<div class="view-header">
    <div>
        <h2>User Management</h2>
        <p>Manage proponents and evaluators registered in the system.</p>
    </div>
</div>

<div class="glass" style="margin-top: 1.5rem; padding: 1.5rem;">
    <div style="display: grid; grid-template-columns: 2.5fr 1fr 1fr; gap: 1rem; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label><i class="fas fa-search"></i> Search User</label>
            <input type="text" id="userSearchInput" class="form-input" placeholder="Search by name, username, or email...">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>College</label>
            <select id="userCollegeFilter" class="form-input">
                <option value="">All Colleges</option>
                <option value="CEIT">CEIT</option>
                <option value="CTHM">CTHM</option>
                <option value="CITTE">CITTE</option>
                <option value="CBA">CBA</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Show</label>
            <select id="userLimitFilter" class="form-input">
                <option value="10">10 entries</option>
                <option value="25">25 entries</option>
                <option value="50">50 entries</option>
                <option value="100">100 entries</option>
                <option value="all">All</option>
            </select>
        </div>
    </div>
</div>

<div
    style="display: flex; gap: 1rem; margin-top: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <button class="btn switch-btn active" id="tabProponents" onclick="switchUserTab('proponents')"
        style="flex: none; padding: 0.5rem 1.5rem; background-color: var(--primary); color: white;">Proponents</button>
    <button class="btn switch-btn" id="tabEvaluators" onclick="switchUserTab('evaluators')"
        style="flex: none; padding: 0.5rem 1.5rem;">Evaluators</button>
</div>

<!-- Proponents Table -->
<div class="glass tab-content" id="contentProponents" style="margin-top: 1.5rem; overflow-x: auto; display: block;">
    <table class="user-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>College</th>
                <th>Email</th>
                <th>Status</th>
                <th>Avatar</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="proponentsTableBody">
            <!-- Dynamic -->
        </tbody>
    </table>
    <div id="userPaginationBar" class="pagination-bar" style="display: none;">
        <span id="userPaginationInfo" class="pagination-info"></span>
        <div class="pagination-controls">
            <button class="btn-page" id="userPrevPage" onclick="changeUserPage(-1)"><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="userPageIndicator" class="page-indicator"></span>
            <button class="btn-page" id="userNextPage" onclick="changeUserPage(1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<!-- Evaluators Table -->
<div class="glass tab-content" id="contentEvaluators" style="margin-top: 1.5rem; overflow-x: auto; display: none;">
    <table class="user-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Position</th>
                <th>Email</th>
                <th>Status</th>
                <th>Avatar</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="evaluatorsTableBody">
            <!-- Dynamic -->
        </tbody>
    </table>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal-overlay">
    <div class="modal glass" style="width: 550px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit User Details</h2>
        </div>
        <form id="editUserForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editUserId">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="editFirstName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" id="editMiddleName" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="editLastName" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="editUsername" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="editEmail" class="form-input" required>
            </div>

            <div id="editCollegeGroup" class="form-group" style="display: none;">
                <label>College</label>
                <select name="college" id="editCollege" class="form-input">
                    <option value="CEIT">CEIT</option>
                    <option value="CTHM">CTHM</option>
                    <option value="CITTE">CITTE</option>
                    <option value="CBA">CBA</option>
                </select>
            </div>

            <div id="editPositionGroup" class="form-group" style="display: none;">
                <label>Position</label>
                <select name="position" id="editPosition" class="form-input">
                    <option value="Research Coordinator">Research Coordinator</option>
                    <option value="Extension Coordinator">Extension Coordinator</option>
                    <option value="College Dean">College Dean</option>
                    <option value="Head">Head</option>
                    <option value="Division Chief">Division Chief</option>
                </select>
            </div>

            <div class="form-group">
                <label>New Password (Leave blank to keep current)</label>
                <div class="password-input-container">
                    <input type="password" name="password" id="editPassword" class="form-input"
                        placeholder="Enter new password">
                    <button type="button" class="eye-btn" onclick="togglePassword('editPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div id="editUserMessage" class="form-message"></div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')"
                    style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .user-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .user-table th,
    .user-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .user-table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        opacity: 0.6;
    }

    .badge-role {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        background: var(--bg-light);
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
    }

    .table-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #10b981;
    }

    .pic-review-btn {
        border-color: #f59e0b !important;
        color: #f59e0b;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        animation: picPulse 2s infinite;
    }

    @keyframes picPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
        }

        50% {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0);
        }
    }
</style>

<script>
    let allUsers = [];
    let currentUserTab = 'proponents';
    let userCurrentPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('tab')) {
            switchUserTab(urlParams.get('tab'));
        }
        if (urlParams.has('search')) {
            const searchInput = document.getElementById('userSearchInput');
            if (searchInput) searchInput.value = urlParams.get('search');
        }

        loadUsers();
        
        // Setup Filter listeners
        ['userSearchInput', 'userCollegeFilter', 'userLimitFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', () => { userCurrentPage = 1; applyUserFilters(); });
            }
        });
    });

    function switchUserTab(tab) {
        currentUserTab = tab;
        document.querySelectorAll('.switch-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.style.backgroundColor = 'transparent';
            btn.style.color = '';
        });
        document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');

        if (tab === 'proponents') {
            const btn = document.getElementById('tabProponents');
            btn.classList.add('active');
            btn.style.backgroundColor = 'var(--primary)';
            btn.style.color = 'white';
            document.getElementById('contentProponents').style.display = 'block';
        } else {
            const btn = document.getElementById('tabEvaluators');
            btn.classList.add('active');
            btn.style.backgroundColor = 'var(--primary)';
            btn.style.color = 'white';
            document.getElementById('contentEvaluators').style.display = 'block';
        }
        applyUserFilters();
        userCurrentPage = 1;
    }

    async function loadUsers() {
        try {
            const response = await fetch('api/admin_users.php');
            allUsers = await response.json();
            applyUserFilters();
        } catch (error) {
            console.error(error);
        }
    }

    function applyUserFilters() {
        const searchTerm = document.getElementById('userSearchInput').value.toLowerCase().trim();
        const collegeFilter = document.getElementById('userCollegeFilter').value;

        const filtered = allUsers.filter(user => {
            const fullName = `${user.first_name} ${user.last_name}`.toLowerCase();
            const matchesSearch = searchTerm === "" || 
                                 fullName.includes(searchTerm) || 
                                 (user.username || '').toLowerCase().includes(searchTerm) || 
                                 (user.email || '').toLowerCase().includes(searchTerm);
            
            const matchesCollege = collegeFilter === "" || user.college === collegeFilter;
            
            const matchesTab = (currentUserTab === 'proponents' && user.role === 'Proponent') ||
                               (currentUserTab === 'evaluators' && user.role !== 'Proponent');

            return matchesSearch && matchesCollege && matchesTab;
        });

        const limit = document.getElementById('userLimitFilter').value;
        if (limit === 'all') {
            renderUsers(filtered);
            document.getElementById('userPaginationBar').style.display = 'none';
        } else {
            const perPage = parseInt(limit);
            const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
            if (userCurrentPage > totalPages) userCurrentPage = totalPages;
            const start = (userCurrentPage - 1) * perPage;
            const pageItems = filtered.slice(start, start + perPage);
            renderUsers(pageItems);
            renderUserPagination(filtered.length, perPage, totalPages);
        }
    }

    function renderUserPagination(total, perPage, totalPages) {
        const bar = document.getElementById('userPaginationBar');
        const info = document.getElementById('userPaginationInfo');
        const indicator = document.getElementById('userPageIndicator');
        const prevBtn = document.getElementById('userPrevPage');
        const nextBtn = document.getElementById('userNextPage');

        if (total === 0) { bar.style.display = 'none'; return; }
        bar.style.display = 'flex';
        const start = (userCurrentPage - 1) * perPage + 1;
        const end = Math.min(userCurrentPage * perPage, total);
        info.textContent = `Showing ${start}-${end} of ${total} entries`;
        indicator.textContent = `Page ${userCurrentPage} of ${totalPages}`;
        prevBtn.disabled = userCurrentPage <= 1;
        nextBtn.disabled = userCurrentPage >= totalPages;
    }

    function changeUserPage(dir) {
        userCurrentPage += dir;
        applyUserFilters();
    }

    function renderUsers(users) {
        const proponentsBody = document.getElementById('proponentsTableBody');
        const evaluatorsBody = document.getElementById('evaluatorsTableBody');

        const proponents = users.filter(u => u.role === 'Proponent');
        const evaluators = users.filter(u => u.role !== 'Proponent');

        // Render Proponents
        proponentsBody.innerHTML = proponents.length ? proponents.map(user => `
            <tr>
                <td>${user.username}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td><span class="badge-role">${user.role}</span></td>
                <td>${user.college || '-'}</td>
                <td>${user.email}</td>
                <td>${renderStatus(user)}</td>
                <td>${renderPicAction(user)}</td>
                <td>${renderActions(user)}</td>
            </tr>
        `).join('') : '<tr><td colspan="8" style="text-align:center; opacity:0.5; padding: 2rem;">No proponents found matching your criteria.</td></tr>';

        // Render Evaluators
        evaluatorsBody.innerHTML = evaluators.length ? evaluators.map(user => `
            <tr>
                <td>${user.username}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td><span class="badge-role">${user.role}</span></td>
                <td>${user.position || '-'}</td>
                <td>${user.email}</td>
                <td>${renderStatus(user)}</td>
                <td>${renderPicAction(user)}</td>
                <td>${renderActions(user)}</td>
            </tr>
        `).join('') : '<tr><td colspan="8" style="text-align:center; opacity:0.5; padding: 2rem;">No evaluators found matching your criteria.</td></tr>';
    }

    function renderStatus(user) {
        if (user.is_approved == 0) {
            return '<span style="color: #f59e0b; font-weight:600;">Pending Approval</span>';
        }
        const color = user.is_blocked == 1 ? '#ef4444' : '#10b981';
        const text = user.is_blocked == 1 ? 'Blocked' : 'Active';
        return `<span style="color: ${color}; font-weight:600;">${text}</span>`;
    }

    function renderPicAction(user) {
        if (user.pending_pic_id) {
            return `
            <button class="btn btn-outline btn-sm pic-review-btn" onclick="previewPicture(${user.pending_pic_id}, '${user.pending_pic_path}', '${user.first_name} ${user.last_name}')" title="Review pending picture">
                <i class="fas fa-camera" style="color: #f59e0b;"></i> Review
            </button>`;
        }
        if (user.approved_pic_path) {
            return `<img src="${user.approved_pic_path}" class="table-avatar" title="Approved">`;
        }
        return '<span style="opacity:0.4; font-size:0.8rem;">None</span>';
    }

    function renderActions(user) {
        let actionButtons = '';
        if (user.is_approved == 0) {
            actionButtons += `<button class="btn btn-primary btn-sm" onclick="approveUser(${user.id})" style="background: #10b981; border-color: #10b981;">Approve</button>`;
        } else {
            const blockText = user.is_blocked == 1 ? 'Unblock' : 'Block';
            actionButtons += `<button class="btn btn-outline btn-sm" onclick="toggleBlock(${user.id})">${blockText}</button>`;
        }
        actionButtons += ` <button class="btn btn-outline btn-sm" style="color: var(--primary);" onclick="editUser(${user.id})">Edit</button>`;

        return `<div style="display: flex; gap: 0.5rem;">${actionButtons}</div>`;
    }

    async function toggleBlock(id) {
        if (!confirm('Are you sure you want to change this user\'s status?')) return;

        const formData = new FormData();
        formData.append('action', 'toggle_block');
        formData.append('id', id);

        await fetch('api/admin_users.php', { method: 'POST', body: formData });
        loadUsers();
    }

    async function approveUser(id) {
        if (!confirm('Approve this user registration?')) return;

        const formData = new FormData();
        formData.append('action', 'approve');
        formData.append('id', id);

        const response = await fetch('api/admin_users.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            loadUsers();
        }
    }

    async function editUser(id) {
        const formData = new FormData();
        formData.append('action', 'get_user');
        formData.append('id', id);

        try {
            const response = await fetch('api/admin_users.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                const user = data.user;
                document.getElementById('editUserId').value = user.id;
                document.getElementById('editFirstName').value = user.first_name;
                document.getElementById('editMiddleName').value = user.middle_name || '';
                document.getElementById('editLastName').value = user.last_name;
                document.getElementById('editUsername').value = user.username;
                document.getElementById('editEmail').value = user.email;
                document.getElementById('editPassword').value = ''; // Clear password field

                const collegeGroup = document.getElementById('editCollegeGroup');
                const positionGroup = document.getElementById('editPositionGroup');

                if (user.role === 'Proponent') {
                    collegeGroup.style.display = 'block';
                    positionGroup.style.display = 'none';
                    document.getElementById('editCollege').value = user.college;
                } else {
                    collegeGroup.style.display = 'none';
                    positionGroup.style.display = 'block';
                    document.getElementById('editPosition').value = user.position;
                }

                openModal('editUserModal');
            }
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    }

    document.getElementById('editUserForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const msgDiv = document.getElementById('editUserMessage');

        try {
            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                msgDiv.style.color = 'var(--primary)';
                msgDiv.textContent = data.message;
                setTimeout(() => {
                    closeModal('editUserModal');
                    loadUsers();
                    msgDiv.textContent = '';
                }, 1500);
            } else {
                msgDiv.style.color = '#ef4444';
                msgDiv.textContent = data.message;
            }
        } catch (error) {
            msgDiv.textContent = 'An error occurred.';
        }
    });
    // ─── Profile Picture Approval ────────────────────────────────────────────
    function previewPicture(picId, picPath, userName) {
        document.getElementById('previewPicImage').src = picPath;
        document.getElementById('previewPicUserName').textContent = userName;
        document.getElementById('previewPicApproveBtn').onclick = () => approvePicture(picId);
        document.getElementById('previewPicRejectBtn').onclick = () => rejectPicture(picId);
        openModal('previewPicModal');
    }

    async function approvePicture(picId) {
        const formData = new FormData();
        formData.append('action', 'approve');
        formData.append('pic_id', picId);

        try {
            const res = await fetch('api/profile_picture.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                else alert(data.message);
                closeModal('previewPicModal');
                loadUsers();
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
                else alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function rejectPicture(picId) {
        if (!confirm('Reject this profile picture?')) return;
        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('pic_id', picId);

        try {
            const res = await fetch('api/profile_picture.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                else alert(data.message);
                closeModal('previewPicModal');
                loadUsers();
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
                else alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>

<!-- Profile Picture Preview / Approval Modal -->
<div id="previewPicModal" class="modal-overlay">
    <div class="modal glass" style="width: 420px; text-align: center;">
        <div class="modal-header">
            <h2 class="modal-title">Profile Picture Review</h2>
        </div>
        <p id="previewPicUserName" style="font-weight: 600; margin-bottom: 1rem; font-size: 1.05rem;"></p>
        <div style="margin-bottom: 1.5rem;">
            <img id="previewPicImage" src="" alt="Pending Profile Picture"
                style="width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
        </div>
        <div style="display: flex; gap: 1rem;">
            <button id="previewPicApproveBtn" class="btn btn-primary"
                style="flex: 1; background: #10b981;">Approve</button>
            <button id="previewPicRejectBtn" class="btn btn-outline"
                style="flex: 1; color: #ef4444; border-color: #ef4444;">Reject</button>
        </div>
        <button class="close-btn" onclick="closeModal('previewPicModal')"
            style="position: absolute; top: 1rem; right: 1rem;">&times;</button>
    </div>
</div>