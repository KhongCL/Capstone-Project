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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compare'])) {
    $upload1 = $_POST['upload1'];
    $upload2 = $_POST['upload2'];
    $comparisonName = trim($_POST['comparisonName']);

    // Insert into saved_comparison
    $stmt = $conn->prepare("INSERT INTO saved_comparison (UserID, ComparisonName) VALUES (?, ?)");
    $stmt->bind_param("is", $userID, $comparisonName);
    $stmt->execute();
    $comparisonID = $conn->insert_id;

    // Insert the two files into comparison_file_link
    $stmt = $conn->prepare("INSERT INTO comparison_file_link (ComparisonID, UploadID, FileOrder) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $comparisonID, $upload1, 1);
    $stmt->execute();
    $stmt->bind_param("iii", $comparisonID, $upload2, 2);
    $stmt->execute();

    echo "<p>✅ Comparison saved successfully as '<strong>$comparisonName</strong>'!</p>";
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
    <style>
        .comparison-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background: white;
            padding: 20px;
        }
        .metric-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
        
        /* Add styles for user-prefixed classes */
        .user-metric-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
        
        .user-metric-box h4 {
            color: #000 !important; /* Force black color */
            margin: 0 0 5px 0;
        }
        
        .user-metric-box small {
            color: #000 !important; /* Force black color */
            font-weight: 500;
        }
        
        .improved { color: #28a745; }
        .declined { color: #dc3545; }
        .unchanged { color: #6c757d; }
        .neutral { color: #17a2b8; }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        .metric-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Override the gradient background text color specifically for metric boxes inside metric-summary */
        .metric-summary .user-metric-box h4,
        .metric-summary .user-metric-box small {
            color: #000 !important; /* Black text even inside the gradient background */
        }
        
        .upload-form {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .user-upload-form {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .file-input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .user-file-input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .file-input-group > div,
        .user-file-input-group > div {
            flex: 1;
        }
        
        .file-input-group label,
        .user-file-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .file-input-group input,
        .user-file-input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .file-input-group small,
        .user-file-input-group small {
            color: #666;
            font-size: 12px;
        }
        
        .btn-submit,
        .user-btn-submit {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover,
        .user-btn-submit:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .user-alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger,
        .user-alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .stats-grid,
        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .metric-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }
        .metric-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .metric-header.success { background: #28a745; color: white; }
        .metric-header.primary { background: #007bff; color: white; }
        .metric-header.secondary { background: #6c757d; color: white; }

        /* Enhanced Detailed Analytics Comparison Styles */
        .comparison-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: box-shadow 0.3s ease;
        }
        
        .comparison-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .comparison-item h5 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 0.95em; /* Reduced from 1.1em */
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .period-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }
        
        .period-data {
            text-align: center;
            flex: 1;
        }
        
        .period-data h6 {
            font-size: 0.75em; /* Reduced from 0.85em */
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .period-data .value {
            font-size: 1.1em; /* Reduced from 1.4em */
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .period-data small {
            color: #6c757d;
            font-size: 0.7em; /* Reduced from 0.8em */
        }
        
        .vs-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            color: #adb5bd;
            font-weight: bold;
            font-size: 0.8em; /* Reduced from 0.9em */
        }
        
        .change-summary {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85em; /* Reduced from 0.95em */
            border-left: 4px solid #dee2e6;
        }
        
        .change-summary .improved {
            border-left-color: #28a745;
        }
        
        .change-summary .declined {
            border-left-color: #dc3545;
        }
        
        .change-summary .unchanged {
            border-left-color: #6c757d;
        }
        
        .metric-percentage {
            font-size: 0.9em; /* Reduced from 1.1em */
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            background: #f8f9fa;
        }
        
        .metric-percentage.improved {
            background: #d4edda;
            color: #155724;
        }
        
        .metric-percentage.declined {
            background: #f8d7da;
            color: #721c24;
        }
        
        .metric-percentage.unchanged {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Fix the inconsistent VS section */
        .detailed-vs-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }
        
        .detailed-period-data {
            text-align: center;
            flex: 1;
        }
        
        .detailed-period-data h6 {
            font-size: 0.75em; /* Reduced from 0.85em */
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .detailed-period-data .period-value {
            font-size: 1.1em; /* Reduced from 1.4em */
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .detailed-period-data .period-avg {
            color: #6c757d;
            font-size: 0.7em; /* Reduced from 0.8em */
            font-weight: 500;
        }

        .data-preview-section {
            display: block;
        }
        
        .preview-column {
            margin-bottom: 25px;
        }
        
        .preview-column h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: white;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
            margin: 0;
        }
        
        .preview-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .preview-table th:last-child {
            border-right: none;
        }
        
        .preview-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f3f4;
            border-right: 1px solid #f1f3f4;
            color: #495057;
        }
        
        .preview-table td:last-child {
            border-right: none;
        }
        
        .preview-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .preview-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        
        .preview-table tbody tr:nth-child(even):hover {
            background-color: #f8f9fa;
        }
        
        /* Responsive table scroll */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .user-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container user-compare-container" id="dashboard">
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
            <h2><i class="fas fa-balance-scale"></i> Analytics CSV Comparison</h2>
            <p>Compare two analytics CSV files to analyze performance metrics including sessions, engagement, revenue, and more.</p>

            <!-- Upload Form -->
            <div class="user-upload-form">
                <h3><i class="fas fa-upload"></i> Upload Analytics CSV Files</h3>
                
                <?php if ($error_message): ?>
                    <div class="user-alert user-alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="user-alert user-alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="user-file-input-group">
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
                    <button type="submit" class="user-btn-submit">
                        <i class="fas fa-chart-bar"></i> Compare Analytics Data
                    </button>
                </form>
            </div>


            <!-- Comparison Results -->
            <?php if ($comparison_results): ?>
                <!-- Debug Information -->
                <div class="alert alert-info">
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
                <div class="metric-summary">
                    <h3><i class="fas fa-tachometer-alt"></i> Performance Overview</h3>
                    <div class="user-stats-grid">
                        <?php 
                        // Dynamically show all available metrics from summary_comparison
                        foreach ($comparison_results['summary_comparison'] as $metric => $data): 
                        ?>
                            <div class="user-metric-box">
                                <h4><?php echo number_format($data['file1_total']); ?></h4>
                                <small><?php echo ucwords(str_replace('_', ' ', $metric)); ?></small>
                                <div style="margin-top: 5px;">
                                    <span class="<?php echo $data['status'] === 'improved' ? 'improved' : ($data['status'] === 'declined' ? 'declined' : 'unchanged'); ?>">
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
                <div class="comparison-card">
                    <div class="metric-header success">
                        <i class="fas fa-chart-bar"></i> Detailed Analytics Comparison
                    </div>
                    <div class="user-stats-grid">
                        <?php foreach ($comparison_results['analytics_metrics'] as $metric => $analysis): ?>
                            <div class="comparison-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h5><?php echo ucwords(str_replace('_', ' ', $metric)); ?></h5>
                                    <span class="metric-percentage <?php echo $analysis['comparison']['improvement']; ?>">
                                        <?php echo $analysis['comparison']['percent_change'] > 0 ? '+' : ''; ?>
                                        <?php echo $analysis['comparison']['percent_change']; ?>%
                                    </span>
                                </div>

                                <div class="detailed-vs-section">
                                    <div class="detailed-period-data">
                                        <h6>Period 1</h6>
                                        <div class="period-value"><?php echo number_format($analysis['file1_stats']['sum']); ?></div>
                                        <div class="period-avg">Avg: <?php echo number_format($analysis['file1_stats']['mean'], 1); ?></div>
                                    </div>

                                    <div class="vs-divider">VS</div>

                                    <div class="detailed-period-data">
                                        <h6>Period 2</h6>
                                        <div class="period-value"><?php echo number_format($analysis['file2_stats']['sum']); ?></div>
                                        <div class="period-avg">Avg: <?php echo number_format($analysis['file2_stats']['mean'], 1); ?></div>
                                    </div>
                                </div>

                                <div class="change-summary <?php echo $analysis['comparison']['improvement']; ?>">
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
                <div class="comparison-card">
                    <div class="metric-header primary">
                        <i class="fas fa-info-circle"></i> File Information
                    </div>
                    <div class="user-stats-grid">
                        <div class="user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_rows']; ?></h4>
                            <small>Period 1 Records</small>
                        </div>
                        <div class="user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file2_rows']; ?></h4>
                            <small>Period 2 Records</small>
                        </div>
                        <div class="user-metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_columns']; ?></h4>
                            <small>Total Columns</small>
                        </div>
                        <div class="user-metric-box">
                            <h4><?php echo count($comparison_results['headers']['common_headers']); ?></h4>
                            <small>Common Columns</small>
                        </div>
                    </div>
                </div>

                <!-- Data Samples -->
                <div class="comparison-card">
                    <div class="metric-header secondary">
                        <i class="fas fa-eye"></i> Data Preview (First 5 Records)
                    </div>
                    <div class="data-preview-section">
                        <div class="preview-column">
                            <h4>Period 1 Sample</h4>
                            <div class="table-container">
                                <table class="preview-table">
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
                                                    
                        <div class="preview-column">
                            <h4>Period 2 Sample</h4>
                            <div class="table-container">
                                <table class="preview-table">
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