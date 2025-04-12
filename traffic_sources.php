<?php
require_once 'config.php';
include 'functions.php';

// Get traffic sources data
$sourcesData = getTrafficSourcesDistribution($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Sources - Web Traffic Analysis Dashboard</title>
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
                    <li><a href="overview.php">Overview</a></li>
                    <li><a href="traffic_sources.php" class="active">Traffic Sources</a></li>
                    <li><a href="pages.php">Pages</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <h2>Traffic Sources Dashboard</h2>
            
            <section class="chart-section">
                <h3>Traffic Sources Distribution</h3>
                <div class="chart-container">
                    <canvas id="sourcesChart"></canvas>
                </div>
                <div class="chart-type-toggle">
                    <button class="btn btn-sm active" data-chart-type="pie">Pie Chart</button>
                    <button class="btn btn-sm" data-chart-type="bar">Bar Chart</button>
                </div>
            </section>
            
            <section class="data-table-section">
                <h3>Traffic Sources Breakdown</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Visits</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sourcesData as $source): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($source['traffic_source']); ?></td>
                            <td><?php echo number_format($source['visit_count']); ?></td>
                            <td><?php echo $source['percentage']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
    
    <script>
        // Parse PHP data to JavaScript
        const sourcesData = <?php echo json_encode($sourcesData); ?>;
        
        // Extract data points for Chart.js
        const labels = sourcesData.map(item => item.traffic_source);
        const visitCounts = sourcesData.map(item => parseInt(item.visit_count));
        const percentages = sourcesData.map(item => parseFloat(item.percentage));
        
        // Define colors for the chart
        const backgroundColors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(255, 99, 255, 0.7)',
            'rgba(255, 211, 99, 0.7)'
        ];
        
        // Create chart context
        const ctx = document.getElementById('sourcesChart').getContext('2d');
        let currentChart = null;
        
        // Function to create chart
        function createChart(type) {
            // Destroy existing chart if it exists
            if (currentChart) currentChart.destroy();
            
            // Chart configuration
            const config = {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        data: type === 'pie' ? percentages : visitCounts,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: type === 'pie' ? 'right' : 'top',
                        },
                        title: {
                            display: true,
                            text: type === 'pie' ? 
                                'Traffic Sources Distribution (%)' : 
                                'Traffic Sources by Visit Count'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    if (type === 'pie') {
                                        return `${label}: ${value}% (${visitCounts[context.dataIndex]} visits)`;
                                    } else {
                                        return `${label}: ${value} visits (${percentages[context.dataIndex]}%)`;
                                    }
                                }
                            }
                        }
                    }
                }
            };
            
            // If bar chart, add extra options
            if (type === 'bar') {
                config.options.scales = {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Visits'
                        }
                    }
                };
                config.data.datasets[0].label = 'Visits';
            }
            
            // Create new chart
            currentChart = new Chart(ctx, config);
        }
        
        // Initialize with pie chart
        createChart('pie');
        
        // Toggle chart type
        document.querySelectorAll('.chart-type-toggle .btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get chart type
                const chartType = this.dataset.chartType;
                
                // Create new chart with the selected type
                createChart(chartType);
                
                // Update active button state
                document.querySelectorAll('.chart-type-toggle .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>