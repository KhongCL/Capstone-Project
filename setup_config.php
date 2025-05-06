<?php
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
            "Avg Session Duration" => "avg_session_duration"
        ],
        "data_types" => [
            "Source" => "string",
            "Medium" => "string",
            "Sessions" => "integer",
            "Users" => "integer",
            "Pageviews" => "integer",
            "Bounce Rate" => "percentage",
            "Avg Session Duration" => "time"
        ]
    ],
    "matomo" => [
        "format_detection" => ["Referrer", "Visits", "Actions", "Bounce Rate"],
        "column_mappings" => [
            "Referrer" => "traffic_source",
            "Visits" => "visits",
            "Unique visitors" => "visitors",
            "Actions" => "page_views",
            "Bounce Rate" => "bounce_rate",
            "Avg. time on website" => "avg_session_duration"
        ],
        "data_types" => [
            "Referrer" => "string",
            "Visits" => "integer",
            "Unique visitors" => "integer",
            "Actions" => "integer",
            "Bounce Rate" => "percentage",
            "Avg. time on website" => "time"
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
            "Event count" => "event_count"
        ],
        "data_types" => [
            "Sessions" => "integer",
            "Engaged sessions" => "integer",
            "Engagement rate" => "float",
            "Average engagement time per session" => "float",
            "Events per session" => "float",
            "Event count" => "integer"
        ]
    ]
];

// Save mappings to JSON file
file_put_contents('config/csv_mappings.json', json_encode($mappings, JSON_PRETTY_PRINT));

echo "Configuration file created successfully!";
?>