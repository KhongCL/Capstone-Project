<?php

// Name: Justin Yong Cheng Xun
// Position: Developer
// TP Number: TP077360
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: get_traffic_data.php
// Description: AJAX endpoint for retrieving traffic data over time with customizable intervals,
//              providing JSON response for dynamic chart visualization and analytics dashboard.
// First Written On: 14 April 2025
// Edited On: 14 June 2025

require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Get interval parameter (default to 'day' if not provided)
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'day';

// Validate interval to prevent SQL injection
if (!in_array($interval, ['hour', 'day', 'month', 'year'])) {
    $interval = 'day';
}

// Get traffic data by interval
$trafficData = getTrafficOverTime($conn, $interval);

// Return data as JSON
echo json_encode($trafficData);
?>