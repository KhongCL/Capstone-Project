<?php

// Name: Lim Jia Jhen
// Position/Role: Developer
// TP Number: TP077404
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: config.php
// Description: Database configuration file containing connection settings and 
//              credentials for MySQL database connection establishment.
// First Written On: 14 April 2025
// Edited On: 14 July 2025

$host = "localhost";
$username = "root";
$password = "";
$database = "trafanalyz";


$conn = new mysqli($host, $username, $password, $database);


if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}
?>