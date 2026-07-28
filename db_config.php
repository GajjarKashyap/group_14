<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "perfume_hub"; 

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($dbname);
?>
