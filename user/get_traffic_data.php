<?php
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