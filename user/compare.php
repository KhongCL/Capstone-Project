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
        
        // CRITICAL FIX: Set up complete comparison session structure BEFORE processing files
        // Store original filenames BEFORE processing
        $_SESSION['compare_files'] = [
            1 => [
                'name' => $file1['name'], // ✅ Store original filename FIRST
                'path' => null,  // Will be set after upload
                'upload_id' => null,  // Will be set after upload
                'needs_mapping' => false,  // Will be updated based on results
                'mapped' => false,
                'result' => null  // Will be set after upload
            ],
            2 => [
                'name' => $file2['name'], // ✅ Store original filename FIRST
                'path' => null,  // Will be set after upload
                'upload_id' => null,  // Will be set after upload
                'needs_mapping' => false,  // Will be updated based on results
                'mapped' => false,
                'result' => null  // Will be set after upload
            ]
        ];
        error_log("Set complete compare_files session structure with names: " . $file1['name'] . " and " . $file2['name']);
        
        // Process both files through handleCsvUpload - they will be saved with unique hash names
        $upload_result1 = handleCsvUploadForComparison($conn, $file1);
        $upload_result2 = handleCsvUploadForComparison($conn, $file2);
        
        // Initialize variables for different scenarios
        $file1_valid = ($upload_result1['type'] === 'success' || $upload_result1['type'] === 'warning');
        $file2_valid = ($upload_result2['type'] === 'success' || $upload_result2['type'] === 'warning');
        $file1_needs_mapping = ($upload_result1['type'] === 'needs_mapping');
        $file2_needs_mapping = ($upload_result2['type'] === 'needs_mapping');
        
        // Update the session data with results - PRESERVE THE NAME
        $compareFiles = $_SESSION['compare_files'];  // Get the structure we just created
        
        // Handle File 1 - UPDATE existing structure but PRESERVE name
        if ($file1_valid || $file1_needs_mapping) {
            $compareFiles[1]['path'] = $upload_result1['file_path'] ?? null;
            $compareFiles[1]['upload_id'] = $upload_result1['upload_id'] ?? null;
            $compareFiles[1]['needs_mapping'] = $file1_needs_mapping;
            $compareFiles[1]['mapped'] = $file1_valid; // Already mapped if valid
            $compareFiles[1]['result'] = $upload_result1;
            // CRITICAL: Name is already preserved from initial setup above
            error_log("File 1 processed - name preserved: " . $compareFiles[1]['name']);
        }
        
        // Handle File 2 - UPDATE existing structure but PRESERVE name
        if ($file2_valid || $file2_needs_mapping) {
            $compareFiles[2]['path'] = $upload_result2['file_path'] ?? null;
            $compareFiles[2]['upload_id'] = $upload_result2['upload_id'] ?? null;
            $compareFiles[2]['needs_mapping'] = $file2_needs_mapping;
            $compareFiles[2]['mapped'] = $file2_valid; // Already mapped if valid
            $compareFiles[2]['result'] = $upload_result2;
            // CRITICAL: Name is already preserved from initial setup above
            error_log("File 2 processed - name preserved: " . $compareFiles[2]['name']);
        }
        
        // Update session with complete information
        $_SESSION['compare_files'] = $compareFiles;
        error_log("Updated compare_files session with names preserved: File1=" . ($compareFiles[1]['name'] ?? 'NOT SET') . ", File2=" . ($compareFiles[2]['name'] ?? 'NOT SET'));
        error_log("Updated compare_files session with complete information: " . json_encode(array_keys($compareFiles)));
        
        session_write_close();
        session_start();
        error_log("Session data written and restarted before redirect logic");
        
        // Handle all 8 scenarios (4 original + 4 with mapping)
        if ($file1_valid && $file2_valid) {
            // Scenario 1: Both files are valid - proceed with comparison
            $file1_path = $upload_result1['file_path'] ?? null;
            $file2_path = $upload_result2['file_path'] ?? null;
            
            if ($file1_path && $file2_path && file_exists($file1_path) && file_exists($file2_path)) {
                // Perform the comparison using the saved files
                $comparison_results = compareCSVFiles($file1_path, $file2_path);
                
                $warning_parts = [];
                if ($upload_result1['type'] === 'warning') {
                    $warning_parts[] = "File 1 had validation warnings";
                }
                if ($upload_result2['type'] === 'warning') {
                    $warning_parts[] = "File 2 had validation warnings";
                }
                
                if (!empty($warning_parts)) {
                    $success_message = "Comparison completed with warnings: " . implode(", ", $warning_parts) . ". Files uploaded to database and saved to uploads directory.";
                } else {
                    $success_message = "Comparison completed successfully! Files uploaded to database and saved to uploads directory.";
                }
            } else {
                $error_message = "Files were processed but could not be found for comparison.";
            }
            
        } elseif ($file1_needs_mapping && $file2_valid) {
            // Scenario 2: File 1 needs mapping, File 2 is valid
            error_log("Scenario 2: File 1 needs mapping, File 2 is valid - redirecting to map file 1");
            // CRITICAL FIX: Force another session write before redirect
            session_write_close();
            header('Location: map_columns_compare.php?file=1');
            exit;
            
        } elseif ($file1_valid && $file2_needs_mapping) {
            // Scenario 3: File 1 is valid, File 2 needs mapping
            error_log("Scenario 3: File 1 is valid, File 2 needs mapping - redirecting to map file 2");
            // CRITICAL FIX: Force another session write before redirect
            session_write_close();
            header('Location: map_columns_compare.php?file=2');
            exit;
            
        } elseif ($file1_needs_mapping && $file2_needs_mapping) {
            // Scenario 4: Both files need mapping - start with file 1
            error_log("Scenario 4: Both files need mapping - redirecting to map file 1");
            // CRITICAL FIX: Force another session write before redirect
            session_write_close();
            header('Location: map_columns_compare.php?file=1');
            exit;
            
        } elseif (($file1_valid || $file1_needs_mapping) && !$file2_valid && !$file2_needs_mapping) {
            // Scenario 5: File 1 valid/mappable, File 2 invalid
            $error_message = "File 1 " . ($file1_needs_mapping ? "uploaded successfully (requires mapping)" : "uploaded successfully") . 
                           ", but File 2 failed: " . $upload_result2['message'];
            
        } elseif (!$file1_valid && !$file1_needs_mapping && ($file2_valid || $file2_needs_mapping)) {
            // Scenario 6: File 1 invalid, File 2 valid/mappable  
            $error_message = "File 2 " . ($file2_needs_mapping ? "uploaded successfully (requires mapping)" : "uploaded successfully") . 
                           ", but File 1 failed: " . $upload_result1['message'];
            
        } else {
            // Scenario 7 & 8: Both files invalid or other error combinations
            $errors = [];
            if ($upload_result1['type'] === 'error') {
                $errors[] = "File 1: " . $upload_result1['message'];
            }
            if ($upload_result2['type'] === 'error') {
                $errors[] = "File 2: " . $upload_result2['message'];
            }
            $error_message = implode(" | ", $errors);
        }
        
    } catch (Exception $e) {
        $error_message = "Error processing files: " . $e->getMessage();
    }
}

// Check if we're returning from mapping and ready to compare
if (isset($_SESSION['compare_ready']) && $_SESSION['compare_ready'] && isset($_SESSION['compare_files'])) {
    $compareFiles = $_SESSION['compare_files'];
    
    // CRITICAL FIX: Ensure file names are available for display
    foreach ([1, 2] as $fileIndex) {
        if (isset($compareFiles[$fileIndex]['upload_id']) && 
            (!isset($compareFiles[$fileIndex]['name']) || $compareFiles[$fileIndex]['name'] === 'Unknown file')) {
            
            // Try to get filename from database
            $uploadId = $compareFiles[$fileIndex]['upload_id'];
            $stmt = $conn->prepare("SELECT FileName FROM csv_upload WHERE UploadID = ?");
            $stmt->bind_param("i", $uploadId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $dbFileName = $row['FileName'];
                // Remove hash prefix if present
                $cleanName = preg_replace('/^[a-f0-9]{8}_/', '', $dbFileName);
                $_SESSION['compare_files'][$fileIndex]['name'] = $cleanName;
                error_log("RECOVERED: File $fileIndex name from database: $cleanName");
            }
        }
    }
    
    // Both files should now be mapped and have upload IDs
    if (isset($compareFiles[1]['upload_id']) && isset($compareFiles[2]['upload_id'])) {
        try {
            // Get file paths for comparison
            $file1_path = $compareFiles[1]['path'];
            $file2_path = $compareFiles[2]['path'];
            
            if ($file1_path && $file2_path && file_exists($file1_path) && file_exists($file2_path)) {
                // Perform the comparison using the saved files
                $comparison_results = compareCSVFiles($file1_path, $file2_path);
                $success_message = "Comparison completed successfully after column mapping!";
            } else {
                $error_message = "Files were processed but could not be found for comparison.";
            }
        } catch (Exception $e) {
            $error_message = "Error performing comparison: " . $e->getMessage();
        }
    }
    
    // Clear the comparison session data
    unset($_SESSION['compare_ready']);
    // Don't unset compare_files yet - we need the names for display
    // unset($_SESSION['compare_files']);
}

// Check for comparison error from mapping
if (isset($_SESSION['compare_error'])) {
    $error_message = $_SESSION['compare_error'];
    unset($_SESSION['compare_error']);
}

// Add the new function for comparison upload handling
function handleCsvUploadForComparison($conn, $file) {
    // Add a flag to indicate this is a comparison context
    $_POST['comparison_context'] = true;
    
    // Use the same logic as handleCsvUpload but with comparison context
    $result = handleCsvUpload($conn, $file);
    
    // FIXED: Make sure we return the upload_id if available
    if (isset($_SESSION['latest_upload_id'])) {
        $result['upload_id'] = $_SESSION['latest_upload_id'];
        error_log("Added upload_id to result: " . $_SESSION['latest_upload_id']);
    }
    
    return $result;
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
                // Perform the comparison using the saved files
                $comparison_results = compareCSVFiles($file1_path, $file2_path);
                $success_message = "Loaded comparison: " . htmlspecialchars($files[1]['FileName']) . " vs " . htmlspecialchars($files[2]['FileName']);
            } catch (Exception $e) {
                $error_message = "Error loading comparison: " . $e->getMessage();
            }
        } else {
            $error_message = "One or both files for this saved comparison no longer exist in the uploads directory.";
            // Debug info to help troubleshoot
            $error_message .= "<br>Looking for: " . htmlspecialchars($file1_path) . " and " . htmlspecialchars($file2_path);
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



function compareCSVFiles($file1_path, $file2_path) {
    $data1 = parseCSV($file1_path);
    $data2 = parseCSV($file2_path);
    
    if (empty($data1) || empty($data2)) {
        throw new Exception("One or both CSV files are empty or invalid.");
    }
    
    $headers1 = array_keys($data1[0]);
    $headers2 = array_keys($data2[0]);
    
    // Define analytics metrics to look for
    $analytics_metrics = [
        'traffic_source', // Add this
        'sessions', 'engaged_sessions', 'engagement_rate', 'average_engagement_time_per_session',
        'events_per_session', 'event_count', 'key_events', 'session_key_event_rate', 'total_revenue'
    ];
    
    $comparison = [
        'basic_metrics' => [
            'file1_rows' => count($data1),
            'file2_rows' => count($data2),
            'file1_columns' => count($headers1),
            'file2_columns' => count($headers2),
            'row_difference' => count($data1) - count($data2),
            'column_difference' => count($headers1) - count($headers2)
        ],
        'headers' => [
            'file1_headers' => $headers1,
            'file2_headers' => $headers2,
            'common_headers' => array_intersect($headers1, $headers2),
            'unique_to_file1' => array_diff($headers1, $headers2),
            'unique_to_file2' => array_diff($headers2, $headers1)
        ],
        'analytics_metrics' => [],
        'summary_comparison' => [],
        'data_sample' => [
            'file1_sample' => array_slice($data1, 0, 5),
            'file2_sample' => array_slice($data2, 0, 5)
        ]
    ];
    
    // Analyze analytics metrics
    $common_headers = array_merge($comparison['headers']['common_headers'], $headers1, $headers2); // Use all headers for better matching
    $semantic_common = [];
    foreach ($analytics_metrics as $metric) {
        // Find matching column (case-insensitive, flexible naming)
        $found_column = findMetricColumn($common_headers, $metric);
        
        if ($found_column) {
            // Check if column exists in both files (or similar columns)
            $col1 = findMetricColumn($headers1, $metric);
            $col2 = findMetricColumn($headers2, $metric);
            
            if ($col1 && $col2) {
                    $semantic_common[] = $metric;
                    $values1 = array_column($data1, $col1);
                    $values2 = array_column($data2, $col2);
                    
                    // Handle conversions for bounce rate vs engagement rate
                    if (strpos($col1, '_CONVERT_FROM_') !== false) {
                        $actualCol1 = str_replace(['_CONVERT_FROM_ENGAGEMENT', '_CONVERT_FROM_BOUNCE'], '', $col1);
                        $values1 = array_column($data1, $actualCol1);
                        
                        if (strpos($col1, '_CONVERT_FROM_ENGAGEMENT') !== false) {
                            // Convert engagement rate to bounce rate: bounce_rate = 1 - engagement_rate
                            $values1 = array_map(function($val) { return 1 - floatval($val); }, $values1);
                        } elseif (strpos($col1, '_CONVERT_FROM_BOUNCE') !== false) {
                            // Convert bounce rate to engagement rate: engagement_rate = 1 - bounce_rate
                            $values1 = array_map(function($val) { return 1 - floatval($val); }, $values1);
                        }
                        $col1 = $actualCol1; // Update column name for display
                    }
                    
                    if (strpos($col2, '_CONVERT_FROM_') !== false) {
                        $actualCol2 = str_replace(['_CONVERT_FROM_ENGAGEMENT', '_CONVERT_FROM_BOUNCE'], '', $col2);
                        $values2 = array_column($data2, $actualCol2);
                        
                        if (strpos($col2, '_CONVERT_FROM_ENGAGEMENT') !== false) {
                            $values2 = array_map(function($val) { return 1 - floatval($val); }, $values2);
                        } elseif (strpos($col2, '_CONVERT_FROM_BOUNCE') !== false) {
                            $values2 = array_map(function($val) { return 1 - floatval($val); }, $values2);
                        }
                        $col2 = $actualCol2;
                    }
                    
                    // Clean and convert to numeric
                    $numeric1 = cleanNumericValues($values1);
                    $numeric2 = cleanNumericValues($values2);
                
                if (count($numeric1) > 0 && count($numeric2) > 0) {
                    $stats1 = calculateStats($numeric1);
                    $stats2 = calculateStats($numeric2);
                    
                    // Fixed percentage calculation
                    $percent_change = 0;
                    if ($stats1['sum'] != 0) {
                        $percent_change = round((($stats2['sum'] - $stats1['sum']) / abs($stats1['sum'])) * 100, 2);
                    } elseif ($stats2['sum'] > 0) {
                        $percent_change = 100;
                    } elseif ($stats2['sum'] < 0) {
                        $percent_change = -100;
                    }
                    
                    $comparison['analytics_metrics'][$metric] = [
                        'column_name' => "$col1 vs $col2",
                        'file1_column' => $col1,
                        'file2_column' => $col2,
                        'file1_stats' => $stats1,
                        'file2_stats' => $stats2,
                        'comparison' => [
                            'total_diff' => $stats2['sum'] - $stats1['sum'],
                            'avg_diff' => $stats2['mean'] - $stats1['mean'],
                            'percent_change' => $percent_change,
                            'improvement' => determineImprovement($metric, $stats2['mean'], $stats1['mean'])
                        ]
                    ];
                }
            }
        }
    }
    
    // Calculate summary totals for key metrics
    $comparison['summary_comparison'] = calculateSummaryComparison($data1, $data2, $comparison['analytics_metrics']);
    $comparison['headers']['semantic_common'] = $semantic_common;
    $comparison['headers']['semantic_common_count'] = count($semantic_common);
    
    return $comparison;
}

function findMetricColumn($headers, $metric) {
    $metric_variations = [
        'sessions' => ['Sessions', 'sessions', 'session', 'total_sessions', 'User Sessions', 'visits', 'Visits'],
        'engaged_sessions' => ['Engaged sessions', 'engaged_sessions', 'engaged sessions', 'engagedsessions', 'Engaged Sessions', 'Active Sessions'],
        'engagement_rate' => ['Engagement rate', 'engagement_rate', 'engagement rate', 'engagementrate'],
        'average_engagement_time_per_session' => ['Average engagement time per session', 'average_engagement_time_per_session', 'avg_engagement_time', 'engagement_time', 'Average engagement time', 'Avg Session Time', 'Session Length'],
        'events_per_session' => ['Events per session', 'events_per_session', 'events per session', 'eventspersession', 'Events Per Session', 'Events/Session'],
        'event_count' => ['Event count', 'event_count', 'events', 'total_events', 'Events', 'Total Events', 'Event Total'],
        'key_events' => ['Key events', 'key_events', 'key events', 'keyevents', 'Conversions', 'conversions', 'Goals'],
        'session_key_event_rate' => ['Session key event rate', 'session_key_event_rate', 'key_event_rate', 'conversion_rate', 'Session conversion rate', 'Conversion Rate', 'Goal Rate'],
        'total_revenue' => ['Total revenue', 'total_revenue', 'revenue', 'total revenue', 'Revenue', 'Purchase revenue', 'Sales Revenue'],
        'total_page_views' => ['total_page_views', 'page_views', 'pageviews', 'Views', 'Page views', 'Pageviews'],
        'unique_visitors' => ['unique_visitors', 'unique visitors', 'users', 'Users', 'Total users', 'Active users'],
        'average_session_duration' => ['average_session_duration', 'avg_session_duration', 'session_duration', 'Average session duration', 'Session duration', 'Avg Session Time'],
        'bounce_rate' => ['bounce_rate', 'bounce rate', 'bouncerate', 'Bounce rate', 'Bounce Rate', 'Exit Rate'],
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
    
    // FIXED: Enhanced conversion logic for bounce rate vs engagement rate
    if ($metric === 'bounce_rate') {
        // Look for engagement rate variations directly (no recursive call)
        $engagement_variations = $metric_variations['engagement_rate'] ?? [];
        foreach ($engagement_variations as $variation) {
            foreach ($headers as $header) {
                if (strcasecmp(trim($header), trim($variation)) === 0) {
                    return $header . '_CONVERT_FROM_ENGAGEMENT';
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
                    return $header . '_CONVERT_FROM_BOUNCE';
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
    $summary = [];
    
    foreach ($analytics_metrics as $metric => $data) {
        $summary[$metric] = [
            'file1_total' => $data['file1_stats']['sum'],
            'file2_total' => $data['file2_stats']['sum'],
            'difference' => $data['comparison']['total_diff'],
            'percent_change' => $data['comparison']['percent_change'],
            'status' => $data['comparison']['improvement']
        ];
    }
    
    return $summary;
}

function parseCSV($file_path) {
    $data = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $headers = null;
        $row_number = 0;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            
            // Skip metadata rows that start with # or are empty
            if (empty($row[0]) || strpos($row[0], '#') === 0) {
                continue;
            }
            
            // First non-metadata row should be headers
            if ($headers === null) {
                $headers = $row;
                continue;
            }
            
            // Process data rows
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

function calculateStats($values) {
    if (empty($values)) return null;
    
    sort($values);
    $count = count($values);
    $sum = array_sum($values);
    $mean = $sum / $count;
    
    $median = $count % 2 === 0 
        ? ($values[$count/2 - 1] + $values[$count/2]) / 2 
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            padding: 12px 16px !important;
            margin: 20px 0 15px 0 !important;
            border-radius: 8px !important;
            font-weight: bold !important;
            font-size: 1.1em !important;
            text-align: center !important;
            border-left: 4px solid #dc3545 !important;
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3) !important;
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
            color: #dc3545 !important;
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
            background: #e2e3e5 !important;
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
                
                <?php if ($error_message): ?>
                    <?php 
                    // Check if this is a validation error with suggestions OR a data validation error
                    if (strpos($error_message, 'Data validation errors') !== false || 
                        strpos($error_message, 'No valid data to save') !== false ||
                        strpos($error_message, 'CSV parsing error') !== false ||
                        strpos($error_message, 'trademark symbols') !== false ||
                        strpos($error_message, 'scientific notation') !== false ||
                        strpos($error_message, 'non-numeric characters') !== false ||
                        strpos($error_message, 'Empty value') !== false ||
                        strpos($error_message, 'whitespace') !== false): ?>
                        <?php
                        // Enhanced error message parsing to extract suggestions
                        $errorMessage = $error_message;
                        $errorMessage = str_replace("Error processing files: ", "", $errorMessage);

                        error_log("=== COMPARE.PHP ERROR PARSING DEBUG ===");
                        error_log("Original error message: " . $error_message);
                        error_log("Cleaned error message: " . $errorMessage);

                        // Determine the scenario and display appropriate prefix
                        $errorPrefix = "";
                        $allErrorList = [];
                        $file1ErrorCount = 0;
                        $file2ErrorCount = 0;

                        // Check for specific file failure patterns
                        if (strpos($errorMessage, 'File 1 uploaded successfully, but File 2 failed: ') !== false) {
                            $errorPrefix = "✅ File 1 uploaded successfully, but ❌ File 2 failed";
                            $cleanErrorForFile2 = str_replace('File 1 uploaded successfully, but File 2 failed: ', '', $errorMessage);
                            $cleanErrorForFile2 = str_replace("Data validation errors found: ", "", $cleanErrorForFile2);
                            $cleanErrorForFile2 = preg_replace('/\. Please correct these issues and upload again\./', '', $cleanErrorForFile2);
                            
                            error_log("File 2 failed scenario - extracting File 2 errors");
                            error_log("Clean error for File 2: " . $cleanErrorForFile2);
                            
                            // FIXED ERROR PARSING - Replace the existing parsing with this robust approach
                            if (strpos($cleanErrorForFile2, 'No valid data to save') !== false) {
                                $allErrorList[] = "No valid data found in CSV file - All rows failed validation";
                                $allErrorList[] = "Common causes: Invalid file format, corrupt data, or unsupported CSV structure";
                                $file2ErrorCount = 2;
                            } else {
                                // Use the robust error splitting approach
                                if (strpos($cleanErrorForFile2, '; Row') !== false) {
                                    // Split by '; Row' and handle properly
                                    $parts = explode('; Row ', $cleanErrorForFile2);
                                    
                                    // First part contains the initial "Row X: ..." 
                                    if (!empty(trim($parts[0]))) {
                                        $allErrorList[] = trim($parts[0]);
                                        $file2ErrorCount++;
                                    }
                                    
                                    // Subsequent parts need "Row " prepended
                                    for ($i = 1; $i < count($parts); $i++) {
                                        $part = trim($parts[$i]);
                                        if (!empty($part)) {
                                            $allErrorList[] = 'Row ' . $part;
                                            $file2ErrorCount++;
                                        }
                                    }
                                } else {
                                    // Single error case
                                    if (!empty(trim($cleanErrorForFile2))) {
                                        $allErrorList[] = trim($cleanErrorForFile2);
                                        $file2ErrorCount++;
                                    }
                                }
                                
                                // Remove any empty entries and reindex
                                $allErrorList = array_filter($allErrorList, function($item) {
                                    return !empty(trim($item));
                                });
                                $allErrorList = array_values($allErrorList);
                                $file2ErrorCount = count($allErrorList); // Correct count based on actual items
                            }
                            
                        } elseif (strpos($errorMessage, 'File 2 uploaded successfully, but File 1 failed: ') !== false) {
                            $errorPrefix = "❌ File 1 failed, but ✅ File 2 uploaded successfully";
                            $cleanErrorForFile1 = str_replace('File 2 uploaded successfully, but File 1 failed: ', '', $errorMessage);
                            $cleanErrorForFile1 = str_replace("Data validation errors found: ", "", $cleanErrorForFile1);
                            $cleanErrorForFile1 = preg_replace('/\. Please correct these issues and upload again\./', '', $cleanErrorForFile1);
                            
                            error_log("File 1 failed scenario - extracting File 1 errors");
                            error_log("Clean error for File 1: " . $cleanErrorForFile1);
                            
                            // FIXED ERROR PARSING - Use the same robust approach
                            if (strpos($cleanErrorForFile1, 'No valid data to save') !== false) {
                                $allErrorList[] = "No valid data found in CSV file - All rows failed validation";
                                $allErrorList[] = "Common causes: Invalid file format, corrupt data, or unsupported CSV structure";
                                $file1ErrorCount = 2;
                            } else {
                                // Use the robust error splitting approach
                                if (strpos($cleanErrorForFile1, '; Row') !== false) {
                                    // Split by '; Row' and handle properly
                                    $parts = explode('; Row ', $cleanErrorForFile1);
                                    
                                    // First part contains the initial "Row X: ..." 
                                    if (!empty(trim($parts[0]))) {
                                        $allErrorList[] = trim($parts[0]);
                                        $file1ErrorCount++;
                                    }
                                    
                                    // Subsequent parts need "Row " prepended
                                    for ($i = 1; $i < count($parts); $i++) {
                                        $part = trim($parts[$i]);
                                        if (!empty($part)) {
                                            $allErrorList[] = 'Row ' . $part;
                                            $file1ErrorCount++;
                                        }
                                    }
                                } else {
                                    // Single error case
                                    if (!empty(trim($cleanErrorForFile1))) {
                                        $allErrorList[] = trim($cleanErrorForFile1);
                                        $file1ErrorCount++;
                                    }
                                }
                                
                                // Remove any empty entries and reindex
                                $allErrorList = array_filter($allErrorList, function($item) {
                                    return !empty(trim($item));
                                });
                                $allErrorList = array_values($allErrorList);
                                $file1ErrorCount = count($allErrorList); // Correct count based on actual items
                            }
                            
                        } elseif (strpos($errorMessage, ' | ') !== false) {
                            // Both files invalid - parse separately
                            $errorPrefix = "❌ Both files failed validation";
                            $fileParts = explode(' | ', $errorMessage);
                            
                            error_log("Both files failed - parsing " . count($fileParts) . " file parts");
                            
                            foreach ($fileParts as $index => $part) {
                                $fileNumber = $index + 1;
                                $cleanError = $part;
                                
                                // Clean the error message
                                if (strpos($part, 'File 1: ') !== false) {
                                    $cleanError = str_replace('File 1: ', '', $part);
                                    $fileNumber = 1;
                                } elseif (strpos($part, 'File 2: ') !== false) {
                                    $cleanError = str_replace('File 2: ', '', $part);
                                    $fileNumber = 2;
                                }
                                
                                $cleanError = str_replace("Data validation errors found: ", "", $cleanError);
                                $cleanError = preg_replace('/\. Please correct these issues and upload again\./', '', $cleanError);
                                
                                error_log("Processing File $fileNumber errors: " . $cleanError);
                                
                                // Add file separator
                                $allErrorList[] = "--- File $fileNumber Errors ---";
                                
                                if (strpos($cleanError, 'No valid data to save') !== false) {
                                    $allErrorList[] = "No valid data found in CSV file - All rows failed validation";
                                    $allErrorList[] = "Common causes: Invalid file format, corrupt data, or unsupported CSV structure";
                                    if ($fileNumber === 1) {
                                        $file1ErrorCount = 2;
                                    } else {
                                        $file2ErrorCount = 2;
                                    }
                                } else {
                                    // FIXED ERROR PARSING - Use the robust approach
                                    if (strpos($cleanError, '; Row') !== false) {
                                        // Split by '; Row' and handle properly
                                        $parts = explode('; Row ', $cleanError);
                                        
                                        // First part contains the initial "Row X: ..." 
                                        if (!empty(trim($parts[0]))) {
                                            $allErrorList[] = trim($parts[0]);
                                            if ($fileNumber === 1) {
                                                $file1ErrorCount++;
                                            } else {
                                                $file2ErrorCount++;
                                            }
                                        }
                                        
                                        // Subsequent parts need "Row " prepended
                                        for ($i = 1; $i < count($parts); $i++) {
                                            $part = trim($parts[$i]);
                                            if (!empty($part)) {
                                                $allErrorList[] = 'Row ' . $part;
                                                if ($fileNumber === 1) {
                                                    $file1ErrorCount++;
                                                } else {
                                                    $file2ErrorCount++;
                                                }
                                            }
                                        }
                                    } else {
                                        // Single error case
                                        if (!empty(trim($cleanError))) {
                                            $allErrorList[] = trim($cleanError);
                                            if ($fileNumber === 1) {
                                                $file1ErrorCount++;
                                            } else {
                                                $file2ErrorCount++;
                                            }
                                        }
                                    }
                                }
                            }
                            
                        } else {
                            // Single file error or generic error - should not happen in compare scenario
                            $cleanError = $errorMessage;
                            $cleanError = str_replace("Data validation errors found: ", "", $cleanError);
                            $cleanError = preg_replace('/\. Please correct these issues and upload again\./', '', $cleanError);
                            
                            error_log("Single file/generic error scenario: " . $cleanError);
                            
                            if (strpos($cleanError, 'No valid data to save') !== false) {
                                $allErrorList[] = "No valid data found in CSV file - All rows failed validation";
                                $allErrorList[] = "Common causes: Invalid file format, corrupt data, or unsupported CSV structure";
                                $file1ErrorCount = 2; // Assume it's file 1 if not specified
                            } else {
                                // FIXED ERROR PARSING - Use the robust approach
                                if (strpos($cleanError, '; Row') !== false) {
                                    // Split by '; Row' and handle properly
                                    $parts = explode('; Row ', $cleanError);
                                    
                                    // First part contains the initial "Row X: ..." 
                                    if (!empty(trim($parts[0]))) {
                                        $allErrorList[] = trim($parts[0]);
                                        $file1ErrorCount++;
                                    }
                                    
                                    // Subsequent parts need "Row " prepended
                                    for ($i = 1; $i < count($parts); $i++) {
                                        $part = trim($parts[$i]);
                                        if (!empty($part)) {
                                            $allErrorList[] = 'Row ' . $part;
                                            $file1ErrorCount++;
                                        }
                                    }
                                } else {
                                    // Single error case
                                    if (!empty(trim($cleanError))) {
                                        $allErrorList[] = trim($cleanError);
                                        $file1ErrorCount++;
                                    }
                                }
                                
                                // Remove any empty entries and reindex
                                $allErrorList = array_filter($allErrorList, function($item) {
                                    return !empty(trim($item));
                                });
                                $allErrorList = array_values($allErrorList);
                                $file1ErrorCount = count(array_filter($allErrorList, function($item) {
                                    return strpos($item, '--- File') === false; // Don't count file separators
                                }));
                            }
                        }

                        // Calculate totals and log debug info
                        $totalErrors = count($allErrorList);
                        // Don't count file separators in total
                        $separatorCount = 0;
                        foreach ($allErrorList as $error) {
                            if (strpos($error, '--- File') !== false) {
                                $separatorCount++;
                            }
                        }
                        $actualErrorCount = $totalErrors - $separatorCount;

                        error_log("=== ERROR COUNT DEBUG ===");
                        error_log("Total items in allErrorList: " . $totalErrors);
                        error_log("File separator count: " . $separatorCount);
                        error_log("Actual error count (excluding separators): " . $actualErrorCount);
                        error_log("File 1 error count: " . $file1ErrorCount);
                        error_log("File 2 error count: " . $file2ErrorCount);
                        error_log("Expected total: " . ($file1ErrorCount + $file2ErrorCount));
                        error_log("=== END ERROR COUNT DEBUG ===");
                        ?>

                        <div class="user-alert user-alert-danger">
                            <div class="error-container">
                                <?php if (!empty($errorPrefix)): ?>
                                    <p class="error-summary"><?php echo $errorPrefix; ?></p>
                                    <?php if ($file1ErrorCount > 0 && $file2ErrorCount > 0): ?>
                                        <p class="error-summary">File 1: <?php echo $file1ErrorCount; ?> errors | File 2: <?php echo $file2ErrorCount; ?> errors | Total: <?php echo $actualErrorCount; ?> validation errors</p>
                                    <?php elseif ($file1ErrorCount > 0): ?>
                                        <p class="error-summary">Found <?php echo $file1ErrorCount; ?> validation errors in File 1:</p>
                                    <?php elseif ($file2ErrorCount > 0): ?>
                                        <p class="error-summary">Found <?php echo $file2ErrorCount; ?> validation errors in File 2:</p>
                                    <?php else: ?>
                                        <p class="error-summary">Found <?php echo $actualErrorCount; ?> validation errors in your CSV file(s):</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="error-summary"><i class="fas fa-exclamation-triangle"></i> Found <?php echo $actualErrorCount; ?> validation errors in your CSV file(s):</p>
                                <?php endif; ?>
                                
                                <ul class="error-list">
                                    <?php foreach($allErrorList as $error): ?>
                                        <?php $error = trim($error); ?>
                                        <?php if(!empty($error)): ?>
                                            <?php
                                            // Check if this is a file separator
                                            if (strpos($error, '--- File') !== false) {
                                                ?>
                                                <li class="error-item" style="background: #e9ecef !important; border-left: 3px solid #6c757d !important; font-weight: bold; text-align: center;">
                                                    <div class="error-message" style="color: #495057 !important;"><?php echo htmlspecialchars($error); ?></div>
                                                </li>
                                                <?php
                                            } else {
                                                // Parse error and suggestions - same as index.php
                                                $parts = explode(' Suggestions: ', $error);
                                                $mainError = $parts[0];
                                                $suggestions = isset($parts[1]) ? $parts[1] : '';
                                                ?>
                                                <li class="error-item">
                                                    <div class="error-message"><?php echo htmlspecialchars($mainError); ?></div>
                                                    <?php if (!empty($suggestions)): ?>
                                                        <div class="error-suggestions">
                                                            <strong>💡 Suggestions:</strong> 
                                                            <span class="suggestions-text"><?php echo htmlspecialchars($suggestions); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                                                                
                            <div class="validation-help">
                                <h4>Quick Fix Guide:</h4>
                                <div class="fix-guide">
                                    <div class="fix-item">
                                        <strong>📁 File Format Issues:</strong>
                                        <ul>
                                            <li>Ensure CSV has proper headers</li>
                                            <li>Check for GA4 metadata lines starting with #</li>
                                            <li>Verify file isn't corrupted or empty</li>
                                            <li>Make sure data rows aren't all empty</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item">
                                        <strong>🔢 Integer Issues:</strong>
                                        <ul>
                                            <li>Remove letters: "15a" → "15"</li>
                                            <li>Evaluate expressions: "42+3" → "45"</li>
                                            <li>Convert Unicode: "５０" → "50"</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item">
                                        <strong>📊 Float/Decimal Issues:</strong>
                                        <ul>
                                            <li>Fix multiple decimals: "8..5" → "8.5"</li>
                                            <li>Convert scientific: "1.2e3" → "1200"</li>
                                            <li>Remove special chars: "~5.3" → "5.3"</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item">
                                        <strong>⏰ Time Format Issues:</strong>
                                        <ul>
                                            <li>Use proper format: "10:65:30" → "11:05:30"</li>
                                            <li>Convert units: "12m30s" → "12:30" or "750"</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item">
                                        <strong>💰 Currency Issues:</strong>
                                        <ul>
                                            <li>Remove symbols: "$1,200" → "1200"</li>
                                            <li>Remove commas: "500.abc" → "500"</li>
                                        </ul>
                                    </div>
                                    <div class="fix-item">
                                        <strong>🚫 Common CSV Issues:</strong>
                                        <ul>
                                            <li>Remove trademark symbols: ™, ®, ©</li>
                                            <li>Fix unquoted commas in data fields</li>
                                            <li>Remove leading/trailing whitespace</li>
                                            <li>Check for mixed data types in columns</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                                                                
                            <p class="error-footer">Please correct these issues and upload again.</p>
                        </div>
                                                                
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="user-alert user-alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="compare-user-file-input-group">
                        <div>
                            <label for="csv_file1">First Period CSV File</label>
                            <input type="file" id="csv_file1" name="csv_file1" accept=".csv" required>
                            <small>Upload your first analytics period data</small>
                        </div>
                        <div>
                            <label for="csv_file2">Second Period CSV File</label>
                            <input type="file" id="csv_file2" name="csv_file2" accept=".csv" required>
                            <small>Upload your second analytics period data</small>
                        </div>
                    </div>
                    <button type="submit" class="compare-user-btn-submit">
                        <i class="fas fa-chart-bar"></i> Compare Analytics Data
                    </button>
                </form>
            </div>

            <div class="compare-comparison-container">
                <h3><i class="fas fa-balance-scale"></i> Compare CSV Files</h3>
                            
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
                        <button type="submit" name="load_comparison" class="btn">Load Comparison</button>
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
                    <p><strong>File Names:</strong><br>
                    <small>Period 1: <?php echo isset($_SESSION['compare_files'][1]['name']) ? htmlspecialchars($_SESSION['compare_files'][1]['name']) : 'Unknown'; ?> | 
                    Period 2: <?php echo isset($_SESSION['compare_files'][2]['name']) ? htmlspecialchars($_SESSION['compare_files'][2]['name']) : 'Unknown'; ?></small></p>
                    
                    <p><strong>Available CSV Headers:</strong><br>
                    <small>Period 1: <?php echo implode(' | ', $comparison_results['headers']['file1_headers'] ?? []); ?></small><br>
                    <small>Period 2: <?php echo implode(' | ', $comparison_results['headers']['file2_headers'] ?? []); ?></small></p>

                    <p><strong>Analytics Metrics Detection Results:</strong><br>
                    <?php 
                    $all_metrics = ['sessions', 'engaged_sessions', 'engagement_rate', 'average_engagement_time_per_session',
                                'events_per_session', 'event_count', 'key_events', 'session_key_event_rate',
                                'total_revenue', 'total_page_views', 'unique_visitors', 'average_session_duration',
                                'bounce_rate', 'traffic_source'];

                    foreach ($all_metrics as $metric) {
                        $found = isset($comparison_results['analytics_metrics'][$metric]);
                        $color = $found ? 'green' : 'red';
                        $status = $found ? '✓ Found' : '✗ Not Found';
                        echo '<small style="color: ' . $color . ';">' . $metric . ': ' . $status;
                        if ($found) {
                            echo ' → ' . htmlspecialchars($comparison_results['analytics_metrics'][$metric]['column_name']);
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
                    <h3><i class="fas fa-tachometer-alt"></i> Performance Overview</h3>
                    <div class="compare-user-stats-grid">
                        <?php 
                        // Dynamically show all available metrics from summary_comparison
                        foreach ($comparison_results['summary_comparison'] as $metric => $data): 
                        ?>
                            <div class="compare-user-metric-box">
                                <h4><?php echo number_format($data['file1_total']); ?></h4>
                                <small><?php echo ucwords(str_replace('_', ' ', $metric)); ?></small>
                                <div style="margin-top: 5px;">
                                    <span class="compare-<?php echo $data['status'] === 'improved' ? 'improved' : ($data['status'] === 'declined' ? 'declined' : 'unchanged'); ?>">
                                        <?php echo $data['percent_change']; ?>%
                                        <i class="fas <?php echo $data['percent_change'] > 0 ? 'fa-arrow-up' : ($data['percent_change'] < 0 ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                                    </span>
                                </div>
                            </div>
                        <?php 
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detailed Analytics Comparison -->
            <?php if (!empty($comparison_results['analytics_metrics'])): ?>
                <div class="compare-comparison-card">
                    <div class="compare-metric-header success">
                        <i class="fas fa-chart-bar"></i> Detailed Analytics Comparison
                    </div>
                    <div class="compare-user-stats-grid">
                        <?php foreach ($comparison_results['analytics_metrics'] as $metric => $analysis): ?>
                            <div class="compare-comparison-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h5><?php echo ucwords(str_replace('_', ' ', $metric)); ?></h5>
                                    <span class="compare-metric-percentage compare-<?php echo $analysis['comparison']['improvement']; ?>">
                                        <?php echo $analysis['comparison']['percent_change'] > 0 ? '+' : ''; ?>
                                        <?php echo $analysis['comparison']['percent_change']; ?>%
                                    </span>
                                </div>

                                <div class="compare-detailed-vs-section">
                                    <div class="compare-detailed-period-data">
                                        <h6>Period 1</h6>
                                        <div class="period-value"><?php echo number_format($analysis['file1_stats']['sum']); ?></div>
                                        <div class="period-avg">Avg: <?php echo number_format($analysis['file1_stats']['mean'], 1); ?></div>
                                    </div>

                                    <div class="compare-vs-divider">VS</div>

                                    <div class="compare-detailed-period-data">
                                        <h6>Period 2</h6>
                                        <div class="period-value"><?php echo number_format($analysis['file2_stats']['sum']); ?></div>
                                        <div class="period-avg">Avg: <?php echo number_format($analysis['file2_stats']['mean'], 1); ?></div>
                                    </div>
                                </div>

                                <div class="compare-change-summary compare-<?php echo $analysis['comparison']['improvement']; ?>">
                                    <strong>
                                        Change: <?php echo $analysis['comparison']['total_diff'] > 0 ? '+' : ''; ?>
                                        <?php echo number_format($analysis['comparison']['total_diff']); ?>
                                        (<?php echo $analysis['comparison']['percent_change'] > 0 ? '+' : ''; ?><?php echo $analysis['comparison']['percent_change']; ?>%)
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

                <!-- Basic File Information -->
                <div class="compare-comparison-card">
                    <div class="compare-metric-header primary">
                        <i class="fas fa-info-circle"></i> File Information
                    </div>
                    <div class="compare-user-stats-grid">
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_rows']; ?></h4>
                            <small>Period 1 Records</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file2_rows']; ?></h4>
                            <small>Period 2 Records</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_columns']; ?></h4>
                            <small>Total Columns</small>
                        </div>
                        <div class="compare-user-metric-box">
                            <h4><?php echo count($comparison_results['headers']['common_headers']); ?></h4>
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
        initializeErrorNavigation();
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

    function addQuickJumpButtons() {
        // Only add the side navigation buttons, remove the back to top button
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
        let currentFile = null;
        let fileErrorCount = {1: 0, 2: 0};
        let firstErrorElements = {1: null, 2: null};
        
        errorItems.forEach((item, index) => {
            const errorText = item.textContent;
            
            // Detect file separators and apply red styling immediately
            if (errorText.includes('--- File 1 Errors ---')) {
                currentFile = 1;
                item.id = 'file-1-header';
                item.className = 'file-section-header';
                
                // CRITICAL FIX: Apply red styling immediately via inline styles to override any existing gray
                item.style.cssText = `
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
                    color: white !important;
                    padding: 12px 16px !important;
                    margin: 20px 0 15px 0 !important;
                    border-radius: 8px !important;
                    font-weight: bold !important;
                    font-size: 1.1em !important;
                    text-align: center !important;
                    border-left: 4px solid #dc3545 !important;
                    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3) !important;
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 10 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 10px !important;
                `;
                
                item.innerHTML = `
                    <i class="fas fa-file-alt file-icon" style="font-size: 1.2em !important; color: white !important;"></i>
                    <span style="color: white !important;">File 1 Errors</span>
                    <span class="error-count-badge" id="file1-count" style="background: rgba(255, 255, 255, 0.9) !important; color: #dc3545 !important; padding: 4px 8px !important; border-radius: 12px !important; font-size: 0.85em !important; font-weight: bold !important; margin-left: 8px !important; border: 1px solid rgba(255, 255, 255, 0.3) !important;">0</span>
                `;
                
            } else if (errorText.includes('--- File 2 Errors ---')) {
                currentFile = 2;
                item.id = 'file-2-header';
                item.className = 'file-section-header';
                
                // CRITICAL FIX: Apply red styling immediately via inline styles to override any existing gray
                item.style.cssText = `
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
                    color: white !important;
                    padding: 12px 16px !important;
                    margin: 20px 0 15px 0 !important;
                    border-radius: 8px !important;
                    font-weight: bold !important;
                    font-size: 1.1em !important;
                    text-align: center !important;
                    border-left: 4px solid #dc3545 !important;
                    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3) !important;
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 10 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 10px !important;
                `;
                
                item.innerHTML = `
                    <i class="fas fa-file-alt file-icon" style="font-size: 1.2em !important; color: white !important;"></i>
                    <span style="color: white !important;">File 2 Errors</span>
                    <span class="error-count-badge" id="file2-count" style="background: rgba(255, 255, 255, 0.9) !important; color: #dc3545 !important; padding: 4px 8px !important; border-radius: 12px !important; font-size: 0.85em !important; font-weight: bold !important; margin-left: 8px !important; border: 1px solid rgba(255, 255, 255, 0.3) !important;">0</span>
                `;
                
            } else if (currentFile && item.classList.contains('error-item')) {
                // This is an actual error item
                fileErrorCount[currentFile]++;
                
                // Set ID for the first actual error of each file
                if (!firstErrorElements[currentFile]) {
                    firstErrorElements[currentFile] = item;
                    item.id = `file-${currentFile}-first-error`;
                }
                
                // Add file badge to regular error items
                const badge = document.createElement('div');
                badge.className = `error-item-badge file${currentFile}`;
                badge.textContent = `File ${currentFile}`;
                item.appendChild(badge);
                item.setAttribute('data-file', currentFile);
            }
        });
        
        // Update error counts
        const file1CountBadge = document.getElementById('file1-count');
        const file2CountBadge = document.getElementById('file2-count');
        if (file1CountBadge) file1CountBadge.textContent = fileErrorCount[1];
        if (file2CountBadge) file2CountBadge.textContent = fileErrorCount[2];
    }

    function scrollToFile(fileNumber) {
        console.log(`Attempting to scroll to file ${fileNumber}`);
        
        // Try to scroll to the first actual error, not the header
        let target = document.getElementById(`file-${fileNumber}-first-error`);
        
        if (!target) {
            // Fallback to header if no errors found
            target = document.getElementById(`file-${fileNumber}-header`);
        }
        
        if (target) {
            console.log(`Found target for file ${fileNumber}:`, target);
            
            target.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start',
                inline: 'nearest' 
            });
            
            // Highlight the section temporarily
            const originalBackground = target.style.background;
            target.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
            target.style.color = 'white';
            
            setTimeout(() => {
                target.style.background = originalBackground;
                target.style.color = '';
            }, 1500);
            
            // Update active navigation button
            document.querySelectorAll('.quick-jump-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to the clicked button
            const clickedButton = document.querySelector(`.quick-jump-btn.file${fileNumber}`);
            if (clickedButton) {
                clickedButton.classList.add('active');
            }
        } else {
            console.error(`Could not find target element for file ${fileNumber}`);
            
            // Enhanced fallback: try to find the section by text content
            const errorItems = document.querySelectorAll('.error-item, .file-section-header');
            for (let item of errorItems) {
                if (item.textContent.includes(`--- File ${fileNumber} Errors ---`) || 
                    item.textContent.includes(`File ${fileNumber} Errors`)) {
                    console.log(`Found fallback target for file ${fileNumber}:`, item);
                    item.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start',
                        inline: 'nearest' 
                    });
                    break;
                }
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
    </script>    
</body>
</html>