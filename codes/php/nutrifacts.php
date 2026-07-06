<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

$username = $_SESSION['username'];

// Get user's total meals to personalize the page
$meal_query = "SELECT COUNT(*) as meal_count FROM meals WHERE username = ?";
$stmt = $conn->prepare($meal_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$meal_data = $result->fetch_assoc();
$meal_count = $meal_data['meal_count'] ?? 0;
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nutrition Facts | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/nutrifactors.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Nutrition Facts</h1>
    
    <div class="welcome-message">
        <p>Hey <strong><?php echo htmlspecialchars($username); ?></strong>! You've tracked <?php echo $meal_count; ?> meals so far. Here's your nutrition guide to help you reach your goals.</p>
    </div>
    
    <div class="tabs">
        <button class="tab-btn active" onclick="openTab(event, 'fat-loss')">Fat Loss</button>
        <button class="tab-btn" onclick="openTab(event, 'muscle-gain')">Muscle Gain</button>
        <button class="tab-btn" onclick="openTab(event, 'general-health')">General Health</button>
    </div>
    
    <!-- Fat Loss Tab -->
    <div id="fat-loss" class="tab-content active">
        <div class="nutrition-card">
            <h2><i class='bx bx-line-chart-down'></i> Fat Loss Nutrition Guide</h2>
            
            <div class="fact-section">
                <h3>Calorie Deficit</h3>
                <p>Aim for a moderate calorie deficit of 500 calories below your maintenance level for sustainable fat loss of about 1 pound per week.</p>
                <div class="fact-box">
                    <h4>Calculate Your Deficit</h4>
                    <p>Maintenance calories ≈ Body weight (lbs) × 15</p>
                    <p>Fat loss target = Maintenance - 500 calories</p>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Macronutrient Breakdown</h3>
                <div class="macro-grid">
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-bowl-hot'></i></div>
                        <h4>Protein</h4>
                        <p><strong>0.8-1g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Preserves muscle mass during fat loss</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cookie'></i></div>
                        <h4>Carbs</h4>
                        <p><strong>0.5-1g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Provides energy for workouts</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cheese'></i></div>
                        <h4>Fats</h4>
                        <p><strong>0.3-0.4g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Hormone production & satiety</p>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Top Fat Loss Foods</h3>
                <div class="food-grid">
                    <div class="food-item">
                        <h4>Lean Proteins</h4>
                        <ul>
                            <li>Chicken breast</li>
                            <li>Turkey</li>
                            <li>White fish</li>
                            <li>Egg whites</li>
                            <li>Greek yogurt</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Filling Carbs</h4>
                        <ul>
                            <li>Oats</li>
                            <li>Sweet potatoes</li>
                            <li>Brown rice</li>
                            <li>Quinoa</li>
                            <li>Beans & legumes</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Healthy Fats</h4>
                        <ul>
                            <li>Avocados</li>
                            <li>Olive oil</li>
                            <li>Nuts & seeds</li>
                            <li>Fatty fish</li>
                            <li>Egg yolks</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Fat Loss Tips</h3>
                <ul class="tips-list">
                    <li><i class='bx bx-check-circle'></i> Eat protein with every meal to stay full longer</li>
                    <li><i class='bx bx-check-circle'></i> Drink water before meals (16oz can reduce hunger)</li>
                    <li><i class='bx bx-check-circle'></i> Include fiber-rich vegetables to increase satiety</li>
                    <li><i class='bx bx-check-circle'></i> Plan meals ahead to avoid impulsive choices</li>
                    <li><i class='bx bx-check-circle'></i> Aim for 7-9 hours of quality sleep to regulate hunger hormones</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Muscle Gain Tab -->
    <div id="muscle-gain" class="tab-content">
        <div class="nutrition-card">
            <h2><i class='bx bx-dumbbell'></i> Muscle Gain Nutrition Guide</h2>
            
            <div class="fact-section">
                <h3>Calorie Surplus</h3>
                <p>Aim for a moderate calorie surplus of 250-500 calories above your maintenance level for lean muscle gain with minimal fat accumulation.</p>
                <div class="fact-box">
                    <h4>Calculate Your Surplus</h4>
                    <p>Maintenance calories ≈ Body weight (lbs) × 15</p>
                    <p>Muscle gain target = Maintenance + 250-500 calories</p>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Macronutrient Breakdown</h3>
                <div class="macro-grid">
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-bowl-hot'></i></div>
                        <h4>Protein</h4>
                        <p><strong>0.8-1.2g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Essential for muscle repair & growth</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cookie'></i></div>
                        <h4>Carbs</h4>
                        <p><strong>1.5-2.5g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Fuels workouts & replenishes glycogen</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cheese'></i></div>
                        <h4>Fats</h4>
                        <p><strong>0.3-0.5g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Supports hormone production</p>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Top Muscle Building Foods</h3>
                <div class="food-grid">
                    <div class="food-item">
                        <h4>Complete Proteins</h4>
                        <ul>
                            <li>Chicken & Turkey</li>
                            <li>Lean beef</li>
                            <li>Eggs</li>
                            <li>Whey protein</li>
                            <li>Greek yogurt</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Performance Carbs</h4>
                        <ul>
                            <li>Rice (white/brown)</li>
                            <li>Potatoes</li>
                            <li>Pasta</li>
                            <li>Oats</li>
                            <li>Fruits</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Anabolic Fats</h4>
                        <ul>
                            <li>Olive & coconut oil</li>
                            <li>Nuts & nut butters</li>
                            <li>Avocados</li>
                            <li>Whole eggs</li>
                            <li>Fatty fish</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Muscle Building Tips</h3>
                <ul class="tips-list">
                    <li><i class='bx bx-check-circle'></i> Consume 20-40g of protein within 30 minutes post-workout</li>
                    <li><i class='bx bx-check-circle'></i> Eat every 3-4 hours to maintain positive nitrogen balance</li>
                    <li><i class='bx bx-check-circle'></i> Include fast-digesting carbs post-workout to spike insulin</li>
                    <li><i class='bx bx-check-circle'></i> Drink a protein shake before bed to minimize overnight catabolism</li>
                    <li><i class='bx bx-check-circle'></i> Stay hydrated - aim for at least 3-4 liters of water daily</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- General Health Tab -->
    <div id="general-health" class="tab-content">
        <div class="nutrition-card">
            <h2><i class='bx bx-heart'></i> General Health Nutrition Guide</h2>
            
            <div class="fact-section">
                <h3>Balanced Calorie Intake</h3>
                <p>For general health and weight maintenance, consume calories at your maintenance level based on activity.</p>
                <div class="fact-box">
                    <h4>Estimating Daily Needs</h4>
                    <p><strong>Sedentary:</strong> Body weight (lbs) × 13-14</p>
                    <p><strong>Moderately Active:</strong> Body weight (lbs) × 15-16</p>
                    <p><strong>Very Active:</strong> Body weight (lbs) × 17-19</p>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Macronutrient Balance</h3>
                <div class="macro-grid">
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-bowl-hot'></i></div>
                        <h4>Protein</h4>
                        <p><strong>0.6-0.8g per pound</strong> of body weight</p>
                        <p class="macro-benefit">Tissue repair & immune function</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cookie'></i></div>
                        <h4>Carbs</h4>
                        <p><strong>40-50%</strong> of total calories</p>
                        <p class="macro-benefit">Primary energy source</p>
                    </div>
                    <div class="macro-item">
                        <div class="macro-icon"><i class='bx bx-cheese'></i></div>
                        <h4>Fats</h4>
                        <p><strong>25-35%</strong> of total calories</p>
                        <p class="macro-benefit">Cell structure & nutrient absorption</p>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Nutrient-Dense Foods</h3>
                <div class="food-grid">
                    <div class="food-item">
                        <h4>Proteins</h4>
                        <ul>
                            <li>Fish & seafood</li>
                            <li>Poultry</li>
                            <li>Lean meats</li>
                            <li>Eggs</li>
                            <li>Beans & lentils</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Carbohydrates</h4>
                        <ul>
                            <li>Vegetables</li>
                            <li>Fruits</li>
                            <li>Whole grains</li>
                            <li>Legumes</li>
                            <li>Tubers</li>
                        </ul>
                    </div>
                    <div class="food-item">
                        <h4>Healthy Fats</h4>
                        <ul>
                            <li>Avocados</li>
                            <li>Olive oil</li>
                            <li>Nuts & seeds</li>
                            <li>Fatty fish</li>
                            <li>Olives</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Micronutrient Focus</h3>
                <div class="micro-grid">
                    <div class="micro-item">
                        <h4>Vitamin D</h4>
                        <p>Critical for immune function & bone health</p>
                        <p class="micro-source">Sources: Sunlight, fatty fish, egg yolks</p>
                    </div>
                    <div class="micro-item">
                        <h4>Magnesium</h4>
                        <p>Involved in 300+ bodily processes</p>
                        <p class="micro-source">Sources: Dark chocolate, nuts, leafy greens</p>
                    </div>
                    <div class="micro-item">
                        <h4>Omega-3</h4>
                        <p>Anti-inflammatory & brain health</p>
                        <p class="micro-source">Sources: Fatty fish, flax seeds, walnuts</p>
                    </div>
                    <div class="micro-item">
                        <h4>Iron</h4>
                        <p>Oxygen transport & energy production</p>
                        <p class="micro-source">Sources: Red meat, spinach, lentils</p>
                    </div>
                </div>
            </div>
            
            <div class="fact-section">
                <h3>Healthy Eating Principles</h3>
                <ul class="tips-list">
                    <li><i class='bx bx-check-circle'></i> Fill half your plate with vegetables & fruits</li>
                    <li><i class='bx bx-check-circle'></i> Choose whole foods over processed options</li>
                    <li><i class='bx bx-check-circle'></i> Stay hydrated - drink water throughout the day</li>
                    <li><i class='bx bx-check-circle'></i> Practice mindful eating - eat slowly and without distractions</li>
                    <li><i class='bx bx-check-circle'></i> Limit added sugars, refined carbs, and highly processed foods</li>
                </ul>
            </div>
        </div>
    </div>

    <a href="home.php" class="btn">Back to Home</a>
</div>
<script src="/nutrition_tracker/codes/js/nutrifacts.js"></script>
</body>
</html>