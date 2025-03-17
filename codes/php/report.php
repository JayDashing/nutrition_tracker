<?php
session_start();

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

//Database connection
require_once 'db.php';

// Handle feedback submission
$feedback_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback'])) {
    $feedback = $_POST['feedback'];
    $feedback_query = "INSERT INTO feedback (username, feedback_text, submission_date) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($feedback_query);
    $stmt->bind_param("ss", $username, $feedback);
    
    if ($stmt->execute()) {
        $feedback_message = "Thank you for your feedback!";
    } else {
        $feedback_message = "Error submitting feedback. Please try again.";
    }
    $stmt->close();
}

// Count total number of reports/feedback
$report_count_query = "SELECT COUNT(*) as total_reports FROM feedback";
$report_count_result = $conn->query($report_count_query);
$report_count = 0;
if ($report_count_result) {
    $report_count = $report_count_result->fetch_assoc()['total_reports'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/reportfeed.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Reports & Feedback</h1>
    
    <div class="report-stats">
        <p>NutriTrack Community Insights: <strong><?php echo $report_count; ?></strong> healthy suggestions from users like you!</p>
    </div>
    
    <div class="feedback-form">
        <h2>Share Your Feedback</h2>
        <?php if (!empty($feedback_message)): ?>
            <div class="feedback-message"><?php echo $feedback_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="feedback">Your suggestions or concerns about the website:</label>
                <textarea id="feedback" name="feedback" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn submit-btn">Submit Feedback</button>
        </form>
    </div>
    
    <a href="home.php" class="btn">Back to Home</a>
</div>

</body>
</html>