<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Set page variables for header
$title = "Top Pages";
$active_page = "pages";

// Get top pages data
$pagesData = getTopVisitedPages($conn, 10);
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
      
      const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "top_pages.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    // Export chart to PDF
    function exportChartToPDF() {
      const canvas = document.getElementById('pagesChart');
      const { jsPDF } = window.jspdf;
      
      html2canvas(canvas).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF();
        const imgWidth = 210;
        const pageHeight = 295;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        
        let position = 0;
        
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
        
        while (heightLeft >= 0) {
          position = heightLeft - imgHeight;
          pdf.addPage();
          pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
          heightLeft -= pageHeight;
        }
        
        pdf.save('pages_chart.pdf');
      }).catch(err => {
        console.error('Error generating PDF:', err);
        alert('Error generating PDF. Please try again.');
      });
    }
  </script>
</body>
</html>