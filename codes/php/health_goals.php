<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

// Database connection
require_once 'db.php';

// Goal submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['goal_amount'], $_POST['goal_name'])) {
    $goal_type = 'daily'; // Only daily goals now
    $goal_amount = (int)$_POST['goal_amount'];
    $goal_name = $conn->real_escape_string($_POST['goal_name']);

    // Check if ANY daily goal already exists for this user (not just with the same name)
    $check_goal = "SELECT id FROM goals WHERE username = ? AND goal_type = 'daily' LIMIT 1";
    $stmt = $conn->prepare($check_goal);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Goal exists, update it
        $goal_row = $result->fetch_assoc();
        $goal_id = $goal_row['id'];
        
        $update_goal = "UPDATE goals SET goal_name = ?, goal_amount = ?, target_value = ? WHERE id = ?";
        $stmt = $conn->prepare($update_goal);
        $stmt->bind_param("siii", $goal_name, $goal_amount, $goal_amount, $goal_id);
        
        if ($stmt->execute()) {
            $success_message = "Daily calorie goal successfully updated!";
        } else {
            $error_message = "Error updating goal: " . $conn->error;
        }
    } else {
        // Goal doesn't exist, insert new
        $insert_goal = "INSERT INTO goals (username, goal_type, goal_name, goal_amount, target_value, progress_value) VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($insert_goal);
        $stmt->bind_param("sssii", $username, $goal_type, $goal_name, $goal_amount, $goal_amount);
        
        if ($stmt->execute()) {
            $success_message = "Daily calorie goal successfully added!";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
    
    $stmt->close();
    
    // Prevent form resubmission on refresh
    header("Location: ".$_SERVER['PHP_SELF']."?success=".urlencode($success_message));
    exit();
}

// Display success message if it exists in URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Goal deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_goal'])) {
    $goal_id = (int)$_POST['delete_goal'];
    
    $delete_goal = "DELETE FROM goals WHERE id = ? AND username = ?";
    $stmt = $conn->prepare($delete_goal);
    $stmt->bind_param("is", $goal_id, $username);

    if ($stmt->execute()) {
        $success_message = "Goal successfully deleted!";
        
        // Prevent form resubmission on refresh
        header("Location: ".$_SERVER['PHP_SELF']."?success=".urlencode($success_message));
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $stmt->close();
}

// Fetch user's goals
$fetch_goals = "SELECT id, goal_name, goal_amount, target_value, progress_value, created_at FROM goals WHERE username = ? AND goal_type = 'daily'";
$stmt = $conn->prepare($fetch_goals);
$stmt->bind_param("s", $username);
$stmt->execute();
$goals_result = $stmt->get_result();
$daily_goals = $goals_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process daily goals
foreach ($daily_goals as &$goal) {
    $goal_amount = $goal['goal_amount'];
    $goal_id = $goal['id'];

    // Sum of meals for TODAY
    $sql_meals = "
        SELECT IFNULL(SUM(calories), 0) AS total_calories 
        FROM meals 
        WHERE username = ? 
          AND DATE(created_at) = CURDATE()
    ";
    $stmt = $conn->prepare($sql_meals);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $total_calories = $row['total_calories'] ?? 0;
    $stmt->close();

    // Sum of activities for TODAY
    $sql_activities = "
        SELECT IFNULL(SUM(calories_burned), 0) AS total_burned
        FROM activities
        WHERE username = ?
          AND DATE(created_at) = CURDATE()
    ";
    $stmt = $conn->prepare($sql_activities);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $total_burned = $row['total_burned'] ?? 0;
    $stmt->close();

    // Calculate net calories for daily
    $net_calories = $total_calories - $total_burned;
    $progress = 0;
    
    if ($goal_amount > 0) {
        $progress = ($net_calories / $goal_amount) * 100;
    }

    // Clamp progress to [0, 100] for the bar
    $display_progress = $progress;
    if ($display_progress < 0) $display_progress = 0;
    if ($display_progress > 100) $display_progress = 100;

    // Store in array
    $goal['net_calories'] = $net_calories;
    $goal['progress'] = $display_progress;
    
    // Update the database with current progress
    $update_progress = "UPDATE goals SET progress_value = ? WHERE id = ?";
    $stmt = $conn->prepare($update_progress);
    $stmt->bind_param("di", $net_calories, $goal_id);
    $stmt->execute();
    $stmt->close();
    
    // Calculate remaining calories
    $goal['remaining_calories'] = $goal_amount - $net_calories;
}

$conn->close();

// Check if user already has a daily goal
$has_daily_goal = count($daily_goals) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Calorie Goal | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/health_goal.css">
</head>
<body>
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Daily Calorie Goal</h1>

    <?php if (isset($success_message)) echo "<p class='message'>$success_message</p>"; ?>
    <?php if (isset($error_message)) echo "<p class='message error'>$error_message</p>"; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="goal-form">
        <div class="input-group">
            <input type="text" name="goal_name" placeholder="Goal Name (e.g., Weight Loss, Maintenance)" required>
            <input type="number" name="goal_amount" placeholder="Daily Calorie Target" required>
        </div>
        <button type="submit" class="btn">
            <?php echo $has_daily_goal ? 'Update Calorie Goal' : 'Set Calorie Goal'; ?>
        </button>
    </form>

    <div class="goals-section">
        <!-- Daily Goals Section -->
        <div class="goal-category">
            <h2><i class='bx bx-calendar-day'></i> Your Daily Calorie Target</h2>
            <div class="goals-container">
                <?php if (count($daily_goals) > 0): ?>
                    <?php foreach ($daily_goals as $goal): ?>
                        <div class="goal-entry">
                            <div class="goal-header">
                                <strong><?php echo htmlspecialchars($goal['goal_name']); ?></strong>
                                <span class="goal-target"><?php echo htmlspecialchars($goal['goal_amount']); ?> kcal</span>
                            </div>
                            
                            <div class="goal-details">
                                <div class="goal-status">
                                    <div class="stat">
                                        <span class="stat-label">Consumed:</span>
                                        <span class="stat-value"><?php echo $goal['net_calories'] ?? 0; ?> kcal</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Remaining:</span>
                                        <span class="stat-value <?php echo $goal['remaining_calories'] < 0 ? 'over-limit' : ''; ?>">
                                            <?php echo $goal['remaining_calories'] > 0 ? $goal['remaining_calories'] : 'Exceeded by ' . abs($goal['remaining_calories']); ?> kcal
                                        </span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Created:</span>
                                        <span class="stat-date"><?php echo date('M d, Y', strtotime($goal['created_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar with percentage -->
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress <?php echo $goal['progress'] > 90 ? 'near-limit' : ''; ?>" style="width: <?php echo isset($goal['progress']) ? $goal['progress'] : 0; ?>%">
                                            <span class="progress-text"><?php echo round($goal['progress'] ?? 0); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="delete_goal" value="<?php echo $goal['id']; ?>">
                                    <button type="submit" class="btn-delete"><i class='bx bx-trash'></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-goals">
                        <i class='bx bx-calendar-x'></i>
                        <p>No calorie goal set yet. Add your daily goal above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calorie Goal Guides Section -->
        <div class="goal-category guides-section">
            <h2><i class='bx bx-bulb'></i> Daily Guides Based on Your Progress</h2>
            
            <?php if (count($daily_goals) > 0): ?>
                <div class="guides-container">
                    <?php foreach ($daily_goals as $goal): ?>
                        <div class="guide-entry">
                            <div class="guide-header">
                                <strong>Recommendations for: <?php echo htmlspecialchars($goal['goal_name']); ?></strong>
                            </div>
                            
                            <div class="guide-content">
                                <?php 
                                $progress = $goal['progress']; 
                                $remaining = $goal['remaining_calories'];
                                ?>
                                
                                <?php if ($progress <= 25): ?>
                                <div class="guide-item">
                                    <i class='bx bx-food-menu'></i>
                                    <div class="guide-text">
                                        <h4>Morning Status</h4>
                                        <p>You've only used <?php echo round($progress); ?>% of your daily calories. Perfect time for a balanced meal!</p>
                                    </div>
                                </div>
                                
                                <?php elseif ($progress <= 50): ?>
                                <div class="guide-item">
                                    <i class='bx bx-time-five'></i>
                                    <div class="guide-text">
                                        <h4>Midday Check</h4>
                                        <p>You're at <?php echo round($progress); ?>% of your daily goal. You have <?php echo $remaining; ?> calories remaining for the day.</p>
                                    </div>
                                </div>
                                
                                <?php elseif ($progress <= 75): ?>
                                <div class="guide-item">
                                    <i class='bx bx-restaurant'></i>
                                    <div class="guide-text">
                                        <h4>Afternoon Update</h4>
                                        <p>You've used <?php echo round($progress); ?>% of your daily calories. Consider lighter options for dinner.</p>
                                    </div>
                                </div>
                                
                                <?php elseif ($progress <= 90): ?>
                                <div class="guide-item">
                                    <i class='bx bx-notification'></i>
                                    <div class="guide-text">
                                        <h4>Evening Alert</h4>
                                        <p>You're at <?php echo round($progress); ?>% of your daily goal with only <?php echo $remaining; ?> calories remaining. Choose wisely!</p>
                                    </div>
                                </div>
                                
                                <?php elseif ($progress <= 100): ?>
                                <div class="guide-item">
                                    <i class='bx bx-check-circle'></i>
                                    <div class="guide-text">
                                        <h4>Goal Reached</h4>
                                        <p>You've reached <?php echo round($progress); ?>% of your calorie goal. Try to maintain this level for the rest of the day.</p>
                                    </div>
                                </div>
                                
                                <?php else: ?>
                                <div class="guide-item warning">
                                    <i class='bx bx-error-circle'></i>
                                    <div class="guide-text">
                                        <h4>Goal Exceeded</h4>
                                        <p>You've exceeded your calorie goal by <?php echo abs($remaining); ?> calories. Consider additional activity to balance.</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Activity Recommendation based on progress -->
<div class="guide-item activity">
    <i class='bx bx-run'></i>
    <div class="guide-text">
        <h4>Activity Recommendation</h4>
        <?php if ($progress <= 25): ?>
            <p>You've consumed only a small portion of your calories. Consider a light 15-minute stretching session to energize your body.</p>
        <?php elseif ($progress <= 50): ?>
            <p>You're halfway through your calorie goal. A 20-minute moderate walk (burning ~100 calories) would help maintain your metabolism.</p>
        <?php elseif ($progress <= 75): ?>
            <p>You've used <?php echo round($progress); ?>% of your calories. A 30-minute bike ride or swimming session would be beneficial at this point.</p>
        <?php elseif ($progress <= 90): ?>
            <p>You're approaching your daily limit. Consider a 25-minute HIIT workout to boost metabolism and burn ~250 calories.</p>
        <?php elseif ($progress <= 100): ?>
            <p>You've reached your calorie goal for the day. A 40-minute strength training session would help build muscle and improve metabolic rate.</p>
        <?php else: ?>
            <p>To burn the excess <?php echo abs($remaining); ?> calories, try a 
            <?php 
            $minutes = ceil(abs($remaining) / 10); // Approximate 10 calories burned per minute of exercise
            echo $minutes; 
            ?>-minute <?php echo $minutes > 30 ? 'jog or high-intensity workout' : 'brisk walk'; ?>.</p>
        <?php endif; ?>
    </div>
</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-goals">
                    <i class='bx bx-message-alt-detail'></i>
                    <p>Set a calorie goal to see personalized recommendations.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="home.php" class="btn">Back to Home</a>
    </div>
</div>
</body>
</html>