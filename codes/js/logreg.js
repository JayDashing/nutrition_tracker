// Modal functionality
const modal = document.getElementById("signup-modal");
const otpModal = document.getElementById("otp-modal");
const desktopSignupLink = document.getElementById("desktop-signup-link");
const mobileSignupLink = document.getElementById("mobile-signup-link");
const signinLink = document.getElementById("signin-link");
const closeBtn = document.getElementsByClassName("close")[0];

function openModal() {
    modal.style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeModal() {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
}

if (desktopSignupLink) desktopSignupLink.addEventListener("click", openModal);
if (mobileSignupLink) mobileSignupLink.addEventListener("click", openModal);
if (signinLink) signinLink.addEventListener("click", closeModal);
if (closeBtn) closeBtn.addEventListener("click", closeModal);

window.addEventListener("click", function(event) {
    if (event.target == modal) {
        closeModal();
    }
});

// Toggle password visibility
function togglePassword(inputId, icon) {
    const passwordInput = document.getElementById(inputId);
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        passwordInput.type = "password";
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
}

// Get modal elements
const forgotPasswordModal = document.getElementById('forgotPasswordModal');
const resetSuccessModal = document.getElementById('resetSuccessModal');
const resetSpinner = document.getElementById('resetSpinner');

// Get the button that opens the modal
const forgotPasswordLink = document.querySelector('.form-check a'); // Add href="javascript:void(0)" to your forgot password link

// Get close buttons
const closeButtons = document.getElementsByClassName('close');

// Get action buttons
const sendResetLink = document.getElementById('sendResetLink');
const resetSuccessOk = document.getElementById('resetSuccessOk');

// Open forgot password modal
forgotPasswordLink.onclick = function() {
    forgotPasswordModal.style.display = 'block';
}

// Close modals when clicking (x)
Array.from(closeButtons).forEach(button => {
    button.onclick = function() {
        forgotPasswordModal.style.display = 'none';
        resetSuccessModal.style.display = 'none';
    }
});

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == forgotPasswordModal || event.target == resetSuccessModal) {
        forgotPasswordModal.style.display = 'none';
        resetSuccessModal.style.display = 'none';
    }
}

// Handle success OK button
resetSuccessOk.onclick = function() {
    resetSuccessModal.style.display = 'none';
}

// OTP Input Handling
document.querySelectorAll('.otp-input').forEach((input, index) => {
    input.addEventListener('keyup', function(e) {
        if (e.key >= 0 && e.key <= 9) {
            if (index < 4) {
                document.querySelectorAll('.otp-input')[index + 1].focus();
            }
        } else if (e.key === 'Backspace') {
            if (index > 0) {
                document.querySelectorAll('.otp-input')[index - 1].focus();
            }
        }
    });
});

// Timer Function
if (document.getElementById('timer')) {
    let timeLeft = 300; // 5 minutes in seconds
    const timerDisplay = document.getElementById('timer');
    const resendBtn = document.getElementById('resend-btn');
    
    const timer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            timerDisplay.textContent = "0:00";
            resendBtn.disabled = false;
            resendBtn.classList.add('active');
        }
        
        timeLeft--;
    }, 1000);
}

document.getElementById('otp-form').addEventListener('submit', function(e) {
    console.log('Form submitted'); // Add this to check if form submission is working
});

document.querySelector('button[name="verify_otp"]').addEventListener('click', function(e) {
    const otpInputs = document.querySelectorAll('.otp-input');
    const allFilled = Array.from(otpInputs).every(input => input.value.trim() !== '');
    
    if (!allFilled) {
        e.preventDefault();
        alert('Please fill in all OTP digits');
    }
});

// Modify the existing script with these changes

sendResetLink.onclick = function() {
    const email = document.getElementById('resetEmail').value;
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
        alert('Please enter your email address');
        return;
    }

    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    // Show loading spinner
    resetSpinner.style.display = 'block';
    sendResetLink.disabled = true;

    // Create a FormData object to send the email
    const formData = new FormData();
    formData.append('reset_email', email);
    formData.append('csrf_token', getCsrfToken());

    // Use fetch to send the reset request
    fetch('forgot-password-handler.php', {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        // log raw response for debugging
        console.log('raw response text:', text);
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse failed, server returned:', text);
            throw e;
        }
        resetSpinner.style.display = 'none';
        sendResetLink.disabled = false;
        forgotPasswordModal.style.display = 'none';
        
        // Check the response from the server
        if (data.status === 'success') {
            resetSuccessModal.style.display = 'block';
        } else {
            alert(data.message || 'An error occurred. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resetSpinner.style.display = 'none';
        sendResetLink.disabled = false;
        alert('An error occurred. Please try again.');
    });
}