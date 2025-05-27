<?php

require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Get user uploads
$stmt = $conn->prepare("SELECT UploadID, FileName, UploadDate FROM csv_upload WHERE UserID = ? ORDER BY UploadDate DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$uploads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Default to the two most recent uploads if available
$firstUploadId = isset($_GET['first']) ? $_GET['first'] : (isset($uploads[0]['UploadID']) ? $uploads[0]['UploadID'] : null);
$secondUploadId = isset($_GET['second']) ? $_GET['second'] : (isset($uploads[1]['UploadID']) ? $uploads[1]['UploadID'] : null);

// Get data for both uploads
$firstMetrics = $firstUploadId ? getKeyMetrics($conn, $firstUploadId) : null;
$secondMetrics = $secondUploadId ? getKeyMetrics($conn, $secondUploadId) : null;
$firstTrafficData = $firstUploadId ? getTrafficOverTime($conn, 'day', $firstUploadId) : [];
$secondTrafficData = $secondUploadId ? getTrafficOverTime($conn, 'day', $secondUploadId) : [];

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
            
            <div class="upload-selector">
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
                      'bounce_rate' => 'Bounce Rate',
                      'new_users' => 'New Users',
                      'total_users' => 'Total Users',
                      'screen_page_views_per_session' => 'Views per Session',
                      'total_sessions' => 'Sessions',
                      'engaged_sessions' => 'Engaged sessions',
                      'engagement_rate' => 'Engagement rate',
                      'avg_engagement_time_per_session' => 'Average engagement time per session',
                      'events_per_session' => 'Events per session',
                      'event_count' => 'Event count',
                      'key_events' => 'Key events',
                      'session_key_event_rate' => 'Session key event rate',
                      'total_revenue' => 'Total revenue'
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
        
        // Form submission handler
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
        
        <?php if ($firstTrafficData && $secondTrafficData): ?>
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