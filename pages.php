<?php
require_once 'config.php';
include 'functions.php';

// Get top pages data
$pagesData = getTopVisitedPages($conn, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Pages - Web Traffic Analysis Dashboard</title>
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
                    <li><a href="traffic_sources.php">Traffic Sources</a></li>
                    <li><a href="pages.php" class="active">Pages</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <h2>Top Pages Dashboard</h2>
            
            <section class="chart-section">
                <h3>Most Visited Pages</h3>
                <div class="chart-container">
                    <canvas id="pagesChart"></canvas>
                </div>
            </section>
            
            <section class="data-table-section">
                <h3>Top Pages Detail</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page URL</th>
                            <th>Page Views</th>
                            <th>Unique Visitors</th>
                            <th>Views/Visitor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagesData as $page): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($page['page_url']); ?></td>
                            <td><?php echo number_format($page['page_views']); ?></td>
                            <td><?php echo number_format($page['unique_visitors']); ?></td>
                            <td><?php echo round($page['page_views'] / $page['unique_visitors'], 2); ?></td>
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
        const pagesData = <?php echo json_encode($pagesData); ?>;
        
        // Extract data points for Chart.js
        const pageUrls = pagesData.map(item => {
            // Truncate long URLs for display
            const url = item.page_url;
            return url.length > 30 ? url.substring(0, 30) + '...' : url;
        });
        const pageViews = pagesData.map(item => parseInt(item.page_views));
        
        // Create pages chart
        const ctx = document.getElementById('pagesChart').getContext('2d');
        const pagesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: pageUrls,
                datasets: [{
                    label: 'Page Views',
                    data: pageViews,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Most Visited Pages'
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                return pagesData[index].page_url;
                            },
                            afterTitle: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                return `Unique visitors: ${pagesData[index].unique_visitors}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Page Views'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>