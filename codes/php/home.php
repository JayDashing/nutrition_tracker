<?php
require_once 'init.php';

// 1. Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];
verify_csrf(); // Our bouncer at the door

// 2. Update last_login
$update_login_time = "UPDATE users SET last_login = NOW() WHERE username = ?";
$update_stmt = $conn->prepare($update_login_time);
$update_stmt->bind_param("s", $username);
$update_stmt->execute();
$update_stmt->close();

// 3. Get user information
$query = "SELECT email, profile_picture, last_login, created_at FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if ($user_data) {
    $userEmail = $user_data['email'];
    $profile_pic = $user_data['profile_picture'] ?: "/nutrition_tracker/codes/images/default_profile.png";
    $account_created = strtotime($user_data['created_at']);
} else {
    $userEmail = "user@example.com";
    $profile_pic = "/nutrition_tracker/codes/images/default_profile.png";
    $account_created = time();
}
$stmt->close();

// 4. Announcements logic
$announcements_result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$read_query = "SELECT announcement_id FROM announcement_reads WHERE username = ?";
$read_stmt = $conn->prepare($read_query);
$read_stmt->bind_param("s", $username);
$read_stmt->execute();
$read_res = $read_stmt->get_result();

$read_announcements = [];
while($row = $read_res->fetch_assoc()) {
    $read_announcements[] = $row['announcement_id'];
}
$read_stmt->close();

// Count unread (we don't close the connection yet!)
$unread_count = 0;
if ($announcements_result->num_rows > 0) {
    while($row = $announcements_result->fetch_assoc()) {
        if (!in_array($row['id'], $read_announcements)) $unread_count++;
    }
    $announcements_result->data_seek(0);
}

// 5. FITNESS DATA (Using the same $conn!)
// Fetch Daily Goal
$query_goal = "SELECT goal_amount FROM goals WHERE username = ? AND goal_type = 'daily' ORDER BY created_at DESC LIMIT 1";
$stmt_goal = $conn->prepare($query_goal);
$stmt_goal->bind_param("s", $username);
$stmt_goal->execute();
$calorieGoal = $stmt_goal->get_result()->fetch_assoc()['goal_amount'] ?? 2000; // Default to 2000
$stmt_goal->close();

// Calculate Calories Consumed Today
$sql_meals = "SELECT IFNULL(SUM(calories), 0) AS total FROM meals WHERE username = ? AND DATE(created_at) = CURDATE()";
$stmt_meals = $conn->prepare($sql_meals);
$stmt_meals->bind_param("s", $username);
$stmt_meals->execute();
$total_calories = $stmt_meals->get_result()->fetch_assoc()['total'];
$stmt_meals->close();

// Calculate Calories Burned Today
$sql_burned = "SELECT IFNULL(SUM(calories_burned), 0) AS total FROM activities WHERE username = ? AND DATE(created_at) = CURDATE()";
$stmt_burned = $conn->prepare($sql_burned);
$stmt_burned->bind_param("s", $username);
$stmt_burned->execute();
$total_burned = $stmt_burned->get_result()->fetch_assoc()['total'];
$stmt_burned->close();

// 6. WATER INTAKE (Now persistent from Database)
$sql_water = "SELECT glasses FROM water_intake WHERE username = ? AND DATE(created_at) = CURDATE()";
$stmt_water = $conn->prepare($sql_water);
$stmt_water->bind_param("s", $username);
$stmt_water->execute();
$waterIntake = $stmt_water->get_result()->fetch_assoc()['glasses'] ?? 0;
$stmt_water->close();

// Calculations
$net_calories = $total_calories - $total_burned;
$caloriePercentage = ($calorieGoal > 0) ? min(($net_calories / $calorieGoal) * 100, 100) : 0;
$waterGoal = 8;
$waterPercentage = min(($waterIntake / $waterGoal) * 100, 100);

// 7. Nutrition Tips
$nutrition_tips = [
    "An apple a day keeps the doctor away! 🍏",
    "Stay hydrated - Drink at least 8 glasses of water a day! 💧",
    "Add more fruits and vegetables to your diet for essential vitamins. 🥦",
    "Limit processed foods - choose whole, natural ingredients. 🌿",
    "Eat protein-rich foods to boost muscle health and metabolism. 💪",
    "Include healthy fats like avocados, nuts, and seeds. 🥑",
    "Balance your meals with carbs, proteins, and healthy fats. 🍽️",
    "Healthy eating is a journey, not a race. Take small steps every day! 🚶‍♂️",
    "Fuel your body with nutritious foods and feel the difference! ⚡",
    "Every bite is an opportunity to nourish your body and mind! 🧠"
];
$random_tip = $nutrition_tips[array_rand($nutrition_tips)];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>NutriTrack | Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/homes.css">
</head>
<body>
    <!-- Theme Mode Toggle -->
    <input type="checkbox" id="theme-toggle" class="theme-toggle">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-details">
            <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo">
        </div>
        
        <div class="user-profile">
            <div class="profile-img">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="">
            </div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
            <a href="profile.php" class="edit-profile-btn"><i class="fas fa-user-edit"></i> Edit Profile</a>
        </div>
        
        <ul class="nav-links">
            <li class="active">
                <a href="home.php">
                    <i class="fas fa-home"></i>
                    <span class="link-name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="meal_tracker.php">
                    <i class="fas fa-utensils"></i>
                    <span class="link-name">Meal Tracker</span>
                </a>
            </li>
            <li>
                <a href="health_goals.php">
                    <i class="fas fa-bullseye"></i>
                    <span class="link-name">Daily Calorie Goal</span>
                </a>
            </li>
            <li>
                <a href="activity_log.php">
                    <i class="fas fa-running"></i>
                    <span class="link-name">Activity Log</span>
                </a>
            </li>
            <li>
                <a href="meal_history.php">
                    <i class="fas fa-history"></i>
                    <span class="link-name">Meal History</span>
                </a>
            </li>
            <li>
                <a href="perinsights.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="link-name">Insights</span>
                </a>
            </li>
            <li>
                <a href="motivation.php">
                    <i class="fas fa-heart"></i>
                    <span class="link-name">Motivation</span>
                </a>
            </li>
            <li>
                <a href="recipes.php">
                    <i class="fas fa-book-open"></i>
                    <span class="link-name">Recipes</span>
                </a>
            </li>
            <li>
                <a href="nutrifacts.php">
                    <i class="fas fa-info-circle"></i>
                    <span class="link-name">Nutrition Facts</span>
                </a>
            </li>
            <li>
                <a href="community.php">
                    <i class="fas fa-users"></i>
                    <span class="link-name">Community</span>
                </a>
            </li>
            <li>
                <a href="challenges.php">
                    <i class="fas fa-trophy"></i>
                    <span class="link-name">Challenges</span>
                </a>
            </li>
            <li>
                <a href="report.php">
                    <i class="fas fa-file-alt"></i>
                    <span class="link-name">Feedback</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-bottom">
            <div class="theme-switch">
                <label for="theme-toggle" class="switch">
                    <span class="mode-text">Light Mode</span>
                    <div class="slider"></div>
                </label>
            </div>
            
            <div class="logout-section">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-name">Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="welcome-text">
                <?php
                function getWelcomeMessage($username) {
                    if (!isset($_SESSION['user_created_at'])) {
                        $_SESSION['user_created_at'] = time();
                        return "Welcome, " . htmlspecialchars($username) . "!";
                    } else {
                        $userCreatedAt = $_SESSION['user_created_at'];
                        $oneDayInSeconds = 86400;
                        if (time() - $userCreatedAt >= $oneDayInSeconds) {
                            return "Welcome back, " . htmlspecialchars($username) . "!";
                        } else {
                            return "Welcome, " . htmlspecialchars($username) . "!";
                        }
                    }
                }
                ?>
                <h1 style="text-align: center;"><?php echo getWelcomeMessage($username); ?></h1>
                <p style="text-align: center;">Let's continue your journey to a healthier lifestyle.</p>
            </div>
            <div class="header-icons">
                <div class="notification" id="notification-toggle">
                    <i class="fas fa-bell" style="margin-right: 10px;"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="badge" style="margin-right: 9px;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            <!-- Progress Summary Section -->
            <section class="progress-summary">
                <h2>Today's Progress</h2>
                <div class="progress-cards">
                    <div class="progress-card">
                        <div class="progress-info">
                            <i class="fas fa-calendar-day"></i>
                            <div>
                                <h3>Daily Calorie Goal</h3>
                                <!-- Display net calories consumed versus the goal -->
                                <p><?php echo $net_calories; ?> / <?php echo $calorieGoal; ?> kcal</p>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <!-- Adjust the progress bar width based on calculated percentage -->
                            <div class="progress" style="width: <?php echo $caloriePercentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-card">
                        <div class="progress-info">
                            <i class="fas fa-tint"></i>
                            <div>
                                <h3>Water Intake</h3>
                                <p id="water-display"><?php echo $waterIntake; ?> / <?php echo $waterGoal; ?> glasses</p>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" id="water-progress" style="width: <?php echo $waterPercentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-card" id="challenge-card">
                        <!-- This card will be populated with challenge data via JavaScript -->
                        <div class="progress-info">
                            <i class="fas fa-trophy"></i>
                            <div>
                                <h3 id="challenge-title">Active Challenge</h3>
                                <p id="challenge-progress">No active challenges</p>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" id="challenge-progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions Section -->
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                  <a href="meal_tracker.php" class="action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add Meal</span>
                 </a>
                 <a href="activity_log.php" class="action-btn">
                    <i class="fas fa-dumbbell"></i>
                    <span>Log Activity</span>
                 </a>
                 <a href="health_goals.php" class="action-btn">
                     <i class="fas fa-edit"></i>
                    <span>Update Goals</span>
                 </a>
                <a href="#" class="action-btn" id="add-water">
                     <i class="fas fa-tint"></i>
                    <span>Add Water</span>
                 </a>
                <a href="#" class="action-btn" id="delete-water">
                 <i class="fas fa-minus"></i>
                 <span>Remove Water</span>
                </a>
             </div>
        </section>

            <!-- Features Card Grid -->
            <section class="features-section">
                <h2>Explore Features</h2>
                <div class="card">
                    <div class="feature-card">
                        <i class="fas fa-utensils fa-2x"></i>
                        <h3>Track Your Meals</h3>
                        <p>Monitor your daily calorie intake and maintain a balanced diet effortlessly.</p>
                        <a href="meal_tracker.php" class="btn">Track Meals</a>
                    </div>

                    <div class="feature-card">
                        <i class="fas fa-chart-line fa-2x"></i>
                        <h3>Personalized Insights</h3>
                        <p>Receive personalized nutritional insights to achieve your health goals.</p>
                        <a href="perinsights.php" class="btn">View Insights</a>
                    </div>

                    <div class="feature-card">
                        <i class="fas fa-heart fa-2x"></i>
                        <h3>Stay Motivated</h3>
                        <p>Get daily tips and motivation to stay on track with your wellness journey.</p>
                        <a href="motivation.php" class="btn">Get Inspired</a>
                    </div>

                    <div class="feature-card">
                        <i class="fas fa-history fa-2x"></i>
                        <h3>Meal History</h3>
                        <p>Review your previous meals and track your eating habits over time.</p>
                        <a href="meal_history.php" class="btn">View History</a>
                    </div>
                </div>
            </section>
            
            <!-- My Challenges Section -->
            <section class="my-challenges">
                <h2>My Nutrition Challenges</h2>
                <div class="challenges-container" id="joined-challenges">
                    <!-- Joined challenges will be inserted here via JavaScript -->
                    <p class="no-challenges">You haven't joined any challenges yet. <a href="challenges.php">Explore challenges</a></p>
                </div>
            </section>
            
            <!-- Nutrition Tip Box -->
            <div class="tip-box">
                <div class="tip-content">
                    <i class="fas fa-lightbulb tip-icon"></i>
                    <div>
                        <h3>Today's Nutrition Tip</h3>
                        <p><?php echo $random_tip; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Announcements Sidebar -->
    <div class="announcements-sidebar" id="announcements-sidebar">
        <div class="announcements-header">
            <h3>Admin Announcements</h3>
            <button id="close-announcements" class="close-announcements"><i class="fas fa-times"></i></button>
        </div>
        <div class="announcements-list">
            <?php if ($announcements_result && $announcements_result->num_rows > 0): ?>
                <?php while($row = $announcements_result->fetch_assoc()): ?>
                    <div class="announcement-item importance-<?php echo $row['importance']; ?>">
                        <div class="title"><?php echo htmlspecialchars($row['title']); ?></div>
                        <div class="meta">
                            <span class="created-by">By: <?php echo htmlspecialchars($row['created_by']); ?></span>
                            <span class="date"><?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?></span>
                            <span class="importance">
                                <?php 
                                switch($row['importance']) {
                                    case 1: echo "Low Importance"; break;
                                    case 2: echo "Medium Importance"; break;
                                    case 3: echo "High Importance"; break;
                                }
                                ?>
                            </span>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($row['announcement_text'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-announcements">
                    <p>No announcements have been published yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Overlay for announcements sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Save Water Intake via AJAX -->
    <form id="water-form" style="display: none">
        <input type="hidden" name="water_count" id="water-count" value="<?php echo $waterIntake; ?>">
        <?php echo csrf_field(); ?>
    </form>
    <script src="/nutrition_tracker/codes/js/home.js"></script>
</body>
</html>