<?php
require_once 'init.php';

// Redirect if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

verify_csrf();

// Initialize search and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'ASC';

$allowed_sort = ['id', 'username', 'email', 'role'];
$allowed_order = ['ASC', 'DESC'];

if (!in_array($sort, $allowed_sort)) {
    $sort = 'id';
}

if (!in_array($order, $allowed_order)) {
    $order = 'ASC';
}

// Handle delete user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF attack detected");
    }

    $user_id = intval($_POST['delete']);

    /* Prevent deleting yourself */
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['message_type'] = "error";
        header("Location: manage_users.php");
        exit();
    }

    /* Check role */
    $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    $check_stmt->close();

    if ($user && $user['role'] !== 'admin') {

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "User deleted successfully!";
        $_SESSION['message_type'] = "success";

    } else {

        $_SESSION['message'] = "Admin users cannot be deleted!";
        $_SESSION['message_type'] = "error";
    }

    header("Location: manage_users.php");
    exit();
}


// Prepare query with search and sort
$query = "SELECT id, username, email, role FROM users";
if (!empty($search)) {
    $search_term = "%{$search}%";
    $query .= " WHERE username LIKE '$search_term' OR email LIKE '$search_term' OR role LIKE '$search_term'";
}
$query .= " ORDER BY $sort $order";

$result = $conn->query($query);

// Count total users
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
$total_regular_users = $total_users - $total_admins;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>User Management | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/manage_user.css">
</head>
<body>

<?php if (isset($_SESSION['message'])): ?>
    <div class="toast <?php echo $_SESSION['message_type']; ?> show">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php 
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
<?php endif; ?>

<header>
    <div class="logo">
        <img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;">
    </div>
</header>

<div class="container">
    <div class="dashboard-header">
        <h1>User Management</h1>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="number"><?php echo $total_users; ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_admins; ?></div>
            <div class="label">Administrators</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_regular_users; ?></div>
            <div class="label">Regular Users</div>
        </div>
    </div>

    <div class="search-sort">
        <form action="" method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <div class="sort-options">
            <label for="sort">Sort by:</label>
            <select name="sort" id="sort" onchange="updateSort(this.value)">
                <option value="id" <?php echo $sort === 'id' ? 'selected' : ''; ?>>ID</option>
                <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                <option value="role" <?php echo $sort === 'role' ? 'selected' : ''; ?>>Role</option>
            </select>
            <select name="order" id="order" onchange="updateOrder(this.value)">
                <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th class="<?php echo $sort === 'id' ? ($order === 'ASC' ? 'asc' : 'desc') : ''; ?>" onclick="sortTable('id')">ID</th>
                <th class="<?php echo $sort === 'username' ? ($order === 'ASC' ? 'asc' : 'desc') : ''; ?>" onclick="sortTable('username')">Username</th>
                <th class="<?php echo $sort === 'email' ? ($order === 'ASC' ? 'asc' : 'desc') : ''; ?>" onclick="sortTable('email')">Email</th>
                <th class="<?php echo $sort === 'role' ? ($order === 'ASC' ? 'asc' : 'desc') : ''; ?>" onclick="sortTable('role')">Role</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="role-badge <?php echo $row['role'] === 'admin' ? 'admin-badge' : 'user-badge'; ?>">
                            <?php echo htmlspecialchars($row['role']); ?>
                        </span>
                    </td>
                    <td class="action-btns">
                        <?php if ($row['role'] !== 'admin'): ?>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn action-btn danger-btn" title="Delete User" 
                               onclick="return confirm('Are you sure you want to delete this user?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn action-btn disabled-btn" title="Admin users cannot be deleted">
                            <i class="fas fa-lock" style="color: grey;"></i>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <p>No users found. Try adjusting your search.</p>
        </div>
    <?php endif; ?>

    <div class="bottom-actions">
        <a href="dashboard.php" class="btn dashboard-btn">
            Back to Dashboard
        </a>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/manage_users.js"></script>
</body>
</html>
<?php
$conn->close();
?>