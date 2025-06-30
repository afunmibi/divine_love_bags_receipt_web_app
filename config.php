<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "divine_love_bags";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>