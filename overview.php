<?php
require_once 'config.php';
include 'functions.php';

// Get key metrics
$metrics = getKeyMetrics($conn);

// Get traffic over time data
$trafficData = getTrafficOverTime($conn, 'day');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="overview.php" class="active">Overview</a></li>
                    <li><a href="traffic_sources.php">Traffic Sources</a></li>
                    <li><a href="pages.php">Pages</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <h2>Overview Dashboard</h2>
            
            <section class="metrics-grid">
                <div class="metric-card">
                    <h3>Total Page Views</h3>
                    <p class="metric-value"><?php echo number_format($metrics['total_page_views']); ?></p>
                </div>
                <div class="metric-card">
                    <h3>Unique Visitors</h3>
                    <p class="metric-value"><?php echo number_format($metrics['unique_visitors']); ?></p>
                </div>
                <div class="metric-card">
                    <h3>Avg. Session Duration</h3>
                    <p class="metric-value"><?php echo $metrics['avg_session_duration']; ?></p>
                </div>
                <div class="metric-card">
                    <h3>Bounce Rate</h3>
                    <p class="metric-value"><?php echo $metrics['bounce_rate']; ?></p>
                </div>
            </section>
            
            <section class="chart-section">
                <h3>Website Traffic Over Time</h3>
                <div class="chart-container">
                    <canvas id="trafficChart"></canvas>
                </div>
                <div class="chart-controls">
                    <button class="btn btn-sm" data-interval="day">Daily</button>
                    <button class="btn btn-sm" data-interval="month">Monthly</button>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
    
    <script>
        // Parse PHP data to JavaScript
        const trafficData = <?php echo json_encode($trafficData); ?>;
        
        // Extract data points for Chart.js
        const labels = trafficData.map(item => item.time_period);
        const pageViewsData = trafficData.map(item => parseInt(item.page_views));
        const uniqueVisitorsData = trafficData.map(item => parseInt(item.unique_visitors));
        
        // Create traffic chart
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Page Views',
                        data: pageViewsData,
                        borderColor: '#4c78d0',
                        backgroundColor: 'rgba(76, 120, 208, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Unique Visitors',
                        data: uniqueVisitorsData,
                        borderColor: '#72b966',
                        backgroundColor: 'rgba(114, 185, 102, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Website Traffic Over Time'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Interval buttons functionality
        document.querySelectorAll('.chart-controls .btn').forEach(button => {
            button.addEventListener('click', function() {
                const interval = this.dataset.interval;
                
                // Make AJAX request to get new data based on interval
                fetch(`get_traffic_data.php?interval=${interval}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update chart data
                        trafficChart.data.labels = data.map(item => item.time_period);
                        trafficChart.data.datasets[0].data = data.map(item => parseInt(item.page_views));
                        trafficChart.data.datasets[1].data = data.map(item => parseInt(item.unique_visitors));
                        trafficChart.update();
                    });
                
                // Update active button state
                document.querySelectorAll('.chart-controls .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>