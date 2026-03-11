<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

$username = $_SESSION['username'];

//Get user's meal count and total calories
$meal_query = "SELECT COUNT(*) as meal_count, SUM(calories) as total_calories FROM meals WHERE username = ?";
$stmt = $conn->prepare($meal_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$meal_data = $stmt->get_result()->fetch_assoc();
$meal_count = $meal_data['meal_count'] ?? 0;
$total_calories = $meal_data['total_calories'] ?? 0;
$stmt->close();

// Handle progress post submission
$upload_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['share_progress'])) {
    $description = $_POST['progress_description'];
    $image_path = "";
    $status = "pending"; // Changed default status to pending for admin approval
    
    // Handle file upload
    if (isset($_FILES['progress_image']) && $_FILES['progress_image']['error'] == 0) {
        $target_dir = "uploads/progress/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["progress_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES["progress_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $upload_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $upload_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    // Insert progress post into database
    if (empty($upload_message)) {
        $created_at = date("Y-m-d H:i:s");
        $insert_query = "INSERT INTO share_progress (username, description, image_path, created_at, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssss", $username, $description, $image_path, $created_at, $status);
        
        if ($stmt->execute()) {
            $upload_message = "Your progress has been shared successfully! It will be visible after admin approval.";
        } else {
            $upload_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch ONLY APPROVED progress posts for gallery - modified to only show approved posts
$posts_query = "SELECT * FROM share_progress WHERE status = 'approved' ORDER BY created_at DESC LIMIT 9";
$result = $conn->query($posts_query);
$progress_posts = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $progress_posts[] = $row;
    }
}

// Customized quotes based on meal count
$meal_quotes = [
    ["quote" => "Starting your journey! Every meal you track builds better habits. 🍏", "category" => "Beginners"],
    ["quote" => "You're developing a great tracking routine. Keep it up! 🚶", "category" => "Progress"],
    ["quote" => "Consistency is key! You're building valuable nutrition data. 🔥", "category" => "Consistency"],
    ["quote" => "Impressive tracking discipline! Your nutrition awareness is growing. 💪", "category" => "Discipline"],
    ["quote" => "You're a tracking master! Your dedication to nutrition is inspiring. 🏆", "category" => "Mastery"]
];

// Select quote based on meal count
if ($meal_count < 10) {
    $quote_index = 0; // Beginner
} elseif ($meal_count < 25) {
    $quote_index = 1; // Progress
} elseif ($meal_count < 50) {
    $quote_index = 2; // Consistency
} elseif ($meal_count < 100) {
    $quote_index = 3; // Discipline
} else {
    $quote_index = 4; // Mastery
}

$meal_quote = $meal_quotes[$quote_index]["quote"];
$meal_category = $meal_quotes[$quote_index]["category"];

// Customized tips based on calorie count
$calorie_tips = [
    ["tip" => "Focus on nutrient-dense foods to make every calorie count! 🥗", "category" => "Nutrition Quality"],
    ["tip" => "Balance your macros for sustainable energy throughout the day. 🍽️", "category" => "Macronutrients"],
    ["tip" => "Consider meal timing to optimize your energy levels and metabolism. ⏱️", "category" => "Meal Timing"],
    ["tip" => "Hydration affects metabolism - aim for 8 glasses of water daily! 💧", "category" => "Hydration"],
    ["tip" => "Remember that quality matters as much as quantity in your nutrition journey. 📊", "category" => "Balance"]
];

// Select tip based on calorie count
if ($total_calories < 5000) {
    $tip_index = 0;
} elseif ($total_calories < 20000) {
    $tip_index = 1;
} elseif ($total_calories < 50000) {
    $tip_index = 2;
} elseif ($total_calories < 100000) {
    $tip_index = 3;
} else {
    $tip_index = 4;
}

$calorie_tip = $calorie_tips[$tip_index]["tip"];
$calorie_category = $calorie_tips[$tip_index]["category"];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Motivation | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/motivate.css">
</head>
<body>

<!-- Navbar - without the home button -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <div class="welcome-section">
        <h1>Stay Motivated, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="subtitle">Your journey to better nutrition continues!</p>
    </div>

    <div class="stats-card">
        <div class="stat-item">
            <i class='bx bx-food-menu stat-icon'></i>
            <div class="stat-content">
                <h3>Total Meals Tracked</h3>
                <p class="stat-number"><?php echo $meal_count; ?></p>
            </div>
        </div>
        <div class="stat-item">
            <i class='bx bx-flame stat-icon'></i>
            <div class="stat-content">
                <h3>Total Calories</h3>
                <p class="stat-number"><?php echo number_format($total_calories); ?> kcal</p>
            </div>
        </div>
    </div>

    <div class="motivation-card quote-card">
        <span class="category-tag"><?php echo $meal_category; ?></span>
        <h3><i class='bx bx-dish'></i> Based on Your Meal Tracking</h3>
        <p class="quote"><?php echo $meal_quote; ?></p>
    </div>
    
    <div class="motivation-card tip-card">
        <span class="category-tag"><?php echo $calorie_category; ?></span>
        <h3><i class='bx bx-bulb'></i> Based on Your Calorie Intake</h3>
        <p class="tip"><?php echo $calorie_tip; ?></p>
    </div>
    
    <!-- Progress Sharing Section -->
    <div class="progress-sharing-section">
        <h2><i class='bx bx-camera'></i> Share Your Progress</h2>
        <p class="section-subtitle">Inspire others by sharing your nutrition journey</p>
        
        <?php if (!empty($upload_message)): ?>
            <div class="message-box <?php echo (strpos($upload_message, "Error") !== false || strpos($upload_message, "Sorry") !== false) ? "error" : "success"; ?>">
                <?php echo $upload_message; ?>
            </div>
        <?php endif; ?>
        
        <form class="progress-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="progress_image"><i class='bx bx-image-add'> </i><strong>Upload a progress photo</strong></label>
                <input type="file" id="progress_image" name="progress_image" accept="image/*" class="file-input">
                <div class="image-preview-container">
                    <img id="image-preview" src="#" alt="Preview" style="display: none;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="progress_description"><i class='bx bx-message-detail'></i> <strong>Share your journey</strong></label>
                <textarea id="progress_description" name="progress_description" rows="4" placeholder="Tell us about your nutrition journey, goals, challenges, or achievements..." required></textarea>
            </div>
            <?php echo csrf_field(); ?>
            <button type="submit" name="share_progress" class="btn btn-share">Share My Progress</button>
        </form>
        
        <!-- Added note about admin approval -->
        <div class="admin-note">
            <p><strong><em>Note: All shared progress requires admin approval before appearing in the community gallery.</em></strong></p>
        </div>
    </div>
    
<!-- Community Progress Gallery -->
<div class="progress-gallery-section">
    <h2><i class='bx bx-group'></i> Community Progress Gallery</h2>
    <p class="section-subtitle">Get inspired by others on their nutrition journey</p>
    
    <div class="progress-gallery">
        <?php if (empty($progress_posts)): ?>
            <div class="empty-gallery">
                <i class='bx bx-camera-off'></i>
                <p>No approved progress posts yet. Be the first to share your journey!</p>
            </div>
        <?php else: ?>
            <?php foreach ($progress_posts as $post): ?>
                <div class="progress-card">
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="progress-image">
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Progress photo">
                        </div>
                    <?php endif; ?>
                    <div class="progress-content">
                        <div class="progress-user">
                            <i class='bx bx-user-circle'></i>
                            <span><?php echo htmlspecialchars($post['username']); ?></span>
                        </div>
                        <p class="progress-description"><?php echo htmlspecialchars($post['description']); ?></p>
                        <p class="progress-date"><?php echo date("F j, Y", strtotime($post['created_at'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
    
    <div style="text-align: center; margin: 30px auto;">
        <a href="home.php" class="btn">Back to Home</a>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/motivation.js"></script>
</body>
</html>