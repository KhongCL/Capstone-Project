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

    // Replace the existing exportChartToPDF() function with this comprehensive version:

    async function exportChartToPDF() {
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF('p', 'mm', 'a4');
      
      // Get current date and time
      const now = new Date();
      const generatedDate = now.getFullYear() + '-' + 
        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
        String(now.getDate()).padStart(2, '0') + ' ' +
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ':' +
        String(now.getSeconds()).padStart(2, '0');
      
      // Get username from session
      const username = '<?php echo $_SESSION['username'] ?? 'Unknown User'; ?>';
      
      // Determine current chart type
      const activeButton = document.querySelector('.user-chart-type-toggle .btn.active');
      const chartType = activeButton ? activeButton.dataset.chartType : 'pie';
      const chartTypeText = chartType === 'pie' ? 'Pie Chart' : 'Bar Chart';
      
      // PDF styling
      const pageWidth = pdf.internal.pageSize.getWidth();
      const margin = 20;
      let yPosition = 30;
      
      // Header
      pdf.setFontSize(20);
      pdf.setFont('helvetica', 'bold');
      pdf.text('TrafAnalyz Traffic Sources Report', pageWidth/2, yPosition, { align: 'center' });
      
      yPosition += 15;
      
      // Generated info
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
      yPosition += 5;
      pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });
      
      yPosition += 20;
      
      // Traffic Sources Summary Section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Summary', margin, yPosition);
      yPosition += 10;
      
      // Calculate totals
      const totalVisits = sourcesData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      const totalSources = sourcesData.length;
      
      // Summary info
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Total Traffic Sources: ${totalSources}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Total Visits: ${totalVisits.toLocaleString()}`, margin, yPosition);
      yPosition += 15;
      
      // Traffic sources detailed table
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Breakdown', margin, yPosition);
      yPosition += 10;
      
      const tableHeaders = ['Source', 'Visits', 'Percentage'];
      const tableData = [tableHeaders];
      
      // Add data rows
      sourcesData.forEach(source => {
        tableData.push([
          source.traffic_source,
          source.visit_count.toLocaleString(),
          source.percentage + '%'
        ]);
      });
      
      // Draw table
      pdf.setFontSize(10);
      tableData.forEach((row, index) => {
        const isHeader = index === 0;
        if (isHeader) {
          pdf.setFont('helvetica', 'bold');
          pdf.setFillColor(240, 240, 240);
          pdf.rect(margin, yPosition - 5, pageWidth - (margin * 2), 8, 'F');
        } else {
          pdf.setFont('helvetica', 'normal');
        }
        
        // Truncate long source names if needed
        const sourceName = row[0].length > 25 ? row[0].substring(0, 25) + '...' : row[0];
        pdf.text(sourceName, margin + 5, yPosition);
        pdf.text(row[1], margin + 80, yPosition);
        pdf.text(row[2], margin + 130, yPosition);
        
        // Draw table lines
        pdf.line(margin, yPosition + 2, pageWidth - margin, yPosition + 2);
        
        yPosition += 8;
        
        // Check if we need a new page
        if (yPosition > 250) {
          pdf.addPage();
          yPosition = 30;
        }
      });
      
      yPosition += 15;
      
      // Chart section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text(`Traffic Sources ${chartTypeText}`, margin, yPosition);
      yPosition += 10;
      
      // Capture chart as image
      const chartContainer = document.getElementById("chartContainer");
      const canvasImage = await html2canvas(chartContainer);
      const imageData = canvasImage.toDataURL("image/png");
      
      // Check if we need a new page for the chart
      if (yPosition > 150) {
        pdf.addPage();
        yPosition = 30;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text(`Traffic Sources ${chartTypeText}`, margin, yPosition);
        yPosition += 10;
      }
      
      // Add chart to PDF
      const chartWidth = pageWidth - (margin * 2);
      const chartHeight = 120;
      pdf.addImage(imageData, 'PNG', margin, yPosition, chartWidth, chartHeight);
      
      yPosition += chartHeight + 15;
      
      // Top performing sources analysis
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Insights', margin, yPosition);
      yPosition += 10;
      
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
      
      // Find top 3 sources
      const sortedSources = [...sourcesData].sort((a, b) => parseInt(b.visit_count) - parseInt(a.visit_count));
      const topSources = sortedSources.slice(0, 3);
      
      pdf.text(`• Top traffic source: ${topSources[0].traffic_source} (${topSources[0].percentage}% of total traffic)`, margin, yPosition);
      yPosition += 6;
      
      if (topSources[1]) {
        pdf.text(`• Second highest: ${topSources[1].traffic_source} (${topSources[1].percentage}% of total traffic)`, margin, yPosition);
        yPosition += 6;
      }
      
      if (topSources[2]) {
        pdf.text(`• Third highest: ${topSources[2].traffic_source} (${topSources[2].percentage}% of total traffic)`, margin, yPosition);
        yPosition += 6;
      }
      
      yPosition += 10;
      
      // Report Information section
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Report Information', margin, yPosition);
      yPosition += 8;
      
      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(10);
      pdf.text(`Upload ID: ${uploadId || 'N/A'}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Report Type: Traffic Sources Analysis`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Chart Type: ${chartTypeText}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Data Source: CSV Upload`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Traffic Sources: ${totalSources}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Visits Analyzed: ${totalVisits.toLocaleString()}`, margin, yPosition);
      
      // Save PDF with descriptive filename
      const chartTypeFilename = chartType === 'pie' ? 'pie_chart' : 'bar_chart';
      pdf.save(`TrafAnalyz_Traffic_Sources_Report_${chartTypeFilename}_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);
      
      // Log the PDF export into the database
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported traffic sources comprehensive report as PDF (uploadId: ${uploadId})`
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