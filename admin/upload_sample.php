<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: upload_sample.php
// Description: Admin sample CSV upload processor with validation, format detection,
//              data transformation, and secure file handling for sample data management.
// First Written On: 20 April 2025
// Edited On: 13 July 2025

require_once '../auth/admin_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
require_once '../functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

// Error logging
error_log("Sample Upload Start");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

$response = [
    'success' => false,
    'message' => ''
];

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request for sample upload");
    
    //  File validation
    if (!isset($_FILES['sampleCsv']) || $_FILES['sampleCsv']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = "Error uploading file: " . getUploadErrorMessage($_FILES['sampleCsv']['error'] ?? UPLOAD_ERR_NO_FILE);
        error_log("File upload error: " . $response['message']);
    } else if ($_FILES['sampleCsv']['size'] > 5 * 1024 * 1024) {
        $response['message'] = "File size exceeds the 5MB limit.";
        error_log("File size too large: " . $_FILES['sampleCsv']['size']);
    } else if (pathinfo($_FILES['sampleCsv']['name'], PATHINFO_EXTENSION) !== 'csv') {
        $response['message'] = "Only CSV files are allowed.";
        error_log("Invalid file extension");
    } else {
        // Get report type
        $reportType = isset($_POST['reportType']) ? trim($_POST['reportType']) : '';
        
        if ($reportType === 'new' && isset($_POST['newReportType']) && !empty($_POST['newReportType'])) {
            $reportType = trim($_POST['newReportType']);
        }
        
        error_log("Report type: " . $reportType);
        
        if (empty($reportType)) {
            $response['message'] = "Please select or provide a report type.";
            error_log("No report type provided");
        } else {
            try {
                // Create uploads directory if it doesn't exist
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    error_log("Created uploads directory: " . $uploadDir);
                }
                
                // Save file with unique name
                $fileName = 'sample_' . uniqid() . '_' . basename($_FILES['sampleCsv']['name']);
                $filePath = $uploadDir . $fileName;
                
                error_log("Attempting to move file to: " . $filePath);
                
                if (move_uploaded_file($_FILES['sampleCsv']['tmp_name'], $filePath)) {
                    error_log("File moved successfully to: " . $filePath);
                    
                    // Create a CSV processor instance
                    $processor = new CsvProcessor();
                    
                    // Extract metadata from the file
                    $metadata = $processor->extractGa4Metadata($filePath);
                    error_log("Extracted metadata: " . json_encode($metadata));
                    
                    // Check if file is empty
                    if (filesize($filePath) === 0) {
                        $response['message'] = "The uploaded CSV file is empty.";
                        error_log("File is empty");
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    } else {
                        error_log("Processing file of size: " . filesize($filePath));
                        
                        // Process the file
                        $result = $processor->processFile($filePath);
                        error_log("Process result: " . json_encode($result));
                        
                        if ($result['status'] === 'success' || $result['status'] === 'needs_mapping') {
                            error_log("File processed successfully, transforming data...");
                            
                            // Transform data using mapping if available
                            $transformedData = [];
                            if ($result['status'] === 'success') {
                                $transformedData = $processor->transformData($filePath, $result['mapping'], $result['format']);
                            } else {
                                // Use suggestions as mapping for unrecognized format
                                // Build mapping from suggestions with higher confidence threshold
                                $mapping = [];
                                foreach ($result['suggestions'] as $column => $suggestion) {
                                    // Fix: use 'mapping' instead of 'suggested_mapping'
                                    if ($suggestion['confidence'] > 70 && isset($suggestion['mapping'])) {
                                        $mapping[$column] = $suggestion['mapping'];
                                    }
                                }
                                
                                // If have good suggestions, use them automatically
                                if (count($mapping) >= 3) { // Need at least 3 good mappings
                                    error_log("Using suggested mapping with high confidence: " . json_encode($mapping));
                                    $transformedData = $processor->transformData($filePath, $mapping);
                                } else {
                                    // Not enough confident mappings - this is where redirect to mappings page
                                    $response['success'] = false;
                                    $response['message'] = "CSV format not recognized and automatic mapping failed. " .
                                                        "As an administrator, please visit the CSV Mappings page to add support for this format before uploading sample data.";
                                    $response['redirect_to_mappings'] = true;
                                    
                                    // Clean up file
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                        error_log("Cleaned up unrecognized format file: " . $filePath);
                                    }
                                    
                                    // Return early - don't try to process further
                                    if ($isAjax) {
                                        echo json_encode($response);
                                    } else {
                                        $_SESSION['sample_upload_message'] = $response;
                                        header('Location: upload_sample_data.php');
                                    }
                                    exit;
                                }
                            }
                            
                            error_log("Transformed data count: " . count($transformedData));
                            
                            // Check for validation errors in session after transform
                            if (session_status() == PHP_SESSION_NONE) {
                                session_start();
                            }
                            
                            // Check if there are validation errors even if some data was transformed
                            if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
                                error_log("Found validation errors after transform: " . count($_SESSION['validation_errors']));
                                
                                // If no data was transformed, treat it as a complete failure
                                if (empty($transformedData)) {
                                    // Format detailed validation errors for response
                                    $response['errors'] = [];
                                    foreach ($_SESSION['validation_errors'] as $error) {
                                        if (strpos($error, ' Suggestions: ') !== false) {
                                            $parts = explode(' Suggestions: ', $error, 2);
                                            $response['errors'][] = [
                                                'message' => $parts[0],
                                                'suggestions' => $parts[1]
                                            ];
                                        } else {
                                            $response['errors'][] = ['message' => $error];
                                        }
                                    }
                                    
                                    $response['message'] = "Data validation errors found: " . implode('; ', $_SESSION['validation_errors']) . ". Please correct these issues and upload again.";
                                    
                                    // Clear validation errors
                                    unset($_SESSION['validation_errors']);
                                    
                                    // Clean up file
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                        error_log("Cleaned up file due to validation errors: " . $filePath);
                                    }
                                    
                                    // Return error response immediately
                                    if ($isAjax) {
                                        header('Content-Type: application/json');
                                        echo json_encode($response);
                                    } else {
                                        $_SESSION['sample_upload_message'] = $response;
                                        header('Location: upload_sample_data.php');
                                    }
                                    exit;
                                }
                            }
                            
                            if (empty($transformedData)) {
                                $response['message'] = "No valid data rows found in the uploaded file after validation.";
                                error_log("No transformed data available");
                                
                                // Clean up file only if no data was saved
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                    error_log("Cleaned up file (no data): " . $filePath);
                                }
                            } else {
                                // Sample transformed row logging
                                if (!empty($transformedData)) {
                                    error_log("Sample transformed row: " . json_encode($transformedData[0]));
                                }
                                
                                // Save as sample data
                                error_log("Attempting to save sample data...");
                                $saved = saveSampleData($conn, $transformedData, $fileName, $_FILES['sampleCsv']['size'], $reportType, $metadata);
                                
                                if ($saved) {
                                    $response['success'] = true;
                                    $response['message'] = "Sample data uploaded and processed successfully.";
                                    error_log("Sample data saved successfully");
                                    // DO NOT DELETE FILE - keep it for preview functionality
                                    error_log("Keeping CSV file for preview: " . $filePath);
                                } else {
                                    $response['message'] = "Failed to save sample data to database.";
                                    error_log("Failed to save sample data");
                                    
                                    // Clean up file only if database save failed
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                        error_log("Cleaned up file (save failed): " . $filePath);
                                    }
                                }
                            }
                        } else {
                            $response['message'] = "Failed to process CSV file: " . ($result['message'] ?? 'Unknown error');
                            error_log("Failed to process CSV: " . $response['message']);
                            
                            // Clean up file on processing failure
                            if (file_exists($filePath)) {
                                unlink($filePath);
                                error_log("Cleaned up file (processing failed): " . $filePath);
                            }
                        }
                        
                    }
                } else {
                    $response['message'] = "Failed to save uploaded file.";
                    error_log("Failed to move uploaded file");
                }
            } catch (Exception $e) {
                $response['message'] = "Error processing file: " . $e->getMessage();
                error_log("Exception during processing: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Check session for validation errors first
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                // If there are validation errors in session, use those instead of the generic message
                if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
                    error_log("Found " . count($_SESSION['validation_errors']) . " validation errors in session");
                    
                    // Format the validation errors for detailed display
                    $response['errors'] = [];
                    foreach ($_SESSION['validation_errors'] as $error) {
                        if (strpos($error, ' Suggestions: ') !== false) {
                            $parts = explode(' Suggestions: ', $error, 2);
                            $response['errors'][] = [
                                'message' => $parts[0],
                                'suggestions' => $parts[1]
                            ];
                        } else {
                            $response['errors'][] = ['message' => $error];
                        }
                    }
                    
                    // Override the generic message with detailed count
                    $response['message'] = "Data validation errors found: " . implode('; ', $_SESSION['validation_errors']) . ". Please correct these issues and upload again.";
                    
                    // Clear the session validation errors
                    unset($_SESSION['validation_errors']);
                    
                } else {
                    // Error response for validation errors from exception message
                    if (strpos($e->getMessage(), 'Data validation errors') !== false ||
                        strpos($e->getMessage(), 'No valid data') !== false ||
                        strpos($e->getMessage(), 'CSV parsing error') !== false) {
                        
                        // Parse validation errors for detailed display
                        $errorMessage = $e->getMessage();
                        $errorMessage = str_replace("Error processing file: ", "", $errorMessage);
                        $errorMessage = str_replace("Data validation errors found: ", "", $errorMessage);
                        $errorMessage = str_replace(". Please correct these issues and upload again.", "", $errorMessage);
                        
                        if (strpos($e->getMessage(), 'No valid data') !== false) {
                            $response['errors'] = [
                                ['message' => 'No valid data found in CSV file - All rows failed validation'],
                                ['message' => 'Common causes: Invalid file format, corrupt data, or unsupported CSV structure']
                            ];
                        } else {
                            // Split validation errors by semicolon
                            $errorList = explode(';', $errorMessage);
                            $response['errors'] = [];
                            
                            foreach ($errorList as $error) {
                                $error = trim($error);
                                if (!empty($error)) {
                                    if (strpos($error, ' Suggestions: ') !== false) {
                                        $parts = explode(' Suggestions: ', $error, 2);
                                        $response['errors'][] = [
                                            'message' => $parts[0],
                                            'suggestions' => $parts[1]
                                        ];
                                    } else {
                                        $response['errors'][] = ['message' => $error];
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Clean up on error
                if (isset($filePath) && file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
}

error_log("Final response: " . json_encode($response));
error_log("Sample Upload End");

// Return response
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $_SESSION['sample_upload_message'] = $response;
    header('Location: upload_sample_data.php');
    exit;
}

// Function to save sample data
function saveSampleData($conn, $data, $fileName, $fileSize, $reportType, $metadata) {
    error_log("Save Sample Data Start");
    error_log("Data count: " . count($data));
    error_log("File name: " . $fileName);
    error_log("Report type: " . $reportType);
    
    if (empty($data)) {
        error_log("No data provided to saveSampleData");
        return false;
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        error_log("Transaction started");
        
        $userId = $_SESSION['user_id'];
        $startDate = $metadata['start_date'] ?? date('Y-m-d');
        $endDate = $metadata['end_date'] ?? date('Y-m-d');
        $accountName = $metadata['account_name'] ?? 'Sample Account';
        $propertyName = $metadata['property_name'] ?? 'Sample Property';
        
        error_log("Using UserID: $userId, Dates: $startDate to $endDate");
        
        // Insert record in CSV_UPLOAD with IsSampleData = 1
        $stmt = $conn->prepare("INSERT INTO CSV_UPLOAD 
            (UserID, FileName, FileSize, IsValidated, ReportType, 
             DataDateStart, DataDateEnd, AccountName, PropertyName, IsSampleData) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 1)");
            
        if (!$stmt) {
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
            throw new Exception("Error creating CSV_UPLOAD record: " . $stmt->error);
        }
        
        $uploadId = $conn->insert_id;
        error_log("Created CSV_UPLOAD record with ID: $uploadId");
        
        // Process data points
        $processedRows = 0;
        foreach ($data as $rowIndex => $row) {
            error_log("Processing row $rowIndex: " . json_encode($row));
            
            $sourceType = $row['traffic_source'] ?? 'Unknown Source';
            
            // Use SourceName (the correct column name)
            $stmt = $conn->prepare("SELECT SourceTypeID FROM SOURCE_TYPE WHERE SourceName = ?");
            $stmt->bind_param("s", $sourceType);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($sourceRow = $result->fetch_assoc()) {
                $sourceTypeId = $sourceRow['SourceTypeID'];
                error_log("Found existing source type ID: $sourceTypeId for $sourceType");
            } else {
                // Create new source type using SourceName
                $stmt = $conn->prepare("INSERT INTO SOURCE_TYPE (SourceName) VALUES (?)");
                $stmt->bind_param("s", $sourceType);
                $stmt->execute();
                $sourceTypeId = $conn->insert_id;
                error_log("Created new source type ID: $sourceTypeId for $sourceType");
            }
            
            // Insert data points using the functions from functions.php
            $dataPointsInserted = 0;
            
            if (isset($row['visits']) && is_numeric($row['visits']) && $row['visits'] > 0) {
                if (insertDataPoint($conn, $uploadId, $sourceTypeId, 'Sessions', $row['visits'], $startDate)) {
                    $dataPointsInserted++;
                    error_log("Inserted Sessions: " . $row['visits']);
                }
            }
            
            if (isset($row['engaged_sessions']) && is_numeric($row['engaged_sessions']) && $row['engaged_sessions'] > 0) {
                if (insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engaged sessions', $row['engaged_sessions'], $startDate)) {
                    $dataPointsInserted++;
                    error_log("Inserted Engaged sessions: " . $row['engaged_sessions']);
                }
            }
            
            if (isset($row['users']) && is_numeric($row['users']) && $row['users'] > 0) {
                if (insertDataPoint($conn, $uploadId, $sourceTypeId, 'Users', $row['users'], $startDate)) {
                    $dataPointsInserted++;
                    error_log("Inserted Users: " . $row['users']);
                }
            }
            
            if (isset($row['bounce_rate']) && is_numeric($row['bounce_rate'])) {
                $bounceRate = $row['bounce_rate'];
                // Convert percentage to decimal if needed
                if (strpos($bounceRate, '%') !== false) {
                    $bounceRate = floatval(str_replace('%', '', $bounceRate)) / 100;
                }
                if (insertDataPoint($conn, $uploadId, $sourceTypeId, 'Bounce Rate', $bounceRate, $startDate)) {
                    $dataPointsInserted++;
                    error_log("Inserted Bounce Rate: " . $bounceRate);
                }
            }
            
            if (isset($row['avg_session_duration']) && is_numeric($row['avg_session_duration'])) {
                if (insertDataPoint($conn, $uploadId, $sourceTypeId, 'Avg. Session Duration', $row['avg_session_duration'], $startDate)) {
                    $dataPointsInserted++;
                    error_log("Inserted Avg Session Duration: " . $row['avg_session_duration']);
                }
            }
            
            error_log("Row $rowIndex: Inserted $dataPointsInserted data points");
            $processedRows++;
        }
        
        error_log("Processed $processedRows rows total");
        
        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");
        
        // Verify the upload was saved
        $verifyStmt = $conn->prepare("SELECT COUNT(*) as count FROM CSV_UPLOAD WHERE UploadID = ? AND IsSampleData = 1");
        $verifyStmt->bind_param("i", $uploadId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verifyRow = $verifyResult->fetch_assoc();
        
        error_log("Verification: Found " . $verifyRow['count'] . " CSV_UPLOAD records with UploadID $uploadId");
        
        // Verify data points were saved
        $dataPointsStmt = $conn->prepare("SELECT COUNT(*) as count FROM PROCESSED_DATA_POINT WHERE UploadID = ?");
        $dataPointsStmt->bind_param("i", $uploadId);
        $dataPointsStmt->execute();
        $dataPointsResult = $dataPointsStmt->get_result();
        $dataPointsRow = $dataPointsResult->fetch_assoc();
        
        error_log("Verification: Found " . $dataPointsRow['count'] . " data points for UploadID $uploadId");
        
        error_log("Save Sample Data Success");
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error saving sample data: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        error_log("Save Sample Data Failed");
        return false;
    }
}
?>