<?php
// Handle CSV file upload and import data to database
require_once 'classes/CsvProcessor.php';

function handleCsvUpload($conn, $file) {
    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file: " . getUploadErrorMessage($file['error']);
    }
    
    // Check file type
    $fileType = mime_content_type($file['tmp_name']);
    if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel') {
        return "Invalid file type. Please upload a CSV file.";
    }
    
    // Move uploaded file to a temporary location
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return "Failed to save uploaded file.";
    }
    
    try {
        // Process the CSV file
        $processor = new CsvProcessor();
        $result = $processor->processFile($filePath);
        
        if ($result['status'] === 'success') {
            // If format was detected, transform and import the data
            $transformedData = $processor->transformData($filePath, $result['mapping']);
            
            if (saveTransformedData($conn, $transformedData)) {
                return "CSV data successfully imported and processed.";
            } else {
                return "Error saving data to database.";
            }
        } else if ($result['status'] === 'needs_mapping') {
            // Store file path and mapping info in session for the mapping page
            session_start();
            $_SESSION['uploaded_csv'] = $filePath;
            $_SESSION['mapping_result'] = $result;
            
            // Redirect to mapping page
            header('Location: map_columns.php');
            exit;
        } else {
            return "Error processing CSV: " . ($result['message'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
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

// Helper function to get upload error message
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension";
        default:
            return "Unknown upload error";
    }
}

// Save transformed data to database
function saveTransformedData($conn, $data) {
    if (empty($data)) {
        return false;
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Get import batch ID
        $stmt = $conn->prepare("INSERT INTO import_batches (import_date) VALUES (NOW())");
        $stmt->execute();
        $batchId = $conn->insert_id;
        
        // Prepare insert statement
        $stmt = $conn->prepare("INSERT INTO traffic_data 
            (batch_id, traffic_source, traffic_medium, visits, visitors, page_views, 
             bounce_rate, avg_session_duration, import_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        // Insert each row
        foreach ($data as $row) {
            $stmt->bind_param("issiiidi", 
                $batchId,
                $row['traffic_source'] ?? '',
                $row['traffic_medium'] ?? '',
                $row['visits'] ?? 0,
                $row['visitors'] ?? 0,
                $row['page_views'] ?? 0,
                $row['bounce_rate'] ?? 0,
                $row['avg_session_duration'] ?? 0
            );
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
?>