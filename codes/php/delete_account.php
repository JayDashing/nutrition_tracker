<?php
require 'db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);

    if ($stmt->execute()) {
        session_destroy();
        header("Location: logreg.php?success=account_deleted");
        exit();
    } else {
        $error = "Error deleting account. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Delete Account | NutriTrack</title>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/delete_account.css">
</head>

<body>

<div class="container">
    <h2>Delete Your Account</h2>
    <p>Are you sure you want to delete your account? This action is irreversible and all your data will be permanently removed.</p>

    <!-- Open the confirmation modal -->
    <button class="btn btn-danger" onclick="openModal()">Delete My Account</button>
    <a href="profile.php" class="btn btn-cancel">Cancel</a>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Account Deletion</h3>
        <p>Once deleted, you cannot recover your account. Are you sure?</p>

        <form method="POST" action="">
            <div class="modal-buttons">
                <button type="submit" name="delete_account" class="btn btn-danger">Yes, Delete My Account</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="/nutrition_tracker/codes/js/delete_account.js"></script>
</body>
</html>

