<?php
// Start the session so CSRF works
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate the CSRF token if it's missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create a helper function so you can easily drop the token into forms
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}