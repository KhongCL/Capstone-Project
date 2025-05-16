<?php
// Handle CSV file upload and import data to database
require_once 'classes/CsvProcessor.php';

function handleCsvUpload($conn, $file) {
    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'type' => 'error',
            'message' => "Error uploading file: " . getUploadErrorMessage($file['error'])
        ];
    }
    
    // Check file extension first (most reliable for CSVs)
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        return [
            'type' => 'error',
            'message' => "Invalid file type. Please upload a CSV file."
        ];
    }

    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxFileSize) {
        return [
            'type' => 'error',
            'message' => "File size exceeds the 5MB limit. Please upload a smaller file."
        ];
    }
    
    // Ensure config directory and mappings file exist
    if (!file_exists('config/csv_mappings.json')) {
        // Create config directory if needed
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        // Create a basic mappings file
        $defaultMappings = [
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
        file_put_contents('config/csv_mappings.json', json_encode($defaultMappings, JSON_PRETTY_PRINT));
    }
    
    // Move uploaded file to a temporary location
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'type' => 'error',
            'message' => "Failed to save uploaded file."
        ];
    }
    
    try {
        // Process the CSV file
        $processor = new CsvProcessor();
        
        // Extract metadata for database storage
        $metadata = $processor->extractGa4Metadata($filePath);
        error_log("Extracted metadata: " . json_encode($metadata));
        
        $result = $processor->processFile($filePath);
        error_log("processFile result status: " . $result['status']);
        
        if ($result['status'] === 'success') {
            // If format was detected, transform and import the data
            error_log("Format detected: " . $result['format']);
            $transformedData = $processor->transformData($filePath, $result['mapping'], $result['format']);
            error_log("Transformed data count: " . count($transformedData));
            
            // Store metadata in session for later use during saving
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['csv_metadata'] = $metadata;
            // Store the file path in session for cleanup if needed
            $_SESSION['uploaded_csv'] = $filePath;
            
            if (saveTransformedData($conn, $transformedData)) {
                return [
                    'type' => 'success',
                    'message' => "CSV data successfully imported and processed."
                ];
            } else {
                // Check if we have a specific message from saveTransformedData
                if (isset($_SESSION['upload_message'])) {
                    // Clean up file if not already done in saveTransformedData
                    if (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv'])) {
                        unlink($_SESSION['uploaded_csv']);
                        unset($_SESSION['uploaded_csv']);
                    }
                    return $_SESSION['upload_message'];
                } else {
                    // Clean up file since there was an error
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    return [
                        'type' => 'error',
                        'message' => "Error saving data to database."
                    ];
                }
            }
        } else if ($result['status'] === 'needs_mapping') {
            // Store file path and mapping info in session for the mapping page
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['uploaded_csv'] = $filePath;
            $_SESSION['mapping_result'] = $result;
            $_SESSION['csv_metadata'] = $metadata;
            
            // Redirect to mapping page
            header('Location: map_columns.php');
            exit;
        } else {
            // Clean up file since there was an error
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return [
                'type' => 'error',
                'message' => "Error processing CSV: " . ($result['message'] ?? 'Unknown error')
            ];
        }
    } catch (Exception $e) {
        // Clean up file on exception
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Enhanced error logging
        error_log("CSV Processing Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        return [
            'type' => 'error',
            'message' => "Error: " . $e->getMessage()
        ];
    }
}

// Get key metrics for the dashboard
function getKeyMetrics($conn) {
    $metrics = [
        'total_page_views' => 0,
        'unique_visitors' => 0,
        'avg_session_duration' => '00:00:00',
        'bounce_rate' => '0%'
    ];
    
    try {
        // Get the most recent upload ID
        $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD";
        $result = $conn->query($query);
        $latestUpload = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $latestUpload = $row['latest_upload'];
        }
        
        // Get Sessions count only from latest upload
        $query = "SELECT SUM(pdp.Value) as total_views 
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName = 'Sessions'
                 AND pdp.UploadID = $latestUpload";
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $metrics['total_page_views'] = $row['total_views'] ?: 0;
        }
        
        // Count distinct source types as unique visitors
        $query = "SELECT COUNT(DISTINCT SourceTypeID) as unique_visitors 
                 FROM PROCESSED_DATA_POINT 
                 WHERE UploadID = $latestUpload";
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $metrics['unique_visitors'] = $row['unique_visitors'] ?: 0;
        }
        
        // Average Session Duration - from latest upload only
        $query = "SELECT AVG(pdp.Value) as avg_duration
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName = 'Average engagement time per session'
                 AND pdp.UploadID = $latestUpload";
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $avgSeconds = $row['avg_duration'] ?: 0;
            $metrics['avg_session_duration'] = gmdate("H:i:s", $avgSeconds);
        }
        
        // Bounce Rate (latest upload only)
        $query = "SELECT AVG(pdp.Value) as avg_engagement_rate
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName = 'Engagement rate'
                 AND pdp.UploadID = $latestUpload";
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $engagementRate = $row['avg_engagement_rate'] ?: 0;
            // Bounce rate is roughly inverse of engagement rate
            $bounceRate = (1 - $engagementRate) * 100;
            $metrics['bounce_rate'] = round($bounceRate, 2) . '%';
        }
    } catch (Exception $e) {
        error_log("Error getting metrics: " . $e->getMessage());
    }
    
    return $metrics;
}


// Get traffic over time data for charts
function getTrafficOverTime($conn, $interval = 'day') {
    $data = [];
    
    try {
        // Get the most recent upload ID
        $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD";
        $result = $conn->query($query);
        $latestUpload = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $latestUpload = $row['latest_upload'];
        }
        
        // Get sessions data by date - only from latest upload
        $query = "SELECT 
                    pdp.DataDate as time_period,
                    SUM(pdp.Value) as page_views,
                    COUNT(DISTINCT pdp.SourceTypeID) as unique_visitors
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = $latestUpload
                  GROUP BY pdp.DataDate
                  ORDER BY pdp.DataDate";
                  
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting traffic data: " . $e->getMessage());
    }
    
    return $data;
}


// Get traffic sources distribution data
function getTrafficSourcesDistribution($conn) {
    $data = [];
    
    try {
        // Get the most recent upload ID
        $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD";
        $result = $conn->query($query);
        $latestUpload = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $latestUpload = $row['latest_upload'];
        }
        
        $query = "SELECT 
                    st.SourceName as traffic_source,
                    SUM(pdp.Value) as visit_count
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = $latestUpload
                  GROUP BY st.SourceName
                  ORDER BY visit_count DESC";
                  
        $result = $conn->query($query);
        
        if ($result) {
            // Calculate total visits
            $totalVisits = 0;
            $tempData = [];
            
            while ($row = $result->fetch_assoc()) {
                $tempData[] = $row;
                $totalVisits += $row['visit_count'];
            }
            
            // Calculate percentage for each source
            foreach ($tempData as $row) {
                $percentage = ($totalVisits > 0) ? 
                    round(($row['visit_count'] / $totalVisits) * 100, 2) : 0;
                    
                $data[] = [
                    'traffic_source' => $row['traffic_source'],
                    'visit_count' => $row['visit_count'],
                    'percentage' => $percentage
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting traffic sources: " . $e->getMessage());
    }
    
    return $data;
}

// Get top visited pages data (since you don't have page data, this is a placeholder)
function getTopVisitedPages($conn, $limit = 10) {
    $data = [];
    
    // Since your current schema doesn't track individual pages
    // This is a placeholder that returns source data instead
    try {
        // Get the most recent upload ID
        $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD";
        $result = $conn->query($query);
        $latestUpload = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $latestUpload = $row['latest_upload'];
        }
        
        // Modified query to only include data from latest upload
        $query = "SELECT 
                    st.SourceName as page_url,
                    SUM(pdp.Value) as page_views,
                    COUNT(DISTINCT pdp.SourceTypeID) as unique_visitors
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = $latestUpload
                  GROUP BY st.SourceName
                  ORDER BY page_views DESC
                  LIMIT $limit";
                  
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Make sure we have at least 1 visitor to avoid division by zero
                if ($row['unique_visitors'] < 1) {
                    $row['unique_visitors'] = 1;
                }
                $data[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting page data: " . $e->getMessage());
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
            return "A PHP extension stopped the file upload";
        default:
            return "Unknown upload error";
    }
}

function saveTransformedData($conn, $data) {
    error_log("SaveTransformedData received data: " . (is_array($data) ? count($data) : "not an array") . " items");
    
    if (empty($data)) {
        error_log("Error: No data rows to save in CSV file");
        
        // Delete the temporary file
        if (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv'])) {
            unlink($_SESSION['uploaded_csv']);
            unset($_SESSION['uploaded_csv']);
        }
        
        // Create a session message for the user
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if we already have validation errors in the session
        if (isset($_SESSION['upload_message']) && $_SESSION['upload_message']['type'] === 'error' 
            && strpos($_SESSION['upload_message']['message'], 'Data validation errors') !== false) {
            // Keep the existing validation error message as it's more specific
            return false;
        }

        $_SESSION['upload_message'] = [
            'type' => 'warning',
            'message' => "CSV file has correct format but contains no data rows to import."
        ];
        return false;
    }
    
    if (isset($data[0])) {
        error_log("First data row: " . json_encode($data[0]));
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // For testing/debugging, use a default user ID (1 for admin)
        $userId = 1;
        
        // Get CSV metadata from session if available
        // Only start session if one doesn't exist
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $metadata = $_SESSION['csv_metadata'] ?? [];
        error_log("Using metadata: " . json_encode($metadata));
        
        // First, create an entry in CSV_UPLOAD table
        $fileName = basename($_FILES['csvFile']['name'] ?? 'manual_upload.csv');
        $fileSize = $_FILES['csvFile']['size'] ?? 0;
        
        // Extract date information from metadata if available
        $startDate = isset($metadata['start_date']) && !empty($metadata['start_date']) 
            ? $metadata['start_date'] : date('Y-m-d');
        $endDate = isset($metadata['end_date']) && !empty($metadata['end_date'])
            ? $metadata['end_date'] : date('Y-m-d');
        $accountName = $metadata['account_name'] ?? '';
        $propertyName = $metadata['property_name'] ?? '';
        $reportType = $metadata['report_type'] ?? 'GA4 Traffic Acquisition';
        
        error_log("Creating CSV_UPLOAD record with dates: $startDate to $endDate, account: $accountName, property: $propertyName");
        
        // Log the CSV upload - FIXED parameter binding (8 parameters)
        $stmt = $conn->prepare("INSERT INTO CSV_UPLOAD 
            (UserID, FileName, FileSize, IsValidated, ReportType, 
             DataDateStart, DataDateEnd, AccountName, PropertyName, IsSampleData) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 0)");
        
        if (!$stmt) {
            error_log("Prepare statement error: " . $conn->error);
            throw new Exception("Failed to prepare CSV_UPLOAD statement: " . $conn->error);
        }
        
        // FIXED: Changed type string from 'isissss' to 'isisssss' to match 8 parameters
        $stmt->bind_param("isisssss", 
            $userId,
            $fileName,
            $fileSize,
            $reportType,
            $startDate,
            $endDate,
            $accountName,
            $propertyName
        );
        
        if (!$stmt->execute()) {
            error_log("Error creating CSV_UPLOAD record: " . $stmt->error);
            throw new Exception("Error creating upload record: " . $stmt->error);
        }
        
        $uploadId = $conn->insert_id;
        error_log("CSV Upload record created with ID: $uploadId");
        
        // Now process each data point
        foreach ($data as $row) {
            // Get source type ID
            $sourceType = $row['traffic_source'] ?? 'Unknown';
            $sourceTypeId = getSourceTypeId($conn, $sourceType);
            error_log("Processing source: $sourceType (ID: $sourceTypeId)");
            
            // Process each metric for this source
            if (isset($row['visits']) && $row['visits'] > 0) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Sessions', $row['visits'], $startDate);
            }
            
            if (isset($row['engaged_sessions']) && $row['engaged_sessions'] > 0) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engaged sessions', $row['engaged_sessions'], $startDate);
            }
            
            if (isset($row['bounce_rate'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engagement rate', $row['bounce_rate'], $startDate);
            }
            
            if (isset($row['avg_session_duration'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Average engagement time per session', $row['avg_session_duration'], $startDate);
            }
            
            if (isset($row['events_per_session'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Events per session', $row['events_per_session'], $startDate);
            }
            
            if (isset($row['event_count']) && $row['event_count'] > 0) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Event count', $row['event_count'], $startDate);
            }
            
            if (isset($row['key_events'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Key events', $row['key_events'], $startDate);
            }
            
            if (isset($row['session_key_event_rate'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Session key event rate', $row['session_key_event_rate'], $startDate);
            }
            
            if (isset($row['total_revenue'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Total revenue', $row['total_revenue'], $startDate);
            }
        }
        
        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        // Enhanced error logging
        error_log("Error saving data: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Helper function to get or create source type ID
function getSourceTypeId($conn, $sourceName) {
    // Try to get existing source type
    $stmt = $conn->prepare("SELECT SourceTypeID FROM SOURCE_TYPE WHERE SourceName = ?");
    $stmt->bind_param("s", $sourceName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['SourceTypeID'];
    }
    
    // If not exists, create new source type
    $stmt = $conn->prepare("INSERT INTO SOURCE_TYPE (SourceName) VALUES (?)");
    $stmt->bind_param("s", $sourceName);
    $stmt->execute();
    
    return $conn->insert_id;
}

// Helper function to get metric type ID
function getMetricTypeId($conn, $metricName) {
    $stmt = $conn->prepare("SELECT MetricTypeID FROM METRIC_TYPE WHERE MetricName = ?");
    $stmt->bind_param("s", $metricName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['MetricTypeID'];
    }
    
    // For safety, return null if no match (should be handled by caller)
    return null;
}

// Helper function to insert a data point
function insertDataPoint($conn, $uploadId, $sourceTypeId, $metricName, $value, $dataDate = null) {
    // Get metric type ID
    $metricTypeId = getMetricTypeId($conn, $metricName);
    
    if (!$metricTypeId) {
        error_log("Metric type not found: $metricName");
        return false;
    }
    
    // Use provided date or current date
    $dataDate = $dataDate ?? date('Y-m-d');
    
    // Default period type (can be customized if needed)
    $periodType = 'Daily';
    
    error_log("Inserting data point: Upload=$uploadId, Source=$sourceTypeId, Metric=$metricTypeId, Value=$value, Date=$dataDate");
    
    try {
        $stmt = $conn->prepare("INSERT INTO PROCESSED_DATA_POINT 
            (UploadID, SourceTypeID, MetricTypeID, DataDate, Value, PeriodType) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log("Prepare error: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iiisss", 
            $uploadId,
            $sourceTypeId,
            $metricTypeId,
            $dataDate,
            $value,
            $periodType
        );
        
        $result = $stmt->execute();
        if (!$result) {
            error_log("Execute error: " . $stmt->error);
        }
        return $result;
    } catch (Exception $e) {
        error_log("Exception in insertDataPoint: " . $e->getMessage());
        return false;
    }
}


// User Management Functions

/**
 * Get all users from the database
 * @param object $conn - Database connection
 * @return array - Array of users
 */
function getAllUsers($conn) {
    $users = [];
    
    try {
        $query = "SELECT UserID, Username, FullName, Email, Role, AccountStatus, CreatedAt 
                 FROM user 
                 ORDER BY UserID";
        
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching users: " . $e->getMessage());
    }
    
    return $users;
}

/**
 * Update user account status
 * @param object $conn - Database connection
 * @param int $userId - User ID to update
 * @param string $status - New status ('Active' or 'Suspended')
 * @return bool - True if successful, false otherwise
 */
function updateUserStatus($conn, $userId, $status) {
    try {
        // Validate status
        if (!in_array($status, ['Active', 'Suspended'])) {
            return false;
        }
        
        $stmt = $conn->prepare("UPDATE user SET AccountStatus = ? WHERE UserID = ?");
        $stmt->bind_param("si", $status, $userId);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating user status: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a user account
 * @param object $conn - Database connection
 * @param int $userId - User ID to delete
 * @return bool - True if successful, false otherwise
 */
function deleteUser($conn, $userId) {
    try {
        // Start transaction to handle related records
        $conn->begin_transaction();
        
        // Note: In a production system, you might want to:
        // 1. Archive the user data instead of deleting
        // 2. Handle related data (uploads, data points) by reassigning or deleting
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM user WHERE UserID = ?");
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        
        if ($result) {
            // Commit transaction
            $conn->commit();
            return true;
        } else {
            // Rollback on failure
            $conn->rollback();
            return false;
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}


?>