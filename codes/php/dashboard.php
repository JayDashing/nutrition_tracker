<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: logreg.php");
    exit();
}
// Connect to the database
require_once 'db.php';

// Update last_login for the current user
$current_user = $_SESSION['username'];
$update_login_time = "UPDATE users SET last_login = NOW() WHERE username = ?";
$update_stmt = $conn->prepare($update_login_time);
$update_stmt->bind_param("s", $current_user);
$update_stmt->execute();
$update_stmt->close();

// Get user information
$user = $_SESSION['username'];
$user_query = "SELECT id, username, email, role, profile_picture FROM users WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $user);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_result->num_rows == 1) {
    $user_data = $user_result->fetch_assoc();
   
} else {
    // Handle error
    $user_data = [
        'email' => 'admin@nutritrack.com',
        'profile_picture' => '/nutrition_tracker/codes/images/default-profile.jpg'
    ];
}
// Fetch activity logs from meals, goals, and meal_shares tables
$activities_query = "
    (SELECT 'added a meal' AS action, m.created_at AS timestamp, u.username
     FROM meals m JOIN users u ON m.username = u.username
     ORDER BY m.created_at DESC LIMIT 3)
    UNION
    (SELECT 'set a nutrition goal' AS action, g.created_at AS timestamp, u.username
     FROM goals g JOIN users u ON g.username = u.username
     ORDER BY g.created_at DESC LIMIT 3)
    UNION
    (SELECT 'shared a meal' AS action, ms.created_at AS timestamp, u.username
     FROM meal_shares ms JOIN users u ON ms.user_id = u.id
     ORDER BY ms.created_at DESC LIMIT 3)
    ORDER BY timestamp DESC LIMIT 5";
$activities_result = $conn->query($activities_query);
$user_stmt->close();
// Default profile picture if none exists
$profile_pic = $user_data['profile_picture'] ? $user_data['profile_picture'] : "/nutrition_tracker/codes/images/default-profile.jpg";
// Fetch total users (admin + user)
$total_users_query = "SELECT COUNT(*) AS total_users FROM users";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total_users'];
// Fetch admin accounts
$admin_query = "SELECT * FROM users WHERE role='admin'";
$admin_result = $conn->query($admin_query);
// Fetch user accounts
$user_query = "SELECT * FROM users WHERE role='user'";
$user_result = $conn->query($user_query);
// Count total user meals for statistics
$meals_query = "SELECT COUNT(*) AS total_meals FROM meals";
$meals_result = $conn->query($meals_query);
$total_meals = $meals_result->fetch_assoc()['total_meals'] ?? 0;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrack | Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/dashboard.css">
</head>
<body>
    <!-- Mobile Navbar (Only visible on small screens) -->
    <div class="navbar">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
        <div class="logo">
            <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 120px; height: auto;">
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 180px; height: auto;">
        </div>
        
        <div class="profile-container">
            <div class="profile-pic">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
            </div>
            <div class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($user_data['email']); ?></div>
            <a href="profile.php" class="edit-profile-btn">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
        </div>
        
        <a href="admin_dashboard.php" class="nav-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="manage_users.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>User Management</span>
        </a>
        
        <a href="meal_database.php" class="nav-item">
            <i class="fas fa-utensils"></i>
            <span>Meal Database</span>
        </a>

        <a href="share_progress.php" class="nav-item">
            <i class="fas fa-tasks"></i>
            <span>Progress Moderation</span>
        </a>
        
        <a href="nutrition_analysis.php" class="nav-item">
            <i class="fas fa-chart-pie"></i>
            <span>User Nutrition Analysis</span>
        </a>
        
        <a href="reports.php" class="nav-item">
            <i class="fas fa-file-alt"></i>
            <span>Reports</span>
        </a>
        
        <a href="announcements.php" class="nav-item">
            <i class="fas fa-bullhorn"></i>
            <span>Announcements</span>
        </a>
        
        <div class="mode-toggle">
            <span>Light Mode</span>
            <label class="toggle-switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>
        
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-header">
        <h1 style="color: white; background-color:rgb(30, 147, 30); padding: 10px; border-radius: 5px; text-align: center; width: 30%; margin: 0 auto;">Admin Dashboard</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $total_meals; ?></h3>
                    <p>Total Meals Logged</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <h3>100%</h3>
                    <p>User Activity</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $admin_result->num_rows; ?></h3>
                    <p>Admin Users</p>
                </div>
            </div>
        </div>

        <!-- Admin and User Accounts -->
        <div class="tables-container">
        <div class="card">
    <div class="card-header">
        <h2>Admin Accounts</h2>
        <a href="add_user.php?role=admin" class="action-btn" style="text-decoration: none;"><i class="fas fa-plus"></i> Add User</a>
    </div>
    <div class="card-body">
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Last Login</th>
            </tr>
            <?php while ($admin = $admin_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin['id']); ?></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                    <td>
                        <?php 
                        if (!empty($admin['last_login'])) {
                            echo date('Y-m-d H:i:s', strtotime($admin['last_login'])); 
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>User Accounts</h2>
        <a href="add_user.php?role=user" class="action-btn" style="text-decoration: none;"><i class="fas fa-plus"></i> Add User</a>
    </div>
    <div class="card-body">
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Registration Date</th>
                <th>Status</th>
            </tr>
            <?php while ($user = $user_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?></td>
                    <td>
                        <?php
                        $status = $user['status'] ?? 'active';
                        $statusClass = ($status == 'active') ? 'active' : 'inactive';
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

            <!-- Activity Log (Removed duplicate) -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Recent Activity</h2>
                    <button class="action-btn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="card-body">
                    <?php if (isset($activities_result) && $activities_result->num_rows > 0): ?>
                        <?php while ($activity = $activities_result->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php 
                                    // Display different icons based on action text
                                    $icon_class = "fas fa-history";
                                    
                                    if (strpos($activity['action'], 'meal') !== false) {
                                        if (strpos($activity['action'], 'shared') !== false) {
                                            $icon_class = "fas fa-share-alt";
                                        } else {
                                            $icon_class = "fas fa-utensils";
                                        }
                                    } else if (strpos($activity['action'], 'goal') !== false) {
                                        $icon_class = "fas fa-bullseye";
                                    } else if (strpos($activity['action'], 'activity') !== false) {
                                        $icon_class = "fas fa-running";
                                    }
                                    ?>
                                    <i class="<?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo htmlspecialchars($activity['username']); ?> <?php echo htmlspecialchars($activity['action']); ?></h4>
                                    <p><?php echo date('F j, Y, g:i a', strtotime($activity['timestamp'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-details">
                                <h4>No recent activity found</h4>
                                <p>User activities will appear here when available</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/nutrition_tracker/codes/js/dashboard.js"></script>
</body>
</html>