<?php
require_once '../auth/admin_auth.php';
require_once '../config.php';

header('Content-Type: text/plain');

echo "=== METRIC TYPES DEBUG INFORMATION ===\n\n";

// 1. Show current metric types in database
echo "1. CURRENT METRIC TYPES IN DATABASE:\n";
echo "=====================================\n";
$result = $conn->query("SELECT MetricTypeID, MetricName, Description FROM metric_type ORDER BY MetricTypeID");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['MetricTypeID']}, Name: '{$row['MetricName']}', Description: '{$row['Description']}'\n";
    }
} else {
    echo "No metric types found in database\n";
}

echo "\n";

// 2. Show current mappings from JSON
echo "2. CURRENT MAPPINGS FROM JSON FILE:\n";
echo "===================================\n";
$mappingsFile = __DIR__ . '/../config/csv_mappings.json';
if (file_exists($mappingsFile)) {
    $mappings = json_decode(file_get_contents($mappingsFile), true);
    
    foreach ($mappings as $formatKey => $format) {
        echo "Format: $formatKey\n";
        if (isset($format['column_mappings'])) {
            foreach ($format['column_mappings'] as $csvCol => $systemField) {
                echo "  '$csvCol' -> '$systemField'\n";
            }
        }
        echo "\n";
    }
} else {
    echo "JSON mappings file not found\n";
}

// 3. Show what should be in metric_type table
echo "3. SYSTEM FIELDS THAT SHOULD BE IN METRIC_TYPE:\n";
echo "===============================================\n";
$systemFields = [
    'traffic_source' => 'Traffic Source',
    'traffic_medium' => 'Traffic Medium', 
    'visits' => 'Number of visits/sessions',
    'visitors' => 'Number of unique visitors',
    'page_views' => 'Total number of page views',
    'bounce_rate' => 'Bounce rate percentage',
    'avg_session_duration' => 'Average session duration',
    'engaged_sessions' => 'Number of engaged sessions',
    'events_per_session' => 'Average events per session',
    'event_count' => 'Total event count',
    'key_events' => 'Number of key events/conversions',
    'session_key_event_rate' => 'Session key event rate',
    'total_revenue' => 'Total revenue generated'
];

$usedSystemFields = [];
if (isset($mappings)) {
    foreach ($mappings as $format) {
        if (isset($format['column_mappings'])) {
            foreach ($format['column_mappings'] as $systemField) {
                $usedSystemFields[$systemField] = true;
            }
        }
    }
}

echo "Used system fields:\n";
foreach ($usedSystemFields as $field => $unused) {
    $description = $systemFields[$field] ?? 'Unknown';
    echo "  '$field' -> '$description'\n";
}

echo "\n=== END DEBUG ===\n";
?>