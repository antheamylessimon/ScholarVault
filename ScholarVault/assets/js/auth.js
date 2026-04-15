// assets/js/auth.js

// Modal controls
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function openModal(id) {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on outside click
window.onclick = function (event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}

// Login Toggle (Proponent/Evaluator vs Admin)
function switchLogin(type) {
    const proBtn = document.getElementById('loginProponentBtn');
    const admBtn = document.getElementById('loginAdminBtn');
    const loginAs = document.getElementById('loginAsInput');

    if (type === 'admin') {
        admBtn.classList.add('active');
        proBtn.classList.remove('active');
        loginAs.value = 'admin';
    } else {
        proBtn.classList.add('active');
        admBtn.classList.remove('active');
        loginAs.value = 'proponent_evaluator';
    }
}

// Register Toggle (Proponent vs Evaluator)
function switchRegister(role) {
    const proBtn = document.getElementById('regProponentBtn');
    const evaBtn = document.getElementById('regEvaluatorBtn');
    const roleInput = document.getElementById('regRoleInput');
    const collegeGroup = document.getElementById('collegeGroup');
    const positionGroup = document.getElementById('positionGroup');

    if (role === 'Evaluator') {
        evaBtn.classList.add('active');
        proBtn.classList.remove('active');
        roleInput.value = 'Evaluator';
        collegeGroup.style.display = 'none';
        positionGroup.style.display = 'block';
    } else {
        proBtn.classList.add('active');
        evaBtn.classList.remove('active');
        roleInput.value = 'Proponent';
        collegeGroup.style.display = 'block';
        positionGroup.style.display = 'none';
    }
}

// Dark Mode Toggle
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
    localStorage.setItem('theme', theme);
    // Add cookie for PHP consistency
    document.cookie = `theme=${theme}; path=/`;
}

document.addEventListener('DOMContentLoaded', () => {
    // Check local storage on load
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = body.classList.contains('dark');
            setTheme(isDark ? 'light' : 'dark');
        });
    }
});

// AJAX Login
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const msgDiv = document.getElementById('loginMessage');

    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            msgDiv.style.color = 'var(--primary)';
            msgDiv.textContent = data.message;
            setTimeout(() => window.location.href = data.redirect, 1000);
        } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.textContent = data.message;
        }
    } catch (error) {
        msgDiv.textContent = 'An error occurred. Please try again.';
    }
});


// AJAX Register
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();


    const formData = new FormData(e.target);
    const msgDiv = document.getElementById('regMessage');

    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            msgDiv.style.color = 'var(--primary)';
            msgDiv.textContent = data.message;
            setTimeout(() => {
                closeModal('registerModal');
                openModal('loginModal');
            }, 2000);
        } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.textContent = data.message;
        }
    } catch (error) {
        msgDiv.textContent = 'An error occurred. Please try again.';
    }
});

// Live Validation Logic
document.addEventListener('DOMContentLoaded', () => {
    const regForm = document.getElementById('registerForm');
    if (!regForm) return;

    const inputs = {
        firstName: document.getElementById('regFirstName'),
        middleName: document.getElementById('regMiddleName'),
        lastName: document.getElementById('regLastName'),
        password: document.getElementById('regPassword'),
        confirmPassword: document.getElementById('regConfirmPassword')
    };

    const errors = {
        firstName: document.getElementById('error-first_name'),
        middleName: document.getElementById('error-middle_name'),
        lastName: document.getElementById('error-last_name'),
        password: document.getElementById('passwordFeedback'),
        confirmPassword: document.getElementById('error-confirm_password')
    };

    const touched = {};
    const submitBtn = document.getElementById('registerSubmit');
    let nameAvailability = { checked: false, available: true, message: '' };
    let nameCheckTimeout = null;

    const checkNameAvailability = async () => {
        const first = inputs.firstName.value.trim();
        const last = inputs.lastName.value.trim();
        
        if (first.length < 2 || last.length < 2) {
            nameAvailability = { checked: false, available: true, message: '' };
            checkValidity();
            return;
        }

        const formData = new FormData();
        formData.append('first_name', first);
        formData.append('last_name', last);

        try {
            const res = await fetch('api/check_name.php', { method: 'POST', body: formData });
            const data = await res.json();
            nameAvailability = { checked: true, available: data.available, message: data.message || '' };
        } catch (e) {
            nameAvailability = { checked: false, available: true, message: '' };
        }
        checkValidity();
    };

    const validateName = (val, type) => {
        if (!val) {
            if (type === 'Middle Initial') return '';
            return 'Required.';
        }

        if (val.includes('  ')) return `${type}: No double spaces.`;
        if (/(.)\1\1/.test(val.replace(/\s/g, '').toLowerCase())) {
            return `${type}: No 3+ consecutive letters.`;
        }
        if (type === 'Middle Initial' && val.length > 2) return `Max 2 chars.`;

        const words = val.split(/[ \-]/);
        const isTitleCase = words.every(word => {
            if (word.length === 0) return true;
            return /^[A-Z][a-z]*$/.test(word);
        });

        if (!isTitleCase) return `${type} must be Title Case (e.g., John).`;
        
        // Final check for name availability if we are validating the Last Name
        if (type === 'Last Name' && nameAvailability.checked && !nameAvailability.available) {
            return nameAvailability.message;
        }

        return '';
    };

    const validatePassword = (val) => {
        const requirements = {
            length: val.length >= 8,
            upper: /[A-Z]/.test(val),
            lower: /[a-z]/.test(val),
            number: /[0-9]/.test(val),
            symbol: /[^A-Za-z0-9]/.test(val)
        };

        Object.keys(requirements).forEach(req => {
            const el = document.getElementById('req-' + req);
            if (el) {
                if (requirements[req]) el.classList.add('valid');
                else el.classList.remove('valid');
            }
        });

        const score = Object.values(requirements).filter(Boolean).length;
        const meter = document.getElementById('passwordMeter');
        const feedback = document.getElementById('passwordFeedback');
        const requirementsDiv = document.querySelector('.password-requirements');

        if (requirementsDiv) {
            // Show when typing, hide when all requirements met
            if (val.length > 0 && score < 5) {
                requirementsDiv.style.display = 'block';
            } else {
                requirementsDiv.style.display = 'none';
            }
        }

        if (!meter || !feedback) return score === 5 ? '' : 'incomplete';

        let strength = '', color = '', width = '';
        if (val.length === 0) {
            strength = ''; color = 'transparent'; width = '0%';
        } else if (score <= 2) {
            strength = 'Weak'; color = '#ef4444'; width = '33%';
        } else if (score <= 4) {
            strength = 'Medium'; color = '#f59e0b'; width = '66%';
        } else {
            strength = 'Strong'; color = '#10b981'; width = '100%';
        }

        meter.style.backgroundColor = color;
        meter.style.width = width;
        feedback.textContent = strength;
        feedback.style.color = color;

        return score === 5 ? '' : 'Incomplete requirements.';
    };

    const checkValidity = () => {
        const results = {
            firstName: { val: inputs.firstName.value, err: validateName(inputs.firstName.value, 'First Name'), id: 'regFirstName' },
            middleName: { val: inputs.middleName.value, err: validateName(inputs.middleName.value, 'Middle Initial'), id: 'regMiddleName' },
            lastName: { val: inputs.lastName.value, err: validateName(inputs.lastName.value, 'Last Name'), id: 'regLastName' },
            password: { val: inputs.password.value, err: validatePassword(inputs.password.value), id: 'regPassword' },
            confirmPassword: { val: inputs.confirmPassword.value, err: (inputs.password.value !== inputs.confirmPassword.value) ? 'Passwords mismatch.' : '', id: 'regConfirmPassword' }
        };

        Object.keys(results).forEach(key => {
            const field = results[key];
            const isTouched = touched[field.id];
            const hasInput = field.val.length > 0;
            // Only show error visually if explored by user
            const showError = (isTouched || hasInput) && !!field.err;

            if (errors[key]) errors[key].textContent = showError ? field.err : '';
            if (inputs[key]) inputs[key].classList.toggle('is-invalid', showError);
        });

        const isValid = !results.firstName.err && !results.middleName.err && !results.lastName.err && !results.password.err && !results.confirmPassword.err && (nameAvailability.available);
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.5';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }
    };

    // Attach listeners
    Object.values(inputs).forEach(input => {
        if (!input) return;
        input.addEventListener('input', () => {
            if (input.id === 'regFirstName' || input.id === 'regLastName') {
                clearTimeout(nameCheckTimeout);
                nameCheckTimeout = setTimeout(checkNameAvailability, 600);
            }
            checkValidity();
        });
        input.addEventListener('blur', () => {
            touched[input.id] = true;
            if (input.id === 'regFirstName' || input.id === 'regLastName') {
                checkNameAvailability();
            }
            checkValidity();
        });
        if (input.id === 'regPassword') {
            input.addEventListener('focus', () => {
                const score = Object.values({
                    length: input.value.length >= 8,
                    upper: /[A-Z]/.test(input.value),
                    lower: /[a-z]/.test(input.value),
                    number: /[0-9]/.test(input.value),
                    symbol: /[^A-Za-z0-9]/.test(input.value)
                }).filter(Boolean).length;
                
                const requirementsDiv = document.querySelector('.password-requirements');
                if (requirementsDiv && score < 5) requirementsDiv.style.display = 'block';
            });
        }
    });

    // Initial check (silent - button disabled but no red boxes)
    checkValidity();
});
