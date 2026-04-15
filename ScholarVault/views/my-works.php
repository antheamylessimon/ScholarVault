<?php
// views/my-works.php
require_role('Proponent');
?>
<div class="view-header" style="display:flex; justify-content:space-between; align-items:center; gap: 1rem;">
    <div>
        <h2>My Scholarly Works</h2>
        <p>Manage and track the progress of your submissions.</p>
    </div>
    <button class="btn btn-primary" onclick="openWorkModal()" style="margin-left:auto;">
        <i class="fas fa-plus"></i> Add New
    </button>
</div>

<!-- Track Select Tabs -->
<div class="tabs-container" style="margin-top: 1.5rem; display: flex; gap: 10px;">
    <button class="btn tab-btn active" data-type="Research" onclick="switchWorkTab('Research')">Research</button>
    <button class="btn tab-btn" data-type="Extension" onclick="switchWorkTab('Extension')">Extension</button>
</div>

<div class="glass" style="margin-top: 1.5rem; border-radius: 16px; overflow: hidden;">
    <div class="list-header">
        <div style="width: 60px;"></div>
        <div>Work Title</div>
        <div>Authors</div>
        <div>College</div>
        <div style="text-align: right;">Progress & Status</div>
    </div>
    <div id="worksList" class="works-list">
        <!-- Dynamic loading -->
    </div>
</div>

<!-- Add/Edit Work Modal -->
<div id="workModal" class="modal-overlay">
    <div class="modal glass" style="width: 700px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h2 class="modal-title" id="workModalTitle">New Scholarly Work</h2>
        </div>
        <form id="workForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="workFormAction" value="create">
            <input type="hidden" name="work_id" id="workIdInput">
            
            <div class="form-group" style="text-align: center; margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 700;">Work Type</label>
                <div class="radio-tile-group">
                    <label class="radio-tile">
                        <input type="radio" name="work_type" value="Research" checked>
                        <div class="tile-content">
                            <div class="tile-icon"><i class="fas fa-microscope"></i></div>
                            <span class="tile-label">Research</span>
                        </div>
                    </label>
                    <label class="radio-tile">
                        <input type="radio" name="work_type" value="Extension">
                        <div class="tile-content">
                            <div class="tile-icon"><i class="fas fa-hand-holding-heart"></i></div>
                            <span class="tile-label">Extension</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-input" placeholder="Enter work title" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Authors</label>
                    <div id="authorSelect" class="multi-select-container form-input" style="height: auto; min-height: 45px;">
                        <!-- Selected authors will show as badges -->
                         <div id="selectedAuthors" style="display: flex; flex-wrap: wrap; gap: 5px;"></div>
                         <input type="text" id="authorSearch" placeholder="Search registered members..." style="border:none; outline:none; background:transparent; width: 100%; min-width: 150px;">
                         <div id="authorDropdown" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Quarter</label>
                    <select name="quarter" class="form-input" required>
                        <option value="Q1">Q1 (Jan-Mar)</option>
                        <option value="Q2">Q2 (Apr-Jun)</option>
                        <option value="Q3">Q3 (Jul-Sep)</option>
                        <option value="Q4">Q4 (Oct-Dec)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Total Proposed Budget</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 1rem; color: var(--text-light); font-weight: bold;">₱</span>
                        <input type="number" min="0" name="total_proposed_budget" class="form-input" style="padding-left: 2.5rem;" placeholder="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Proposed Start Date</label>
                    <input type="date" name="proposed_starting_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Proposed Completion Date</label>
                    <input type="date" name="proposed_completion_date" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label>Files (PDF, DOCX, XLSX)</label>
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & Drop files here or click to select</p>
                    <input type="file" name="files[]" id="fileInput" multiple hidden>
                </div>
                <div id="fileList" class="file-preview-list"></div>
            </div>

            <div class="form-group">
                <label>External Links (Google Drive, Docs, Sheets, Slides, etc.)</label>
                <label style="font-size: 0.8rem; color: #ef4444; margin-top: -4px;">The General Access Role must be "Commenter"</label>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="url" id="linkInput" class="form-input" placeholder="https://drive.google.com/..." style="flex: 1;">
                    <button type="button" class="btn btn-outline" onclick="addLink()" style="flex: none;">Add URL</button>
                </div>
                <div id="linkList" class="file-preview-list"></div>
            </div>

            <div id="workMessage" class="form-message"></div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" id="workSubmitBtn" class="btn btn-primary" style="flex: 1;">Create Folder</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('workModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.works-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.work-folder {
    padding: 1.5rem;
    border-radius: 16px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 200px;
}

.work-folder:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15);
}

.folder-icon {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 1rem;
}

.work-title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.work-meta {
    font-size: 0.8rem;
    opacity: 0.7;
    margin-top: 0.5rem;
    line-height: 1.8;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.status-Gray { background: #9ca3af; color: white; }
.status-Yellow { background: #f59e0b; color: white; }
.status-Red { background: #ef4444; color: white; }
.status-Green { background: #10b981; color: white; }

.drop-zone {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
}

.drop-zone:hover, .drop-zone.active {
    border-color: var(--primary);
    background: rgba(16, 185, 129, 0.05);
}

.file-preview-item {
    display: flex;
    justify-content: space-between;
    padding: 8px;
    background: var(--bg-light);
    border-radius: 6px;
    margin-top: 5px;
    font-size: 0.85rem;
}

/* Author role badges inside the work form */
.selected-author-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: default;
}
.author-leader {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}
.author-member {
    background: rgba(16, 185, 129, 0.15);
    color: var(--text-light);
    border: 1px solid var(--primary);
}
.author-role-badge {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.85;
}
.leader-badge { color: #fff; }
.member-badge { color: var(--primary); }

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

/* Radio Tiles Styling */
.radio-tile-group {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
}

.radio-tile {
    position: relative;
    width: 140px;
    cursor: pointer;
}

.radio-tile input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.tile-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 1.25rem;
    border-radius: 16px;
    background: var(--glass-bg);
    border: 2px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tile-icon {
    font-size: 1.8rem;
    color: var(--text-light);
    opacity: 0.6;
    transition: all 0.3s;
}

.tile-label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-light);
    opacity: 0.8;
}

.radio-tile input:checked + .tile-content {
    background: rgba(16, 185, 129, 0.1);
    border-color: var(--primary);
    transform: translateY(-5px);
    box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.3);
}

.radio-tile input:checked + .tile-content .tile-icon {
    color: var(--primary);
    opacity: 1;
    transform: scale(1.1);
}

.radio-tile input:checked + .tile-content .tile-label {
    color: var(--primary);
    opacity: 1;
}

.radio-tile:hover .tile-content {
    border-color: var(--primary);
    background: rgba(16, 185, 129, 0.05);
}
</style>

<script src="assets/js/works.js"></script>
