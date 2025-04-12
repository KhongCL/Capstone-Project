<?php
// Handle CSV file upload and import data to database
function handleCsvUpload($conn, $file) {
    // Check if the file is a CSV
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($fileType != 'csv') {
        return "Error: Please upload a CSV file.";
    }
    
    // Open the uploaded file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        // Skip the header row
        $header = fgetcsv($handle, 1000, ",");
        
        // Check if the CSV has the required columns
        $requiredColumns = ['timestamp', 'page_url', 'user_id', 'traffic_source', 'session_id', 'event_type'];
        $headerColumns = array_map('strtolower', $header);
        
        // Ensure all required columns are present
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headerColumns)) {
                return "Error: CSV file must contain the following columns: " . implode(", ", $requiredColumns);
            }
        }
        
        // Get column indexes
        $columnIndexes = [];
        foreach ($requiredColumns as $column) {
            $columnIndexes[$column] = array_search($column, $headerColumns);
        }
        
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO web_traffic_data (timestamp, page_url, user_id, traffic_source, session_id, event_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $timestamp, $page_url, $user_id, $traffic_source, $session_id, $event_type);
        
        // Process each row
        $rowCount = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $timestamp = $data[$columnIndexes['timestamp']];
            $page_url = $data[$columnIndexes['page_url']];
            $user_id = $data[$columnIndexes['user_id']];
            $traffic_source = $data[$columnIndexes['traffic_source']];
            $session_id = $data[$columnIndexes['session_id']];
            $event_type = $data[$columnIndexes['event_type']];
            
            $stmt->execute();
            $rowCount++;
        }
        
        fclose($handle);
        $stmt->close();
        
        return "Success: Imported $rowCount records from the CSV file.";
    } else {
        return "Error: Unable to read the CSV file.";
    }
}

// Get key metrics for the dashboard
function getKeyMetrics($conn) {
    $metrics = [];
    
    // Total Page Views
    $query = "SELECT COUNT(*) as total_page_views FROM web_traffic_data WHERE event_type = 'page_view'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $metrics['total_page_views'] = $row['total_page_views'];
    
    // Unique Visitors
    $query = "SELECT COUNT(DISTINCT user_id) as unique_visitors FROM web_traffic_data";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $metrics['unique_visitors'] = $row['unique_visitors'];
    
    // Average Session Duration (simplified approach)
    $query = "SELECT 
                session_id, 
                MAX(timestamp) as end_time, 
                MIN(timestamp) as start_time
              FROM web_traffic_data
              GROUP BY session_id";
    $result = $conn->query($query);
    $totalSessions = 0;
    $totalDuration = 0;
    
    while($row = $result->fetch_assoc()) {
        $start = strtotime($row['start_time']);
        $end = strtotime($row['end_time']);
        $duration = $end - $start;
        $totalDuration += $duration;
        $totalSessions++;
    }
    
    $metrics['avg_session_duration'] = $totalSessions > 0 ? 
        gmdate("H:i:s", $totalDuration / $totalSessions) : "00:00:00";
    
    // Bounce Rate (sessions with only one page view)
    $query = "SELECT 
                COUNT(*) as single_page_sessions
              FROM (
                SELECT session_id, COUNT(*) as views
                FROM web_traffic_data
                WHERE event_type = 'page_view'
                GROUP BY session_id
                HAVING views = 1
              ) as bounce_sessions";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $singlePageSessions = $row['single_page_sessions'];
    
    $query = "SELECT COUNT(DISTINCT session_id) as total_sessions 
              FROM web_traffic_data 
              WHERE event_type = 'page_view'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $totalPageViewSessions = $row['total_sessions'];
    
    $metrics['bounce_rate'] = $totalPageViewSessions > 0 ? 
        round(($singlePageSessions / $totalPageViewSessions) * 100, 2) . '%' : '0%';
    
    return $metrics;
}

// Get traffic over time data for charts
function getTrafficOverTime($conn, $interval = 'day') {
    switch ($interval) {
        case 'hour':
            $format = '%Y-%m-%d %H:00:00';
            break;
        case 'day':
            $format = '%Y-%m-%d';
            break;
        case 'month':
            $format = '%Y-%m';
            break;
        default:
            $format = '%Y-%m-%d';
    }
    
    $query = "SELECT 
                DATE_FORMAT(timestamp, '$format') as time_period,
                COUNT(*) as page_views,
                COUNT(DISTINCT user_id) as unique_visitors
              FROM web_traffic_data 
              WHERE event_type = 'page_view'
              GROUP BY time_period
              ORDER BY MIN(timestamp)";
    
    $result = $conn->query($query);
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Get traffic sources distribution data
function getTrafficSourcesDistribution($conn) {
    $query = "SELECT 
                traffic_source,
                COUNT(*) as visit_count,
                ROUND((COUNT(*) / (SELECT COUNT(*) FROM web_traffic_data WHERE event_type = 'page_view')) * 100, 2) as percentage
              FROM web_traffic_data 
              WHERE event_type = 'page_view'
              GROUP BY traffic_source
              ORDER BY visit_count DESC";
    
    $result = $conn->query($query);
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Get top visited pages data
function getTopVisitedPages($conn, $limit = 10) {
    $query = "SELECT 
                page_url,
                COUNT(*) as page_views,
                COUNT(DISTINCT user_id) as unique_visitors
              FROM web_traffic_data 
              WHERE event_type = 'page_view'
              GROUP BY page_url
              ORDER BY page_views DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}
?>