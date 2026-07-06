<?php
require_once 'init.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// suppress notices/warnings to avoid breaking JSON output
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

error_log("SMTP_EMAIL from ENV: " . ($_ENV['SMTP_EMAIL'] ?? 'not set'));
error_log("SMTP_EMAIL from getenv: " . (getenv('SMTP_EMAIL') ?? 'not set'));

header('Content-Type: application/json');

// debugging incoming POST data
error_log('forgot-password-handler POST: ' . print_r($_POST, true));
// log tokens for CSRF debugging
error_log('forgot-password-handler CSRF session=' . ($_SESSION['csrf_token'] ?? 'none') . ' post=' . ($_POST['csrf_token'] ?? 'none'));

verify_csrf();

function generateResetToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function sendResetEmail($email, $resetToken) {
    error_log("Attempting to send reset email to: $email with token: $resetToken");
    $mail = new PHPMailer(true);
    
    // buffer any notice/warning output from PHPMailer
    ob_start();
    try {
        // use SMTP (like logreg.php) instead of sendmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_EMAIL'] ?? '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // some local setups may not permit outgoing SMTP – fallback to logging
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];

        $mail->setFrom($_ENV['SMTP_EMAIL'] ?? 'no-reply@localhost', 'NutriTrack');
        $mail->addAddress($email);
        
        $resetLink = "http://localhost/nutrition_tracker/codes/php/reset-password.php?token=" . $resetToken;
        
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request for NutriTrack';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; background-color: #f9f9f9;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='font-family: Arial, sans-serif; font-size: 36px; font-weight: bold; letter-spacing: 1px;'>
                        <span style='color: #32a852;'>Nutri</span><span style='color: #c9e265;'>Track</span>
                    </div>
                </div>
                <div style='height: 5px; background-color: #32a852; margin-bottom: 20px; border-radius: 3px;'></div>
                <h2 style='color: #32a852; text-align: center;'>Password Reset Request</h2>
                <p style='font-size: 16px; color: #333;'>You have requested to reset your password for NutriTrack. We received a request to reset the password associated with this email address.</p>
                <p style='font-size: 16px; color: #333;'>Please click the link below to reset your password. This link will take you to a secure page where you can create a new password.</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='$resetLink' style='display: inline-block; padding: 10px 20px; background-color: #32a852; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                </div>
                <p style='font-size: 16px; color: #333;'>If you did not request this password reset, please ignore this email. Your password will remain unchanged and no further action is required on your part.</p>
                <p style='font-size: 16px; color: #333;'>Please note that this link will expire in 1 hour for security reasons. If you need a new link, you can request another password reset.</p>
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    Thank you for using <span style='color: #32a852;'>Nutri<span style='color: #b4d235;'>Track</span></span>!
                </p>
            </div>";

        $result = $mail->send();
        error_log("Email send result: " . ($result ? 'success' : 'failed'));
        ob_end_clean();
        return $result;
    } catch (Exception $e) {
        ob_end_clean();
        error_log('PHPMailer Exception: ' . $e->getMessage());
        // if SMTP failed and we're on local dev, log link instead
        error_log('Reset link was: http://localhost/nutrition_tracker/codes/php/reset-password.php?token=' . $resetToken);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate email
    if (!isset($_POST['reset_email']) || empty($_POST['reset_email'])) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required']);
        exit;
    }

    $email = $conn->real_escape_string($_POST['reset_email']);

    // Check if email exists in database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email address']);
        exit;
    }

    // Generate reset token
    $resetToken = generateResetToken();
    $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $resetExpires = strtotime('+1 hour');

    // Store reset token in database
    $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiry = ?, reset_expires = ? WHERE email = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssss", $resetToken, $resetTokenExpiry, $resetExpires, $email);
    
    if ($updateStmt->execute()) {
        error_log("Database updated successfully for email: $email");
        // Send reset email
        if (sendResetEmail($email, $resetToken)) {
            error_log("Reset email sent successfully");
            echo json_encode(['status' => 'success', 'message' => 'Reset link sent to your email']);
        } else {
            error_log("Failed to send reset email");
            // For local dev include the link in response for manual testing
            $debugLink = "http://localhost/nutrition_tracker/codes/php/reset-password.php?token=" . $resetToken;
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to send reset email',
                'debug_link' => $debugLink
            ]);
        }
    } else {
        error_log("Database update failed: " . $updateStmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>