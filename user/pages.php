<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Set page variables for header
$title = "Top Pages";
$active_page = "pages";

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
</head>
<body>
  <div class="container user-pages-container">
    <?php include 'user_header.php'; ?>
    
    <main>
      <h2>Top Pages Dashboard</h2>

      <?php if ($dataQuality['source_type'] === 'estimated'): ?>
        <div class="data-quality-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <h4 style="margin: 0 0 10px 0; color: #856404;">📊 Data Quality Notice</h4>
          <p style="margin: 0; color: #856404;">
            <?php if ($dataQuality['estimation_method'] === 'sessions_70_percent_rule'): ?>
              <strong>Estimated Unique Visitors:</strong> Your CSV doesn't contain unique visitor data. We've estimated it as 70% of sessions based on industry averages.
            <?php elseif ($dataQuality['estimation_method'] === 'sessions_60_percent_rule'): ?>
              <strong>Rough Estimate:</strong> Limited data available. Unique visitors estimated as 60% of available session data.
            <?php endif ?>
            <br><small>💡 For more accurate data, upload a CSV with both page views and unique visitor metrics.</small>
          </p>
        </div>
        <?php elseif ($dataQuality['estimation_method'] === 'sessions_as_page_views'): ?>
        <div class="data-quality-notice" style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <h4 style="margin: 0 0 10px 0; color: #0c5460;">ℹ️ Data Source Information</h4>
          <p style="margin: 0; color: #0c5460;">
            <strong>Using Sessions as Page Views:</strong> Your CSV contains session data which we're using as page view metrics.
            <br><small>This provides accurate relative comparisons between traffic sources.</small>
          </p>
        </div>
      <?php endif ?>

      <section class="user-chart-section">
        <h3>Most Visited Pages</h3>
        <div class="user-chart-container" id="chartContainer">
          <canvas id="pagesChart"></canvas>
        </div>
        <div style="margin-top: 10px;">
          <button onclick="exportChartToPDF()" class="export-btn pdf">
            <span class="icon">📄</span>
            <span class="text">Export Chart to PDF</span>
          </button>
        </div>
      </section>
      
      <section class="user-data-table-section">
        <h3>Top Pages Detail</h3>
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
          <button onclick="exportTableToCSV()" class="export-btn csv">
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
    const pagesData = <?php echo json_encode($pagesData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    
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
            text: 'Most Visited Pages'
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

    // Export table to CSV
    function exportTableToCSV() {
      const table = document.getElementById("pagesTable");
      let csv = [];
      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText}"`);
        csv.push(cols.join(","));
      }
      
      const blob = new Blob([csv.join("\n")], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "top_pages_table.csv";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);

      // Log export in DB
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=CSV&description=Exported top pages table data (uploadId: ${uploadId})`
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
    function exportChartToPDF() {
      const chartContainer = document.getElementById('chartContainer');
      
      html2canvas(chartContainer).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth - 20, pdfHeight);
        pdf.save('top_pages_chart.pdf');

        // Log the PDF export into the database
        fetch('log_export.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `exportType=PDF&description=Exported top pages chart as PDF (uploadId: ${uploadId})`
        }).then(response => response.json())
          .then(data => {
            if (!data.success) {
              console.warn('Export log failed:', data.message);
            }
          })
          .catch(error => {
            console.error('Error logging export:', error);
          });
      }).catch(err => {
        console.error('Error generating PDF:', err);
        alert('Error generating PDF. Please try again.');
      });
    }
  </script>
</body>
</html>