<?php
require_once 'config.php';
include 'functions.php';

// Get traffic sources data
$sourcesData = getTrafficSourcesDistribution($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Traffic Sources - Web Traffic Analysis Dashboard</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .export-controls {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .export-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .export-btn.csv {
      background-color: #4CAF50;
      color: white;
    }
    
    .export-btn.pdf {
      background-color: #f44336;
      color: white;
    }
    
    .export-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .export-btn:active {
      transform: translateY(0);
    }
    
    .export-btn .icon {
      font-size: 1.2rem;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        <div class="chart-container" id="chartContainer">
          <canvas id="sourcesChart"></canvas>
        </div>
        <div class="chart-type-toggle">
          <button class="btn btn-sm active" data-chart-type="pie">Pie Chart</button>
          <button class="btn btn-sm" data-chart-type="bar">Bar Chart</button>
        </div>
        <div style="margin-top: 10px;">
          <button onclick="exportChartToPDF()" class="export-btn pdf">
            <span class="icon">📄</span>
            <span class="text">Export Chart to PDF</span>
         </button>
        </div>
      </section>

      <section class="data-table-section">
        <h3>Traffic Sources Breakdown</h3>
        <table class="data-table" id="sourcesTable">
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
        <div style="margin-top: 10px;">
          <button onclick="exportTableToCSV()" class="export-btn csv">
          <span class="icon">📊</span>
          <span class="text">Export Table to CSV</span>
         </button>
        </div>
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
    let currentChart = null;
    const ctx = document.getElementById('sourcesChart').getContext('2d');

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
            legend: { position: type === 'pie' ? 'right' : 'top' },
            title: {
              display: true,
              text: type === 'pie'
                ? 'Traffic Sources Distribution (%)'
                : 'Traffic Sources by Visit Count'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw;
                  return type === 'pie'
                    ? `${label}: ${value}% (${visitCounts[context.dataIndex]} visits)`
                    : `${label}: ${value} visits (${percentages[context.dataIndex]}%)`;
                }
              }
            }
          },
          scales: type === 'bar' ? {
            y: {
              beginAtZero: true,
              title: { display: true, text: 'Number of Visits' }
            }
          } : {}
        }
      };
      // If bar chart, add extra options
      if (type === 'bar') config.data.datasets[0].label = 'Visits';
      currentChart = new Chart(ctx, config);
    }

    // Chart type toggle
    document.querySelectorAll('.chart-type-toggle .btn').forEach(button => {
      button.addEventListener('click', function() {
        // Get chart type
        const chartType = this.dataset.chartType;
        // Create new chart with the selected type
        createChart(chartType);
        // Update active button state
        document.querySelectorAll('.chart-type-toggle .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
      });
    });

    createChart('pie');

    // Export table to CSV
    function exportTableToCSV() {
      const table = document.getElementById("sourcesTable");
      let csv = [];
      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText}"`);
        csv.push(cols.join(","));
      }
      const blob = new Blob([csv.join("\n")], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "traffic_sources_table.csv";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    // Export chart to PDF
    async function exportChartToPDF() {
      const chartContainer = document.getElementById("chartContainer");
      const canvasImage = await html2canvas(chartContainer);
      const imageData = canvasImage.toDataURL("image/png");

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF();
      const imgProps = pdf.getImageProperties(imageData);
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
      pdf.addImage(imageData, "PNG", 10, 10, pdfWidth - 20, pdfHeight);
      pdf.save("traffic_sources_chart.pdf");
    }
  </script>
</body>
</html>