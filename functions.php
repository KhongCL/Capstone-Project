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
    
    // File validation
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
    if (!file_exists(__DIR__ . '/config/csv_mappings.json')) {
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
        file_put_contents(__DIR__ . '/config/csv_mappings.json', json_encode($defaultMappings, JSON_PRETTY_PRINT));
    }
    
    // Move uploaded file to a temporary location
    $uploadDir = __DIR__ . '/uploads/';
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
            
            // Check if this is an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($isAjax) {
                // Return JSON response for AJAX requests
                return [
                    'type' => 'needs_mapping',
                    'message' => 'CSV format requires manual column mapping',
                    'redirect' => 'map_columns.php'
                ];
            } else {
                // Redirect to mapping page for regular form submissions
                header('Location: map_columns.php');
                exit;
            }
        }else {
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
        
        // Return the full error message for AJAX handling
        return [
            'type' => 'error',
            'message' => $e->getMessage() // Don't add "Error: " prefix here
        ];
    }
}

function saveComparison($conn, $uploadId1, $uploadId2) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        $userId = $_SESSION['user_id'] ?? 1;
        $comparisonName = "Comparison " . date('Y-m-d H:i:s');
        
        // Create comparison record
        $stmt = $conn->prepare("INSERT INTO saved_comparison 
                              (UserID, ComparisonName) 
                              VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $comparisonName);
        $stmt->execute();
        $comparisonId = $conn->insert_id;
        
        // Link first file
        $stmt = $conn->prepare("INSERT INTO comparison_file_link 
                              (ComparisonID, UploadID, FileOrder) 
                              VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $comparisonId, $uploadId1);
        $stmt->execute();
        
        // Link second file
        $stmt = $conn->prepare("INSERT INTO comparison_file_link 
                              (ComparisonID, UploadID, FileOrder) 
                              VALUES (?, ?, 2)");
        $stmt->bind_param("ii", $comparisonId, $uploadId2);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving comparison: " . $e->getMessage());
        return false;
    }
}

function updateUploadProgress($stage, $percent, $message, $rowsProcessed = 0, $totalRows = 0) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['upload_progress'] = [
        'stage' => $stage,
        'percent' => $percent,
        'message' => $message,
        'rows_processed' => $rowsProcessed,
        'total_rows' => $totalRows,
        'timestamp' => time()
    ];
    
    // Write session data immediately to make it available for polling
    session_write_close();
    
    // Small delay to allow session to be written
    usleep(50000); // 50ms - increased from 10ms for better reliability
    
    // Restart session for continued use
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug logging
    error_log("Progress updated: Stage $stage, {$percent}%, $message");
}

function getKeyMetrics($conn, $uploadId = null) {
    $metrics = [
        'total_page_views' => 0,
        'unique_visitors' => 0,
        'avg_session_duration' => 0,
        'bounce_rate' => 65.0
    ];
    
    try {
        // ENHANCED: Use session upload ID first, then fall back to MAX
        if ($uploadId === null) {
            // Try to get from session first (for recently uploaded data)
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['latest_upload_id'])) {
                $uploadId = $_SESSION['latest_upload_id'];
                error_log("Using session upload ID: $uploadId");
            } else {
                // Fall back to getting the most recent upload ID for current user
                $userId = $_SESSION['user_id'] ?? 1;
                $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD WHERE UserID = ? AND IsValidated = 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    $uploadId = $row['latest_upload'];
                    error_log("Using latest upload ID for user $userId: $uploadId");
                }
            }
        }
        
        if (!$uploadId) {
            error_log("No upload ID found, returning default metrics");
            return $metrics;
        }
        
        // Get Sessions count for the specified upload
        $query = "SELECT SUM(pdp.Value) as total_views 
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName = 'Sessions'
                 AND pdp.UploadID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $metrics['total_page_views'] = $row['total_views'] ?: 0;
            error_log("Found total page views: " . $metrics['total_page_views']);
        }
        
        // Get unique visitors from Engaged sessions
        $query = "SELECT SUM(pdp.Value) as unique_visitors 
                FROM PROCESSED_DATA_POINT pdp
                JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                WHERE mt.MetricName = 'Engaged sessions'
                AND pdp.UploadID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $metrics['unique_visitors'] = $row['unique_visitors'] ?: 0;
            error_log("Found unique visitors: " . $metrics['unique_visitors']);
        }
        
        // Average Session Duration
        $query = "SELECT AVG(pdp.Value) as avg_duration
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName = 'Average engagement time per session'
                 AND pdp.UploadID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $avgSeconds = $row['avg_duration'] ?: 0;
            $metrics['avg_session_duration'] = round($avgSeconds, 1);
            error_log("Found avg session duration: " . $metrics['avg_session_duration']);
        }
        
        $query = "SELECT 
                    SUM(pdp.Value * sessions.session_count) as weighted_bounce_sum,
                    SUM(sessions.session_count) as total_sessions
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 JOIN (
                     SELECT pdp2.SourceTypeID, pdp2.Value as session_count 
                     FROM PROCESSED_DATA_POINT pdp2
                     JOIN METRIC_TYPE mt2 ON pdp2.MetricTypeID = mt2.MetricTypeID
                     WHERE mt2.MetricName = 'Sessions' AND pdp2.UploadID = ?
                 ) sessions ON pdp.SourceTypeID = sessions.SourceTypeID
                 WHERE mt.MetricName IN ('Engagement rate', 'Bounce Rate') 
                 AND pdp.UploadID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $uploadId, $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $totalSessions = $row['total_sessions'] ?: 1; // Prevent division by zero
            $weightedBounceSum = $row['weighted_bounce_sum'] ?: 0;
            
            // Calculate weighted average bounce rate
            $bounceRate = ($weightedBounceSum / $totalSessions) * 100;
            $metrics['bounce_rate'] = round($bounceRate, 2);
            error_log("Calculated weighted bounce rate: " . $metrics['bounce_rate'] . "%");
        }
        
    } catch (Exception $e) {
        error_log("Error getting metrics: " . $e->getMessage());
    }
    
    return $metrics;
}

// Get traffic over time data for charts
function getTrafficOverTime($conn, $interval = 'day', $uploadId = null) {
    $data = [];
    
    try {
        // If no uploadId provided, get the most recent upload ID
        if (!$uploadId) {
            $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD";
            $result = $conn->query($query);
            if ($result && $row = $result->fetch_assoc()) {
                $uploadId = $row['latest_upload'];
            }
        }
        
        // Get sessions data by date for the specified upload
        $query = "SELECT 
                    pdp.DataDate as time_period,
                    SUM(pdp.Value) as page_views,
                    COUNT(DISTINCT pdp.SourceTypeID) as unique_visitors
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = ?
                  GROUP BY pdp.DataDate
                  ORDER BY pdp.DataDate";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
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
        // ENHANCED: Use session upload ID first, then fall back to MAX
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $latestUpload = 0;
        if (isset($_SESSION['latest_upload_id'])) {
            $latestUpload = $_SESSION['latest_upload_id'];
            error_log("Using session upload ID for traffic sources: $latestUpload");
        } else {
            // Fall back to getting the most recent upload ID for current user
            $userId = $_SESSION['user_id'] ?? 1;
            $query = "SELECT MAX(UploadID) as latest_upload FROM CSV_UPLOAD WHERE UserID = ? AND IsValidated = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $latestUpload = $row['latest_upload'];
                error_log("Using latest upload ID for traffic sources (user $userId): $latestUpload");
            }
        }
        
        if (!$latestUpload) {
            error_log("No upload ID found for traffic sources");
            return $data;
        }
        
        $query = "SELECT 
                    st.SourceName as traffic_source,
                    SUM(pdp.Value) as visit_count
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = ?
                  GROUP BY st.SourceName
                  ORDER BY visit_count DESC";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $latestUpload);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            // Calculate total visits
            $totalVisits = 0;
            $tempData = [];
            
            while ($row = $result->fetch_assoc()) {
                $tempData[] = $row;
                $totalVisits += $row['visit_count'];
            }
            
            error_log("Found $totalVisits total visits from " . count($tempData) . " traffic sources");
            
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

function saveTransformedData($conn, $transformedData) {
    // Enhanced debugging
    error_log("=== SAVE TRANSFORMED DATA DEBUG ===");
    error_log("Received " . count($transformedData) . " transformed data rows");
    error_log("Sample transformed data: " . json_encode(array_slice($transformedData, 0, 2), JSON_PRETTY_PRINT));
    
    if (empty($transformedData)) {
        error_log("No transformed data provided to saveTransformedData");
        $_SESSION['upload_message'] = [
            'type' => 'error',
            'message' => 'No valid data to save. Please check your CSV file format.'
        ];
        return false;
    }

    try {
        $conn->begin_transaction();
        
        // For testing/debugging, use a default user ID (1 for admin)
        $userId = $_SESSION['user_id'] ?? 1;
        error_log("Using User ID: $userId");
        
        // Get CSV metadata from session if available
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $metadata = $_SESSION['csv_metadata'] ?? [];
        error_log("Using metadata: " . json_encode($metadata));
        
        // First, create an entry in CSV_UPLOAD table
        $fileName = basename($_SESSION['uploaded_csv'] ?? 'manual_upload.csv');
        $fileSize = file_exists($_SESSION['uploaded_csv']) ? filesize($_SESSION['uploaded_csv']) : 0;
        
        // Extract date information from metadata if available, otherwise use CSV data dates
        $startDate = isset($metadata['start_date']) && !empty($metadata['start_date']) 
            ? $metadata['start_date'] : '2024-02-01'; // From your test file
        $endDate = isset($metadata['end_date']) && !empty($metadata['end_date'])
            ? $metadata['end_date'] : '2024-02-28'; // From your test file
        
        $accountName = $metadata['account_name'] ?? 'TestAccount2';
        $propertyName = $metadata['property_name'] ?? 'TestProperty2';
        $reportType = $metadata['report_type'] ?? 'Manual Column Mapping';
        
        error_log("Creating CSV_UPLOAD record with dates: $startDate to $endDate, account: $accountName, property: $propertyName");
        
        // CRITICAL FIX: Delete any existing data for this user to prevent conflicts
        $deleteQuery = "DELETE pdp FROM PROCESSED_DATA_POINT pdp 
                       JOIN CSV_UPLOAD cu ON pdp.UploadID = cu.UploadID 
                       WHERE cu.UserID = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();
        error_log("Deleted existing data points for user: $userId");
        
        // Log the CSV upload
        $stmt = $conn->prepare("INSERT INTO CSV_UPLOAD 
            (UserID, FileName, FileSize, IsValidated, ReportType, 
             DataDateStart, DataDateEnd, AccountName, PropertyName, IsSampleData) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 0)");
        
        if (!$stmt) {
            error_log("Prepare statement error: " . $conn->error);
            throw new Exception("Failed to prepare CSV_UPLOAD statement: " . $conn->error);
        }
        
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
        
        // CRITICAL: Store the upload ID in session for immediate use
        $_SESSION['latest_upload_id'] = $uploadId;
        
        // Now process each data point with enhanced debugging
        $rowIndex = 0;
        foreach ($transformedData as $row) {
            $rowIndex++;
            error_log("Processing row $rowIndex: " . json_encode($row));
            
            // Get source type ID
            $sourceType = $row['traffic_source'] ?? 'Unknown';
            $sourceTypeId = getSourceTypeId($conn, $sourceType);
            error_log("Processing source: '$sourceType' (ID: $sourceTypeId)");
            
            // Process each metric for this source with enhanced logging and value validation
            if (isset($row['visits']) && is_numeric($row['visits']) && $row['visits'] > 0) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Sessions', $row['visits'], $startDate);
                error_log("Inserted Sessions data point: VALUE={$row['visits']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Sessions - invalid value: " . ($row['visits'] ?? 'NULL'));
            }
            
            if (isset($row['engaged_sessions']) && is_numeric($row['engaged_sessions']) && $row['engaged_sessions'] > 0) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engaged sessions', $row['engaged_sessions'], $startDate);
                error_log("Inserted Engaged sessions data point: VALUE={$row['engaged_sessions']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Engaged sessions - invalid value: " . ($row['engaged_sessions'] ?? 'NULL'));
            }
            
            if (isset($row['bounce_rate']) && is_numeric($row['bounce_rate'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engagement rate', $row['bounce_rate'], $startDate);
                error_log("Inserted Engagement rate data point: VALUE={$row['bounce_rate']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Bounce rate - invalid value: " . ($row['bounce_rate'] ?? 'NULL'));
            }
            
            if (isset($row['avg_session_duration']) && is_numeric($row['avg_session_duration'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Average engagement time per session', $row['avg_session_duration'], $startDate);
                error_log("Inserted Average engagement time data point: VALUE={$row['avg_session_duration']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Avg session duration - invalid value: " . ($row['avg_session_duration'] ?? 'NULL'));
            }
            
            if (isset($row['events_per_session']) && is_numeric($row['events_per_session'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Events per session', $row['events_per_session'], $startDate);
                error_log("Inserted Events per session data point: VALUE={$row['events_per_session']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Events per session - invalid value: " . ($row['events_per_session'] ?? 'NULL'));
            }
            
            if (isset($row['event_count']) && is_numeric($row['event_count']) && $row['event_count'] > 0) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Event count', $row['event_count'], $startDate);
                error_log("Inserted Event count data point: VALUE={$row['event_count']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Event count - invalid value: " . ($row['event_count'] ?? 'NULL'));
            }
            
            if (isset($row['key_events']) && is_numeric($row['key_events'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Key events', $row['key_events'], $startDate);
                error_log("Inserted Key events data point: VALUE={$row['key_events']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Key events - invalid value: " . ($row['key_events'] ?? 'NULL'));
            }
            
            if (isset($row['session_key_event_rate']) && is_numeric($row['session_key_event_rate'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Session key event rate', $row['session_key_event_rate'], $startDate);
                error_log("Inserted Session key event rate data point: VALUE={$row['session_key_event_rate']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Session key event rate - invalid value: " . ($row['session_key_event_rate'] ?? 'NULL'));
            }
            
            if (isset($row['total_revenue']) && is_numeric($row['total_revenue'])) {
                $result = insertDataPoint($conn, $uploadId, $sourceTypeId, 'Total revenue', $row['total_revenue'], $startDate);
                error_log("Inserted Total revenue data point: VALUE={$row['total_revenue']}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Skipping Total revenue - invalid value: " . ($row['total_revenue'] ?? 'NULL'));
            }
        }
        
        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully for upload ID: $uploadId");
        
        // Verify data was actually inserted
        $verifyQuery = "SELECT COUNT(*) as count, SUM(Value) as total_value FROM PROCESSED_DATA_POINT WHERE UploadID = ?";
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->bind_param("i", $uploadId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        if ($verifyRow = $verifyResult->fetch_assoc()) {
            error_log("VERIFICATION: Inserted {$verifyRow['count']} data points with total value {$verifyRow['total_value']}");
        }
        
        // CRITICAL: Clear any cached data
        if (isset($_SESSION['cached_metrics'])) {
            unset($_SESSION['cached_metrics']);
        }
        if (isset($_SESSION['cached_traffic_sources'])) {
            unset($_SESSION['cached_traffic_sources']);
        }
        
        error_log("=== END SAVE TRANSFORMED DATA DEBUG ===");
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error saving data: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $_SESSION['upload_message'] = [
            'type' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ];
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
    // Enhanced debugging
    error_log("insertDataPoint called: Upload=$uploadId, Source=$sourceTypeId, Metric='$metricName', Value=$value, Date=$dataDate");
    
    // Validate input
    if (!is_numeric($value)) {
        error_log("ERROR: Non-numeric value provided: $value");
        return false;
    }
    
    // Get metric type ID
    $metricTypeId = getMetricTypeId($conn, $metricName);
    
    if (!$metricTypeId) {
        error_log("Metric type not found: $metricName, creating it now");
        
        // If metric doesn't exist, create it
        $stmt = $conn->prepare("INSERT INTO METRIC_TYPE (MetricName, Description) VALUES (?, ?)");
        $description = "Automatically added from CSV import";
        $stmt->bind_param("ss", $metricName, $description);
        $stmt->execute();
        
        // Get the newly created metric type ID
        $metricTypeId = $conn->insert_id;
        error_log("Created new metric type with ID: $metricTypeId for: $metricName");
    }
    
    // Use provided date or current date
    $dataDate = $dataDate ?? date('Y-m-d');
    
    // Default period type (can be customized if needed)
    $periodType = 'Daily';
    
    // Convert value to proper decimal format
    $numericValue = floatval($value);
    
    error_log("Final values: Upload=$uploadId, Source=$sourceTypeId, Metric=$metricTypeId ($metricName), Value=$numericValue, Date=$dataDate");
    
    try {
        $stmt = $conn->prepare("INSERT INTO PROCESSED_DATA_POINT 
                              (UploadID, SourceTypeID, MetricTypeID, DataDate, Value, PeriodType) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log("Error preparing statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iiisds", 
                         $uploadId, 
                         $sourceTypeId, 
                         $metricTypeId, 
                         $dataDate, 
                         $numericValue, 
                         $periodType);
        
        $result = $stmt->execute();
        if (!$result) {
            error_log("Error executing statement: " . $stmt->error);
        } else {
            error_log("Successfully inserted data point with ID: " . $conn->insert_id);
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
        // FIXED: Remove FullName since it doesn't exist in your schema
        $query = "SELECT UserID, Username, Email, Role, AccountStatus, CreatedAt 
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