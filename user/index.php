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
            'message' => 'Sample data loaded successfully! Explore the data preview below, then visit the dashboard pages to see the analytics.'
        ];
    } else {
        error_log("No sample data found in database");
        $uploadMessage = [
            'type' => 'error',
            'message' => 'No sample data available. Please contact the administrator.'
        ];
    }
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
        /* ==================== SAMPLE DATA STATUS STYLES ==================== */
        .sample-data-status {
            background: linear-gradient(135deg, #2980b9 0%, #0066cc 100%);
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

        /* ==================== RESPONSIVE STYLES ==================== */
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
                                <a href="download_sample.php" class="btn btn-success" style="background: #28a745; border-color: #28a745;">
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
            
            <section class="upload-section">
                <h2>Upload Traffic Data</h2>
                
                <?php if (!empty($uploadMessage)): ?>
                    <?php 
                    // Normalize $uploadMessage to always be an array
                    if (is_string($uploadMessage)) {
                        $uploadMessage = ['type' => 'info', 'message' => $uploadMessage];
                    }
                    ?>
                    
                    <?php if ($uploadMessage['type'] === 'error' && 
                              strpos($uploadMessage['message'], 'Data validation errors') !== false): ?>
                        <?php
                        // Enhanced error message parsing to extract suggestions
                        $errorMessage = $uploadMessage['message'];
                        $errorMessage = str_replace("Data validation errors found: ", "", $errorMessage);
                        $errorMessage = preg_replace('/\. Please correct these issues and upload again\./', '', $errorMessage);
                        
                        // Split by semicolons and parse suggestions
                        $errorList = explode(';', $errorMessage);
                        ?>
                        
                        <div class="error-container">
                            <p class="error-summary">Found <?php echo count($errorList); ?> validation errors in your CSV file:</p>
                            <ul class="error-list">
                                <?php foreach($errorList as $error): ?>
                                    <?php $error = trim($error); ?>
                                    <?php if(!empty($error)): ?>
                                        <?php
                                        // Parse error and suggestions
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
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="validation-help">
                            <h4>Common CSV Issues & Quick Fixes:</h4>
                            <div class="validation-tips">
                                <div class="tip-item">
                                    <strong>🔢 Number Format Issues:</strong>
                                    <ul>
                                        <li>Remove letters from numbers: "123abc" → "123"</li>
                                        <li>Fix decimal points: "12.34.56" → "12.34"</li>
                                        <li>Remove special characters: "1,234" → "1234"</li>
                                    </ul>
                                </div>
                                <div class="tip-item">
                                    <strong>📝 Text Issues:</strong>
                                    <ul>
                                        <li>Remove extra quotes: ""text"" → "text"</li>
                                        <li>Fix line breaks in cells</li>
                                        <li>Remove trademark symbols: "Brand™" → "Brand"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="message <?php echo $uploadMessage['type']; ?>">
                            <?php echo htmlspecialchars($uploadMessage['message']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <p>Upload your CSV file containing web traffic data. 
                    <i class="fas fa-info-circle tooltip-trigger" title="Expected format: GA4 export with columns for date, sessions, users, etc."></i>
                </p>
                <form action="" method="post" enctype="multipart/form-data" id="uploadForm" data-ajax-handler="upload_handler.php">
                    <div class="form-group">
                        <label for="csvFile">Select CSV File:</label>
                        <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                        <div class="file-info" id="fileInfo" style="display: none;">
                            <span class="file-name"></span>
                            <span class="file-size"></span>
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

        <?php include '../footer.php'; ?>
    </div>

    <script src="upload_progress.js"></script>
    <script>
        // File info display
        document.getElementById('csvFile').addEventListener('change', function() {
            const fileInfo = document.getElementById('fileInfo');
            const fileName = fileInfo.querySelector('.file-name');
            const fileSize = fileInfo.querySelector('.file-size');
            
            if (this.files.length > 0) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                
                // DON'T clear error messages here - let them remain visible
                // Error messages will be cleared when upload actually starts
            } else {
                fileInfo.style.display = 'none';
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
            // ENHANCED: Force a fresh check of session state
            const hasExistingData = <?php echo (isset($_SESSION['latest_upload_id']) || isset($_SESSION['using_sample_data'])) ? 'true' : 'false'; ?>;
            const isUsingSampleData = <?php echo (isset($_SESSION['using_sample_data']) && $_SESSION['using_sample_data']) ? 'true' : 'false'; ?>;
            
            // NEW: Check if there are error messages displayed that would be helpful to keep
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            // ENHANCED DEBUG LOGGING
            console.log('=== CONFIRMATION DEBUG ===');
            console.log('hasExistingData:', hasExistingData);
            console.log('isUsingSampleData:', isUsingSampleData);
            console.log('hasErrorMessages:', hasErrorMessages);
            console.log('Current URL:', window.location.href);
            console.log('Referrer:', document.referrer);
            
            // CRITICAL FIX: If we just came back from map_columns.php, force a page refresh to get current session state
            if (document.referrer && document.referrer.includes('map_columns.php')) {
                console.log('Detected return from mapping page - session state may be stale');
                // Don't use cached session state, force server check
                return true; // Let the upload proceed and let server handle current state
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
            
            // NEW: Prioritize error message warning if present
            if (hasErrorMessages && !hasExistingData) {
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

        // Fallback handler for non-AJAX form submission (if JS fails)
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');
            
            // Add a fallback listener that only triggers if upload_progress.js fails to load
            setTimeout(function() {
                // Check if UploadProgressTracker is handling the form
                if (!uploadForm.dataset.handledByTracker) {
                    console.log('Adding fallback form submission handler');
                    
                    uploadForm.addEventListener('submit', function(e) {
                        const hasExistingData = <?php echo (isset($_SESSION['latest_upload_id']) || isset($_SESSION['using_sample_data'])) ? 'true' : 'false'; ?>;
                        
                        if (hasExistingData && !confirmDataReplacement()) {
                            e.preventDefault(); // Stop the form submission
                            return false;
                        }
                        // If confirmed or no existing data, let form submit normally
                    });
                }
            }, 500); // Give upload_progress.js time to load
        });
    </script>
</body>
</html>