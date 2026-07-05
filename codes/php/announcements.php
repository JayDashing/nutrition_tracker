<?php
require_once 'init.php';

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

$username = $_SESSION['username'];

// Handle announcement submission
$announcement_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement_text'])) {
    $announcement_text = $_POST['announcement_text'];
    $title = $_POST['title'];
    $importance = $_POST['importance'];
    
    $announcement_query = "INSERT INTO announcements (title, announcement_text, importance, created_at, created_by) 
                          VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($announcement_query);
    $stmt->bind_param("ssis", $title, $announcement_text, $importance, $username);
    
    if ($stmt->execute()) {
        $announcement_message = "Announcement published successfully!";
    } else {
        $announcement_message = "Error publishing announcement. Please try again.";
    }
    $stmt->close();
}

// Handle announcement deletion if ID is provided
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_query = "DELETE FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $announcement_message = "Announcement deleted successfully.";
    } else {
        $announcement_message = "Error deleting announcement.";
    }
    $stmt->close();
}

// Get all announcements
$announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcements_result = $conn->query($announcements_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Announcements | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/announcements.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Admin Announcements</h1>
    
    <div class="announcement-stats">
        <p>Manage announcements that will be visible to all NutriTrack users.</p>
    </div>
    
    <div class="announcement-form">
        <h2>Create New Announcement</h2>
        <?php if (!empty($announcement_message)): ?>
            <div class="announcement-message"><?php echo $announcement_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="title">Announcement Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="importance">Importance Level:</label>
                <select id="importance" name="importance">
                    <option value="1">Low</option>
                    <option value="2" selected>Medium</option>
                    <option value="3">High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="announcement_text">Announcement Content:</label>
                <textarea id="announcement_text" name="announcement_text" rows="5" required></textarea>
            </div>
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn submit-btn">Publish Announcement</button>
        </form>
    </div>
    
    <div class="announcements-list">
        <h2>Current Announcements</h2>
        
        <?php if ($announcements_result && $announcements_result->num_rows > 0): ?>
            <?php while($row = $announcements_result->fetch_assoc()): ?>
                <div class="announcement-item importance-<?php echo $row['importance']; ?>">
                    <div class="announcement-header">
                        <div class="announcement-details">
                            <div class="title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="meta">
                                <span class="created-by">By: <?php echo htmlspecialchars($row['created_by']); ?></span>
                                <span class="date"><?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?></span>
                                <span class="importance">
                                    <?php 
                                    switch($row['importance']) {
                                        case 1: echo "Low Importance"; break;
                                        case 2: echo "Medium Importance"; break;
                                        case 3: echo "High Importance"; break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="announcement-actions">
                            <a href="announcements.php?delete=<?php echo $row['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
                        </div>
                    </div>
                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($row['announcement_text'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-announcements">
                <p>No announcements have been published yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

</body>
</html>