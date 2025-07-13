<?php
// Handle CSV file upload and import data to database
require_once 'classes/CsvProcessor.php';

// Replace the handleCsvUpload function:

function handleCsvUpload($conn, $file) {
    error_log("=== HANDLE CSV UPLOAD START ===");
    
    // CRITICAL FIX: Check if this is a comparison upload
    $isComparisonUpload = isset($_POST['comparison_context']) && $_POST['comparison_context'] === true;
    error_log("Is comparison upload: " . ($isComparisonUpload ? 'YES' : 'NO'));
    
    // CRITICAL FIX: Clear sample data session when user uploads their own file
    if (isset($_SESSION['using_sample_data']) && !$isComparisonUpload) {
        error_log("CRITICAL: User uploading new file - clearing sample data session");
        unset($_SESSION['using_sample_data']);
        unset($_SESSION['sample_upload_id']);
        
        // Clear cached data
        unset($_SESSION['cached_metrics']);
        unset($_SESSION['cached_traffic_sources']);
        unset($_SESSION['pages_data_quality']);
    }
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'type' => 'error',
            'message' => 'File upload failed: ' . getUploadErrorMessage($file['error'])
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
                "format_detection" => ["Sessions", "Engaged sessions", "Engagement rate"],
                "column_mappings" => [],
                "data_types" => []
            ]
        ];
        file_put_contents(__DIR__ . '/config/csv_mappings.json', json_encode($defaultMappings, JSON_PRETTY_PRINT));
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique hash-based filename to avoid conflicts
    $originalName = basename($file['name']);
    $shortHash = substr(hash('md5', $originalName . time()), 0, 8); // Only 8 characters
    $fileName = $shortHash . '_' . $originalName;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file to permanent location with unique name
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'type' => 'error',
            'message' => "Failed to save uploaded file."
        ];
    }

    // FIXED: Store file information in session for later use
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['uploaded_file_name'] = $fileName;
    $_SESSION['original_file_name'] = $originalName;
    $_SESSION['uploaded_file_size'] = $file['size']; // Store the actual file size

    error_log("STORED FILE INFO: original_name=$originalName, hashed_name=$fileName, size={$file['size']}");

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
            $_SESSION['uploaded_csv'] = $filePath;
            $_SESSION['uploaded_file_name'] = $fileName; // Use the unique filename with hash

            // CRITICAL FIX: Actually check the result from saveTransformedData
            $saveResult = saveTransformedData($conn, $transformedData);
            error_log("Save result: " . json_encode($saveResult));
            
            if ($saveResult['type'] === 'success') {
                // Check if there were validation warnings
                if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
                    $validationErrors = $_SESSION['validation_errors'];
                    $errorCount = count($validationErrors);
                    
                    // DON'T delete the file - keep it in uploads directory for future comparisons
                }
                
                // Normal success case (no warnings)
                // DON'T delete the file - keep it in uploads directory for future comparisons
                
                // CRITICAL: Clear ALL session data for clean state AND comparison data
                unset($_SESSION['uploaded_csv']);
                unset($_SESSION['csv_metadata']);
                unset($_SESSION['uploaded_file_name']);
                unset($_SESSION['uploaded_file_size']);
                
                // CRITICAL FIX: Clear comparison session data when doing regular upload
                unset($_SESSION['compare_files']);
                unset($_SESSION['compare_ready']);
                unset($_SESSION['compare_error']);
                unset($_SESSION['compare_file_1_upload_id']);
                unset($_SESSION['compare_file_2_upload_id']);
                error_log("CRITICAL: Cleared comparison session data for regular upload");

                return [
                    'type' => 'success',
                    'message' => 'CSV file successfully uploaded and processed.'
                ];
            } else {
                // CRITICAL: Return the actual error message from saveTransformedData
                error_log("Save failed with message: " . $saveResult['message']);
                
                // CRITICAL FIX: Clear session data when upload fails
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                // Clear the session data that would indicate existing data
                unset($_SESSION['latest_upload_id']);
                unset($_SESSION['using_sample_data']);
                unset($_SESSION['sample_upload_id']);
                
                // CRITICAL FIX: Also clear comparison session data on failure
                unset($_SESSION['compare_files']);
                unset($_SESSION['compare_ready']);
                unset($_SESSION['compare_error']);
                unset($_SESSION['compare_file_1_upload_id']);
                unset($_SESSION['compare_file_2_upload_id']);
                
                error_log("CRITICAL: Cleared session data due to upload failure");
                
                // Clean up file on error only
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                return [
                    'type' => 'error',
                    'message' => $saveResult['message']
                ];
            }
        } else if ($result['status'] === 'needs_mapping') {
            // Store file path and mapping info in session for the mapping page
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['uploaded_csv'] = $filePath;
            $_SESSION['mapping_result'] = $result;
            $_SESSION['csv_metadata'] = $metadata;
            
            error_log("MAPPING: Stored session data for mapping page - uploaded_csv: $filePath");
            
            // CRITICAL FIX: Clear sample data session when user uploads their own file
            if (isset($_SESSION['using_sample_data']) && !$isComparisonUpload) {
                unset($_SESSION['using_sample_data']);
                unset($_SESSION['sample_upload_id']);
                error_log("CRITICAL: Cleared sample data session for manual mapping");
            }
            
            // CRITICAL FIX: Only clear comparison session data for regular uploads
            if (!$isComparisonUpload) {
                unset($_SESSION['compare_files']);
                unset($_SESSION['compare_ready']);
                unset($_SESSION['compare_error']);
                unset($_SESSION['compare_file_1_upload_id']);
                unset($_SESSION['compare_file_2_upload_id']);
                error_log("CRITICAL: Cleared comparison session data for regular upload needing mapping");
            } else {
                error_log("COMPARISON: Preserving session data for comparison upload");
            }

            // Check if this is an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            // IMPROVED: More precise comparison context detection
            $isComparison = $isComparisonUpload;

            // CRITICAL FIX: Handle session clearing differently based on context
            if (!$isComparison) {
                // CRITICAL FIX: For regular uploads, preserve mapping session data
                // These variables are needed by map_columns.php:
                // - $_SESSION['uploaded_csv'] (file path)
                // - $_SESSION['mapping_result'] (mapping analysis)
                // - $_SESSION['csv_metadata'] (file metadata)
                
                // Only clear latest_upload_id since no upload record was created yet
                unset($_SESSION['latest_upload_id']);
                error_log("MAPPING: Preserved session data for regular mapping page, cleared only latest_upload_id");
            } else {
                // CRITICAL FIX: For comparison uploads, don't clear session data
                // The session data will be handled by compare.php
                error_log("COMPARISON: Preserving all session data for comparison upload");
            }

            if ($isAjax) {
                return [
                    'type' => 'needs_mapping',
                    'message' => 'Manual column mapping required.',
                    'redirect' => $isComparison ? 'map_columns_compare.php?file=1' : 'map_columns.php'
                ];
            } else {
                // CRITICAL FIX: Different handling for comparison vs regular uploads
                if ($isComparison) {
                    // CRITICAL FIX: For comparison uploads, return result instead of redirecting
                    // Let compare.php handle the redirect logic
                    return [
                        'type' => 'needs_mapping',
                        'message' => 'Manual column mapping required.',
                        'original_filename' => $originalName,
                        'clean_filename' => $originalName,
                        'file_path' => $filePath,
                        'needs_mapping' => true
                    ];
                } else {
                    // For regular uploads, redirect to mapping page
                    error_log("REGULAR UPLOAD REDIRECT: Redirecting to map_columns.php");
                    header('Location: map_columns.php');
                    exit();
                }
            }
        } else {
            // Clean up file since there was an error
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return [
                'type' => 'error',
                'message' => $result['message'] ?? 'Unknown error processing CSV file.'
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
            'message' => $e->getMessage()
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

// Update getKeyMetrics function to use sample-aware logic
function getKeyMetrics($conn, $uploadId = null) {
    $metrics = [
        'total_page_views' => 0,
        'unique_visitors' => 'N/A',
        'avg_session_duration' => 'N/A',
        'bounce_rate' => 'N/A'
    ];
    
    try {
        // If no uploadId provided, get it using the sample-aware function
        if ($uploadId === null) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return $metrics;
            }
            
            $uploadId = getCurrentUploadId($conn, $userId);
        }
        
        if (!$uploadId) {
            error_log("No upload ID found for getKeyMetrics");
            return $metrics;
        }
        
        error_log("📊 Getting metrics for Upload ID: $uploadId");
        
        // 1. Get Page Views - prioritize correctly
        $query = "SELECT mt.MetricName
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName IN ('Page Views', 'Sessions', 'visits')
                 AND pdp.UploadID = ?
                 ORDER BY CASE 
                     WHEN mt.MetricName = 'Page Views' THEN 1
                     WHEN mt.MetricName = 'Sessions' THEN 2
                     WHEN mt.MetricName = 'visits' THEN 3
                 END
                 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $selectedMetric = $row['MetricName'];
            
            $sumQuery = "SELECT SUM(pdp.Value) as total_views 
                        FROM PROCESSED_DATA_POINT pdp
                        JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                        WHERE mt.MetricName = ? AND pdp.UploadID = ?";
            $sumStmt = $conn->prepare($sumQuery);
            $sumStmt->bind_param("si", $selectedMetric, $uploadId);
            $sumStmt->execute();
            $sumResult = $sumStmt->get_result();
            
            if ($sumResult && $sumRow = $sumResult->fetch_assoc()) {
                $metrics['total_page_views'] = $sumRow['total_views'] ?: 0;
            }
        }
        
        // 2. Get unique visitors
        $query = "SELECT mt.MetricName
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName IN ('Users', 'Unique visitors', 'visitors', 'Engaged sessions')
                 AND pdp.UploadID = ?
                 ORDER BY CASE 
                     WHEN mt.MetricName = 'Users' THEN 1
                     WHEN mt.MetricName = 'Unique visitors' THEN 2
                     WHEN mt.MetricName = 'visitors' THEN 3
                     WHEN mt.MetricName = 'Engaged sessions' THEN 4
                 END
                 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $selectedMetric = $row['MetricName'];
            
            $sumQuery = "SELECT SUM(pdp.Value) as unique_visitors 
                        FROM PROCESSED_DATA_POINT pdp
                        JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                        WHERE mt.MetricName = ? AND pdp.UploadID = ?";
            $sumStmt = $conn->prepare($sumQuery);
            $sumStmt->bind_param("si", $selectedMetric, $uploadId);
            $sumStmt->execute();
            $sumResult = $sumStmt->get_result();
            
            if ($sumResult && $sumRow = $sumResult->fetch_assoc()) {
                $uniqueVisitors = $sumRow['unique_visitors'] ?: 0;
                if ($uniqueVisitors > 0) {
                    $metrics['unique_visitors'] = $uniqueVisitors;
                }
            }
        }
        
        // 3. Average Session Duration
        $query = "SELECT mt.MetricName
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName IN ('Avg Time on Site', 'Average engagement time per session', 'avg_session_duration')
                 AND pdp.UploadID = ?
                 ORDER BY CASE 
                     WHEN mt.MetricName = 'Avg Time on Site' THEN 1
                     WHEN mt.MetricName = 'Average engagement time per session' THEN 2
                     WHEN mt.MetricName = 'avg_session_duration' THEN 3
                 END
                 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $selectedMetric = $row['MetricName'];
            
            $avgQuery = "SELECT AVG(pdp.Value) as avg_duration
                        FROM PROCESSED_DATA_POINT pdp
                        JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                        WHERE mt.MetricName = ? AND pdp.UploadID = ?";
            $avgStmt = $conn->prepare($avgQuery);
            $avgStmt->bind_param("si", $selectedMetric, $uploadId);
            $avgStmt->execute();
            $avgResult = $avgStmt->get_result();
            
            if ($avgResult && $avgRow = $avgResult->fetch_assoc()) {
                $avgSeconds = $avgRow['avg_duration'] ?: 0;
                if ($avgSeconds > 0) {
                    $metrics['avg_session_duration'] = round($avgSeconds, 1);
                }
            }
        }
        
        // 4. Bounce rate
        $query = "SELECT AVG(pdp.Value) as avg_engagement_rate
                 FROM PROCESSED_DATA_POINT pdp
                 JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                 WHERE mt.MetricName IN ('Engagement rate', 'Bounce Rate') 
                 AND pdp.UploadID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $engagementRate = $row['avg_engagement_rate'] ?: 0;
            if ($engagementRate > 0) {
                $bounceRate = (1 - $engagementRate) * 100;
                $metrics['bounce_rate'] = round($bounceRate, 1);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error getting metrics: " . $e->getMessage());
    }
    
    return $metrics;
}

// Update getTrafficOverTime function
function getTrafficOverTime($conn, $interval = 'day', $uploadId = null) {
    $data = [];
    
    try {
        // If no uploadId provided, get it using the sample-aware function
        if (!$uploadId) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return $data;
            }
            
            $uploadId = getCurrentUploadId($conn, $userId);
        }
        
        if (!$uploadId) {
            return $data;
        }
        
        // Get sessions data by date
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

// Update getTrafficSourcesDistribution function
function getTrafficSourcesDistribution($conn, $uploadId = null) {
    $data = [];
    
    try {
        // If no uploadId provided, get it using the sample-aware function
        if (!$uploadId) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return $data;
            }
            
            $uploadId = getCurrentUploadId($conn, $userId);
        }
        
        if (!$uploadId) {
            error_log("No upload ID found for traffic sources");
            return $data;
        }
        
        error_log("Getting traffic sources for upload ID: $uploadId");
        
        // Use the correct column name: SourceName
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
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $totalVisits = 0;
            $tempData = [];
            
            while ($row = $result->fetch_assoc()) {
                $tempData[] = $row;
                $totalVisits += $row['visit_count'];
                error_log("Found traffic source: " . $row['traffic_source'] . " with " . $row['visit_count'] . " visits");
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
            
            error_log("Final traffic sources data: " . json_encode($data));
        }
    } catch (Exception $e) {
        error_log("Error getting traffic sources: " . $e->getMessage());
    }
    
    return $data;
}

// Get top visited pages data (since you don't have page data, this is a placeholder)
function getTopVisitedPages($conn, $limit = 10) {
    $data = [];
    $dataQuality = [
        'source_type' => 'unknown',
        'estimation_method' => null,
        'confidence_level' => 'high'
    ];
    
    try {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $data;
        }
        
        $latestUpload = getCurrentUploadId($conn, $userId);
        
        if (!$latestUpload) {
            error_log("No upload ID found for pages");
            return $data;
        }
        
        error_log("Getting pages data for upload ID: $latestUpload");
        
        // Get total unique visitors from overview metrics to maintain consistency
        $overviewMetrics = getKeyMetrics($conn, $latestUpload);
        $totalEstimatedVisitors = $overviewMetrics['unique_visitors'];
        
        // Get sessions data (same as before)
        $query = "SELECT 
                    st.SourceName as page_url,
                    SUM(pdp.Value) as page_views
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE mt.MetricName = 'Sessions'
                  AND pdp.UploadID = ?
                  GROUP BY st.SourceName
                  HAVING page_views > 0
                  ORDER BY page_views DESC
                  LIMIT ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $latestUpload, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $totalSessions = 0;
            $tempData = [];
            
            // First pass: collect data and calculate totals
            while ($row = $result->fetch_assoc()) {
                $tempData[] = $row;
                $totalSessions += $row['page_views'];
            }
            
            // Second pass: distribute estimated visitors proportionally
            if ($totalEstimatedVisitors !== 'N/A' && $totalSessions > 0) {
                $distributedVisitors = 0;
                $processedPages = [];
                
                // Calculate proportional visitors for each page (except the last one)
                for ($i = 0; $i < count($tempData) - 1; $i++) {
                    $row = $tempData[$i];
                    $proportionalVisitors = round(($row['page_views'] / $totalSessions) * $totalEstimatedVisitors);
                    
                    // Ensure at least 1 visitor per page
                    if ($proportionalVisitors < 1) {
                        $proportionalVisitors = 1;
                    }
                    
                    $processedPages[] = [
                        'page_url' => $row['page_url'],
                        'page_views' => $row['page_views'],
                        'unique_visitors' => $proportionalVisitors
                    ];
                    
                    $distributedVisitors += $proportionalVisitors;
                }
                
                // For the last page, assign the remainder to ensure exact total
                if (count($tempData) > 0) {
                    $lastRow = $tempData[count($tempData) - 1];
                    $remainingVisitors = $totalEstimatedVisitors - $distributedVisitors;
                    
                    // Ensure at least 1 visitor for the last page
                    if ($remainingVisitors < 1) {
                        $remainingVisitors = 1;
                        // Adjust one of the previous pages to maintain total
                        if (count($processedPages) > 0 && $processedPages[0]['unique_visitors'] > 1) {
                            $processedPages[0]['unique_visitors']--;
                        }
                    }
                    
                    $processedPages[] = [
                        'page_url' => $lastRow['page_url'],
                        'page_views' => $lastRow['page_views'],
                        'unique_visitors' => $remainingVisitors
                    ];
                }
                
                $data = $processedPages;
                
                // Verify total for logging
                $actualTotal = array_sum(array_column($data, 'unique_visitors'));
                error_log("Proportional distribution: Target=$totalEstimatedVisitors, Actual=$actualTotal");
                
                $dataQuality = [
                    'source_type' => 'estimated',
                    'estimation_method' => 'proportional_distribution',
                    'confidence_level' => 'medium'
                ];
            } else {
                // Fallback to individual estimation if overview data unavailable
                foreach ($tempData as $row) {
                    $estimatedVisitors = round($row['page_views'] * 0.7);
                    if ($estimatedVisitors < 1) {
                        $estimatedVisitors = 1;
                    }
                    
                    $data[] = [
                        'page_url' => $row['page_url'],
                        'page_views' => $row['page_views'],
                        'unique_visitors' => $estimatedVisitors
                    ];
                }
                
                $dataQuality = [
                    'source_type' => 'estimated',
                    'estimation_method' => 'sessions_70_percent_rule',
                    'confidence_level' => 'low'
                ];
            }
            
            error_log("Pages data processed with " . count($data) . " pages");
        }
    } catch (Exception $e) {
        error_log("Error getting pages data: " . $e->getMessage());
    }
    
    // Store data quality info
    $_SESSION['pages_data_quality'] = $dataQuality;
    
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
    error_log("=== SAVE TRANSFORMED DATA DEBUG ===");
    error_log("Received " . count($transformedData) . " transformed data rows");
    
    // CRITICAL: Check for empty data and return proper error
    if (empty($transformedData)) {
        error_log("ERROR: No transformed data received - likely validation errors");
        
        // CRITICAL FIX: Check if we have validation errors in session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if we have validation errors in session
        if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
            $validationErrors = $_SESSION['validation_errors'];
            error_log("Found validation errors in session: " . print_r($validationErrors, true));
            
            // NEW: Store file information for re-upload display  
            $_SESSION['failed_file_info'] = [
                'name' => $_SESSION['original_file_name'] ?? ($_SESSION['uploaded_file_name'] ?? 'unknown.csv'),
                'size' => $_SESSION['uploaded_file_size'] ?? (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv']) ? filesize($_SESSION['uploaded_csv']) : 0),
                'mapped_columns' => count($columnMapping ?? []),
                'total_columns' => count($csvHeaders ?? [])
            ];
            
            // CRITICAL FIX: Format validation errors with proper separation and count
            $uniqueErrors = array_unique($validationErrors);
            $errorCount = count($validationErrors);
            $uniqueCount = count($uniqueErrors);

            // Create a clean, well-formatted error message - SHOW ALL ERRORS
            $errorMessage = "Found $errorCount validation errors in your CSV file:\n\n";
            foreach ($validationErrors as $error) {
                $errorMessage .= $error . "\n";
            }

            // DON'T clear validation errors here - let map_columns_compare.php handle them
            // IMPORTANT: Don't clear session data here - let the user see the errors and try again
            return ['type' => 'error', 'message' => $errorMessage];
        }
        
        // Only clear session data if there are no validation errors (complete failure)
        unset($_SESSION['latest_upload_id']);
        unset($_SESSION['using_sample_data']);
        unset($_SESSION['sample_upload_id']);
        error_log("CRITICAL: Cleared session data due to complete upload failure");
        
        return ['type' => 'error', 'message' => 'No valid data found after processing. Please check your CSV file for errors.'];
    }
    
    // Log sample data
    error_log("Sample transformed data: " . json_encode(array_slice($transformedData, 0, 2), JSON_PRETTY_PRINT));

    try {
        $conn->begin_transaction();
        
        // Get user ID from session
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            throw new Exception('User not logged in');
        }
        error_log("Using User ID: $userId");
        
        // Get metadata from session
        $metadata = $_SESSION['csv_metadata'] ?? [];
        error_log("Using metadata: " . json_encode($metadata));
        
        // Set default dates if not provided
        $startDate = $metadata['start_date'] ?? '2024-02-01';
        $endDate = $metadata['end_date'] ?? '2024-02-28';
        $accountName = $metadata['account_name'] ?? 'TestAccount2';
        $propertyName = $metadata['property_name'] ?? 'TestProperty2';
        $reportType = $metadata['report_type'] ?? 'Custom Analytics';
        
        error_log("Creating CSV_UPLOAD record with dates: $startDate to $endDate, account: $accountName, property: $propertyName");
        
        // NO CLEANUP - just insert new upload record directly
        error_log("User $userId uploading new file - no cleanup performed");
        
        
        // Insert NEW CSV upload record
        $stmt = $conn->prepare("INSERT INTO CSV_UPLOAD (UserID, FileName, FileSize, IsValidated, ReportType, DataDateStart, DataDateEnd, AccountName, PropertyName) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $fileName = $_SESSION['uploaded_file_name'] ?? 'manual_upload.csv';

        // FIXED: Get the actual file size from the uploaded file
        $fileSize = 0;
        if (isset($_SESSION['uploaded_file_size'])) {
            $fileSize = $_SESSION['uploaded_file_size'];
        } else {
            // Fallback: try to get file size from the uploaded file if it still exists
            if (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv'])) {
                $fileSize = filesize($_SESSION['uploaded_csv']);
            }
        }

        // Debug logging
        error_log("Binding parameters: UserID=$userId, FileName=$fileName, FileSize=$fileSize, ReportType=$reportType");

        // FIXED: Correct the bind_param type string
        $bindResult = $stmt->bind_param("isisssss", $userId, $fileName, $fileSize, $reportType, $startDate, $endDate, $accountName, $propertyName);

        if (!$bindResult) {
            throw new Exception('Bind failed: ' . $stmt->error);
        }

        $executeResult = $stmt->execute();

        if (!$executeResult) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        $uploadId = $conn->insert_id;
        error_log("CSV Upload record created with ID: $uploadId");
        
        // CRITICAL: Store the NEW upload ID in session immediately
        $_SESSION['latest_upload_id'] = $uploadId;
        
        // Process each row of transformed data
        $rowIndex = 0;
        foreach ($transformedData as $row) {
            $rowIndex++;
            error_log("Processing row $rowIndex: " . json_encode($row));
            
            // Get or create source type
            $sourceName = $row['traffic_source'] ?? 'Unknown';
            $sourceTypeId = getSourceTypeId($conn, $sourceName);
            error_log("Processing source: '$sourceName' (ID: $sourceTypeId)");
            
            // Process ALL the metrics properly - FIXED: Allow 0 values
            $metricsToProcess = [
                'visits' => 'Sessions',
                'unique_visitors' => 'Users', 
                'page_views' => 'Page Views',
                'engaged_sessions' => 'Engaged sessions',
                'bounce_rate' => 'Engagement rate',
                'avg_session_duration' => 'Average engagement time per session',
                'events_per_session' => 'Events per session',
                'event_count' => 'Event count',
                'key_events' => 'Key events',
                'session_key_event_rate' => 'Session key event rate',
                'total_revenue' => 'Total revenue'
            ];
            
            foreach ($metricsToProcess as $rowKey => $metricName) {
                // FIXED: Allow 0 values - only check if numeric and exists
                if (isset($row[$rowKey]) && is_numeric($row[$rowKey])) {
                    $result = insertDataPoint($conn, $uploadId, $sourceTypeId, $metricName, $row[$rowKey], $startDate);
                    error_log("Inserted $metricName data point: VALUE={$row[$rowKey]}, RESULT=" . ($result ? 'SUCCESS' : 'FAILED'));
                } else {
                    $value = $row[$rowKey] ?? 'NULL';
                    error_log("Skipping $metricName - invalid value: $value");
                }
            }
        }
        
        $conn->commit();
        error_log("Transaction committed successfully for upload ID: $uploadId");
        
        // Verification
        $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(Value) as total_value FROM PROCESSED_DATA_POINT WHERE UploadID = ?");
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification = $result->fetch_assoc();
        error_log("VERIFICATION: Inserted {$verification['count']} data points with total value {$verification['total_value']}");
        
        // CRITICAL: Clear any cached session data to force refresh
        if (isset($_SESSION['cached_metrics'])) {
            unset($_SESSION['cached_metrics']);
        }
        if (isset($_SESSION['cached_traffic_sources'])) {
            unset($_SESSION['cached_traffic_sources']);
        }
        
        error_log("=== END SAVE TRANSFORMED DATA DEBUG ===");
        
        return ['type' => 'success', 'message' => 'CSV data successfully imported and processed.'];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in saveTransformedData: " . $e->getMessage());
        return ['type' => 'error', 'message' => 'Error saving data: ' . $e->getMessage()];
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
    
    // Validate input - FIXED: Allow 0 values, only reject non-numeric values
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
    
    // Convert value to proper decimal format - FIXED: Allow 0 values
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

/**
 * Get current upload ID (sample-aware)
 */
function getCurrentUploadId($conn, $userId) {
    error_log("=== GET CURRENT UPLOAD ID DEBUG ===");
    error_log("User ID: $userId");
    error_log("Session using_sample_data: " . (isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'));
    error_log("Session sample_upload_id: " . ($_SESSION['sample_upload_id'] ?? 'not set'));
    
    // Check if user is using sample data
    if (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data'] === true && isset($_SESSION['sample_upload_id'])) {
        $sampleUploadId = $_SESSION['sample_upload_id'];
        error_log("Using sample data with UploadID: $sampleUploadId");
        
        // Verify the sample upload exists and is valid
        $stmt = $conn->prepare("SELECT UploadID, FileName, AccountName, PropertyName FROM csv_upload WHERE UploadID = ? AND IsSampleData = 1");
        $stmt->bind_param("i", $sampleUploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            error_log("Sample data verified: " . json_encode($row));
            error_log("=== END GET CURRENT UPLOAD ID (SAMPLE) ===");
            return $sampleUploadId;
        } else {
            error_log("WARNING: Sample upload ID $sampleUploadId not found or not sample data, falling back to user data");
        }
    }
    
    // Get most recent user upload
    $stmt = $conn->prepare("SELECT UploadID, FileName FROM csv_upload WHERE UserID = ? AND (IsSampleData = 0 OR IsSampleData IS NULL) ORDER BY UploadDate DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $uploadId = $row ? $row['UploadID'] : null;
    error_log("User upload ID: " . ($uploadId ?? 'NULL'));
    if ($row) {
        error_log("User upload file: " . $row['FileName']);
    }
    error_log("=== END GET CURRENT UPLOAD ID (USER) ===");
    
    return $uploadId;
}

/**
 * Check if current data is sample data
 */
function isUsingSampleData() {
    return isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data'] === true;
}

/**
 * Get sample data notice for display
 */
function getSampleDataNotice() {
    error_log("=== GET SAMPLE DATA NOTICE DEBUG ===");
    error_log("Session using_sample_data: " . (isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'));
    
    if (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data'] === true) {
        // Determine the current page to handle redirects properly
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        // Use index.php as the central handler for clearing sample data
        $clearUrl = ($currentPage === 'index.php') ? '?clear_sample=1' : 'index.php?clear_sample=1';
        
        $notice = [
            'is_sample' => true,
            'message' => 'You\'re currently viewing sample data to explore TrafAnalyz features.',
            'action' => '<a href="' . $clearUrl . '" class="notice-content-btn">Switch to Your Data</a>'
        ];
        error_log("Returning sample notice: " . json_encode($notice));
        error_log("=== END GET SAMPLE DATA NOTICE (SAMPLE) ===");
        return $notice;
    }
    
    error_log("Not using sample data, returning no notice");
    error_log("=== END GET SAMPLE DATA NOTICE (NO SAMPLE) ===");
    return ['is_sample' => false];
}

function handleCsvUploadForComparison($conn, $file) {
    // CRITICAL FIX: Set comparison context flag BEFORE calling handleCsvUpload
    $_POST['comparison_context'] = true;
    
    // CRITICAL FIX: Store original filename and create unique identifier for comparison context
    $originalFileName = $file['name'];
    $comparisonFileId = uniqid('comp_', true); // Create unique ID for this comparison file
    
    error_log("COMPARISON: Processing file with original name: $originalFileName, ID: $comparisonFileId");
    
    // CRITICAL FIX: Store comparison context in session before processing
    if (!isset($_SESSION['comparison_context'])) {
        $_SESSION['comparison_context'] = [];
    }
    $_SESSION['comparison_context'][$comparisonFileId] = [
        'original_name' => $originalFileName,
        'processed' => false
    ];
    
    // Use the same logic as handleCsvUpload but with comparison context
    $result = handleCsvUpload($conn, $file);
    
    // CRITICAL FIX: Update comparison context after processing
    $_SESSION['comparison_context'][$comparisonFileId]['processed'] = true;
    $_SESSION['comparison_context'][$comparisonFileId]['result'] = $result;
    
    // CRITICAL FIX: Ensure clean filename is preserved in result
    $result['original_filename'] = $originalFileName;
    $result['clean_filename'] = $originalFileName;
    $result['comparison_id'] = $comparisonFileId;
    
    // Make sure we return the upload_id if available
    if (isset($_SESSION['latest_upload_id'])) {
        $result['upload_id'] = $_SESSION['latest_upload_id'];
        error_log("Added upload_id to result: " . $_SESSION['latest_upload_id']);
    }
    
    // CRITICAL FIX: Add file path to result if available
    if (isset($_SESSION['uploaded_file_name'])) {
        $result['file_path'] = __DIR__ . '/uploads/' . $_SESSION['uploaded_file_name'];
        error_log("COMPARISON: Added file_path to result: " . $result['file_path']);
    }
    
    return $result;
}
?>