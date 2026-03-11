<?php
require_once 'init.php';
// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();
// Handle profile picture upload
$upload_message = "";
if (isset($_POST['upload_image'])) {
    $target_dir = "uploads/profile_pictures/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $user_id = $_SESSION['username'];
    $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
    $new_filename = $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    $uploadOk = 1;
    
    // Check if image file is an actual image
    if(isset($_FILES["profile_picture"])) {
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $upload_message = "File is not an image.";
            $uploadOk = 0;
        }
    }
    
    // Check file size (5MB max)
    if ($_FILES["profile_picture"]["size"] > 5000000) {
        $upload_message = "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
        $upload_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $upload_message = "Sorry, your file was not uploaded. " . $upload_message;
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            
            $old_pic_query = "SELECT profile_picture FROM users WHERE username = ?";
            $old_stmt = $conn->prepare($old_pic_query);
            $old_stmt->bind_param("s", $user_id);
            $old_stmt->execute();
            $old_result = $old_stmt->get_result();
            if ($old_row = $old_result->fetch_assoc()) {
                if (!empty($old_row['profile_picture']) && file_exists($old_row['profile_picture'])) {
                    unlink($old_row['profile_picture']); // Deletes the old file from the server
                }
            }
            $old_stmt->close();
            
            // Update user's profile picture in database
            $update_query = "UPDATE users SET profile_picture = ? WHERE username = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ss", $target_file, $user_id);
            
            if ($stmt->execute()) {
                $upload_message = "Profile picture updated successfully!";
            } else {
                $upload_message = "Error updating database: " . $conn->error;
            }
            $stmt->close();
        } else {
            $upload_message = "Sorry, there was an error uploading your file.";
        }
    }
}

// User information
$user = $_SESSION['username'];
$query = "SELECT id, username, email, role, created_at, profile_picture FROM users WHERE username = ?";
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
$stmt->close();
$conn->close();

function formatDate($datetime) {
    return date("F j, Y \ | \t g:i A", strtotime($datetime));
}

// Default profile picture if none exists
$profile_pic = $user_data['profile_picture'] ? $user_data['profile_picture'] : "/nutrition_tracker/codes/images/default_profile.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>NutriTrack | Profile</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/profiles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="navbar">
        <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
    </div>
    
    <div class="container">
        <h1>Your Profile</h1>
        
        <!-- Profile picture display -->
        <div class="profile-picture-container">
            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
            </div>
            
            <!-- Profile picture upload form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="upload-form">
                <div class="file-input-container">
                    <input type="file" name="profile_picture" id="profile_picture" class="file-input">
                    <label for="profile_picture" class="file-label">Choose Image</label>
                </div>
                <?php echo csrf_field(); ?>
                <button type="submit" name="upload_image" class="upload-btn"><i class="bx bx-check"></i> Upload</button>
            </form>
            
            <?php if(!empty($upload_message)): ?>
                <div class="upload-message <?php echo (strpos($upload_message, 'successfully') !== false) ? 'success' : 'error'; ?>">
                    <i class="bx <?php echo (strpos($upload_message, 'successfully') !== false) ? 'bx-check-circle' : 'bx-error-circle'; ?>"></i>
                    <?php echo $upload_message; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <p><strong><i class="bx bx-user"></i> Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
            <p><strong><i class="bx bx-envelope"></i> Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <p><strong><i class="bx bx-id-card"></i> Role:</strong> <?php echo ucfirst(htmlspecialchars($user_data['role'])); ?></p>
            <p><strong><i class="bx bx-calendar"></i> Joined On:</strong> <?php echo formatDate($user_data['created_at']); ?></p>
        </div>
        
        <div class="btn-container">
            <a href="edit_profile.php" class="btn">Edit Profile</a>
            
            <form id="delete-form" action="delete_account.php" method="POST" style="display: none;">
                <?php echo csrf_field(); ?>
            </form>
            
            <a href="#" class="btn delete-btn" onclick="confirmDelete(event)">Delete Account</a>
        </div>

        <script>
            function confirmDelete(event) {
                event.preventDefault();
                
                if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
                    document.getElementById('delete-form').submit();
                }
            }
            
            // Add animation class to profile info items
            document.addEventListener('DOMContentLoaded', function() {
                const profileItems = document.querySelectorAll('.profile-info p');
                profileItems.forEach((item, index) => {
                    item.style.setProperty('--i', index + 1);
                });
            });
        </script>
        <a href="<?php echo ($user_data['role'] === 'admin') ? 'dashboard.php' : 'home.php'; ?>" class="btn home-btn">
         <?php echo ($user_data['role'] === 'admin') ? 'Back to Dashboard' : 'Back to Home'; ?>
        </a>
    </div>
</body>
</html>