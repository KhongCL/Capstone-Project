<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle sample data loading
$sampleDataLoaded = false;
$sampleDataInfo = null;
$sampleCsvData = null;

if (isset($_GET['load_sample']) && $_GET['load_sample'] == '1') {
    // Get the most recent sample data upload
    $stmt = $conn->prepare("
        SELECT cu.UploadID, cu.FileName, cu.ReportType, cu.UploadDate, 
               cu.AccountName, cu.PropertyName, cu.DataDateStart, cu.DataDateEnd
        FROM csv_upload cu 
        WHERE cu.IsSampleData = 1 
        ORDER BY cu.UploadDate DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sampleDataInfo = $result->fetch_assoc();
        
        // Set the sample upload as the current user's active upload
        $_SESSION['sample_upload_id'] = $sampleDataInfo['UploadID'];
        $_SESSION['using_sample_data'] = true;
        
        // Get sample CSV data for display
        $sampleCsvData = getSampleDataForDisplay($conn, $sampleDataInfo['UploadID']);
        $sampleDataLoaded = true;
        
        error_log("Sample data loaded: Upload ID " . $sampleDataInfo['UploadID']);
    }
}

// Clear sample data if requested
if (isset($_GET['clear_sample']) && $_GET['clear_sample'] == '1') {
    unset($_SESSION['sample_upload_id']);
    unset($_SESSION['using_sample_data']);
    header('Location: index.php');
    exit();
}

// CRITICAL FIX: Clear validation errors when page loads fresh (not from form submission)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['from_upload']) && !isset($_GET['load_sample'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear any lingering validation errors and upload messages
    unset($_SESSION['validation_errors']);
    unset($_SESSION['upload_message']);
    error_log("Cleared validation errors on fresh page load");
}

// Set page variables for header
$title = "Dashboard Home";
$active_page = "home";

// Handle CSV upload (fallback for non-JavaScript)
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Clear sample data when user uploads their own file
    unset($_SESSION['sample_upload_id']);
    unset($_SESSION['using_sample_data']);
    $uploadMessage = handleCsvUpload($conn, $_FILES['csvFile']);
}

/**
 * Get sample data for display in table format
 */
function getSampleDataForDisplay($conn, $uploadId) {
    $data = [];
    
    // Get traffic sources and their metrics
    $sql = "
        SELECT 
            st.SourceTypeName as traffic_source,
            SUM(CASE WHEN mt.MetricName = 'Sessions' THEN pdp.Value ELSE 0 END) as sessions,
            SUM(CASE WHEN mt.MetricName = 'Users' THEN pdp.Value ELSE 0 END) as users,
            SUM(CASE WHEN mt.MetricName = 'Engaged sessions' THEN pdp.Value ELSE 0 END) as engaged_sessions,
            AVG(CASE WHEN mt.MetricName = 'Bounce Rate' THEN pdp.Value ELSE NULL END) as bounce_rate,
            AVG(CASE WHEN mt.MetricName = 'Avg. Session Duration' THEN pdp.Value ELSE NULL END) as avg_session_duration
        FROM PROCESSED_DATA_POINT pdp
        JOIN SOURCE_TYPE st ON pdp.SourceTypeID = st.SourceTypeID
        JOIN METRIC_TYPE mt ON pdp.MetricTypeID = mt.MetricTypeID
        WHERE pdp.UploadID = ?
        GROUP BY st.SourceTypeID, st.SourceTypeName
        ORDER BY sessions DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uploadId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'traffic_source' => $row['traffic_source'],
            'sessions' => (int)$row['sessions'],
            'users' => (int)$row['users'],
            'engaged_sessions' => (int)$row['engaged_sessions'],
            'bounce_rate' => $row['bounce_rate'] ? round($row['bounce_rate'] * 100, 2) . '%' : 'N/A',
            'avg_session_duration' => $row['avg_session_duration'] ? formatDuration($row['avg_session_duration']) : 'N/A'
        ];
    }
    
    return $data;
}

/**
 * Format duration in seconds to readable format
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return round($seconds) . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . 'm ' . round($secs) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
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
</head>
<body>
    <div class="container">
        <?php include 'user_header.php'; ?>
        
        <main>
            <section class="welcome-section">
                <h2>Welcome to TrafAnalyz</h2>
                <p>Your one-stop solution for analyzing web traffic data. Upload your data and start exploring!</p>
            </section>
            
            <!-- Sample Data Display Section -->
            <?php if ($sampleDataLoaded && $sampleDataInfo): ?>
                <section class="sample-data-display">
                    <div class="sample-data-header">
                        <h2><i class="fas fa-vial"></i> Sample Data Loaded</h2>
                        <p class="sample-notice">
                            <i class="fas fa-info-circle"></i>
                            You are now viewing sample data for demonstration purposes. 
                            This data shows how TrafAnalyz works with real analytics data.
                        </p>
                        <div class="sample-actions">
                            <a href="overview.php" class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> View Sample Dashboard
                            </a>
                            <a href="?clear_sample=1" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Sample Data
                            </a>
                        </div>
                    </div>
                    
                    <div class="sample-data-info">
                        <h3>Sample Dataset Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Report Type:</strong> <?php echo htmlspecialchars($sampleDataInfo['ReportType']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Date Range:</strong> 
                                <?php echo date('M d, Y', strtotime($sampleDataInfo['DataDateStart'])); ?> - 
                                <?php echo date('M d, Y', strtotime($sampleDataInfo['DataDateEnd'])); ?>
                            </div>
                            <div class="info-item">
                                <strong>Property:</strong> <?php echo htmlspecialchars($sampleDataInfo['PropertyName'] ?: 'Sample Website'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Uploaded:</strong> <?php echo date('M d, Y H:i', strtotime($sampleDataInfo['UploadDate'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($sampleCsvData)): ?>
                        <div class="sample-data-table">
                            <h3>Sample Data Preview</h3>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Traffic Source</th>
                                            <th>Sessions</th>
                                            <th>Users</th>
                                            <th>Engaged Sessions</th>
                                            <th>Bounce Rate</th>
                                            <th>Avg Session Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sampleCsvData as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['traffic_source']); ?></td>
                                                <td><?php echo number_format($row['sessions']); ?></td>
                                                <td><?php echo number_format($row['users']); ?></td>
                                                <td><?php echo number_format($row['engaged_sessions']); ?></td>
                                                <td><?php echo $row['bounce_rate']; ?></td>
                                                <td><?php echo $row['avg_session_duration']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="table-note">
                                <i class="fas fa-lightbulb"></i>
                                This sample data demonstrates typical web analytics metrics. 
                                Use the dashboard navigation below to explore different views and insights.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            
            <!-- Regular Upload Section (hidden when sample data is loaded) -->
            <section class="upload-section" <?php echo $sampleDataLoaded ? 'style="display: none;"' : ''; ?>>
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
                        <!-- Error handling code remains the same -->
                        <div class="error-container">
                            <!-- ... existing error handling code ... -->
                        </div>
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="message <?php echo $uploadMessage['type']; ?>">
                            <?php echo htmlspecialchars($uploadMessage['message']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Existing validation warnings section -->
                <?php if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])): ?>
                    <?php 
                    $validationErrors = $_SESSION['validation_errors'];
                    unset($_SESSION['validation_errors']);
                    ?>
                    
                    <div class="message warning">
                        <h4>📋 Upload Completed with Warnings</h4>
                        <p>Data imported with <?php echo count($validationErrors); ?> validation warnings. Some rows had errors but valid data was processed.</p>
                        
                        <details>
                            <summary>View validation errors (<?php echo count($validationErrors); ?>)</summary>
                            <div class="validation-errors-list">
                                <?php foreach ($validationErrors as $error): ?>
                                    <div class="error-item">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        
                        <p><a href="overview.php" class="btn">View Imported Data</a></p>
                    </div>
                <?php endif; ?>
                
                <p>Upload your CSV file containing web traffic data. 
                    <i class="fas fa-info-circle tooltip-trigger" title="Expected format: GA4 export with columns for date, sessions, users, etc."></i>
                </p>
                
                <!-- Existing upload form -->
                <form action="" method="post" enctype="multipart/form-data" id="uploadForm" data-ajax-handler="upload_handler.php">
                    <!-- ... existing form content ... -->
                    <div class="form-group">
                        <label for="csvFile">Select CSV File:</label>
                        <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                        <div class="file-info" id="fileInfo" style="display: none;">
                            <span class="file-name"></span>
                            <span class="file-size"></span>
                        </div>
                    </div>
                    
                    <!-- Progress indicators remain the same -->
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <!-- ... existing progress content ... -->
                    </div>
                    
                    <button type="submit" class="btn" id="uploadBtn">Upload Data</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn" style="display: none;">Cancel Upload</button>
                </form>
                
                <div class="sample-data">
                    <p>New to TrafAnalyz? Try with our sample data:</p>
                    <a href="?load_sample=1" class="btn btn-secondary">
                        <i class="fas fa-vial"></i> Load Sample Data
                    </a>
                </div>
            </section>
                
            <section class="dashboard-links">
                <h2>Dashboard Navigation</h2>
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Overview</h3>
                        <p>View key metrics and website traffic over time.</p>
                        <a href="overview.php" class="btn">Go to Overview</a>
                    </div>
                    <div class="card">
                        <h3>Traffic Sources</h3>
                        <p>Analyze where your website traffic is coming from.</p>
                        <a href="traffic_sources.php" class="btn">Go to Traffic Sources</a>
                    </div>
                    <div class="card">
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
document.addEventListener('DOMContentLoaded', function() {
    // Check if we came from a successful upload
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from_upload') === 'success') {
        // Clean the URL without triggering a reload
        window.history.replaceState({}, document.title, window.location.pathname);
        
        // Optionally show a brief success message
        const uploadSection = document.querySelector('.upload-section');
        if (uploadSection) {
            const successMessage = document.createElement('div');
            successMessage.className = 'message success';
            successMessage.innerHTML = '<i class="fas fa-check-circle"></i> File uploaded successfully!';
            
            const form = uploadSection.querySelector('form');
            if (form) {
                form.parentNode.insertBefore(successMessage, form);
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    successMessage.remove();
                }, 3000);
            }
        }
    }
});
</script>
</body>
</html>