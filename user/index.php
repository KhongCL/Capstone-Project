<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set page variables for header
$title = "Dashboard Home";
$active_page = "home";

// Enhanced debugging for sample data
error_log("=== INDEX.PHP SAMPLE DATA DEBUG ===");
error_log("GET parameters: " . print_r($_GET, true));
error_log("Current session state:");
error_log("- using_sample_data: " . (isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'));
error_log("- sample_upload_id: " . ($_SESSION['sample_upload_id'] ?? 'not set'));
error_log("- latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'not set'));

error_log("=== INDEX.PHP SESSION DEBUG ===");
error_log("Current session ID: " . session_id());
error_log("Session latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'not set'));
error_log("Session using_sample_data: " . (isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'));
error_log("All session vars: " . print_r($_SESSION, true));
error_log("=== END SESSION DEBUG ===");

// Handle successful upload redirect
if (isset($_GET['upload_success']) && $_GET['upload_success'] == '1') {
    error_log("Upload success redirect detected - ensuring sample data is cleared");
    
    // Force clear any remaining sample data session variables IMMEDIATELY
    if (isset($_SESSION['using_sample_data'])) {
        error_log("CRITICAL: Clearing remaining sample data session after successful upload");
        unset($_SESSION['using_sample_data']);
        unset($_SESSION['sample_upload_id']);
        
        // Clear cached data
        unset($_SESSION['cached_metrics']);
        unset($_SESSION['cached_traffic_sources']);
        unset($_SESSION['pages_data_quality']);
    }
    
    // CRITICAL: Force session write to ensure changes are saved
    session_write_close();
    session_start();
    
    // Set success message
    $uploadMessage = [
        'type' => 'success',
        'message' => 'CSV data successfully uploaded and imported! You can now view your analytics.'
    ];
    
    // IMMEDIATE redirect to overview page (no delay)
    echo "<script>
        // Hide any sample data UI immediately
        document.addEventListener('DOMContentLoaded', function() {
            const sampleDataStatus = document.querySelector('.sample-data-status');
            if (sampleDataStatus) {
                sampleDataStatus.style.display = 'none';
            }
        });
        
        // Redirect immediately
        window.location.href = 'overview.php?uploaded=1';
    </script>";
    exit(); // Important: stop PHP execution here
}

// Handle failed mapping redirect
if (isset($_GET['mapping_failed']) && $_GET['mapping_failed'] == '1') {
    // Message will be in session from map_columns.php
    error_log("Mapping failed redirect detected");
}

// Handle failed upload redirect  
if (isset($_GET['upload_failed']) && $_GET['upload_failed'] == '1') {
    // Message will be in session from map_columns.php
    error_log("Upload failed redirect detected");
}

// Handle failed processing redirect
if (isset($_GET['processing_failed']) && $_GET['processing_failed'] == '1') {
    // Message will be in session from map_columns.php
    error_log("Processing failed redirect detected");
}

// Check for upload message in session (from map_columns.php redirects)
if (isset($_SESSION['upload_message'])) {
    $uploadMessage = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']); // Clear it so it doesn't persist
    error_log("Found upload message in session: " . json_encode($uploadMessage));
}

// Handle sample data loading
if (isset($_GET['load_sample']) && $_GET['load_sample'] == '1') {
    error_log("Loading sample data requested");
    
    // Ensure session is properly started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get the most recent sample data upload
    $stmt = $conn->prepare("SELECT UploadID, FileName, AccountName, PropertyName FROM csv_upload WHERE IsSampleData = 1 ORDER BY UploadDate DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $sampleUploadId = $row['UploadID'];
        error_log("Found sample data: UploadID = $sampleUploadId, FileName = " . $row['FileName']);
        
        // CRITICAL: Set session variables
        $_SESSION['using_sample_data'] = true;
        $_SESSION['sample_upload_id'] = $sampleUploadId;
        $_SESSION['latest_upload_id'] = $sampleUploadId; // For compatibility
        
        // Clear any cached data
        unset($_SESSION['cached_metrics']);
        unset($_SESSION['cached_traffic_sources']);
        unset($_SESSION['pages_data_quality']);
        
        // CRITICAL: Force session save
        session_write_close();
        
        // Restart session for continued use
        session_start();
        
        error_log("Sample data session variables set:");
        error_log("- using_sample_data: " . ($_SESSION['using_sample_data'] ? 'true' : 'false'));
        error_log("- sample_upload_id: " . ($_SESSION['sample_upload_id'] ?? 'not set'));
        error_log("- latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'not set'));
        
        $uploadMessage = [
            'type' => 'success',
            'message' => 'Sample data loaded successfully! Explore the data preview above, then visit the dashboard pages to see the analytics.'
        ];
    } else {
        error_log("No sample data found in database");
        $uploadMessage = [
            'type' => 'error',
            'message' => 'No sample data available. Please contact the administrator.'
        ];
    }
}

// Check if upload was just completed and clear sample data UI
if (isset($_SESSION['upload_just_completed'])) {
    error_log("Upload just completed - clearing sample data session");
    unset($_SESSION['upload_just_completed']);
    
    // Force clear sample data to ensure UI updates
    unset($_SESSION['using_sample_data']);
    unset($_SESSION['sample_upload_id']);
    
    // Clear cached data
    unset($_SESSION['cached_metrics']);
    unset($_SESSION['cached_traffic_sources']);
    unset($_SESSION['pages_data_quality']);
}

// Handle clearing sample data
if (isset($_GET['clear_sample']) && $_GET['clear_sample'] == '1') {
    error_log("Clearing sample data requested");
    
    unset($_SESSION['using_sample_data']);
    unset($_SESSION['sample_upload_id']);

    // Clear mapping-related session data
    unset($_SESSION['uploaded_csv']);
    unset($_SESSION['mapping_result']);
    unset($_SESSION['csv_metadata']);
    
    // Get user's most recent upload
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT UploadID FROM csv_upload WHERE UserID = ? AND (IsSampleData = 0 OR IsSampleData IS NULL) ORDER BY UploadDate DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['latest_upload_id'] = $row['UploadID'];
        error_log("Restored user upload ID: " . $row['UploadID']);
    } else {
        unset($_SESSION['latest_upload_id']);
        error_log("No user uploads found, cleared latest_upload_id");
    }
    
    // Clear cached data
    unset($_SESSION['cached_metrics']);
    unset($_SESSION['cached_traffic_sources']);
    unset($_SESSION['pages_data_quality']);
    
    $uploadMessage = [
        'type' => 'info',
        'message' => 'Sample data cleared. You\'re now viewing your own data.'
    ];
    
    // Redirect back to overview
    header('Location: overview.php');
    exit();
}

// Check current sample data status for display
$currentSampleInfo = null;
if (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data'] === true) {
    $sampleUploadId = $_SESSION['sample_upload_id'] ?? null;
    if ($sampleUploadId) {
        error_log("Checking current sample info for UploadID: $sampleUploadId");
        $stmt = $conn->prepare("SELECT FileName, AccountName, PropertyName, DataDateStart, DataDateEnd FROM csv_upload WHERE UploadID = ? AND IsSampleData = 1");
        $stmt->bind_param("i", $sampleUploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentSampleInfo = $row;
            error_log("Current sample info retrieved: " . json_encode($currentSampleInfo));
        } else {
            error_log("WARNING: Sample upload ID $sampleUploadId not found, clearing sample session");
            // Clear invalid sample data session
            unset($_SESSION['using_sample_data']);
            unset($_SESSION['sample_upload_id']);
        }
    }
} else {
    error_log("Not currently viewing sample data");
}

$sampleDataPreview = null;
$csvHeaders = [];
if ($currentSampleInfo) {
    // Instead of getting processed data, read the original CSV file
    $sampleUploadId = $_SESSION['sample_upload_id'];
    
    // Get the file path from the database
    $stmt = $conn->prepare("SELECT FileName FROM csv_upload WHERE UploadID = ? AND IsSampleData = 1");
    $stmt->bind_param("i", $sampleUploadId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $fileName = $row['FileName'];
        $filePath = __DIR__ . '/../uploads/' . $fileName;
        
        // Check if file exists and read it
        if (file_exists($filePath)) {
            error_log("Reading raw CSV file: $filePath");
            
            $file = fopen($filePath, 'r');
            if ($file) {
                // Read the header row
                $csvHeaders = fgetcsv($file);
                
                // Initialize the array to prevent null issues
                $sampleDataPreview = [];
                
                // Read up to 10 data rows for preview
                $rowCount = 0;
                while (($row = fgetcsv($file)) !== false && $rowCount < 10) {
                    $sampleDataPreview[] = $row;
                    $rowCount++;
                }
                fclose($file);
                
                error_log("CSV Headers: " . json_encode($csvHeaders ?? []));
                error_log("Sample rows count: " . count($sampleDataPreview ?? []));
            } else {
                error_log("Failed to open CSV file: $filePath");
            }
        } else {
            error_log("CSV file not found: $filePath");
        }
    }
}

// Handle CSV upload (fallback for non-JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $uploadMessage = handleCsvUpload($conn, $_FILES['csvFile']);
}

error_log("=== END INDEX.PHP DEBUG ===");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../scripts.js"></script>
    <style>
        /* ==================== UPLOAD BUTTON ANIMATIONS ==================== */
        #uploadBtn {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        /* Show upload button when file is selected */
        #uploadBtn.show {
            display: inline-block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Enhanced button container styling */
        .button-container {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        /* ==================== SAMPLE DATA STATUS STYLES ==================== */
        .sample-data-status {
            background-color: var(--primary-color);
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(41, 128, 185, 0.4);
        }
        
        .sample-data-status .status-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .sample-data-status i {
            font-size: 1.2em;
            margin-right: 10px;
            color: #fff;
        }
        
        .sample-data-status .btn {
            padding: 8px 16px;
            font-size: 0.9em;
            border: 1px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .sample-data-status .btn:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

        .sample-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ==================== SAMPLE DATA DETAILS STYLES ==================== */
        .sample-data-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9em;
            color: #fff;
        }

        .sample-data-details .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .sample-data-details .detail-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.15); 
            padding: 8px 12px;
            border-radius: 6px;
            overflow: hidden; 
        }

        .sample-data-details .detail-label {
            font-weight: 600;
            margin-right: 8px;
            color: rgba(255, 255, 255, 0.9);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sample-data-details .detail-item span:last-child {
            color: #fff;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        /* ==================== SAMPLE DATA PREVIEW STYLES ==================== */
        .sample-data-preview {
            margin-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 15px;
        }

        .preview-header {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .preview-header:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .preview-count {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9em;
        }

        .preview-toggle {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .preview-toggle.rotated {
            transform: rotate(180deg);
        }

        .preview-content {
            margin-top: 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 20px;
            color: #333;
        }

        .preview-controls {
            margin-bottom: 15px;
        }

        .preview-controls p {
            margin: 0;
            color: #666;
            font-style: italic;
        }

        /* ==================== PREVIEW TABLE STYLES ==================== */
        .preview-table-container {
            max-height: 400px;
            overflow: auto; /* Both vertical and horizontal scrolling */
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em; /* Optimized for multiple columns */
            min-width: 600px; /* Ensure minimum width for scrolling */
        }

        .preview-table th {
            background: #f8f9fa;
            padding: 8px 6px; /* Optimized for more columns */
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap; /* Prevent header text wrapping */
            min-width: 80px; /* Minimum column width */
        }

        .preview-table td {
            padding: 8px 6px; /* Consistent with headers */
            border-bottom: 1px solid #f1f3f4;
            white-space: nowrap; /* Prevent text wrapping */
            max-width: 150px; /* Maximum column width */
            overflow: hidden;
            text-overflow: ellipsis; /* Show ... for long content */
        }

        .preview-table tbody tr:hover {
            background: #f8f9fa;
        }

        .preview-table tbody tr:nth-child(even) {
            background: #fdfdfd;
        }

        .preview-table tbody tr:nth-child(even):hover {
            background: #f8f9fa;
        }

        /* Tooltip for truncated content */
        .preview-table td:hover {
            overflow: visible;
            white-space: normal;
            background: #f8f9fa;
            position: relative;
            z-index: 100;
        }

        /* ==================== BUTTON STYLES ==================== */
        .btn-primary {
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }

        .btn-success:hover {
            background: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
        }

        /* ==================== PREVIEW ACTIONS STYLES ==================== */
        .preview-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .preview-actions .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid transparent; /* Add border for consistency */
        }

        /* Specific button color overrides for preview actions */
        .preview-actions .btn-success {
            background: #28a745 !important;
            color: #fff !important;
            border-color: #28a745 !important;
        }

        .preview-actions .btn-success:hover {
            background: #218838 !important;
            border-color: #1e7e34 !important;
            color: #fff !important;
        }

        .preview-actions .btn-primary {
            background: #007bff !important;
            color: #fff !important;
            border-color: #007bff !important;
        }

        .preview-actions .btn-primary:hover {
            background: #0056b3 !important;
            border-color: #004085 !important;
            color: #fff !important;
        }

        .preview-actions .btn-secondary {
            background: #6c757d !important;
            color: #fff !important;
            border-color: #6c757d !important;
        }

        .preview-actions .btn-secondary:hover {
            background: #545b62 !important;
            border-color: #4e555b !important;
            color: #fff !important;
        }

        /* ==================== ERROR CONTAINER AND BASIC ERROR STYLES ==================== */
        .error-container {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
            max-height: 400px; /* Limit height */
            overflow-y: auto; /* Make scrollable */
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 300px; /* Limit the list height */
            overflow-y: auto; /* Make the list scrollable */
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

        /* ==================== ERROR MESSAGE AND SUGGESTIONS STYLING ==================== */
        .error-message {
            font-weight: 500;
            color: #721c24;
            margin-bottom: 8px;
            display: block;
            width: 100%;
            line-height: 1.4;
        }

        /* Enhanced suggestions styling with proper yellow highlighting for ALL sources */
        .error-suggestions,
        .error-item strong + span {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%) !important;
            border: 1px solid #ffeaa7 !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin-top: 10px !important;
            font-size: 0.9em !important;
            color: #856404 !important;
            box-shadow: 0 2px 4px rgba(255, 234, 167, 0.3) !important;
            border-left: 4px solid #ffc107 !important;
            display: block !important;
        }

        /* Target the specific pattern from map_columns.php suggestions */
        .error-item br + strong {
            margin-top: 10px;
            display: block;
            color: #b8860b !important;
            font-weight: 600 !important;
        }

        /* Style the text after "💡 Suggestions:" from map_columns.php */
        .error-item strong:contains("💡 Suggestions:") + * {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%) !important;
            border: 1px solid #ffeaa7 !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin-top: 5px !important;
            font-size: 0.9em !important;
            color: #856404 !important;
            box-shadow: 0 2px 4px rgba(255, 234, 167, 0.3) !important;
            border-left: 4px solid #ffc107 !important;
            display: block !important;
        }

        .error-suggestions strong {
            color: #b8860b !important;
            font-weight: 600 !important;
        }

        .suggestions-text {
            color: #856404 !important;
            font-weight: 500 !important;
            display: inline !important;
        }

        /* Styling for upload_progress.js generated errors */
        .error-suggestions .suggestions-text {
            color: #856404 !important;
            background: transparent !important;
        }

        /* ==================== VALIDATION HELP SECTION ==================== */
        .validation-help {
            background: #e8f5e8;
            border: 2px solid #68d391;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            max-height: 500px; /* Limit height */
            overflow-y: auto; /* Make scrollable */
        }

        .validation-tips {
            max-height: 350px; /* Limit the tips section */
            overflow-y: auto; /* Make scrollable */
            padding-right: 10px; /* Add space for scrollbar */
        }

        /* ==================== MAP_COLUMNS.PHP ERROR COMPATIBILITY ==================== */
        
        /* Style for validation errors list from map_columns.php */
        .validation-errors-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
            max-height: 300px; /* Limit the list height */
            overflow-y: auto; /* Make the list scrollable */
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

        /* Error footer styling */
        .error-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
            color: #721c24;
            text-align: center;
        }

        /* ==================== ENHANCED ERROR HEADERS AND STRUCTURE ==================== */
        
        /* Enhanced error container for mapping validation failures */
        .error-container h4 {
            color: #721c24;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.2em;
            font-weight: 600;
        }

        .error-container h4 i {
            color: #dc3545;
            font-size: 1.1em;
        }

        /* Enhanced error summary styling */
        .error-summary {
            font-weight: 600;
            color: #721c24;
            margin-bottom: 15px;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-summary i {
            color: #dc3545;
            font-size: 1.1em;
        }

        /* ==================== FILE INFO BOX STYLING - FIXED FOR MAP_COLUMNS.PHP ==================== */
        
        /* Enhanced file info styling within error container AND standalone */
        .error-container div[style*="margin: 15px 0"] {
            margin: 15px 0 !important;
            padding: 15px !important;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.05) 0%, rgba(220, 53, 69, 0.1) 100%) !important;
            border: 1px solid rgba(220, 53, 69, 0.2) !important;
            border-radius: 8px !important;
            font-size: 0.9em !important;
            border-left: 4px solid #dc3545 !important;
            color: #721c24 !important;
        }

        /* Target the specific inline style from map_columns.php */
        .error-container div[style*="background: rgba(0,0,0,0.05)"] {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.05) 0%, rgba(220, 53, 69, 0.1) 100%) !important;
            border: 1px solid rgba(220, 53, 69, 0.2) !important;
            border-left: 4px solid #dc3545 !important;
            color: #721c24 !important;
        }

        .error-container div[style*="margin: 15px 0"] strong {
            color: #721c24 !important;
            font-weight: 600 !important;
        }

        /* Individual file info lines */
        .file-info-line {
            margin: 8px 0;
            padding: 6px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .file-info-line:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .file-info-line strong {
            display: inline-block;
            min-width: 120px;
            font-weight: 600;
        }

        /* ==================== VALIDATION HELP SECTION STYLING ==================== */
        
        /* Help section specific styling */
        .validation-help h4 {
            color: #2d6a4f;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.2em;
            font-weight: 600;
        }

        .validation-help h4 i {
            color: #28a745;
            font-size: 1.1em;
        }

        .validation-help h5 {
            color: #2d6a4f;
            margin: 15px 0 10px 0;
            font-size: 1em;
            border-bottom: 1px solid rgba(45, 106, 79, 0.2);
            padding-bottom: 5px;
            font-weight: 600;
        }

        .validation-help ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .validation-help li {
            margin: 8px 0;
            color: #333;
            line-height: 1.5;
        }

        .validation-help li strong {
            color: #2d6a4f;
        }

        /* ==================== UPLOAD PROGRESS.JS ERROR COMPATIBILITY ==================== */

        .upload-section .error-item .error-suggestions {
            /* Enhanced styling for suggestions from upload_progress.js */
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%) !important;
            border: 1px solid #ffeaa7 !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin-top: 10px !important;
            font-size: 0.9em !important;
            color: #856404 !important;
            box-shadow: 0 2px 4px rgba(255, 234, 167, 0.3) !important;
            border-left: 4px solid #ffc107 !important;
        }

        .upload-section .error-item .error-suggestions strong {
            color: #b8860b !important;
            font-weight: 600 !important;
        }

        .upload-section .error-item .error-suggestions .suggestions-text {
            color: #856404 !important;
            font-weight: 500 !important;
        }

        /* ==================== CUSTOM SCROLLBAR STYLES ==================== */
        
        /* Custom scrollbar styles for better appearance */
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

        /* ==================== RESPONSIVE DESIGN ==================== */
        
        /* Responsive adjustments for sample data and error messages */
        @media (max-width: 768px) {
            .status-content {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .sample-actions {
                justify-content: center;
            }

            .preview-actions {
                grid-template-columns: 1fr;
            }
            
            .preview-table-container {
                max-height: 300px;
            }

            .error-container {
                max-height: 300px; /* Smaller on mobile */
                padding: 15px;
            }
            
            .error-list,
            .validation-errors-list {
                max-height: 200px; /* Smaller list on mobile */
            }
            
            .validation-help {
                max-height: 350px; /* Smaller on mobile */
                padding: 15px;
            }
            
            .validation-tips {
                max-height: 250px; /* Smaller tips on mobile */
            }

            .error-container h4,
            .validation-help h4 {
                font-size: 1.1em;
            }

            .file-info-line strong {
                min-width: 100px;
                display: block;
                margin-bottom: 4px;
            }
        }

        @media (max-width: 480px) {
            .error-container {
                max-height: 250px; /* Even smaller on very small screens */
                padding: 12px;
            }
            
            .error-list,
            .validation-errors-list {
                max-height: 150px;
            }
            
            .validation-help {
                max-height: 300px;
                padding: 12px;
            }
            
            .validation-tips {
                max-height: 200px;
            }

            .error-container h4,
            .validation-help h4 {
                font-size: 1em;
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'user_header.php'; ?>
        
        <main>
            <section class="welcome-section">
                <h2>Welcome to TrafAnalyz</h2>
                <p>Your one-stop solution for analyzing web traffic data. Upload your data and start exploring!</p>
            </section>

            <!-- Current Sample Data Status -->
            <?php if ($currentSampleInfo): ?>
                <div class="sample-data-status">
                    <div class="status-content">
                        <div>
                            <i class="fas fa-flask"></i>
                            <strong>Currently Viewing Sample Data</strong>
                        </div>
                        <div class="sample-actions">
                            <a href="download_sample.php" class="btn" title="Download the raw CSV file">
                                <i class="fas fa-download"></i> Download CSV
                            </a>
                            <a href="?clear_sample=1" class="btn">Switch to Your Data</a>
                            <a href="overview.php" class="btn">View Dashboard</a>
                        </div>
                    </div>
                    <div class="sample-data-details">
                        <strong>Sample Dataset Information:</strong>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Account:</span>
                                <span><?php echo htmlspecialchars($currentSampleInfo['AccountName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Property:</span>
                                <span><?php echo htmlspecialchars($currentSampleInfo['PropertyName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date Range:</span>
                                <span><?php echo $currentSampleInfo['DataDateStart'] . ' to ' . $currentSampleInfo['DataDateEnd']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">File:</span>
                                <span><?php echo htmlspecialchars($currentSampleInfo['FileName']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- NEW: Data Preview Section -->
                    <?php if (!empty($sampleDataPreview)): ?>
                    <div class="sample-data-preview">
                        <div class="preview-header" onclick="toggleDataPreview()">
                            <i class="fas fa-table"></i>
                            <strong>Raw CSV Data Preview</strong>
                            <span class="preview-count">(<?php echo count($sampleDataPreview) ?: 0; ?> rows shown)</span>
                            <i class="fas fa-chevron-down preview-toggle" id="previewToggle"></i>
                        </div>
                        <div class="preview-content" id="previewContent" style="display: none;">
                            <div class="preview-controls">
                                <p>This table shows the first 10 rows of your raw CSV file exactly as it appears. The data below has been processed and stored in the database for analysis.</p>
                            </div>
                            <div class="preview-table-container">
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <?php if (!empty($csvHeaders)): ?>
                                                <?php foreach ($csvHeaders as $header): ?>
                                                    <th><?php echo htmlspecialchars($header); ?></th>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <th>No Data Available</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($sampleDataPreview)): ?>
                                            <?php foreach ($sampleDataPreview as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $cell): ?>
                                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?php echo count($csvHeaders) ?: 1; ?>">No sample data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="preview-actions">
                                <a href="download_sample.php" class="btn btn-success">
                                    <i class="fas fa-download"></i> Download Sample CSV
                                </a>
                                <a href="overview.php" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i> View Overview Dashboard
                                </a>
                                <a href="traffic_sources.php" class="btn btn-secondary">
                                    <i class="fas fa-share-alt"></i> Traffic Sources Analysis
                                </a>
                                <a href="pages.php" class="btn btn-secondary">
                                    <i class="fas fa-file-alt"></i> Top Pages Analysis
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <script>
                // CRITICAL: Hide sample data UI immediately if upload was just completed
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('=== INDEX.PHP DOM CONTENT LOADED ===');
                    
                    const uploadBtn = document.getElementById('uploadBtn');
                    const csvFileInput = document.getElementById('csvFile');
                    const fileInfo = document.getElementById('fileInfo');
                    
                    console.log('Elements found:', {
                        uploadBtn: !!uploadBtn,
                        csvFileInput: !!csvFileInput,
                        fileInfo: !!fileInfo
                    });
                    
                    // CRITICAL: Ensure upload button is in correct initial state
                    if (uploadBtn) {
                        // FIXED: Clear all inline styles first
                        uploadBtn.style.cssText = '';
                        uploadBtn.classList.remove('show');
                        uploadBtn.disabled = false;
                        
                        // Set proper initial state
                        uploadBtn.style.display = 'none';
                        uploadBtn.style.opacity = '0';
                        uploadBtn.style.transform = 'translateY(10px)';
                        uploadBtn.style.transition = 'all 0.3s ease';
                        
                        console.log('Upload button reset to initial state');
                    }
                    
                    // CRITICAL: Ensure file input is clean
                    if (csvFileInput) {
                        csvFileInput.value = '';
                        console.log('File input cleared');
                    }
                    
                    // CRITICAL: Ensure file info is hidden
                    if (fileInfo) {
                        fileInfo.style.display = 'none';
                        console.log('File info hidden');
                    }
                    
                    // Check if we're on a post-upload page load and handle sample data UI
                    const urlParams = new URLSearchParams(window.location.search);
                    const uploadSuccess = urlParams.get('upload_success');
                    
                    if (uploadSuccess === '1') {
                        // Immediately hide sample data UI
                        const sampleDataStatus = document.querySelector('.sample-data-status');
                        if (sampleDataStatus) {
                            sampleDataStatus.style.display = 'none';
                            console.log('Sample data UI hidden immediately after upload success');
                        }
                    }
                    
                    console.log('=== INDEX.PHP DOM CONTENT LOADED COMPLETE ===');
                });
            </script>
            
            <section class="upload-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Upload Traffic Data</h2>
                    <a href="supported_formats.php" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i> Supported CSV Formats
                    </a>
                </div>
                
                <?php if (!empty($uploadMessage)): ?>
                    <?php 
                    // Normalize $uploadMessage to always be an array
                    if (is_string($uploadMessage)) {
                        $uploadMessage = ['type' => 'info', 'message' => $uploadMessage];
                    }
                    ?>
                    
                    <?php if ($uploadMessage['type'] === 'error' && isset($uploadMessage['show_detailed_errors']) && $uploadMessage['show_detailed_errors']): ?>
                        <!-- ENHANCED: Detailed validation errors display -->
                        <div class="error-container">
                            <h4><i class="fas fa-exclamation-triangle"></i> CSV File Validation Failed</h4>
                            <p><strong>Your file couldn't be processed due to data validation errors.</strong></p>
                            
                            <?php if (isset($_SESSION['failed_file_info'])): ?>
                                <?php $fileInfo = $_SESSION['failed_file_info']; ?>
                                <div style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 5px;">
                                    <strong>File:</strong> <?php echo htmlspecialchars($fileInfo['name']); ?> 
                                    (<?php echo number_format($fileInfo['size']); ?> bytes)<br>
                                    <strong>Mapping Status:</strong> Successfully mapped <?php echo $fileInfo['mapped_columns']; ?> of <?php echo $fileInfo['total_columns']; ?> columns<br>
                                    <strong>Issue:</strong> Data validation failed during processing
                                </div>
                                <?php unset($_SESSION['failed_file_info']); ?>
                            <?php endif; ?>
                            
                            <?php if (isset($uploadMessage['validation_errors'])): ?>
                                <?php 
                                $errors = $uploadMessage['validation_errors'];
                                $uniqueErrors = array_unique($errors);
                                $errorCount = count($errors);
                                $uniqueCount = count($uniqueErrors);
                                ?>
                                
                                <div style="margin: 15px 0;">
                                    <p><strong>Found <?php echo $errorCount; ?> validation issues<?php echo $uniqueCount != $errorCount ? " ({$uniqueCount} unique types)" : ""; ?>:</strong></p>
                                    
                                    <ul class="error-list">
                                        <?php foreach ($uniqueErrors as $error): ?>
                                            <?php
                                            // Parse error message and suggestions
                                            $parts = explode(' Suggestions: ', $error);
                                            $errorMessage = $parts[0];
                                            $suggestions = isset($parts[1]) ? $parts[1] : '';
                                            ?>
                                            <li class="error-item">
                                                <?php echo htmlspecialchars($errorMessage); ?>
                                                <?php if (!empty($suggestions)): ?>
                                                    <br><strong>💡 Suggestions:</strong> <?php echo htmlspecialchars($suggestions); ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
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
                        </div>
                    <?php else: ?>
                        <!-- Regular error message -->
                        <div class="message <?php echo $uploadMessage['type']; ?>">
                            <?php echo htmlspecialchars($uploadMessage['message']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <p>Upload your CSV file containing web traffic data. 
                    <i class="fas fa-info-circle tooltip-trigger" title="Expected format: GA4 export with columns for date, sessions, users, etc."></i>
                </p>
                <form action="" method="post" enctype="multipart/form-data" id="uploadForm" data-ajax-handler="upload_handler.php">
									<div class="file-input-container">
									    <input type="file" id="csvFile" name="csvFile" accept=".csv" required>
									    <label for="csvFile" class="btn">
									        <i class="fas fa-upload"></i>
									        Choose CSV File
									    </label>

											<div class="file-info" id="fileInfo" style="display: none;">
  									      <div class="file-details">
  									          <div class="file-detail-item">
  									              <i class="fas fa-file-csv"></i>
  									              <span class="file-name"></span>
  									          </div>
  									          <div class="file-detail-item">
  									              <i class="fas fa-database"></i>
  									              <span class="file-size"></span>
  									          </div>
  									      </div>
  									  </div>
									</div>


                    
                    <!-- Enhanced Progress Indicators -->
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-container">
                            <div class="progress-stage active" id="stage1">
                                <div class="stage-icon">📁</div>
                                <div class="stage-text">Uploading File</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="uploadBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="uploadPercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage2">
                                <div class="stage-icon">🔍</div>
                                <div class="stage-text">Validating Structure</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="validateBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="validatePercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage3">
                                <div class="stage-icon">⚙️</div>
                                <div class="stage-text">Processing Data</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="processBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="processPercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage4">
                                <div class="stage-icon">💾</div>
                                <div class="stage-text">Saving to Database</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="saveBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="savePercent">0%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overall-progress">
                            <div class="overall-bar">
                                <div class="overall-fill" id="overallFill" style="width: 0%"></div>
                            </div>
                            <div class="overall-text">
                                <span id="overallPercent">0%</span> Complete
                                <span id="currentTask">Ready to upload...</span>
                            </div>
                        </div>
                        
                        <div class="progress-details" id="progressDetails">
                            <div class="detail-item">
                                <span class="detail-label">File Size:</span>
                                <span class="detail-value" id="fileSizeDetail">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Upload Speed:</span>
                                <span class="detail-value" id="uploadSpeed">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time Remaining:</span>
                                <span class="detail-value" id="timeRemaining">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Rows Processed:</span>
                                <span class="detail-value" id="rowsProcessed">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" id="uploadBtn">Upload Data</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn" style="display: none;">Cancel Upload</button>
                </form>
                
                <?php if (!$currentSampleInfo): ?>
                <div class="sample-data">
                    <p>New to TrafAnalyz? Try with our sample data:</p>
                    <a href="?load_sample=1" class="btn btn-secondary">Load Sample Data</a>
                </div>
                <?php endif; ?>
            </section>
                
            <section class="dashboard-links">
                <h2>Dashboard Navigation</h2>
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <h3>Overview</h3>
                        <p>View key metrics and website traffic over time.</p>
                        <a href="overview.php" class="btn">Go to Overview</a>
                    </div>
                    <div class="dashboard-card">
                        <h3>Traffic Sources</h3>
                        <p>Analyze where your website traffic is coming from.</p>
                        <a href="traffic_sources.php" class="btn">Go to Traffic Sources</a>
                    </div>
                    <div class="dashboard-card">
                        <h3>Pages</h3>
                        <p>Discover your most visited webpages.</p>
                        <a href="pages.php" class="btn">Go to Pages</a>
                    </div>
                </div>
            </section>
        </main>

        <?php include 'user_footer.php'; ?>
    </div>

    <script src="upload_progress.js"></script>
    <script>
        // CRITICAL: Set global variables for upload_progress.js to use
        window.sessionHasExistingData = <?php 
            // Check if user has actual data (not just session variables)
            $hasData = false;
            if (isset($_SESSION['latest_upload_id'])) {
                $uploadId = $_SESSION['latest_upload_id'];
                // FIX: Use correct table name 'processed_data_point' instead of 'analytics_data'
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM processed_data_point WHERE UploadID = ?");
                $stmt->bind_param("i", $uploadId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $hasData = ($row['count'] > 0);
                error_log("GLOBAL VARS DEBUG: Checking upload ID $uploadId, found {$row['count']} records, hasData = " . ($hasData ? 'true' : 'false'));
            } else {
                error_log("GLOBAL VARS DEBUG: No latest_upload_id in session");
            }
            echo $hasData ? 'true' : 'false'; 
        ?>;
        window.sessionIsUsingSampleData = <?php 
            $isSample = (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data']);
            error_log("GLOBAL VARS DEBUG: using_sample_data = " . ($isSample ? 'true' : 'false'));
            echo $isSample ? 'true' : 'false'; 
        ?>;
        
        // NEW: Add debug logging for global variables
        console.log('=== GLOBAL VARIABLES SET ===');
        console.log('window.sessionHasExistingData:', window.sessionHasExistingData);
        console.log('window.sessionIsUsingSampleData:', window.sessionIsUsingSampleData);
        console.log('Current session latest_upload_id:', '<?php echo $_SESSION['latest_upload_id'] ?? 'not set'; ?>');
        console.log('Current session using_sample_data:', '<?php echo isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'; ?>');
        console.log('=============================');
        
        // File info display
        document.getElementById('csvFile').addEventListener('change', function() {
            const fileInfo = document.getElementById('fileInfo');
            const fileName = fileInfo.querySelector('.file-name');
            const fileSize = fileInfo.querySelector('.file-size');
            const uploadBtn = document.getElementById('uploadBtn');

            console.log('=== FILE INPUT CHANGE EVENT ===');
            console.log('Files selected:', this.files.length);
            console.log('File info element found:', !!fileInfo);
            console.log('Upload button found:', !!uploadBtn);
            console.log('Upload button current state:', {
                display: uploadBtn ? uploadBtn.style.display : 'not found',
                classList: uploadBtn ? Array.from(uploadBtn.classList) : 'not found'
            });

            if (this.files.length > 0) {
                const file = this.files[0];
                console.log('Selected file:', file.name, 'Size:', file.size);
                
                // Update file info display
                if (fileName && fileSize) {
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                }
                
                if (fileInfo) {
                    fileInfo.style.display = 'block';
                    console.log('File info displayed');
                }
                
                // FIXED: Ensure upload button is properly shown
                if (uploadBtn) {
                    // CRITICAL: Clear all previous styles and classes first
                    uploadBtn.style.cssText = '';
                    uploadBtn.classList.remove('show');
                    uploadBtn.disabled = false;
                    
                    // FIXED: Apply the correct styles immediately
                    uploadBtn.style.display = 'inline-block';
                    uploadBtn.style.opacity = '1';
                    uploadBtn.style.transform = 'translateY(0)';
                    uploadBtn.style.transition = 'all 0.3s ease';
                    
                    // Add the show class for consistency
                    uploadBtn.classList.add('show');
                    
                    console.log('Upload button shown with proper styles');
                    console.log('Final button state:', {
                        display: uploadBtn.style.display,
                        opacity: uploadBtn.style.opacity,
                        transform: uploadBtn.style.transform,
                        disabled: uploadBtn.disabled,
                        classList: Array.from(uploadBtn.classList)
                    });
                } else {
                    console.error('Upload button not found in DOM');
                }
                
                // DON'T clear error messages here - let them remain visible
                // Error messages will be cleared when upload actually starts
            } else {
                console.log('No files selected, hiding elements');
                
                // Hide upload button when no file is selected
                if (fileInfo) {
                    fileInfo.style.display = 'none';
                }
                
                if (uploadBtn) {
                    uploadBtn.classList.remove('show');
                    uploadBtn.style.display = 'none';
                    uploadBtn.style.opacity = '0';
                    uploadBtn.style.transform = 'translateY(10px)';
                    console.log('Upload button hidden');
                }
            }
            
            console.log('=== END FILE INPUT CHANGE EVENT ===');
        });

        window.addEventListener('beforeunload', function(e) {
            console.log('=== BEFOREUNLOAD EVENT TRIGGERED ===');
            
            const hasExistingData = window.sessionHasExistingData;
            const isUsingSampleData = window.sessionIsUsingSampleData;
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            console.log('beforeunload - hasExistingData:', hasExistingData);
            console.log('beforeunload - isUsingSampleData:', isUsingSampleData);
            console.log('beforeunload - hasErrorMessages:', hasErrorMessages);
            console.log('beforeunload - error elements found:', document.querySelectorAll('.error-container, .validation-help, .message.error').length);
            
            // Only show confirmation if there are error messages or existing data
            if (hasErrorMessages || hasExistingData) {
                console.log('Conditions met for showing beforeunload confirmation');
                
                // Browser will show its own message regardless
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?'; // Set a custom message
                
                console.log('beforeunload event prevented');
                return 'You have unsaved changes. Are you sure you want to leave?'; // For older browsers
            } else {
                console.log('No conditions met, allowing navigation');
            }
        });

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
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.classList.add('rotated');
            } else {
                content.style.display = 'none';
                toggle.classList.remove('rotated');
            }
        }

        // Global confirmation function for upload progress tracker
        function confirmDataReplacement() {
            // ENHANCED: Use the global variables set by PHP
            const hasExistingData = window.sessionHasExistingData;
            const isUsingSampleData = window.sessionIsUsingSampleData;
            
            // NEW: Check if there are error messages displayed that would be helpful to keep
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            // ENHANCED DEBUG LOGGING
            console.log('=== CONFIRMATION DEBUG ===');
            console.log('hasExistingData:', hasExistingData);
            console.log('isUsingSampleData:', isUsingSampleData);
            console.log('hasErrorMessages:', hasErrorMessages);
            console.log('Current URL:', window.location.href);
            console.log('Referrer:', document.referrer);
            
            // NEW: Check if this is a page refresh after successful upload
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('upload_success') === '1') {
                console.log('Page loaded after successful upload - sample data should be cleared');
                return true; // Proceed without confirmation on post-upload page loads
            }
            
            // CRITICAL FIX: Check for mapping page return but STILL show confirmation if there are error messages
            const isFromMappingPage = document.referrer && document.referrer.includes('map_columns.php');
            if (isFromMappingPage) {
                console.log('Detected return from mapping page');
                // FIXED: Even if from mapping page, we should ALWAYS confirm if there are error messages
                // Only bypass confirmation if there are NO error messages AND no existing data
                if (!hasErrorMessages && !hasExistingData) {
                    console.log('No error messages or existing data from mapping page - proceeding');
                    return true;
                }
                console.log('Has error messages or existing data from mapping page - showing confirmation');
                // Don't return here - fall through to show confirmation
            }
            
            console.log('Session latest_upload_id:', '<?php echo $_SESSION['latest_upload_id'] ?? 'not set'; ?>');
            console.log('Session using_sample_data:', '<?php echo isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'; ?>');
            console.log('========================');
            
            // CRITICAL CHANGE: Show confirmation if there's existing data OR error messages
            if (!hasExistingData && !hasErrorMessages) {
                console.log('No existing data or error messages - proceeding without confirmation');
                return true; // No existing data or helpful messages, proceed
            }
            
            let confirmMessage;
            
            // NEW: Special messaging for mapping page returns with error messages
            if (isFromMappingPage && hasErrorMessages) {
                confirmMessage = "⚠️ Upload Different File?\n\n" +
                            "You just came back from the column mapping page with validation errors displayed.\n\n" +
                            "The current error messages contain helpful suggestions for fixing your previous CSV file:\n" +
                            "• 💡 Data fix suggestions\n" +
                            "• 🔧 Quick fix guide\n" +
                            "• 📋 Detailed error explanations\n\n" +
                            "Uploading a new file will clear these helpful messages.\n\n" +
                            "Do you want to continue with the upload?";
            } else if (hasErrorMessages && !hasExistingData) {
                confirmMessage = "⚠️ Clear Error Messages?\n\n" +
                            "You have validation error messages displayed that contain helpful suggestions for fixing your CSV file:\n" +
                            "• 💡 Data fix suggestions\n" +
                            "• 🔧 Quick fix guide\n" +
                            "• 📋 Detailed error explanations\n\n" +
                            "Uploading a new file will clear these helpful messages.\n\n" +
                            "Do you want to continue with the upload?";
            } else if (hasErrorMessages && hasExistingData) {
                if (isUsingSampleData) {
                    confirmMessage = "⚠️ Upload New Data?\n\n" +
                                "You are currently viewing sample data AND have error messages with helpful suggestions displayed.\n\n" +
                                "Uploading a new file will:\n" +
                                "• Replace the sample data with your own data\n" +
                                "• Clear all current dashboard results\n" +
                                "• Reset all analytics and charts\n" +
                                "• Remove the helpful error messages and fix suggestions\n\n" +
                                "Do you want to continue with the upload?";
                } else {
                    confirmMessage = "⚠️ Replace Existing Data?\n\n" +
                                "You have uploaded data AND error messages with helpful suggestions displayed.\n\n" +
                                "Uploading a new file will:\n" +
                                "• Replace your current data completely\n" +
                                "• Clear all dashboard results and analytics\n" +
                                "• Remove all annotations and saved metrics\n" +
                                "• Clear the helpful error messages and fix suggestions\n\n" +
                                "This action cannot be undone. Do you want to continue?";
                }
            } else if (isUsingSampleData) {
                confirmMessage = "⚠️ Upload New Data?\n\n" +
                            "You are currently viewing sample data. Uploading a new file will:\n" +
                            "• Replace the sample data with your own data\n" +
                            "• Clear all current dashboard results\n" +
                            "• Reset all analytics and charts\n\n" +
                            "Do you want to continue with the upload?";
            } else {
                confirmMessage = "⚠️ Replace Existing Data?\n\n" +
                            "You already have uploaded data. Uploading a new file will:\n" +
                            "• Replace your current data completely\n" +
                            "• Clear all dashboard results and analytics\n" +
                            "• Remove all annotations and saved metrics\n" +
                            "• Reset all charts and comparisons\n\n" +
                            "This action cannot be undone. Do you want to continue?";
            }
            
            console.log('Showing confirmation dialog:', confirmMessage);
            const result = confirm(confirmMessage);
            console.log('Confirmation result:', result);
            return result;
        }

        // CRITICAL: Ensure the function is available globally
        window.confirmDataReplacement = confirmDataReplacement;

        // NEW: Browser refresh/navigation confirmation for error messages
        window.addEventListener('beforeunload', function(e) {
            console.log('=== BEFOREUNLOAD EVENT TRIGGERED ===');
            
            const hasExistingData = window.sessionHasExistingData;
            const isUsingSampleData = window.sessionIsUsingSampleData;
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            console.log('hasExistingData:', hasExistingData);
            console.log('isUsingSampleData:', isUsingSampleData);
            console.log('hasErrorMessages:', hasErrorMessages);
            
            // Only show confirmation if there are error messages or existing data
            if (hasErrorMessages || hasExistingData) {
                console.log('Conditions met for showing beforeunload confirmation');
                
                // Browser will show its own message regardless
                e.preventDefault();
                e.returnValue = ''; // Empty string is sufficient
                
                console.log('beforeunload event prevented');
                return ''; // For older browsers
            } else {
                console.log('No conditions met, allowing navigation');
            }
        });

        // Fallback handler for non-AJAX form submission (if JS fails)
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for upload_progress.js to load and initialize
            setTimeout(() => {
                const uploadForm = document.getElementById('uploadForm');
                const uploadBtn = document.getElementById('uploadBtn');
                
                // FIXED: Ensure the upload button state is properly managed after upload_progress.js
                if (uploadBtn && !uploadBtn.dataset.stateManaged) {
                    console.log('Managing upload button state after upload_progress.js initialization');
                    
                    // Mark as managed to avoid duplicate handlers
                    uploadBtn.dataset.stateManaged = 'true';
                    
                    // CRITICAL: Don't override the button state if it's already properly set
                    // Just ensure it's not disabled and has proper initial styling
                    if (!uploadBtn.classList.contains('show')) {
                        uploadBtn.style.display = 'none';
                        uploadBtn.style.opacity = '0';
                        uploadBtn.style.transform = 'translateY(10px)';
                    }
                    uploadBtn.disabled = false;
                    
                    console.log('Upload button state properly managed');
                }
                
                // Verify that upload_progress.js is properly loaded
                if (window.UploadProgressTracker || uploadForm.dataset.handledByTracker) {
                    console.log('UploadProgressTracker is active');
                } else {
                    console.log('UploadProgressTracker not detected, fallback handlers will be used');
                }
            }, 600); // Give upload_progress.js time to initialize
        });
    </script>
</body>
</html>