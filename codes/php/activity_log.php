<?php
require_once 'init.php';

if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

verify_csrf();

/**
 * Get calories burned from Nutritionix API
 *
 * @param string $activity_type The exercise type
 * @param int $duration Duration in minutes
 * @param string $gender User's gender (optional)
 * @param float $weight User's weight in kg (optional)
 * @param int $height User's height in cm (optional)
 * @param int $age User's age (optional)
 * @return int|false Returns calories burned or false on failure
 */
function getCaloriesBurned($activity_type, $duration, $gender = null, $weight = null, $height = null, $age = null) {
    // Nutritionix API credentials
    $app_id = $_ENV['NUTRITIONIX_APP_ID'] ?? getenv('NUTRITIONIX_APP_ID');
    $app_key = $_ENV['NUTRITIONIX_APP_KEY'] ?? getenv('NUTRITIONIX_APP_KEY');
   
    // API endpoint for exercise
    $endpoint = "https://trackapi.nutritionix.com/v2/natural/exercise";
   
    // Create query string
    $query = $activity_type . " for " . $duration . " minutes";
    
    // Prepare the request data
    $data = [
        'query' => $query,
        'gender' => $gender,
        'weight_kg' => $weight,
        'height_cm' => $height,
        'age' => $age
    ];
    
    // Remove null values from data
    $data = array_filter($data, function($value) {
        return $value !== null;
    });
    
    // Initialize cURL session
    $ch = curl_init($endpoint);
   
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-app-id: ' . $app_id,
        'x-app-key: ' . $app_key,
        'x-remote-user-id: 0' // 0 for development
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
   
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   
    // Close cURL session
    curl_close($ch);
   
    // Check if request was successful
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['exercises']) && !empty($result['exercises'])) {
            return round($result['exercises'][0]['nf_calories']);
        }
    }
    
    error_log("Nutritionix API error: " . $response);
    return false;
}

// Fetch user profile data 
$user_data = null;
$fetch_user_data = "SELECT gender, weight_kg, height_cm, age FROM activities WHERE username = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($fetch_user_data);
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user_result = $stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
    }
    $stmt->close();
}

// Activity submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activity_type'], $_POST['duration'])) {
    $activity_type = $_POST['activity_type'];
    $duration = (int)$_POST['duration'];
    
    // Get user input data if provided
    $gender = isset($_POST['gender']) ? $conn->real_escape_string($_POST['gender']) : null;
    $weight = isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;
    $height = isset($_POST['height_cm']) && $_POST['height_cm'] !== '' ? (int)$_POST['height_cm'] : null;
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    
    // Check if auto-calculate is enabled
    if (isset($_POST['auto_calculate']) && $_POST['auto_calculate'] == 'yes') {
        // Get calories burned from API using user-provided data
        $calories_burned = getCaloriesBurned($activity_type, $duration, $gender, $weight, $height, $age);
        
        if ($calories_burned === false) {
            // If API fails, use manual input if provided
            $calories_burned = isset($_POST['calories_burned']) ? (int)$_POST['calories_burned'] : 0;
            $api_error = "Could not auto-calculate calories. Using provided value.";
        }
    } else {
        // Use manual input
        $calories_burned = isset($_POST['calories_burned']) ? (int)$_POST['calories_burned'] : 0;
    }

    $insert_activity = "INSERT INTO activities (username, activity_type, duration, calories_burned, gender, weight_kg, height_cm, age) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_activity);
    
    $stmt->bind_param("ssiisdii", $username, $activity_type, $duration, $calories_burned, $gender, $weight, $height, $age);

    if ($stmt->execute()) {
        $success_message = "Activity successfully added!";
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $stmt->close();
}

// Activity deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_activity'])) {
    $activity_id = (int)$_POST['delete_activity'];

    $delete_activity = "DELETE FROM activities WHERE id = ? AND username = ?";
    $stmt = $conn->prepare($delete_activity);
    $stmt->bind_param("is", $activity_id, $username);

    if ($stmt->execute()) {
        $success_message = "Activity successfully deleted!";
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $stmt->close();
}

// User's activities
$fetch_activities = "SELECT id, activity_type, duration, calories_burned, created_at FROM activities WHERE username = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($fetch_activities);
$stmt->bind_param("s", $username);
$stmt->execute();
$activities_result = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Activity Log | NutriTrack</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/activity_logging.css">
</head>
<body>
    <nav class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
    </nav>

    <div class="container">
        <h1>Activity Log</h1>

        <?php if (isset($success_message)) echo "<p class='message'>$success_message</p>"; ?>
        <?php if (isset($error_message)) echo "<p class='message' style='color: red;'>$error_message</p>"; ?>
        <?php if (isset($api_error)) echo "<p class='message' style='color: orange;'>$api_error</p>"; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="input-group">
                <select name="activity_type" id="activity-type" required>
                    <option value="Running">Running</option>
                    <option value="Cycling">Cycling</option>
                    <option value="Swimming">Swimming</option>
                    <option value="Walking">Walking</option>
                    <option value="Weight Training">Weight Training</option>
                    <option value="Yoga">Yoga</option>
                    <option value="Hiking">Hiking</option>
                    <option value="Dancing">Dancing</option>
                    <option value="Basketball">Basketball</option>
                    <option value="Soccer">Soccer</option>
                </select>
                <input type="number" name="duration" id="duration" placeholder="Duration (minutes)" required>
                <input type="number" name="calories_burned" id="calories-input" class="calories-field" placeholder="Calories Burned" <?php echo isset($_POST['auto_calculate']) && $_POST['auto_calculate'] == 'yes' ? 'disabled' : 'required'; ?>>
            </div>
            
            <h2>Personal Details for Accurate Calculation</h2>
            <div class="personal-details-group">
                <div class="input-item">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($user_data && $user_data['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($user_data && $user_data['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="input-item">
                    <label for="weight_kg">Weight (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" id="weight_kg" placeholder="Weight in kg" value="<?php echo ($user_data && $user_data['weight_kg']) ? htmlspecialchars($user_data['weight_kg']) : ''; ?>">
                </div>
                
                <div class="input-item">
                    <label for="height_cm">Height (cm)</label>
                    <input type="number" name="height_cm" id="height_cm" placeholder="Height in cm" value="<?php echo ($user_data && $user_data['height_cm']) ? htmlspecialchars($user_data['height_cm']) : ''; ?>">
                </div>
                
                <div class="input-item">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" placeholder="Your age" value="<?php echo ($user_data && $user_data['age']) ? htmlspecialchars($user_data['age']) : ''; ?>">
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="auto-calculate" name="auto_calculate" value="yes" <?php echo isset($_POST['auto_calculate']) && $_POST['auto_calculate'] == 'yes' ? 'checked' : ''; ?>>
                <label for="auto-calculate">Auto-calculate calories burned</label>
                <span class="info-tooltip">ⓘ
                    <span class="tooltip-text">Uses Nutritionix API to estimate calories burned based on activity type, duration, and your personal details.</span>
                </span>
            </div>
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn">Add Activity</button>
        </form>

        <h2>Your Activities</h2>

        <div class="activities-container">
            <?php if ($activities_result->num_rows > 0): ?>
                <?php while ($activity = $activities_result->fetch_assoc()): ?>
                    <div class="activity-entry">
                        <div class="activity-info">
                            <strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong>: 
                            <?php echo htmlspecialchars($activity['duration']); ?> mins, 
                            <?php echo htmlspecialchars($activity['calories_burned']); ?> kcal
                            <br>
                            <small>Recorded on: <?php echo $activity['created_at']; ?></small>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="delete_activity" value="<?php echo $activity['id']; ?>">
                            <button type="submit" class="btn delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No activities logged yet. Start tracking today!</p>
            <?php endif; ?>
        </div>

        <div class="btn-container">
            <a href="home.php" class="btn">Back to Home</a>
        </div>
    </div>
    <script src="/nutrition_tracker/codes/js/activity_logs.js"></script>
</body>
</html>