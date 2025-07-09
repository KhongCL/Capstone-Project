<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
include '../functions.php';

session_start();

// Clear sample data session when user reaches manual mapping
if (isset($_SESSION['using_sample_data'])) {
    unset($_SESSION['using_sample_data']);
    unset($_SESSION['sample_upload_id']);
    error_log("Cleared sample data session in map_columns_compare.php");
}

// Enhanced debugging for form submission
error_log("=== MAP_COLUMNS_COMPARE.PHP DEBUG ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));
error_log("HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'NOT SET'));
error_log("Session compare_files: " . (isset($_SESSION['compare_files']) ? 'SET' : 'NOT SET'));
if (isset($_SESSION['compare_files'])) {
    error_log("Compare files structure: " . json_encode($_SESSION['compare_files']));
}
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// CRITICAL FIX: Wait a moment and try to restore session if it's missing
if (!isset($_SESSION['compare_files'])) {
    error_log("CRITICAL: compare_files not found in session, attempting to restore...");
    
    // Try to restore from uploaded files if we have them
    if (isset($_SESSION['uploaded_csv']) && isset($_SESSION['mapping_result'])) {
        error_log("Found uploaded_csv and mapping_result in session, attempting manual restoration");
        
        // Get the file number from URL
        $currentFileIndex = $_GET['file'] ?? 1;
        
        // Try to reconstruct the comparison session structure
        $filePath = $_SESSION['uploaded_csv'];
        $fileName = basename($filePath);
        
        if (file_exists($filePath)) {
            $_SESSION['compare_files'] = [
                $currentFileIndex => [
                    'name' => $fileName,
                    'path' => $filePath,
                    'upload_id' => $_SESSION['latest_upload_id'] ?? null,
                    'needs_mapping' => true,
                    'mapped' => false,
                    'result' => $_SESSION['mapping_result']
                ]
            ];
            
            error_log("Successfully restored compare_files session for file $currentFileIndex");
        } else {
            error_log("ERROR: Cannot restore - uploaded file does not exist: $filePath");
        }
    }
}

// Check if we have comparison files in session
if (!isset($_SESSION['compare_files'])) {
    error_log("ERROR: No compare_files in session after restoration attempt - redirecting to compare.php");
    $_SESSION['compare_error'] = "Comparison session lost. Please upload your files again.";
    header('Location: compare.php');
    exit;
}

$compareFiles = $_SESSION['compare_files'];
$currentFileIndex = $_GET['file'] ?? 1; // Which file we're mapping (1 or 2)
error_log("Current file index: $currentFileIndex");

$currentFile = $compareFiles[$currentFileIndex] ?? null;

if (!$currentFile) {
    error_log("ERROR: Current file not found - index $currentFileIndex not in compareFiles");
    error_log("Available indices: " . implode(', ', array_keys($compareFiles)));
    $_SESSION['compare_error'] = "File information not found for mapping.";
    header('Location: compare.php');
    exit;
}

// CRITICAL FIX: Check if file exists and if not, try to find it
if (!file_exists($currentFile['path'])) {
    error_log("ERROR: File path does not exist: " . ($currentFile['path'] ?? 'NULL'));
    
    // Try to find the file in uploads directory by name
    $fileName = $currentFile['name'] ?? null;
    if ($fileName) {
        $uploadsDir = __DIR__ . '/../uploads/';
        $pattern = $uploadsDir . '*_' . $fileName; // Look for hash_filename pattern
        $foundFiles = glob($pattern);
        
        if (!empty($foundFiles)) {
            $foundFile = $foundFiles[0]; // Take the first match
            error_log("RECOVERY: Found file at new location: $foundFile");
            
            // Update the session with the correct path
            $_SESSION['compare_files'][$currentFileIndex]['path'] = $foundFile;
            $currentFile['path'] = $foundFile;
            
            error_log("Updated session with correct file path");
        } else {
            error_log("RECOVERY FAILED: Could not find file with pattern: $pattern");
            $_SESSION['compare_error'] = "File not found for mapping. Please upload your files again.";
            header('Location: compare.php');
            exit;
        }
    } else {
        error_log("ERROR: No filename available for recovery");
        $_SESSION['compare_error'] = "File information incomplete. Please upload your files again.";
        header('Location: compare.php');
        exit;
    }
}

error_log("Successfully found file for mapping: " . $currentFile['path']);

$processor = new CsvProcessor();

// Process the mapping for current file
if (!isset($_SESSION["mapping_result_$currentFileIndex"])) {
    $_SESSION["mapping_result_$currentFileIndex"] = $processor->processFile($currentFile['path']);
}

$mappingResult = $_SESSION["mapping_result_$currentFileIndex"];

// Get system fields (same as map_columns.php)
$systemFields = [];
$query = "SELECT DISTINCT SystemFieldName, 
          GROUP_CONCAT(DISTINCT CSVColumnName SEPARATOR ', ') as CSVColumnNames 
          FROM COLUMN_MAPPING 
          WHERE FormatID = 1 
          GROUP BY SystemFieldName 
          ORDER BY SystemFieldName";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $systemFields[] = [
            'value' => $row['SystemFieldName'],
            'label' => ucwords(str_replace('_', ' ', $row['SystemFieldName'])),
            'default_columns' => explode(', ', $row['CSVColumnNames'])
        ];
    }
}

// Also add any system fields that might be missing from database but exist in JSON
$allSystemFields = [];
if (isset($mappingResult['format']) && $mappingResult['format']) {
    $mappings = json_decode(file_get_contents(__DIR__ . '/../config/csv_mappings.json'), true);
    if (isset($mappings[$mappingResult['format']]['column_mappings'])) {
        foreach ($mappings[$mappingResult['format']]['column_mappings'] as $csvCol => $systemField) {
            $allSystemFields[$systemField] = ucwords(str_replace('_', ' ', $systemField));
        }
    }
}

// Merge any missing system fields
foreach ($allSystemFields as $field => $label) {
    $exists = false;
    foreach ($systemFields as $existing) {
        if ($existing['value'] === $field) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $systemFields[] = [
            'value' => $field,
            'label' => $label,
            'default_columns' => []
        ];
    }
}

// Handle form submission for manual mapping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_mapping'])) {
    error_log("=== PROCESSING FORM SUBMISSION FOR FILE $currentFileIndex ===");
    
    $columnMapping = [];
    foreach ($_POST['mapping'] as $sourceCol => $targetCol) {
        if (!empty($targetCol)) {
            $columnMapping[$sourceCol] = $targetCol;
            error_log("Mapped: $sourceCol -> $targetCol");
        }
    }
    
    error_log("Total mappings: " . count($columnMapping));
    
    // Check if at least one column is mapped
    if (empty($columnMapping)) {
        $error_message = "Please map at least one column before proceeding.";
        error_log("ERROR: No columns mapped");
    } else {
        error_log("Starting data transformation for comparison file...");
        
        // For manual mapping cases, we need to determine the format
        $format = null;
        if (isset($mappingResult['format']) && $mappingResult['format']) {
            $format = $mappingResult['format'];
            error_log("Using detected format: $format");
        } else {
            // Manual mapping - try to detect format based on column mappings
            $ga4RequiredFields = ['traffic_source', 'visits', 'engaged_sessions', 'bounce_rate'];
            $mappedFields = array_values($columnMapping);
            $ga4MatchCount = count(array_intersect($ga4RequiredFields, $mappedFields));
            
            if ($ga4MatchCount >= 3) {
                $format = 'ga4_traffic_acquisition';
                error_log("Detected GA4 format based on manual mappings (matches: $ga4MatchCount)");
            } else {
                error_log("Could not detect format automatically, using manual mapping");
            }
        }

        error_log("Using format for transformation: " . ($format ?? 'null'));
        
        try {
            // CRITICAL FIX: Clear any previous validation errors before transformation
            if (isset($_SESSION['validation_errors'])) {
                unset($_SESSION['validation_errors']);
                error_log("Cleared previous validation errors before new transformation");
            }
            
            $transformedData = $processor->transformData($currentFile['path'], $columnMapping, $format);
            error_log("Transformation completed. Rows: " . count($transformedData));
            
            if (empty($transformedData)) {
                error_log("ERROR: No data returned from transformation");
                
                // IMPROVED: Better error handling for validation errors
                if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
                    $validationErrors = $_SESSION['validation_errors'];
                    error_log("Found validation errors: " . implode('; ', array_slice($validationErrors, 0, 5)));
                    
                    // FIXED: Count unique error types instead of showing repetitive errors
                    $uniqueErrors = array_unique($validationErrors);
                    $errorCount = count($validationErrors);
                    $uniqueCount = count($uniqueErrors);
                    
                    if ($uniqueCount < 3) {
                        // Show all unique errors if we have fewer than 3
                        $error_message = "Validation errors found: " . implode('; ', $uniqueErrors);
                    } else {
                        // Show first 2 unique errors + count
                        $firstTwoErrors = array_slice($uniqueErrors, 0, 2);
                        $error_message = "Validation errors found: " . implode('; ', $firstTwoErrors) . 
                                        ($uniqueCount > 2 ? " and " . ($uniqueCount - 2) . " more error type(s)" : "");
                    }
                    
                    if ($errorCount > $uniqueCount) {
                        $error_message .= " (Total: $errorCount issues found)";
                    }
                    
                    $error_message .= ". Please review your column mappings and try again.";
                    
                    // Don't clear session data here - let user try again
                } else {
                    $error_message = 'No valid data found after transformation. Please check your CSV file and column mappings.';
                }
            } else {
                error_log("Sample transformed data: " . json_encode($transformedData[0] ?? []));
                
                // Save transformed data to database with comparison flag
                if (saveTransformedDataForComparison($conn, $transformedData, $currentFileIndex)) {
                    error_log("Data successfully saved to database for comparison");
                    
                    // Mark this file as mapped and update session
                    $_SESSION['compare_files'][$currentFileIndex]['mapped'] = true;
                    $_SESSION['compare_files'][$currentFileIndex]['upload_id'] = $_SESSION['latest_upload_id'];
                    $_SESSION['compare_files'][$currentFileIndex]['needs_mapping'] = false;
                    
                    // Clear any validation errors since we succeeded
                    if (isset($_SESSION['validation_errors'])) {
                        unset($_SESSION['validation_errors']);
                    }
                    
                    // CRITICAL FIX: Ensure the name is preserved
                    if (!isset($_SESSION['compare_files'][$currentFileIndex]['name']) || 
                        $_SESSION['compare_files'][$currentFileIndex]['name'] === 'Unknown file') {
                        
                        if (isset($currentFile['path']) && $currentFile['path']) {
                            $extractedName = basename($currentFile['path']);
                            // Remove the hash prefix (e.g., "dd09fba0_" from "dd09fba0_test70_90.csv")
                            $cleanName = preg_replace('/^[a-f0-9]{8}_/', '', $extractedName);
                            $_SESSION['compare_files'][$currentFileIndex]['name'] = $cleanName;
                            error_log("FIXED: Restored filename for file $currentFileIndex: $cleanName");
                        }
                    } else {
                        error_log("Filename already preserved for file $currentFileIndex: " . $_SESSION['compare_files'][$currentFileIndex]['name']);
                    }
                    
                    // Clear mapping session data for this file
                    unset($_SESSION["mapping_result_$currentFileIndex"]);
                    
                    // CRITICAL FIX: Force session write before checking next file
                    session_write_close();
                    session_start();
                    
                    // Get fresh session data and check properly
                    $updatedCompareFiles = $_SESSION['compare_files'];
                    error_log("Updated compare files after mapping file $currentFileIndex: " . json_encode($updatedCompareFiles));
                    
                    // Check if we need to map the other file or proceed with comparison
                    $nextFileIndex = ($currentFileIndex == 1) ? 2 : 1;
                    $nextFile = $updatedCompareFiles[$nextFileIndex] ?? null;
                    
                    error_log("Checking next file (index $nextFileIndex): " . json_encode($nextFile));
                    
                    // FIXED: Better logic to determine if next file needs mapping
                    $nextFileNeedsMapping = false;
                    if ($nextFile) {
                        // Check multiple conditions to determine if mapping is needed
                        $nextFileNeedsMapping = (
                            isset($nextFile['needs_mapping']) && $nextFile['needs_mapping'] === true &&
                            (!isset($nextFile['mapped']) || $nextFile['mapped'] === false)
                        );
                        
                        error_log("Next file needs mapping check: needs_mapping=" . 
                                ($nextFile['needs_mapping'] ?? 'not set') . 
                                ", mapped=" . ($nextFile['mapped'] ?? 'not set') . 
                                ", result=" . ($nextFileNeedsMapping ? 'YES' : 'NO'));
                    }
                    
                    if ($nextFileNeedsMapping) {
                        // CRITICAL FIX: Verify the next file exists before redirecting
                        $nextFilePath = $nextFile['path'] ?? null;
                        
                        if ($nextFilePath && file_exists($nextFilePath)) {
                            error_log("Next file exists, redirecting to map file $nextFileIndex");
                            header("Location: map_columns_compare.php?file=$nextFileIndex");
                            exit;
                        } else {
                            error_log("ERROR: Next file path doesn't exist: " . ($nextFilePath ?? 'NULL'));
                            
                            // Try to recover the file path
                            $nextFileName = $nextFile['name'] ?? null;
                            if ($nextFileName) {
                                $uploadsDir = __DIR__ . '/../uploads/';
                                $pattern = $uploadsDir . '*_' . $nextFileName;
                                $foundFiles = glob($pattern);
                                
                                if (!empty($foundFiles)) {
                                    $foundFile = $foundFiles[0];
                                    error_log("RECOVERY: Found next file at: $foundFile");
                                    
                                    // Update session with correct path
                                    $_SESSION['compare_files'][$nextFileIndex]['path'] = $foundFile;
                                    
                                    // Force session write and redirect
                                    session_write_close();
                                    header("Location: map_columns_compare.php?file=$nextFileIndex");
                                    exit;
                                } else {
                                    error_log("RECOVERY FAILED: Could not find next file with pattern: $pattern");
                                    $_SESSION['compare_error'] = "File not found for mapping. Please upload your files again.";
                                    header('Location: compare.php');
                                    exit;
                                }
                            } else {
                                error_log("ERROR: No filename available for recovery of next file");
                                $_SESSION['compare_error'] = "File information incomplete. Please upload your files again.";
                                header('Location: compare.php');
                                exit;
                            }
                        }
                    } else {
                        // All files are ready, proceed with comparison
                        error_log("All files ready for comparison");
                        
                        // Verify both files have upload IDs
                        $file1Ready = isset($updatedCompareFiles[1]['upload_id']) && $updatedCompareFiles[1]['upload_id'] !== null;
                        $file2Ready = isset($updatedCompareFiles[2]['upload_id']) && $updatedCompareFiles[2]['upload_id'] !== null;
                        
                        error_log("File readiness check - File 1: " . ($file1Ready ? 'READY' : 'NOT READY') . 
                                ", File 2: " . ($file2Ready ? 'READY' : 'NOT READY'));
                        
                        if ($file1Ready && $file2Ready) {
                            $_SESSION['compare_ready'] = true;
                            error_log("Both files ready, setting compare_ready flag and redirecting");
                            header('Location: compare.php');
                            exit;
                        } else {
                            error_log("ERROR: Not all files have upload IDs - File 1: " . 
                                    ($updatedCompareFiles[1]['upload_id'] ?? 'NULL') . 
                                    ", File 2: " . ($updatedCompareFiles[2]['upload_id'] ?? 'NULL'));
                            $_SESSION['compare_error'] = "File processing incomplete. Please try uploading again.";
                            header('Location: compare.php');
                            exit;
                        }
                    }
                } else {
                    error_log("ERROR: Failed to save data to database");
                    if (isset($_SESSION['upload_message'])) {
                        $error_message = $_SESSION['upload_message']['message'];
                        unset($_SESSION['upload_message']);
                    } else {
                        $error_message = 'Error saving data to database.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("ERROR: Exception during transformation: " . $e->getMessage());
            $error_message = 'Error processing data: ' . $e->getMessage();
        }
    }
    
    error_log("=== END FORM PROCESSING ===");
}

// Function to save transformed data specifically for comparison
function saveTransformedDataForComparison($conn, $transformedData, $fileIndex) {
    // Use the same saveTransformedData function but with comparison context
    $result = saveTransformedData($conn, $transformedData);
    
    if ($result['type'] === 'success') {
        // Store the upload ID for comparison use
        if (isset($_SESSION['latest_upload_id'])) {
            $_SESSION["compare_file_{$fileIndex}_upload_id"] = $_SESSION['latest_upload_id'];
            error_log("Stored upload ID for comparison file $fileIndex: " . $_SESSION['latest_upload_id']);
            return true;
        }
    } else {
        error_log("saveTransformedData failed: " . $result['message']);
        // Store the error message for display
        $_SESSION['upload_message'] = $result;
    }
    
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map CSV Columns for Comparison - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
    <style>
        /* Add to the existing styles in map_columns_compare.php */
        .user-alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .user-alert-warning h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .user-alert-warning h5 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .user-alert-warning ul li {
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .user-alert-warning ul li strong {
            color: #856404;
        }

        .user-alert-warning em {
            color: #6c5220;
            font-size: 0.9em;
        }

    </style>
</head>
<body>
    <div class="container user-map-columns-container">
        <?php include 'user_header.php'; ?>
        
        <main>
            <section class="user-mapping-section">
                <h2>Map CSV Columns - File <?php echo $currentFileIndex; ?> for Comparison</h2>
                
                <div class="user-alert user-alert-info">
                    <h4>📊 Mapping File <?php echo $currentFileIndex; ?> for Comparison</h4>
                    
                    <?php 
                    // ENHANCED DEBUG: Show actual file name and how we got it
                    $displayName = $currentFile['name'] ?? 'Unknown file';
                    $actualPath = $currentFile['path'] ?? 'No path';
                    $extractedFromPath = $actualPath ? basename($actualPath) : 'No path';
                    
                    error_log("=== FILE NAME DEBUG for File $currentFileIndex ===");
                    error_log("currentFile['name']: " . ($currentFile['name'] ?? 'NOT SET'));
                    error_log("currentFile['path']: " . ($currentFile['path'] ?? 'NOT SET'));
                    error_log("extracted from path: " . $extractedFromPath);
                    error_log("final display name: " . $displayName);
                    ?>
                    
                    <p><strong>File Name:</strong> <?php echo htmlspecialchars($displayName); ?></p>
                    
                    <?php if ($displayName === 'Unknown file' && $actualPath): ?>
                        <p><small><strong>Debug Info:</strong> Path: <?php echo htmlspecialchars($extractedFromPath); ?></small></p>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['compare_files'])): ?>
                        <?php $compareFiles = $_SESSION['compare_files']; ?>
                        <p><strong>Comparison Overview:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <?php if (isset($compareFiles[1])): ?>
                                <li><strong>File 1:</strong> <?php echo htmlspecialchars($compareFiles[1]['name'] ?? 'Unknown'); ?> 
                                    <?php if ($compareFiles[1]['mapped'] ?? false): ?>
                                        <span style="color: #28a745; font-weight: bold;">✓ Mapped</span>
                                    <?php elseif ($compareFiles[1]['needs_mapping'] ?? false): ?>
                                        <span style="color: #ffc107; font-weight: bold;">
                                            <?php echo $currentFileIndex == 1 ? '⚙️ Currently Mapping' : '⚠ Needs Mapping'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">⏳ Pending</span>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                            <?php if (isset($compareFiles[2])): ?>
                                <li><strong>File 2:</strong> <?php echo htmlspecialchars($compareFiles[2]['name'] ?? 'Unknown'); ?>
                                    <?php if ($compareFiles[2]['mapped'] ?? false): ?>
                                        <span style="color: #28a745; font-weight: bold;">✓ Mapped</span>
                                    <?php elseif ($compareFiles[2]['needs_mapping'] ?? false): ?>
                                        <span style="color: #ffc107; font-weight: bold;">
                                            <?php echo $currentFileIndex == 2 ? '⚙️ Currently Mapping' : '⚠ Needs Mapping'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">⏳ Pending</span>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                    <p>This file requires manual column mapping before it can be used in the comparison. Please map the columns below and we'll return to the comparison page.</p>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="user-alert user-alert-warning">
                        <h4><i class="fas fa-exclamation-triangle"></i> Column Mapping Issue</h4>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        
                        <?php if (strpos($error_message, 'validation errors') !== false): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                                <h5>💡 Quick Fix Tips:</h5>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    <li><strong>Check required mappings:</strong> Make sure you've mapped the essential columns (Traffic Source and at least one metric like Visits or Sessions)</li>
                                    <li><strong>Verify data format:</strong> Ensure numeric columns contain valid numbers (remove commas, currency symbols, or text)</li>
                                    <li><strong>Review sample data:</strong> Check the sample data preview below to ensure your mappings match the actual data</li>
                                </ul>
                                <p><em>The system tries to validate your data to ensure accurate comparison results. Simply adjust your mappings above and try again.</em></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!isset($error_message)): ?>
                    <div class="user-alert user-alert-success">
                        <h4>🎉 Upload Successful!</h4>
                        <p><strong>Your CSV file has been successfully uploaded and validated.</strong></p>
                        <p>Since the format wasn't automatically recognized, please review and confirm the column mappings below to continue with the comparison.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($mappingResult['status'] === 'needs_mapping'): ?>
                    <div class="user-alert user-alert-info">
                        This CSV format was not automatically recognized. Please review and confirm the column mappings below.
                    </div>
                <?php elseif ($mappingResult['status'] === 'success'): ?>
                    <div class="user-alert user-alert-success">
                        CSV format detected: <strong><?php echo ucfirst(str_replace('_', ' ', $mappingResult['format'])); ?></strong>
                        <p>Please confirm the column mappings below:</p>
                    </div>
                <?php endif; ?>

                <div class="upload-progress" id="mappingProgress" style="display: none;">
                    <h3>Processing Your Data</h3>
                    
                    <div class="progress-container">
                        <div class="progress-stage completed" id="mappingStage1">
                            <div class="stage-icon">✅</div>
                            <div class="stage-text">File Upload</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <div class="progress-text">100%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage completed" id="mappingStage2">
                            <div class="stage-icon">✅</div>
                            <div class="stage-text">Column Mapping</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <div class="progress-text">100%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage active" id="mappingStage3">
                            <div class="stage-icon">⚙️</div>
                            <div class="stage-text">Data Validation</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="progress-text">0%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage" id="mappingStage4">
                            <div class="stage-icon">💾</div>
                            <div class="stage-text">Saving to Database</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="progress-text">0%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overall-progress">
                        <div class="overall-bar">
                            <div class="overall-fill" id="mappingOverallFill" style="width: 50%"></div>
                        </div>
                        <div class="overall-text">
                            <span id="mappingOverallPercent">50%</span> Complete
                            <div id="mappingCurrentTask">Validating mapped data...</div>
                        </div>
                    </div>
                    
                    <div class="progress-details">
                        <div class="detail-item">
                            <span class="detail-label">Processing Status:</span>
                            <span class="detail-value" id="processingStatus">In Progress</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Current Stage:</span>
                            <span class="detail-value" id="currentStage">Data Validation</span>
                        </div>
                    </div>
                </div>
                
                <form action="" method="post">
                    <table class="user-mapping-table">
                        <thead>
                            <tr>
                                <th>CSV Column</th>
                                <th>Sample Data</th>
                                <th>Map To</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $header = $mappingResult['header'];
                            $sampleRow = !empty($mappingResult['sample']) ? $mappingResult['sample'][0] : [];
                            
                            foreach ($header as $index => $column):
                                $sampleValue = isset($sampleRow[$index]) ? $sampleRow[$index] : '';
                                
                                // Get mapping info
                                $targetField = '';
                                $confidence = null;
                                
                                if ($mappingResult['status'] === 'success') {
                                    $targetField = isset($mappingResult['mapping'][$column]) ? 
                                        $mappingResult['mapping'][$column] : '';
                                    $confidence = 100;
                                } else {
                                    $targetField = isset($mappingResult['suggestions'][$column]['mapping']) ? 
                                        $mappingResult['suggestions'][$column]['mapping'] : '';
                                    $confidence = isset($mappingResult['suggestions'][$column]['confidence']) ? 
                                        $mappingResult['suggestions'][$column]['confidence'] : 0;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($column); ?></td>
                                <td><?php echo htmlspecialchars($sampleValue); ?></td>
                                <td>
                                <select name="mapping[<?php echo htmlspecialchars($column); ?>]" class="user-field-select">
                                    <option value="">-- Ignore this column --</option>
                                    <?php foreach ($systemFields as $field): ?>
                                        <?php 
                                        $selected = '';
                                        if ($targetField === $field['value']) {
                                            $selected = 'selected'; 
                                        } elseif (empty($targetField) && isset($field['default_column']) && $column === $field['default_column']) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo $field['value']; ?>" <?php echo $selected; ?> data-field="<?php echo $field['value']; ?>">
                                            <?php echo $field['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </td>
                                <td>
                                    <?php if ($confidence !== null): ?>
                                        <div class="user-confidence-bar">
                                            <div class="user-confidence-fill" style="width: <?php echo $confidence; ?>%"></div>
                                            <span><?php echo round($confidence); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="user-form-actions">
                        <button type="submit" name="confirm_mapping" class="user-btn-primary">Confirm Mapping & Continue Comparison</button>
                        <a href="compare.php" class="user-btn-secondary">Cancel Comparison</a>
                    </div>
                </form>
                
                <div class="user-sample-data">
                    <h3>Sample Data Preview</h3>
                    <div class="user-table-container">
                        <table class="user-data-table">
                            <thead>
                                <tr>
                                    <?php foreach ($header as $column): ?>
                                        <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappingResult['sample'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
        
        <?php include 'user_footer.php'; ?>
    </div>

<!-- Use the same JavaScript as map_columns.php -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldSelects = document.querySelectorAll('.user-field-select');
    
    // String similarity calculation function (same as map_columns.php)
    function calculateStringSimilarity(str1, str2) {
        // Convert to lowercase for comparison
        str1 = str1.toLowerCase().replace(/[_\s]/g, '');
        str2 = str2.toLowerCase().replace(/[_\s]/g, '');
        
        // If exact match, return 100%
        if (str1 === str2) return 100;
        
        // Calculate Levenshtein distance
        const matrix = [];
        const len1 = str1.length;
        const len2 = str2.length;
        
        // Initialize matrix
        for (let i = 0; i <= len1; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= len2; j++) {
            matrix[0][j] = j;
        }
        
        // Fill matrix
        for (let i = 1; i <= len1; i++) {
            for (let j = 1; j <= len2; j++) {
                if (str1.charAt(i - 1) === str2.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1, // substitution
                        matrix[i][j - 1] + 1,     // insertion
                        matrix[i - 1][j] + 1      // deletion
                    );
                }
            }
        }
        
        // Calculate similarity percentage
        const maxLen = Math.max(len1, len2);
        const distance = matrix[len1][len2];
        const similarity = ((maxLen - distance) / maxLen) * 100;
        
        // Boost similarity for partial matches
        if (str1.includes(str2) || str2.includes(str1)) {
            return Math.max(similarity, 75);
        }
        
        // Check for common keywords
        const keywords = {
            'traffic': ['traffic', 'source', 'channel'],
            'sessions': ['sessions', 'visits', 'session'],
            'users': ['users', 'visitors', 'unique'],
            'pageviews': ['pageviews', 'pages', 'views'],
            'bounce': ['bounce', 'rate'],
            'duration': ['duration', 'time', 'avg'],
            'engaged': ['engaged', 'engagement'],
            'events': ['events', 'event'],
            'revenue': ['revenue', 'total', 'value']
        };
        
        for (const [category, terms] of Object.entries(keywords)) {
            if (terms.some(term => str1.includes(term)) && terms.some(term => str2.includes(term))) {
                return Math.max(similarity, 80);
            }
        }
        
        return Math.max(similarity, 0);
    }
    
    // Function to update confidence
    function updateConfidence(selectElement) {
        const csvColumn = selectElement.closest('tr').cells[0].textContent.trim();
        const selectedField = selectElement.value;
        const confidenceCell = selectElement.closest('tr').cells[3];
        
        if (!selectedField) {
            confidenceCell.innerHTML = '';
            return;
        }
        
        // Calculate confidence based on string similarity
        let confidence = calculateStringSimilarity(csvColumn, selectedField);
        
        // Determine confidence color and icon
        let confidenceColor = '#dc3545'; // Red for low confidence
        let confidenceIcon = '⚠️';
        
        if (confidence >= 85) {
            confidenceColor = '#28a745'; // Green for high confidence
            confidenceIcon = '✅';
        } else if (confidence >= 60) {
            confidenceColor = '#ffc107'; // Yellow for medium confidence
            confidenceIcon = '⚡';
        }
        
        // Update confidence bar with proper text visibility
        confidenceCell.innerHTML = `
            <div class="user-confidence-bar">
                <div class="user-confidence-fill" style="width: ${confidence}%; background-color: ${confidenceColor}; transition: all 0.3s ease;"></div>
                <span>${confidenceIcon} ${Math.round(confidence)}%</span>
            </div>
        `;
    }
    
    // Function to update available options
    function updateAvailableOptions() {
        // Get all currently selected values
        const selectedValues = Array.from(fieldSelects).map(select => select.value).filter(Boolean);
        
        // For each select element
        fieldSelects.forEach(select => {
            const currentValue = select.value;
            
            // Check each option
            Array.from(select.options).forEach(option => {
                const optionValue = option.value;
                
                // Skip the empty option
                if (!optionValue) return;
                
                // If this option is selected in this select, keep it enabled
                if (optionValue === currentValue) {
                    option.disabled = false;
                    return;
                }
                
                // If this option is selected in another select, disable it
                option.disabled = selectedValues.includes(optionValue);
            });
        });
    }
    
    // Add change event listeners to all selects
    fieldSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateAvailableOptions();
            updateConfidence(this);
        });
    });
    
    // Initial update of available options and confidence
    updateAvailableOptions();
    
    // Calculate initial confidence for pre-selected fields
    fieldSelects.forEach(select => {
        if (select.value) {
            updateConfidence(select);
        }
    });

    // Form submission with progress animation
    const form = document.querySelector('form');
    const progressDiv = document.getElementById('mappingProgress');
    let formSubmitted = false;
    
    form.addEventListener('submit', function(e) {
        if (formSubmitted) return;
        formSubmitted = true;
        
        progressDiv.style.display = 'block';
        form.style.display = 'none';
        
        runProgressAnimation();
        
        setTimeout(() => {
            console.log('Form processing completed, PHP will handle redirect');
        }, 1000);
    });
    
    // Progress animation functions (same as map_columns.php)
    function runProgressAnimation() {
        setTimeout(() => {
            updateMappingProgress(3, 20, 'Initializing data validation...');
        }, 200);
        
        setTimeout(() => {
            updateMappingProgress(3, 50, 'Checking data types...');
        }, 400);
        
        setTimeout(() => {
            updateMappingProgress(3, 80, 'Validating data values...');
        }, 600);
        
        setTimeout(() => {
            updateMappingProgress(3, 100, 'Data validation completed ✓');
            completeStage(3);
            updateProcessingStatus('Validation Complete', 'Database Operations');
        }, 800);
        
        setTimeout(() => {
            activateStage(4);
            updateMappingProgress(4, 25, 'Preparing database transaction...');
            updateProcessingStatus('In Progress', 'Database Saving');
        }, 900);
        
        setTimeout(() => {
            updateMappingProgress(4, 50, 'Creating data records...');
        }, 1000);
        
        setTimeout(() => {
            updateMappingProgress(4, 75, 'Inserting traffic data...');
        }, 1100);
        
        setTimeout(() => {
            updateMappingProgress(4, 100, 'Data saved successfully! ✓');
            completeStage(4);
            updateOverallProgress(100, 'Import completed successfully! 🎉');
            updateProcessingStatus('Complete', 'Ready');
        }, 1200);
    }
    
    function updateMappingProgress(stage, percent, message) {
        const stageElement = document.getElementById(`mappingStage${stage}`);
        const progressFill = stageElement.querySelector('.progress-fill');
        const progressText = stageElement.querySelector('.progress-text');
        
        if (progressFill) {
            progressFill.style.width = `${percent}%`;
            
            if (percent === 100) {
                progressFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
                progressFill.style.boxShadow = '0 2px 8px rgba(40, 167, 69, 0.4)';
            }
        }
        if (progressText) {
            progressText.textContent = `${percent}%`;
        }
        
        let overallPercent = 50;
        if (stage === 3) {
            overallPercent += (percent * 0.25);
        } else if (stage === 4) {
            overallPercent = 75 + (percent * 0.25);
        }
        
        updateOverallProgress(overallPercent, message);
    }
    
    function updateOverallProgress(percent, message) {
        const overallFill = document.getElementById('mappingOverallFill');
        const overallPercent = document.getElementById('mappingOverallPercent');
        const currentTask = document.getElementById('mappingCurrentTask');
        
        if (overallFill) {
            overallFill.style.width = `${Math.round(percent)}%`;
            
            if (percent >= 100) {
                overallFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
                overallFill.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.5)';
                overallFill.style.animation = 'pulse-success 1.5s infinite';
            }
        }
        if (overallPercent) {
            overallPercent.textContent = `${Math.round(percent)}%`;
            
            if (percent >= 100) {
                overallPercent.style.color = '#28a745';
                overallPercent.style.fontWeight = '700';
            }
        }
        if (currentTask) {
            currentTask.textContent = message;
        }
    }
    
    function updateProcessingStatus(status, stage) {
        const processingStatus = document.getElementById('processingStatus');
        const currentStage = document.getElementById('currentStage');
        
        if (processingStatus) {
            processingStatus.textContent = status;
            if (status === 'Complete') {
                processingStatus.style.color = '#28a745';
                processingStatus.style.fontWeight = '600';
            }
        }
        if (currentStage) {
            currentStage.textContent = stage;
        }
    }
    
    function activateStage(stageIndex) {
        const stageElement = document.getElementById(`mappingStage${stageIndex}`);
        stageElement.classList.remove('completed');
        stageElement.classList.add('active');
        
        const icon = stageElement.querySelector('.stage-icon');
        icon.textContent = '⚙️';
        icon.style.animation = 'pulse 2s infinite';
    }
    
    function completeStage(stageIndex) {
        const stageElement = document.getElementById(`mappingStage${stageIndex}`);
        stageElement.classList.remove('active');
        stageElement.classList.add('completed');
        
        const icon = stageElement.querySelector('.stage-icon');
        icon.textContent = '✅';
        icon.style.animation = 'bounce 0.6s ease';
        
        const progressFill = stageElement.querySelector('.progress-fill');
        const progressText = stageElement.querySelector('.progress-text');
        
        if (progressFill) {
            progressFill.style.width = '100%';
            progressFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
        }
        if (progressText) {
            progressText.textContent = '100%';
            progressText.style.color = '#28a745';
            progressText.style.fontWeight = '600';
        }
    }

    // Handle browser back button to prevent stuck state
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            const form = document.querySelector('form');
            const progressDiv = document.getElementById('mappingProgress');
            
            if (form) form.style.display = 'block';
            if (progressDiv) progressDiv.style.display = 'none';
        }
    });
});
</script>
</body>
</html>