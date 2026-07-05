const modal = document.getElementById('signup-modal');
const desktopSignupLink = document.getElementById('desktop-signup-link');
const mobileSignupLink = document.getElementById('mobile-signup-link');
const signinLink = document.getElementById('signin-link');
const closeButtons = document.getElementsByClassName('close');
const forgotPasswordLink = document.getElementById('forgot-password-link');
const forgotPasswordModal = document.getElementById('forgotPasswordModal');
const resetSuccessModal = document.getElementById('resetSuccessModal');
const resetSpinner = document.getElementById('resetSpinner');
const sendResetLink = document.getElementById('sendResetLink');
const resetSuccessOk = document.getElementById('resetSuccessOk');
const formMessage = document.getElementById('form-message');
const loginForm = document.getElementById('login-form');
const signupForm = document.getElementById('signup-form');

function openModal() {
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

if (desktopSignupLink) {
    desktopSignupLink.addEventListener('click', (event) => {
        event.preventDefault();
        openModal();
    });
}

if (mobileSignupLink) {
    mobileSignupLink.addEventListener('click', (event) => {
        event.preventDefault();
        openModal();
    });
}

if (signinLink) {
    signinLink.addEventListener('click', (event) => {
        event.preventDefault();
        closeModal();
    });
}

if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener('click', (event) => {
        event.preventDefault();
        if (forgotPasswordModal) {
            forgotPasswordModal.style.display = 'block';
        }
    });
}

Array.from(closeButtons).forEach((button) => {
    button.addEventListener('click', () => {
        if (forgotPasswordModal) forgotPasswordModal.style.display = 'none';
        if (resetSuccessModal) resetSuccessModal.style.display = 'none';
        closeModal();
    });
});

window.addEventListener('click', (event) => {
    if (event.target === modal) {
        closeModal();
    }
    if (event.target === forgotPasswordModal) {
        forgotPasswordModal.style.display = 'none';
    }
    if (event.target === resetSuccessModal) {
        resetSuccessModal.style.display = 'none';
    }
});

function togglePassword(inputId, icon) {
    const passwordInput = document.getElementById(inputId);
    if (!passwordInput) return;
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
}

function getCsrfToken() {
    if (window.csrfToken) {
        return window.csrfToken;
    }
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!formMessage) return;
        formMessage.textContent = '';

        const identifierInput = document.getElementById('signin_identifier');
        const passwordInput = document.getElementById('signin_password');

        const identifier = identifierInput ? identifierInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';

        if (!identifier || !password) {
            formMessage.textContent = 'Please enter username/email and password.';
            formMessage.className = 'form-message error-message';
            return;
        }

        const payload = {
            signin_identifier: identifier,
            password,
            csrf_token: getCsrfToken()
        };

        const response = await fetch('/api/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            formMessage.textContent = data.error || 'Login failed.';
            formMessage.className = 'form-message error-message';
            return;
        }

        window.location.href = data.redirect || '/dashboard';
    });
}

if (signupForm) {
    signupForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!formMessage) return;
        formMessage.textContent = '';

        const usernameInput = document.getElementById('signup_username');
        const emailInput = document.getElementById('signup_email');
        const passwordInput = document.getElementById('signup_password');
        const confirmInput = document.getElementById('signup_confirm_password');

        const username = usernameInput ? usernameInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';
        const confirmPassword = confirmInput ? confirmInput.value : '';

        if (!username || !email || !password || !confirmPassword) {
            formMessage.textContent = 'Please fill in all registration fields.';
            formMessage.className = 'form-message error-message';
            return;
        }

        if (password !== confirmPassword) {
            formMessage.textContent = 'Passwords do not match.';
            formMessage.className = 'form-message error-message';
            return;
        }

        const payload = {
            username,
            email,
            password,
            csrf_token: getCsrfToken()
        };

        const response = await fetch('/api/auth/register', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            formMessage.textContent = data.error || 'Registration failed.';
            formMessage.className = 'form-message error-message';
            return;
        }

        window.location.href = data.redirect || '/dashboard';
    });
}

if (sendResetLink) {
    sendResetLink.addEventListener('click', async (event) => {
        event.preventDefault();

        const emailInput = document.getElementById('resetEmail');
        const email = emailInput ? emailInput.value.trim() : '';
        if (!email) {
            alert('Please enter your email address.');
            return;
        }

        if (resetSpinner) resetSpinner.style.display = 'block';
        sendResetLink.disabled = true;

        const response = await fetch('/api/auth/forgot-password', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, csrf_token: getCsrfToken() })
        });

        if (resetSpinner) resetSpinner.style.display = 'none';
        sendResetLink.disabled = false;

        const data = await response.json();
        if (!response.ok) {
            alert(data.error || 'Unable to send reset link.');
            return;
        }

        if (forgotPasswordModal) forgotPasswordModal.style.display = 'none';
        if (resetSuccessModal) resetSuccessModal.style.display = 'block';
    });
}

if (resetSuccessOk) {
    resetSuccessOk.addEventListener('click', () => {
        if (resetSuccessModal) resetSuccessModal.style.display = 'none';
    });
}
