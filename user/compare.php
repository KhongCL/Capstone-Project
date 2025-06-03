<?php
// filepath: c:\xampp\htdocs\Capstone-Project\user\compare.php

require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

$error = '';
$success = '';
$firstUploadId = null;
$secondUploadId = null;

// Process file uploads if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['first_file'], $_FILES['second_file'])) {
    // Process first file upload
    if ($_FILES['first_file']['error'] === UPLOAD_ERR_OK) {
        $firstFileResult = processUpload($_FILES['first_file'], $conn, $_SESSION['user_id']);
        if ($firstFileResult['success']) {
            $firstUploadId = $firstFileResult['upload_id'];
            $success .= "First file uploaded successfully. ";
        } else {
            $error .= "First file error: " . $firstFileResult['message'] . " ";
        }
    } else {
        $error .= "Error uploading first file: " . getUploadErrorMessage($_FILES['first_file']['error']) . " ";
    }

    // Process second file upload
    if ($_FILES['second_file']['error'] === UPLOAD_ERR_OK) {
        $secondFileResult = processUpload($_FILES['second_file'], $conn, $_SESSION['user_id']);
        if ($secondFileResult['success']) {
            $secondUploadId = $secondFileResult['upload_id'];
            $success .= "Second file uploaded successfully.";
        } else {
            $error .= "Second file error: " . $secondFileResult['message'];
        }
    } else {
        $error .= "Error uploading second file: " . getUploadErrorMessage($_FILES['second_file']['error']);
    }
}

// Fallback to GET parameters if no successful uploads
if (!$firstUploadId && isset($_GET['first'])) {
    $firstUploadId = $_GET['first'];
}
if (!$secondUploadId && isset($_GET['second'])) {
    $secondUploadId = $_GET['second'];
}

// Get user uploads for the dropdown fallback
$stmt = $conn->prepare("SELECT UploadID, FileName, UploadDate FROM csv_upload WHERE UserID = ? ORDER BY UploadDate DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$uploads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get data for both uploads
$firstMetrics = $firstUploadId ? getKeyMetrics($conn, $firstUploadId) : null;
$secondMetrics = $secondUploadId ? getKeyMetrics($conn, $secondUploadId) : null;
$firstTrafficData = $firstUploadId ? getTrafficOverTime($conn, 'day', $firstUploadId) : [];
$secondTrafficData = $secondUploadId ? getTrafficOverTime($conn, 'day', $secondUploadId) : [];

// Helper function to process file upload and return result
function processUpload($file, $conn, $userId) {
    $result = ['success' => false, 'message' => '', 'upload_id' => null];
    
    // Check file type
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileType != 'csv') {
        $result['message'] = "Only CSV files are allowed.";
        return $result;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate a unique filename
    $uniqueFileName = time() . '_' . $fileName;
    $targetFile = $uploadDir . $uniqueFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        $result['message'] = "Failed to move uploaded file.";
        return $result;
    }
    
    // Parse CSV and process data
    $csvData = parseCSV($targetFile);
    if (!$csvData) {
        $result['message'] = "Failed to parse CSV file.";
        return $result;
    }
    
    // Record the upload in the database
    $stmt = $conn->prepare("INSERT INTO csv_upload (UserID, FileName, FileLocation, UploadDate) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $fileName, $targetFile);
    
    if (!$stmt->execute()) {
        $result['message'] = "Failed to record upload in database: " . $conn->error;
        return $result;
    }
    
    $uploadId = $conn->insert_id;
    
    // Transform and save the data
    $transformResult = saveTransformedData($conn, $csvData, $uploadId);
    if (!$transformResult['success']) {
        $result['message'] = "Error processing data: " . $transformResult['message'];
        return $result;
    }
    
    $result['success'] = true;
    $result['upload_id'] = $uploadId;
    return $result;
}

// Function to parse CSV file
function parseCSV($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $data = [];
    if (($handle = fopen($filePath, "r")) !== false) {
        // Get headers
        $headers = fgetcsv($handle);
        
        // Process rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($headers) === count($row)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
        return $data;
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compare Periods - Web Traffic Analysis Dashboard</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="user_style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .compare-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .upload-selector {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #f5f5f5;
      border-radius: 8px;
    }
    
    .metrics-comparison {
      margin-bottom: 30px;
    }
    
    .metric-difference {
      font-size: 14px;
      font-weight: bold;
      margin-top: 5px;
    }
    
    .increase {
      color: green;
    }
    
    .decrease {
      color: red;
    }
    
    .chart-toggle {
      margin-bottom: 15px;
      text-align: center;
    }
    
    .chart-container {
      height: 400px;
      width: 100%;
    }
    
    .btn {
      background-color: #4a6baf;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 10px;
    }
    
    .btn:hover {
      background-color: #3a5a9f;
    }
    
    .btn.btn-sm {
      padding: 5px 10px;
      font-size: 12px;
    }
    
    .btn.active {
      background-color: #1e3c72;
    }
    
    .metric-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 15px;
      margin-bottom: 15px;
    }
    
    .metric-card h4 {
      margin-top: 0;
      margin-bottom: 10px;
      color: #1e3c72;
    }
    
    .message-box {
      background-color: #f8f9fa;
      border-left: 4px solid #4a6baf;
      padding: 15px;
      margin: 20px 0;
    }
    
    .error-box {
      background-color: #fff3f3;
      border-left: 4px solid #e74c3c;
      padding: 15px;
      margin: 20px 0;
    }
    
    .success-box {
      background-color: #f0fff0;
      border-left: 4px solid #2ecc71;
      padding: 15px;
      margin: 20px 0;
    }
    
    .file-upload {
      margin-bottom: 15px;
    }
    
    .file-upload label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    .file-upload input[type="file"] {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .upload-methods {
      margin-bottom: 20px;
    }
    
    .method-tab {
      display: inline-block;
      padding: 8px 16px;
      margin-right: 5px;
      background-color: #f2f2f2;
      border-radius: 4px 4px 0 0;
      cursor: pointer;
    }
    
    .method-tab.active {
      background-color: #4a6baf;
      color: white;
    }
    
    .upload-method {
      display: none;
      padding: 15px;
      background-color: #f5f5f5;
      border-radius: 0 0 8px 8px;
    }
    
    .upload-method.active {
      display: block;
    }
    
    select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="overview.php">Overview</a></li>
                    <li><a href="traffic_sources.php">Traffic Sources</a></li>
                    <li><a href="pages.php">Pages</a></li>
                    <li><a href="compare.php" class="active">Compare</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <h2>Compare Traffic Periods</h2>
            
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="error-box">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-box">
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Upload Method Selection -->
            <div class="upload-methods">
                <div id="uploadTab" class="method-tab active">Upload New Files</div>
                <div id="selectTab" class="method-tab">Select Existing Files</div>
            </div>
            
            <!-- Upload New Files -->
            <div id="uploadMethod" class="upload-method active">
                <form action="compare.php" method="post" enctype="multipart/form-data">
                    <div class="compare-container">
                        <div class="file-upload">
                            <h3>First Period</h3>
                            <label for="first_file">Upload CSV for First Period:</label>
                            <input type="file" name="first_file" id="first_file" accept=".csv" required>
                            <small>Only CSV files are allowed</small>
                        </div>
                        <div class="file-upload">
                            <h3>Second Period</h3>
                            <label for="second_file">Upload CSV for Second Period:</label>
                            <input type="file" name="second_file" id="second_file" accept=".csv" required>
                            <small>Only CSV files are allowed</small>
                        </div>
                    </div>
                    <button type="submit" class="btn">Upload & Compare Data</button>
                </form>
            </div>
            
            <!-- Select Existing Files -->
            <div id="selectMethod" class="upload-method">
                <form id="compareForm">
                    <div class="compare-container">
                        <div>
                            <h3>First Period</h3>
                            <select name="first" id="firstUpload" required>
                                <option value="">Select a CSV upload</option>
                                <?php foreach ($uploads as $upload): ?>
                                    <option value="<?= $upload['UploadID'] ?>" <?= $firstUploadId == $upload['UploadID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($upload['FileName']) ?> (<?= date('Y-m-d', strtotime($upload['UploadDate'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <h3>Second Period</h3>
                            <select name="second" id="secondUpload" required>
                                <option value="">Select a CSV upload</option>
                                <?php foreach ($uploads as $upload): ?>
                                    <option value="<?= $upload['UploadID'] ?>" <?= $secondUploadId == $upload['UploadID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($upload['FileName']) ?> (<?= date('Y-m-d', strtotime($upload['UploadDate'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn">Compare Data</button>
                </form>
            </div>
            
            <!-- Comparison Section for All Metrics -->
            <?php if ($firstMetrics && $secondMetrics): ?>
              <section class="metrics-comparison">
                <h3>Key Metrics Comparison</h3>
                <div class="compare-container">
                  <?php
                    $allMetrics = [
                      'total_page_views' => 'Total Page Views',
                      'unique_visitors' => 'Unique Visitors',
                      'avg_session_duration' => 'Average Session Duration',
                      'bounce_rate' => 'Bounce Rate'
                    ];
                  ?>
                  <?php foreach ($allMetrics as $key => $label): ?>
                    <div class="metric-card">
                      <h4><?= htmlspecialchars($label) ?></h4>
                      <div class="compare-container">
                        <div>Period 1: <?= isset($firstMetrics[$key]) ? htmlspecialchars($firstMetrics[$key]) : 'N/A' ?><?= ($key == 'bounce_rate' || $key == 'engagement_rate') && !str_contains($firstMetrics[$key] ?? '', '%') ? '%' : '' ?><?= $key == 'avg_session_duration' ? 's' : '' ?></div>
                        <div>Period 2: <?= isset($secondMetrics[$key]) ? htmlspecialchars($secondMetrics[$key]) : 'N/A' ?><?= ($key == 'bounce_rate' || $key == 'engagement_rate') && !str_contains($secondMetrics[$key] ?? '', '%') ? '%' : '' ?><?= $key == 'avg_session_duration' ? 's' : '' ?></div>
                      </div>
                      <?php
                        $val1 = is_numeric($firstMetrics[$key] ?? null) ? (float)$firstMetrics[$key] : null;
                        $val2 = is_numeric($secondMetrics[$key] ?? null) ? (float)$secondMetrics[$key] : null;
                        if ($val1 !== null && $val2 !== null):
                          $diff = $val2 - $val1;
                          $percent = $val1 > 0 ? round(($diff / $val1) * 100, 2) : 0;
                        
                          // For bounce rate, lower is better, so reverse the class
                          if ($key == 'bounce_rate') {
                            $diffClass = $percent <= 0 ? 'increase' : 'decrease';
                          } else {
                            $diffClass = $percent >= 0 ? 'increase' : 'decrease';
                          }

                          $diffSign = $percent >= 0 ? '+' : '';
                      ?>
                        <div class="metric-difference <?= $diffClass ?>">
                          <?= $diffSign . $percent ?>% (<?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 2) ?><?= $key == 'bounce_rate' || $key == 'engagement_rate' ? ' points' : '' ?>)
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </section>
              
              <!-- Charts Section -->
              <section class="chart-section">
                <h3>Traffic Comparison</h3>
                <div class="chart-toggle">
                  <button id="overlayBtn" class="btn btn-sm active">Overlay Chart</button>
                  <button id="sideBySideBtn" class="btn btn-sm">Side-by-Side</button>
                </div>
                
                <div id="overlayChartContainer" class="chart-container">
                  <canvas id="overlayChart"></canvas>
                </div>
                
                <div id="sideBySideContainer" class="compare-container" style="display:none;">
                  <div class="chart-container">
                    <canvas id="firstChart"></canvas>
                  </div>
                  <div class="chart-container">
                    <canvas id="secondChart"></canvas>
                  </div>
                </div>
              </section>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['first']) || isset($_GET['second'])): ?>
              <div class="message-box">
                <p>Comparison data could not be loaded. Please ensure both files contain valid traffic data.</p>
              </div>
            <?php else: ?>
              <div class="message-box">
                <p>Upload or select two CSV files to compare their traffic data.</p>
              </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>

    <script>
        // Convert PHP data to JavaScript
        const firstTrafficData = <?= json_encode($firstTrafficData) ?>;
        const secondTrafficData = <?= json_encode($secondTrafficData) ?>;
        
        // Tab switching
        document.getElementById('uploadTab').addEventListener('click', function() {
            document.getElementById('uploadMethod').classList.add('active');
            document.getElementById('selectMethod').classList.remove('active');
            document.getElementById('uploadTab').classList.add('active');
            document.getElementById('selectTab').classList.remove('active');
        });
        
        document.getElementById('selectTab').addEventListener('click', function() {
            document.getElementById('selectMethod').classList.add('active');
            document.getElementById('uploadMethod').classList.remove('active');
            document.getElementById('selectTab').classList.add('active');
            document.getElementById('uploadTab').classList.remove('active');
        });
        
        // Form submission handler for existing files
        document.getElementById('compareForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const firstId = document.getElementById('firstUpload').value;
            const secondId = document.getElementById('secondUpload').value;
            
            if (firstId && secondId) {
                window.location.href = `compare.php?first=${firstId}&second=${secondId}`;
            } else {
                alert('Please select two uploads to compare');
            }
        });
        
        <?php if ($firstTrafficData && $secondTrafficData): ?>
        // Chart toggling
        document.getElementById('overlayBtn').addEventListener('click', function() {
            document.getElementById('overlayChartContainer').style.display = 'block';
            document.getElementById('sideBySideContainer').style.display = 'none';
            this.classList.add('active');
            document.getElementById('sideBySideBtn').classList.remove('active');
        });
        
        document.getElementById('sideBySideBtn').addEventListener('click', function() {
            document.getElementById('overlayChartContainer').style.display = 'none';
            document.getElementById('sideBySideContainer').style.display = 'grid';
            this.classList.add('active');
            document.getElementById('overlayBtn').classList.remove('active');
        });
        
        // Initialize the charts
        const overlayCtx = document.getElementById('overlayChart').getContext('2d');
        const firstCtx = document.getElementById('firstChart').getContext('2d');
        const secondCtx = document.getElementById('secondChart').getContext('2d');
        
        // Overlay chart
        const overlayChart = new Chart(overlayCtx, {
            type: 'line',
            data: {
                labels: firstTrafficData.map(item => item.time_period),
                datasets: [
                    {
                        label: 'Period 1 - Page Views',
                        data: firstTrafficData.map(item => parseInt(item.page_views)),
                        borderColor: '#4c78d0',
                        backgroundColor: 'rgba(76, 120, 208, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Period 1 - Unique Visitors',
                        data: firstTrafficData.map(item => parseInt(item.unique_visitors)),
                        borderColor: '#72b966',
                        backgroundColor: 'rgba(114, 185, 102, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Period 2 - Page Views',
                        data: secondTrafficData.map(item => parseInt(item.page_views)),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Period 2 - Unique Visitors',
                        data: secondTrafficData.map(item => parseInt(item.unique_visitors)),
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // First period chart
        const firstChart = new Chart(firstCtx, {
            type: 'line',
            data: {
                labels: firstTrafficData.map(item => item.time_period),
                datasets: [
                    {
                        label: 'Page Views',
                        data: firstTrafficData.map(item => parseInt(item.page_views)),
                        borderColor: '#4c78d0',
                        backgroundColor: 'rgba(76, 120, 208, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Unique Visitors',
                        data: firstTrafficData.map(item => parseInt(item.unique_visitors)),
                        borderColor: '#72b966',
                        backgroundColor: 'rgba(114, 185, 102, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Period 1 Traffic'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Second period chart
        const secondChart = new Chart(secondCtx, {
            type: 'line',
            data: {
                labels: secondTrafficData.map(item => item.time_period),
                datasets: [
                    {
                        label: 'Page Views',
                        data: secondTrafficData.map(item => parseInt(item.page_views)),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Unique Visitors',
                        data: secondTrafficData.map(item => parseInt(item.unique_visitors)),
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Period 2 Traffic'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>