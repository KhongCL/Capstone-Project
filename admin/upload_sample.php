<?php
// filepath: c:\xampp\htdocs\Capstone-Project\admin\upload_sample.php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
require_once '../functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

// Initialize response array for AJAX or standard requests
$response = [
    'success' => false,
    'message' => ''
];

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enhanced file validation
    if (!isset($_FILES['sampleCsv']) || $_FILES['sampleCsv']['error'] !== UPLOAD_ERR_OK) {
        // Use the function from functions.php instead of redefining it here
        $response['message'] = "Error uploading file: " . getUploadErrorMessage($_FILES['sampleCsv']['error'] ?? UPLOAD_ERR_NO_FILE);
    } else if ($_FILES['sampleCsv']['size'] > 5 * 1024 * 1024) { // 5MB limit
        $response['message'] = "File size exceeds the 5MB limit.";
    } else if (pathinfo($_FILES['sampleCsv']['name'], PATHINFO_EXTENSION) !== 'csv') {
        $response['message'] = "Only CSV files are allowed.";
    } else {
        // Additional MIME type checking
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['sampleCsv']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])) {
            $response['message'] = "The uploaded file does not appear to be a valid CSV file. Detected type: $mime";
        } else {
            // Get report type
            $reportType = isset($_POST['reportType']) ? trim($_POST['reportType']) : '';
            
            // If new report type is selected, use the provided name
            if ($reportType === 'new' && isset($_POST['newReportType']) && !empty($_POST['newReportType'])) {
                $reportType = trim($_POST['newReportType']);
            }
            
            if (empty($reportType)) {
                $response['message'] = "Please select or provide a report type.";
            } else {
                // Process the upload
                try {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = __DIR__ . '/../uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Save file with unique name
                    $fileName = uniqid() . '_' . basename($_FILES['sampleCsv']['name']);
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['sampleCsv']['tmp_name'], $filePath)) {
                        // Create a CSV processor instance
                        $processor = new CsvProcessor();
                        
                        // Extract metadata from the file
                        $metadata = $processor->extractGa4Metadata($filePath);
                        
                        // Check if file is empty
                        if (filesize($filePath) === 0) {
                            $response['message'] = "The uploaded CSV file is empty.";
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        } else {
                            // Process the file with enhanced error handling
                            $result = $processor->processFile($filePath);
                            
                            if ($result['status'] === 'success' || $result['status'] === 'needs_mapping') {
                                // Transform data using mapping if available
                                $transformedData = [];
                                if ($result['status'] === 'success') {
                                    $transformedData = $processor->transformData($filePath, $result['mapping'], $result['format']);
                                } else {
                                    // Use suggestions as mapping
                                    $mapping = [];
                                    foreach ($result['suggestions'] as $column => $suggestion) {
                                        if ($suggestion['confidence'] > 70) {
                                            $mapping[$column] = $suggestion['suggested_mapping'];
                                        }
                                    }
                                    $transformedData = $processor->transformData($filePath, $mapping);
                                }
                                
                                if (empty($transformedData)) {
                                    $response['message'] = "No valid data rows found in the uploaded file after validation.";
                                } else {
                                    // Save as sample data (IsSampleData = 1)
                                    $saved = saveSampleData($conn, $transformedData, $fileName, $_FILES['sampleCsv']['size'], $reportType, $metadata);
                                    
                                    if ($saved) {
                                        $response['success'] = true;
                                        $response['message'] = "Sample data uploaded and processed successfully.";
                                    } else {
                                        $response['message'] = "Failed to save sample data to database.";
                                    }
                                }
                            } else {
                                $response['message'] = "Failed to process CSV file: " . ($result['error'] ?? 'Unknown error');
                            }
                            
                            // Clean up the file
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    } else {
                        $response['message'] = "Failed to save uploaded file.";
                    }
                } catch (Exception $e) {
                    $response['message'] = "Error processing file: " . $e->getMessage();
                    
                    // Clean up on error
                    if (isset($filePath) && file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }
    }
}

// Return response for AJAX requests, or redirect for standard form submit
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Store message in session for display after redirect
    $_SESSION['sample_upload_message'] = $response;
    
    // IMPORTANT CHANGE: Redirect to upload_sample_data.php instead of admin_mappings.php
    header('Location: upload_sample_data.php');
    exit;
}

// Function to save sample data
function saveSampleData($conn, $data, $fileName, $fileSize, $reportType, $metadata) {
    if (empty($data)) {
        return false;
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        $userId = $_SESSION['user_id'];
        $startDate = $metadata['start_date'] ?? date('Y-m-d');
        $endDate = $metadata['end_date'] ?? date('Y-m-d');
        $accountName = $metadata['account_name'] ?? '';
        $propertyName = $metadata['property_name'] ?? '';
        
        // Insert record in CSV_UPLOAD with IsSampleData = 1
        $stmt = $conn->prepare("INSERT INTO CSV_UPLOAD 
            (UserID, FileName, FileSize, IsValidated, ReportType, 
             DataDateStart, DataDateEnd, AccountName, PropertyName, IsSampleData) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 1)");
            
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
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
        
        // Log the upload ID for debugging
        error_log("Created sample data upload with ID: $uploadId");
        
        // Process data points
        foreach ($data as $row) {
            $sourceType = $row['traffic_source'] ?? 'Unknown';
            $sourceTypeId = getSourceTypeId($conn, $sourceType);
            
            if (isset($row['visits']) && $row['visits'] > 0) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Sessions', $row['visits'], $startDate);
            }
            
            if (isset($row['engaged_sessions']) && $row['engaged_sessions'] > 0) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Engaged sessions', $row['engaged_sessions'], $startDate);
            }
            
            if (isset($row['bounce_rate'])) {
                // Convert percentage to decimal if needed
                $bounceRate = $row['bounce_rate'];
                if (strpos($bounceRate, '%') !== false) {
                    $bounceRate = floatval(str_replace('%', '', $bounceRate)) / 100;
                }
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Bounce Rate', $bounceRate, $startDate);
            }
            
            if (isset($row['avg_session_duration'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Avg. Session Duration', $row['avg_session_duration'], $startDate);
            }
            
            // Add more data points as needed
            if (isset($row['events_per_session'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Events per session', $row['events_per_session'], $startDate);
            }
            
            if (isset($row['event_count'])) {
                insertDataPoint($conn, $uploadId, $sourceTypeId, 'Event count', $row['event_count'], $startDate);
            }
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error saving sample data: " . $e->getMessage());
        return false;
    }
}