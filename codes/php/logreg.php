<?php
require 'init.php';

verify_csrf();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use ZxcvbnPhp\Zxcvbn;

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/vendor/autoload.php';

function generateOTP() {
    return str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
}

// sendOTP function in logreg.php
function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_EMAIL'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true
            )
        );

        $mail->setFrom('nutritrack2025@gmail.com', 'NutriTrack');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your NutriTrack OTP Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
                <h1 style='text-align: center; color: #32a852; font-size: 28px;'>Nutri<span style='color: #b4d235;'>Track</span></h1>
                <div style='height: 4px; background-color: #32a852; margin: 20px 0;'></div>
                
                <h2 style='text-align: center; color: #32a852; font-size: 24px;'>Email Verification</h2>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    You have requested to verify your email for NutriTrack. We received a request to
                    verify the email address associated with this account.
                </p>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    Please use the verification code below to complete your registration. This code is
                    valid for 5 minutes.
                </p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <div style='display: inline-block; background-color: #32a852; color: white; font-size: 28px; 
                        font-weight: bold; padding: 15px 40px; border-radius: 5px; letter-spacing: 5px;'>
                        $otp
                    </div>
                </div>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    If you did not request this verification code, please ignore this email. Your account
                    will remain unchanged and no further action is required on your part.
                </p>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    Please note that this code will expire in 5 minutes for security reasons. If you need a new
                    code, you can request another verification code.
                </p>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    Thank you for using <span style='color: #32a852;'>Nutri<span style='color: #b4d235;'>Track</span></span>!
                </p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Exception: ' . $e->getMessage());
        return false;
    }
}

// Handle OTP Verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = implode('', $_POST['otp']);
    $stored_otp = $_SESSION['registration_otp'];
    $stored_otp_time = $_SESSION['registration_otp_time'];
    
    // Check if OTP is expired (5 minutes)
    if (time() - $stored_otp_time > 300) {
        $_SESSION['error'] = 'OTP has expired. Please request a new one.';
        header('Location: logreg.php?show_otp=true');
        exit;
    }
    
    if ($entered_otp === $stored_otp) {
        // Complete registration process
        $username = $_SESSION['temp_username'];
        $email = $_SESSION['temp_email'];
        $hashed_password = $_SESSION['temp_password'];
        $role = 'user';
        
        $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            // Clear all registration session variables
            unset($_SESSION['registration_otp']);
            unset($_SESSION['registration_otp_time']);
            unset($_SESSION['temp_username']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_password']);
            
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            header('Location: home.php');
            exit;
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again.';
            header('Location: logreg.php?show_otp=true');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Invalid OTP. Please try again.';
        header('Location: logreg.php?show_otp=true');
        exit;
    }
}

// Resend OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    $new_otp = generateOTP();
    $_SESSION['registration_otp'] = $new_otp;
    $_SESSION['registration_otp_time'] = time();
    
    if (sendOTP($_SESSION['temp_email'], $new_otp)) {
        $_SESSION['success'] = 'New OTP has been sent to your email.';
    } else {
        $_SESSION['error'] = 'Failed to send OTP. Please try again.';
    }
    header('Location: logreg.php?show_otp=true');
    exit;
}

// Sign-Up
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    if (!isset($_POST['terms'])) {
        $_SESSION['error'] = "You must agree to the Terms & Conditions to register.";
        header('Location: logreg.php');
        exit;
    }

    $username = $conn->real_escape_string($_POST['signup_username']);
    $email = $conn->real_escape_string($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $confirm_password = $_POST['signup_confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: logreg.php');
        exit;
    }

    //Password Policies
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password); // Matches anything that isn't a letter or number

    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters and include at least one uppercase letter, lowercase letter, number, and special character.";
        header('Location: logreg.php');
        exit;
    }

    $userData = [
        $username,
        $email,
        'NutriTrack' 
    ];

    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($password, $userData);

    if ($strength['score'] < 3) {
        $warning = !empty($strength['feedback']['warning'])
            ? $strength['feedback']['warning']
            : "The password is easy to guess.";
        
        if (!empty($strength['feedback']['suggestions'][0])) {
            $warning .= " " . $strength['feedback']['suggestions'][0];
        }
        
        $_SESSION['error'] = "(Score: " . $strength['score'] . "/4) " . $warning;

        header('Location: logreg.php');
        exit;
    }

    $checkQuery = "SELECT * FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username or Email already exists.";
        header('Location: logreg.php');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Store temporary registration data
    $_SESSION['temp_username'] = $username;
    $_SESSION['temp_email'] = $email;
    $_SESSION['temp_password'] = $hashed_password;
    
    // Generate and send OTP
    $otp = generateOTP();
    $_SESSION['registration_otp'] = $otp;
    $_SESSION['registration_otp_time'] = time();
    
    if (sendOTP($email, $otp)) {
        header('Location: logreg.php?show_otp=true');
        exit;
    } else {
        $_SESSION['error'] = "Failed to send verification email. Please try again.";
        header('Location: logreg.php');
        exit;
    }
}

// Sign-In
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signin'])) {
    $identifier = $conn->real_escape_string($_POST['signin_identifier']);
    $password = $_POST['signin_password'];

    $query = "SELECT * FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: " . ($user['role'] == 'admin' ? 'dashboard.php' : 'home.php'));
            exit;
        }
    }
    
    $_SESSION['error'] = "Invalid username/email or password.";
    header('Location: logreg.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>NutriTrack | Login & Register</title>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/logreg.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <!-- Login Form Side -->
        <div class="form-wrapper login-wrapper">
            <div class="logo-container">
                <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo">
            </div>
            
            <h2 class="title" style="text-align: center;">Welcome Back</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <i class='bx bxs-user'></i>
                    <input type="text" name="signin_identifier" placeholder="Username or Email" required>
                </div>
                
                <div class="form-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="signin_password" id="signin_password" placeholder="Password" required>
                    <i class='bx bx-show password-toggle' onclick="togglePassword('signin_password', this)"></i>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                    <a href="#"><strong>Forgot Password?</strong></a>
                </div>
                <?php echo csrf_field(); ?>
                <button class="btn" type="submit" name="signin">Sign In</button>
            </form>
            
            <div class="social-divider">
                <span>Or continue with</span>
            </div>
            
            <div class="social-login">
                <div class="social-btn facebook"><i class='bx bxl-facebook'></i></div>
                <div class="social-btn google"><i class='bx bxl-gmail'></i></div>
                <div class="social-btn twitter"><i class='bx bxl-twitter'></i></div>
            </div>
            
            <div class="toggle-form" id="mobile-toggle">
                <span>Don't have an account? <a href="#" id="mobile-signup-link">Sign Up</a></span>
            </div>
        </div>
        
        <!-- Signup Side -->
        <div class="form-wrapper signup-wrapper" id="signup-side">
            <div class="signup-content">
                <h2>New to NutriTrack?</h2>
                <p>Join us today and start your journey to better nutrition tracking!</p>
                <a href="#" class="signup-btn" id="desktop-signup-link">Sign Up</a>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
<div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 class="otp-title">Forgot Password</h2>
        <p class="otp-subtitle">Enter your email address to receive password reset instructions.</p>
        
        <div class="form-group">
            <i class='bx bxs-envelope'></i>
            <input type="email" id="resetEmail" placeholder="Enter your email" required>
        </div>

        <button class="verify-btn" id="sendResetLink">Send Reset Link</button>
        <div class="loading-spinner" id="resetSpinner"></div>
    </div>
</div>

<!-- Reset Success Modal -->
<div id="resetSuccessModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 class="otp-title">Check Your Email</h2>
        <p class="otp-subtitle">An email with instructions has been sent to you if the email was previously saved on your account. Please check all your folders, including spam.</p>
        <button class="verify-btn" id="resetSuccessOk">OK</button>
    </div>
</div>
    
    <!-- Modal for Registration -->
    <div id="signup-modal" class="modal" style="display: <?php echo isset($_GET['show_signup']) ? 'block' : 'none'; ?>">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="title">Create Account</h2>
            
            <form action="" method="POST">
                <div class="form-group">
                    <i class='bx bxs-user'></i>
                    <input type="text" name="signup_username" placeholder="Username" required>
                </div>
                
                <div class="form-group">
                    <i class='bx bxs-envelope'></i>
                    <input type="email" name="signup_email" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                     <i class='bx bxs-lock-alt'></i>
                     <input type="password" name="signup_password" id="signup_password" 
                        placeholder="Password" 
                        required 
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w]).{8,}" 
                        title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
                     <i class='bx bx-show password-toggle' onclick="togglePassword('signup_password', this)"></i>
                </div>
                
                <div class="form-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="signup_confirm_password" id="signup_confirm_password" 
                        placeholder="Confirm Password" 
                        required
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w]).{8,}" 
                        title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
                    <i class='bx bx-show password-toggle' onclick="togglePassword('signup_confirm_password', this)"></i>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="terms" id="termsCheckbox" required>
                    <label for="termsCheckbox">I agree with the <a href="terms.php" target="_blank">Terms & Conditions</a></label>
                </div>
                <?php echo csrf_field(); ?>
                <button class="btn" type="submit" name="signup">Create Account</button>
            </form>
            
            <div class="toggle-form">
                <span>Already have an account? <a href="#" id="signin-link">Sign In</a></span>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otp-modal" class="otp-modal" style="display: <?php echo isset($_GET['show_otp']) ? 'block' : 'none'; ?>">
        <div class="otp-modal-content">
            <h2 class="otp-title">Email Verification</h2>
            <p class="otp-subtitle">Please enter the 5-digit code sent to <?php echo isset($_SESSION['temp_email']) ? $_SESSION['temp_email'] : ''; ?></p>
            
            <!-- Updated OTP form in the HTML to ensure proper form submission -->
            <form action="" method="POST" id="otp-form">
                <div class="otp-inputs">
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                </div>
                <div class="otp-timer">Code expires in: <span id="timer"><strong>5:00</strong></span></div>
            <?php echo csrf_field(); ?>
            <button type="submit" name="resend_otp" class="resend-btn" id="resend-btn">Resend Code</button>
            <div><button type="submit" name="verify_otp" class="verify-btn">Verify</button></div>
        </form>
    </div>
 </div>
 <script src="/nutrition_tracker/codes/js/utils.js"></script>
 <script src="/nutrition_tracker/codes/js/logreg.js"></script>
</body>
</html>