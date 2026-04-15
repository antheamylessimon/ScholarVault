<?php
// views/logs.php
require_role('Admin');
?>
<div class="view-header">
    <div>
        <h2>System Activity Logs</h2>
        <p>Audit trail of all system interactions and evaluations.</p>
    </div>
</div>

<div class="log-tabs glass" style="margin-top: 2rem; padding: 0.5rem; display: flex; gap: 0.5rem; border-radius: 12px;">
    <button class="btn btn-tab active" onclick="switchTheme('auth')">Auth Logs</button>
    <button class="btn btn-tab" onclick="switchTheme('evaluation')">Evaluation Logs</button>
    <button class="btn btn-tab" onclick="switchTheme('submission')">Submission Logs</button>
    <div class="form-group" style="margin-left: auto; margin-bottom: 0; display: flex; align-items: center; gap: 0.5rem; padding-right: 1rem;">
        <label style="white-space: nowrap; font-size: 0.85rem; opacity: 0.8;">Show</label>
        <select id="logLimitFilter" class="form-input" style="padding: 0.4rem 2rem 0.4rem 1rem; font-size: 0.85rem;">
            <option value="10">10 entries</option>
            <option value="25">25 entries</option>
            <option value="50">50 entries</option>
            <option value="100">100 entries</option>
            <option value="all" selected>All</option>
        </select>
    </div>
</div>

<div id="authSection" class="glass" style="margin-top: 1rem; padding: 1rem; border-radius: 16px;">
    <div id="authTableBody" class="works-list" style="margin-top: 0;"></div>
    <div id="authPaginationBar" class="pagination-bar" style="display: none;">
        <span id="authPaginationInfo" class="pagination-info"></span>
        <div class="pagination-controls">
            <button class="btn-page" id="authPrevPage" onclick="changeLogPage('auth', -1)"><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="authPageIndicator" class="page-indicator"></span>
            <button class="btn-page" id="authNextPage" onclick="changeLogPage('auth', 1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<div id="evaluationSection" class="glass" style="margin-top: 1rem; padding: 1rem; border-radius: 16px; display: none;">
    <div id="evaluationTableBody" class="works-list" style="margin-top: 0;"></div>
    <div id="evaluationPaginationBar" class="pagination-bar" style="display: none;">
        <span id="evaluationPaginationInfo" class="pagination-info"></span>
        <div class="pagination-controls">
            <button class="btn-page" id="evaluationPrevPage" onclick="changeLogPage('evaluation', -1)"><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="evaluationPageIndicator" class="page-indicator"></span>
            <button class="btn-page" id="evaluationNextPage" onclick="changeLogPage('evaluation', 1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<div id="submissionSection" class="glass" style="margin-top: 1rem; padding: 1rem; border-radius: 16px; display: none;">
    <div id="submissionTableBody" class="works-list" style="margin-top: 0;"></div>
    <div id="submissionPaginationBar" class="pagination-bar" style="display: none;">
        <span id="submissionPaginationInfo" class="pagination-info"></span>
        <div class="pagination-controls">
            <button class="btn-page" id="submissionPrevPage" onclick="changeLogPage('submission', -1)"><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="submissionPageIndicator" class="page-indicator"></span>
            <button class="btn-page" id="submissionNextPage" onclick="changeLogPage('submission', 1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<style>
.btn-tab {
    background: transparent;
    border: none;
    color: var(--text-light);
    opacity: 0.6;
    padding: 0.75rem 1.5rem;
    cursor: pointer;
    border-radius: 8px;
    font-weight: 600;
}
.btn-tab.active {
    background: var(--primary);
    color: white;
    opacity: 1;
}
</style>

<script>
let allLogs = [];
let logPages = { auth: 1, evaluation: 1, submission: 1 };

document.addEventListener('DOMContentLoaded', loadLogs);

async function loadLogs() {
    try {
        const response = await fetch('api/logs.php');
        allLogs = await response.json();
        renderAllLogs();
        
        document.getElementById('logLimitFilter').addEventListener('change', () => {
            logPages = { auth: 1, evaluation: 1, submission: 1 };
            renderAllLogs();
        });
    } catch (error) {
        console.error(error);
    }
}

function renderAllLogs() {
    renderAuthLogs();
    renderEvaluationLogs();
    renderSubmissionLogs();
}

function switchTheme(section) {
    document.querySelectorAll('.btn-tab').forEach(b => b.classList.remove('active'));
    document.querySelector(`button[onclick="switchTheme('${section}')"]`).classList.add('active');
    
    document.getElementById('authSection').style.display = section === 'auth' ? 'block' : 'none';
    document.getElementById('evaluationSection').style.display = section === 'evaluation' ? 'block' : 'none';
    document.getElementById('submissionSection').style.display = section === 'submission' ? 'block' : 'none';
}

function changeLogPage(section, dir) {
    logPages[section] += dir;
    renderAllLogs();
}

function renderLogPagination(section, total, perPage, currentPage, totalPages) {
    const bar = document.getElementById(section + 'PaginationBar');
    const info = document.getElementById(section + 'PaginationInfo');
    const indicator = document.getElementById(section + 'PageIndicator');
    const prevBtn = document.getElementById(section + 'PrevPage');
    const nextBtn = document.getElementById(section + 'NextPage');

    if (total === 0) { bar.style.display = 'none'; return; }
    bar.style.display = 'flex';
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, total);
    info.textContent = `Showing ${start}-${end} of ${total} entries`;
    indicator.textContent = `Page ${currentPage} of ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
}

function paginateLogs(logs, section) {
    const limit = document.getElementById('logLimitFilter').value;
    if (limit === 'all') return { paginated: logs, showBar: false };
    
    const perPage = parseInt(limit);
    const totalPages = Math.max(1, Math.ceil(logs.length / perPage));
    if (logPages[section] > totalPages) logPages[section] = totalPages;
    const start = (logPages[section] - 1) * perPage;
    const paginated = logs.slice(start, start + perPage);
    renderLogPagination(section, logs.length, perPage, logPages[section], totalPages);
    return { paginated, showBar: true };
}

function renderAuthLogs() {
    const tbody = document.getElementById('authTableBody');
    const authActions = ['Login', 'Logout', 'Registration'];
    let logs = allLogs.filter(l => authActions.includes(l.action));
    
    if (logs.length === 0) {
        tbody.innerHTML = '<div style="text-align: center; padding: 2rem; opacity: 0.5;">No authentication logs found.</div>';
        document.getElementById('authPaginationBar').style.display = 'none';
        return;
    }

    const result = paginateLogs(logs, 'auth');
    logs = result.paginated;
    if (!result.showBar) document.getElementById('authPaginationBar').style.display = 'none';

    tbody.innerHTML = logs.map(log => `
        <div class="work-list-item">
            <div class="work-list-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fas ${log.action === 'Login' ? 'fa-sign-in-alt' : (log.action === 'Logout' ? 'fa-sign-out-alt' : 'fa-user-plus')}"></i>
            </div>
            <div class="work-list-main">
                <h3 class="work-list-title">${log.first_name || 'System'} ${log.last_name || ''}</h3>
                <div class="work-list-meta">
                    <span><i class="fas fa-user-tag"></i> ${log.role || '-'}</span>
                    <span><i class="fas fa-building"></i> ${log.role === 'Evaluator' ? (log.position || '-') : (log.college || '-')}</span>
                </div>
            </div>
            <div class="work-list-status">
                <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--primary);">${log.action}</span>
            </div>
            <div class="work-list-action">
                <div class="progress-info" style="font-size: 0.75rem; opacity: 0.7;">
                    <i class="fas fa-clock"></i> ${log.created_at}
                </div>
            </div>
        </div>
    `).join('');
}

function renderEvaluationLogs() {
    const tbody = document.getElementById('evaluationTableBody');
    const evalActions = ['Evaluation', 'Evaluate Work'];
    let logs = allLogs.filter(l => evalActions.includes(l.action));
    
    if (logs.length === 0) {
        tbody.innerHTML = '<div style="text-align: center; padding: 2rem; opacity: 0.5;">No evaluation logs found.</div>';
        document.getElementById('evaluationPaginationBar').style.display = 'none';
        return;
    }

    const result = paginateLogs(logs, 'evaluation');
    logs = result.paginated;
    if (!result.showBar) document.getElementById('evaluationPaginationBar').style.display = 'none';

    tbody.innerHTML = logs.map(log => {
        let status = 'Unknown';
        let workTitle = 'N/A';
        
        const newFormatMatch = log.details.match(/Evaluated work '(.+)' as (\w+):/);
        const oldFormatMatch = log.details.match(/Evaluated work ID \d+ as (\w+)/);
        
        if (newFormatMatch) {
            workTitle = newFormatMatch[1];
            status = newFormatMatch[2];
        } else if (oldFormatMatch) {
            status = oldFormatMatch[1];
            workTitle = "ID " + (log.details.match(/ID (\d+)/)?.[1] || "??");
        }

        return `
            <div class="work-list-item">
                <div class="work-list-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="work-list-main">
                    <h3 class="work-list-title" title="${log.details}">${workTitle}</h3>
                    <div class="work-list-meta">
                        <span><i class="fas fa-user-tie"></i> ${log.first_name || 'System'} ${log.last_name || ''}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${log.position || '-'}</span>
                    </div>
                </div>
                <div class="work-list-status">
                    <span class="status-badge status-${status}">${status}</span>
                </div>
                <div class="work-list-action">
                    <div class="progress-info" style="font-size: 0.75rem; opacity: 0.7;">
                        <i class="fas fa-clock"></i> ${log.created_at}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderSubmissionLogs() {
    const tbody = document.getElementById('submissionTableBody');
    const subActions = ['Submission', 'Create Work'];
    let logs = allLogs.filter(l => subActions.includes(l.action));
    
    if (logs.length === 0) {
        tbody.innerHTML = '<div style="text-align: center; padding: 2rem; opacity: 0.5;">No submission logs found.</div>';
        document.getElementById('submissionPaginationBar').style.display = 'none';
        return;
    }

    const result = paginateLogs(logs, 'submission');
    logs = result.paginated;
    if (!result.showBar) document.getElementById('submissionPaginationBar').style.display = 'none';

    tbody.innerHTML = logs.map(log => {
        let title = log.details.replace('New work submitted: ', '').replace('Created work titled: ', '');
        return `
            <div class="work-list-item">
                <div class="work-list-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-file-upload"></i>
                </div>
                <div class="work-list-main">
                    <h3 class="work-list-title">${title}</h3>
                    <div class="work-list-meta">
                        <span><i class="fas fa-user-edit"></i> ${log.first_name || 'System'} ${log.last_name || ''}</span>
                        <span><i class="fas fa-university"></i> ${log.college || '-'}</span>
                    </div>
                </div>
                <div class="work-list-action">
                    <div class="progress-info" style="font-size: 0.75rem; opacity: 0.7;">
                        <i class="fas fa-clock"></i> ${log.created_at}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}
</script>
