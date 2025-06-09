<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$comparison_results = null;
$error_message = null;

// Handle file upload and comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file1']) && isset($_FILES['csv_file2'])) {
    $file1 = $_FILES['csv_file1'];
    $file2 = $_FILES['csv_file2'];
    
    // Validate files
    if ($file1['error'] === UPLOAD_ERR_OK && $file2['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
        
        if (in_array($file1['type'], $allowed_types) && in_array($file2['type'], $allowed_types)) {
            try {
                $comparison_results = compareCSVFiles($file1['tmp_name'], $file2['tmp_name']);
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
        'events_per_session', 'event_count', 'key_events', 'session_key_event_rate',
        'total_revenue', 'total_page_views', 'unique_visitors', 'average_session_duration',
        'bounce_rate'
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
                
                $comparison['analytics_metrics'][$metric] = [
                    'column_name' => $found_column,
                    'file1_stats' => $stats1,
                    'file2_stats' => $stats2,
                    'comparison' => [
                        'total_diff' => $stats1['sum'] - $stats2['sum'],
                        'avg_diff' => $stats1['mean'] - $stats2['mean'],
                        'percent_change' => $stats2['mean'] != 0 ? round((($stats1['mean'] - $stats2['mean']) / $stats2['mean']) * 100, 2) : 0,
                        'improvement' => determineImprovement($metric, $stats1['mean'], $stats2['mean'])
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
        'average_engagement_time_per_session' => ['Average engagement time per session', 'average_engagement_time_per_session', 'avg_engagement_time', 'engagement_time'],
        'events_per_session' => ['Events per session', 'events_per_session', 'events per session', 'eventspersession'],
        'event_count' => ['Event count', 'event_count', 'events', 'total_events'],
        'key_events' => ['Key events', 'key_events', 'key events', 'keyevents'],
        'session_key_event_rate' => ['Session key event rate', 'session_key_event_rate', 'key_event_rate', 'conversion_rate'],
        'total_revenue' => ['Total revenue', 'total_revenue', 'revenue', 'total revenue'],
        'total_page_views' => ['total_page_views', 'page_views', 'pageviews', 'Views', 'Page views'],
        'unique_visitors' => ['unique_visitors', 'unique visitors', 'users', 'Users', 'Total users'],
        'average_session_duration' => ['average_session_duration', 'avg_session_duration', 'session_duration', 'Average session duration'],
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

function determineImprovement($metric, $value1, $value2) {
    // For metrics where higher is better
    $higher_is_better = ['sessions', 'engaged_sessions', 'events_per_session', 'event_count', 
                        'key_events', 'total_revenue', 'total_page_views', 'unique_visitors', 
                        'average_session_duration', 'engagement_rate', 'session_key_event_rate'];
    
    // For metrics where lower is better
    $lower_is_better = ['bounce_rate'];
    
    if (in_array($metric, $higher_is_better)) {
        return $value1 > $value2 ? 'improved' : ($value1 < $value2 ? 'declined' : 'unchanged');
    } elseif (in_array($metric, $lower_is_better)) {
        return $value1 < $value2 ? 'improved' : ($value1 > $value2 ? 'declined' : 'unchanged');
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
        .upload-form {
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
        .file-input-group > div {
            flex: 1;
        }
        .file-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .file-input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .file-input-group small {
            color: #666;
            font-size: 12px;
        }
        .btn-submit {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: #0056b3;
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .stats-grid {
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
    </style>
</head>
<body>
    <div class="container" id="dashboard">
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
            <div class="upload-form">
                <h3><i class="fas fa-upload"></i> Upload Analytics CSV Files</h3>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="file-input-group">
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
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-chart-bar"></i> Compare Analytics Data
                    </button>
                </form>
            </div>

            <!-- Comparison Results -->
            <?php if ($comparison_results): ?>
                <!-- Debug Information -->
                <div class="alert alert-info">
                    <h4><i class="fas fa-bug"></i> Debug Information</h4>
                    <p><strong>Detected Headers:</strong><br>
                    <small><?php echo implode(' | ', $comparison_results['headers']['common_headers']); ?></small></p>
                    <p><strong>Analytics Metrics Found:</strong><br>
                    <small style="color: green;">
                        <?php 
                        if (!empty($comparison_results['analytics_metrics'])) {
                            echo count($comparison_results['analytics_metrics']) . ' metrics detected: ' . implode(', ', array_keys($comparison_results['analytics_metrics']));
                        } else {
                            echo "No analytics metrics detected - checking column matching...";
                        }
                        ?>
                    </small></p>
                </div>

                <!-- Analytics Metrics Summary -->
                <?php if (!empty($comparison_results['summary_comparison'])): ?>
                    <div class="metric-summary">
                        <h3><i class="fas fa-tachometer-alt"></i> Performance Overview</h3>
                        <div class="stats-grid">
                            <?php 
                            $key_metrics = ['sessions', 'engagement_rate', 'total_revenue', 'bounce_rate'];
                            foreach ($key_metrics as $metric): 
                                if (isset($comparison_results['summary_comparison'][$metric])):
                                    $data = $comparison_results['summary_comparison'][$metric];
                            ?>
                                <div class="metric-box">
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
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Detailed Analytics Metrics -->
                <?php if (!empty($comparison_results['analytics_metrics'])): ?>
                    <div class="comparison-card">
                        <div class="metric-header success">
                            <i class="fas fa-chart-bar"></i> Detailed Analytics Comparison
                        </div>
                        <div class="stats-grid">
                            <?php foreach ($comparison_results['analytics_metrics'] as $metric => $analysis): ?>
                                <div class="metric-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <strong><?php echo ucwords(str_replace('_', ' ', $metric)); ?></strong>
                                        <span class="<?php echo $analysis['comparison']['improvement'] === 'improved' ? 'improved' : ($analysis['comparison']['improvement'] === 'declined' ? 'declined' : 'unchanged'); ?>">
                                            <?php echo $analysis['comparison']['percent_change']; ?>%
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; text-align: center;">
                                        <div>
                                            <h5 style="color: #007bff;">Period 1</h5>
                                            <p><strong>Total:</strong> <?php echo number_format($analysis['file1_stats']['sum']); ?></p>
                                            <p><strong>Average:</strong> <?php echo number_format($analysis['file1_stats']['mean'], 2); ?></p>
                                        </div>
                                        <div>
                                            <h5 style="color: #17a2b8;">Period 2</h5>
                                            <p><strong>Total:</strong> <?php echo number_format($analysis['file2_stats']['sum']); ?></p>
                                            <p><strong>Average:</strong> <?php echo number_format($analysis['file2_stats']['mean'], 2); ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div style="text-align: center;">
                                        <small class="<?php echo $analysis['comparison']['improvement']; ?>">
                                            <strong>Change:</strong> 
                                            <?php echo $analysis['comparison']['total_diff'] > 0 ? '+' : ''; ?>
                                            <?php echo number_format($analysis['comparison']['total_diff']); ?>
                                            (<?php echo $analysis['comparison']['percent_change']; ?>%)
                                        </small>
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
                    <div class="stats-grid">
                        <div class="metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_rows']; ?></h4>
                            <small>Period 1 Records</small>
                        </div>
                        <div class="metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file2_rows']; ?></h4>
                            <small>Period 2 Records</small>
                        </div>
                        <div class="metric-box">
                            <h4><?php echo $comparison_results['basic_metrics']['file1_columns']; ?></h4>
                            <small>Columns</small>
                        </div>
                        <div class="metric-box">
                            <h4><?php echo count($comparison_results['headers']['common_headers']); ?></h4>
                            <small>Common Metrics</small>
                        </div>
                    </div>
                </div>

                <!-- Data Samples -->
                <div class="comparison-card">
                    <div class="metric-header secondary">
                        <i class="fas fa-eye"></i> Data Preview (First 5 Records)
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="flex: 1;">
                            <h4>Period 1 Sample</h4>
                            <div class="table-container">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <?php if (!empty($comparison_results['data_sample']['file1_sample'])): ?>
                                        <thead>
                                            <tr style="background: #f8f9fa;">
                                                <?php foreach (array_keys($comparison_results['data_sample']['file1_sample'][0]) as $header): ?>
                                                    <th style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($header); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comparison_results['data_sample']['file1_sample'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($value); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <h4>Period 2 Sample</h4>
                            <div class="table-container">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <?php if (!empty($comparison_results['data_sample']['file2_sample'])): ?>
                                        <thead>
                                            <tr style="background: #f8f9fa;">
                                                <?php foreach (array_keys($comparison_results['data_sample']['file2_sample'][0]) as $header): ?>
                                                    <th style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($header); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comparison_results['data_sample']['file2_sample'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($value); ?></td>
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