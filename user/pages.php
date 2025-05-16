<?php
require_once '../config.php';
include '../functions.php';

// Get top pages data
$pagesData = getTopVisitedPages($conn, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Top Pages - Web Traffic Analysis Dashboard</title>
  <link rel="stylesheet" href="../styles.css" />
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
          <li><a href="traffic_sources.php">Traffic Sources</a></li>
          <li><a href="pages.php" class="active">Pages</a></li>
        </ul>
      </nav>
    </header>
    
    <main>
      <h2>Top Pages Dashboard</h2>
      
      <section class="chart-section">
        <h3>Most Visited Pages</h3>
        <div class="chart-container" id="chartContainer">
          <canvas id="pagesChart"></canvas>
        </div>
        <div style="margin-top: 10px;">
          <button onclick="exportChartToPDF()" class="export-btn pdf">
            <span class="icon">📄</span>
            <span class="text">Export Chart to PDF</span>
          </button>
        </div>
      </section>
      
      <section class="data-table-section">
        <h3>Top Pages Detail</h3>
        <table class="data-table" id="pagesTable">
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
    
    <footer>
      <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
    </footer>
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
      const blob = new Blob([csv.join("\n")], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "top_pages_table.csv";
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
      pdf.save("top_pages_chart.pdf");
    }
  </script>
</body>
</html>