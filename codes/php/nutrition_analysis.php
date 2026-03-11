<?php
require_once 'init.php';
// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

// Get user stats
$user_count_query = "SELECT COUNT(*) as total_users FROM users";
$user_count_result = $conn->query($user_count_query);
$user_count = 0;
if ($user_count_result) {
    $user_count = $user_count_result->fetch_assoc()['total_users'];
}

// Get all users
$users_query = "SELECT username FROM users ORDER BY username";
$users_result = $conn->query($users_query);
$users = [];
if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row['username'];
    }
}

// Initialize selected user
$selected_user = isset($_GET['user']) && in_array($_GET['user'], $users) ? $_GET['user'] : (count($users) > 0 ? $users[0] : '');

// Get user's most tracked foods
$user_foods_query = "SELECT f.food_name, COUNT(m.food_id) as count, 
                    f.calories_per_100g, f.protein, f.carbs, f.fat
                    FROM meals m
                    JOIN food_database f ON m.food_id = f.id
                    WHERE m.username = ?
                    GROUP BY m.food_id
                    ORDER BY count DESC
                    LIMIT 5";
$user_foods_stmt = $conn->prepare($user_foods_query);
$user_foods_stmt->bind_param("s", $selected_user);
$user_foods_stmt->execute();
$user_foods_result = $user_foods_stmt->get_result();

// Get user's daily calorie intake
$calorie_intake_query = "SELECT DATE(m.created_at) as date, 
                       SUM(f.calories_per_100g * m.portion_size / 100) as daily_calories,
                       SUM(f.protein * m.portion_size / 100) as daily_protein,
                       SUM(f.carbs * m.portion_size / 100) as daily_carbs,
                       SUM(f.fat * m.portion_size / 100) as daily_fat
                       FROM meals m
                       JOIN food_database f ON m.food_id = f.id
                       WHERE m.username = ?
                       GROUP BY DATE(m.created_at)
                       ORDER BY DATE(m.created_at) DESC
                       LIMIT 7";
$calorie_intake_stmt = $conn->prepare($calorie_intake_query);
$calorie_intake_stmt->bind_param("s", $selected_user);
$calorie_intake_stmt->execute();
$calorie_intake_result = $calorie_intake_stmt->get_result();

// Calculate user's averages
$user_averages_query = "SELECT 
                      AVG(daily_calories) as avg_calories,
                      AVG(daily_protein) as avg_protein,
                      AVG(daily_carbs) as avg_carbs,
                      AVG(daily_fat) as avg_fat,
                      COUNT(*) as days_tracked
                      FROM (
                          SELECT 
                          DATE(m.created_at) as date,
                          SUM(f.calories_per_100g * m.portion_size / 100) as daily_calories,
                          SUM(f.protein * m.portion_size / 100) as daily_protein,
                          SUM(f.carbs * m.portion_size / 100) as daily_carbs,
                          SUM(f.fat * m.portion_size / 100) as daily_fat
                          FROM meals m
                          JOIN food_database f ON m.food_id = f.id
                          WHERE m.username = ?
                          GROUP BY DATE(m.created_at)
                      ) as daily_totals";
$user_averages_stmt = $conn->prepare($user_averages_query);
$user_averages_stmt->bind_param("s", $selected_user);
$user_averages_stmt->execute();
$user_averages_result = $user_averages_stmt->get_result();
$user_averages = $user_averages_result->fetch_assoc();

// Get user's activities
$activity_query = "SELECT activity_type, 
                  AVG(calories_burned) as avg_calories_burned,
                  AVG(duration) as avg_duration,
                  COUNT(*) as count
                  FROM activities
                  WHERE username = ?
                  GROUP BY activity_type
                  ORDER BY count DESC";
$activity_stmt = $conn->prepare($activity_query);
$activity_stmt->bind_param("s", $selected_user);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();

// Get user's goals
$goal_query = "SELECT id, goal_type, goal_name, goal_amount, target_value, progress_value, 
              created_at
              FROM goals
              WHERE username = ?
              ORDER BY created_at DESC";
$goal_stmt = $conn->prepare($goal_query);
$goal_stmt->bind_param("s", $selected_user);
$goal_stmt->execute();
$goal_result = $goal_stmt->get_result();

// Total calories logged and total days for selected user
$total_stats_query = "SELECT 
                    SUM(f.calories_per_100g * m.portion_size / 100) as total_calories,
                    COUNT(DISTINCT DATE(m.created_at)) as total_days
                    FROM meals m
                    JOIN food_database f ON m.food_id = f.id
                    WHERE m.username = ?";
$total_stats_stmt = $conn->prepare($total_stats_query);
$total_stats_stmt->bind_param("s", $selected_user);
$total_stats_stmt->execute();
$total_stats_result = $total_stats_stmt->get_result();
$total_stats = $total_stats_result->fetch_assoc();

// Note: Don't close connection yet, we'll need it for the goal progress calculations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>User Nutrition Analysis | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/nutrition_analysis.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>User Nutrition Analysis</h1>
    
    <div class="user-selector">
        <form method="GET" action="">
            <label for="user-select">Select User:</label>
            <select id="user-select" name="user" onchange="this.form.submit()">
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user); ?>" <?php echo ($user === $selected_user) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <div class="report-stats">
        <p>Analyzing data for: <strong><?php echo htmlspecialchars($selected_user); ?></strong> | Last updated: <strong><?php echo date('M d, Y h:i A'); ?></strong></p>
    </div>
    
    <!-- User Summary Section -->
    <div class="analysis-section">
        <h2>Nutrition Summary</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Average Daily Calories</div>
                <div class="stat-value"><?php echo round($user_averages['avg_calories'] ?? 0, 0); ?></div>
                <div class="stat-info">Average calories consumed per day</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Total Calories Tracked</div>
                <div class="stat-value"><?php echo number_format($total_stats['total_calories'] ?? 0, 0); ?></div>
                <div class="stat-info">Total calories logged across <?php echo $total_stats['total_days'] ?? 0; ?> days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Average Macros (Daily)</div>
                <div class="stat-value stat-macros">
                    <span class="macro protein"><?php echo round($user_averages['avg_protein'] ?? 0, 1); ?>g P</span>
                    <span class="macro carbs"><?php echo round($user_averages['avg_carbs'] ?? 0, 1); ?>g C</span>
                    <span class="macro fat"><?php echo round($user_averages['avg_fat'] ?? 0, 1); ?>g F</span>
                </div>
                <div class="stat-info">Protein, Carbs, Fat average per day</div>
            </div>
        </div>
    </div>
    
    <!-- Most Tracked Foods Section -->
    <div class="analysis-section">
        <h2>Most Tracked Foods</h2>
        <?php if ($user_foods_result && $user_foods_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Food</th>
                        <th>Times Tracked</th>
                        <th>Calories/100g</th>
                        <th>Protein (g)</th>
                        <th>Carbs (g)</th>
                        <th>Fat (g)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $user_foods_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['food_name']); ?></td>
                            <td><?php echo $row['count']; ?></td>
                            <td><?php echo round($row['calories_per_100g'], 1); ?></td>
                            <td><?php echo round($row['protein'], 1); ?></td>
                            <td><?php echo round($row['carbs'], 1); ?></td>
                            <td><?php echo round($row['fat'], 1); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No food tracking data available for this user.</div>
        <?php endif; ?>
    </div>
    
    <!-- Daily Intake Section -->
    <div class="analysis-section">
        <h2>Recent Daily Intake</h2>
        <?php if ($calorie_intake_result && $calorie_intake_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Calories</th>
                        <th>Protein (g)</th>
                        <th>Carbs (g)</th>
                        <th>Fat (g)</th>
                        <th>Macro Ratio (P/C/F)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $calorie_intake_result->fetch_assoc()): 
                        $total_macros = $row['daily_protein'] + $row['daily_carbs'] + $row['daily_fat'];
                        $protein_pct = $total_macros > 0 ? round(($row['daily_protein'] / $total_macros) * 100, 0) : 0;
                        $carbs_pct = $total_macros > 0 ? round(($row['daily_carbs'] / $total_macros) * 100, 0) : 0;
                        $fat_pct = $total_macros > 0 ? round(($row['daily_fat'] / $total_macros) * 100, 0) : 0;
                    ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo round($row['daily_calories'], 0); ?></td>
                            <td><?php echo round($row['daily_protein'], 1); ?></td>
                            <td><?php echo round($row['daily_carbs'], 1); ?></td>
                            <td><?php echo round($row['daily_fat'], 1); ?></td>
                            <td>
                                <div class="macro-ratio">
                                    <div class="macro-bar protein" style="width: <?php echo $protein_pct; ?>%"></div>
                                    <div class="macro-bar carbs" style="width: <?php echo $carbs_pct; ?>%"></div>
                                    <div class="macro-bar fat" style="width: <?php echo $fat_pct; ?>%"></div>
                                </div>
                                <?php echo $protein_pct; ?>% / <?php echo $carbs_pct; ?>% / <?php echo $fat_pct; ?>%
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No daily intake data available for this user.</div>
        <?php endif; ?>
    </div>
    
    <!-- Activity Analysis Section -->
    <div class="analysis-section">
        <h2>Activity & Exercise Analysis</h2>
        <?php if ($activity_result && $activity_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Activity Type</th>
                        <th>Average Duration (min)</th>
                        <th>Average Calories Burned</th>
                        <th>Times Tracked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $activity_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['activity_type']); ?></td>
                            <td><?php echo round($row['avg_duration'], 1); ?></td>
                            <td><?php echo round($row['avg_calories_burned'], 1); ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No activity data available for this user.</div>
        <?php endif; ?>
    </div>
    
<!-- Goal Progress Section - UPDATED to match health_goals.php -->
<div class="analysis-section">
    <h2>Goals & Progress</h2>
    <?php if ($goal_result && $goal_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Goal Name</th>
                    <th>Calorie Target</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                    <th>Progress Percentage</th>
                    <th>Created On</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $goal_result->fetch_assoc()): 
                    // Get consumed calories directly from progress_value
                    $consumed = $row['progress_value'];
                    
                    // Calculate remaining calories
                    $remaining_calories = $row['goal_amount'] - $consumed;
                    
                    // Calculate progress percentage
                    $progress_percentage = $row['goal_amount'] > 0 ? ($consumed / $row['goal_amount']) * 100 : 0;
                    
                    // Clamp progress to [0, 100] for the display bar
                    $display_progress = $progress_percentage;
                    if ($display_progress < 0) $display_progress = 0;
                    if ($display_progress > 100) $display_progress = 100;
                    
                    $progress_percent = round($display_progress, 1);
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['goal_name']); ?></td>
                        <td><?php echo $row['goal_amount']; ?> kcal</td>
                        <td><?php echo round($consumed, 0); ?> kcal</td>
                        <td class="<?php echo $remaining_calories < 0 ? 'over-limit' : ''; ?>">
                            <?php echo $remaining_calories > 0 ? $remaining_calories : 'Exceeded by ' . abs($remaining_calories); ?> kcal
                        </td>
                        <td>
                            <div class="progress-bar-container">
                                <div class="progress-bar <?php echo $progress_percent > 90 ? 'near-limit' : ''; ?>" style="width: <?php echo min(100, $progress_percent); ?>%"></div>
                            </div>
                            <?php echo $progress_percent; ?>%
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No goal data available for this user.</div>
    <?php endif; ?>
</div>
    
    <div class="button-container">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
?>
</body>
</html>