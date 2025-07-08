<?php
require_once '../auth/flexible_auth.php';
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

// UPDATED: Check user role and adjust navigation accordingly
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$backUrl = $isAdmin ? '../admin/upload_sample_data.php' : 'index.php';

// Set page variables for header
$title = "Top Pages";
$active_page = "pages";

// Get uploadId using sample-aware function
$uploadId = getCurrentUploadId($conn, $_SESSION['user_id']);

// Get sample data notice
$sampleNotice = getSampleDataNotice();

// Get top pages data
$pagesData = getTopVisitedPages($conn, 10);

// Get data quality information
$dataQuality = $_SESSION['pages_data_quality'] ?? [
    'source_type' => 'unknown',
    'estimation_method' => null,
    'confidence_level' => 'high'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Top Pages - Web Traffic Analysis Dashboard</title>
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
        color: #d63384;
    }

    .notice-content span {
        flex: 1;
        font-weight: 500;
    }

    .notice-content .btn {
        padding: 8px 16px;
        font-size: 0.9em;
        border: 1px solid #d63384;
        background: #d63384;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .notice-content .btn:hover {
        background: #b02a5b;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .notice-content {
            flex-direction: column;
            text-align: center;
        }
    }

    .admin-notice {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        padding: 15px 20px;
        margin: 20px 0;
        border-radius: 8px;
        color: #fff;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
    }

    .admin-notice .btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: #fff;
    }

    .admin-notice .btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }
  </style>
</head>
<body>
  <div class="container">
    <?php 
    // UPDATED: Use appropriate header based on user role
    if ($isAdmin) {
        echo '<header style="background: #343a40; color: #fff; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <h1 style="margin: 0; font-size: 1.5em;"><i class="fas fa-file-alt"></i> Top Pages - Admin View</h1>
                </div>
              </header>';
    } else {
        include 'user_header.php';
    }
    ?>
    
    <main>
			<section class="user-section">
      		<h2>Top Pages Dashboard</h2>

      		<!-- Sample Data Notice -->
      		<?php if ($sampleNotice['is_sample']): ?>
      		  <div class="<?php echo $isAdmin ? 'admin-notice' : 'sample-data-notice'; ?>">
      		    <div class="notice-content">
      		      <i class="fas fa-vial"></i>
      		      <span><?php echo $sampleNotice['message']; ?></span>
      		      <?php if (!$isAdmin): ?>
      		          <?php echo $sampleNotice['action']; ?>
      		      <?php else: ?>
      		          <a href="<?php echo $backUrl; ?>" class="btn">Back to Admin Panel</a>
      		      <?php endif; ?>
      		    </div>
      		  </div>
      		<?php endif; ?>
								
      		<?php if ($dataQuality['source_type'] === 'estimated'): ?>
      		  <div class="data-quality-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; 		margin-bottom: 20px; border-radius: 5px;">
      		    <h4 style="margin: 0 0 10px 0; color: #856404;">📊 Data Quality Notice</h4>
      		    <p style="margin: 0; color: #856404;">
      		      <?php if ($dataQuality['estimation_method'] === 'sessions_70_percent_rule'): ?>
      		        <strong>Estimated Unique Visitors:</strong> Your CSV doesn't contain unique visitor data. We've 		estimated it as 70% of sessions based on industry averages.
      		      <?php elseif ($dataQuality['estimation_method'] === 'sessions_60_percent_rule'): ?>
      		        <strong>Rough Estimate:</strong> Limited data available. Unique visitors estimated as 60% of 		available session data.
      		      <?php endif ?>
      		      <br><>💡 For more accurate data, upload a CSV with both page views and unique visitor metrics.</		small>
      		    </p>
      		  </div>
      		  <?php elseif ($dataQuality['estimation_method'] === 'sessions_as_page_views'): ?>
      		  <div class="data-quality-notice" style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; 		margin-bottom: 20px; border-radius: 5px;">
      		    <h4 style="margin: 0 0 10px 0; color: #0c5460;">ℹ️ Data Source Information</h4>
      		    <p style="margin: 0; color: #0c5460;">
      		      <strong>Using Sessions as Page Views:</strong> Your CSV contains session data which we're using as 		page view metrics.
      		      <br><small>This provides accurate relative comparisons between traffic sources.</small>
      		    </p>
      		  </div>
      		<?php endif ?>
						
      		<div class="user-chart-section">
      		  <h3><i class="fas fa-chart-bar"></i> Most Visited Pages</h3>
      		  <div class="user-chart-container" id="chartContainer">
      		    <canvas id="pagesChart"></canvas>
      		  </div>
      		  <div style="margin-top: 10px;">
      		    <button onclick="exportChartToPDF()" class="user-export-btn pdf">
      		      <span class="icon">📄</span>
      		      <span class="text">Export Chart to PDF</span>
      		    </button>
      		  </div>
      		</div>
						
      		<div class="user-data-table-section">
      		  <h3><i class="fas fa-list-alt"></i> Top Pages Detail</h3>
      		  <table class="user-data-table" id="pagesTable">
      		    <thead>
      		      <tr>
      		        <th>Page URL</th>
      		        <th>Page Views</th>
      		        <th>Unique Visitors 
      		          <?php if ($dataQuality['source_type'] === 'estimated'): ?>
      		            <span style="font-size: 0.8em; color: #856404;">*</span>
      		          <?php endif ?>
      		        </th>
      		        <th>Views/Visitor</th>
      		      </tr>
      		    </thead>
      		    <tbody>
      		      <?php foreach ($pagesData as $page): ?>
      		      <tr>
      		        <td><?php echo htmlspecialchars($page['page_url']); ?></td>
      		        <td><?php echo number_format($page['page_views']); ?></td>
      		        <td>
      		          <?php echo number_format($page['unique_visitors']); ?>
      		          <?php if ($dataQuality['source_type'] === 'estimated'): ?>
      		            <span style="font-size: 0.8em; color: #856404;">*</span>
      		          <?php endif ?>
      		        </td>
      		        <td><?php echo round($page['page_views'] / $page['unique_visitors'], 2); ?></td>
      		      </tr>
      		      <?php endforeach; ?>
      		    </tbody>
      		  </table>
										
      		  <?php if ($dataQuality['source_type'] === 'estimated'): ?>
      		  <p style="font-size: 0.9em; color: #856404; margin-top: 10px;">
      		    <span style="font-size: 0.8em;">*</span> Estimated values based on available session data
      		  </p>
      		  <?php endif ?>
						
      		  <div style="margin-top: 10px;">
      		    <button onclick="exportTableToCSV()" class="user-export-btn csv">
      		      <span class="icon">📊</span>
      		      <span class="text">Export Table to CSV</span>
      		    </button>
      		  </div>
      		</div>
			</section>
    </main>

    <?php 
    if (!$isAdmin) {
        include 'user_footer.php';
    }
    ?>
  </div>

  <script>
    // Parse PHP data to JavaScript
    const pagesData = <?php echo json_encode($pagesData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    const isSampleData = <?php echo $sampleNotice['is_sample'] ? 'true' : 'false'; ?>;
    
    // Extract data points for Chart.js
    const pageUrls = pagesData.map(item => {
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
            text: isSampleData ? 'Most Visited Pages (Sample Data)' : 'Most Visited Pages'
          },
          legend: { display: false },
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

    function exportTableToCSV() {
      const table = document.getElementById("pagesTable");
      let csv = [];
      
      // Get table rows
      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText.trim()}"`);
        csv.push(cols.join(","));
      }
      
      // Create and download CSV file
      const blob = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `top_pages_table${isSampleData ? '_sample' : ''}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    
      // Log export in DB
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=CSV&description=Exported top pages table data (uploadId: ${uploadId}, sample: ${isSampleData})`
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
      pdf.text('TrafAnalyz Top Pages Report', pageWidth/2, yPosition, { align: 'center' });

      yPosition += 15;

      // Generated info
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
      yPosition += 5;
      pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });
      
      if (isSampleData) {
        yPosition += 5;
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(255, 0, 0);
        pdf.text('⚠️ Sample Data Report', pageWidth - margin, yPosition, { align: 'right' });
        pdf.setTextColor(0, 0, 0);
      }

      yPosition += 20;

      // Pages Summary Section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Top Pages Summary', margin, yPosition);
      yPosition += 10;

      // Calculate totals
      const totalPages = pagesData.length;
      const totalPageViews = pagesData.reduce((sum, page) => sum + parseInt(page.page_views), 0);
      const totalUniqueVisitors = pagesData.reduce((sum, page) => sum + parseInt(page.unique_visitors), 0);
      const avgViewsPerVisitor = totalUniqueVisitors > 0 ? (totalPageViews / totalUniqueVisitors).toFixed(2) : 'N/A';

      // Summary info
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Total Pages Analyzed: ${totalPages}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Total Page Views: ${totalPageViews.toLocaleString()}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Total Unique Visitors: ${totalUniqueVisitors.toLocaleString()}`, margin, yPosition);
      yPosition += 8;
      pdf.text(`Average Views per Visitor: ${avgViewsPerVisitor}`, margin, yPosition);
      yPosition += 15;

      // Top Pages detailed table
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Top Pages Breakdown', margin, yPosition);
      yPosition += 10;

      const tableHeaders = ['Page URL', 'Page Views', 'Unique Visitors', 'Views/Visitor'];
      const tableData = [tableHeaders];

      // Add data rows
      pagesData.forEach(page => {
        const pageUrl = page.page_url.length > 40 ? page.page_url.substring(0, 40) + '...' : page.page_url;
        tableData.push([
          pageUrl,
          page.page_views.toLocaleString(),
          page.unique_visitors.toLocaleString(),
          (page.page_views / page.unique_visitors).toFixed(2)
        ]);
      });

      // Draw table
      pdf.setFontSize(9);
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
        pdf.text(row[2], margin + 115, yPosition);
        pdf.text(row[3], margin + 150, yPosition);

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
      pdf.text('Most Visited Pages Chart', margin, yPosition);
      yPosition += 10;

      // Check if we need a new page for the chart
      if (yPosition > 150) {
        pdf.addPage();
        yPosition = 30;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Most Visited Pages Chart', margin, yPosition);
        yPosition += 10;
      }

      // Capture chart as image
      const chartContainer = document.getElementById('chartContainer');
      const canvasImage = await html2canvas(chartContainer);
      const imageData = canvasImage.toDataURL("image/png");

      // Add chart to PDF
      const chartWidth = pageWidth - (margin * 2);
      const chartHeight = 120;
      pdf.addImage(imageData, 'PNG', margin, yPosition, chartWidth, chartHeight);

      yPosition += chartHeight + 15;

      // Key Insights section
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Insights', margin, yPosition);
      yPosition += 10;

      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');

      // Find top 3 pages
      const sortedPages = [...pagesData].sort((a, b) => parseInt(b.page_views) - parseInt(a.page_views));
      const topPages = sortedPages.slice(0, 3);

      if (topPages[0]) {
        const topPageUrl = topPages[0].page_url.length > 50 ? topPages[0].page_url.substring(0, 50) + '...' : topPages[0].page_url;
        pdf.text(`• Most visited page: ${topPageUrl}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${topPages[0].page_views.toLocaleString()} views, ${topPages[0].unique_visitors.toLocaleString()} unique visitors)`, margin + 5, yPosition);
        yPosition += 8;
      }

      if (topPages[1]) {
        const secondPageUrl = topPages[1].page_url.length > 50 ? topPages[1].page_url.substring(0, 50) + '...' : topPages[1].page_url;
        pdf.text(`• Second most visited: ${secondPageUrl}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${topPages[1].page_views.toLocaleString()} views, ${topPages[1].unique_visitors.toLocaleString()} unique visitors)`, margin + 5, yPosition);
        yPosition += 8;
      }

      if (topPages[2]) {
        const thirdPageUrl = topPages[2].page_url.length > 50 ? topPages[2].page_url.substring(0, 50) + '...' : topPages[2].page_url;
        pdf.text(`• Third most visited: ${thirdPageUrl}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${topPages[2].page_views.toLocaleString()} views, ${topPages[2].unique_visitors.toLocaleString()} unique visitors)`, margin + 5, yPosition);
        yPosition += 8;
      }

      // Add data quality notice if applicable
      const dataQuality = <?php echo json_encode($dataQuality); ?>;
      if (dataQuality.source_type === 'estimated') {
        yPosition += 5;
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'italic');
        pdf.text('* Note: Some unique visitor data has been estimated based on available session data.', margin, yPosition);
        yPosition += 10;
      }

      yPosition += 5;

      // Report Information section
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Report Information', margin, yPosition);
      yPosition += 8;

      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(10);
      pdf.text(`Upload ID: ${uploadId || 'N/A'}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Report Type: Top Pages Analysis`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Data Source: ${isSampleData ? 'Sample Data' : 'CSV Upload'}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Pages Analyzed: ${totalPages}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Page Views: ${totalPageViews.toLocaleString()}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Unique Visitors: ${totalUniqueVisitors.toLocaleString()}`, margin, yPosition);

      if (isSampleData) {
        yPosition += 10;
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'italic');
        pdf.setTextColor(255, 0, 0);
        pdf.text('Note: This report was generated using sample data for demonstration purposes.', margin, yPosition);
        pdf.setTextColor(0, 0, 0);
      }

      // Save PDF with descriptive filename
      pdf.save(`TrafAnalyz_Top_Pages_Report_${isSampleData ? 'Sample_' : ''}${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);

      // Log the PDF export into the database
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported top pages comprehensive report as PDF (uploadId: ${uploadId}, sample: ${isSampleData})`
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