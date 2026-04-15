<?php
// views/published.php
require_login();
?>
<div class="view-header">
    <div>
        <h2>Approved Works</h2>
        <p>Officially approved scholarly works archived for public viewing.</p></br>
    </div>
</div>

<!-- Filters Section -->
<div class="glass" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; gap: 1rem; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label><i class="fas fa-search"></i> Search Title or Author</label>
            <input type="text" id="searchInput" class="form-input" placeholder="Enter keywords...">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>College</label>
            <select id="collegeFilter" class="form-input">
                <option value="">All Colleges</option>
                <option value="CEIT">CEIT</option>
                <option value="CTHM">CTHM</option>
                <option value="CITTE">CITTE</option>
                <option value="CBA">CBA</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Quarter</label>
            <select id="quarterFilter" class="form-input">
                <option value="">All Quarters</option>
                <option value="Q1">Q1</option>
                <option value="Q2">Q2</option>
                <option value="Q3">Q3</option>
                <option value="Q4">Q4</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Year</label>
            <select id="yearFilter" class="form-input">
                <option value="">All Years</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Sort By</label>
            <select id="sortFilter" class="form-input">
                <option value="newest">Recent Approved Works</option>
                <option value="oldest">Oldest Approved Works</option>
                <option value="title_az">Title (A-Z)</option>
                <option value="title_za">Title (Z-A)</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Show</label>
            <select id="limitFilter" class="form-input">
                <option value="10">10 entries</option>
                <option value="25">25 entries</option>
                <option value="50">50 entries</option>
                <option value="100">100 entries</option>
                <option value="all">All</option>
            </select>
        </div>
    </div>
</div>

<!-- Track Select Tabs -->
<div class="tabs-container" style="margin-bottom: 1.5rem; display: flex; gap: 10px;">
    <button class="btn tab-btn active" data-type="Research" onclick="switchWorkTab('Research')">Research</button>
    <button class="btn tab-btn" data-type="Extension" onclick="switchWorkTab('Extension')">Extension</button>
</div>

<div class="glass" style="margin-top: 1.5rem; border-radius: 16px; overflow: hidden;">
    <div class="list-header">
        <div style="width: 60px;"></div>
        <div>Work Title</div>
        <div>Authors</div>
        <div>College</div>
        <div style="text-align: right;">Quarter/Year</div>
    </div>
    <div id="publishedList" class="works-list">
        <!-- Dynamic loading -->
    </div>
    <div id="paginationBar" class="pagination-bar" style="display: none;">
        <span id="paginationInfo" class="pagination-info"></span>
        <div class="pagination-controls">
            <button class="btn-page" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="pageIndicator" class="page-indicator"></span>
            <button class="btn-page" id="nextPage" onclick="changePage(1)">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<script>
let allPublishedWorks = [];
let currentWorkTypeTab = 'Research';
let currentPage = 1;
let lastFilteredWorks = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPublishedWorks();
    
    // Setup event listeners
    const filters = ['searchInput', 'collegeFilter', 'quarterFilter', 'yearFilter', 'sortFilter', 'limitFilter'];
    filters.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const eventType = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(eventType, () => { currentPage = 1; applyFiltersAndSort(); });
        }
    });
});

function switchWorkTab(type) {
    currentWorkTypeTab = type;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === type);
    });
    currentPage = 1;
    applyFiltersAndSort();
}

async function loadPublishedWorks() {
    try {
        const response = await fetch('api/works.php?type=published');
        allPublishedWorks = await response.json();
        populateYearDropdown();
        applyFiltersAndSort();
    } catch (error) {
        console.error('Error:', error);
    }
}

function populateYearDropdown() {
    const yearFilter = document.getElementById('yearFilter');
    const years = [...new Set(allPublishedWorks.map(w => w.year))].sort((a, b) => b - a);
    
    // Keep the "All Years" option
    yearFilter.innerHTML = '<option value="">All Years</option>' + 
        years.map(y => `<option value="${y}">${y}</option>`).join('');
}

function applyFiltersAndSort() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const college = document.getElementById('collegeFilter').value;
    const quarter = document.getElementById('quarterFilter').value;
    const year = document.getElementById('yearFilter').value;
    const sortBy = document.getElementById('sortFilter').value;

    let filtered = allPublishedWorks.filter(work => {
        // Search matches Title or Author
        const titleMatch = (work.title || '').toLowerCase().includes(searchTerm);
        const authorMatch = (work.author_names || '').toLowerCase().includes(searchTerm);
        const matchesSearch = searchTerm === "" || titleMatch || authorMatch;
        
        // Filter matches
        const matchesCollege = college === "" || (work.colleges && work.colleges.includes(college));
        const matchesQuarter = quarter === "" || work.quarter === quarter;
        const matchesYear = year === "" || work.year.toString() === year;
        const matchesType = work.work_type === currentWorkTypeTab;
        
        return matchesSearch && matchesCollege && matchesQuarter && matchesYear && matchesType;
    });

    // Handle Sorting
    filtered.sort((a, b) => {
        const aDate = new Date(a.approved_at || a.created_at);
        const bDate = new Date(b.approved_at || b.created_at);
        
        switch (sortBy) {
            case 'oldest':
                return aDate - bDate;
            case 'title_az':
                return (a.title || '').localeCompare(b.title || '');
            case 'title_za':
                return (b.title || '').localeCompare(a.title || '');
            case 'newest':
            default:
                return bDate - aDate;
        }
    });

    lastFilteredWorks = filtered;
    const limit = document.getElementById('limitFilter').value;
    if (limit === 'all') {
        renderWorks(filtered);
        document.getElementById('paginationBar').style.display = 'none';
    } else {
        const perPage = parseInt(limit);
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * perPage;
        const pageItems = filtered.slice(start, start + perPage);
        renderWorks(pageItems);
        renderPagination(filtered.length, perPage, totalPages);
    }
}

function renderPagination(total, perPage, totalPages) {
    const bar = document.getElementById('paginationBar');
    const info = document.getElementById('paginationInfo');
    const indicator = document.getElementById('pageIndicator');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    if (total === 0) { bar.style.display = 'none'; return; }
    bar.style.display = 'flex';
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, total);
    info.textContent = `Showing ${start}-${end} of ${total} entries`;
    indicator.textContent = `Page ${currentPage} of ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
}

function changePage(dir) {
    currentPage += dir;
    applyFiltersAndSort();
}

function renderWorks(works) {
    const list = document.getElementById('publishedList');
    
    if (works.length === 0) {
        list.innerHTML = '<div style="text-align: center; padding: 3rem; opacity: 0.5;">No works found matching your criteria.</div>';
        return;
    }

    list.innerHTML = works.map(work => `
        <div class="work-list-item" onclick="viewWorkDetails(${work.id})">
            <div class="work-list-icon"><i class="fas fa-book-reader"></i></div>
            <div class="work-list-title" title="${work.title}">${work.title}</div>
            <div class="meta-authors">${renderAuthorBadges(work)}</div>
            <div class="meta-college"><i class="fas fa-university"></i> ${work.colleges || 'N/A'}</div>
            <div class="work-list-action" style="text-align: right; color: var(--primary); font-weight: 600;">
                <i class="fas fa-calendar-alt"></i> ${work.quarter} ${work.year}
            </div>
        </div>
    `).join('');
}
</script>

<script>
function renderAuthorBadges(work) {
    let html = '';
    
    if (work.leader_name) {
        html += `<span class="list-author-badge list-leader-badge"><i class="fas fa-crown"></i> ${escapeHtml(work.leader_name)}</span>`;
    }
    if (work.member_names) {
        html += `<span class="list-author-badge list-member-badge"><i class="fas fa-users"></i> ${escapeHtml(work.member_names)}</span>`;
    }
    return html || `<span><i class="fas fa-user-edit"></i> ${escapeHtml(work.author_names || '')}</span>`;
}
</script>

<style>
/* Work Type Badges */
.list-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.type-research {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}
.type-extension {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.tab-btn {
    opacity: 0.6;
    background: var(--glass-bg);
}
.tab-btn.active {
    opacity: 1;
    background: var(--primary);
    color: white;
}
</style>
