<?php
include_once 'includes/auth_helper.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarVault | RDIE Repository System</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/google_sans.css">
    <link rel="stylesheet" href="assets/css/material_icons.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,600,1,0&icon_names=assured_workload" />
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="light">
    <header class="glass">
        <div class="logo">
            <img src="assets/images/favicon.svg" alt="ScholarVault Logo"
                style="width: 28px; height: 28px; vertical-align: middle;">
            <span><span class="text-scholar">Scholar</span><span class="text-vault">Vault</span></span>
        </div>
        <nav class="nav-links">
            <button class="btn btn-outline" onclick="openModal('loginModal')">Login</button>
            <button class="btn btn-primary" onclick="openModal('registerModal')">Register</button>
            <button class="btn btn-outline" id="themeToggle" title="Toggle Dark Mode"
                style="display: flex; align-items: center; justify-content: center; width: 45px; height: 42px; padding: 0;">
                <i class="fas fa-moon" style="font-size: 1.30rem;">🌗</i>
            </button>
        </nav>
    </header>

    <main class="hero">
        <h1>ScholarVault RDIE Repository System</h1>
        <div style="display: flex; gap: 1rem;">
            <button class="btn btn-primary btn-lg" onclick="openModal('registerModal')">Get Started</button>
            <button class="btn btn-outline btn-lg" onclick="openModal('loginModal')">Explore Published Works</button>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal glass">
            <div class="modal-header">
                <h2 class="modal-title">Login As</h2>
                <div class="switch-container">
                    <button id="loginProponentBtn" class="switch-btn active"
                        onclick="switchLogin('proponent_evaluator')">Proponent/Evaluator</button>
                    <button id="loginAdminBtn" class="switch-btn" onclick="switchLogin('admin')">Admin</button>
                </div>
            </div>
            <form id="loginForm">
                <input type="hidden" name="login_as" id="loginAsInput" value="proponent_evaluator">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <input type="text" id="loginUsername" name="username" class="form-input" placeholder="johndoe.smith"
                        required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="password-input-container">
                        <input type="password" id="loginPassword" name="password" class="form-input"
                            placeholder="••••••••" required>
                        <button type="button" class="eye-btn" onclick="togglePassword('loginPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div id="loginMessage" class="form-message"></div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
            </form>
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
                Don't have an account? <a href="#" onclick="openModal('registerModal')"
                    style="color: var(--primary);">Register here</a>
            </div>
            <button class="close-btn" onclick="closeModal('loginModal')">&times;</button>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal-overlay">
        <div class="modal glass" style="width: 550px;">
            <div class="modal-header">
                <h2 class="modal-title">Register As</h2>
                <div class="switch-container">
                    <button id="regProponentBtn" class="switch-btn active"
                        onclick="switchRegister('Proponent')">Proponent</button>
                    <button id="regEvaluatorBtn" class="switch-btn"
                        onclick="switchRegister('Evaluator')">Evaluator</button>
                </div>
            </div>
            <form id="registerForm">
                <input type="hidden" name="role" id="regRoleInput" value="Proponent">

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="regFirstName" name="first_name" class="form-input" required>
                        <span id="error-first_name" class="validation-error"></span>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="regMiddleName" name="middle_name" class="form-input">
                        <span id="error-middle_name" class="validation-error"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="regLastName" name="last_name" class="form-input" required>
                    <span id="error-last_name" class="validation-error"></span>
                </div>

                <div id="collegeGroup" class="form-group">
                    <label>College</label>
                    <select name="college" class="form-input">
                        <option value="CEIT">CEIT</option>
                        <option value="CTHM">CTHM</option>
                        <option value="CITTE">CITTE</option>
                        <option value="CBA">CBA</option>
                    </select>
                </div>

                <div id="positionGroup" class="form-group" style="display: none;">
                    <label>Position</label>
                    <select name="position" class="form-input">
                        <option value="Research Coordinator">Research Coordinator</option>
                        <option value="Extension Coordinator">Extension Coordinator</option>
                        <option value="College Dean">College Dean</option>
                        <option value="Head">Head</option>
                        <option value="Division Chief">Division Chief</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-input-container">
                            <input type="password" id="regPassword" name="password" class="form-input" required>
                            <button type="button" class="eye-btn" onclick="togglePassword('regPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-meter-container">
                            <div id="passwordMeter" class="password-meter-bar"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span id="passwordFeedback" class="password-text-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Re-enter Password</label>
                        <div class="password-input-container">
                            <input type="password" id="regConfirmPassword" name="confirm_password" class="form-input"
                                required>
                            <button type="button" class="eye-btn" onclick="togglePassword('regConfirmPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="error-confirm_password" class="validation-error"></span>
                    </div>
                </div>

                <div class="password-requirements">
                    <p style="margin-bottom: 5px; font-weight: 600;">Security Requirements:</p>
                    <ul style="list-style: none; padding: 0;">
                        <li id="req-length"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least 8 characters</li>
                        <li id="req-upper"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> One uppercase letter</li>
                        <li id="req-lower"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> One lowercase letter</li>
                        <li id="req-number"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> One numeric digit</li>
                        <li id="req-symbol"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> One special symbol</li>
                    </ul>
                </div>

                <div id="regMessage" class="form-message"></div>
                <button type="submit" id="registerSubmit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register</button>
            </form>
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
                Already have an account? <a href="#" onclick="openModal('loginModal')"
                    style="color: var(--primary);">Login here</a>
            </div>
            <button class="close-btn" onclick="closeModal('registerModal')">&times;</button>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 ScholarVault RDIE Repository System. All rights reserved.</p>
    </footer>

    <script src="assets/js/auth.js"></script>
    <script src="assets/js/security.js"></script>
</body>

</html>