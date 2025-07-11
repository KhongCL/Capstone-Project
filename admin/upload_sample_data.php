<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../classes/CsvProcessor.php';

// Track if this page was loaded after a form submission
$isPostRedirect = false;
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $isPostRedirect = (strpos($referer, 'upload_sample.php') !== false);
}

// Get report types for sample upload dropdown
$reportTypes = [];
$sql = "SELECT DISTINCT ReportType FROM csv_upload ORDER BY ReportType";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reportTypes[] = $row['ReportType'];
    }
}

// Check if there's existing sample data
$hasExistingSampleData = false;
$existingSampleInfo = null;
$sampleDataPreview = null;
$csvHeaders = [];

$stmt = $conn->prepare("SELECT UploadID, FileName, AccountName, PropertyName, DataDateStart, DataDateEnd FROM csv_upload WHERE IsSampleData = 1 ORDER BY UploadDate DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $hasExistingSampleData = true;
    $existingSampleInfo = $row;
    
    // Load sample data preview similar to user/index.php
    $fileName = $row['FileName'];
    $filePath = __DIR__ . '/../uploads/' . $fileName;
    
    // Check if file exists and read it for preview
    if (file_exists($filePath)) {
        error_log("Reading sample CSV file for admin preview: $filePath");
        
        $file = fopen($filePath, 'r');
        if ($file) {
            // Skip metadata lines (lines starting with #)
            $line = '';
            while (($line = fgets($file)) !== false) {
                $line = trim($line);
                if (empty($line) || substr($line, 0, 1) === '#') {
                    continue; // Skip metadata and empty lines
                }
                // First non-metadata line is the header
                $csvHeaders = str_getcsv($line);
                break;
            }
            
            // Initialize the array
            $sampleDataPreview = [];
            
            // Read up to 10 data rows for preview
            $rowCount = 0;
            while (($line = fgets($file)) !== false && $rowCount < 10) {
                $line = trim($line);
                if (!empty($line)) {
                    $row = str_getcsv($line);
                    $sampleDataPreview[] = $row;
                    $rowCount++;
                }
            }
            fclose($file);
            
            error_log("Admin preview: CSV Headers: " . json_encode($csvHeaders ?? []));
            error_log("Admin preview: Sample rows count: " . count($sampleDataPreview ?? []));
        } else {
            error_log("Failed to open sample CSV file for admin preview: $filePath");
        }
    } else {
        error_log("Sample CSV file not found for admin preview: $filePath");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Sample Data - TrafAnalyz Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Error Display Styles - matching user/index.php */
        .message {
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .message i {
            font-size: 16px;
        }

        .error-container {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
            max-height: 400px;
            overflow-y: auto;
        }

        .error-summary {
            font-weight: bold;
            margin-bottom: 15px;
            color: #721c24;
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

        .error-message {
            font-weight: 500;
            color: #721c24;
            margin-bottom: 8px;
        }

        .error-suggestions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 3px;
            padding: 8px;
            font-size: 0.9em;
            color: #856404;
        }

        .suggestions-text {
            color: #856404;
        }

        .validation-help {
            background: #e2e3e5;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            max-height: 500px;
            overflow-y: auto;
        }

        .validation-help h4 {
            color: #495057;
            margin-bottom: 12px;
        }

        .fix-guide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .fix-item {
            background: white;
            border-radius: 4px;
            padding: 12px;
            border: 1px solid #ced4da;
        }

        .fix-item strong {
            display: block;
            margin-bottom: 8px;
            color: #495057;
        }

        .fix-item ul {
            margin: 0;
            padding-left: 20px;
        }

        .fix-item li {
            font-size: 0.85em;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .error-footer {
            font-weight: bold;
            color: #721c24;
            margin-top: 15px;
            text-align: center;
        }

        /* Existing sample data info styles */
        .existing-sample-info {
            background: #138496;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }

        .existing-sample-info h4 {
            margin: 0 0 10px 0;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .existing-sample-info .sample-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            font-size: 0.9em;
        }

        .existing-sample-info .detail-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 12px;
            border-radius: 6px;
						overflow: hidden;
						text-overflow: ellipsis;
        }

        .existing-sample-info .detail-label {
            font-weight: 600;
            margin-right: 8px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Sample Data Preview Styles - copied from user/index.php */
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

        /* Preview Table Styles */
        .preview-table-container {
            max-height: 400px;
            overflow: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
            min-width: 600px;
        }

        .preview-table th {
            background: #f8f9fa;
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
            min-width: 80px;
        }

        .preview-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #f1f3f4;
            white-space: nowrap;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
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

        /* Preview Actions Styles */
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
            border: 1px solid transparent;
        }

        .preview-actions .btn-success {
            background: #28a745 !important;
            color: #fff !important;
            border-color: #28a745 !important;
        }

        .preview-actions .btn-success:hover {
            background: #218838 !important;
            border-color: #1e7e34 !important;
            color: #fff !important;
            transform: translateY(-1px);
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
            transform: translateY(-1px);
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
            transform: translateY(-1px);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom scrollbar styles */
        .error-container::-webkit-scrollbar,
        .error-list::-webkit-scrollbar,
        .validation-help::-webkit-scrollbar,
        .preview-table-container::-webkit-scrollbar {
            width: 8px;
        }

        .error-container::-webkit-scrollbar-track,
        .error-list::-webkit-scrollbar-track,
        .validation-help::-webkit-scrollbar-track,
        .preview-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .error-container::-webkit-scrollbar-thumb,
        .error-list::-webkit-scrollbar-thumb,
        .validation-help::-webkit-scrollbar-thumb,
        .preview-table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .error-container::-webkit-scrollbar-thumb:hover,
        .error-list::-webkit-scrollbar-thumb:hover,
        .validation-help::-webkit-scrollbar-thumb:hover,
        .preview-table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .error-container {
                max-height: 300px;
                padding: 15px;
            }
            
            .error-list {
                max-height: 200px;
            }
            
            .validation-help {
                max-height: 350px;
                padding: 15px;
            }
            
            .fix-guide {
                grid-template-columns: 1fr;
            }

            .existing-sample-info .sample-details {
                grid-template-columns: 1fr;
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
        <?php 
            $title = "Upload Sample Data";
            $active_page = "sample_data";
            include 'admin_header.php';
        ?>

        <main>
            <section class="admin-section">
                <h2>Upload Sample CSV Data</h2>
                
                <?php 
                // Only show message if it exists AND this was a post-redirect
                if (isset($_SESSION['sample_upload_message']) && $isPostRedirect): 
                ?>
                    <div class="message <?php echo $_SESSION['sample_upload_message']['success'] ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['sample_upload_message']['message']; ?>
                    </div>
                    <?php unset($_SESSION['sample_upload_message']); ?>
                <?php endif; ?>

                <!-- Show existing sample data info -->
                <?php if ($hasExistingSampleData && $existingSampleInfo): ?>
                <div class="existing-sample-info">
                    <h4>
                        <i class="fas fa-database"></i>
                        Current Sample Data
                    </h4>
                    <p>There is existing sample data in the system. Uploading a new sample file will replace the current data.</p>
                    <div class="sample-details">
                        <div class="detail-item">
                            <span class="detail-label">File:</span>
                            <span><?php echo htmlspecialchars($existingSampleInfo['FileName']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Account:</span>
                            <span><?php echo htmlspecialchars($existingSampleInfo['AccountName']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Property:</span>
                            <span><?php echo htmlspecialchars($existingSampleInfo['PropertyName']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date Range:</span>
                            <span><?php echo htmlspecialchars($existingSampleInfo['DataDateStart']); ?> to <?php echo htmlspecialchars($existingSampleInfo['DataDateEnd']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Sample Data Preview Section - similar to user/index.php -->
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
                                <p>This table shows the first 10 rows of the current sample CSV file exactly as it appears. This data is available for users to explore TrafAnalyz features.</p>
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
                                <a href="../user/overview.php?sample_data=1" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-chart-line"></i> View Sample Dashboard
                                </a>
                                <a href="../user/traffic_sources.php?sample_data=1" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-share-alt"></i> View Traffic Sources
                                </a>
                                <a href="../user/pages.php?sample_data=1" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-file-alt"></i> View Top Pages
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="sample-upload-section">                    
                    <div class="card" style="margin-bottom: 30px;">
                        <h3><i class="fas fa-upload"></i> Upload New Sample File</h3>
                        <form action="upload_sample.php" method="post" enctype="multipart/form-data" id="sampleUploadForm">
                            <div class="form-group">
                                <label for="sampleCsv">Select CSV File:</label>
                                <input type="file" name="sampleCsv" id="sampleCsv" accept=".csv" required>
                                <small class="help-text">Only CSV files up to 5MB are allowed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reportType">Report Type:</label>
                                <select name="reportType" id="reportType" required>
                                    <option value="">-- Select Report Type --</option>
                                    <?php foreach ($reportTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">Define New Report Type...</option>
                                </select>
                            </div>
                            
                            <div id="newReportTypeField" class="form-group" style="display: none;">
                                <label for="newReportType">New Report Type Name:</label>
                                <input type="text" name="newReportType" id="newReportType" placeholder="Enter new report type name">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Upload Sample</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3><i class="fas fa-trash-alt"></i> Clear Sample Data</h3>
                        <p>Remove all sample data from the system if you need to start fresh.</p>
                        <div class="admin-actions" style="margin-top: 15px;">
                            <button id="clearSampleDataBtn" class="btn btn-danger">Clear All Sample Data</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <?php include 'admin_footer.php'; ?>
    </div>
    
    <script>
        // CRITICAL: Set global variables for confirmation logic
        window.adminHasExistingSampleData = <?php echo $hasExistingSampleData ? 'true' : 'false'; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Report Type Selection Logic
            const reportTypeSelect = document.getElementById('reportType');
            const newReportTypeField = document.getElementById('newReportTypeField');
            
            if (reportTypeSelect) {
                reportTypeSelect.addEventListener('change', function() {
                    if (this.value === 'new') {
                        newReportTypeField.style.display = 'block';
                    } else {
                        newReportTypeField.style.display = 'none';
                    }
                });
            }
            
            // Enhanced form submission with confirmation and validation error handling
            const uploadForm = document.getElementById('sampleUploadForm');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateSampleFile()) {
                        return false;
                    }

                    // CRITICAL: Show confirmation before upload
                    if (!confirmSampleDataUpload()) {
                        return false;
                    }
                    
                    // Show loading state
                    showUploadProgress();
                    
                    // Create FormData and submit via AJAX
                    const formData = new FormData(this);
                    
                    fetch('upload_sample.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideUploadProgress();
                        
                        if (data.success) {
                            showSuccessMessage(data.message);
                            // Reset form
                            uploadForm.reset();
                            newReportTypeField.style.display = 'none';
                            
                            // Update global state
                            window.adminHasExistingSampleData = true;
                            
                            // Refresh page after 2 seconds to show updated existing data info
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            // Enhanced error handling like user/index.php
                            if (data.errors && data.errors.length > 0) {
                                showDetailedErrors(data);
                            } else if (data.message) {
                                // Handle validation error messages with suggestions
                                if (data.message.includes('Data validation errors') || 
                                    data.message.includes('No valid data') ||
                                    data.message.includes('CSV parsing error') ||
                                    data.message.includes('validation')) {
                                    showValidationErrors(data.message);
                                } else {
                                    showErrorMessage(data.message);
                                }
                            } else {
                                showErrorMessage('An unknown error occurred during upload.');
                            }
                        }
                    })
                    .catch(error => {
                        hideUploadProgress();
                        console.error('Upload error:', error);
                        showErrorMessage('An error occurred during upload. Please try again.');
                    });
                });
            }
            
            // Clear Sample Data Button with confirmation
            const clearSampleDataBtn = document.getElementById('clearSampleDataBtn');
            if (clearSampleDataBtn) {
                clearSampleDataBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to clear all sample data? This action cannot be undone and will affect all users currently viewing sample data.')) {
                        fetch('clear_sample_data.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showSuccessMessage(data.message);
                                // Update global state
                                window.adminHasExistingSampleData = false;
                                
                                // Refresh page after 2 seconds to hide existing data info
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showErrorMessage(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showErrorMessage('An error occurred while clearing sample data.');
                        });
                    }
                });
            }
        });

        // CRITICAL: Toggle data preview function - copied from user/index.php
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

        // CRITICAL: Upload confirmation function similar to user/index.php
        function confirmSampleDataUpload() {
            const hasExistingSampleData = window.adminHasExistingSampleData;
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            console.log('=== ADMIN SAMPLE UPLOAD CONFIRMATION ===');
            console.log('hasExistingSampleData:', hasExistingSampleData);
            console.log('hasErrorMessages:', hasErrorMessages);
            
            // Show confirmation if there's existing sample data OR error messages
            if (!hasExistingSampleData && !hasErrorMessages) {
                console.log('No existing data or error messages - proceeding without confirmation');
                return true; 
            }
            
            let confirmMessage;
            
            // Prioritize error message warning if present
            if (hasErrorMessages && !hasExistingSampleData) {
                confirmMessage = "⚠️ Clear Error Messages?\n\n" +
                               "You have validation error messages displayed that contain helpful suggestions for fixing your CSV file:\n" +
                               "• 💡 Data fix suggestions\n" +
                               "• 🔧 Quick fix guide\n" +
                               "• 📋 Detailed error explanations\n\n" +
                               "Uploading a new file will clear these helpful messages.\n\n" +
                               "Do you want to continue with the upload?";
            } else if (hasErrorMessages && hasExistingSampleData) {
                confirmMessage = "⚠️ Replace Sample Data & Clear Error Messages?\n\n" +
                               "You have existing sample data AND validation error messages displayed.\n\n" +
                               "Uploading a new file will:\n" +
                               "• Replace the current sample data completely\n" +
                               "• Clear all sample analytics and metrics\n" +
                               "• Affect all users currently viewing sample data\n" +
                               "• Remove the helpful error messages and fix suggestions\n\n" +
                               "This action cannot be undone. Do you want to continue?";
            } else if (hasExistingSampleData) {
                confirmMessage = "⚠️ Replace Existing Sample Data?\n\n" +
                               "There is already sample data in the system. Uploading a new file will:\n" +
                               "• Replace the current sample data completely\n" +
                               "• Clear all sample analytics and metrics\n" +
                               "• Affect all users currently viewing sample data\n" +
                               "• Reset all sample dashboard results\n\n" +
                               "This action cannot be undone. Do you want to continue?";
            }
            
            console.log('Showing confirmation dialog:', confirmMessage);
            const result = confirm(confirmMessage);
            console.log('Confirmation result:', result);
            return result;
        }

        // CRITICAL: Browser refresh/navigation confirmation for error messages (like user/index.php)
        window.addEventListener('beforeunload', function(e) {
            console.log('=== ADMIN BEFOREUNLOAD EVENT TRIGGERED ===');
            
            const hasExistingSampleData = window.adminHasExistingSampleData;
            const hasErrorMessages = document.querySelector('.error-container, .validation-help, .message.error') !== null;
            
            console.log('hasExistingSampleData:', hasExistingSampleData);
            console.log('hasErrorMessages:', hasErrorMessages);
            
            // Only show confirmation if there are error messages with helpful content
            if (hasErrorMessages) {
                console.log('Error messages found - showing beforeunload confirmation');
                
                // Browser will show its own message regardless
                e.preventDefault();
                e.returnValue = ''; // Empty string is sufficient
                
                console.log('beforeunload event prevented');
                return ''; // For older browsers
            } else {
                console.log('No error messages, allowing navigation');
            }
        });

        function showUploadProgress() {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            }
        }

        function hideUploadProgress() {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Upload Sample';
            }
        }

        function showSuccessMessage(message) {
            // Remove existing messages
            removeExistingMessages();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message success';
            messageDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            
            // Insert after the h2 title
            const title = document.querySelector('h2');
            if (title && title.parentNode) {
                title.parentNode.insertBefore(messageDiv, title.nextSibling);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 5000);
        }

        function showErrorMessage(message) {
            // Remove existing messages
            removeExistingMessages();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message error';
            messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            // Insert after the h2 title
            const title = document.querySelector('h2');
            if (title && title.parentNode) {
                title.parentNode.insertBefore(messageDiv, title.nextSibling);
            }
        }

        // Enhanced validation error display like user/index.php
        function showValidationErrors(errorMessage) {
            // Remove existing messages
            removeExistingMessages();
            
            // Parse validation errors
            let errorMessage_clean = errorMessage;
            errorMessage_clean = errorMessage_clean.replace("Data validation errors found: ", "");
            errorMessage_clean = errorMessage_clean.replace(". Please correct these issues and upload again.", "");
            
            // Check for "No valid data" message
            if (errorMessage.includes('No valid data')) {
                const errorList = [
                    "No valid data found in CSV file - All rows failed validation",
                    "Common causes: Invalid file format, corrupt data, or unsupported CSV structure"
                ];
                showDetailedErrorList(errorList);
                return;
            }
            
            // Split by semicolons for validation errors
            const errorList = errorMessage_clean.split(';').filter(error => error.trim().length > 0);
            
            if (errorList.length > 0) {
                showDetailedErrorList(errorList);
            } else {
                showErrorMessage(errorMessage);
            }
        }

        function showDetailedErrorList(errorList) {
            const errorContainer = document.createElement('div');
            errorContainer.className = 'error-container';
            
            const errorSummary = document.createElement('p');
            errorSummary.className = 'error-summary';
            errorSummary.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Found ${errorList.length} validation errors in your CSV file:`;
            errorContainer.appendChild(errorSummary);
            
            const errorListElement = document.createElement('ul');
            errorListElement.className = 'error-list';
            
            errorList.forEach(error => {
                const error_clean = error.trim();
                if (error_clean.length > 0) {
                    // Parse error and suggestions
                    const parts = error_clean.split(' Suggestions: ');
                    const mainError = parts[0];
                    const suggestions = parts.length > 1 ? parts[1] : '';
                    
                    const errorItem = document.createElement('li');
                    errorItem.className = 'error-item';
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = mainError;
                    errorItem.appendChild(errorMessage);
                    
                    if (suggestions) {
                        const errorSuggestions = document.createElement('div');
                        errorSuggestions.className = 'error-suggestions';
                        errorSuggestions.innerHTML = `<strong>💡 Suggestions:</strong> <span class="suggestions-text">${suggestions}</span>`;
                        errorItem.appendChild(errorSuggestions);
                    }
                    
                    errorListElement.appendChild(errorItem);
                }
            });
            
            errorContainer.appendChild(errorListElement);
            
            // Add validation help section
            const validationHelp = document.createElement('div');
            validationHelp.className = 'validation-help';
            validationHelp.innerHTML = `
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
            `;
            errorContainer.appendChild(validationHelp);
            
            const errorFooter = document.createElement('p');
            errorFooter.className = 'error-footer';
            errorFooter.innerHTML = '<strong>Please correct these issues and upload again.</strong>';
            errorContainer.appendChild(errorFooter);
            
            // Insert after the h2 title
            const title = document.querySelector('h2');
            if (title && title.parentNode) {
                title.parentNode.insertBefore(errorContainer, title.nextSibling);
            }
        }

        function showDetailedErrors(response) {
            // Remove existing messages
            removeExistingMessages();
            
            if (response.errors && response.errors.length > 0) {
                // Create detailed error display for errors array
                const errorContainer = document.createElement('div');
                errorContainer.className = 'error-container';
                
                const errorSummary = document.createElement('p');
                errorSummary.className = 'error-summary';
                errorSummary.textContent = `Found ${response.errors.length} validation errors in your CSV file:`;
                errorContainer.appendChild(errorSummary);
                
                const errorList = document.createElement('ul');
                errorList.className = 'error-list';
                
                response.errors.forEach(error => {
                    const errorItem = document.createElement('li');
                    errorItem.className = 'error-item';
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    
                    // Handle both string and object error formats
                    if (typeof error === 'string') {
                        errorMessage.textContent = error;
                    } else if (error.message) {
                        errorMessage.textContent = error.message;
                        
                        if (error.suggestions) {
                            const suggestions = document.createElement('div');
                            suggestions.className = 'error-suggestions';
                            suggestions.innerHTML = '<strong>💡 Suggestions:</strong> ';
                            
                            const suggestionText = document.createElement('span');
                            suggestionText.className = 'suggestions-text';
                            suggestionText.textContent = error.suggestions;
                            suggestions.appendChild(suggestionText);
                            
                            errorItem.appendChild(suggestions);
                        }
                    }
                    
                    errorItem.appendChild(errorMessage);
                    errorList.appendChild(errorItem);
                });
                
                errorContainer.appendChild(errorList);
                
                // Insert after the h2 title
                const title = document.querySelector('h2');
                if (title && title.parentNode) {
                    title.parentNode.insertBefore(errorContainer, title.nextSibling);
                }
            }
        }

        function removeExistingMessages() {
            const existingMessages = document.querySelectorAll('.message, .error-container, .validation-help');
            existingMessages.forEach(msg => {
                if (msg.parentNode) {
                    msg.parentNode.removeChild(msg);
                }
            });
        }

        // Form validation
        function validateSampleFile() {
            const fileInput = document.getElementById('sampleCsv');
            const reportType = document.getElementById('reportType');
            const newReportType = document.getElementById('newReportType');
            
            // File validation
            if (fileInput.files.length === 0) {
                alert('Please select a CSV file to upload');
                return false;
            }
            
            const file = fileInput.files[0];
            
            // Check file extension
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Only CSV files are allowed');
                return false;
            }
            
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds the 5MB limit');
                return false;
            }
            
            // Report type validation
            if (reportType.value === '') {
                alert('Please select a report type');
                return false;
            }
            
            // New report type validation
            if (reportType.value === 'new' && !newReportType.value.trim()) {
                alert('Please enter a name for the new report type');
                return false;
            }
            
            return true;
        }
        </script>
</body>
</html>