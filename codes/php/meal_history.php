<?php
require_once 'init.php';

// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

$username = $_SESSION['username'];


// Meal deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_meal'])) {
    $meal_id = (int)$_POST['delete_meal'];

    $delete_query = "DELETE FROM meals WHERE id = ? AND username = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("is", $meal_id, $username);
    $stmt->execute();
    $stmt->close();

    header("Location: meal_history.php");
    exit();
}

// Meal history
$search = isset($_GET['search']) ? "%" . $conn->real_escape_string($_GET['search']) . "%" : "%";

$meals_query = "SELECT id, meal_name, calories, created_at FROM meals WHERE username = ? AND meal_name LIKE ? ORDER BY created_at DESC";
$stmt = $conn->prepare($meals_query);
$stmt->bind_param("ss", $username, $search);
$stmt->execute();
$result = $stmt->get_result();

$meals = [];
$total_calories = 0;
while ($row = $result->fetch_assoc()) {
    $meals[] = $row;
    $total_calories += $row['calories'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal History | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/meal_history.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
<div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Meal History</h1>

    <p><strong>Total Calories:</strong> <?php echo $total_calories; ?> kcal</p>

    <div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" id="searchInput" placeholder="Search by meal name" 
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit" class="btn">Search</button>
        <button type="button" class="btn clear-btn" onclick="clearSearch()">Clear</button>
    </form>
</div>

<script src="/nutrition_tracker/codes/js/meal_history.js"></script>

    <?php if (count($meals) > 0): ?>
        <?php foreach ($meals as $meal): ?>
            <div class="meal-entry">
                <div>
                    <strong><?php echo htmlspecialchars($meal['meal_name']); ?></strong> - <?php echo $meal['calories']; ?> kcal<br>
                    <small>Recorded on: <?php echo $meal['created_at']; ?></small>
                </div>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="delete_meal" value="<?php echo $meal['id']; ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No meals found.</p>
    <?php endif; ?>

    <a href="home.php" class="btn back-btn">Back to Home</a>
</div>
<script src="/nutrition_tracker/codes/js/meal_history.js"></script>
</body>
</html>