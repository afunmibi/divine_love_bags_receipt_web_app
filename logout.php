<?php
session_start();
require 'config.php';

// Clean up CSRF tokens for this user
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("DELETE FROM csrf_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

session_destroy();
header("Location: index.php");
exit;
?>