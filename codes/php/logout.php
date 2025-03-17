<?php
session_start();
session_destroy();
header("Location: logreg.php"); // Redirects to your login/signup page
exit();
?>
