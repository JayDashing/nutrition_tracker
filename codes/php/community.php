<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

$share_message = "";

verify_csrf();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['share_meal'])) {
    // Get user ID
    $user_id_query = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($user_id_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_row = $result->fetch_assoc();
    $user_id = $user_row['id'];
    $stmt->close();

    // Process file upload
    $meal_name = htmlspecialchars($_POST['meal_name']);
    $meal_description = htmlspecialchars($_POST['meal_description']);
    $calories = (int)$_POST['calories'];
    $status = "pending"; // Default status is pending for admin approval
    
    $target_dir = "uploads/meals/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["meal_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $uploadOk = 1;
    
    // Check if file was uploaded
    if(!isset($_FILES["meal_image"]) || $_FILES["meal_image"]["error"] !== UPLOAD_ERR_OK) {
        $share_message = "No file uploaded or upload error occurred.";
        $uploadOk = 0;
    }
    
    // Validate MIME type
    if ($uploadOk == 1) {
        $allowed_mime_types = ['image/jpeg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES["meal_image"]["tmp_name"]);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_mime_types)) {
            $share_message = "Invalid file type. Only JPG, JPEG, PNG images are allowed.";
            $uploadOk = 0;
        }
    }
    
    // Check if image file is an actual image using getimagesize
    if ($uploadOk == 1) {
        $check = getimagesize($_FILES["meal_image"]["tmp_name"]);
        if($check === false) {
            $share_message = "File is not a valid image.";
            $uploadOk = 0;
        }
    }
    
    // Check file size (5MB max)
    if ($uploadOk == 1 && $_FILES["meal_image"]["size"] > 5000000) {
        $share_message = "Sorry, your file is too large. Maximum size is 5MB.";
        $uploadOk = 0;
    }
    
    // Allow certain file formats (case-insensitive)
    if ($uploadOk == 1) {
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_extension, $allowed_extensions)) {
            $share_message = "Sorry, only JPG, JPEG, PNG files are allowed.";
            $uploadOk = 0;
        }
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["meal_image"]["tmp_name"], $target_file)) {
            // Insert into database
            $insert_query = "INSERT INTO meal_shares (user_id, meal_name, description, calories, image_path, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ississ", $user_id, $meal_name, $meal_description, $calories, $target_file, $status);
            
            if ($stmt->execute()) {
                $share_message = "Your meal has been shared and is pending approval!";
            } else {
                $share_message = "Sorry, there was an error uploading your meal.";
            }
            $stmt->close();
        } else {
            $share_message = "Sorry, there was an error uploading your file.";
        }
    }
}

// Get top users by meals logged
$top_meals_query = "SELECT username, COUNT(*) as meal_count, SUM(calories) as total_calories 
                    FROM meals 
                    GROUP BY username 
                    ORDER BY meal_count DESC 
                    LIMIT 3";
$top_meals_result = $conn->query($top_meals_query);
$top_meals_users = [];
if ($top_meals_result) {
    while ($row = $top_meals_result->fetch_assoc()) {
        $top_meals_users[] = $row;
    }
}

// Get top users by calories burned
$top_calories_query = "SELECT username, SUM(calories_burned) as total_burned, COUNT(*) as activity_count 
                       FROM activities 
                       GROUP BY username 
                       ORDER BY total_burned DESC 
                       LIMIT 3";
$top_calories_result = $conn->query($top_calories_query);
$top_calories_users = [];
if ($top_calories_result) {
    while ($row = $top_calories_result->fetch_assoc()) {
        $top_calories_users[] = $row;
    }
}

// Get recent community activities (combined meals and physical activities)
$recent_activity_query = "
    (SELECT username, 'meal' as type, meal_name as name, calories, created_at 
     FROM meals 
     ORDER BY created_at DESC 
     LIMIT 5)
    UNION ALL
    (SELECT username, 'activity' as type, activity_type as name, calories_burned as calories, created_at 
     FROM activities 
     ORDER BY created_at DESC 
     LIMIT 5)
    ORDER BY created_at DESC 
    LIMIT 10";
$recent_activity_result = $conn->query($recent_activity_query);
$recent_activities = [];
if ($recent_activity_result) {
    while ($row = $recent_activity_result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// Get total community stats
$community_stats_query = "
    SELECT 
        (SELECT COUNT(DISTINCT username) FROM users) as total_users,
        (SELECT COUNT(*) FROM meals) as total_meals,
        (SELECT SUM(calories) FROM meals) as total_calories_consumed,
        (SELECT COUNT(*) FROM activities) as total_activities,
        (SELECT SUM(calories_burned) FROM activities) as total_calories_burned
";
$community_stats_result = $conn->query($community_stats_query);
$community_stats = $community_stats_result->fetch_assoc();

// Get approved meal shares for carousel
$shared_meals_query = "SELECT ms.*, u.username 
                      FROM meal_shares ms
                      JOIN users u ON ms.user_id = u.id
                      WHERE ms.status = 'approved'
                      ORDER BY ms.created_at DESC
                      LIMIT 10";
$shared_meals_result = $conn->query($shared_meals_query);
$shared_meals = [];
if ($shared_meals_result) {
    while ($row = $shared_meals_result->fetch_assoc()) {
        $shared_meals[] = $row;
    }
}

// Get streak data for 7-Day Streak challenge
$streak_query = "SELECT COUNT(DISTINCT DATE(created_at)) as streak_days 
                FROM meals 
                WHERE username = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($streak_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$streak_result = $stmt->get_result();
$streak_data = $streak_result->fetch_assoc();
$streak_days = $streak_data['streak_days'];
$streak_percentage = ($streak_days / 7) * 100;
$stmt->close();

// Get calories burned for 1000 Calorie Club challenge
$calories_query = "SELECT SUM(calories_burned) as total_calories 
                  FROM activities 
                  WHERE username = ? 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($calories_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$calories_result = $stmt->get_result();
$calories_data = $calories_result->fetch_assoc();
$calories_burned = $calories_data['total_calories'] ?? 0;
$calories_percentage = ($calories_burned / 1000) * 100;
if($calories_percentage > 100) $calories_percentage = 100;
$stmt->close();

// Get variety of meals for Variety Master challenge
$variety_query = "SELECT COUNT(DISTINCT meal_name) as meal_variety 
                 FROM meals 
                 WHERE username = ?";
$stmt = $conn->prepare($variety_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$variety_result = $stmt->get_result();
$variety_data = $variety_result->fetch_assoc();
$meal_variety = $variety_data['meal_variety'];
$variety_percentage = ($meal_variety / 10) * 100;
if($variety_percentage > 100) $variety_percentage = 100;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Community | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/community.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>NutriTrack Community</h1>
    
    <div class="community-stats">
        <h2>Community Achievements</h2>
        <div class="stats-cards">
            <div class="stat-card">
                <i class='bx bx-user'></i>
                <span class="stat-number"><?php echo $community_stats['total_users'] ?? 0; ?></span>
                <span class="stat-label">Active Users</span>
            </div>
            <div class="stat-card">
                <i class='bx bx-food-menu'></i>
                <span class="stat-number"><?php echo $community_stats['total_meals'] ?? 0; ?></span>
                <span class="stat-label">Meals Tracked</span>
            </div>
            <div class="stat-card">
                <i class='bx bx-run'></i>
                <span class="stat-number"><?php echo $community_stats['total_activities'] ?? 0; ?></span>
                <span class="stat-label">Activities Logged</span>
            </div>
            <div class="stat-card">
                <i class='bx bx-trending-up'></i>
                <span class="stat-number"><?php echo number_format($community_stats['total_calories_burned'] ?? 0); ?></span>
                <span class="stat-label">Calories Burned</span>
            </div>
        </div>
    </div>

    <div class="rankings-section">
        <div class="ranking-column">
            <h2>Top Meal Trackers 🍽️</h2>
            <div class="rankings">
                <?php foreach ($top_meals_users as $index => $user): ?>
                    <div class="ranking-card rank-<?php echo $index + 1; ?>">
                        <div class="medal"><?php echo ($index == 0) ? "🥇" : (($index == 1) ? "🥈" : "🥉"); ?></div>
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                            <span class="details"><?php echo $user['meal_count']; ?> meals | <?php echo number_format($user['total_calories']); ?> calories</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($top_meals_users) == 0): ?>
                    <p class="no-data">No meal data available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="ranking-column">
            <h2>Top Calorie Burners 🔥</h2>
            <div class="rankings">
                <?php foreach ($top_calories_users as $index => $user): ?>
                    <div class="ranking-card rank-<?php echo $index + 1; ?>">
                        <div class="medal"><?php echo ($index == 0) ? "🥇" : (($index == 1) ? "🥈" : "🥉"); ?></div>
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                            <span class="details"><?php echo number_format($user['total_burned']); ?> calories | <?php echo $user['activity_count']; ?> activities</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($top_calories_users) == 0): ?>
                    <p class="no-data">No activity data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- New Meal Sharing Section -->
    <div class="meal-sharing-section">
        <h2>Community Meal Showcase</h2>
        
        <?php if (!empty($share_message)): ?>
            <div class="message <?php echo strpos($share_message, 'Sorry') !== false ? 'error' : 'success'; ?>">
                <?php echo $share_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="meal-carousel-container">
            <button class="carousel-btn prev-btn"><i class='bx bx-chevron-left'></i></button>
            
            <div class="meal-carousel">
                <?php if (count($shared_meals) > 0): ?>
                    <?php foreach ($shared_meals as $meal): ?>
                        <div class="meal-card">
                            <div class="meal-image">
                                <img src="<?php echo htmlspecialchars($meal['image_path']); ?>" alt="<?php echo htmlspecialchars($meal['meal_name']); ?>">
                            </div>
                            <div class="meal-info">
                                <h3><?php echo htmlspecialchars($meal['meal_name']); ?></h3>
                                <p class="meal-user">By <?php echo htmlspecialchars($meal['username']); ?></p>
                                <p class="meal-calories"><?php echo $meal['calories']; ?> calories</p>
                                <p class="meal-description"><?php echo htmlspecialchars($meal['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-meals">
                        <p>No meal shares yet. Be the first to share!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <button class="carousel-btn next-btn"><i class='bx bx-chevron-right'></i></button>
        </div>
        
        <div class="share-meal-form-toggle">
            <button id="showShareForm" class="btn">Share Your Meal</button>
        </div>
        
        <div id="shareMealForm" class="share-meal-form">
            <h3>Share Your Meal</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="meal_name">Meal Name:</label>
                    <input type="text" id="meal_name" name="meal_name" required>
                </div>
                
                <div class="form-group">
                    <label for="meal_description">Description:</label>
                    <textarea id="meal_description" name="meal_description" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="calories">Calories:</label>
                    <input type="number" id="calories" name="calories" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="meal_image">Upload Image:</label>
                    <input type="file" id="meal_image" name="meal_image" accept=".jpg, .jpeg, .png" required>
                    <p class="file-help">Max size: 5MB. Only JPG, JPEG, PNG files allowed.</p>
                </div>

                <?php echo csrf_field(); ?>
                
                <div class="form-actions">
                    <button type="button" id="cancelShare" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="share_meal" class="btn btn-submit">Share Meal</button>
                </div>
            </form>
        </div>

    <div class="recent-activity">
        <h2>Recent Community Activity</h2>
        <div class="activity-feed scrollable">
            <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-card">
                    <div class="activity-icon">
                        <?php if ($activity['type'] == 'meal'): ?>
                            <i class='bx bx-food-menu'></i>
                        <?php else: ?>
                            <i class='bx bx-run'></i>
                        <?php endif; ?>
                    </div>
                    <div class="activity-info">
                        <span class="username"><?php echo htmlspecialchars($activity['username']); ?></span>
                        <span class="action">
                            <?php if ($activity['type'] == 'meal'): ?>
                                logged a meal: <strong><?php echo htmlspecialchars($activity['name']); ?></strong> (<?php echo $activity['calories']; ?> kcal)
                            <?php else: ?>
                                completed: <strong><?php echo htmlspecialchars($activity['name']); ?></strong> (<?php echo $activity['calories']; ?> kcal burned)
                            <?php endif; ?>
                        </span>
                        <span class="timestamp"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($recent_activities) == 0): ?>
                <p class="no-data">No recent activity available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="motivation">
    <h2>Community Challenges</h2>
    <div class="challenge-cards">
    <div class="challenge-card">
        <div class="challenge-icon"><i class='bx bx-calendar-check'></i></div>
        <h3>7-Day Streak</h3>
        <p>Log your meals for 7 consecutive days to complete this challenge.</p>
        <div class="progress-container">
            <div class="progress-bar" style="width: 0%;" data-width="<?php echo $streak_percentage; ?>%"></div>
        </div>
        <div class="progress-text"><?php echo $streak_days; ?>/7 days completed</div>
        <?php if($streak_days >= 7): ?>
            <span class="status-tag complete">Complete</span>
        <?php else: ?>
            <span class="status-tag active">In Progress</span>
        <?php endif; ?>
        <a href="/nutrition_tracker/codes/php/meal_tracker.php" class="join-challenge-btn">View Details</a>
    </div>
    
    <div class="challenge-card">
        <div class="challenge-icon"><i class='bx bxs-flame'></i></div>
        <h3>1000 Calorie Club</h3>
        <p>Burn 1000 calories in a single week through tracked activities.</p>
        <div class="progress-container">
            <div class="progress-bar" style="width: 0%;" data-width="<?php echo $calories_percentage; ?>%"></div>
        </div>
        <div class="progress-text"><?php echo round($calories_burned); ?>/1000 calories</div>
        <?php if($calories_burned >= 1000): ?>
            <span class="status-tag complete">Complete</span>
        <?php else: ?>
            <span class="status-tag active">In Progress</span>
        <?php endif; ?>
        <a href="/nutrition_tracker/codes/php/activity_log.php" class="join-challenge-btn">View Details</a>
    </div>
    
    <div class="challenge-card">
        <div class="challenge-icon"><i class='bx bx-bowl-hot'></i></div>
        <h3>Variety Master</h3>
        <p>Log 10 different types of meals to earn this achievement.</p>
        <div class="progress-container">
            <div class="progress-bar" style="width: 0%;" data-width="<?php echo $variety_percentage; ?>%"></div>
        </div>
        <div class="progress-text"><?php echo $meal_variety; ?>/10 meal types</div>
        <?php if($meal_variety >= 10): ?>
            <span class="status-tag complete">Complete</span>
        <?php else: ?>
            <span class="status-tag active">In Progress</span>
        <?php endif; ?>
        <a href="/nutrition_tracker/codes/php/meal_tracker.php" class="join-challenge-btn">View Details</a>
    </div>
</div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="home.php" class="btn">Back to Home</a>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/community.js"></script>
</body>
</html>