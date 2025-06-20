<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Set page variables for header
$title = "Traffic Sources";
$active_page = "traffic_sources";
// Get uploadId from URL parameter or most recent upload
$uploadId = isset($_GET['uploadId']) ? $_GET['uploadId'] : null;

if (!$uploadId) {
    // Get most recent upload for the current user
    $stmt = $conn->prepare("SELECT UploadID FROM csv_upload WHERE UserID = ? ORDER BY UploadDate DESC LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $uploadId = $row ? $row['UploadID'] : null;
}

// Get traffic sources data
$sourcesData = getTrafficSourcesDistribution($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Traffic Sources - Web Traffic Analysis Dashboard</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="user_style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
  <div class="container user-traffic-sources-container">
		<?php include 'user_header.php'; ?>
    
    <main>
      <h2>Traffic Sources Dashboard</h2>

      <section class="user-chart-section">
        <h3>Traffic Sources Distribution</h3>
        <div class="user-sources-chart-container" id="chartContainer">
          <canvas id="sourcesChart"></canvas>
        </div>
        <div class="user-chart-type-toggle">
          <button class="btn btn-sm active" data-chart-type="pie">Pie Chart</button>
          <button class="btn btn-sm" data-chart-type="bar">Bar Chart</button>
        </div>
        <div class="user-export-controls">
          <button onclick="exportChartToPDF()" class="user-export-btn pdf">
            <span class="icon">📄</span>
            <span class="text">Export Chart to PDF</span>
          </button>
        </div>
      </section>

      <section class="user-data-table-section">
        <h3>Traffic Sources Breakdown</h3>
        <div class="user-sources-table-container">
          <table class="user-data-table" id="sourcesTable">
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
        </div>
        <div class="user-export-controls">
          <button onclick="exportTableToCSV()" class="user-export-btn csv">
            <span class="icon">📊</span>
            <span class="text">Export Table to CSV</span>
          </button>
        </div>
      </section>
    </main>

    <?php include 'user_footer.php'; ?>
  </div>

  <script>
    // Parse PHP data to JavaScript
    const sourcesData = <?php echo json_encode($sourcesData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    
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
          maintainAspectRatio: false,
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
    document.querySelectorAll('.user-chart-type-toggle .btn').forEach(button => {
      button.addEventListener('click', function() {
        // Get chart type
        const chartType = this.dataset.chartType;
        // Create new chart with the selected type
        createChart(chartType);
        // Update active button state
        document.querySelectorAll('.user-chart-type-toggle .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Initialize with pie chart
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

      // Log export in DB
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=CSV&description=Exported traffic sources table data (uploadId: ${uploadId})`
      }).then(response => response.json())
        .then(data => {
          if (!data.success) {
            console.warn('Export log failed:', data.message);
          }
        })
        .catch(error => {
          console.error('Error logging export:', error);
        });
    }

    // Export chart to PDF
    async function exportChartToPDF() {
      const chartContainer = document.getElementById("chartContainer");
      const canvasImage = await html2canvas(chartContainer);
      const imageData = canvasImage.toDataURL("image/png");
        
      // Determine current chart type
      const activeButton = document.querySelector('.user-chart-type-toggle .btn.active');
      const chartType = activeButton ? activeButton.dataset.chartType : 'pie';
      const chartTypeText = chartType === 'pie' ? 'pie chart' : 'bar chart';
        
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF();
      const imgProps = pdf.getImageProperties(imageData);
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
      pdf.addImage(imageData, "PNG", 10, 10, pdfWidth - 20, pdfHeight);
      pdf.save(`traffic_sources_${chartType}_chart.pdf`);
        
      // Log the PDF export into the database with specific chart type
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported traffic sources ${chartTypeText} as PDF (uploadId: ${uploadId})`
      }).then(response => response.json())
        .then(data => {
          if (!data.success) {
            console.warn('Export log failed:', data.message);
          }
        })
        .catch(error => {
          console.error('Error logging export:', error);
        });
    }
  </script>
</body>
</html>