<?php
// views/dashboard.php
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$isProponent = ($role === 'Proponent');
$isEvaluator = ($role === 'Evaluator');
$isRestricted = ($isProponent || $isEvaluator);

// Fetch Evaluator Position if needed
$userPos = '';
if ($isEvaluator) {
    $st = $pdo->prepare("SELECT position FROM users WHERE id = ?");
    $st->execute([$userId]);
    $userPos = $st->fetchColumn();
}

// Helper to get counts
function get_count($pdo, $query, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

if ($isProponent) {
    // Proponent-specific isolated data
    $total_works = get_count($pdo, "SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ?", [$userId]);
    $published_works = get_count($pdo, "SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND current_step = 'Published'", [$userId]);
    $ongoing_works = $total_works - $published_works;

    $gray_works = get_count($pdo, "SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND status = 'Gray'", [$userId]);
    $yellow_works = get_count($pdo, "SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND status = 'Yellow'", [$userId]);
    $red_works = get_count($pdo, "SELECT COUNT(*) FROM works w JOIN work_authors wa ON w.id = wa.work_id WHERE wa.user_id = ? AND status = 'Red'", [$userId]);
} else if ($isEvaluator) {
    // Evaluator-specific isolated data
    $total_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = ?", [$userPos]);
    $published_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = ? AND status = 'Green'", [$userPos]);
    $ongoing_works = $total_works - $published_works;

    $gray_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = ? AND status = 'Gray'", [$userPos]);
    $yellow_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = ? AND status = 'Yellow'", [$userPos]);
    $red_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = ? AND status = 'Red'", [$userPos]);
} else {
    // Global system data (Admin)
    $total_works = get_count($pdo, "SELECT COUNT(*) FROM works");
    $published_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE current_step = 'Published'");
    $ongoing_works = $total_works - $published_works;

    $gray_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE status = 'Gray'");
    $yellow_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE status = 'Yellow'");
    $red_works = get_count($pdo, "SELECT COUNT(*) FROM works WHERE status = 'Red'");
}

// User Summary Stats (Not shown for Restricted roles)
$proponents = get_count($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Proponent'");
$evaluators = get_count($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Evaluator'");
$total_users = $proponents + $evaluators;
?>

<div class="dashboard-header">
    <header>
        <h2><?php echo $isRestricted ? 'Personal Repository Analytics' : 'System Analytics Dashboard'; ?></h2>
        <p><?php echo $isRestricted ? 'Overview of your personal scholarly work activities.' : 'Comprehensive overview of system activities and community engagement.'; ?></p>
    </header>
</div>

<!-- Repository Analytics Section -->
<section class="analytics-section" aria-labelledby="repo-heading">
    <div class="section-title">
        <h3 id="repo-heading"><i class="fas fa-archive"></i> <?php echo $isRestricted ? 'My Analytics' : 'Repository Analytics'; ?></h3>
    </div>
    
    <div class="stats-grid">
        <article class="stat-card glass gray">
            <div class="icon"><i class="fas fa-eye-slash"></i></div>
            <div class="stat-value"><?php echo $gray_works; ?></div>
            <div class="stat-label"><?php echo $isRestricted ? 'My Unread' : 'Unread'; ?></div>
        </article>
        <article class="stat-card glass yellow">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?php echo $yellow_works; ?></div>
            <div class="stat-label"><?php echo $isRestricted ? 'My Pending' : 'Pending'; ?></div>
        </article>
        <article class="stat-card glass red">
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-value"><?php echo $red_works; ?></div>
            <div class="stat-label"><?php echo $isRestricted ? 'My Need Revision' : 'Need Revision'; ?></div>
        </article>
        <article class="stat-card glass ongoing" <?php if($isEvaluator) echo 'style="grid-column: span 2;"'; ?>>
            <div class="icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?php echo $ongoing_works; ?></div>
            <div class="stat-label"><?php echo $isRestricted ? 'My Total Ongoing' : 'Total Ongoing'; ?></div>
        </article>
        <?php if (!$isEvaluator): ?>
        <article class="stat-card glass green">
            <div class="icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-value"><?php echo $published_works; ?></div>
            <div class="stat-label"><?php echo $isRestricted ? 'My Total Approved' : 'Total Approved'; ?></div>
        </article>
        <?php endif; ?>
    </div>

    <div class="charts-grid">
        <?php if (!$isProponent): ?>
        <article class="chart-card glass" <?php if($isEvaluator) echo 'style="grid-column: 1 / -1;"'; ?>>
            <h4><?php echo $isEvaluator ? 'For Evaluation Works per College' : 'Works per College'; ?></h4>
            <div class="chart-container">
                <canvas id="collegeChart"></canvas>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($isEvaluator): ?>
        <article class="chart-card glass" style="grid-column: 1 / -1; margin-top: 1.5rem;">
            <h4>My Evaluated Works per College</h4>
            <div class="chart-container">
                <canvas id="collegeChartEvaluated"></canvas>
            </div>
        </article>
        <?php endif; ?>

        <?php if (!$isEvaluator): ?>
        <article class="chart-card glass" <?php if($isProponent) echo 'style="grid-column: 1 / -1;"'; ?>>
            <h4><?php echo $isRestricted ? 'My Evaluation Progress' : 'Evaluation Progress'; ?></h4>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </article>
        <?php endif; ?>
    </div>

    <?php if ($role === 'Admin'): ?>
    <article class="chart-card glass" style="margin-top: 1.5rem;">
        <h4>Annual Submission Velocity</h4>
        <div class="chart-container" style="height: 300px;">
            <canvas id="yearChartSubmitted"></canvas>
        </div>
    </article>
    <article class="chart-card glass" style="margin-top: 1.5rem;">
        <h4>Annual Completion Rate</h4>
        <div class="chart-container" style="height: 300px;">
            <canvas id="yearChartApproved"></canvas>
        </div>
    </article>
    <?php elseif ($isProponent): ?>
    <article class="chart-card glass" style="margin-top: 1.5rem;">
        <h4>My Annual Submission Velocity</h4>
        <div class="chart-container" style="height: 300px;">
            <canvas id="yearChartSubmitted"></canvas>
        </div>
    </article>
    <article class="chart-card glass" style="margin-top: 1.5rem;">
        <h4>My Annual Completion Rate</h4>
        <div class="chart-container" style="height: 300px;">
            <canvas id="yearChartApproved"></canvas>
        </div>
    </article>
    <?php elseif ($isEvaluator): ?>
    <article class="chart-card glass" style="margin-top: 1.5rem;">
        <h4>My Annual Evaluated Works</h4>
        <div class="chart-container" style="height: 300px;">
            <canvas id="yearChartEvaluated"></canvas>
        </div>
    </article>
    <?php endif; ?>
</section>

<?php if (!$isRestricted): ?>
<!-- User Analytics Section -->
<section class="analytics-section" style="margin-top: 3rem;" aria-labelledby="user-heading">
    <div class="section-title">
        <h3 id="user-heading"><i class="fas fa-users"></i> Community Metrics</h3>
    </div>

    <div class="stats-grid">
        <article class="stat-card glass pro">
            <div class="icon"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-value"><?php echo $proponents; ?></div>
            <div class="stat-label">Proponents</div>
        </article>
        <article class="stat-card glass eva">
            <div class="icon"><i class="fas fa-user-tie"></i></div>
            <div class="stat-value"><?php echo $evaluators; ?></div>
            <div class="stat-label">Evaluators</div>
        </article>
        <article class="stat-card glass total">
            <div class="icon"><i class="fas fa-users-cog"></i></div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Members</div>
        </article>
    </div>

    <div class="charts-grid user-charts">
         <article class="chart-card glass">
            <h4>Role Distribution</h4>
            <div class="chart-container">
                <canvas id="userDistChart"></canvas>
            </div>
        </article>
        <article class="chart-card glass">
            <h4>Users per College</h4>
            <div class="chart-container">
                <canvas id="usersPerCollegeChart"></canvas>
            </div>
        </article>
    </div>
</section>
<?php endif; ?>

<style>
.analytics-section {
    margin-bottom: 2rem;
}
.section-title {
    margin: 2rem 0 1.5rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}
.section-title h3 {
    font-size: 1.25rem;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 10px;
}
.chart-card h4 {
    margin-bottom: 1.5rem;
    font-size: 1rem;
    opacity: 0.8;
}
.chart-container {
    height: 250px;
    position: relative;
}
.d-flex-centered {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.stat-card.ongoing .icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.stat-card.pro .icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.stat-card.eva .icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.stat-card.total .icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }

.user-charts {
    grid-template-columns: 1fr 1fr;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const response = await fetch('api/stats.php');
    const data = await response.json();

    const getThemeColor = () => getComputedStyle(document.body).getPropertyValue('--text-light').trim() || '#333';
    const getMutedColor = () => getThemeColor() === '#111827' ? '#64748b' : '#94a3b8';

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: getThemeColor(),
                    font: { weight: '600', size: 11 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                titleColor: '#fff',
                bodyColor: '#cbd5e1',
                borderColor: 'rgba(16, 185, 129, 0.3)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) label += ': ';
                        if (context.parsed !== undefined) {
                            label += context.parsed;
                            if (context.chart.config.type === 'pie' || context.chart.config.type === 'doughnut') {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1) + '%';
                                label += ` (${percentage})`;
                            }
                        }
                        return label;
                    }
                }
            }
        }
    };

    const collegeColors = {
        'CEIT': '#ef4444',
        'CITTE': '#3b82f6',
        'CBA': '#f59e0b',
        'CTHM': '#ec4899'
    };

    const getColors = (labels) => labels.map(label => collegeColors[label] || '#10b981');

    const allCharts = [];

    // College Chart (Works per College)
    const collegeCtx = document.getElementById('collegeChart');
    if (collegeCtx && data.colleges) {
        const collegeLabels = Object.keys(data.colleges);
        const collegeChart = new Chart(collegeCtx, {
            type: 'bar',
            data: {
                labels: collegeLabels,
                datasets: [{
                    label: 'Works',
                    data: Object.values(data.colleges),
                    backgroundColor: getColors(collegeLabels),
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'College', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { display: true, text: 'Number of Works', color: getMutedColor(), font: { weight: 'bold' } },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(collegeChart);
    }

    // Evaluated Works per College Chart (Evaluator specific)
    const collegeEvalCtx = document.getElementById('collegeChartEvaluated');
    if (collegeEvalCtx && data.colleges_evaluated) {
        const collegeLabels = Object.keys(data.colleges_evaluated);
        const collegeChartEvaluated = new Chart(collegeEvalCtx, {
            type: 'bar',
            data: {
                labels: collegeLabels,
                datasets: [{
                    label: 'Evaluated Works',
                    data: Object.values(data.colleges_evaluated),
                    backgroundColor: getColors(collegeLabels),
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'College', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { display: true, text: 'Evaluated Works', color: getMutedColor(), font: { weight: 'bold' } },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(collegeChartEvaluated);
    }

    // Status Chart (Evaluation Progress)
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const statusChart = new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Ongoing', 'Approved'],
                datasets: [{
                    label: 'Number of Works',
                    data: [data.overall.ongoing, data.overall.published],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderRadius: 4,
                    barThickness: 50
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(statusChart);
    }

    // Year Chart (Annual Submission Velocity)
    const yearSubCtx = document.getElementById('yearChartSubmitted');
    if (yearSubCtx && data.years_submitted) {
        const yearChartSubmitted = new Chart(yearSubCtx, {
            type: 'line',
            data: {
                labels: Object.keys(data.years_submitted),
                datasets: [{
                    label: 'Submission Velocity',
                    data: Object.values(data.years_submitted),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'Academic Year', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { 
                            display: true, 
                            text: 'Submitted Works', 
                            color: getMutedColor(), 
                            font: { weight: 'bold' } 
                        },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(yearChartSubmitted);
    }

    // Year Chart (Annual Evaluation History)
    const yearEvalCtx = document.getElementById('yearChartEvaluated');
    if (yearEvalCtx && data.years_evaluated) {
        const yearChartEvaluated = new Chart(yearEvalCtx, {
            type: 'line',
            data: {
                labels: Object.keys(data.years_evaluated),
                datasets: [{
                    label: 'Evaluation Activity',
                    data: Object.values(data.years_evaluated),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'Academic Year', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { 
                            display: true, 
                            text: 'Evaluated Works', 
                            color: getMutedColor(), 
                            font: { weight: 'bold' } 
                        },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(yearChartEvaluated);
    }

    // Year Chart (Annual Completion Rate)
    const yearAppCtx = document.getElementById('yearChartApproved');
    if (yearAppCtx && data.years_approved) {
        const yearChartApproved = new Chart(yearAppCtx, {
            type: 'line',
            data: {
                labels: Object.keys(data.years_approved),
                datasets: [{
                    label: 'Completion Velocity',
                    data: Object.values(data.years_approved),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'Academic Year', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { 
                            display: true, 
                            text: 'Approved Works', 
                            color: getMutedColor(), 
                            font: { weight: 'bold' } 
                        },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(yearChartApproved);
    }

    // User Distribution Chart
    const userDistCtx = document.getElementById('userDistChart');
    if (userDistCtx && data.users) {
        const userDistChart = new Chart(userDistCtx, {
            type: 'pie',
            data: {
                labels: ['Proponents', 'Evaluators'],
                datasets: [{
                    data: [data.users.proponents, data.users.evaluators],
                    backgroundColor: ['#3b82f6', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: 'rgba(255,255,255,0.05)'
                }]
            },
            options: chartOptions
        });
        allCharts.push(userDistChart);
    }

    // Users per College Chart
    const usersPerCollegeCtx = document.getElementById('usersPerCollegeChart');
    if (usersPerCollegeCtx && data.users_per_college) {
        const userCollegeLabels = Object.keys(data.users_per_college);
        const usersPerCollegeChart = new Chart(usersPerCollegeCtx, {
            type: 'bar',
            data: {
                labels: userCollegeLabels,
                datasets: [{
                    label: 'Registered Proponents',
                    data: Object.values(data.users_per_college),
                    backgroundColor: getColors(userCollegeLabels),
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'College', color: getMutedColor(), font: { weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: getMutedColor() }
                    },
                    y: { 
                        title: { display: true, text: 'Registered Proponents', color: getMutedColor(), font: { weight: 'bold' } },
                        beginAtZero: true,
                        ticks: { color: getMutedColor() },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
        allCharts.push(usersPerCollegeChart);
    }

    // Listen for global theme change and update all charts
    window.addEventListener('svThemeChanged', () => {
        const newColor = getThemeColor();
        const muted = getMutedColor();

        allCharts.forEach(chart => {
            // Update legend color
            if (chart.options.plugins && chart.options.plugins.legend) {
                chart.options.plugins.legend.labels.color = newColor;
            }
            // Update scales if they exist
            if (chart.options.scales) {
                Object.keys(chart.options.scales).forEach(axis => {
                    const scale = chart.options.scales[axis];
                    if (scale.ticks) scale.ticks.color = muted;
                    if (scale.title) scale.title.color = muted;
                });
            }
            chart.update();
        });
    });
});
</script>
