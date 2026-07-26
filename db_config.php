<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "perfume_hub"; // Jo database aapne phpMyAdmin me banaya hai

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
