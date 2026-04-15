// assets/js/works.js

const worksList = document.getElementById('worksList');
const workForm = document.getElementById('workForm');
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');
const authorSearch = document.getElementById('authorSearch');
const selectedAuthorsDiv = document.getElementById('selectedAuthors');

let selectedAuthors = []; // { id, name, role: 'leader'|'member' }
let uploadedFiles = [];
let addedLinks = [];
let currentWorkTypeTab = 'Research';
let leaderId = null; // ID of the leader author

let allWorks = []; // Store all works for filtering/sorting

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadWorks();
});

function openWorkModal() {
    workForm.reset();
    document.getElementById('workFormAction').value = 'create';
    document.getElementById('workIdInput').value = '';
    document.getElementById('workModalTitle').textContent = 'New Scholarly Work';
    document.getElementById('workSubmitBtn').textContent = 'Create Folder';
    // Auto-add current user as leader
    leaderId = String(currentUserId);
    selectedAuthors = [{ id: String(currentUserId), name: currentUserName, role: 'leader' }];
    uploadedFiles = [];
    addedLinks = [];
    renderSelectedAuthors();
    renderFileList();
    renderLinkList();
    openModal('workModal');
}

async function openEditWorkModal(id) {
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
            
            // Set mode to update
            document.getElementById('workFormAction').value = 'update';
            document.getElementById('workIdInput').value = work.id;
            document.getElementById('workModalTitle').textContent = 'Edit Scholarly Work';
            document.getElementById('workSubmitBtn').textContent = 'Save Changes';

            // Populate fields
            workForm.title.value = work.title;
            workForm.quarter.value = work.quarter;
            if(workForm.total_proposed_budget) workForm.total_proposed_budget.value = work.total_proposed_budget;
            if(workForm.proposed_starting_date) workForm.proposed_starting_date.value = work.proposed_starting_date;
            if(workForm.proposed_completion_date) workForm.proposed_completion_date.value = work.proposed_completion_date;

            // Populate authors — preserve roles from server
            selectedAuthors = work.authors_list.map(a => ({
                id: String(a.id),
                name: a.name,
                role: a.role || 'member'
            }));
            // Determine leader
            const leaderEntry = selectedAuthors.find(a => a.role === 'leader');
            leaderId = leaderEntry ? leaderEntry.id : String(currentUserId);
            // If no leader found, assign current user as leader
            if (!leaderEntry) {
                const me = selectedAuthors.find(a => a.id === String(currentUserId));
                if (me) me.role = 'leader';
                leaderId = String(currentUserId);
            }

            uploadedFiles = []; // Reset file uploads for edit (we only add new ones)
            addedLinks = work.links || []; // Populate existing links
            renderSelectedAuthors();
            renderFileList();
            renderLinkList();
            openModal('workModal');
        }
    } catch (error) {
        console.error('Error fetching work for edit:', error);
    }
}

function mapStatusLabel(status) {
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

// Load Works
async function loadWorks() {
    try {
        const response = await fetch('api/works.php?type=my');
        allWorks = await response.json();
        renderLoadedWorks();
    } catch (error) {
        console.error('Error loading works:', error);
    }
}

function renderLoadedWorks() {
    if (allWorks.length === 0) {
        worksList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; opacity: 0.5;">No works found. Click "Add New" to start.</div>';
        return;
    }

    // Filter by tab
    let filteredWorks = allWorks.filter(w => w.work_type === currentWorkTypeTab);

    if (filteredWorks.length === 0) {
        worksList.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 3rem; opacity: 0.5;">No ${currentWorkTypeTab} works found.</div>`;
        return;
    }

    worksList.innerHTML = filteredWorks.map(work => {
        const statusLabel = mapStatusLabel(work.status);
        return `
            <div class="work-list-item" onclick="viewWorkDetails(${work.id})">
                <div class="work-list-icon"><i class="fas fa-folder"></i></div>
                <div class="work-list-title" title="${work.title}">${work.title}</div>
                <div class="meta-authors">${renderAuthorBadges(work)}</div>
                <div class="meta-college"><i class="fas fa-university"></i> ${work.colleges || 'N/A'}</div>
                <div class="work-list-action" style="text-align: right;">
                    <div style="font-size: 0.7rem; opacity: 0.7; margin-bottom: 4px;"><strong>${work.current_step}</strong></div>
                    <span class="status-badge status-${work.status}">${statusLabel}</span>
                </div>
            </div>
        `;
    }).join('');
}

function switchWorkTab(type) {
    currentWorkTypeTab = type;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === type);
    });
    renderLoadedWorks();
}

function renderAuthorBadges(work) {
    let html = '';
    
    if (work.leader_name) {
        html += `<span class="list-author-badge list-leader-badge"><i class="fas fa-crown"></i> ${escapeLink(work.leader_name)}</span>`;
    }
    if (work.member_names) {
        html += `<span class="list-author-badge list-member-badge"><i class="fas fa-users"></i> ${escapeLink(work.member_names)}</span>`;
    }
    return html || `<span><i class="fas fa-user-edit"></i> ${escapeLink(work.author_names || '')}</span>`;
}

const authorDropdown = document.getElementById('authorDropdown');

// Author Search Logic
authorSearch.addEventListener('input', async (e) => {
    const q = e.target.value;
    if (q.length < 2) {
        authorDropdown.classList.remove('active');
        return;
    }

    try {
        const response = await fetch(`api/users.php?q=${q}`);
        const users = await response.json();

        if (users.length > 0) {
            authorDropdown.innerHTML = users.map(user => `
                <div class="autocomplete-item" onclick="addAuthor('${user.id}', '${user.first_name} ${user.last_name}')">
                    <span class="user-name">${user.first_name} ${user.last_name}</span>
                    <span class="user-info">${user.email} | ${user.role}</span>
                </div>
            `).join('');
            authorDropdown.classList.add('active');
        } else {
            authorDropdown.innerHTML = '<div class="autocomplete-item" style="cursor:default; opacity:0.5;">No results found</div>';
            authorDropdown.classList.add('active');
        }
    } catch (error) {
        console.error('Error fetching users:', error);
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.multi-select-container')) {
        if (authorDropdown) authorDropdown.classList.remove('active');
    }
});

function addAuthor(id, name) {
    const sid = String(id);
    if (!selectedAuthors.find(a => a.id === sid)) {
        selectedAuthors.push({ id: sid, name, role: 'member' });
        renderSelectedAuthors();
    }
    authorSearch.value = '';
    authorDropdown.classList.remove('active');
}

function renderSelectedAuthors() {
    selectedAuthorsDiv.innerHTML = selectedAuthors.map(a => {
        const isLeader = a.role === 'leader';
        const removeBtn = isLeader
            ? '' // leader cannot be removed
            : `<i class="fas fa-times" onclick="removeAuthor('${a.id}')" style="cursor:pointer; margin-left:4px;"></i>`;
        const roleLabel = isLeader
            ? `<span class="author-role-badge leader-badge">Leader</span>`
            : `<span class="author-role-badge member-badge">Member</span>`;
        return `<span class="selected-author-badge ${isLeader ? 'author-leader' : 'author-member'}">${roleLabel} ${escapeLink(a.name)} ${removeBtn}</span>`;
    }).join('');
}

function removeAuthor(id) {
    const sid = String(id);
    if (sid === String(leaderId)) return; // cannot remove the leader
    selectedAuthors = selectedAuthors.filter(a => a.id !== sid);
    renderSelectedAuthors();
}

// Drag & Drop Logic
dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('active');
});

dropZone.addEventListener('dragleave', () => dropZone.classList.remove('active'));

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('active');
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

function handleFiles(files) {
    for (const file of files) {
        uploadedFiles.push(file);
    }
    renderFileList();
}

function renderFileList() {
    fileList.innerHTML = uploadedFiles.map((f, i) => `
        <div class="file-preview-item">
            <span><i class="fas fa-file"></i> ${f.name}</span>
            <i class="fas fa-trash" onclick="removeFile(${i})" style="color:#ef4444; cursor:pointer;"></i>
        </div>
    `).join('');
}

function removeFile(index) {
    uploadedFiles.splice(index, 1);
    renderFileList();
}

function addLink() {
    const linkInput = document.getElementById('linkInput');
    const url = linkInput.value.trim();
    if (url) {
        // Basic URL validation
        try {
            new URL(url);
            addedLinks.push(url);
            renderLinkList();
            linkInput.value = '';
        } catch (e) {
            alert('Please enter a valid URL including http:// or https://');
        }
    }
}

function renderLinkList() {
    const linkList = document.getElementById('linkList');
    if (!linkList) return;
    linkList.innerHTML = addedLinks.map((link, i) => `
        <div class="file-preview-item">
            <span><i class="fas fa-link"></i> <a href="${escapeLink(link)}" target="_blank">${escapeLink(link)}</a></span>
            <i class="fas fa-trash" onclick="removeLink(${i})" style="color:#ef4444; cursor:pointer;"></i>
        </div>
    `).join('');
}

function escapeLink(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function removeLink(index) {
    addedLinks.splice(index, 1);
    renderLinkList();
}

// Form Submission
workForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(workForm);

    // Add authors and their roles manually to formData
    selectedAuthors.forEach(a => {
        formData.append('authors[]', a.id);
        formData.append('author_roles[]', a.role);
    });

    // Add work_type
    const workTypeEl = document.querySelector('input[name="work_type"]:checked');
    if (workTypeEl) {
        formData.set('work_type', workTypeEl.value);
    }

    // Add files manually if not already there
    formData.delete('files[]');
    uploadedFiles.forEach(f => formData.append('files[]', f));

    // Add links manually
    formData.delete('links[]');
    addedLinks.forEach(link => formData.append('links[]', link));

    const msgDiv = document.getElementById('workMessage');

    try {
        const response = await fetch('api/works.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            msgDiv.style.color = 'var(--primary)';
            msgDiv.textContent = data.message;
            setTimeout(() => {
                closeModal('workModal');
                loadWorks();
            }, 1000);
        } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.textContent = data.message;
        }
    } catch (error) {
        console.error('Submission error:', error);
        msgDiv.style.color = '#ef4444';
        msgDiv.textContent = 'An error occurred: ' + error.message;
    }
});
