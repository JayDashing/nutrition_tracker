<?php
require_once 'init.php';

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

// Get total number of reports/feedback
$report_count_query = "SELECT COUNT(*) as total_reports FROM feedback";
$report_count_result = $conn->query($report_count_query);
$report_count = 0;
if ($report_count_result) {
    $report_count = $report_count_result->fetch_assoc()['total_reports'];
}

// Get all feedback submissions, newest first
$feedback_query = "SELECT f.*, u.email FROM feedback f 
                  LEFT JOIN users u ON f.username = u.username 
                  ORDER BY f.submission_date DESC";
$feedback_result = $conn->query($feedback_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/reports.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>User Feedback & Reports</h1>
    
    <div class="report-stats">
        <p>Total feedback submissions: <strong><?php echo $report_count; ?></strong></p>
    </div>
    
    <div class="feedback-list">
        <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
            <?php while($row = $feedback_result->fetch_assoc()): ?>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="user-details">
                            <span class="username"><?php echo htmlspecialchars($row['username']); ?></span>
                            <span class="email"><?php echo htmlspecialchars($row['email'] ?? 'No email'); ?></span>
                        </div>
                        <div class="submission-date">
                            <?php echo date('M d, Y h:i A', strtotime($row['submission_date'])); ?>
                        </div>
                    </div>
                    <div class="feedback-content">
                        <?php echo nl2br(htmlspecialchars($row['feedback_text'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-feedback">
                <p>No feedback submissions found.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

</body>
</html>