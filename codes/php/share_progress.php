<?php
session_start();

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: logreg.php");
    exit();
}

// Database connection
require_once 'db.php';

// Handle approval/rejection actions
$status_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_post'])) {
        $post_id = $_POST['post_id'];
        
        $update_query = "UPDATE share_progress SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $post_id);
        
        if ($stmt->execute()) {
            $status_message = "Post #" . $post_id . " has been approved and is now visible in the community gallery.";
        } else {
            $status_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['reject_post'])) {
        $post_id = $_POST['post_id'];
        $rejection_reason = $_POST['rejection_reason'] ?? "Content does not meet community guidelines.";
        
        $update_query = "UPDATE share_progress SET status = 'rejected', admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $rejection_reason, $post_id);
        
        if ($stmt->execute()) {
            $status_message = "Post #" . $post_id . " has been rejected.";
        } else {
            $status_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch pending posts first, then approved, then rejected
$posts_query = "SELECT * FROM share_progress ORDER BY 
                CASE 
                    WHEN status = 'pending' THEN 1 
                    WHEN status = 'approved' THEN 2 
                    WHEN status = 'rejected' THEN 3 
                END, 
                created_at DESC";
$result = $conn->query($posts_query);
$all_posts = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $all_posts[] = $row;
    }
}

// Get counts
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($all_posts as $post) {
    if ($post['status'] == 'pending') {
        $pending_count++;
    } else if ($post['status'] == 'approved') {
        $approved_count++;
    } else if ($post['status'] == 'rejected') {
        $rejected_count++;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Approval | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/share_progresses.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <div class="welcome-section">
        <h1>Progress Moderation</h1>
    </div>

    <!-- Status Counts -->
    <div class="status-counts">
        <div class="count-item pending">
            <i class='bx bx-time-five count-icon'></i>
            <div>
                <h3>Pending</h3>
                <p class="count-number"><?php echo $pending_count; ?></p>
            </div>
        </div>
        <div class="count-item approved">
            <i class='bx bx-check-circle count-icon'></i>
            <div>
                <h3>Approved</h3>
                <p class="count-number"><?php echo $approved_count; ?></p>
            </div>
        </div>
        <div class="count-item rejected">
            <i class='bx bx-x-circle count-icon'></i>
            <div>
                <h3>Rejected</h3>
                <p class="count-number"><?php echo $rejected_count; ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($status_message)): ?>
        <div class="message-box <?php echo (strpos($status_message, "Error") !== false) ? "error" : "success"; ?>">
            <?php echo $status_message; ?>
        </div>
    <?php endif; ?>

    <!-- Filter Controls -->
    <div class="filter-controls">
        <button class="filter-btn active" data-filter="all">All Posts</button>
        <button class="filter-btn" data-filter="pending">Pending</button>
        <button class="filter-btn" data-filter="approved">Approved</button>
        <button class="filter-btn" data-filter="rejected">Rejected</button>
    </div>

    <!-- Posts Management -->
    <div class="posts-container">
        <?php if (empty($all_posts)): ?>
            <div class="empty-posts">
                <i class='bx bx-file-blank'></i>
                <p>No posts to moderate at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_posts as $post): ?>
                <div class="post-card <?php echo $post['status']; ?>">
                    <div class="post-header">
                        <span class="post-id">#<?php echo $post['id']; ?></span>
                        <span class="status-badge <?php echo $post['status']; ?>">
                            <?php if ($post['status'] == 'pending'): ?>
                                <i class='bx bx-time-five'></i> Pending
                            <?php elseif ($post['status'] == 'approved'): ?>
                                <i class='bx bx-check-circle'></i> Approved
                            <?php elseif ($post['status'] == 'rejected'): ?>
                                <i class='bx bx-x-circle'></i> Rejected
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="post-content">
                        <div class="post-user">
                            <i class='bx bx-user-circle'></i>
                            <span><?php echo htmlspecialchars($post['username']); ?></span>
                            <span class="post-date"><?php echo date("M j, Y, g:i a", strtotime($post['created_at'])); ?></span>
                        </div>
                        
                        <p class="post-description"><?php echo htmlspecialchars($post['description']); ?></p>
                        
                        <?php if (!empty($post['image_path'])): ?>
                            <div class="post-image">
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Progress photo">
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($post['status'] == 'rejected' && !empty($post['admin_notes'])): ?>
                            <div class="rejection-reason">
                                <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($post['admin_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-actions">
                        <?php if ($post['status'] == 'pending'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="approval-form">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="approve_post" class="btn btn-approve">
                                    <i class='bx bx-check'></i> Approve
                                </button>
                            </form>
                            
                            <button class="btn btn-reject show-rejection-form">
                                <i class='bx bx-x'></i> Reject
                            </button>
                            
                            <div class="rejection-form-container" style="display: none;">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="rejection-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <select name="rejection_reason" required>
                                        <option value="">Select a reason...</option>
                                        <option value="Content does not meet community guidelines.">Does not meet guidelines</option>
                                        <option value="Inappropriate or offensive content.">Inappropriate content</option>
                                        <option value="Low quality image or description.">Low quality</option>
                                        <option value="Not related to nutrition or fitness.">Unrelated to topic</option>
                                        <option value="Contains promotional content.">Promotional content</option>
                                    </select>
                                    <button type="submit" name="reject_post" class="btn btn-reject-confirm">
                                        <i class='bx bx-x'></i> Confirm Rejection
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($post['status'] == 'approved'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="rejection_reason" value="Post approval reversed.">
                                <button type="submit" name="reject_post" class="btn btn-reverse">
                                    <i class='bx bx-revision'></i> Reverse Approval
                                </button>
                            </form>
                        <?php elseif ($post['status'] == 'rejected'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="approve_post" class="btn btn-reverse">
                                    <i class='bx bx-revision'></i> Reverse Rejection
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin: 30px auto;">
        <a href="dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/share_progress.js"></script>
</body>
</html>