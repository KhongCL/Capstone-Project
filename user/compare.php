<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection and functions - Updated to match your other files
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

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

// Handle file upload and comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file1']) && isset($_FILES['csv_file2'])) {
    $file1 = $_FILES['csv_file1'];
    $file2 = $_FILES['csv_file2'];
    
    // Validate files
    if ($file1['error'] === UPLOAD_ERR_OK && $file2['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
        
        if (in_array($file1['type'], $allowed_types) && in_array($file2['type'], $allowed_types)) {
            try {
                // Process comparison first
                $comparison_results = compareCSVFiles($file1['tmp_name'], $file2['tmp_name']);
                
                // Save both files to database
                $upload_result1 = handleCsvUpload($conn, $file1);
                $upload_result2 = handleCsvUpload($conn, $file2);
                
                if ($upload_result1['type'] === 'success' && $upload_result2['type'] === 'success') {
                    $success_message = "Comparison completed successfully! Files uploaded to database.";
                } else {
                    // If one upload failed, show error but still show comparison results
                    $error_message = "Comparison completed but file upload had issues: ";
                    $error_message .= $upload_result1['type'] !== 'success' ? "File 1: " . $upload_result1['message'] : "";
                    $error_message .= $upload_result2['type'] !== 'success' ? " File 2: " . $upload_result2['message'] : "";
                }
                
            } catch (Exception $e) {
                $error_message = "Error comparing files: " . $e->getMessage();
            }
        } else {
            $error_message = "Please upload valid CSV files only.";
        }
    } else {
        $error_message = "Error uploading files. Please try again.";
    }
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

        echo "<p>✅ Comparison saved successfully as '<strong>$comparisonName</strong>'!</p>";
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
$stmt = $conn->prepare("SELECT UploadID, FileName FROM csv_upload WHERE UserID = ? ORDER BY UploadDate DESC");
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
    $common_headers = $comparison['headers']['common_headers'];
    foreach ($analytics_metrics as $metric) {
        // Find matching column (case-insensitive, flexible naming)
        $found_column = findMetricColumn($common_headers, $metric);
        
        if ($found_column) {
            $values1 = array_column($data1, $found_column);
            $values2 = array_column($data2, $found_column);
            
            // Clean and convert to numeric
            $numeric1 = cleanNumericValues($values1);
            $numeric2 = cleanNumericValues($values2);
            
            if (count($numeric1) > 0 && count($numeric2) > 0) {
                $stats1 = calculateStats($numeric1);
                $stats2 = calculateStats($numeric2);
                
                // Fixed percentage calculation
                $percent_change = 0;
                if ($stats1['mean'] != 0) {
                    $percent_change = round((($stats2['mean'] - $stats1['mean']) / $stats1['mean']) * 100, 2);
                } elseif ($stats2['mean'] > 0) {
                    // If Period 1 is 0 but Period 2 has value, it's a 100% increase
                    $percent_change = 100;
                }
                
                $comparison['analytics_metrics'][$metric] = [
                    'column_name' => $found_column,
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
    
    // Calculate summary totals for key metrics
    $comparison['summary_comparison'] = calculateSummaryComparison($data1, $data2, $comparison['analytics_metrics']);
    
    return $comparison;
}

function findMetricColumn($headers, $metric) {
    $metric_variations = [
        'sessions' => ['Sessions', 'sessions', 'session', 'total_sessions'],
        'engaged_sessions' => ['Engaged sessions', 'engaged_sessions', 'engaged sessions', 'engagedsessions'],
        'engagement_rate' => ['Engagement rate', 'engagement_rate', 'engagement rate', 'engagementrate'],
        'average_engagement_time_per_session' => ['Average engagement time per session', 'average_engagement_time_per_session', 'avg_engagement_time', 'engagement_time', 'Average engagement time'],
        'events_per_session' => ['Events per session', 'events_per_session', 'events per session', 'eventspersession'],
        'event_count' => ['Event count', 'event_count', 'events', 'total_events', 'Events'],
        'key_events' => ['Key events', 'key_events', 'key events', 'keyevents', 'Conversions', 'conversions'],
        'session_key_event_rate' => ['Session key event rate', 'session_key_event_rate', 'key_event_rate', 'conversion_rate', 'Session conversion rate'],
        'total_revenue' => ['Total revenue', 'total_revenue', 'revenue', 'total revenue', 'Revenue', 'Purchase revenue'],
        'total_page_views' => ['total_page_views', 'page_views', 'pageviews', 'Views', 'Page views', 'Pageviews'],
        'unique_visitors' => ['unique_visitors', 'unique visitors', 'users', 'Users', 'Total users', 'Active users'],
        'average_session_duration' => ['average_session_duration', 'avg_session_duration', 'session_duration', 'Average session duration', 'Session duration'],
        'bounce_rate' => ['bounce_rate', 'bounce rate', 'bouncerate', 'Bounce rate']
    ];
    
    $variations = $metric_variations[$metric] ?? [$metric];
    
    foreach ($variations as $variation) {
        foreach ($headers as $header) {
            if (strcasecmp(trim($header), trim($variation)) === 0) {
                return $header;
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
</head>
<body>
    <div class="container compare-user-compare-container" id="dashboard">
        <header>
            <a href="../index.php" class="logo">
                <div class="logo-icon">T</div>
                TrafAnalyz
            </a>
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
            <h2><i class="fas fa-balance-scale"></i> Analytics CSV Comparison</h2>
            <p>Compare two analytics CSV files to analyze performance metrics including sessions, engagement, revenue, and more.</p>

            <!-- Upload Form -->
            <div class="compare-user-upload-form">
                <h3><i class="fas fa-upload"></i> Upload Analytics CSV Files</h3>
                
                <?php if ($error_message): ?>
                    <div class="compare-user-alert compare-user-alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="compare-user-alert compare-user-alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
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
                <h3>Compare CSV Files</h3>
                            
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
                        <button type="submit" name="load_comparison" class="compare-button">Load Comparison</button>
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
                                    <?php echo htmlspecialchars($file['FileName']); ?>
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
                                    <?php echo htmlspecialchars($file['FileName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                            
                    <div class="compare-comparison-name">
                        <label>Comparison Name (optional):</label>
                        <input type="text" name="comparisonName" placeholder="Enter a name to save this comparison">
                    </div>
                            
                    <button type="submit" name="compare" class="compare-button">Compare Files</button>
                </form>
            </div>


            <!-- Comparison Results -->
            <?php if ($comparison_results): ?>
                <!-- Debug Information -->
                <div class="compare-alert compare-alert-info">
                    <h4><i class="fas fa-bug"></i> Debug Information</h4>
                    <p><strong>Available CSV Headers:</strong><br>
                    <small><?php echo implode(' | ', $comparison_results['headers']['common_headers']); ?></small></p>

                    <p><strong>Analytics Metrics Detection Results:</strong><br>
                    <?php 
                    $all_metrics = ['sessions', 'engaged_sessions', 'engagement_rate', 'average_engagement_time_per_session',
                                   'events_per_session', 'event_count', 'key_events', 'session_key_event_rate',
                                   'total_revenue', 'total_page_views', 'unique_visitors', 'average_session_duration',
                                   'bounce_rate'];

                    foreach ($all_metrics as $metric) {
                        $found = isset($comparison_results['analytics_metrics'][$metric]);
                        $color = $found ? 'green' : 'red';
                        $status = $found ? '✓ Found' : '✗ Not Found';
                        echo '<small style="color: ' . $color . ';">' . $metric . ': ' . $status;
                        if ($found) {
                            echo ' → ' . $comparison_results['analytics_metrics'][$metric]['column_name'];
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
</body>
</html>