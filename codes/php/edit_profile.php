<?php
session_start();

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

//Database connection
require_once 'db.php';

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

    if (!empty($_POST['password'])) {
        $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET username = ?, email = ?, password = ? WHERE username = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssss", $new_username, $new_email, $new_password, $user);
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

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
            <input type="password" id="password" name="password">
            <span class="focus-border"></span>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn"><i class="fas fa-save" style="margin-right: 8px;"></i>Update Profile</button>
            <a href="profile.php" class="btn"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Profile</a>
        </div>
    </form>
</div>

</body>
</html>