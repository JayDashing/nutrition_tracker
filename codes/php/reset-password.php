<?php
session_start();
require_once 'db.php';

$token = '';
$error = '';
$success = '';
$userEmail = '';

// Check for session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$validToken = false;

// Check if token is valid
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Validate reset token using reset_expires timestamp
    $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_expires > UNIX_TIMESTAMP()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
        $userData = $result->fetch_assoc();
        $userEmail = $userData['email'];
    } else {
        $error = "Invalid or expired reset token.";
    }
} else {
    $error = "No reset token provided.";
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validate password
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update password in database
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $updateStmt->bind_param("ss", $hashed_password, $userEmail);

        if ($updateStmt->execute()) {
            $_SESSION['success'] = "Password successfully reset. Enter your new password to log in.";
            header("Location: logreg.php");
            exit();
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | NutriTrack</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/reset-password.css">
</head>
<body>
    <div class="reset-container">
        <div class="logo-container">
            <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo">
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST">
                <div class="form-group">
                    <i class='bx bxs-envelope'></i>
                    <input type="text" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                </div>

                <div class="form-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="password" id="password" placeholder="New Password" required>
                    <i class='bx bx-show password-toggle' onclick="togglePassword('password', this)"></i>
                </div>

                <div class="form-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                    <i class='bx bx-show password-toggle' onclick="togglePassword('confirm_password', this)"></i>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
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
    </script>
</body>
</html>