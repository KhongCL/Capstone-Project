<?php
require_once 'config.php';

// Create tables if they don't exist
$createTables = [
    "CREATE TABLE IF NOT EXISTS import_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        import_date DATETIME NOT NULL,
        status ENUM('processed', 'failed') DEFAULT 'processed'
    )",
    
    "CREATE TABLE IF NOT EXISTS traffic_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        traffic_source VARCHAR(255),
        traffic_medium VARCHAR(255),
        visits INT DEFAULT 0,
        visitors INT DEFAULT 0,
        page_views INT DEFAULT 0,
        bounce_rate DECIMAL(5,2) DEFAULT 0,
        avg_session_duration FLOAT DEFAULT 0,
        engaged_sessions INT DEFAULT 0,
        engagement_rate FLOAT DEFAULT 0,
        events_per_session FLOAT DEFAULT 0,
        event_count INT DEFAULT 0,
        key_events INT DEFAULT 0,
        session_key_event_rate FLOAT DEFAULT 0,
        total_revenue DECIMAL(10,2) DEFAULT 0,
        import_date DATETIME NOT NULL,
        FOREIGN KEY (batch_id) REFERENCES import_batches(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS csv_format_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        detected_format VARCHAR(50),
        manual_mapping BOOLEAN DEFAULT FALSE,
        upload_date DATETIME NOT NULL,
        column_count INT,
        row_count INT,
        status VARCHAR(50)
    )"
];

// Drop the existing table if it already exists
$conn->query("DROP TABLE IF EXISTS traffic_data");

$success = true;
$messages = [];

foreach ($createTables as $query) {
    if (!$conn->query($query)) {
        $success = false;
        $messages[] = "Error creating table: " . $conn->error;
    }
}

if ($success) {
    echo "Database setup completed successfully.";
} else {
    echo "Errors occurred during database setup:<br>";
    foreach ($messages as $message) {
        echo "- $message<br>";
    }
}
?>