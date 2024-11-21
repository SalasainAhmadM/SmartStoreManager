<?php
$host = "localhost";
$username = "root";
$password = "";
$database_name = "ssm";

$conn = new mysqli($host, $username, $password, $database_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>