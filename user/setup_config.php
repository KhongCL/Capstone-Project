<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: setup_config.php
// Description: Configuration setup utility that creates CSV mapping configuration files
//              for various analytics platforms including Google Analytics and GA4 formats.
// First Written On: 9July 2025
// Edited On: 14 July 2025

// Create config directory if it doesn't exist
if (!is_dir('config')) {
    mkdir('config', 0755, true);
}

// Define mappings including GA4 traffic acquisition report format
$mappings = [
    "google_analytics" => [
        "format_detection" => ["Source", "Medium", "Sessions", "Pageviews", "Bounce Rate"],
        "column_mappings" => [
            "Source" => "traffic_source",
            "Medium" => "traffic_medium",
            "Sessions" => "visits",
            "Users" => "visitors",
            "Pageviews" => "page_views",
            "Bounce Rate" => "bounce_rate",
            "Avg Session Duration" => "avg_session_duration",
            "Key events" => "key_events",
            "Session key event rate" => "session_key_event_rate",
            "Total revenue" => "total_revenue"
        ],
        "data_types" => [
            "Source" => "text",
            "Medium" => "text", 
            "Sessions" => "integer",
            "Users" => "integer",
            "Pageviews" => "integer",
            "Bounce Rate" => "percentage",
            "Avg Session Duration" => "time",
            "Key events" => "integer",
            "Session key event rate" => "percentage",
            "Total revenue" => "currency"
        ]
    ],
    "ga4_traffic_acquisition" => [
        "format_detection" => ["Sessions", "Engaged sessions", "Engagement rate", "Session primary channel group (Default channel group)"],
        "column_mappings" => [
            "Session primary channel group (Default channel group)" => "traffic_source",
            "Sessions" => "visits", 
            "Engaged sessions" => "engaged_sessions",
            "Engagement rate" => "bounce_rate",
            "Average engagement time per session" => "avg_session_duration",
            "Events per session" => "events_per_session",
            "Event count" => "event_count",
            "Key events" => "key_events",
            "Session key event rate" => "session_key_event_rate",
            "Total revenue" => "total_revenue"
        ],
        "data_types" => [
            "Session primary channel group (Default channel group)" => "text",
            "Sessions" => "integer",
            "Engaged sessions" => "integer",
            "Engagement rate" => "float",
            "Average engagement time per session" => "float",
            "Events per session" => "float",
            "Event count" => "integer",
            "Key events" => "integer",
            "Session key event rate" => "percentage",
            "Total revenue" => "currency"
        ]
    ],
];

// Save mappings to JSON file
file_put_contents(__DIR__ . '/../config/csv_mappings.json', json_encode($mappings, JSON_PRETTY_PRINT));

echo "Configuration file created successfully!";
?>