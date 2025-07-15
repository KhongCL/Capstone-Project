<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: map_columns.php
// Description: CSV column mapping interface that handles manual column mapping, data validation,
//              and transformation with confidence scoring and duplicate prevention for uploaded files.
// First Written On: 8 July 2025
// Edited On: 14 July 2025

require_once '../auth/user_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
include '../functions.php';

session_start();

// Check if we came from comparison - if so, redirect to comparison mapping
if (isset($_SESSION['compare_files'])) {
    header('Location: map_columns_compare.php?file=1');
    exit;
}

// Clear sample data session when user reaches manual mapping
if (isset($_SESSION['using_sample_data'])) {
    unset($_SESSION['using_sample_data']);
    unset($_SESSION['sample_upload_id']);
    error_log("Cleared sample data session in map_columns.php");
}

// Debugging for form submission
error_log("map_columns.php Debug");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("Session uploaded_csv: " . ($_SESSION['uploaded_csv'] ?? 'NOT SET'));
error_log("Session mapping_result: " . (isset($_SESSION['mapping_result']) ? 'SET' : 'NOT SET'));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// If no uploaded file in session, redirect
if (!isset($_SESSION['uploaded_csv'])) {
    header('Location: index.php');
    exit;
}

$processor = new CsvProcessor();

// Process the initial mapping if first visit
if (!isset($_SESSION['mapping_result'])) {
    $_SESSION['mapping_result'] = $processor->processFile($_SESSION['uploaded_csv']);
}

$mappingResult = $_SESSION['mapping_result'];
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
    $processor = new CsvProcessor();
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
    error_log("=== PROCESSING FORM SUBMISSION ===");
    
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
        // Check for minimum required mappings for meaningful analytics
        $mappedFields = array_values($columnMapping);
        $hasTrafficSource = in_array('traffic_source', $mappedFields);
        $hasMetric = array_intersect(['visits', 'sessions', 'users', 'pageviews', 'events_per_session', 'key_events', 'total_revenue'], $mappedFields);
        
        if (!$hasTrafficSource) {
            $error_message = "Please map at least one 'Traffic Source' column for meaningful analytics. This helps identify where your visitors are coming from.";
            error_log("ERROR: No traffic source mapped");
        } elseif (empty($hasMetric)) {
            $error_message = "Please map at least one metric column (Visits, Sessions, Users, Events, Revenue, etc.) to analyze your data properly.";
            error_log("ERROR: No metrics mapped");
        } else {
            error_log("Starting data transformation...");
            
            // Get the file path from session
            $filePath = $_SESSION['uploaded_csv'] ?? null;
            
            if (!$filePath || !file_exists($filePath)) {
                $error_message = "File not found. Please upload your CSV file again.";
                error_log("ERROR: File path not found or file doesn't exist: " . ($filePath ?? 'NULL'));
            } else {
                // For manual mapping, set a default format to avoid validation errors
                $format = 'manual_mapping'; // Use a special format identifier for manual mappings
                
                // Check if we can detect format based on mapped columns
                $ga4RequiredFields = ['traffic_source', 'visits', 'engaged_sessions', 'bounce_rate'];
                $ga4MatchCount = count(array_intersect($ga4RequiredFields, $mappedFields));
                
                if ($ga4MatchCount >= 3) {
                    $format = 'ga4_traffic_acquisition';
                    error_log("Detected GA4 format based on manual mappings (matches: $ga4MatchCount)");
                } else {
                    error_log("Using manual mapping format for validation");
                }

                error_log("Using format for transformation: " . $format);
                
                try {
                    // Clear any previous validation errors before transformation
                    if (isset($_SESSION['validation_errors'])) {
                        unset($_SESSION['validation_errors']);
                        error_log("Cleared previous validation errors before new transformation");
                    }
                    
                    //Set a flag to indicate this is manual mapping
                    $_SESSION['manual_mapping_mode'] = true;
                    
                    $transformedData = $processor->transformData($filePath, $columnMapping, $format);
                    error_log("Transformation completed. Rows: " . count($transformedData));
                    
                    // Clear the manual mapping flag
                    unset($_SESSION['manual_mapping_mode']);
                    
                    if (empty($transformedData)) {
                        error_log("ERROR: No data returned from transformation");
                        
                        // Redirect to index.php with validation errors instead of showing them on mapping page
                        if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
                            $validationErrors = $_SESSION['validation_errors'];
                            error_log("Found validation errors, redirecting to index.php: " . implode('; ', array_slice($validationErrors, 0, 5)));
                            
                            // Store file information for re-upload display
                            $_SESSION['failed_file_info'] = [
                                'name' => $_SESSION['original_file_name'] ?? $fileName, // Use original name
                                'size' => $_SESSION['uploaded_file_size'] ?? filesize($filePath), // Use actual file size
                                'mapping_attempted' => true,
                                'mapped_columns' => count($columnMapping),
                                'total_columns' => count($mappingResult['header'] ?? [])
                            ];

                            error_log("STORED FAILED FILE INFO: " . json_encode($_SESSION['failed_file_info']));
                            
                            // Create a comprehensive error message for index.php
                            $uniqueErrors = array_unique($validationErrors);
                            $errorCount = count($validationErrors);
                            
                            if (count($uniqueErrors) > 20) {
                                // Too many errors - show summary
                                $sampleErrors = array_slice($uniqueErrors, 0, 5);
                                $errorMessage = "Your CSV file contains " . $errorCount . " validation errors across multiple rows. " .
                                               "Sample errors: " . implode('; ', $sampleErrors) . "... " .
                                               "Please fix the data issues in your CSV file and upload again.";
                            } else {
                                $errorMessage = "Data validation errors found: " . implode('; ', $uniqueErrors) . 
                                               ". Please correct these issues and upload again.";
                            }
                            
                            $_SESSION['upload_message'] = [
                                'type' => 'error',
                                'message' => $errorMessage,
                                'validation_errors' => $validationErrors,
                                'show_detailed_errors' => true
                            ];
                            
                            // Clean up the uploaded file
                            if (file_exists($filePath)) {
                                unlink($filePath);
                                error_log("Cleaned up failed file: $filePath");
                            }
                            
                            // Clear mapping session data
                            unset($_SESSION['uploaded_csv']);
                            unset($_SESSION['mapping_result']);
                            unset($_SESSION['csv_metadata']);
                            unset($_SESSION['uploaded_file_name']);
                            unset($_SESSION['uploaded_file_size']);
                            
                            error_log("Redirecting to index.php with validation errors");
                            error_log("Error Processing - Redirecting to index.php");
                            error_log("Found " . count($validationErrors) . " validation errors");
                            error_log("About to redirect to: index.php?mapping_failed=1");
                            header('Location: index.php?mapping_failed=1');
                            exit;
                        } else {
                            // No validation errors but no data - general error
                            $_SESSION['upload_message'] = [
                                'type' => 'error',
                                'message' => 'No valid data found after processing. Please check your CSV file and try again.'
                            ];
                            
                            // Clean up
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            unset($_SESSION['uploaded_csv']);
                            unset($_SESSION['mapping_result']);
                            unset($_SESSION['csv_metadata']);
                            
                            header('Location: index.php?upload_failed=1');
                            exit;
                        }
                    } else {
                        error_log("Sample transformed data: " . json_encode($transformedData[0] ?? []));
                        
                        // Save transformed data to database
                        $saveResult = saveTransformedData($conn, $transformedData);
                        error_log("Save result: " . json_encode($saveResult));
                        
                        if ($saveResult['type'] === 'success') {
                            // Success - redirect to overview page
                            error_log("Data successfully saved to database");
                            
                            // Clear mapping session data
                            unset($_SESSION['uploaded_csv']);
                            unset($_SESSION['mapping_result']);
                            unset($_SESSION['csv_metadata']);
                            unset($_SESSION['uploaded_file_name']);
                            unset($_SESSION['uploaded_file_size']);
                            
                            // Clear any validation errors since we succeeded
                            if (isset($_SESSION['validation_errors'])) {
                                unset($_SESSION['validation_errors']);
                            }
                            
                            // Set success flag for redirect
                            $_SESSION['upload_just_completed'] = true;
                            
                            // Redirect to index.php with success parameter
                            error_log("Successful Processing - Redirecting to index.php");
                            error_log("Transformation completed successfully with " . count($transformedData) . " rows");
                            error_log("About to redirect to: index.php?upload_success=1");
                            header('Location: index.php?upload_success=1');
                            exit;
                        } else {
                            error_log("ERROR: Failed to save data to database");
                            $error_message = $saveResult['message'];
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERROR: Exception during transformation: " . $e->getMessage());
                    
                    // Clear the manual mapping flag on error
                    unset($_SESSION['manual_mapping_mode']);
                    
                    // Also redirect exceptions to index.php
                    $_SESSION['upload_message'] = [
                        'type' => 'error',
                        'message' => 'Error processing data: ' . $e->getMessage()
                    ];
                    
                    // Clean up
                    if (isset($filePath) && file_exists($filePath)) {
                        unlink($filePath);
                    }
                    unset($_SESSION['uploaded_csv']);
                    unset($_SESSION['mapping_result']);
                    unset($_SESSION['csv_metadata']);
                    
                    header('Location: index.php?processing_failed=1');
                    exit;
                }
            }
        }
    }
    
    error_log("End Form Processing");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map CSV Columns - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
    <style>
				/* Scrollable Error Message Styles */
        .error-container {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--danger);
            max-height: 400px;
            overflow-y: auto;
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 6px;
            background-color: rgba(255,255,255,0.5);
        }

        .error-item {
            background-color: #fff5f5;
            border: 1px solid #fed7e2;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 3px solid #e53e3e;
        }

        .error-item:last-child {
            margin-bottom: 0;
        }

        .validation-help {
            background: var(--light-gray);
            border: 2px solid #68d391;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            max-height: 500px;
            overflow-y: auto;
        }

        .validation-tips {
            max-height: 350px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .validation-errors-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 6px;
            background-color: rgba(255,255,255,0.5);
        }

        .validation-errors-list .error-item {
            background-color: #fff5f5;
            border: 1px solid #fed7e2;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 3px solid #e53e3e;
        }

        .validation-errors-list .error-item:last-child {
            margin-bottom: 0;
        }

        .error-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
            color: #721c24;
        }

        .error-container::-webkit-scrollbar,
        .error-list::-webkit-scrollbar,
        .validation-help::-webkit-scrollbar,
        .validation-tips::-webkit-scrollbar,
        .validation-errors-list::-webkit-scrollbar {
            width: 8px;
        }

        .error-container::-webkit-scrollbar-track,
        .error-list::-webkit-scrollbar-track,
        .validation-help::-webkit-scrollbar-track,
        .validation-tips::-webkit-scrollbar-track,
        .validation-errors-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .error-container::-webkit-scrollbar-thumb,
        .error-list::-webkit-scrollbar-thumb,
        .validation-help::-webkit-scrollbar-thumb,
        .validation-tips::-webkit-scrollbar-thumb,
        .validation-errors-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .error-container::-webkit-scrollbar-thumb:hover,
        .error-list::-webkit-scrollbar-thumb:hover,
        .validation-help::-webkit-scrollbar-thumb:hover,
        .validation-tips::-webkit-scrollbar-thumb:hover,
        .validation-errors-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        @media (max-width: 768px) {
            .error-container {
                max-height: 300px;
                padding: 15px;
            }
            
            .error-list,
            .validation-errors-list {
                max-height: 200px;
            }
            
            .validation-help {
                max-height: 350px;
                padding: 15px;
            }
            
            .validation-tips {
                max-height: 250px;
						}
        }

        @media (max-width: 480px) {
            .error-container {
                max-height: 250px;
						}
            
            .error-list,
            .validation-errors-list {
                max-height: 150px;
            }
            
            .validation-help {
                max-height: 300px;
            }
            
            .validation-tips {
                max-height: 200px;
            }
        }

        /* Confidence Bar Styles */
        .user-confidence-bar {
            justify-content: left;
        }

        .user-confidence-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            border-radius: 11px;
            transition: all 0.3s ease;
            background-color: var(--danger);
            transform-origin: left;
        }

        .user-confidence-bar span {
            position: relative;
            z-index: 10;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            margin: 0;
            padding: 0 8px;
            text-align: center;
        }

        .user-confidence-bar[data-confidence="low"] span {
            color: #333;
            text-shadow: none;
            justify-content: center;
        }

        .user-confidence-bar[data-confidence="medium"] span {
            color: #333;
            text-shadow: none;
            justify-content: center;
        }

        .user-confidence-bar[data-confidence="high"] span {
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.7);
            justify-content: center;
        }

        /* Ensure progress bar fills from left when width is very small */
        .user-confidence-fill[style*="width: 0%"],
        .user-confidence-fill[style*="width: 1%"],
        .user-confidence-fill[style*="width: 2%"],
        .user-confidence-fill[style*="width: 3%"],
        .user-confidence-fill[style*="width: 4%"],
        .user-confidence-fill[style*="width: 5%"] {
            min-width: 2px;
        }

        /* Table styling to accommodate confidence bars */
        .user-mapping-table td:last-child {
            min-width: 120px;
            padding: 8px;
        }

        /* Error State Styles  */
        .progress-stage.error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .progress-stage.error .stage-icon {
            color: #dc3545;
            font-size: 1.2em;
        }

        .progress-stage.error .progress-fill {
            background-color: #dc3545 !important;
        }

        .progress-stage.error .progress-text {
            color: #dc3545 !important;
            font-weight: 600;
        }

        .progress-stage.error .stage-text {
            color: #721c24;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container user-map-columns-container">
        <?php include 'user_header.php'; ?>
        
        <main>
            <section class="user-mapping-section">
                <h2>Map CSV Columns</h2>
                <?php if (isset($error_message)): ?>
                    <?php 
                    // Check if this is a validation error message with multiple errors
                    if (strpos($error_message, 'Data validation errors found:') !== false): 
                        // Parse the validation errors for better display
                        $errorText = str_replace('Data validation errors found: ', '', $error_message);
                        $errorText = str_replace('. Please correct these issues and upload again.', '', $errorText);
                        
                        // Split by row pattern to separate individual errors
                        $errors = preg_split('/(?=Row \d+)/', $errorText);
                        $errors = array_filter(array_map('trim', $errors)); // Remove empty elements
                    ?>
                        <!-- Error container matching index.php style -->
                        <div class="error-container">
                            <h4><i class="fas fa-exclamation-triangle"></i> CSV File Validation Failed</h4>
                            <p><strong>Your file couldn't be processed due to data validation errors.</strong></p>
                            
                            <div style="margin: 15px 0;">
                                <p><strong>Found <?php echo count($errors); ?> validation issues:</strong></p>
                                
                                <ul class="validation-errors-list">
                                    <?php foreach ($errors as $error): ?>
                                        <?php
                                        // Parse error message and suggestions
                                        $parts = explode(' Suggestions: ', $error);
                                        $errorMessage = $parts[0];
                                        $suggestions = isset($parts[1]) ? $parts[1] : '';
                                        ?>
                                        <li class="error-item">
                                            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
                                            <?php if (!empty($suggestions)): ?>
                                                <div class="error-suggestions">
                                                    <strong>💡 Suggestions:</strong> 
                                                    <span class="suggestions-text"><?php echo htmlspecialchars($suggestions); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="validation-help">
                                <h4><i class="fas fa-lightbulb"></i> How to Fix These Issues</h4>
                                <div class="validation-tips">
                                    <h5>🔍 Common Data Issues:</h5>
                                    <ul>
                                        <li><strong>Invalid numbers:</strong> Remove text, symbols, or extra characters from numeric columns (e.g., "42+3" → "45", "12:45" → "765" seconds)</li>
                                        <li><strong>Percentage format:</strong> Use decimal format (0.25) or percentage with % symbol (25%)</li>
                                        <li><strong>Time format:</strong> Convert time to seconds or decimal hours (e.g., "2:30" → "150" seconds)</li>
                                        <li><strong>Empty values:</strong> Fill in missing required data or use 0 for zero values</li>
                                        <li><strong>Negative values:</strong> Ensure metrics like visits, events are positive numbers</li>
                                        <li><strong>Scientific notation:</strong> Convert to regular numbers (e.g., "1.2e3" → "1200")</li>
                                        <li><strong>Special characters:</strong> Remove currency symbols, commas from numbers (e.g., "$1,200" → "1200")</li>
                                    </ul>
                                    
                                    <h5>✅ Quick Fixes:</h5>
                                    <ul>
                                        <li>Open your CSV in Excel/Google Sheets</li>
                                        <li>Check columns with errors and fix the data format</li>
                                        <li>Save the file and upload again</li>
                                        <li>Use "Find & Replace" to fix common issues across multiple rows</li>
                                    </ul>
                                    
                                    <h5>📋 Alternative Options:</h5>
                                    <ul>
                                        <li><strong>Try our sample data:</strong> Load sample data to explore the dashboard features</li>
                                        <li><strong>Export from your analytics tool:</strong> Check if your source has a different export format</li>
                                        <li><strong>Simplify your data:</strong> Remove problematic columns and re-upload with core metrics only</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <p class="error-footer"><strong>Please correct these issues in your CSV file and upload again.</strong></p>
                        </div>
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="user-alert user-alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!isset($error_message)): ?>
                    <div class="user-alert user-alert-success">
                        <h4>🎉 Upload Successful!</h4>
                        <p><strong>Your CSV file has been successfully uploaded and validated.</strong></p>
                        <p>Since the format wasn't automatically recognized, please review and confirm the column mappings below to complete the import process.</p>
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
                        <button type="submit" name="confirm_mapping" class="btn">Confirm Mapping & Import Data</button>
                        <a href="index.php?clear_mapping=1" class="btn btn-secondary">Cancel</a>
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
    <script>
        // Set global variables for upload_progress.js to use
        window.sessionHasExistingData = <?php echo (isset($_SESSION['latest_upload_id']) || isset($_SESSION['using_sample_data'])) ? 'true' : 'false'; ?>;
        window.sessionIsUsingSampleData = <?php echo (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data']) ? 'true' : 'false'; ?>;
        
        // Pass validation error state to JavaScript
        window.hasValidationErrors = <?php echo (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) ? 'true' : 'false'; ?>;
        
        // Utility functions that don't require DOM to be ready
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function toggleDataPreview() {
            const content = document.getElementById('previewContent');
            const toggle = document.getElementById('previewToggle');
            
            if (content && toggle) {
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    toggle.classList.add('rotated');
                } else {
                    content.style.display = 'none';
                    toggle.classList.remove('rotated');
                }
            }
        }

        // Single DOMContentLoaded event handler
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Map Columns Page Initialization');
            
            const errorItems = document.querySelectorAll('.error-item');
            errorItems.forEach(function(errorItem) {
                const html = errorItem.innerHTML;
                
                if (html.includes('<br><strong>💡 Suggestions:</strong>')) {
                    const parts = html.split('<br><strong>💡 Suggestions:</strong>');
                    if (parts.length >= 2) {
                        const errorMessage = parts[0];
                        const suggestionText = parts[1].trim();
                        
                        errorItem.innerHTML = errorMessage + 
                            '<div class="error-suggestions">' +
                            '<strong>💡 Suggestions:</strong> ' +
                            '<span class="suggestions-text">' + suggestionText + '</span>' +
                            '</div>';
                    }
                }
                // Direct suggestion text after strong tag
                else if (html.includes('💡 Suggestions:') && !html.includes('error-suggestions')) {
                    const suggestionRegex = /<strong>💡 Suggestions:<\/strong>\s*([^<]+)/gi;
                    const newHtml = html.replace(suggestionRegex, function(match, suggestionText) {
                        return '<div class="error-suggestions">' +
                            '<strong>💡 Suggestions:</strong> ' +
                            '<span class="suggestions-text">' + suggestionText.trim() + '</span>' +
                            '</div>';
                    });
                    
                    if (newHtml !== html) {
                        errorItem.innerHTML = newHtml;
                    }
                }
            });
            
            // Handle upload success redirect case
            const urlParams = new URLSearchParams(window.location.search);
            const uploadSuccess = urlParams.get('upload_success');
            
            if (uploadSuccess === '1') {
                const sampleDataStatus = document.querySelector('.sample-data-status');
                if (sampleDataStatus) {
                    sampleDataStatus.style.display = 'none';
                    console.log('Sample data UI hidden immediately after upload success');
                }
            }
            
            // Setup file input handler (only if element exists)
            const csvFileInput = document.getElementById('csvFile');
            if (csvFileInput) {
                csvFileInput.addEventListener('change', function() {
                    const fileInfo = document.getElementById('fileInfo');
                    const fileName = fileInfo?.querySelector('.file-name');
                    const fileSize = fileInfo?.querySelector('.file-size');
                    
                    if (this.files.length > 0 && fileInfo && fileName && fileSize) {
                        const file = this.files[0];
                        fileName.textContent = file.name;
                        fileSize.textContent = formatFileSize(file.size);
                        fileInfo.style.display = 'block';
                    } else if (fileInfo) {
                        fileInfo.style.display = 'none';
                    }
                });
            }
            
						// Confidence System and Dropdown Locking
            console.log('Confidence System Debug Start');
            
            const fieldSelects = document.querySelectorAll('.user-field-select');
            console.log('Found field selects:', fieldSelects.length);
            
            if (fieldSelects.length === 0) {
                console.log('No field selects found - confidence system disabled');
                return;
            }
            
            // String similarity calculation function
            function calculateStringSimilarity(str1, str2) {
                console.log(`Calculating similarity between "${str1}" and "${str2}"`);
                
                // Convert to lowercase for comparison
                str1 = str1.toLowerCase().replace(/[_\s]/g, '');
                str2 = str2.toLowerCase().replace(/[_\s]/g, '');
                
                // If exact match, return 100%
                if (str1 === str2) {
                    console.log('Exact match found: 100%');
                    return 100;
                }
                
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
                    const boostedSimilarity = Math.max(similarity, 75);
                    console.log(`Partial match boost: ${similarity}% -> ${boostedSimilarity}%`);
                    return boostedSimilarity;
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
                        const keywordSimilarity = Math.max(similarity, 80);
                        console.log(`Keyword match for "${category}": ${similarity}% -> ${keywordSimilarity}%`);
                        return keywordSimilarity;
                    }
                }
                
                console.log(`Final similarity: ${Math.max(similarity, 0)}%`);
                return Math.max(similarity, 0);
            }

            // Function to update confidence
            function updateConfidence(selectElement) {
                console.log('Update Confidence Debug');
                
                const row = selectElement.closest('tr');
                if (!row) {
                    console.log('ERROR: Could not find table row');
                    return;
                }
                
                const csvColumn = row.cells[0]?.textContent?.trim();
                const selectedField = selectElement.value;
                const confidenceCell = row.cells[3];
                
                console.log('CSV Column:', csvColumn);
                console.log('Selected Field:', selectedField);
                console.log('Confidence Cell:', confidenceCell);
                
                if (!csvColumn || !confidenceCell) {
                    console.log('ERROR: Missing CSV column or confidence cell');
                    return;
                }
                
                if (!selectedField) {
                    console.log('No field selected, clearing confidence');
                    confidenceCell.innerHTML = '';
                    return;
                }
                
                // Calculate confidence based on string similarity
                let confidence = calculateStringSimilarity(csvColumn, selectedField);
                console.log('Calculated confidence:', confidence);
                
                // Determine confidence color and icon
                let confidenceColor = '#dc3545';
                let confidenceIcon = '⚠️';
                let confidenceLevel = 'low';
                let textColor = '#333';
                
                if (confidence >= 85) {
                    confidenceColor = '#28a745';
                    confidenceIcon = '✅';
                    confidenceLevel = 'high';
                    textColor = '#fff';
                } else if (confidence >= 60) {
                    confidenceColor = '#ffc107';
                    confidenceIcon = '⚡';
                    confidenceLevel = 'medium';
                    textColor = '#333';
                }
                
                console.log('Confidence color:', confidenceColor);
                console.log('Confidence icon:', confidenceIcon);
                console.log('Confidence level:', confidenceLevel);
                
                // Update confidence bar with proper left-to-right fill and text visibility
                const confidenceHTML = `
                    <div class="user-confidence-bar" data-confidence="${confidenceLevel}">
                        <div class="user-confidence-fill" style="width: ${confidence}%; background-color: ${confidenceColor}; transition: all 0.3s ease;"></div>
                        <span style="color: ${textColor}; text-shadow: ${textColor === '#fff' ? '0 1px 2px rgba(0,0,0,0.7)' : 'none'};">${confidenceIcon} ${Math.round(confidence)}%</span>
                    </div>
                `;
                
                console.log('Setting confidence HTML:', confidenceHTML);
                confidenceCell.innerHTML = confidenceHTML;
                
                console.log('Update Confidence Complete');
            }
            
            // Function to update available options (prevent duplicate selections)
            function updateAvailableOptions() {
                console.log('Update Available Options Debug');
                
                // Get all currently selected values
                const selectedValues = Array.from(fieldSelects).map(select => select.value).filter(Boolean);
                console.log('Currently selected values:', selectedValues);
                
                // For each select element
                fieldSelects.forEach((select, selectIndex) => {
                    const currentValue = select.value;
                    console.log(`Processing select ${selectIndex}, current value: "${currentValue}"`);
                    
                    // Check each option
                    Array.from(select.options).forEach((option, optionIndex) => {
                        const optionValue = option.value;
                        
                        // Skip the empty option
                        if (!optionValue) return;
                        
                        // If this option is selected in this select, keep it enabled
                        if (optionValue === currentValue) {
                            option.disabled = false;
                            console.log(`Select ${selectIndex}, option ${optionIndex} ("${optionValue}"): enabled (current selection)`);
                            return;
                        }
                        
                        // If this option is selected in another select, disable it
                        const shouldDisable = selectedValues.includes(optionValue);
                        option.disabled = shouldDisable;
                        console.log(`Select ${selectIndex}, option ${optionIndex} ("${optionValue}"): ${shouldDisable ? 'disabled' : 'enabled'}`);
                    });
                });
                
                console.log('Update Available Options Complete');
            }
            
            // Add change event listeners to all selects
            fieldSelects.forEach((select, index) => {
                console.log(`Adding event listener to select ${index}`);
                
                select.addEventListener('change', function() {
                    console.log(`Select ${index} changed to: "${this.value}"`);
                    updateAvailableOptions();
                    updateConfidence(this);
                });
            });
            
            // Initial update of available options and confidence
            console.log('Running initial updates...');
            updateAvailableOptions();
            
            // Calculate initial confidence for pre-selected fields
            fieldSelects.forEach((select, index) => {
                if (select.value) {
                    console.log(`Initial confidence calculation for select ${index} with value: "${select.value}"`);
                    updateConfidence(select);
                }
            });
            
            console.log('Confidence System Debug End');

						// Form Submission and Progress Animation
            const form = document.querySelector('form');
            const progressDiv = document.getElementById('mappingProgress');
            let formSubmitted = false;
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (formSubmitted) return;
                    formSubmitted = true;
                    
                    if (progressDiv) {
                        progressDiv.style.display = 'block';
                        form.style.display = 'none';
                        runProgressAnimation();
                    }
                    
                    setTimeout(() => {
                        console.log('Form processing completed, PHP will handle redirect');
                    }, 1000);
                });
            }
            
            // Progress animation functions that work for both valid and invalid files
            function runProgressAnimation() {
                // Start data validation stage (Stage 3)
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
                    
                    setTimeout(() => {
                        activateStage(4);
                        updateMappingProgress(4, 25, 'Preparing database transaction...');
                        updateProcessingStatus('In Progress', 'Database Saving');
                    }, 200);
                    
                    setTimeout(() => {
                        updateMappingProgress(4, 50, 'Creating data records...');
                    }, 400);
                    
                    setTimeout(() => {
                        updateMappingProgress(4, 75, 'Inserting traffic data...');
                    }, 600);
                    
                    setTimeout(() => {
                        updateMappingProgress(4, 100, 'Data saved successfully! ✓');
                        completeStage(4);
                        updateOverallProgress(100, 'Import completed successfully! 🎉');
                        updateProcessingStatus('Complete', 'Ready');
                    }, 800);
                }, 800);
            }
            
            function updateMappingProgress(stage, percent, message) {
                const stageElement = document.getElementById(`mappingStage${stage}`);
                if (!stageElement) return;
                
                const progressFill = stageElement.querySelector('.progress-fill');
                const progressText = stageElement.querySelector('.progress-text');
                
                if (progressFill) {
                    progressFill.style.width = `${percent}%`;
                    
                    if (percent === 100 && !stageElement.classList.contains('error')) {
                        progressFill.style.background = '#28a745';
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
                        overallFill.style.background = '#28a745';
                        overallFill.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.5)';
                        overallFill.style.animation = 'pulse-success 1.5s infinite';
                    } else if (message && message.includes('failed')) {
                        overallFill.style.background = '#dc3545';
                        overallFill.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.5)';
                    }
                }
                if (overallPercent) {
                    overallPercent.textContent = `${Math.round(percent)}%`;
                    
                    if (percent >= 100) {
                        overallPercent.style.color = '#28a745';
                        overallPercent.style.fontWeight = '700';
                    } else if (message && message.includes('failed')) {
                        overallPercent.style.color = '#dc3545';
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
                    } else if (status === 'Failed') {
                        processingStatus.style.color = '#dc3545';
                        processingStatus.style.fontWeight = '600';
                    }
                }
                if (currentStage) {
                    currentStage.textContent = stage;
                }
            }
            
            function activateStage(stageIndex) {
                const stageElement = document.getElementById(`mappingStage${stageIndex}`);
                if (!stageElement) return;
                
                stageElement.classList.remove('completed');
                stageElement.classList.add('active');
                
                const icon = stageElement.querySelector('.stage-icon');
                if (icon) {
                    icon.textContent = '⚙️';
                    icon.style.animation = 'pulse 2s infinite';
                }
            }
            
            function completeStage(stageIndex) {
                const stageElement = document.getElementById(`mappingStage${stageIndex}`);
                if (!stageElement) return;
                
                stageElement.classList.remove('active');
                stageElement.classList.add('completed');
                
                const icon = stageElement.querySelector('.stage-icon');
                if (icon) {
                    icon.textContent = '✅';
                    icon.style.animation = 'bounce 0.6s ease';
                }
                
                const progressFill = stageElement.querySelector('.progress-fill');
                const progressText = stageElement.querySelector('.progress-text');
                
                if (progressFill) {
                    progressFill.style.width = '100%';
                    progressFill.style.background = '#28a745';
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
            
            console.log('Map Column Pages Initialization Complete');
        });
    </script>
</body>
</html>