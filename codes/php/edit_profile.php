<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

//User information
$user = $_SESSION['username'];
$query = "SELECT username, email FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit();
}

$message = "";

//Profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);

    $update_password = false;
    if (!empty($new_password)) {
        // Validate password
        if (strlen($new_password) < 8 || !preg_match('/[a-z]/', $new_password) || !preg_match('/[A-Z]/', $new_password) || !preg_match('/\d/', $new_password) || !preg_match('/[^\w]/', $new_password)) {
            $message = "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
        } else {
            $update_password = true;
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        }
    }

    if (empty($message)) {
        if ($update_password) {
            $update_query = "UPDATE users SET username = ?, email = ?, password = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssss", $new_username, $new_email, $hashed_password, $user);
        } else {
            $update_query = "UPDATE users SET username = ?, email = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sss", $new_username, $new_email, $user);
        }

        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $message = "Profile updated successfully!";
        } else {
            $message = "Error updating profile: " . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Edit Profile | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/edit_profiles.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation classes to form groups with a delay
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('active');
                }, 200 * (index + 1));
            });
            
            // Add input focus animation
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('input-focused');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('input-focused');
                });
            });
        });
    </script>
</head>
<body>
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <!-- Decorative elements -->
    <div class="leaf-decoration leaf-1"></div>
    <div class="leaf-decoration leaf-2"></div>
    <div class="leaf-decoration leaf-3"></div>
    
    <div class="profile-decoration">
        <i class="fas fa-user-edit profile-icon"></i>
    </div>
    
    <h1>Edit Profile</h1>

    <?php if ($message): ?>
        <div class="message"> 
            <i class="fas fa-check-circle" style="margin-right: 10px; color: #2e7d32;"></i>
            <?php echo $message; ?> 
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username"><i class="fas fa-user" style="margin-right: 8px;"></i>Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
            <span class="focus-border"></span>
        </div>

        <div class="form-group">
            <label for="email"><i class="fas fa-envelope" style="margin-right: 8px;"></i>Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            <span class="focus-border"></span>
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock" style="margin-right: 8px;"></i>New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password"
                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w]).{8,}" 
                title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
            <span class="focus-border"></span>
        </div>
        <?php echo csrf_field(); ?>
        <div class="btn-container">
            <button type="submit" class="btn"><i class="fas fa-save" style="margin-right: 8px;"></i>Update Profile</button>
            <a href="profile.php" class="btn"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Profile</a>
        </div>
    </form>
</div>

</body>
</html>