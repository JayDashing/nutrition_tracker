<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

verify_csrf();

//Pre-defined challenges with increasing difficulty
$basic_challenges = [
    [
        "title" => "Drink 8 Glasses of Water",
        "description" => "Stay hydrated by drinking at least 8 glasses of water daily for 7 days.",
        "duration" => 7,
        "difficulty" => "Easy",
        "icon" => "💧"
    ],
    [
        "title" => "5 Veggies",
        "description" => "Include 5 different types of veggies into your daily meals for 5 days.",
        "duration" => 5,
        "difficulty" => "Medium",
        "icon" => "🥦"
    ],
    [
        "title" => "No Processed Sugar",
        "description" => "Avoid all processed sugar and sweetened beverages for 3 days.",
        "duration" => 3,
        "difficulty" => "Hard",
        "icon" => "🚫"
    ],
    [
        "title" => "Meal Prep Challenge",
        "description" => "Prepare and log all your meals in advance for a full week.",
        "duration" => 7,
        "difficulty" => "Medium",
        "icon" => "🍱"
    ],
    [
        "title" => "Protein Power",
        "description" => "Ensure each meal contains a healthy source of protein for 5 days.",
        "duration" => 5,
        "difficulty" => "Easy",
        "icon" => "🥚"
    ],
    [
        "title" => "Colorful Plate",
        "description" => "Include 3 different colored fruits or veggies in each meal for 3 days.",
        "duration" => 3,
        "difficulty" => "Medium",
        "icon" => "🌈"
    ]
];

$intermediate_challenges = [
    [
        "title" => "Intermittent Fasting",
        "description" => "Follow a 16:8 intermittent fasting schedule for 5 days.",
        "duration" => 5,
        "difficulty" => "Hard",
        "icon" => "⏱️"
    ],
    [
        "title" => "Plant-Based Week",
        "description" => "Eat only plant-based meals for 7 consecutive days.",
        "duration" => 7,
        "difficulty" => "Hard",
        "icon" => "🌱"
    ],
    [
        "title" => "Macro Tracking",
        "description" => "Track and meet your daily macro nutrient goals for 7 days.",
        "duration" => 7,
        "difficulty" => "Medium",
        "icon" => "📊"
    ],
    [
        "title" => "No Added Salt",
        "description" => "Cook and eat meals without adding extra salt for 5 days.",
        "duration" => 5,
        "difficulty" => "Medium",
        "icon" => "🧂"
    ]
];

$advanced_challenges = [
    [
        "title" => "Full Ketogenic Diet",
        "description" => "Follow a strict ketogenic diet (under 20g carbs) for 7 days.",
        "duration" => 7,
        "difficulty" => "Expert",
        "icon" => "🥑"
    ],
    [
        "title" => "Calorie Deficit",
        "description" => "Maintain a 500 calorie deficit each day while meeting nutrient requirements for 10 days.",
        "duration" => 10,
        "difficulty" => "Hard",
        "icon" => "📉"
    ],
    [
        "title" => "Micronutrient Master",
        "description" => "Track and meet 100% of your daily vitamin and mineral requirements for 7 days.",
        "duration" => 7,
        "difficulty" => "Expert",
        "icon" => "🧪"
    ],
    [
        "title" => "OMAD (One Meal A Day)",
        "description" => "Practice eating only one nutritionally complete meal per day for 3 days.",
        "duration" => 3,
        "difficulty" => "Expert",
        "icon" => "🍽️"
    ]
];

$master_challenges = [
    [
        "title" => "90-Day Transformation",
        "description" => "Complete a comprehensive 90-day nutrition plan with weekly check-ins.",
        "duration" => 90,
        "difficulty" => "Master",
        "icon" => "🏆"
    ],
    [
        "title" => "Nutritional Analysis",
        "description" => "Document and analyze the nutritional profile of every meal for 14 days.",
        "duration" => 14,
        "difficulty" => "Master",
        "icon" => "🔬"
    ],
    [
        "title" => "Cyclical Nutrition",
        "description" => "Follow a cyclical nutrition plan with alternating high and low carb days for 21 days.",
        "duration" => 21,
        "difficulty" => "Master",
        "icon" => "🔄"
    ]
];

$completed_challenges = 0;

//Achievement Badges for challenges
function getChallengesBadge($completed_count) {
    if ($completed_count >= 20) return "🏆 Challenge Master";
    if ($completed_count >= 10) return "🎯 Goal Crusher";
    if ($completed_count >= 5) return "⭐ Challenge Star";
    return "🔰 Challenge Beginner";
}
$challenge_badge = getChallengesBadge($completed_challenges);

// Determine which challenges to show based on badge level
$all_challenges = $basic_challenges;
if ($completed_challenges >= 5) {
    $all_challenges = array_merge($all_challenges, $intermediate_challenges);
}
if ($completed_challenges >= 10) {
    $all_challenges = array_merge($all_challenges, $advanced_challenges);
}
if ($completed_challenges >= 20) {
    $all_challenges = array_merge($all_challenges, $master_challenges);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta charset="UTF-8">
    <title>Challenges | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/challenges.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Nutrition Challenges</h1>
    
    <p class="stats">You've completed <strong><?php echo $completed_challenges; ?></strong> challenges so far.</p>
    
    <p class="badge">Your Challenge Badge: <?php echo $challenge_badge; ?></p>
    
    <div class="challenges-container">
        <?php foreach ($all_challenges as $challenge): ?>
            <div class="challenge-card <?php echo strtolower($challenge['difficulty']); ?>">
                <div class="challenge-icon"><?php echo $challenge['icon']; ?></div>
                <h3><?php echo htmlspecialchars($challenge['title']); ?></h3>
                <p><?php echo htmlspecialchars($challenge['description']); ?></p>
                <div class="challenge-meta">
                    <span class="duration"><i class='bx bx-time'></i> <?php echo $challenge['duration']; ?> days</span>
                    <span class="difficulty"><?php echo $challenge['difficulty']; ?></span>
                </div>
                <div class="challenge-progress" style="display: none;">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <div class="timer" style="display: none;">
                    <i class='bx bx-timer'></i>
                    <span class="time-remaining">0 days remaining</span>
                </div>
                <button class="join-btn" onclick="joinChallenge('<?php echo htmlspecialchars($challenge['title']); ?>', '<?php echo htmlspecialchars($challenge['icon']); ?>', '<?php echo $challenge['duration']; ?>', '<?php echo htmlspecialchars($challenge['difficulty']); ?>', '<?php echo htmlspecialchars($challenge['description']); ?>')">Join Challenge</button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <a href="home.php" class="btn">Back to Home</a>
</div>
<script src="/nutrition_tracker/codes/js/challenges.js"></script>
</body>
</html>