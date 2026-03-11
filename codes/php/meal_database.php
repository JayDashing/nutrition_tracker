<?php
require_once 'init.php';

// Check if user is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: logreg.php");
    exit();
}

verify_csrf();

$status_message = "";

// Handle approval/rejection/deletion actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_meal'])) {
        $meal_id = $_POST['meal_id'];
        $update_query = "UPDATE meal_shares SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $meal_id);
        
        if ($stmt->execute()) {
            $status_message = "Meal approved successfully!";
        } else {
            $status_message = "Error approving meal: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reject_meal'])) {
        $meal_id = $_POST['meal_id'];
        $rejection_reason = $_POST['rejection_reason'];
        
        // Update meal status
        $update_query = "UPDATE meal_shares SET status = 'rejected', rejection_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $rejection_reason, $meal_id);
        
        if ($stmt->execute()) {
            $status_message = "Meal rejected successfully!";
        } else {
            $status_message = "Error rejecting meal: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_meal'])) {
        $meal_id = $_POST['meal_id'];
        
        // Delete meal from database
        $delete_query = "DELETE FROM meal_shares WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $meal_id);
        
        if ($stmt->execute()) {
            $status_message = "Meal deleted successfully!";
        } else {
            $status_message = "Error deleting meal: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get pending meal shares
$pending_query = "SELECT ms.*, u.username 
                 FROM meal_shares ms
                 JOIN users u ON ms.user_id = u.id
                 WHERE ms.status = 'pending'
                 ORDER BY ms.created_at DESC";
$pending_result = $conn->query($pending_query);

// Get all meal shares for history
$all_meals_query = "SELECT ms.*, u.username 
                   FROM meal_shares ms
                   JOIN users u ON ms.user_id = u.id
                   ORDER BY ms.created_at DESC";
$all_meals_result = $conn->query($all_meals_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Meal Database Admin | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/meal_databases.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Meal Database Management</h1>
    
    <?php if (!empty($status_message)): ?>
        <div class="status-message <?php echo strpos($status_message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $status_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="admin-panel">
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="pending">Pending Approval (<?php echo $pending_result->num_rows; ?>)</div>
                <div class="tab" data-tab="all">All Meal Submissions</div>
            </div>
            
            <div class="tab-content active" id="pending-content">
                <h2><i class='bx bx-time'></i> Pending Approval</h2>
                
                <?php if ($pending_result->num_rows > 0): ?>
                    <?php while ($meal = $pending_result->fetch_assoc()): ?>
                        <div class="meal-card">
                            <div class="meal-header">
                                <div>
                                    <span class="meal-title"><?php echo htmlspecialchars($meal['meal_name']); ?></span>
                                    <span class="meal-date"> · Submitted: <?php echo date('M d, Y H:i', strtotime($meal['created_at'])); ?></span>
                                </div>
                                <span class="meal-user">By: <?php echo htmlspecialchars($meal['username']); ?></span>
                            </div>
                            <div class="meal-content">
                                <div class="meal-image">
                                    <img src="<?php echo htmlspecialchars($meal['image_path']); ?>" alt="<?php echo htmlspecialchars($meal['meal_name']); ?>">
                                </div>
                                <div class="meal-details">
                                    <div class="meal-info-item">
                                        <span class="meal-info-label">Calories:</span> 
                                        <?php echo $meal['calories']; ?> kcal
                                    </div>
                                    <div class="meal-info-item">
                                        <span class="meal-info-label">Description:</span> 
                                        <p class="meal-description"><?php echo htmlspecialchars($meal['description']); ?></p>
                                    </div>
                                    
                                    <div class="meal-actions">
                                        <form method="POST">
                                            <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" name="approve_meal" class="btn btn-approve">
                                                <i class='bx bx-check'></i> Approve
                                            </button>
                                        </form>
                                        
                                        <button class="btn btn-reject" onclick="toggleRejectionForm(<?php echo $meal['id']; ?>)">
                                            <i class='bx bx-x'></i> Reject
                                        </button>
                                        
                                        <form method="POST" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" name="delete_meal" class="btn btn-delete">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                        
                                        <div id="rejection-form-<?php echo $meal['id']; ?>" class="rejection-form">
                                            <form method="POST">
                                                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                                <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (optional)" required></textarea>
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" name="reject_meal" class="btn btn-reject">Confirm Rejection</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-meals">
                        <p>No pending meals to approve. All caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="all-content">
                <h2><i class='bx bx-list-ul'></i> All Meal Submissions</h2>
                
                <div class="search-filter">
                    <input type="text" id="search-meals" placeholder="Search by meal name or username...">
                    <select id="status-filter">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <?php if ($all_meals_result->num_rows > 0): ?>
                    <?php while ($meal = $all_meals_result->fetch_assoc()): ?>
                        <div class="meal-card meal-item" data-status="<?php echo $meal['status']; ?>">
                            <div class="meal-header">
                                <div>
                                    <span class="meal-title"><?php echo htmlspecialchars($meal['meal_name']); ?></span>
                                    <span class="meal-date"> · Submitted: <?php echo date('M d, Y H:i', strtotime($meal['created_at'])); ?></span>
                                </div>
                                <div>
                                    <span class="meal-status status-<?php echo $meal['status']; ?>">
                                        <?php echo ucfirst($meal['status']); ?>
                                    </span>
                                    <span class="meal-user"> · By: <?php echo htmlspecialchars($meal['username']); ?></span>
                                </div>
                            </div>
                            <div class="meal-content">
                                <div class="meal-image">
                                    <img src="<?php echo htmlspecialchars($meal['image_path']); ?>" alt="<?php echo htmlspecialchars($meal['meal_name']); ?>">
                                </div>
                                <div class="meal-details">
                                    <div class="meal-info-item">
                                        <span class="meal-info-label">Calories:</span> 
                                        <?php echo $meal['calories']; ?> kcal
                                    </div>
                                    <div class="meal-info-item">
                                        <span class="meal-info-label">Description:</span> 
                                        <p class="meal-description"><?php echo htmlspecialchars($meal['description']); ?></p>
                                    </div>
                                    
                                    <?php if ($meal['status'] === 'rejected' && !empty($meal['rejection_reason'])): ?>
                                    <div class="meal-info-item">
                                        <span class="meal-info-label">Rejection Reason:</span> 
                                        <p class="meal-description"><?php echo htmlspecialchars($meal['rejection_reason']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="meal-actions">
                                        <?php if ($meal['status'] === 'pending'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" name="approve_meal" class="btn btn-approve">
                                                    <i class='bx bx-check'></i> Approve
                                                </button>
                                            </form>
                                            
                                            <button class="btn btn-reject" onclick="toggleRejectionForm(<?php echo $meal['id']; ?>)">
                                                <i class='bx bx-x'></i> Reject
                                            </button>
                                        <?php endif; ?>
                                        
                                        <form method="POST" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" name="delete_meal" class="btn btn-delete">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                        
                                        <?php if ($meal['status'] === 'pending'): ?>
                                        <div id="rejection-form-<?php echo $meal['id']; ?>" class="rejection-form">
                                            <form method="POST">
                                                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                                <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (optional)" required></textarea>
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" name="reject_meal" class="btn btn-reject">Confirm Rejection</button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-meals">
                        <p>No meal submissions found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div style="text-align: center;">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/meal_database.js"></script>
</body>
</html>