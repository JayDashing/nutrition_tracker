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

// Meal deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $delete_query = "DELETE FROM meals WHERE id = ? AND username = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("is", $delete_id, $username);
    $stmt->execute();
    $stmt->close();
    header("Location: meal_tracker.php");
    exit();
}

// Load food database for search functionality
$food_database = [];
$food_query = "SELECT id, food_name, calories_per_100g, protein, carbs, fat FROM food_database ORDER BY food_name ASC";
$food_result = $conn->query($food_query);
if ($food_result) {
    while ($row = $food_result->fetch_assoc()) {
        $food_database[] = $row;
    }
}

// Add custom food to database
if (isset($_POST['add_custom_food'])) {
    $food_name = $conn->real_escape_string($_POST['custom_food_name']);
    $calories = (float)$_POST['custom_calories'];
    $protein = (float)$_POST['custom_protein'];
    $carbs = (float)$_POST['custom_carbs'];
    $fat = (float)$_POST['custom_fat'];
    
    $insert_food = "INSERT INTO food_database (food_name, calories_per_100g, protein, carbs, fat) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_food);
    $stmt->bind_param("sdddd", $food_name, $calories, $protein, $carbs, $fat);
    
    if ($stmt->execute()) {
        $success_message = "Custom food added to database!";
        // Add the new food to our array for immediate use
        $food_database[] = [
            'id' => $conn->insert_id,
            'food_name' => $food_name,
            'calories_per_100g' => $calories,
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat
        ];
    } else {
        $error_message = "Error adding custom food: " . $conn->error;
    }
    $stmt->close();
}

/**
 * Get nutrition information from Nutritionix API
 * 
 * @param string $query The food/meal query (e.g. "2 eggs with toast")
 * @return array|false Returns nutrition data or false on failure
 */
function getNutritionInfo($query) {
    // Nutritionix API credentials
    $app_id = "14be65b6";
    $app_key = "013362f3a3c7bcada7df574cb2fadf81";
    
    // API endpoint for natural language processing
    $endpoint = "https://trackapi.nutritionix.com/v2/natural/nutrients";
    
    // Prepare the request
    $data = json_encode([
        'query' => $query,
        'timezone' => 'Pacific', // Adjust as needed
    ]);
    
    // Initialize cURL session
    $ch = curl_init($endpoint);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-app-id: ' . $app_id,
        'x-app-key: ' . $app_key,
        'x-remote-user-id: 0' // 0 for development
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL session
    curl_close($ch);
    
    // Check if request was successful
    if ($http_code === 200) {
        return json_decode($response, true);
    } else {
        error_log("Nutritionix API error: " . $response);
        return false;
    }
}

// Function to extract and format nutrition data from API response
function extractNutritionData($apiResponse) {
    if (!$apiResponse || !isset($apiResponse['foods']) || empty($apiResponse['foods'])) {
        return false;
    }
    
    $result = [
        'foods' => [],
        'totals' => [
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0
        ]
    ];
    
    foreach ($apiResponse['foods'] as $food) {
        $foodItem = [
            'name' => $food['food_name'],
            'quantity' => $food['serving_qty'],
            'unit' => $food['serving_unit'],
            'calories' => round($food['nf_calories']),
            'protein' => round($food['nf_protein'], 1),
            'carbs' => round($food['nf_total_carbohydrate'], 1),
            'fat' => round($food['nf_total_fat'], 1)
        ];
        
        $result['foods'][] = $foodItem;
        
        // Add to totals
        $result['totals']['calories'] += $foodItem['calories'];
        $result['totals']['protein'] += $foodItem['protein'];
        $result['totals']['carbs'] += $foodItem['carbs'];
        $result['totals']['fat'] += $foodItem['fat'];
    }
    
    return $result;
}

// Convert different units to grams for calculation
function convert_to_grams($size, $unit) {
    switch($unit) {
        case 'g':
            return $size;
        case 'oz':
            return $size * 28.35;
        case 'cup':
            return $size * 240; // Approximate, varies by food
        case 'tbsp':
            return $size * 15;
        case 'tsp':
            return $size * 5;
        case 'ml':
            return $size; // Assuming density of 1g/ml
        default:
            return $size;
    }
}

// Meal submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['meal_name'])) {
    $meal_name = $conn->real_escape_string($_POST['meal_name']);
    $calories = (int)$_POST['calories'];
    $portion_size = (float)$_POST['portion_size'];
    $portion_unit = $conn->real_escape_string($_POST['portion_unit']);
    $food_id = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
    
    // Calculate actual calories based on portion size (if from database)
    if ($food_id > 0) {
        foreach ($food_database as $food) {
            if ($food['id'] == $food_id) {
                // Convert portion to grams based on unit
                $grams = convert_to_grams($portion_size, $portion_unit);
                $calories = ($food['calories_per_100g'] / 100) * $grams;
                break;
            }
        }
    }
    
    $insert_query = "INSERT INTO meals (username, meal_name, calories, portion_size, portion_unit, food_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssidsi", $username, $meal_name, $calories, $portion_size, $portion_unit, $food_id);

    if ($stmt->execute()) {
        $success_message = "Meal successfully added!";
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $stmt->close();
}

// Meals and total calories
$meals_query = "SELECT id, meal_name, calories, portion_size, portion_unit, food_id, created_at 
                FROM meals WHERE username = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($meals_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$meals = [];
$total_calories = 0;
$meals_by_date = [];

while ($row = $result->fetch_assoc()) {
    $meals[] = $row;
    $total_calories += $row['calories'];
    
    // Group meals by date for the timeline view
    $date = date('Y-m-d', strtotime($row['created_at']));
    if (!isset($meals_by_date[$date])) {
        $meals_by_date[$date] = [];
    }
    $meals_by_date[$date][] = $row;
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Meals | NutriTrack</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/meal_trackers.css">
</head>
<body>

<div class="navbar">
<div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Track Your Meals</h1>
    
    <?php if (isset($success_message)) echo "<p class='message'>$success_message</p>"; ?>
    <?php if (isset($error_message)) echo "<p class='message' style='color: red;'>$error_message</p>"; ?>
    
    <div class="tabs">
    <div class="tab active" data-tab="quick-add">Quick Add</div>
    <div class="tab" data-tab="food-database">Food Database</div>
    <div class="tab" data-tab="custom-food">Add Custom Food</div>
    <div class="tab" data-tab="meal-search">Search by Meal</div>
</div>
    
    <!-- Quick Add Tab -->
    <div class="tab-content active" id="quick-add">
        <form method="POST" action="">
            <input type="text" name="meal_name" placeholder="Meal Name" required pattern="[A-Za-z\s]{3,}" title="Only valid meals with more than two letters are allowed" onkeypress="return event.charCode != 96">
            <input type="number" name="calories" placeholder="Calories" required>
            <div class="portion-controls">
                <input type="number" name="portion_size" placeholder="Portion Size" value="1" step="0.1" min="0.1" required>
                <select name="portion_unit">
                    <option value="serving">Serving</option>
                    <option value="g">Grams</option>
                    <option value="oz">Ounces</option>
                    <option value="cup">Cup</option>
                    <option value="tbsp">Tablespoon</option>
                    <option value="tsp">Teaspoon</option>
                    <option value="ml">Milliliter</option>
                </select>
            </div>
            <input type="hidden" name="food_id" value="0">
            <button type="submit" class="btn">Add Meal</button>
        </form>
    </div>
    
    <!-- Food Database Tab -->
    <div class="tab-content" id="food-database">
        <div class="search-container">
            <input type="text" id="foodSearchInput" placeholder="Search for a food...">
            <div class="search-results" id="searchResults">
                <?php if (count($food_database) > 0): ?>
                    <?php foreach ($food_database as $food): ?>
                        <div class="food-item" data-id="<?php echo $food['id']; ?>" 
                             data-name="<?php echo $food['food_name']; ?>"
                             data-calories="<?php echo $food['calories_per_100g']; ?>"
                             data-protein="<?php echo $food['protein']; ?>"
                             data-carbs="<?php echo $food['carbs']; ?>"
                             data-fat="<?php echo $food['fat']; ?>">
                            <strong><?php echo $food['food_name']; ?></strong>
                            <div><?php echo $food['calories_per_100g']; ?> kcal per 100g</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-results">No foods in database yet. Add custom foods to get started.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="nutrient-info" id="selectedFoodInfo" style="display: none;">
            <h3 id="selectedFoodName">Food Name</h3>
            <div class="nutrient-grid">
                <div class="nutrient-box">
                    <div class="nutrient-value" id="caloriesValue">0</div>
                    <div class="nutrient-label">Calories</div>
                </div>
                <div class="nutrient-box">
                    <div class="nutrient-value" id="proteinValue">0g</div>
                    <div class="nutrient-label">Protein</div>
                </div>
                <div class="nutrient-box">
                    <div class="nutrient-value" id="carbsValue">0g</div>
                    <div class="nutrient-label">Carbs</div>
                </div>
                <div class="nutrient-box">
                    <div class="nutrient-value" id="fatValue">0g</div>
                    <div class="nutrient-label">Fat</div>
                </div>
            </div>
            
            <form method="POST" action="" id="databaseFoodForm">
                <div class="portion-controls">
                    <input type="number" name="portion_size" id="portionSize" placeholder="Portion Size" value="100" step="0.1" min="0.1" required>
                    <select name="portion_unit" id="portionUnit">
                        <option value="g">Grams</option>
                        <option value="oz">Ounces</option>
                        <option value="cup">Cup</option>
                        <option value="tbsp">Tablespoon</option>
                        <option value="tsp">Teaspoon</option>
                        <option value="ml">Milliliter</option>
                        <option value="serving">Serving</option>
                    </select>
                </div>
                <input type="hidden" name="meal_name" id="foodName">
                <input type="hidden" name="calories" id="calculatedCalories">
                <input type="hidden" name="food_id" id="foodId">
                <button type="submit" class="btn">Add to Journal</button>
            </form>
        </div>
    </div>
    
    <!-- Custom Food Tab -->
    <div class="tab-content" id="custom-food">
        <div class="custom-food-form">
            <h3>Add Custom Food to Database</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <input type="text" name="custom_food_name" placeholder="Food Name" required>
                </div>
                <div class="form-row">
                    <input type="number" name="custom_calories" placeholder="Calories per 100g" step="0.1" min="0" required>
                    <input type="number" name="custom_protein" placeholder="Protein (g) per 100g" step="0.1" min="0" required>
                </div>
                <div class="form-row">
                    <input type="number" name="custom_carbs" placeholder="Carbs (g) per 100g" step="0.1" min="0" required>
                    <input type="number" name="custom_fat" placeholder="Fat (g) per 100g" step="0.1" min="0" required>
                </div>
                <input type="hidden" name="add_custom_food" value="1">
                <button type="submit" class="btn">Add Custom Food</button>
            </form>
        </div>
    </div>

    <div class="tab-content" id="meal-search">
    <div class="meal-search-container">
        <form method="POST" action="" id="mealSearchForm">
            <div class="search-input-container">
                <input type="text" name="meal_query" id="mealQueryInput" placeholder="(e.g., '2 eggs with toast and coffee')" required>
                <button type="submit" name="search_meal" class="btn" style="margin-top: 10px;">Search</button>
            </div>
        </form>
        
        <?php
        // Process the meal search
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_meal'])) {
            $meal_query = $_POST['meal_query'];
            $nutrition_data = getNutritionInfo($meal_query);
            $formatted_data = extractNutritionData($nutrition_data);
            
            if ($formatted_data) {
                ?>
                <div class="meal-search-results">
                    <h3>Results for "<?php echo htmlspecialchars($meal_query); ?>"</h3>
                    
                    <div class="nutrition-summary">
                        <div class="nutrient-grid">
                            <div class="nutrient-box">
                                <div class="nutrient-value"><?php echo $formatted_data['totals']['calories']; ?></div>
                                <div class="nutrient-label">Calories</div>
                            </div>
                            <div class="nutrient-box">
                                <div class="nutrient-value"><?php echo $formatted_data['totals']['protein']; ?>g</div>
                                <div class="nutrient-label">Protein</div>
                            </div>
                            <div class="nutrient-box">
                                <div class="nutrient-value"><?php echo $formatted_data['totals']['carbs']; ?>g</div>
                                <div class="nutrient-label">Carbs</div>
                            </div>
                            <div class="nutrient-box">
                                <div class="nutrient-value"><?php echo $formatted_data['totals']['fat']; ?>g</div>
                                <div class="nutrient-label">Fat</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="found-foods">
                     <h4>Foods Found:</h4>
                    <div class="foods-container">
                          <?php foreach ($formatted_data['foods'] as $food): ?>
                         <div class="food-item-result">
                          <span class="food-name"><?php echo htmlspecialchars($food['name']); ?></span>
                          <span class="food-quantity"><?php echo $food['quantity'] . ' ' . $food['unit']; ?></span>
                         <span class="food-calories"><?php echo $food['calories']; ?> kcal</span>
                        </div>
                      <?php endforeach; ?>
                      </div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal_query); ?>">
                        <input type="hidden" name="calories" value="<?php echo $formatted_data['totals']['calories']; ?>">
                        <input type="hidden" name="portion_size" value="1">
                        <input type="hidden" name="portion_unit" value="serving">
                        <input type="hidden" name="food_id" value="0">
                        <button type="submit" class="btn">Add to Journal</button>
                    </form>
                </div>
                <?php
            } else {
                echo '<div class="error-message">Sorry, could not find nutrition information for that meal. Try being more specific or use different keywords.</div>';
            }
        }
        ?>
    </div>
</div>
    
    <div class="nutrition-journal">
        <div class="journal-header">
            <h2>Nutrition Journal</h2>
            <div class="calorie-summary">
                <div class="calorie-badge">
                    <i class='bx bxs-hot'></i>
                    <span><?php echo $total_calories; ?> kcal</span>
                </div>
                <div class="meal-count">
                    <i class='bx bx-restaurant'></i>
                    <span><?php echo count($meals); ?> entries</span>
                </div>
            </div>
        </div>
        
        <?php if (count($meals_by_date) > 0): ?>
            <div class="journal-timeline">
                <?php foreach ($meals_by_date as $date => $day_meals): ?>
                    <div class="journal-day">
                        <div class="day-header">
                            <div class="day-marker">
                                <div class="date-circle"></div>
                                <div class="date-line"></div>
                            </div>
                            <h3 class="day-date"><?php echo date('l, F j, Y', strtotime($date)); ?></h3>
                        </div>
                        
                        <div class="day-entries">
                            <?php 
                            $day_total = 0;
                            foreach ($day_meals as $meal) {
                                $day_total += $meal['calories'];
                            }
                            ?>
                            
                            <div class="day-summary">
                                <span class="day-total"><?php echo $day_total; ?> kcal</span>
                                <span class="day-count"><?php echo count($day_meals); ?> meals</span>
                            </div>
                            
                            <?php foreach ($day_meals as $meal): ?>
                                <div class="journal-entry">
                                    <div class="entry-time">
                                        <?php echo date('h:i A', strtotime($meal['created_at'])); ?>
                                    </div>
                                    <div class="entry-content">
                                        <h4><?php echo htmlspecialchars($meal['meal_name']); ?></h4>
                                        <div class="entry-calories"><?php echo htmlspecialchars($meal['calories']); ?> kcal</div>
                                        <?php if($meal['portion_size']): ?>
                                            <div class="entry-portion">
                                                <?php echo htmlspecialchars($meal['portion_size']); ?> 
                                                <?php echo htmlspecialchars($meal['portion_unit']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="?delete_id=<?php echo $meal['id']; ?>" class="entry-delete" title="Delete this meal">
                                        <i class='bx bx-x'></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-journal">
                <i class='bx bx-book-content'></i>
                <p>Your nutrition journal is empty. Add your first meal to start tracking!</p>
            </div>
        <?php endif; ?>
    </div>
    <a href="home.php" class="btn home-btn">Back to Home</a>
</div>

<script src="/nutrition_tracker/codes/js/meal_tracker.js"></script>
</body>
</html>