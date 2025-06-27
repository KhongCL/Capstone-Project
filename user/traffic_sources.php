<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Check if this is a sample data redirect
if (isset($_GET['sample_data']) && $_GET['sample_data'] == '1') {
    // Ensure session is active
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verify sample data session variables are set
    if (!isset($_SESSION['using_sample_data']) || !isset($_SESSION['sample_upload_id'])) {
        error_log("WARNING: Sample data redirect but session variables missing, attempting to restore");
        
        // Attempt to restore sample data session
        $stmt = $conn->prepare("SELECT UploadID FROM csv_upload WHERE IsSampleData = 1 ORDER BY UploadDate DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $_SESSION['using_sample_data'] = true;
            $_SESSION['sample_upload_id'] = $row['UploadID'];
            $_SESSION['latest_upload_id'] = $row['UploadID'];
            error_log("Restored sample data session with UploadID: " . $row['UploadID']);
        }
    }
}

// Set page variables for header
$title = "Traffic Sources";
$active_page = "traffic_sources";

// Get uploadId using sample-aware function (enhanced from current)
$uploadId = getCurrentUploadId($conn, $_SESSION['user_id']);

// Get sample data notice (from current)
$sampleNotice = getSampleDataNotice();

// Get traffic sources data (enhanced to pass uploadId)
$sourcesData = getTrafficSourcesDistribution($conn, $uploadId);
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
  <style>
    .sample-data-notice {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
        padding: 15px 20px;
        margin: 20px 0;
        border-radius: 8px;
        color: #333;
        box-shadow: 0 4px 12px rgba(255, 154, 158, 0.3);
    }

    .notice-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    .notice-content i {
        font-size: 1.2em;
        margin-right: 10px;
        color: #e91e63;
    }

    .notice-content span {
        flex: 1;
        font-weight: 500;
    }

    .notice-content .btn {
        padding: 8px 16px;
        font-size: 0.9em;
        border: 1px solid rgba(255, 255, 255, 0.5);
        background: rgba(255, 255, 255, 0.2);
        color: #333;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .notice-content .btn:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .notice-content {
            flex-direction: column;
            text-align: center;
        }
    }
  </style>
</head>
<body>
  <div class="container user-traffic-sources-container">
    <?php include 'user_header.php'; ?>
    
    <main>
      <h2>Traffic Sources Dashboard</h2>

      <!-- Sample Data Notice (from current) -->
      <?php if ($sampleNotice['is_sample']): ?>
        <div class="sample-data-notice">
          <div class="notice-content">
            <i class="fas fa-vial"></i>
            <span><?php echo $sampleNotice['message']; ?></span>
            <?php echo $sampleNotice['action']; ?>
          </div>
        </div>
      <?php endif; ?>

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
    // Parse PHP data to JavaScript (enhanced from current)
    const sourcesData = <?php echo json_encode($sourcesData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    const isSampleData = <?php echo $sampleNotice['is_sample'] ? 'true' : 'false'; ?>;
    
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
      
      // Chart configuration (enhanced from current with sample data support)
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
                ? (isSampleData ? 'Traffic Sources Distribution (%) - Sample Data' : 'Traffic Sources Distribution (%)')
                : (isSampleData ? 'Traffic Sources by Visit Count - Sample Data' : 'Traffic Sources by Visit Count')
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
        const chartType = this.dataset.chartType;
        createChart(chartType);
        document.querySelectorAll('.user-chart-type-toggle .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Initialize with pie chart
    createChart('pie');

    // Export table to CSV (enhanced from current with sample data support)
    function exportTableToCSV() {
      const table = document.getElementById("sourcesTable");
      let csv = [];

      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText.trim()}"`);
        csv.push(cols.join(","));
      }

      const blob = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `traffic_sources_table${isSampleData ? '_sample' : ''}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      // Log export in DB (enhanced from current)
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=CSV&description=Exported traffic sources table data (uploadId: ${uploadId}, sample: ${isSampleData})`
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

    // Export chart to PDF (comprehensive version from incoming, enhanced with sample data support)
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
      
      // Sample data indicator (enhanced from current)
      if (isSampleData) {
        yPosition += 5;
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(255, 0, 0);
        pdf.text('⚠️ Sample Data Report', pageWidth - margin, yPosition, { align: 'right' });
        pdf.setTextColor(0, 0, 0);
      }
        
      yPosition += 20;
        
      // Traffic Sources Summary Section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Summary', margin, yPosition);
      yPosition += 10;
        
      // Calculate totals and insights
      const totalSources = sourcesData.length;
      const totalVisits = sourcesData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      const topSource = sourcesData.reduce((top, current) => 
        parseInt(current.visit_count) > parseInt(top.visit_count) ? current : top
      );
    
      // Summary info
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Total Traffic Sources: ${totalSources}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Total Visits: ${totalVisits.toLocaleString()}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Top Source: ${topSource.traffic_source} (${parseFloat(topSource.percentage).toFixed(1)}%)`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Top Source Visits: ${parseInt(topSource.visit_count).toLocaleString()}`, margin, yPosition);
      yPosition += 15;
    
      // Traffic Sources detailed table
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
          parseInt(source.visit_count).toLocaleString(),
          `${parseFloat(source.percentage).toFixed(1)}%`
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
      
        // Column positions
        pdf.text(row[0], margin + 2, yPosition);
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
    
      // Pie Chart section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Distribution (Pie Chart)', margin, yPosition);
      yPosition += 10;
    
      // Check if we need a new page for the chart
      if (yPosition > 150) {
        pdf.addPage();
        yPosition = 30;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Traffic Sources Distribution (Pie Chart)', margin, yPosition);
        yPosition += 10;
      }
    
      // Save current chart state
      const currentChartType = document.querySelector('.user-chart-type-toggle .btn.active').dataset.chartType;
      
      // Switch to pie chart and capture
      createChart('pie');
      await new Promise(resolve => setTimeout(resolve, 500)); // Wait for chart to render
    
      const chartContainer = document.getElementById('chartContainer');
      const pieChartImage = await html2canvas(chartContainer);
      const pieImageData = pieChartImage.toDataURL("image/png");
    
      // Add pie chart to PDF
      const chartWidth = pageWidth - (margin * 2);
      const chartHeight = 100;
      pdf.addImage(pieImageData, 'PNG', margin, yPosition, chartWidth, chartHeight);
    
      yPosition += chartHeight + 20;
    
      // Check if we need a new page for bar chart
      if (yPosition > 180) {
        pdf.addPage();
        yPosition = 30;
      }
    
      // Bar Chart section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources by Visit Count (Bar Chart)', margin, yPosition);
      yPosition += 10;
    
      // Switch to bar chart and capture
      createChart('bar');
      await new Promise(resolve => setTimeout(resolve, 500)); // Wait for chart to render
    
      const barChartImage = await html2canvas(chartContainer);
      const barImageData = barChartImage.toDataURL("image/png");
    
      // Add bar chart to PDF
      pdf.addImage(barImageData, 'PNG', margin, yPosition, chartWidth, chartHeight);
    
      yPosition += chartHeight + 15;
    
      // Restore original chart type
      createChart(currentChartType);
      document.querySelectorAll('.user-chart-type-toggle .btn').forEach(btn => btn.classList.remove('active'));
      document.querySelector(`[data-chart-type="${currentChartType}"]`).classList.add('active');
    
      // Check if we need a new page for insights
      if (yPosition > 200) {
        pdf.addPage();
        yPosition = 30;
      }
    
      // Key Insights section
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Insights', margin, yPosition);
      yPosition += 10;
    
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
    
      // Find top 3 sources
      const sortedSources = [...sourcesData].sort((a, b) => parseInt(b.visit_count) - parseInt(a.visit_count));
      const topSources = sortedSources.slice(0, 3);
    
      if (topSources[0]) {
        pdf.text(`• Primary traffic source: ${topSources[0].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[0].visit_count).toLocaleString()} visits, ${parseFloat(topSources[0].percentage).toFixed(1)}% of total traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      if (topSources[1]) {
        pdf.text(`• Secondary traffic source: ${topSources[1].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[1].visit_count).toLocaleString()} visits, ${parseFloat(topSources[1].percentage).toFixed(1)}% of total traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      if (topSources[2]) {
        pdf.text(`• Third largest source: ${topSources[2].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[2].visit_count).toLocaleString()} visits, ${parseFloat(topSources[2].percentage).toFixed(1)}% of total traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      // Calculate diversity metrics
      const topThreePercentage = topSources.slice(0, 3).reduce((sum, source) => sum + parseFloat(source.percentage), 0);
      pdf.text(`• Top 3 sources account for ${topThreePercentage.toFixed(1)}% of total traffic`, margin, yPosition);
      yPosition += 8;
    
      // Traffic diversity assessment
      if (topThreePercentage > 80) {
        pdf.text(`• Traffic concentration: High (heavily dependent on top sources)`, margin, yPosition);
      } else if (topThreePercentage > 60) {
        pdf.text(`• Traffic concentration: Moderate (balanced traffic distribution)`, margin, yPosition);
      } else {
        pdf.text(`• Traffic concentration: Low (well-diversified traffic sources)`, margin, yPosition);
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
      pdf.text(`Data Source: ${isSampleData ? 'Sample Data' : 'CSV Upload'}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Sources Analyzed: ${totalSources}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Visits: ${totalVisits.toLocaleString()}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Charts Included: Pie Chart (percentage distribution), Bar Chart (visit counts)`, margin, yPosition);
      
      if (isSampleData) {
        yPosition += 10;
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'italic');
        pdf.setTextColor(255, 0, 0);
        pdf.text('Note: This report was generated using sample data for demonstration purposes.', margin, yPosition);
        pdf.setTextColor(0, 0, 0);
      }
    
      // Save PDF with descriptive filename (enhanced with sample data support)
      pdf.save(`TrafAnalyz_Traffic_Sources_Report_${isSampleData ? 'Sample_' : ''}${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);
    
      // Log the PDF export into the database (enhanced from current)
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported traffic sources comprehensive report with both pie and bar charts as PDF (uploadId: ${uploadId}, sample: ${isSampleData})`
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