<?php
//Connect to the database
require_once 'db.php';

//Admin credentials
$admin_username = 'nutriadmin';
$admin_password = password_hash('nutrida', PASSWORD_DEFAULT); // Hash the password
$admin_role = 'admin';

$check_query = "SELECT * FROM users WHERE username='$admin_username'";
$result = $conn->query($check_query);

if ($result->num_rows == 0) {
    $insert_query = "INSERT INTO users (username, password, role) 
                     VALUES ('$admin_username', '$admin_password', '$admin_role')";
    if ($conn->query($insert_query) === TRUE) {
        echo "Admin account created successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Admin account already exists!";
}

$conn->close();
?>
