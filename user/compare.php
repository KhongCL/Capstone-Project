<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set page variables for header
$title = "Compare Analytics";
$active_page = "compare";

// Database connection and functions - Updated to match your other files
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';
require_once '../classes/CsvProcessor.php';

$userID = $_SESSION['user_id']; // Make sure userID is defined

$comparison_results = null;
$error_message = null;
$success_message = null;

// Get user's validated CSV uploads using MySQLi
$stmt = $conn->prepare("SELECT UploadID, FileName, UploadDate FROM csv_upload WHERE UserID = ? AND IsValidated = 1 ORDER BY UploadDate DESC");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$uploads = $result->fetch_all(MYSQLI_ASSOC);

// Handle file upload and comparison using the same validation as index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file1']) && isset($_FILES['csv_file2'])) {
    $file1 = $_FILES['csv_file1'];
    $file2 = $_FILES['csv_file2'];
    
    try {
        // Clear any previous comparison session data
        unset($_SESSION['compare_files']);
        unset($_SESSION['compare_ready']);
        unset($_SESSION['compare_error']);
        unset($_SESSION['comparison_context']); // CRITICAL FIX: Clear comparison context
        
        // CRITICAL FIX: Process files separately without session interference
        error_log("=== PROCESSING FILE 1 ===");
        $upload_result1 = handleCsvUploadForComparison($conn, $file1);
        
        // CRITICAL FIX: Clear any session state that might interfere with second file
        $file1_session_state = [
            'uploaded_csv' => $_SESSION['uploaded_csv'] ?? null,
            'latest_upload_id' => $_SESSION['latest_upload_id'] ?? null,
            'uploaded_file_name' => $_SESSION['uploaded_file_name'] ?? null
        ];
        
        // Clear session state for second file processing
        unset($_SESSION['uploaded_csv']);
        unset($_SESSION['latest_upload_id']);
        unset($_SESSION['uploaded_file_name']);
        
        error_log("=== PROCESSING FILE 2 ===");
        $upload_result2 = handleCsvUploadForComparison($conn, $file2);
        
        // ENHANCED DEBUGGING: Log detailed results
        error_log("=== COMPARISON FILE PROCESSING RESULTS ===");
        error_log("File 1 result: " . json_encode($upload_result1));
        error_log("File 2 result: " . json_encode($upload_result2));
        
        // Initialize variables for different scenarios
        $file1_valid = ($upload_result1['type'] === 'success' || $upload_result1['type'] === 'warning');
        $file2_valid = ($upload_result2['type'] === 'success' || $upload_result2['type'] === 'warning');
        $file1_needs_mapping = ($upload_result1['type'] === 'needs_mapping');
        $file2_needs_mapping = ($upload_result2['type'] === 'needs_mapping');
        
        error_log("COMPARISON: File 1 processed - valid: " . ($file1_valid ? 'YES' : 'NO') . ", needs_mapping: " . ($file1_needs_mapping ? 'YES' : 'NO') . ", upload_id: " . ($upload_result1['upload_id'] ?? 'NULL'));
        error_log("COMPARISON: File 2 processed - valid: " . ($file2_valid ? 'YES' : 'NO') . ", needs_mapping: " . ($file2_needs_mapping ? 'YES' : 'NO') . ", upload_id: " . ($upload_result2['upload_id'] ?? 'NULL'));
        
        // CRITICAL FIX: Build comparison session structure properly
        $_SESSION['compare_files'] = [
            1 => [
                'name' => $upload_result1['clean_filename'] ?? $file1['name'],
                'upload_id' => $upload_result1['upload_id'] ?? null,
                'needs_mapping' => $file1_needs_mapping,
                'mapped' => $file1_valid,
                'path' => $file1_session_state['uploaded_csv'] ?? ($upload_result1['file_path'] ?? null),
                'result' => $file1_needs_mapping ? $upload_result1 : null
            ],
            2 => [
                'name' => $upload_result2['clean_filename'] ?? $file2['name'],
                'upload_id' => $upload_result2['upload_id'] ?? null,
                'needs_mapping' => $file2_needs_mapping,
                'mapped' => $file2_valid,
                'path' => $_SESSION['uploaded_csv'] ?? ($upload_result2['file_path'] ?? null),
                'result' => $file2_needs_mapping ? $upload_result2 : null
            ]
        ];
        
        // Handle errors for failed files
        if (!$file1_valid && !$file1_needs_mapping) {
            $_SESSION['compare_files'][1]['error'] = $upload_result1['message'] ?? 'Unknown error';
        }
        if (!$file2_valid && !$file2_needs_mapping) {
            $_SESSION['compare_files'][2]['error'] = $upload_result2['message'] ?? 'Unknown error';
        }
        
        error_log("Updated compare_files session: " . json_encode($_SESSION['compare_files']));
        
        session_write_close();
        session_start();
        error_log("Session data written and restarted before redirect logic");
        
        // Handle all 8 scenarios (4 original + 4 with mapping)
        if ($file1_valid && $file2_valid) {
            // CRITICAL FIX: Use upload IDs instead of file paths for comparison
            $uploadId1 = $upload_result1['upload_id'] ?? null;
            $uploadId2 = $upload_result2['upload_id'] ?? null;
            
            error_log("COMPARISON: Upload IDs - File 1: $uploadId1, File 2: $uploadId2");
            
            if ($uploadId1 && $uploadId2) {
                try {
                    // FIXED: Pass upload IDs instead of file paths
                    $comparison_results = compareCSVFiles($uploadId1, $uploadId2);
                    
                    error_log("COMPARISON: Successfully compared upload IDs $uploadId1 and $uploadId2");
                    $success_message = "Files compared successfully!";
                } catch (Exception $e) {
                    error_log("COMPARISON ERROR: " . $e->getMessage());
                    $error_message = "Error comparing files: " . $e->getMessage();
                }
            } else {
                error_log("ERROR: Missing upload IDs - File 1: $uploadId1, File 2: $uploadId2");
                $error_message = "Upload IDs not found for comparison.";
            }
        } elseif ($file1_needs_mapping && $file2_valid) {
            error_log("Scenario 2: File 1 needs mapping, File 2 is valid");
            header('Location: map_columns_compare.php?file=1');
            exit;
        } elseif ($file1_valid && $file2_needs_mapping) {
            error_log("Scenario 3: File 1 is valid, File 2 needs mapping");
            header('Location: map_columns_compare.php?file=2');
            exit;
        } elseif ($file1_needs_mapping && $file2_needs_mapping) {
            error_log("Scenario 4: Both files need mapping");
            header('Location: map_columns_compare.php?file=1');
            exit;
        } elseif (($file1_valid || $file1_needs_mapping) && !$file2_valid && !$file2_needs_mapping) {
            // CRITICAL FIX: Create a clear success/failure message with icons
            error_log("=== SCENARIO 5: File 1 SUCCESS, File 2 FAILED ===");
            error_log("File 1 upload_id: " . ($upload_result1['upload_id'] ?? 'NULL'));
            error_log("File 2 error: " . ($upload_result2['message'] ?? 'Unknown'));
            
            $file1_success_msg = "✅ First file ('" . ($upload_result1['original_filename'] ?? $file1['name']) . "') uploaded successfully";
            $file2_failure_msg = "❌ Second file ('" . ($upload_result2['original_filename'] ?? $file2['name']) . "') failed";
            
            $error_message = $file1_success_msg . ", but " . $file2_failure_msg . ":\n\n" . ($upload_result2['message'] ?? 'Unknown error');
            
            // CRITICAL DEBUG: Log the exact error message being constructed
            error_log("SCENARIO 5: File 1 success message: " . $file1_success_msg);
            error_log("SCENARIO 5: File 2 failure message: " . $file2_failure_msg);
            error_log("SCENARIO 5: Combined error message: " . $error_message);
            error_log("SCENARIO 5: Raw file 2 error: " . ($upload_result2['message'] ?? 'Unknown error'));
            
            // Set flags for detailed error display
            if (strpos($upload_result2['message'] ?? '', 'Found ') === 0 && strpos($upload_result2['message'] ?? '', 'validation errors') !== false) {
                $show_detailed_errors = true;
                
                // Parse the validation errors for detailed display
                $errorLines = explode("\n", $upload_result2['message']);
                $compare_validation_errors = [];
                
                foreach ($errorLines as $line) {
                    $line = trim($line);
                    if (!empty($line) && strpos($line, 'Row ') === 0) {
                        $compare_validation_errors[] = $line;
                    }
                }
                
                error_log("SCENARIO 5: Found " . count($compare_validation_errors) . " validation errors for file 2");
                error_log("SCENARIO 5: Show detailed errors flag: " . ($show_detailed_errors ? 'true' : 'false'));
            }
            
            error_log("SCENARIO 5: Final error message set: " . substr($error_message, 0, 200) . "...");
            
        } elseif (!$file1_valid && !$file1_needs_mapping && ($file2_valid || $file2_needs_mapping)) {
            // CRITICAL FIX: Create a clear success/failure message with icons
            error_log("=== SCENARIO 6: File 1 FAILED, File 2 SUCCESS ===");
            error_log("File 1 error: " . ($upload_result1['message'] ?? 'Unknown'));
            error_log("File 2 upload_id: " . ($upload_result2['upload_id'] ?? 'NULL'));
            
            $file1_failure_msg = "❌ First file ('" . ($upload_result1['original_filename'] ?? $file1['name']) . "') failed";
            $file2_success_msg = "✅ Second file ('" . ($upload_result2['original_filename'] ?? $file2['name']) . "') uploaded successfully";
            
            $error_message = $file1_failure_msg . ", but " . $file2_success_msg . ":\n\n" . ($upload_result1['message'] ?? 'Unknown error');
            
            // CRITICAL DEBUG: Log the exact error message being constructed
            error_log("SCENARIO 6: File 1 failure message: " . $file1_failure_msg);
            error_log("SCENARIO 6: File 2 success message: " . $file2_success_msg);
            error_log("SCENARIO 6: Combined error message: " . $error_message);
            error_log("SCENARIO 6: Raw file 1 error: " . ($upload_result1['message'] ?? 'Unknown error'));
            
            // Set flags for detailed error display
            if (strpos($upload_result1['message'] ?? '', 'Found ') === 0 && strpos($upload_result1['message'] ?? '', 'validation errors') !== false) {
                $show_detailed_errors = true;
                
                // Parse the validation errors for detailed display
                $errorLines = explode("\n", $upload_result1['message']);
                $compare_validation_errors = [];
                
                foreach ($errorLines as $line) {
                    $line = trim($line);
                    if (!empty($line) && strpos($line, 'Row ') === 0) {
                        $compare_validation_errors[] = $line;
                    }
                }
                
                error_log("SCENARIO 6: Found " . count($compare_validation_errors) . " validation errors for file 1");
                error_log("SCENARIO 6: Show detailed errors flag: " . ($show_detailed_errors ? 'true' : 'false'));
            }
            
            error_log("SCENARIO 6: Final error message set: " . substr($error_message, 0, 200) . "...");
            
        } else {
            error_log("=== SCENARIO 7/8: BOTH FILES FAILED ===");
            error_log("File 1 error: " . ($upload_result1['message'] ?? 'Unknown'));
            error_log("File 2 error: " . ($upload_result2['message'] ?? 'Unknown'));
            
            $file1_failure_msg = "❌ First file ('" . ($upload_result1['original_filename'] ?? $file1['name']) . "') failed";
            $file2_failure_msg = "❌ Second file ('" . ($upload_result2['original_filename'] ?? $file2['name']) . "') failed";
            
            $error_message = "❌ Both files failed validation:\n\n" . $file1_failure_msg . ":\n" . ($upload_result1['message'] ?? 'Unknown error') . "\n\n" . $file2_failure_msg . ":\n" . ($upload_result2['message'] ?? 'Unknown error');
            
            // CRITICAL FIX: Set flags for detailed error display with file separation
            $file1_has_validation_errors = (strpos($upload_result1['message'] ?? '', 'Found ') === 0 && strpos($upload_result1['message'] ?? '', 'validation errors') !== false);
            $file2_has_validation_errors = (strpos($upload_result2['message'] ?? '', 'Found ') === 0 && strpos($upload_result2['message'] ?? '', 'validation errors') !== false);
            
            if ($file1_has_validation_errors || $file2_has_validation_errors) {
                $show_detailed_errors = true;
                $compare_validation_errors = [];
                
                // Process File 1 errors
                if ($file1_has_validation_errors) {
                    $file1ErrorLines = explode("\n", $upload_result1['message']);
                    $compare_validation_errors[] = "--- File 1 Errors ---";
                    foreach ($file1ErrorLines as $line) {
                        $line = trim($line);
                        if (!empty($line) && strpos($line, 'Row ') === 0) {
                            $compare_validation_errors[] = $line;
                        }
                    }
                }
                
                // Process File 2 errors
                if ($file2_has_validation_errors) {
                    $file2ErrorLines = explode("\n", $upload_result2['message']);
                    $compare_validation_errors[] = "--- File 2 Errors ---";
                    foreach ($file2ErrorLines as $line) {
                        $line = trim($line);
                        if (!empty($line) && strpos($line, 'Row ') === 0) {
                            $compare_validation_errors[] = $line;
                        }
                    }
                }
                
                error_log("SCENARIO 7/8: Found validation errors - File 1: " . ($file1_has_validation_errors ? 'YES' : 'NO') . ", File 2: " . ($file2_has_validation_errors ? 'YES' : 'NO'));
                error_log("SCENARIO 7/8: Total parsed errors: " . count($compare_validation_errors));
                
                // Create summary message for both files failed scenario
                $file1_summary = $file1_has_validation_errors ? 
                    "❌ First file ('" . ($upload_result1['original_filename'] ?? $file1['name']) . "') failed" : 
                    "❌ First file ('" . ($upload_result1['original_filename'] ?? $file1['name']) . "') failed";
                $file2_summary = $file2_has_validation_errors ? 
                    "❌ Second file ('" . ($upload_result2['original_filename'] ?? $file2['name']) . "') failed" : 
                    "❌ Second file ('" . ($upload_result2['original_filename'] ?? $file2['name']) . "') failed";
                
                $error_message = $file1_summary . " and " . $file2_summary;
            }
            
            error_log("SCENARIO 7/8: Final error message set: " . substr($error_message, 0, 200) . "...");
        }
        
    } catch (Exception $e) {
        $error_message = "Error processing files: " . $e->getMessage();
        error_log("COMPARISON EXCEPTION: " . $e->getMessage());
    }
}

function findUploadedFile($filename) {
    $uploadsDir = __DIR__ . '/../uploads/';
    
    // Try exact filename first
    $exactPath = $uploadsDir . $filename;
    if (file_exists($exactPath)) {
        return $exactPath;
    }

    // Try to find with hash prefix
    $pattern = $uploadsDir . '*_' . $filename;
    $foundFiles = glob($pattern);

    if (!empty($foundFiles)) {
        return $foundFiles[0]; // Return the first match
    }

    // Try without extension matching
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    $pattern = $uploadsDir . '*' . $nameWithoutExt . '*';
    $foundFiles = glob($pattern);

    if (!empty($foundFiles)) {
        return $foundFiles[0];
    }

    return null;
}

// Handle failed mapping redirect with validation errors
if (isset($_GET['mapping_failed']) && $_GET['mapping_failed'] == '1') {
    error_log("Mapping failed redirect detected in compare.php");
    
    // Check if we have validation errors from mapping
    if (isset($_SESSION['compare_validation_errors']) && !empty($_SESSION['compare_validation_errors'])) {
        $validationErrors = $_SESSION['compare_validation_errors'];
        $fileInfo = $_SESSION['failed_compare_file_info'] ?? null;
        
        // Create detailed error message for comparison context - SHOW ALL ERRORS
        $errorMessage = "File contains " . count($validationErrors) . " validation errors. Please fix the data issues and try again.";
        
        $error_message = $errorMessage;
        $show_detailed_errors = true;
        $compare_validation_errors = $validationErrors;
        
        // Clear the session variables
        unset($_SESSION['compare_validation_errors']);
        unset($_SESSION['failed_compare_file_info']);
    }
}

// Check if we're returning from mapping and ready to compare
if (isset($_SESSION['compare_ready']) && $_SESSION['compare_ready'] && isset($_SESSION['compare_files'])) {
    $compareFiles = $_SESSION['compare_files'];
    
    // Verify we have the expected file structure
    if (!isset($compareFiles[1]) || !isset($compareFiles[2])) {
        error_log("ERROR: compare_files structure incomplete - missing file 1 or 2");
        $_SESSION['compare_error'] = "Comparison session incomplete. Please upload your files again.";
        unset($_SESSION['compare_ready']);
        unset($_SESSION['compare_files']);
        // Don't redirect here, just clear the error state
    } else {
        // Both files should now be mapped and have upload IDs
        $file1Ready = isset($compareFiles[1]['upload_id']) && $compareFiles[1]['upload_id'] !== null;
        $file2Ready = isset($compareFiles[2]['upload_id']) && $compareFiles[2]['upload_id'] !== null;
        
        if ($file1Ready && $file2Ready) {
            // CRITICAL FIX: Use upload IDs instead of file paths
            error_log("COMPARISON: Upload IDs - File 1: " . $compareFiles[1]['upload_id'] . ", File 2: " . $compareFiles[2]['upload_id']);
            
            try {
                // CRITICAL FIX: Pass upload IDs directly, not file paths
                $comparison_results = compareCSVFiles($compareFiles[1]['upload_id'], $compareFiles[2]['upload_id']);
                error_log("COMPARISON: Successfully compared uploads " . $compareFiles[1]['upload_id'] . " and " . $compareFiles[2]['upload_id']);
                
                $success_message = "Files compared successfully!";
            } catch (Exception $e) {
                error_log("COMPARISON EXCEPTION: " . $e->getMessage());
                $error_message = "Error processing files: " . $e->getMessage();
            }
        } else {
            error_log("ERROR: Files not ready for comparison - File 1 ready: " . ($file1Ready ? 'YES' : 'NO') . ", File 2 ready: " . ($file2Ready ? 'YES' : 'NO'));
            $error_message = "Files are not ready for comparison. Please try uploading again.";
        }
    }
    
    // Clear the comparison session data
    unset($_SESSION['compare_ready']);
    unset($_SESSION['compare_files']);
}

// Check for comparison error from mapping
if (isset($_SESSION['compare_error'])) {
    $error_message = $_SESSION['compare_error'];
    unset($_SESSION['compare_error']);
}

// Handle saving comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compare'])) {
    $upload1 = $_POST['upload1'];
    $upload2 = $_POST['upload2'];
    $comparisonName = trim($_POST['comparisonName']);

    if (!empty($comparisonName)) {
        // Insert into saved_comparison
        $stmt = $conn->prepare("INSERT INTO saved_comparison (UserID, ComparisonName) VALUES (?, ?)");
        $stmt->bind_param("is", $userID, $comparisonName);
        $stmt->execute();
        $comparisonID = $conn->insert_id;

        // Insert the first file into comparison_file_link
        $stmt1 = $conn->prepare("INSERT INTO comparison_file_link (ComparisonID, UploadID, FileOrder) VALUES (?, ?, ?)");
        $fileOrder1 = 1;
        $stmt1->bind_param("iii", $comparisonID, $upload1, $fileOrder1);
        $stmt1->execute();
        
        // Insert the second file into comparison_file_link
        $stmt2 = $conn->prepare("INSERT INTO comparison_file_link (ComparisonID, UploadID, FileOrder) VALUES (?, ?, ?)");
        $fileOrder2 = 2;
        $stmt2->bind_param("iii", $comparisonID, $upload2, $fileOrder2);
        $stmt2->execute();

        // Reset the dropdown selections after successful save
        $upload1 = null;
        $upload2 = null;
    }
}

// Handle loading saved comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_comparison'])) {
    $selectedComparisonID = $_POST['saved_comparison_id'];
    
    // Get the files for this comparison - using actual column names
    $stmt = $conn->prepare("
        SELECT cfl.UploadID, cfl.FileOrder, cu.FileName, cu.UploadID as FileUploadID
        FROM comparison_file_link cfl 
        JOIN csv_upload cu ON cfl.UploadID = cu.UploadID 
        WHERE cfl.ComparisonID = ? 
        ORDER BY cfl.FileOrder
    ");
    $stmt->bind_param("i", $selectedComparisonID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[$row['FileOrder']] = $row;
    }
    
    if (count($files) >= 2) {
        // Set the selected files for comparison
        $upload1 = $files[1]['UploadID'];
        $upload2 = $files[2]['UploadID'];
        
        // Construct the file paths using the FileName
        // Files are typically stored in the uploads directory with their original filename
        $file1_path = '../uploads/' . $files[1]['FileName'];
        $file2_path = '../uploads/' . $files[2]['FileName'];
        
        // Check if files exist
        if (file_exists($file1_path) && file_exists($file2_path)) {
            try {
                $comparison_results = compareCSVFiles($file1_path, $file2_path);
                $success_message = "Saved comparison loaded successfully!";
            } catch (Exception $e) {
                $error_message = "Error loading comparison: " . $e->getMessage();
            }
        } else {
            $error_message = "One or both files from the saved comparison could not be found.";
        }
    } else {
        $error_message = "Invalid comparison data found.";
    }
}


// Get user's uploaded CSV files for dropdown
$csvFiles = [];
$stmt = $conn->prepare("SELECT UploadID, FileName, UploadDate FROM csv_upload WHERE UserID = ? ORDER BY UploadDate DESC");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $csvFiles[] = $row;
}

// Get user's saved comparisons for dropdown
$savedComparisons = [];
$stmt = $conn->prepare("SELECT ComparisonID, ComparisonName FROM saved_comparison WHERE UserID = ? ORDER BY ComparisonName");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $savedComparisons[] = $row;
}

function compareCSVFiles($uploadId1, $uploadId2) {
    global $conn;
    
    // CRITICAL FIX: Log the parameters we receive
    error_log("=== COMPARE CSV FILES DEBUG ===");
    error_log("Upload ID 1: " . var_export($uploadId1, true));
    error_log("Upload ID 2: " . var_export($uploadId2, true));
    
    // CRITICAL FIX: Ensure we have valid upload IDs
    if (!is_numeric($uploadId1) || !is_numeric($uploadId2)) {
        error_log("ERROR: Invalid upload IDs provided - ID1: $uploadId1, ID2: $uploadId2");
        throw new Exception("Invalid upload IDs provided for comparison.");
    }
    
    // Get data from database using upload IDs
    $data1 = getDataFromDatabase($uploadId1);
    $data2 = getDataFromDatabase($uploadId2);
    
    error_log("Data1 count: " . count($data1));
    error_log("Data2 count: " . count($data2));
    
    if (empty($data1) || empty($data2)) {
        error_log("ERROR: One or both datasets are empty - Data1: " . count($data1) . ", Data2: " . count($data2));
        throw new Exception("One or both datasets are empty or invalid.");
    }
    
    // Get available metrics from both datasets
    $metrics1 = array_keys($data1[0] ?? []);
    $metrics2 = array_keys($data2[0] ?? []);
    
    // Calculate common headers and differences
    $common_headers = array_intersect($metrics1, $metrics2);
    $unique_to_file1 = array_diff($metrics1, $metrics2);
    $unique_to_file2 = array_diff($metrics2, $metrics1);
    
    // Define analytics metrics to look for
    $analytics_metrics = [
        'traffic_source', 'sessions', 'engaged_sessions', 'engagement_rate', 'average_engagement_time_per_session',
        'events_per_session', 'event_count', 'key_events', 'session_key_event_rate', 'total_revenue'
    ];
    
    $comparison = [
        'basic_metrics' => [
            'file1_rows' => count($data1), // CRITICAL FIX: Add row counts
            'file2_rows' => count($data2), // CRITICAL FIX: Add row counts
            'file1_columns' => count($metrics1), // CRITICAL FIX: Add column counts
            'file2_columns' => count($metrics2), // CRITICAL FIX: Add column counts
            'common_columns' => count($common_headers)
        ],
        'headers' => [
            'common_headers' => array_values($common_headers), // CRITICAL FIX: Add this line
            'unique_to_file1' => array_values($unique_to_file1),
            'unique_to_file2' => array_values($unique_to_file2)
        ],
        'analytics_metrics' => [],
        'summary_comparison' => [],
        'data_sample' => [
            'file1_sample' => array_slice($data1, 0, 5),
            'file2_sample' => array_slice($data2, 0, 5)
        ]
    ];
    
    // Analyze analytics metrics with IMPROVED metric detection
    error_log("Common headers for metrics analysis: " . json_encode($common_headers));

    foreach ($analytics_metrics as $metric) {
        // IMPROVED: Use the findMetricColumn function for better matching
        $found_metric = findMetricColumn($common_headers, $metric);
        
        if ($found_metric) {
            $comparison['analytics_metrics'][$metric] = [
                'column_name' => $found_metric,
                'available' => true
            ];
            error_log("Found metric $metric as column $found_metric");
        } else {
            $comparison['analytics_metrics'][$metric] = [
                'column_name' => null,
                'available' => false
            ];
            error_log("Metric $metric not found in common headers");
        }
    }

    error_log("Analytics metrics populated: " . json_encode(array_keys($comparison['analytics_metrics'])));
    
    // Calculate summary totals for key metrics
    $comparison['summary_comparison'] = calculateSummaryComparison($data1, $data2, $comparison['analytics_metrics']);
    
    error_log("=== END COMPARE CSV FILES DEBUG ===");
    return $comparison;
}

function getDataFromDatabase($uploadId) {
    global $conn;
    
    $data = [];
    
    try {
        // Get all data for this upload with metric names
        $query = "SELECT 
                    st.SourceName as traffic_source,
                    mt.MetricName,
                    pdp.Value
                  FROM PROCESSED_DATA_POINT pdp
                  JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
                  JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
                  WHERE pdp.UploadID = ?
                  ORDER BY st.SourceName, mt.MetricName";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grouped_data = [];
        while ($row = $result->fetch_assoc()) {
            $source = $row['traffic_source'];
            $metric = $row['MetricName'];
            $value = $row['Value'];
            
            if (!isset($grouped_data[$source])) {
                $grouped_data[$source] = ['traffic_source' => $source];
            }
            
            // Map metric names to consistent keys
            $metric_key = strtolower(str_replace(' ', '_', $metric));
            $grouped_data[$source][$metric_key] = $value;
        }
        
        // Convert grouped data to array
        $data = array_values($grouped_data);
        
        error_log("Retrieved " . count($data) . " records from database for upload $uploadId");
        
    } catch (Exception $e) {
        error_log("Error getting data from database: " . $e->getMessage());
    }
    
    return $data;
}

function findMetricColumn($headers, $metric) {
    $metric_variations = [
        'sessions' => ['Sessions', 'sessions', 'session', 'total_sessions', 'User Sessions', 'visits'],
        'engaged_sessions' => ['Engaged sessions', 'engaged_sessions', 'engaged sessions', 'engagedsessions', 'Engaged Sessions'],
        'engagement_rate' => ['Engagement rate', 'engagement_rate', 'engagement rate', 'engagementrate'],
        'average_engagement_time_per_session' => ['Average engagement time per session', 'average_engagement_time_per_session', 'avg_engagement_time', 'engagement_time', 'Average engagement time', 'Avg Session Time'],
        'events_per_session' => ['Events per session', 'events_per_session', 'events per session', 'eventspersession', 'Events Per Session'],
        'event_count' => ['Event count', 'event_count', 'events', 'total_events', 'Events', 'Total Events'],
        'key_events' => ['Key events', 'key_events', 'key events', 'keyevents', 'Conversions', 'conversions'],
        'session_key_event_rate' => ['Session key event rate', 'session_key_event_rate', 'key_event_rate', 'conversion_rate', 'Session conversion rate', 'Conversion Rate'],
        'total_revenue' => ['Total revenue', 'total_revenue', 'revenue', 'total revenue', 'Revenue', 'Purchase revenue'],
        'total_page_views' => ['total_page_views', 'page_views', 'pageviews', 'Views', 'Page views', 'Pageviews'],
        'unique_visitors' => ['unique_visitors', 'unique visitors', 'users', 'Users', 'Total users', 'Active users'],
        'average_session_duration' => ['average_session_duration', 'avg_session_duration', 'session_duration', 'Average session duration', 'Session duration', 'Avg Session Time'],
        'bounce_rate' => ['bounce_rate', 'bounce rate', 'bouncerate', 'Bounce rate', 'Bounce Rate'],
        'traffic_source' => ['Traffic Source', 'traffic_source', 'Session primary channel group (Default channel group)', 'Channel', 'Source']
    ];

    // FIXED: First try direct matching to avoid recursion
    $variations = $metric_variations[$metric] ?? [$metric];
    
    foreach ($variations as $variation) {
        foreach ($headers as $header) {
            if (strcasecmp(trim($header), trim($variation)) === 0) {
                return $header;
            }
        }
    }
    
    // FIXED: Only try conversion if direct match failed AND to prevent infinite recursion
    if ($metric === 'bounce_rate') {
        // Look for engagement rate variations directly (no recursive call)
        $engagement_variations = $metric_variations['engagement_rate'] ?? [];
        foreach ($engagement_variations as $variation) {
            foreach ($headers as $header) {
                if (strcasecmp(trim($header), trim($variation)) === 0) {
                    return $header;
                }
            }
        }
    }
    
    if ($metric === 'engagement_rate') {
        // Look for bounce rate variations directly (no recursive call)
        $bounce_variations = $metric_variations['bounce_rate'] ?? [];
        foreach ($bounce_variations as $variation) {
            foreach ($headers as $header) {
                if (strcasecmp(trim($header), trim($variation)) === 0) {
                    return $header;
                }
            }
        }
    }
    
    return null;
}

function cleanNumericValues($values) {
    $cleaned = [];
    foreach ($values as $value) {
        // Remove common formatting characters
        $cleaned_value = preg_replace('/[,$%]/', '', trim($value));
        if (is_numeric($cleaned_value)) {
            $cleaned[] = floatval($cleaned_value);
        }
    }
    return $cleaned;
}

function determineImprovement($metric, $value2, $value1) {
    // For metrics where higher is better
    $higher_is_better = ['sessions', 'engaged_sessions', 'events_per_session', 'event_count', 
                        'key_events', 'total_revenue', 'total_page_views', 'unique_visitors', 
                        'average_session_duration', 'engagement_rate', 'session_key_event_rate',
                        'average_engagement_time_per_session']; // Added this metric
    
    // For metrics where lower is better
    $lower_is_better = ['bounce_rate'];
    
    if (in_array($metric, $higher_is_better)) {
        return $value2 > $value1 ? 'improved' : ($value2 < $value1 ? 'declined' : 'unchanged');
    } elseif (in_array($metric, $lower_is_better)) {
        return $value2 < $value1 ? 'improved' : ($value2 > $value1 ? 'declined' : 'unchanged');
    }
    
    return 'neutral';
}

function calculateSummaryComparison($data1, $data2, $analytics_metrics) {
    error_log("=== CALCULATE SUMMARY COMPARISON DEBUG ===");
    error_log("Data1 count: " . count($data1));
    error_log("Data2 count: " . count($data2));
    error_log("Analytics metrics: " . json_encode(array_keys($analytics_metrics)));
    
    $summary = [];
    
    foreach ($analytics_metrics as $metric => $data) {
        // Skip if no data available for this metric
        if (!isset($data['column_name']) || empty($data['column_name'])) {
            continue;
        }
        
        $column_name = $data['column_name'];
        error_log("Processing metric: $metric with column: $column_name");
        
        // CRITICAL FIX: Handle traffic_source as a count, not sum
        if ($metric === 'traffic_source') {
            $count1 = count($data1);
            $count2 = count($data2);
            
            $summary[$metric] = [
                'file1_total' => $count1,
                'file2_total' => $count2,
                'change' => $count2 - $count1,
                'percent_change' => $count1 != 0 ? (($count2 - $count1) / $count1) * 100 : ($count2 != 0 ? 100 : 0),
                'status' => $count2 > $count1 ? 'improved' : ($count2 < $count1 ? 'declined' : 'unchanged'),
                'improvement' => $count2 > $count1 ? 'improved' : ($count2 < $count1 ? 'declined' : 'unchanged'),
                'total_diff' => $count2 - $count1,
                'avg1' => $count1,
                'avg2' => $count2,
                'comparison' => [
                    'value1' => $count1,
                    'value2' => $count2,
                    'difference' => $count2 - $count1,
                    'percentage' => $count1 != 0 ? (($count2 - $count1) / $count1) * 100 : ($count2 != 0 ? 100 : 0)
                ]
            ];
            
            error_log("Traffic source count - Period 1: $count1, Period 2: $count2");
            continue;
        }
        
        // Extract values for this metric from both datasets
        $values1 = [];
        $values2 = [];
        
        foreach ($data1 as $row) {
            if (isset($row[$column_name]) && is_numeric($row[$column_name])) {
                $values1[] = floatval($row[$column_name]);
            }
        }
        
        foreach ($data2 as $row) {
            if (isset($row[$column_name]) && is_numeric($row[$column_name])) {
                $values2[] = floatval($row[$column_name]);
            }
        }
        
        error_log("Metric $metric - Values1 count: " . count($values1) . ", Values2 count: " . count($values2));
        
        // Calculate totals
        $total1 = array_sum($values1);
        $total2 = array_sum($values2);
        
        // Calculate percentage change
        $percent_change = 0;
        if ($total1 != 0) {
            $percent_change = (($total2 - $total1) / $total1) * 100;
        } elseif ($total2 != 0) {
            $percent_change = 100; // If starting from 0, it's 100% increase
        }
        
        // Calculate averages
        $avg1 = count($values1) > 0 ? $total1 / count($values1) : 0;
        $avg2 = count($values2) > 0 ? $total2 / count($values2) : 0;
        
        // Determine status
        $status = determineImprovement($metric, $total2, $total1);
        
        // Calculate difference
        $total_diff = $total2 - $total1;
        
        // Determine improvement status
        $improvement = 'neutral';
        if ($total_diff > 0) {
            $improvement = in_array($metric, ['bounce_rate']) ? 'declined' : 'improved';
        } elseif ($total_diff < 0) {
            $improvement = in_array($metric, ['bounce_rate']) ? 'improved' : 'declined';
        } else {
            $improvement = 'unchanged';
        }
        
        $summary[$metric] = [
            'file1_total' => $total1,
            'file2_total' => $total2,
            'change' => $total_diff,
            'percent_change' => $percent_change,
            'status' => $status,
            'improvement' => $improvement,
            'total_diff' => $total_diff,
            'avg1' => $avg1,
            'avg2' => $avg2,
            'comparison' => [
                'value1' => $total1,
                'value2' => $total2,
                'difference' => $total_diff,
                'percentage' => $percent_change
            ]
        ];
        
        error_log("Summary for $metric: " . json_encode($summary[$metric]));
    }
    
    error_log("Final summary structure: " . json_encode(array_keys($summary)));
    error_log("=== END CALCULATE SUMMARY COMPARISON DEBUG ===");
    
    return $summary;
}

function parseCSV($file_path) {
    $data = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $headers = null;
        $row_number = 0;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            if ($row_number === 0) {
                $headers = $row;
            } else {
                // FIXED: Check if headers is not null and is an array before using it
                if ($headers !== null && is_array($headers) && is_array($row) && count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            $row_number++;
        }
        fclose($handle);
    }
    return $data;
}

function calculateStats($values) {
    if (empty($values)) return null;
    
    // Convert all values to float to ensure numeric operations work
    $values = array_map('floatval', $values);
    
    sort($values);
    $count = count($values);
    $sum = array_sum($values);
    $mean = $sum / $count;
    
    $median = $count % 2 === 0 
        ? ($values[intval($count/2 - 1)] + $values[intval($count/2)]) / 2 
        : $values[floor($count/2)];
    
    return [
        'count' => $count,
        'sum' => round($sum, 2),
        'mean' => round($mean, 2),
        'median' => round($median, 2),
        'min' => min($values),
        'max' => max($values),
        'range' => max($values) - min($values)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Metrics - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* =====================================================
        BASIC COMPONENT STYLES
        ===================================================== */
        .comparison-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background: white;
            padding: 20px;
        }

        .metric-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }

        .user-metric-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }

        .user-metric-box h4 {
            color: #000 !important; /* Force black color */
            margin: 0 0 5px 0;
        }

        .user-metric-box small {
            color: #000 !important; /* Force black color */
            font-weight: 500;
        }

        /* Status Color Classes */
        .improved { color: #28a745; }
        .declined { color: #dc3545; }
        .unchanged { color: #6c757d; }
        .neutral { color: #17a2b8; }

        /* =====================================================
        LAYOUT AND GRID STYLES
        ===================================================== */
        .stats-grid,
        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: white;
        }

        /* =====================================================
        FORM AND INPUT STYLES
        ===================================================== */
        .upload-form,
        .user-upload-form {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .file-input-group,
        .user-file-input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .file-input-group > div,
        .user-file-input-group > div {
            flex: 1;
        }

        .file-input-group label,
        .user-file-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .file-input-group input,
        .user-file-input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .file-input-group small,
        .user-file-input-group small {
            color: #666;
            font-size: 12px;
        }

        /* =====================================================
        BUTTON STYLES
        ===================================================== */
        .btn-submit,
        .user-btn-submit {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit:hover,
        .user-btn-submit:hover {
            background: #0056b3;
        }

        button {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #005a87;
        }

        /* =====================================================
        EXPORT CONTROLS
        ===================================================== */
        .user-export-controls {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .user-export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
        }

        .user-export-btn.csv {
            background: #28a745;
            color: white;
        }

        .user-export-btn.csv:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .user-export-btn.pdf {
            background: #dc3545;
            color: white;
        }

        .user-export-btn.pdf:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* =====================================================
        ALERT AND MESSAGE STYLES
        ===================================================== */
        .alert,
        .user-alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-danger,
        .user-alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .user-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* =====================================================
        ERROR DISPLAY STYLES
        ===================================================== */
        .user-alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: block !important;
            width: 100%;
            box-sizing: border-box;
            clear: both;
            overflow: hidden;
        }

        .error-navigation {
            background: #e2e3e5 !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin-bottom: 20px !important;
            display: flex !important;
            justify-content: center !important;
            gap: 15px !important;
            flex-wrap: wrap !important;
            border: 1px solid #adb5bd !important;
        }

        .error-nav-button {
            background: #495057 !important;
            color: white !important;
            padding: 8px 16px !important;
            border: none !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            font-size: 0.9em !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .error-nav-button:hover {
            background: #343a40 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
        }

        .error-nav-button.active {
            background: #dc3545 !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4) !important;
        }

        .error-nav-counter {
            background: rgba(255,255,255,0.9) !important;
            color: #495057 !important;
            padding: 2px 6px !important;
            border-radius: 12px !important;
            font-size: 0.8em !important;
            font-weight: bold !important;
            min-width: 18px !important;
            text-align: center !important;
        }

        .user-alert-danger * {
            display: block !important;
            width: 100% !important;
            box-sizing: border-box !important;
            float: none !important;
            clear: both !important;
        }

        .user-alert-danger .error-container {
            margin-bottom: 20px;
            display: block !important;
            width: 100% !important;
            clear: both !important;
            overflow: hidden;
        }

        /* Quick Jump Buttons */
        .quick-jump-container {
            position: fixed !important;
            right: 20px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1000 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            opacity: 0.8 !important;
            transition: opacity 0.3s ease !important;
        }

        .quick-jump-container:hover {
            opacity: 1 !important;
        }

        .quick-jump-btn {
            background: #495057 !important;
            color: white !important;
            border: none !important;
            padding: 8px 12px !important;
            border-radius: 20px !important;
            cursor: pointer !important;
            font-size: 0.8em !important;
            font-weight: bold !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
            transition: all 0.3s ease !important;
            min-width: 60px !important;
            text-align: center !important;
        }

        .quick-jump-btn:hover {
            background: #343a40 !important;
            transform: scale(1.05) !important;
        }

        .quick-jump-btn.file1 {
            background: #007bff !important;
        }

        .quick-jump-btn.file1:hover {
            background: #0056b3 !important;
        }

        .quick-jump-btn.file2 {
            background: #28a745 !important;
        }

        .quick-jump-btn.file2:hover {
            background: #1e7e34 !important;
        }

        .quick-jump-btn.file1.active {
            background: #0056b3 !important;
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.4) !important;
        }

        .quick-jump-btn.file2.active {
            background: #1e7e34 !important;
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.4) !important;
        }

        .user-alert-danger .error-summary {
            font-weight: bold !important;
            margin-bottom: 20px !important;
            color: #721c24 !important;
            display: block !important;
            width: 100% !important;
            clear: both !important;
            padding: 12px !important;
            background: rgba(255,255,255,0.7) !important;
            border-radius: 6px !important;
            border-left: 4px solid #dc3545 !important;
        }

        .user-alert-danger .error-list {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 0 20px 0 !important;
            display: block !important;
            width: 100% !important;
            clear: both !important;
            max-height: 500px !important; /* Increased for better viewing */
            overflow-y: auto !important;
            scroll-behavior: smooth !important; /* Smooth scrolling */
        }

        .user-alert-danger .error-item {
            background: #fff5f5 !important;
            border: 1px solid #fed7e2 !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            border-left: 4px solid #e53e3e !important;
            display: block !important;
            width: calc(100% - 2px) !important;
            box-sizing: border-box !important;
            clear: both !important;
            float: none !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }

        .user-alert-danger .error-item:hover {
            background: #fff0f0 !important;
            border-left-color: #dc3545 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15) !important;
        }

        .error-item-badge {
            position: absolute !important;
            top: -8px !important;
            right: 12px !important;
            background: #6c757d !important;
            color: white !important;
            padding: 4px 8px !important;
            border-radius: 12px !important;
            font-size: 0.75em !important;
            font-weight: bold !important;
            z-index: 5 !important;
        }

        .error-item-badge.file1 {
            background: #007bff !important;
        }

        .error-item-badge.file2 {
            background: #28a745 !important;
        }

        .user-alert-danger .error-message {
            font-weight: 500 !important;
            color: #721c24 !important;
            margin-bottom: 10px !important;
            display: block !important;
            width: 100% !important;
            clear: both !important;
            word-wrap: break-word !important;
            line-height: 1.4 !important;
            padding-right: 60px !important; /* Space for badge */
        }

        .user-alert-danger .error-suggestions {
            background: #fff3cd !important;
            border: 1px solid #ffeaa7 !important;
            border-radius: 4px !important;
            padding: 10px !important;
            font-size: 0.9em !important;
            color: #856404 !important;
            margin-top: 10px !important;
            display: block !important;
            width: calc(100% - 22px) !important;
            box-sizing: border-box !important;
            clear: both !important;
            border-left: 3px solid #ffc107 !important;
        }

        .user-alert-danger .suggestions-text {
            color: #856404 !important;
            display: inline !important;
            width: auto !important;
        }

        .user-alert-danger .error-footer {
            font-weight: bold !important;
            color: #721c24 !important;
            margin-top: 15px !important;
            text-align: center !important;
            display: block !important;
            clear: both !important;
            padding: 10px 0 !important;
            width: 100% !important;
        }

        .file-section-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important; /* Changed to blue */
            color: white !important;
            padding: 12px 16px !important;
            margin: 20px 0 15px 0 !important;
            border-radius: 8px !important;
            font-weight: bold !important;
            font-size: 1.1em !important;
            text-align: center !important;
            border-left: 4px solid #007bff !important; /* Changed to blue */
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3) !important; /* Changed to blue shadow */
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
        }

        .file-section-header:first-of-type {
            margin-top: 0 !important;
        }

        .file-section-header .file-icon {
            font-size: 1.2em !important;
            color: white !important;
        }

        .file-section-header .error-count-badge {
            background: rgba(255, 255, 255, 0.9) !important;
            color: #007bff !important; /* Changed to blue */
            padding: 4px 8px !important;
            border-radius: 12px !important;
            font-size: 0.85em !important;
            font-weight: bold !important;
            margin-left: 8px !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        /* CRITICAL FIX: Override any gray styling that might be applied initially */
        .user-alert-danger .error-item[style*="background: #e9ecef"] {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
        }

        .user-alert-danger .error-item[style*="border-left: 3px solid #6c757d"] {
            border-left: 4px solid #dc3545 !important;
        }

        .user-alert-danger .error-item[style*="color: #495057"] .error-message {
            color: white !important;
        }

        /* Add active state for quick jump buttons */
        .quick-jump-btn.active {
            background: #dc3545 !important;
            transform: scale(1.1) !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4) !important;
        }

        .quick-jump-btn.file1.active {
            background: #0056b3 !important;
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.4) !important;
        }

        .quick-jump-btn.file2.active {
            background: #1e7e34 !important;
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.4) !important;
        }

        /* Ensure error items have proper styling when highlighted */
        .user-alert-danger .error-item.highlighted {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            border-left-color: #721c24 !important;
            transform: translateX(8px) !important;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3) !important;
        }

        .error-progress {
            background: #e9ecef !important;
            height: 4px !important;
            border-radius: 2px !important;
            margin: 15px 0 !important;
            overflow: hidden !important;
        }

        .error-progress-bar {
            background: linear-gradient(90deg, #dc3545, #c82333) !important;
            height: 100% !important;
            border-radius: 2px !important;
            transition: width 0.3s ease !important;
        }

        /* =====================================================
        VALIDATION HELP STYLES
        ===================================================== */
        .user-alert-danger .validation-help {
            background: white !important;
            border-radius: 6px !important;
            padding: 15px !important;
            margin: 20px 0 !important;
            display: block !important;
            clear: both !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .user-alert-danger .validation-help h4 {
            color: #495057 !important;
            margin-bottom: 12px !important;
            display: block !important;
            width: 100% !important;
        }

        .user-alert-danger .fix-guide {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
            gap: 15px !important;
            margin-bottom: 15px !important;
            width: 100% !important;
        }

        .user-alert-danger .fix-item {
            background: white !important;
            border-radius: 4px !important;
            padding: 12px !important;
            border: 1px solid #ced4da !important;
            display: block !important;
            box-sizing: border-box !important;
        }

        .user-alert-danger .fix-item strong {
            display: block !important;
            margin-bottom: 8px !important;
            color: #495057 !important;
            width: 100% !important;
        }

        .user-alert-danger .fix-item ul {
            margin: 0 !important;
            padding-left: 20px !important;
            display: block !important;
            width: 100% !important;
        }

        .user-alert-danger .fix-item li {
            font-size: 0.85em !important;
            color: #6c757d !important;
            margin-bottom: 4px !important;
            display: list-item !important;
            list-style-type: disc !important;
            width: auto !important;
        }

        @media (max-width: 768px) {
            .error-navigation {
                flex-direction: column !important;
                align-items: center !important;
            }
            
            .quick-jump-container {
                position: fixed !important;
                bottom: 20px !important;
                right: 20px !important;
                top: auto !important;
                transform: none !important;
                flex-direction: row !important;
            }
            
            .user-alert-danger .error-list {
                max-height: 400px !important;
            }
            
            .file-section-header {
                font-size: 1em !important;
                padding: 10px 12px !important;
            }
            
            .error-item-badge {
                position: static !important;
                display: inline-block !important;
                margin-bottom: 8px !important;
            }
            
            .user-alert-danger .error-message {
                padding-right: 16px !important;
            }
        }

        .scroll-indicator {
            text-align: center !important;
            padding: 8px !important;
            color: #6c757d !important;
            font-size: 0.85em !important;
            font-style: italic !important;
        }

        .scroll-indicator.top {
            background: linear-gradient(to bottom, rgba(248,215,218,0.9), transparent) !important;
        }

        .scroll-indicator.bottom {
            background: linear-gradient(to top, rgba(248,215,218,0.9), transparent) !important;
        }

        /* =====================================================
        ERROR DISPLAY FALLBACK STYLES
        ===================================================== */
        .error-container {
            margin-bottom: 20px;
            display: block !important;
            width: 100%;
            clear: both;
        }

        .error-summary {
            font-weight: bold;
            margin-bottom: 15px;
            color: #721c24;
            display: block !important;
            width: 100%;
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            display: block !important;
            width: 100%;
        }

        .error-item {
            background: #fff5f5;
            border: 1px solid #fed7e2;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 3px solid #e53e3e;
            display: block !important;
            width: 100%;
            box-sizing: border-box;
        }

        .error-message {
            font-weight: 500;
            color: #721c24;
            margin-bottom: 8px;
            display: block !important;
            width: 100%;
        }

        .error-suggestions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 3px;
            padding: 8px;
            font-size: 0.9em;
            color: #856404;
            margin-top: 8px;
            display: block !important;
            width: 100%;
            box-sizing: border-box;
        }

        .suggestions-text {
            color: #856404;
        }

        .validation-help {
            background: #e2e3e5;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            display: block !important;
            clear: both;
            width: 100%;
            box-sizing: border-box;
        }

        .validation-help h4 {
            color: #495057;
            margin-bottom: 12px;
            display: block !important;
            width: 100%;
        }

        .fix-guide {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
            width: 100%;
        }

        .fix-item {
            background: white;
            border-radius: 4px;
            padding: 12px;
            border: 1px solid #ced4da;
            display: block !important;
            box-sizing: border-box;
        }

        .fix-item strong {
            display: block !important;
            margin-bottom: 8px;
            color: #495057;
            width: 100%;
        }

        .fix-item ul {
            margin: 0;
            padding-left: 20px;
            display: block !important;
            width: 100%;
        }

        .fix-item li {
            font-size: 0.85em;
            color: #6c757d;
            margin-bottom: 4px;
            display: list-item !important;
        }

        .error-footer {
            font-weight: bold;
            color: #721c24;
            margin-top: 15px;
            text-align: center;
            display: block !important;
            clear: both;
            padding: 10px 0;
            width: 100%;
        }

        /* =====================================================
        METRIC SUMMARY STYLES
        ===================================================== */
        .metric-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Override gradient background text color for metric boxes inside metric-summary */
        .metric-summary .user-metric-box h4,
        .metric-summary .user-metric-box small {
            color: #000 !important;
        }

        /* =====================================================
        METRIC CARD STYLES
        ===================================================== */
        .metric-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }

        .metric-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .metric-header.success { background: #28a745; color: white; }
        .metric-header.primary { background: #007bff; color: white; }
        .metric-header.secondary { background: #6c757d; color: white; }

        /* =====================================================
        COMPARISON ITEM STYLES
        ===================================================== */
        .comparison-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: box-shadow 0.3s ease;
        }

        .comparison-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .comparison-item h5 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 0.95em;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* =====================================================
        PERIOD COMPARISON STYLES
        ===================================================== */
        .period-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }

        .period-data {
            text-align: center;
            flex: 1;
        }

        .period-data h6 {
            font-size: 0.75em;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .period-data .value {
            font-size: 1.1em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .period-data small {
            color: #6c757d;
            font-size: 0.7em;
        }

        .vs-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            color: #adb5bd;
            font-weight: bold;
            font-size: 0.8em;
        }

        /* =====================================================
        DETAILED VS SECTION STYLES
        ===================================================== */
        .detailed-vs-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }

        .detailed-period-data {
            text-align: center;
            flex: 1;
        }

        .detailed-period-data h6 {
            font-size: 0.75em;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .detailed-period-data .period-value {
            font-size: 1.1em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .detailed-period-data .period-avg {
            color: #6c757d;
            font-size: 0.7em;
            font-weight: 500;
        }

        /* =====================================================
        CHANGE SUMMARY STYLES
        ===================================================== */
        .change-summary {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85em;
            border-left: 4px solid #dee2e6;
        }

        .change-summary .improved {
            border-left-color: #28a745;
        }

        .change-summary .declined {
            border-left-color: #dc3545;
        }

        .change-summary .unchanged {
            border-left-color: #6c757d;
        }

        .metric-percentage {
            font-size: 0.9em;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            background: #f8f9fa;
        }

        .metric-percentage.improved {
            background: #d4edda;
            color: #155724;
        }

        .metric-percentage.declined {
            background: #f8d7da;
            color: #721c24;
        }

        .metric-percentage.unchanged {
            background: #e2e3e5;
            color: #383d41;
        }

        /* =====================================================
        DATA PREVIEW STYLES
        ===================================================== */
        .data-preview-section {
            display: block;
        }

        .preview-column {
            margin-bottom: 25px;
        }

        .preview-column h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
            font-weight: 600;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
            margin: 0;
        }

        .preview-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .preview-table th:last-child {
            border-right: none;
        }

        .preview-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f3f4;
            border-right: 1px solid #f1f3f4;
            color: #495057;
        }

        .preview-table td:last-child {
            border-right: none;
        }

        .preview-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .preview-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }

        .preview-table tbody tr:nth-child(even):hover {
            background-color: #f8f9fa;
        }

        /* =====================================================
        COMPARISON CONTAINER STYLES
        ===================================================== */
        .comparison-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .saved-comparisons {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .file-selection, .comparison-name {
            margin-bottom: 15px;
        }

        .file-selection label, .comparison-name label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .file-selection select, .comparison-name input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* =====================================================
        SCROLLBAR STYLES
        ===================================================== */
        .table-container::-webkit-scrollbar,
        .user-alert-danger .error-list::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track,
        .user-alert-danger .error-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb,
        .user-alert-danger .error-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover,
        .user-alert-danger .error-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* =====================================================
        CLEARFIX AND LAYOUT FIXES
        ===================================================== */
        .user-alert-danger::after,
        .user-alert-danger .error-container::after,
        .user-alert-danger .validation-help::after {
            content: "" !important;
            display: table !important;
            clear: both !important;
        }

        .user-alert-danger .error-container::after,
        .user-alert-danger .validation-help::after,
        .user-alert-danger .error-footer::after {
            content: "";
            display: table;
            clear: both;
        }

        .user-alert-danger .error-container,
        .user-alert-danger .validation-help,
        .user-alert-danger .error-footer {
            width: 100% !important;
            float: none !important;
            clear: both !important;
            display: block !important;
        }

        /* =====================================================
        RESPONSIVE STYLES
        ===================================================== */
        @media (max-width: 768px) {
            .user-alert-danger .fix-guide {
                grid-template-columns: 1fr !important;
            }
            
            .user-alert-danger .error-list {
                max-height: 300px !important;
            }
        }

        /* =====================================================
        FILE INPUT CONTAINER STYLES
        ===================================================== */
        .file-input-container {
            position: relative;
            margin-bottom: 10px;
        }

        .file-input-container input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-button {
            display: inline-block;
            padding: 12px 20px;
            background: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-align: center;
            border: 2px dashed transparent;
            position: relative;
            z-index: 1;
        }

        .file-input-button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .file-input-button i {
            margin-right: 8px;
        }

        /* File info display styles */
        .file-info {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            display: none; /* Initially hidden */
        }

        .file-details {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .file-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            color: #495057;
        }

        .file-detail-item i {
            color: #28a745;
            font-size: 1.1em;
        }

        .file-name {
            font-weight: 600;
            color: #2c3e50;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-size {
            color: #6c757d;
            font-weight: 500;
        }

        /* Selected state styling */
        .file-input-container.has-file .file-input-button {
            background: #28a745;
            border-color: #28a745;
        }

        .file-input-container.has-file .file-input-button:hover {
            background: #218838;
        }

        /* Compare specific file input group styling */
        .compare-user-file-input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .compare-user-file-input-group > div {
            flex: 1;
        }

        .compare-user-file-input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .compare-user-file-input-group small {
            color: #6c757d;
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }

        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .compare-user-file-input-group {
                flex-direction: column;
                gap: 15px;
            }
            
            .file-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .file-name {
                max-width: 100%;
            }
        }

        /* Enhanced Detailed Analytics Comparison Styles */
        .compare-comparison-item {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: box-shadow 0.3s ease;
        }

        .compare-comparison-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .compare-comparison-item h5 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 0.95em;
            font-weight: 600;
            text-transform: capitalize;
        }

        .compare-detailed-vs-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }

        .compare-detailed-period-data {
            text-align: center;
            flex: 1;
        }

        .compare-detailed-period-data h6 {
            font-size: 0.75em; /* Reduced from 0.85em */
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .compare-detailed-period-data .period-value {
            font-size: 1.1em; /* Reduced from 1.4em */
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .compare-period-data small {
            color: #6c757d;
            font-size: 0.7em; /* Reduced from 0.8em */
        }

        .compare-vs-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            color: #adb5bd;
            font-weight: bold;
            font-size: 0.8em; /* Reduced from 0.9em */
        }

        .compare-change-summary {
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85em; /* Reduced from 0.95em */
            border-left: 4px solid #dee2e6;
        }

        .compare-change-summary.improved {
            border-left-color: var(--success);
        }

        .compare-change-summary.declined {
            border-left-color: var(--danger);
        }

        .compare-change-summary.unchanged {
            border-left-color: var(--info);
        }

        /* Grid layout for detailed comparison */
        .compare-detailed-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .compare-detailed-analytics-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .compare-detailed-vs-section {
                flex-direction: column;
                gap: 10px;
            }
            
            .compare-vs-divider {
                margin: 10px 0;
                transform: rotate(90deg);
            }
        }

        @media (max-width: 480px) {
            .compare-comparison-item {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .compare-detailed-period-data .period-value {
                font-size: 1em;
            }
            
            .compare-detailed-period-data h6 {
                font-size: 0.7em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'user_header.php'; ?>

        <main>
						<section class="user-section">
            		<h2>Analytics CSV Comparison</h2>
            		<p>Compare two analytics CSV files to analyze performance metrics including sessions, engagement, revenue, and more.</p>

            <!-- Upload Form -->
            <div class="compare-user-upload-form">
                <h3><i class="fas fa-upload"></i> Upload Analytics CSV Files</h3>
                
                <?php if (!empty($error_message)): ?>
                    <?php
                    // CRITICAL DEBUG: Log what we're about to display
                    error_log("=== ERROR MESSAGE DISPLAY DEBUG ===");
                    error_log("Error message to display: " . $error_message);
                    error_log("show_detailed_errors flag: " . (isset($show_detailed_errors) ? ($show_detailed_errors ? 'true' : 'false') : 'not set'));
                    error_log("compare_validation_errors count: " . (isset($compare_validation_errors) ? count($compare_validation_errors) : 'not set'));
                    ?>
                    <?php if (isset($show_detailed_errors) && $show_detailed_errors && isset($compare_validation_errors)): ?>
                        <!-- Enhanced validation errors display for comparison -->
                        <div class="user-alert user-alert-danger">
                            <?php 
                            error_log("DISPLAY: Using detailed errors display path");
                            
                            // CRITICAL FIX: Parse the error message to extract just the summary
                            $displayErrorMessage = $error_message;
                            
                            // Extract just the summary part using the same pattern as below
                            $summaryPattern = '/^(.*?)(Found \d+ validation errors.*)/s';
                            if (preg_match($summaryPattern, $displayErrorMessage, $matches)) {
                                $summaryOnly = trim($matches[1]);
                                // Remove any "Error processing files: " prefix
                                $summaryOnly = str_replace("Error processing files: ", "", $summaryOnly);
                                $displayErrorMessage = $summaryOnly;
                                error_log("DISPLAY: Extracted summary only: " . $displayErrorMessage);
                            } else {
                                // Fallback: try to find the summary pattern manually
                                if (strpos($displayErrorMessage, '✅') !== false || strpos($displayErrorMessage, '❌') !== false) {
                                    // Find the end of the summary (before "Found X validation errors")
                                    $foundPos = strpos($displayErrorMessage, 'Found ');
                                    if ($foundPos !== false) {
                                        $displayErrorMessage = trim(substr($displayErrorMessage, 0, $foundPos));
                                    }
                                }
                                error_log("DISPLAY: Fallback summary extraction: " . $displayErrorMessage);
                            }
                            ?>
                            <h4><i class="fas fa-exclamation-triangle"></i> File Validation Failed</h4>
                            <p><strong><?php echo htmlspecialchars($displayErrorMessage); ?></strong></p>
                            
                            <?php if (isset($_SESSION['failed_compare_file_info'])): ?>
                                <?php $fileInfo = $_SESSION['failed_compare_file_info']; ?>
                                <div style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 5px;">
                                    <strong>File:</strong> <?php echo htmlspecialchars($fileInfo['name']); ?> (File <?php echo $fileInfo['file_index']; ?>)<br>
                                    <strong>Mapping Status:</strong> Column mapping was successful<br>
                                    <strong>Issue:</strong> Data validation failed during processing (<?php echo $fileInfo['error_count']; ?> errors, <?php echo $fileInfo['unique_error_count']; ?> unique types)
                                </div>
                            <?php endif; ?>
                            
                            <div class="error-container" style="margin-top: 15px;">
                                <div class="error-summary">
                                    <h5>Found <?php echo count($compare_validation_errors); ?> validation issues:</h5>
                                </div>
                                
                                <div class="error-list" style="max-height: 400px; overflow-y: auto; margin: 15px 0;">
                                    <?php foreach ($compare_validation_errors as $index => $error): ?>
                                        <div class="error-item" style="padding: 8px 12px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">
                                            <?php
                                            // Parse error and suggestions
                                            $parts = explode(' Suggestions: ', $error);
                                            $mainError = $parts[0];
                                            $suggestions = isset($parts[1]) ? $parts[1] : '';
                                            ?>
                                            <div class="error-message" style="font-family: 'Courier New', monospace; font-size: 0.9em; color: #721c24;">
                                                <?php echo htmlspecialchars($mainError); ?>
                                            </div>
                                            <?php if (!empty($suggestions)): ?>
                                                <div class="error-suggestions" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; padding: 8px; font-size: 0.9em; color: #856404; margin-top: 8px;">
                                                    <strong>💡 Suggestions:</strong> 
                                                    <span class="suggestions-text"><?php echo htmlspecialchars($suggestions); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Keep the existing validation help and error footer sections -->
                            <div class="validation-help" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;">
                                <h4 style="color: #155724; margin-bottom: 10px;">💡 How to Fix These Issues</h4>
                                <div class="fix-guide">
                                    <div class="fix-item" style="margin-bottom: 12px;">
                                        <strong style="color: #155724;">For Number Fields:</strong>
                                        <ul style="margin: 5px 0 0 20px;">
                                            <li>Remove any letters, symbols, or special characters</li>
                                            <li>Use only digits and decimal points (e.g., "123", "45.67")</li>
                                            <li>Convert percentages to decimals (e.g., "75%" → "0.75")</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item" style="margin-bottom: 12px;">
                                        <strong style="color: #155724;">For Time Values:</strong>
                                        <ul style="margin: 5px 0 0 20px;">
                                            <li>Convert time formats to seconds (e.g., "5m30s" → "330")</li>
                                            <li>Use decimal format for partial seconds (e.g., "45.5")</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item" style="margin-bottom: 12px;">
                                        <strong style="color: #155724;">For Negative/Invalid Values:</strong>
                                        <ul style="margin: 5px 0 0 20px;">
                                            <li>Ensure all values are positive numbers</li>
                                            <li>Check for realistic ranges (engagement rates 0-1, etc.)</li>
                                            <li>Remove any text labels or descriptive words</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="error-footer" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6; text-align: center;">
                                <p style="color: #6c757d; margin: 0; font-size: 0.9em;">
                                    <strong>Tip:</strong> Fix the errors in your CSV file and try uploading again.
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (strpos($error_message, 'Data validation errors') !== false || 
                                strpos($error_message, 'No valid data to save') !== false ||
                                strpos($error_message, 'CSV parsing error') !== false ||
                                strpos($error_message, 'Found ') === 0 ||
                                strpos($error_message, 'validation errors') !== false): ?>
                            
                            <?php
                                // This parsing logic is already correct - it creates $errorPrefix
                                // which contains only the summary message
                                $errorMessage = $error_message;
                                $errorMessage = str_replace("Error processing files: ", "", $errorMessage);

                                error_log("=== COMPARE.PHP ERROR PARSING DEBUG ===");
                                error_log("Original error message: " . $error_message);
                                error_log("Cleaned error message: " . $errorMessage);

                                // Initialize variables
                                $errorPrefix = "";
                                $allErrorList = [];
                                $file1ErrorCount = 0;
                                $file2ErrorCount = 0;
                                $file1Success = false;
                                $file2Success = false;

                                // ENHANCED: Check for "Both files failed" scenario first
                                if (strpos($errorMessage, '❌ Both files failed validation') === 0) {
                                    error_log("DETECTED: Both files failed scenario");
                                    $file1Success = false;
                                    $file2Success = false;
                                    $errorPrefix = "❌ Both files failed validation";
                                    
                                    // Parse all validation errors and separate by file headers
                                    $lines = explode("\n", $errorMessage);
                                    $currentFileSection = null;
                                    
                                    foreach ($lines as $line) {
                                        $line = trim($line);
                                        if (strpos($line, '--- File 1 Errors ---') === 0) {
                                            $currentFileSection = 1;
                                            $allErrorList[] = $line; // Add the header
                                        } elseif (strpos($line, '--- File 2 Errors ---') === 0) {
                                            $currentFileSection = 2;
                                            $allErrorList[] = $line; // Add the header
                                        } elseif (!empty($line) && strpos($line, 'Row ') === 0 && $currentFileSection !== null) {
                                            $allErrorList[] = $line;
                                            if ($currentFileSection == 1) {
                                                $file1ErrorCount++;
                                            } elseif ($currentFileSection == 2) {
                                                $file2ErrorCount++;
                                            }
                                        }
                                    }
                                } else {
                                    // STEP 1: Extract the summary message and separate it from detailed errors
                                    $summaryPattern = '/^(.*?)(Found \d+ validation errors.*)/s';
                                    if (preg_match($summaryPattern, $errorMessage, $matches)) {
                                        $summaryMessage = trim($matches[1]);
                                        $detailedErrors = trim($matches[2]);
                                        
                                        error_log("PARSED: Summary message: " . $summaryMessage);
                                        error_log("PARSED: Detailed errors start: " . substr($detailedErrors, 0, 100) . "...");
                                        
                                        // Use the summary as our main error message
                                        $errorPrefix = $summaryMessage;
                                        
                                        // Determine file success/failure from summary
                                        if (strpos($summaryMessage, '✅') !== false && strpos($summaryMessage, 'First file') !== false) {
                                            $file1Success = true;
                                        }
                                        if (strpos($summaryMessage, '✅') !== false && strpos($summaryMessage, 'Second file') !== false) {
                                            $file2Success = true;
                                        }
                                        if (strpos($summaryMessage, '❌') !== false && strpos($summaryMessage, 'First file') !== false) {
                                            $file1Success = false;
                                        }
                                        if (strpos($summaryMessage, '❌') !== false && strpos($summaryMessage, 'Second file') !== false) {
                                            $file2Success = false;
                                        }
                                        
                                        // Parse the detailed errors from the "Found X validation errors" part
                                        $lines = explode("\n", $detailedErrors);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (!empty($line) && strpos($line, 'Row ') === 0) {
                                                $allErrorList[] = $line;
                                                
                                                // Since we know from summary which file failed, count accordingly
                                                if (!$file1Success && $file2Success) {
                                                    $file1ErrorCount++;
                                                } elseif ($file1Success && !$file2Success) {
                                                    $file2ErrorCount++;
                                                } else {
                                                    // Both files failed - count for both
                                                    $file1ErrorCount++;
                                                    $file2ErrorCount++;
                                                }
                                            }
                                        }
                                    } else {
                                        // Fallback parsing if regex doesn't match
                                        error_log("FALLBACK: Regex didn't match, using fallback parsing");
                                        
                                        if (strpos($errorMessage, '✅ First file uploaded successfully, but ❌ second file failed') !== false) {
                                            $file1Success = true;
                                            $file2Success = false;
                                            $errorPrefix = "✅ First file uploaded successfully, but ❌ second file failed";
                                        } elseif (strpos($errorMessage, '❌ First file failed, but ✅ second file uploaded successfully') !== false) {
                                            $file1Success = false;
                                            $file2Success = true;
                                            $errorPrefix = "❌ First file failed, but ✅ second file uploaded successfully";
                                        } elseif (strpos($errorMessage, 'Both files failed') !== false) {
                                            $file1Success = false;
                                            $file2Success = false;
                                            $errorPrefix = "❌ Both files failed validation";
                                        } else {
                                            // Default case
                                            $file1Success = true;
                                            $file2Success = false;
                                            $errorPrefix = "✅ First file uploaded successfully, but ❌ second file failed";
                                        }
                                        
                                        // Parse all validation errors
                                        $lines = explode("\n", $errorMessage);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (!empty($line) && strpos($line, 'Row ') === 0) {
                                                $allErrorList[] = $line;
                                                if (!$file2Success) {
                                                    $file2ErrorCount++;
                                                }
                                                if (!$file1Success) {
                                                    $file1ErrorCount++;
                                                }
                                            }
                                        }
                                    }
                                }

                                error_log("Final parsed results - File1 Success: " . ($file1Success ? 'YES' : 'NO') . ", File2 Success: " . ($file2Success ? 'YES' : 'NO'));
                                error_log("Error counts - File1: $file1ErrorCount, File2: $file2ErrorCount");
                                error_log("Total errors found: " . count($allErrorList));
                                error_log("Error prefix: " . $errorPrefix);
                                ?>

                                <div class="user-alert user-alert-danger">
                                            <h4><i class="fas fa-exclamation-triangle"></i> File Validation Failed</h4>
                                            
                                            <!-- This is correct - uses $errorPrefix which contains only the summary -->
                                            <?php if (!empty($errorPrefix)): ?>
                                                <p><strong><?php echo $errorPrefix; ?></strong></p>
                                            <?php else: ?>
                                                <p><strong>One of your comparison files couldn't be processed due to data validation errors.</strong></p>
                                            <?php endif; ?>
                                
                                <div class="error-container">
                                    <!-- IMPROVED: Show proper error summary -->
                                    <?php if ($file1Success && !$file2Success): ?>
                                        <p class="error-summary">Found <?php echo count($allErrorList); ?> validation errors in File 2:</p>
                                    <?php elseif (!$file1Success && $file2Success): ?>
                                        <p class="error-summary">Found <?php echo count($allErrorList); ?> validation errors in File 1:</p>
                                    <?php elseif (!$file1Success && !$file2Success): ?>
                                        <?php 
                                        // Check if we have file separation headers
                                        $hasFileSeparation = false;
                                        foreach ($allErrorList as $error) {
                                            if (strpos($error, '--- File 1 Errors ---') === 0 || strpos($error, '--- File 2 Errors ---') === 0) {
                                                $hasFileSeparation = true;
                                                break;
                                            }
                                        }
                                        
                                        if ($hasFileSeparation): ?>
                                            <p class="error-summary">Both files failed validation. Use the navigation buttons on the right to jump between file errors:</p>
                                        <?php else: ?>
                                            <p class="error-summary">Both files failed validation with a total of <?php echo count($allErrorList); ?> errors:</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="error-summary">Found <?php echo count($allErrorList); ?> validation errors:</p>
                                    <?php endif; ?>
                                    
                                    <div class="error-list" style="max-height: 400px; overflow-y: auto; margin: 15px 0;">
                                        <?php foreach($allErrorList as $error): ?>
                                            <?php $error = trim($error); ?>
                                            <?php if(!empty($error)): ?>
                                                <div class="error-item" style="padding: 8px 12px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">
                                                    <?php
                                                    // CRITICAL FIX: Parse error and suggestions properly
                                                    $parts = explode(' Suggestions: ', $error);
                                                    $mainError = $parts[0];
                                                    $suggestions = isset($parts[1]) ? $parts[1] : '';
                                                    ?>
                                                    <div class="error-message" style="font-family: 'Courier New', monospace; font-size: 0.9em; color: #721c24;">
                                                        <?php echo htmlspecialchars($mainError); ?>
                                                    </div>
                                                    <?php if (!empty($suggestions)): ?>
                                                        <div class="error-suggestions" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; padding: 8px; font-size: 0.9em; color: #856404; margin-top: 8px;">
                                                            <strong>💡 Suggestions:</strong> 
                                                            <span class="suggestions-text"><?php echo htmlspecialchars($suggestions); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                                                        
                                <div class="validation-help" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;">
                                    <h4 style="color: #155724; margin-bottom: 10px;">💡 How to Fix These Issues</h4>
                                    <div class="fix-guide">
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">📁 File Format Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Ensure CSV has proper headers</li>
                                                <li>Check for GA4 metadata lines starting with #</li>
                                                <li>Verify file isn't corrupted or empty</li>
                                                <li>Make sure data rows aren't all empty</li>
                                            </ul>
                                        </div>
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">🔢 Integer Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Remove letters: "15a" → "15"</li>
                                                <li>Evaluate expressions: "42+3" → "45"</li>
                                                <li>Convert Unicode: "５０" → "50"</li>
                                            </ul>
                                        </div>
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">📊 Float/Decimal Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Fix multiple decimals: "8..5" → "8.5"</li>
                                                <li>Convert scientific: "1.2e3" → "1200"</li>
                                                <li>Remove special chars: "~5.3" → "5.3"</li>
                                            </ul>
                                        </div>
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">⏰ Time Format Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Use proper format: "10:65:30" → "11:05:30"</li>
                                                <li>Convert units: "12m30s" → "12:30" or "750"</li>
                                            </ul>
                                        </div>
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">💰 Currency Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Remove symbols: "$1,200" → "1200"</li>
                                                <li>Remove commas: "500.abc" → "500"</li>
                                            </ul>
                                        </div>
                                        <div class="fix-item" style="margin-bottom: 12px;">
                                            <strong style="color: #155724;">🚫 Common CSV Issues:</strong>
                                            <ul style="margin: 5px 0 0 20px;">
                                                <li>Remove trademark symbols: ™, ®, ©</li>
                                                <li>Fix unquoted commas in data fields</li>
                                                <li>Remove leading/trailing whitespace</li>
                                                <li>Check for mixed data types in columns</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                                                        
                                <div class="error-footer" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6; text-align: center;">
                                    <p style="color: #6c757d; margin: 0; font-size: 0.9em;">
                                        <strong>Tip:</strong> Fix the errors in your CSV file and try uploading again.
                                    </p>
                                </div>
                            </div>
                                                                        
                        <?php else: ?>
                            <!-- Display regular error message -->
                            <div class="user-alert user-alert-danger">
                                <h4><i class="fas fa-exclamation-triangle"></i> Upload Error</h4>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
										<div class="compare-user-file-input-group">
												<div>
														<label>First Period CSV File</label>
														<div class="file-input-container">
																<input type="file" id="csv_file1" name="csv_file1" accept=".csv" required>
																<label for="csv_file1" class="file-input-button">
																		<i class="fas fa-upload"></i>
																		Choose First Period CSV
																</label>
																<div class="file-info" id="fileInfo1" style="display: none;">
																		<div class="file-details">
																				<div class="file-detail-item">
																						<i class="fas fa-file-csv"></i>
																						<span class="file-name">-</span>
																				</div>
																				<div class="file-detail-item">
																						<i class="fas fa-weight-hanging"></i>
																						<span class="file-size">-</span>
																				</div>
																		</div>
																</div>
														</div>
														<small>Upload your first analytics period data</small>
												</div>
												<div>
														<label>Second Period CSV File</label>
														<div class="file-input-container">
																<input type="file" id="csv_file2" name="csv_file2" accept=".csv" required>
																<label for="csv_file2" class="file-input-button">
																		<i class="fas fa-upload"></i>
																		Choose Second Period CSV
																</label>
																<div class="file-info" id="fileInfo2" style="display: none;">
																		<div class="file-details">
																				<div class="file-detail-item">
																						<i class="fas fa-file-csv"></i>
																						<span class="file-name">-</span>
																				</div>
																				<div class="file-detail-item">
																						<i class="fas fa-weight-hanging"></i>
																						<span class="file-size">-</span>
																				</div>
																		</div>
																</div>
														</div>
														<small>Upload your second analytics period data</small>
												</div>
                                            </div>
                    <button type="submit" class="compare-user-btn-submit">
                        <i class="fas fa-chart-bar"></i> Compare Analytics Data
                    </button>
                </form>
            </div>

            <div class="compare-comparison-container">
                <h3>Compare CSV Files</h3>
                            
                <!-- Load Saved Comparison -->
                <?php if (!empty($savedComparisons)): ?>
                <div class="compare-saved-comparisons">
                    <h4>Load Saved Comparison</h4>
                    <form method="POST">
                        <select name="saved_comparison_id" required>
                            <option value="">Select a saved comparison...</option>
                            <?php foreach ($savedComparisons as $comparison): ?>
                                <option value="<?php echo $comparison['ComparisonID']; ?>">
                                    <?php echo htmlspecialchars($comparison['ComparisonName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="load_comparison" class="compare-button">Load Comparison</button>
                    </form>
                    <hr>
                </div>
                <?php endif; ?>
                            
                <!-- New Comparison -->
                <form method="POST">
                    <div class="compare-file-selection">
                        <label>First CSV File:</label>
                        <select name="upload1" required>
                            <option value="">Select first file...</option>
                            <?php foreach ($csvFiles as $file): ?>
                                <option value="<?php echo $file['UploadID']; ?>" 
                                        <?php echo (isset($upload1) && $upload1 == $file['UploadID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($file['FileName']) . ' (' . date('M j, Y g:i A', strtotime($file['UploadDate'])) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                            
                    <div class="compare-file-selection">
                        <label>Second CSV File:</label>
                        <select name="upload2" required>
                            <option value="">Select second file...</option>
                            <?php foreach ($csvFiles as $file): ?>
                                <option value="<?php echo $file['UploadID']; ?>"
                                        <?php echo (isset($upload2) && $upload2 == $file['UploadID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($file['FileName']) . ' (' . date('M j, Y g:i A', strtotime($file['UploadDate'])) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                            
                    <div class="compare-comparison-name">
                        <label>Comparison Name (optional):</label>
                        <input type="text" name="comparisonName" placeholder="Enter a name to save this comparison">
                    </div>
                            
                    <button type="submit" name="compare" class="btn">Save Comparison</button>
                </form>
            </div>

            <!-- Export Controls (only show if we have comparison data) -->
            <?php if (isset($comparison_results) && !empty($comparison_results)): ?>
            <div class="user-export-controls" style="margin: 20px 0; text-align: right;">
                <button class="user-export-btn csv" onclick="exportToCSV()" style="background: #28a745; color: white; padding: 10px 20px; margin-right: 10px; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button class="user-export-btn pdf" onclick="exportToPDF()" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
            </div>
            <?php endif; ?>


            <!-- Comparison Results -->
            <?php if ($comparison_results): ?>
                <!-- Debug Information -->
                <div class="compare-alert compare-alert-info">
                    <h4><i class="fas fa-bug"></i> Debug Information</h4>
                    <p><strong>Available CSV Headers:</strong><br>
                    <small><?php echo implode(' | ', $comparison_results['headers']['common_headers']); ?></small></p>

                    <p><strong>Analytics Metrics Detection Results:</strong><br>
                    <?php 
                    $all_metrics = ['sessions', 'engaged_sessions', 'engagement_rate', 'average_engagement_time_per_session',
                                   'events_per_session', 'event_count', 'key_events', 'session_key_event_rate',
                                   'total_revenue', 'total_page_views', 'unique_visitors', 'average_session_duration',
                                   'bounce_rate'];

                    foreach ($all_metrics as $metric) {
                        $found = isset($comparison_results['analytics_metrics'][$metric]);
                        $color = $found ? 'green' : 'red';
                        $status = $found ? '✓ Found' : '✗ Not Found';
                        echo '<small style="color: ' . $color . ';">' . $metric . ': ' . $status;
                        if ($found) {
                            echo ' → ' . $comparison_results['analytics_metrics'][$metric]['column_name'];
                        }
                        echo '</small><br>';
                    }
                    ?>
                    </p>

                    <p><strong>Performance Overview Metrics:</strong><br>
                    <?php 
                    $key_metrics = ['sessions', 'engagement_rate', 'total_revenue', 'bounce_rate', 
                                   'unique_visitors', 'total_page_views', 'average_session_duration'];
                    foreach ($key_metrics as $metric) {
                        $available = isset($comparison_results['summary_comparison'][$metric]);
                        $color = $available ? 'blue' : 'orange';
                        echo '<small style="color: ' . $color . ';">' . $metric . ': ' . ($available ? 'Available' : 'Not Available') . '</small><br>';
                    }
                    ?>
                    </p>
                </div>

            <!-- Performance Overview -->
            <?php if (!empty($comparison_results['summary_comparison'])): ?>
                <div class="compare-metric-summary">
                    <h3>📊 Performance Overview</h3>
                    <div class="compare-user-stats-grid">
                        <?php 
                        // Define display metrics with proper names
                        $display_metrics = [
                            'traffic_source' => 'Traffic Source',
                            'sessions' => 'Sessions', 
                            'engaged_sessions' => 'Engaged Sessions',
                            'engagement_rate' => 'Engagement Rate',
                            'average_engagement_time_per_session' => 'Average Engagement Time Per Session',
                            'events_per_session' => 'Events Per Session',
                            'event_count' => 'Event Count',
                            'key_events' => 'Key Events',
                            'session_key_event_rate' => 'Session Key Event Rate',
                            'total_revenue' => 'Total Revenue'
                        ];
                        
                        foreach ($display_metrics as $metric_key => $metric_name): 
                            if (isset($comparison_results['summary_comparison'][$metric_key])):
                                $metric_data = $comparison_results['summary_comparison'][$metric_key];
                                $total_value = $metric_data['file2_total'] ?? 0;
                                $percent_change = $metric_data['percent_change'] ?? 0;
                                $improvement = $metric_data['improvement'] ?? 'neutral';
                                
                                // Format the value appropriately
                                if ($metric_key === 'engagement_rate' || $metric_key === 'session_key_event_rate') {
                                    $formatted_value = number_format($total_value, 3);
                                } elseif ($metric_key === 'total_revenue') {
                                    $formatted_value = number_format($total_value, 2);
                                } elseif ($metric_key === 'average_engagement_time_per_session' || $metric_key === 'events_per_session') {
                                    $formatted_value = number_format($total_value, 1);
                                } else {
                                    $formatted_value = number_format($total_value, 0);
                                }
                                
                                $improvement_class = $improvement;
                                $change_symbol = '';
                                if ($percent_change > 0) {
                                    $change_symbol = '+';
                                }
                        ?>
                            <div class="compare-user-metric-box">
                                <h4><?php echo $formatted_value; ?></h4>
                                <small><?php echo htmlspecialchars($metric_name); ?></small>
                                <div class="metric-change <?php echo $improvement_class; ?>">
                                    <?php echo $change_symbol . number_format($percent_change, 1); ?>% 
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detailed Analytics Comparison -->
            <?php if (!empty($comparison_results['analytics_metrics'])): ?>
                <div class="compare-comparison-card">
                    <h3>📈 Detailed Analytics Comparison</h3>
                    <div class="compare-detailed-analytics-grid">
                        <?php foreach ($comparison_results['analytics_metrics'] as $metric => $data): ?>
                            <?php if ($data['available'] && isset($comparison_results['summary_comparison'][$metric])): ?>
                                <?php 
                                $summary = $comparison_results['summary_comparison'][$metric];
                                $improvement = $summary['improvement'] ?? 'neutral';
                                $total_diff = $summary['total_diff'] ?? 0;
                                $percent_change = $summary['percent_change'] ?? 0;
                                $avg1 = $summary['avg1'] ?? 0;
                                $avg2 = $summary['avg2'] ?? 0;
                                
                                $metric_name = ucwords(str_replace('_', ' ', $metric));
                                ?>
                                <div class="compare-comparison-item">
                                    <h5><?php echo htmlspecialchars($metric_name); ?></h5>
                                    <div class="compare-detailed-vs-section">
                                        <div class="compare-detailed-period-data">
                                            <h6>Period 1</h6>
                                            <div class="period-value"><?php echo number_format($summary['file1_total'] ?? 0, ($metric === 'engagement_rate' || $metric === 'session_key_event_rate') ? 3 : (($metric === 'total_revenue' || $metric === 'average_engagement_time_per_session' || $metric === 'events_per_session') ? 1 : 0)); ?></div>
                                            <small>Avg: <?php echo number_format($avg1, 1); ?></small>
                                        </div>
                                        <div class="compare-vs-divider">VS</div>
                                        <div class="compare-detailed-period-data">
                                            <h6>Period 2</h6>
                                            <div class="period-value"><?php echo number_format($summary['file2_total'] ?? 0, ($metric === 'engagement_rate' || $metric === 'session_key_event_rate') ? 3 : (($metric === 'total_revenue' || $metric === 'average_engagement_time_per_session' || $metric === 'events_per_session') ? 1 : 0)); ?></div>
                                            <small>Avg: <?php echo number_format($avg2, 1); ?></small>
                                        </div>
                                    </div>
                                    <div class="compare-change-summary <?php echo $improvement; ?>">
                                        Change: <?php echo number_format($total_diff, ($metric === 'engagement_rate' || $metric === 'session_key_event_rate') ? 3 : (($metric === 'total_revenue') ? 2 : 0)); ?> (<?php echo number_format($percent_change, 1); ?>%)
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

                <!-- Basic File Information -->
                <div class="compare-comparison-card">
                    <h3>📋 File Information</h3>
                    <div class="compare-user-stats-grid">
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_rows'] ?? 0; ?></h4>
                            <small>Period 1 Records</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file2_rows'] ?? 0; ?></h4>
                            <small>Period 2 Records</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['common_columns'] ?? 0; ?></h4>
                            <small>Total Columns</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo count($comparison_results['headers']['common'] ?? []); ?></h4>
                            <small>Common Columns</small>
                        </div>
                    </div>
                </div>

                <!-- Data Samples -->
                <div class="compare-comparison-card">
                    <div class="compare-metric-header secondary">
                        <i class="fas fa-eye"></i> Data Preview (First 5 Records)
                    </div>
                    <div class="compare-data-preview-section">
                        <div class="compare-preview-column">
                            <h4>Period 1 Sample</h4>
                            <div class="compare-table-container">
                                <table class="compare-preview-table">
                                    <?php if (!empty($comparison_results['data_sample']['file1_sample'])): ?>
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys($comparison_results['data_sample']['file1_sample'][0]) as $header): ?>
                                                    <th><?php echo htmlspecialchars($header); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comparison_results['data_sample']['file1_sample'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?php echo htmlspecialchars($value); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                                                    
                        <div class="compare-preview-column">
                            <h4>Period 2 Sample</h4>
                            <div class="compare-table-container">
                                <table class="compare-preview-table">
                                    <?php if (!empty($comparison_results['data_sample']['file2_sample'])): ?>
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys($comparison_results['data_sample']['file2_sample'][0]) as $header): ?>
                                                    <th><?php echo htmlspecialchars($header); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comparison_results['data_sample']['file2_sample'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?php echo htmlspecialchars($value); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <?php include 'user_footer.php'; ?>
		</div>

    
    <script>

    const comparisonData = <?php echo json_encode($comparison_results ?? []); ?>;
    
    // Export Functions
    function exportToCSV() {
        if (typeof comparisonData === 'undefined' || !comparisonData) {
            alert('No comparison data available to export');
            return;
        }

        let csv = 'TrafAnalyz Analytics Comparison Report\n';
        csv += `Generated on: ${new Date().toLocaleString()}\n`;
        csv += `Generated by: <?php echo $_SESSION['username'] ?? 'Unknown User'; ?>\n\n`;

        // 1. Performance Overview
        if (comparisonData.summary_comparison && Object.keys(comparisonData.summary_comparison).length > 0) {
            csv += '1. PERFORMANCE OVERVIEW\n';
            csv += 'Metric,Period 1 Total,Period 2 Total,Change,Percentage Change,Status\n';
            
            Object.entries(comparisonData.summary_comparison).forEach(([metric, data]) => {
                if (data && typeof data === 'object') {
                    const metricName = metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const file1Total = (data.file1_total || 0).toLocaleString();
                    const file2Total = (data.file2_total || 0).toLocaleString();
                    const change = ((data.total_diff || 0) > 0 ? '+' : '') + (data.total_diff || 0).toLocaleString();
                    const percentage = ((data.percent_change || 0) > 0 ? '+' : '') + (data.percent_change || 0) + '%';
                    const status = (data.status || 'unknown').charAt(0).toUpperCase() + (data.status || 'unknown').slice(1);
                    
                    csv += `"${metricName}","${file1Total}","${file2Total}","${change}","${percentage}","${status}"\n`;
                }
            });
            csv += '\n';
        }

        // 2. Detailed Analytics Comparison
        if (comparisonData.analytics_metrics && Object.keys(comparisonData.analytics_metrics).length > 0) {
            csv += '2. DETAILED ANALYTICS COMPARISON\n';
            csv += 'Metric,Period 1 Total,Period 1 Average,Period 2 Total,Period 2 Average,Change,Percentage Change,Status\n';
            
            Object.entries(comparisonData.analytics_metrics).forEach(([metric, analysis]) => {
                if (analysis && analysis.file1_stats && analysis.file2_stats && analysis.comparison) {
                    const metricName = metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const p1Total = (analysis.file1_stats.sum || 0).toLocaleString();
                    const p1Avg = (analysis.file1_stats.mean || 0).toFixed(2);
                    const p2Total = (analysis.file2_stats.sum || 0).toLocaleString();
                    const p2Avg = (analysis.file2_stats.mean || 0).toFixed(2);
                    const change = ((analysis.comparison.total_diff || 0) > 0 ? '+' : '') + (analysis.comparison.total_diff || 0).toLocaleString();
                    const percentage = ((analysis.comparison.percent_change || 0) > 0 ? '+' : '') + (analysis.comparison.percent_change || 0) + '%';
                    const status = (analysis.comparison.improvement || 'neutral').charAt(0).toUpperCase() + (analysis.comparison.improvement || 'neutral').slice(1);
                    
                    csv += `"${metricName}","${p1Total}","${p1Avg}","${p2Total}","${p2Avg}","${change}","${percentage}","${status}"\n`;
                }
            });
            csv += '\n';
        }

        // 3. File Information
        if (comparisonData.basic_metrics && comparisonData.headers) {
            csv += '3. FILE INFORMATION\n';
            csv += 'Information,Period 1,Period 2,Details\n';
            
            const file1Rows = (comparisonData.basic_metrics.file1_rows || 0).toLocaleString();
            const file2Rows = (comparisonData.basic_metrics.file2_rows || 0).toLocaleString();
            const rowDiff = ((comparisonData.basic_metrics.file2_rows || 0) - (comparisonData.basic_metrics.file1_rows || 0));
            const rowDetails = (rowDiff > 0 ? '+' : '') + rowDiff.toLocaleString() + ' records';
            csv += `"Total Records","${file1Rows}","${file2Rows}","${rowDetails}"\n`;
            
            const file1Cols = (comparisonData.basic_metrics.file1_columns || 0).toString();
            const file2Cols = (comparisonData.basic_metrics.file2_columns || 0).toString();
            const colDetails = (comparisonData.basic_metrics.file1_columns || 0) === (comparisonData.basic_metrics.file2_columns || 0) ? 'Same structure' : 'Different structure';
            csv += `"Total Columns","${file1Cols}","${file2Cols}","${colDetails}"\n`;
            
            const commonCols = (comparisonData.headers.common_headers ? comparisonData.headers.common_headers.length : 0).toString();
            csv += `"Common Columns","${commonCols}","${commonCols}","Compatible for comparison"\n`;
            csv += '\n';
        }

        // 4. Data Preview - Period 1
        if (comparisonData.data_sample && comparisonData.data_sample.file1_sample && comparisonData.data_sample.file1_sample.length > 0) {
            csv += '4a. PERIOD 1 SAMPLE DATA (First 5 Records)\n';
            
            // Headers
            const headers1 = ['Record #'].concat(Object.keys(comparisonData.data_sample.file1_sample[0] || {}));
            csv += headers1.map(h => `"${h}"`).join(',') + '\n';
            
            // Data rows
            comparisonData.data_sample.file1_sample.slice(0, 5).forEach((row, index) => {
                const rowData = [index + 1].concat(Object.values(row || {}));
                csv += rowData.map(cell => `"${String(cell || '').replace(/"/g, '""')}"`).join(',') + '\n';
            });
            csv += '\n';
        }

        // 5. Data Preview - Period 2
        if (comparisonData.data_sample && comparisonData.data_sample.file2_sample && comparisonData.data_sample.file2_sample.length > 0) {
            csv += '4b. PERIOD 2 SAMPLE DATA (First 5 Records)\n';
            
            // Headers
            const headers2 = ['Record #'].concat(Object.keys(comparisonData.data_sample.file2_sample[0] || {}));
            csv += headers2.map(h => `"${h}"`).join(',') + '\n';
            
            // Data rows
            comparisonData.data_sample.file2_sample.slice(0, 5).forEach((row, index) => {
                const rowData = [index + 1].concat(Object.values(row || {}));
                csv += rowData.map(cell => `"${String(cell || '').replace(/"/g, '""')}"`).join(',') + '\n';
            });
            csv += '\n';
        }

        // Summary Information
        csv += 'EXPORT INFORMATION\n';
        csv += `Export Date,"${new Date().toLocaleString()}"\n`;
        csv += `Export Type,"Complete Analytics Comparison Report"\n`;
        csv += `Data Source,"CSV File Comparison"\n`;

        // Create and download CSV
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `TrafAnalyz_Comparison_Report_${new Date().getFullYear()}${String(new Date().getMonth() + 1).padStart(2, '0')}${String(new Date().getDate()).padStart(2, '0')}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        // Log export to database
        fetch('log_export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `exportType=CSV&description=Exported complete analytics comparison report as CSV with all sections`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('CSV export logged successfully');
            }
        }).catch(error => {
            console.error('Error logging CSV export:', error);
        });
    }

    async function exportToPDF() {
        if (!window.jspdf) {
            alert('PDF export library not loaded. Please refresh the page and try again.');
            return;
        }

        if (typeof comparisonData === 'undefined' || !comparisonData) {
            alert('No comparison data available to export');
            return;
        }

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        // Get current date and time
        const now = new Date();
        const generatedDate = now.getFullYear() + '-' + 
            String(now.getMonth() + 1).padStart(2, '0') + '-' + 
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');

        // Get username from session
        const username = '<?php echo $_SESSION['username'] ?? 'Unknown User'; ?>';

        // PDF styling
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 15;
        let yPosition = 25;

        try {
            // Header
            pdf.setFontSize(18);
            pdf.setFont('helvetica', 'bold');
            pdf.text('TrafAnalyz Analytics Comparison Report', pageWidth/2, yPosition, { align: 'center' });

            yPosition += 12;

            // Generated info
            pdf.setFontSize(9);
            pdf.setFont('helvetica', 'normal');
            pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
            yPosition += 4;
            pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });

            yPosition += 15;

            // Helper function to add a new page if needed
            function checkPageBreak(requiredHeight) {
                if (yPosition + requiredHeight > pageHeight - margin) {
                    pdf.addPage();
                    yPosition = margin + 10;
                    return true;
                }
                return false;
            }

            // Helper function to truncate long text
            function truncateText(text, maxLength = 25) {
                if (String(text).length > maxLength) {
                    return String(text).substring(0, maxLength) + '...';
                }
                return String(text);
            }

            // Helper function to create table from comparison data
            function createComparisonTable(title, data, headers) {
                if (!data || data.length === 0) {
                    return;
                }

                checkPageBreak(30);
                
                // Section title
                pdf.setFontSize(12);
                pdf.setFont('helvetica', 'bold');
                pdf.text(title, margin, yPosition);
                yPosition += 8;

                // Calculate column width based on number of columns
                const colWidth = Math.max(20, (pageWidth - 2 * margin) / headers.length);
                const rowHeight = 6;

                // Headers
                pdf.setFillColor(240, 240, 240);
                pdf.rect(margin, yPosition, pageWidth - 2 * margin, rowHeight, 'F');
                
                pdf.setFontSize(7);
                pdf.setFont('helvetica', 'bold');
                headers.forEach((header, index) => {
                    const xPos = margin + (index * colWidth) + 2;
                    if (xPos < pageWidth - margin) {
                        pdf.text(truncateText(header, 15), xPos, yPosition + 4);
                    }
                });
                yPosition += rowHeight;

                // Data rows
                pdf.setFont('helvetica', 'normal');
                data.forEach((row, rowIndex) => {
                    checkPageBreak(rowHeight + 2);
                    
                    // Alternate row colors
                    if (rowIndex % 2 === 0) {
                        pdf.setFillColor(250, 250, 250);
                        pdf.rect(margin, yPosition, pageWidth - 2 * margin, rowHeight, 'F');
                    }

                    row.forEach((cell, colIndex) => {
                        const xPos = margin + (colIndex * colWidth) + 2;
                        if (xPos < pageWidth - margin) {
                            pdf.text(truncateText(cell || '', 20), xPos, yPosition + 4);
                        }
                    });
                    yPosition += rowHeight;
                });

                yPosition += 10; // Space after table
            }

            // 1. Performance Overview Table
            if (comparisonData.summary_comparison && Object.keys(comparisonData.summary_comparison).length > 0) {
                const perfHeaders = ['Metric', 'Period 1', 'Period 2', 'Change', '% Change', 'Status'];
                const perfData = [];
                
                Object.entries(comparisonData.summary_comparison).forEach(([metric, data]) => {
                    if (data && typeof data === 'object') {
                        perfData.push([
                            metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                            (data.file1_total || 0).toLocaleString(),
                            (data.file2_total || 0).toLocaleString(),
                            ((data.total_diff || 0) > 0 ? '+' : '') + (data.total_diff || 0).toLocaleString(),
                            ((data.percent_change || 0) > 0 ? '+' : '') + (data.percent_change || 0) + '%',
                            (data.status || 'unknown').charAt(0).toUpperCase() + (data.status || 'unknown').slice(1)
                        ]);
                    }
                });

                if (perfData.length > 0) {
                    createComparisonTable('1. Performance Overview', perfData, perfHeaders);
                }
            }

            // 2. Detailed Analytics Comparison Table
            if (comparisonData.analytics_metrics && Object.keys(comparisonData.analytics_metrics).length > 0) {
                const detailHeaders = ['Metric', 'P1 Total', 'P1 Avg', 'P2 Total', 'P2 Avg', 'Change', '% Change'];
                const detailData = [];
                
                Object.entries(comparisonData.analytics_metrics).forEach(([metric, analysis]) => {
                    if (analysis && analysis.file1_stats && analysis.file2_stats && analysis.comparison) {
                        detailData.push([
                            metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                            (analysis.file1_stats.sum || 0).toLocaleString(),
                            (analysis.file1_stats.mean || 0).toFixed(1),
                            (analysis.file2_stats.sum || 0).toLocaleString(),
                            (analysis.file2_stats.mean || 0).toFixed(1),
                            ((analysis.comparison.total_diff || 0) > 0 ? '+' : '') + (analysis.comparison.total_diff || 0).toLocaleString(),
                            ((analysis.comparison.percent_change || 0) > 0 ? '+' : '') + (analysis.comparison.percent_change || 0) + '%'
                        ]);
                    }
                });

                if (detailData.length > 0) {
                    createComparisonTable('2. Detailed Analytics Comparison', detailData, detailHeaders);
                }
            }

            // 3. File Information Table
            if (comparisonData.basic_metrics && comparisonData.headers) {
                const fileHeaders = ['Information', 'Period 1', 'Period 2', 'Details'];
                const fileData = [
                    [
                        'Total Records',
                        (comparisonData.basic_metrics.file1_rows || 0).toLocaleString(),
                        (comparisonData.basic_metrics.file2_rows || 0).toLocaleString(),
                        (((comparisonData.basic_metrics.file2_rows || 0) - (comparisonData.basic_metrics.file1_rows || 0)) > 0 ? '+' : '') + 
                        ((comparisonData.basic_metrics.file2_rows || 0) - (comparisonData.basic_metrics.file1_rows || 0)).toLocaleString() + ' records'
                    ],
                    [
                        'Total Columns',
                        (comparisonData.basic_metrics.file1_columns || 0).toString(),
                        (comparisonData.basic_metrics.file2_columns || 0).toString(),
                        (comparisonData.basic_metrics.file1_columns || 0) === (comparisonData.basic_metrics.file2_columns || 0) ? 'Same structure' : 'Different structure'
                    ],
                    [
                        'Common Columns',
                        (comparisonData.headers.common_headers ? comparisonData.headers.common_headers.length : 0).toString(),
                        (comparisonData.headers.common_headers ? comparisonData.headers.common_headers.length : 0).toString(),
                        'Compatible for comparison'
                    ]
                ];

                createComparisonTable('3. File Information', fileData, fileHeaders);
            }

            // 4. Data Preview Tables
            if (comparisonData.data_sample) {
                // Period 1 Sample
                if (comparisonData.data_sample.file1_sample && comparisonData.data_sample.file1_sample.length > 0) {
                    const sample1Headers = ['#'].concat(Object.keys(comparisonData.data_sample.file1_sample[0] || {}));
                    const sample1Data = comparisonData.data_sample.file1_sample.slice(0, 5).map((row, index) => {
                        return [index + 1].concat(Object.values(row || {}));
                    });

                    if (sample1Data.length > 0) {
                        createComparisonTable('4a. Period 1 Sample Data (First 5 Records)', sample1Data, sample1Headers);
                    }
                }

                // Period 2 Sample
                if (comparisonData.data_sample.file2_sample && comparisonData.data_sample.file2_sample.length > 0) {
                    const sample2Headers = ['#'].concat(Object.keys(comparisonData.data_sample.file2_sample[0] || {}));
                    const sample2Data = comparisonData.data_sample.file2_sample.slice(0, 5).map((row, index) => {
                        return [index + 1].concat(Object.values(row || {}));
                    });

                    if (sample2Data.length > 0) {
                        createComparisonTable('4b. Period 2 Sample Data (First 5 Records)', sample2Data, sample2Headers);
                    }
                }
            }

            // Footer with metadata
            checkPageBreak(40);
            yPosition += 10;
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Report Information:', margin, yPosition);
            yPosition += 6;
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'normal');
            pdf.text(`• Export Type: Analytics Comparison Report`, margin + 5, yPosition);
            yPosition += 4;
            pdf.text(`• Data Source: CSV File Comparison`, margin + 5, yPosition);
            yPosition += 4;
            pdf.text(`• Generated: ${generatedDate}`, margin + 5, yPosition);

        } catch (error) {
            console.error('Detailed PDF generation error:', error);
            console.log('Comparison data structure:', comparisonData);
            alert('Error generating PDF: ' + error.message + '. Please check the console for details.');
            return;
        }

        // Save PDF
        pdf.save(`TrafAnalyz_Comparison_Report_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);

        // Log the PDF export to database
        fetch('log_export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `exportType=PDF&description=Exported analytics comparison report as PDF with tabular data`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('PDF export logged successfully');
            }
        }).catch(error => {
            console.error('Error logging PDF export:', error);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Set global variables for confirmation logic
        window.compareHasErrorMessages = document.querySelector('.user-alert-danger, .error-container, .validation-help') !== null;
        window.compareHasComparisonResults = <?php echo isset($comparison_results) && !empty($comparison_results) ? 'true' : 'false'; ?>;
        
        console.log('Compare page loaded - hasErrorMessages:', window.compareHasErrorMessages);
        console.log('Compare page loaded - hasComparisonResults:', window.compareHasComparisonResults);
        
        // Global confirmation function for file uploads
        window.confirmComparisonFileUpload = function() {
            const hasErrorMessages = window.compareHasErrorMessages;
            const hasComparisonResults = window.compareHasComparisonResults;
            
            console.log('=== COMPARISON UPLOAD CONFIRMATION ===');
            console.log('hasErrorMessages:', hasErrorMessages);
            console.log('hasComparisonResults:', hasComparisonResults);
            
            // Show confirmation if there are error messages OR comparison results
            if (!hasErrorMessages && !hasComparisonResults) {
                console.log('No error messages or comparison results - proceeding without confirmation');
                return true;
            }
            
            let confirmMessage;
            
            // Prioritize error message warning if present
            if (hasErrorMessages && !hasComparisonResults) {
                confirmMessage = "⚠️ Clear Error Messages?\n\n" +
                                "You have validation error messages displayed that contain helpful suggestions for fixing your CSV files:\n" +
                                "• 💡 Data fix suggestions for each file\n" +
                                "• 🔧 Quick fix guide for common issues\n" +
                                "• 📋 Detailed error explanations\n\n" +
                                "Uploading new files will clear these helpful messages.\n\n" +
                                "Do you want to continue with the upload?";
            } else if (hasErrorMessages && hasComparisonResults) {
                confirmMessage = "⚠️ Replace Comparison & Clear Error Messages?\n\n" +
                                "You have both comparison results AND error messages with helpful suggestions displayed.\n\n" +
                                "Uploading new files will:\n" +
                                "• Replace your current comparison completely\n" +
                                "• Clear all comparison charts and analytics\n" +
                                "• Remove saved comparison data\n" +
                                "• Clear the helpful error messages and fix suggestions\n\n" +
                                "This action cannot be undone. Do you want to continue?";
            } else if (hasComparisonResults) {
                confirmMessage = "⚠️ Replace Existing Comparison?\n\n" +
                                "You already have comparison results displayed. Uploading new files will:\n" +
                                "• Replace your current comparison completely\n" +
                                "• Clear all comparison charts and analytics\n" +
                                "• Remove performance overview and detailed metrics\n" +
                                "• Reset all comparison data and exports\n\n" +
                                "This action cannot be undone. Do you want to continue?";
            }
            
            console.log('Showing confirmation dialog:', confirmMessage);
            const result = confirm(confirmMessage);
            console.log('Confirmation result:', result);
            return result;
        };
        
        // Add confirmation to the file upload form
        const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                if (!window.confirmComparisonFileUpload()) {
                    e.preventDefault();
                    console.log('File upload cancelled by user');
                    return false;
                }
            });
        }
        
        // Browser refresh/navigation confirmation for error messages and comparison results
        window.addEventListener('beforeunload', function(e) {
            console.log('=== COMPARE BEFOREUNLOAD EVENT TRIGGERED ===');
            
            const hasErrorMessages = document.querySelector('.user-alert-danger, .error-container, .validation-help') !== null;
            const hasComparisonResults = document.querySelector('.compare-comparison-card, .compare-metric-summary') !== null;
            
            console.log('hasErrorMessages:', hasErrorMessages);
            console.log('hasComparisonResults:', hasComparisonResults);
            
            // Only show confirmation if there are error messages or comparison results
            if (hasErrorMessages || hasComparisonResults) {
                console.log('Conditions met for showing beforeunload confirmation in compare page');
                
                // Browser will show its own message regardless
                e.preventDefault();
                e.returnValue = ''; // Empty string is sufficient
                
                console.log('beforeunload event prevented in compare page');
                return ''; // For older browsers
            } else {
                console.log('No conditions met, allowing navigation from compare page');
            }
        });
        
        // Update global variables when new content is loaded
        function updateCompareGlobalState() {
            window.compareHasErrorMessages = document.querySelector('.user-alert-danger, .error-container, .validation-help') !== null;
            window.compareHasComparisonResults = document.querySelector('.compare-comparison-card, .compare-metric-summary') !== null;
            
            console.log('Compare state updated - hasErrorMessages:', window.compareHasErrorMessages);
            console.log('Compare state updated - hasComparisonResults:', window.compareHasComparisonResults);
        }
        
        // Call update function whenever the page content might change
        // This can be extended if you have AJAX content loading
        updateCompareGlobalState();
        
        // Also update state after form submissions (in case of page reload)
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                // Update state before form submission
                setTimeout(updateCompareGlobalState, 100);
            });
        });
    });

    // Add file selection confirmation for the compare form dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const compareForm = document.querySelector('form[method="POST"]:not([enctype])');
        if (compareForm) {
            compareForm.addEventListener('submit', function(e) {
                const hasErrorMessages = window.compareHasErrorMessages;
                const hasComparisonResults = window.compareHasComparisonResults;
                
                // Only show confirmation if user is replacing existing content
                if (hasErrorMessages || hasComparisonResults) {
                    let confirmMessage = "⚠️ Load New Comparison?\n\n";
                    
                    if (hasErrorMessages && hasComparisonResults) {
                        confirmMessage += "This will replace your current comparison results and clear any error messages displayed.\n\n";
                    } else if (hasErrorMessages) {
                        confirmMessage += "This will clear the error messages currently displayed.\n\n";
                    } else if (hasComparisonResults) {
                        confirmMessage += "This will replace your current comparison results.\n\n";
                    }
                    
                    confirmMessage += "Do you want to continue?";
                    
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Check for both files failed scenario and add navigation
        checkForBothFilesFailedAndAddNavigation();
    });

    function initializeErrorNavigation() {
        const errorList = document.querySelector('.user-alert-danger .error-list');
        if (!errorList) return;
        
        const fileHeaders = errorList.querySelectorAll('[id^="file-"], .error-item[style*="File"]');
        const hasBothFiles = document.querySelector('.user-alert-danger .error-summary')?.textContent?.includes('Both files failed') || 
                            document.querySelector('.user-alert-danger .error-summary')?.textContent?.includes('File 1:') && 
                            document.querySelector('.user-alert-danger .error-summary')?.textContent?.includes('File 2:');
        
        if (hasBothFiles) {
            addQuickJumpButtons();
            enhanceFileHeaders();
            addScrollProgress();
        }
    }

    function checkForBothFilesFailedAndAddNavigation() {
        // Simple detection: look for both "--- File 1 Errors ---" and "--- File 2 Errors ---" in the error list
        const errorList = document.querySelector('.user-alert-danger .error-list');
        if (!errorList) return;
        
        const errorText = errorList.textContent;
        const hasFile1Errors = errorText.includes('--- File 1 Errors ---');
        const hasFile2Errors = errorText.includes('--- File 2 Errors ---');
        
        if (hasFile1Errors && hasFile2Errors) {
            addQuickJumpButtons();
            enhanceFileHeaders();
        }
    }

    function addQuickJumpButtons() {
        // Remove any existing quick jump container first
        const existingContainer = document.querySelector('.quick-jump-container');
        if (existingContainer) {
            existingContainer.remove();
        }
        
        // Add the side navigation buttons
        const jumpHTML = `
            <div class="quick-jump-container">
                <button class="quick-jump-btn file1" onclick="scrollToFile(1)" title="Jump to File 1 errors">
                    <i class="fas fa-file"></i> F1
                </button>
                <button class="quick-jump-btn file2" onclick="scrollToFile(2)" title="Jump to File 2 errors">
                    <i class="fas fa-file"></i> F2
                </button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', jumpHTML);
    }

    function enhanceFileHeaders() {
        const errorList = document.querySelector('.user-alert-danger .error-list');
        if (!errorList) return;
        
        const errorItems = errorList.querySelectorAll('.error-item');
        let fileErrorCount = {1: 0, 2: 0};
        let firstErrorElements = {1: null, 2: null};
        let currentFileSection = null; // Track which file section we're in
        
        errorItems.forEach((item, index) => {
            const errorText = item.textContent;
            
            // Style File 1 header
            if (errorText.includes('--- File 1 Errors ---')) {
                currentFileSection = 1; // Set current section to File 1
                item.id = 'file-1-header';
                item.style.cssText = `
                    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
                    color: white !important;
                    padding: 12px 16px !important;
                    margin: 20px 0 15px 0 !important;
                    border-radius: 8px !important;
                    font-weight: bold !important;
                    font-size: 1.1em !important;
                    text-align: center !important;
                    border-left: 4px solid #007bff !important;
                    box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3) !important;
                `;
                
                item.innerHTML = `
                    <i class="fas fa-file-alt" style="font-size: 1.2em; color: white; margin-right: 8px;"></i>
                    <span style="color: white;">File 1 Errors</span>
                    <span id="file1-count" style="background: rgba(255, 255, 255, 0.9); color: #007bff; padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; margin-left: 8px;">0</span>
                `;
            }
            // Style File 2 header  
            else if (errorText.includes('--- File 2 Errors ---')) {
                currentFileSection = 2; // Set current section to File 2
                item.id = 'file-2-header';
                item.style.cssText = `
                    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
                    color: white !important;
                    padding: 12px 16px !important;
                    margin: 20px 0 15px 0 !important;
                    border-radius: 8px !important;
                    font-weight: bold !important;
                    font-size: 1.1em !important;
                    text-align: center !important;
                    border-left: 4px solid #28a745 !important;
                    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3) !important;
                `;
                
                item.innerHTML = `
                    <i class="fas fa-file-alt" style="font-size: 1.2em; color: white; margin-right: 8px;"></i>
                    <span style="color: white;">File 2 Errors</span>
                    <span id="file2-count" style="background: rgba(255, 255, 255, 0.9); color: #28a745; padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; margin-left: 8px;">0</span>
                `;
            }
            // Count actual errors for each file - FIXED LOGIC
            else if (item.classList.contains('error-item') && errorText.includes('Row ') && currentFileSection !== null) {
                // Count errors based on current file section
                if (currentFileSection === 1) {
                    fileErrorCount[1]++;
                    if (!firstErrorElements[1]) {
                        firstErrorElements[1] = item;
                        item.id = 'file-1-first-error';
                    }
                } else if (currentFileSection === 2) {
                    fileErrorCount[2]++;
                    if (!firstErrorElements[2]) {
                        firstErrorElements[2] = item;
                        item.id = 'file-2-first-error';
                    }
                }
            }
        });
        
        // Update error counts
        const file1CountBadge = document.getElementById('file1-count');
        const file2CountBadge = document.getElementById('file2-count');
        if (file1CountBadge) file1CountBadge.textContent = fileErrorCount[1];
        if (file2CountBadge) file2CountBadge.textContent = fileErrorCount[2];
        
        // CRITICAL FIX: Update the main error summary count to exclude headers
        const errorSummary = document.querySelector('.user-alert-danger .error-summary h5');
        if (errorSummary) {
            const totalActualErrors = fileErrorCount[1] + fileErrorCount[2];
            errorSummary.textContent = `Found ${totalActualErrors} validation issues:`;
        }
        
        console.log('Error counts updated - File 1:', fileErrorCount[1], 'File 2:', fileErrorCount[2]);
        console.log('Total actual errors (excluding headers):', fileErrorCount[1] + fileErrorCount[2]);
    }

    function scrollToFile(fileNumber) {
        // Try to scroll to the first actual error, not the header
        let target = document.getElementById(`file-${fileNumber}-first-error`);
        
        if (!target) {
            // Fallback to header if no errors found
            target = document.getElementById(`file-${fileNumber}-header`);
        }
        
        if (target) {
            target.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start'
            });
            
            // Update active navigation button
            document.querySelectorAll('.quick-jump-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            const clickedButton = document.querySelector(`.quick-jump-btn.file${fileNumber}`);
            if (clickedButton) {
                clickedButton.classList.add('active');
            }
        }
    }

    function scrollToTop() {
        const errorContainer = document.querySelector('.user-alert-danger');
        if (errorContainer) {
            errorContainer.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
        
        // Clear active navigation buttons
        document.querySelectorAll('.error-nav-button').forEach(btn => {
            btn.classList.remove('active');
        });
    }

    function countFileErrors(fileName) {
        const errorList = document.querySelector('.user-alert-danger .error-list');
        if (!errorList) return 0;
        
        const errorItems = errorList.querySelectorAll('.error-item');
        let count = 0;
        let countingFile = false;
        
        errorItems.forEach(item => {
            const text = item.textContent;
            if (text.includes(`--- ${fileName} Errors ---`)) {
                countingFile = true;
            } else if (text.includes('--- File') && text.includes('Errors ---') && !text.includes(fileName)) {
                countingFile = false;
            } else if (countingFile && !text.includes('--- File')) {
                count++;
            }
        });
        
        return count;
    }

    // Cleanup function for mobile
    function cleanupErrorNavigation() {
        const quickJump = document.querySelector('.quick-jump-container');
        if (quickJump && window.innerWidth <= 768) {
            quickJump.style.bottom = '80px'; // Adjust for mobile keyboard
        }
    }

    window.addEventListener('resize', cleanupErrorNavigation);

    // File selection handling for comparison uploads
    document.addEventListener('DOMContentLoaded', function() {
        // Handle file input for csv_file1
        const csvFile1 = document.getElementById('csv_file1');
        const fileInfo1 = document.getElementById('fileInfo1');
        
        if (csvFile1 && fileInfo1) {
            csvFile1.addEventListener('change', function() {
                handleFileSelection(this, fileInfo1, 'csv_file1');
            });
        }
        
        // Handle file input for csv_file2
        const csvFile2 = document.getElementById('csv_file2');
        const fileInfo2 = document.getElementById('fileInfo2');
        
        if (csvFile2 && fileInfo2) {
            csvFile2.addEventListener('change', function() {
                handleFileSelection(this, fileInfo2, 'csv_file2');
            });
        }
    });

    function handleFileSelection(fileInput, fileInfoDiv, inputId) {
        console.log(`=== FILE SELECTION HANDLER for ${inputId} ===`);
        console.log('Files selected:', fileInput.files.length);
        
        const container = fileInput.closest('.file-input-container');
        const fileNameSpan = fileInfoDiv.querySelector('.file-name');
        const fileSizeSpan = fileInfoDiv.querySelector('.file-size');
        
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            console.log('Selected file:', file.name, 'Size:', file.size);
            
            // Update file info display
            if (fileNameSpan && fileSizeSpan) {
                fileNameSpan.textContent = file.name;
                fileSizeSpan.textContent = formatFileSize(file.size);
            }
            
            // Show file info
            if (fileInfoDiv) {
                fileInfoDiv.style.display = 'block';
                console.log('File info displayed for', inputId);
            }
            
            // Add selected state to container
            if (container) {
                container.classList.add('has-file');
            }
            
            // Update button text to show selection
            const button = container.querySelector('.file-input-button');
            if (button) {
                const icon = button.querySelector('i');
                const iconClass = icon ? icon.className : 'fas fa-check';
                
                if (inputId === 'csv_file1') {
                    button.innerHTML = `<i class="${iconClass}"></i> First Period CSV Selected`;
                } else {
                    button.innerHTML = `<i class="${iconClass}"></i> Second Period CSV Selected`;
                }
            }
            
        } else {
            console.log('No files selected for', inputId);
            
            // Hide file info
            if (fileInfoDiv) {
                fileInfoDiv.style.display = 'none';
            }
            
            // Remove selected state from container
            if (container) {
                container.classList.remove('has-file');
            }
            
            // Reset button text
            const button = container.querySelector('.file-input-button');
            if (button) {
                if (inputId === 'csv_file1') {
                    button.innerHTML = `<i class="fas fa-upload"></i> Choose First Period CSV`;
                } else {
                    button.innerHTML = `<i class="fas fa-upload"></i> Choose Second Period CSV`;
                }
            }
        }
        
        console.log(`=== END FILE SELECTION HANDLER for ${inputId} ===`);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Clear file selections when page loads (for clean state)
    document.addEventListener('DOMContentLoaded', function() {
        const fileInputs = ['csv_file1', 'csv_file2'];
        
        fileInputs.forEach(function(inputId) {
            const input = document.getElementById(inputId);
            const fileInfo = document.getElementById(inputId === 'csv_file1' ? 'fileInfo1' : 'fileInfo2');
            
            if (input) {
                input.value = ''; // Clear any previous selections
                
                if (fileInfo) {
                    fileInfo.style.display = 'none'; // Hide file info initially
                }
                
                const container = input.closest('.file-input-container');
                if (container) {
                    container.classList.remove('has-file'); // Remove selected state
                }
            }
        });
    });

    // Enhanced file upload confirmation with better state tracking
    document.addEventListener('DOMContentLoaded', function() {
        // Track file selection state
        let filesSelected = {
            file1: false,
            file2: false
        };
        
        // Track if user has made any changes
        let hasUnsavedChanges = false;
        
        // Update file selection tracking
        function updateFileSelection(fileInputId, isSelected) {
            const fileNumber = fileInputId === 'csv_file1' ? 'file1' : 'file2';
            filesSelected[fileNumber] = isSelected;
            hasUnsavedChanges = filesSelected.file1 || filesSelected.file2;
            
            console.log('File selection updated:', filesSelected, 'hasUnsavedChanges:', hasUnsavedChanges);
        }
        
        // Monitor file input changes
        const csvFile1 = document.getElementById('csv_file1');
        const csvFile2 = document.getElementById('csv_file2');
        
        if (csvFile1) {
            csvFile1.addEventListener('change', function() {
                updateFileSelection('csv_file1', this.files.length > 0);
            });
        }
        
        if (csvFile2) {
            csvFile2.addEventListener('change', function() {
                updateFileSelection('csv_file2', this.files.length > 0);
            });
        }
        
        // Monitor dropdown selections for saved comparisons
        const savedComparisonSelect = document.querySelector('select[name="saved_comparison_id"]');
        const upload1Select = document.querySelector('select[name="upload1"]');
        const upload2Select = document.querySelector('select[name="upload2"]');
        
        [savedComparisonSelect, upload1Select, upload2Select].forEach(select => {
            if (select) {
                select.addEventListener('change', function() {
                    if (this.value) {
                        hasUnsavedChanges = true;
                        console.log('Dropdown selection made, hasUnsavedChanges:', hasUnsavedChanges);
                    }
                });
            }
        });
        
        // Browser navigation confirmation
        window.addEventListener('beforeunload', function(e) {
            // Show confirmation if files are selected or dropdowns have selections
            if (hasUnsavedChanges && !window.formSubmitted) {
                const message = 'You have selected files for comparison but haven\'t submitted the form yet. Are you sure you want to leave?';
                e.preventDefault();
                e.returnValue = message;
                return message;
            }
        });
        
        // Track form submission to avoid false warnings
        window.formSubmitted = false;
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                window.formSubmitted = true;
                hasUnsavedChanges = false;
                console.log('Form submitted, clearing unsaved changes flag');
            });
        });
        
        // Clear state when comparison results are loaded
        if (window.compareHasComparisonResults) {
            hasUnsavedChanges = false;
            filesSelected = { file1: false, file2: false };
            console.log('Comparison results detected, clearing unsaved changes');
        }
    });
    </script>    
</body>
</html>