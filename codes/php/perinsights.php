<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

// Logged-in user's name
$username = $_SESSION['username'];

// Database connection
require_once 'db.php';

// User's meal data
$meals_query = "SELECT meal_name, calories, created_at FROM meals WHERE username = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($meals_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$meals = [];
$total_calories = 0;
$meal_counts = [];
$dates = [];

while ($row = $result->fetch_assoc()) {
    $meals[] = $row;
    $total_calories += $row['calories'];
    
    $meal_name = $row['meal_name'];
    if (!isset($meal_counts[$meal_name])) {
        $meal_counts[$meal_name] = 0;
    }
    $meal_counts[$meal_name]++;
    
    // Extract date for tracking days
    $date = date('Y-m-d', strtotime($row['created_at']));
    $dates[$date] = true;
}

// Count unique dates as tracking days
$tracking_days = count($dates);

$stmt->close();

// Get highest calorie meal
$highest_calorie_query = "SELECT meal_name, calories FROM meals WHERE username = ? ORDER BY calories DESC LIMIT 1";
$stmt = $conn->prepare($highest_calorie_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$highest_calorie_result = $stmt->get_result();
$highest_calorie_meal = $highest_calorie_result->fetch_assoc();
$stmt->close();

$conn->close();

// Identify most frequent meal
$most_frequent_meal = !empty($meal_counts) ? array_keys($meal_counts, max($meal_counts))[0] : 'None';
$most_frequent_count = !empty($meal_counts) ? max($meal_counts) : 0;

// Average calories
$average_calories = count($meals) > 0 ? round($total_calories / count($meals), 2) : 0;

// Data for charts
$meal_labels = json_encode(array_keys($meal_counts));
$meal_values = json_encode(array_values($meal_counts));
$calorie_labels = json_encode(['Total Calories Consumed', 'Average Calories Per Meal']);
$calorie_values = json_encode([$total_calories, $average_calories]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insights | NutriTrack</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/perinsights.css">
</head>
<body>
    <div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
    </div>

    <div class="container">
        <h1>Personalized Insights</h1>
        
        <div class="charts-container">
            <div class="chart-wrapper">
                <canvas id="mealChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <canvas id="calorieChart"></canvas>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h2>Tracking Streak</h2>
                    <p><?php echo $tracking_days; ?> day<?php echo $tracking_days != 1 ? 's' : ''; ?></p>
                    <div class="stat-desc">Days you've logged meals</div>
                </div>
            </div>
            
            <div class="stat">
                <div class="stat-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-content">
                    <h2>Favorite Meal</h2>
                    <p><?php echo htmlspecialchars($most_frequent_meal); ?></p>
                    <div class="stat-desc">Logged <?php echo $most_frequent_count; ?> times</div>
                </div>
            </div>
            
            <div class="stat">
                <div class="stat-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-content">
                    <h2>Highest Calorie Meal</h2>
                    <p><?php echo isset($highest_calorie_meal['meal_name']) ? htmlspecialchars($highest_calorie_meal['meal_name']) : 'None'; ?></p>
                    <div class="stat-desc"><?php echo isset($highest_calorie_meal['calories']) ? $highest_calorie_meal['calories'] . ' kcal' : '0 kcal'; ?></div>
                </div>
            </div>
            
            <div class="stat">
                <div class="stat-icon">
                    <i class="fas fa-fire-alt"></i>
                </div>
                <div class="stat-content">
                    <h2>Daily Average</h2>
                    <p><?php echo $tracking_days > 0 ? round($total_calories / $tracking_days) : 0; ?> kcal</p>
                    <div class="stat-desc">Average calories per day</div>
                </div>
            </div>
        </div>

        <a href="home.php" class="btn">Back to Home</a>
    </div>

    <script>
        new Chart(document.getElementById('mealChart'), {
            type: 'pie',
            data: { labels: <?php echo $meal_labels; ?>, datasets: [{ data: <?php echo $meal_values; ?>, backgroundColor: ['#2e7d32', '#66bb6a', '#43a047', '#81c784', '#a5d6a7', '#c8e6c9'] }] },
        });

        new Chart(document.getElementById('calorieChart'), {
            type: 'doughnut',
            data: { labels: <?php echo $calorie_labels; ?>, datasets: [{ data: <?php echo $calorie_values; ?>, backgroundColor: ['#66bb6a', '#43a047'] }] },
        });
    </script>
</body>
</html>