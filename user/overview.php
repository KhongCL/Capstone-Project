<?php
require_once '../config.php';
include '../functions.php';

$metrics = getKeyMetrics($conn);
$trafficData = getTrafficOverTime($conn, 'day');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Overview - Web Traffic Analysis Dashboard</title>
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
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.1.0"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
  <div class="container" id="dashboard">
    <header>
      <h1>Web Traffic Analysis Dashboard</h1>
      <nav>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="overview.php" class="active">Overview</a></li>
          <li><a href="traffic_sources.php">Traffic Sources</a></li>
          <li><a href="pages.php">Pages</a></li>
        </ul>
      </nav>
    </header>

    <main>
      <h2>Overview Dashboard</h2>
      <div class="export-controls">
        <button class="export-btn csv" onclick="exportToCSV()">
          <span class="icon">📊</span>
          <span class="text">Export to CSV</span>
        </button>
        <button class="export-btn pdf" onclick="exportToPDF()">
          <span class="icon">📄</span>
          <span class="text">Export to PDF</span>
        </button>
      </div>

      <section class="metrics-grid" id="metricsSection">
        <div class="metric-card">
          <h3>Total Page Views</h3>
          <p class="metric-value"><?php echo number_format($metrics['total_page_views']); ?></p>
        </div>
        <div class="metric-card">
          <h3>Unique Visitors</h3>
          <p class="metric-value"><?php echo number_format($metrics['unique_visitors']); ?></p>
        </div>
        <div class="metric-card">
          <h3>Avg. Session Duration</h3>
          <p class="metric-value"><?php echo $metrics['avg_session_duration']; ?></p>
        </div>
        <div class="metric-card">
          <h3>Bounce Rate</h3>
          <p class="metric-value"><?php echo $metrics['bounce_rate']; ?></p>
        </div>
      </section>

      <section class="chart-section" id="chartSection">
        <h3>Website Traffic Over Time</h3>
        <div class="chart-container">
          <canvas id="trafficChart"></canvas>
        </div>
        <div class="chart-controls">
          <button class="btn btn-sm" data-interval="day">Daily</button>
          <button class="btn btn-sm" data-interval="month">Monthly</button>
        </div>
      </section>

      <section>
        <h3>📝 Annotations</h3>
        <form id="annotationForm">
          <input type="hidden" id="annotationId" />
          <label>Date: <input type="date" id="annotationDate" required /></label>
          <label>Note: <input type="text" id="annotationNote" required /></label>
          <button type="submit">Save Annotation</button>
          <button type="button" onclick="resetForm()">Clear</button>
        </form>
        <div id="annotationsList"></div>
      </section>
    </main>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
    </footer>
  </div>

  <script>
    const trafficData = <?php echo json_encode($trafficData); ?>;
    const labels = trafficData.map(item => item.time_period);
    const pageViewsData = trafficData.map(item => parseInt(item.page_views));
    const uniqueVisitorsData = trafficData.map(item => parseInt(item.unique_visitors));

    const ctx = document.getElementById('trafficChart').getContext('2d');
    const trafficChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Page Views',
            data: pageViewsData,
            borderColor: '#4c78d0',
            backgroundColor: 'rgba(76, 120, 208, 0.1)',
            tension: 0.1,
            fill: true
          },
          {
            label: 'Unique Visitors',
            data: uniqueVisitorsData,
            borderColor: '#72b966',
            backgroundColor: 'rgba(114, 185, 102, 0.1)',
            tension: 0.1,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Website Traffic Over Time'
          },
          annotation: {
            annotations: {}
          }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // Interval Switcher
    document.querySelectorAll('.chart-controls .btn').forEach(button => {
      button.addEventListener('click', function () {
        const interval = this.dataset.interval;
        fetch(`get_traffic_data.php?interval=${interval}`)
          .then(response => response.json())
          .then(data => {
            trafficChart.data.labels = data.map(item => item.time_period);
            trafficChart.data.datasets[0].data = data.map(item => parseInt(item.page_views));
            trafficChart.data.datasets[1].data = data.map(item => parseInt(item.unique_visitors));
            trafficChart.update();
            renderAnnotationsList();
          });

        document.querySelectorAll('.chart-controls .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // ========== Annotations Logic ==========
    function getAnnotations() {
      return JSON.parse(localStorage.getItem('annotations') || '[]');
    }

    function saveAnnotations(data) {
      localStorage.setItem('annotations', JSON.stringify(data));
    }

    function resetForm() {
      document.getElementById('annotationForm').reset();
      document.getElementById('annotationId').value = '';
    }

    function renderAnnotationsList() {
      const list = document.getElementById('annotationsList');
      const annotations = getAnnotations();
      list.innerHTML = '';

      annotations.forEach((item, index) => {
        const div = document.createElement('div');
        div.innerHTML = `<strong>${item.date}</strong>: ${item.note}
          <button onclick="editAnnotation(${index})">Edit</button>
          <button onclick="deleteAnnotation(${index})">Delete</button>`;
        list.appendChild(div);
      });

      trafficChart.options.plugins.annotation.annotations = {};
      annotations.forEach((item, i) => {
        trafficChart.options.plugins.annotation.annotations['line' + i] = {
          type: 'line',
          scaleID: 'x',
          value: item.date,
          borderColor: 'red',
          borderWidth: 2,
          label: {
            content: item.note,
            enabled: true,
            position: 'top'
          }
        };
      });
      trafficChart.update();
    }

    function editAnnotation(index) {
      const annotations = getAnnotations();
      const item = annotations[index];
      document.getElementById('annotationId').value = index;
      document.getElementById('annotationDate').value = item.date;
      document.getElementById('annotationNote').value = item.note;
    }

    function deleteAnnotation(index) {
      const annotations = getAnnotations();
      annotations.splice(index, 1);
      saveAnnotations(annotations);
      renderAnnotationsList();
      resetForm();
    }

    document.getElementById('annotationForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const id = document.getElementById('annotationId').value;
      const date = document.getElementById('annotationDate').value;
      const note = document.getElementById('annotationNote').value;
      const annotations = getAnnotations();

      if (id === '') {
        annotations.push({ date, note });
      } else {
        annotations[id] = { date, note };
      }

      saveAnnotations(annotations);
      renderAnnotationsList();
      resetForm();
    });

    renderAnnotationsList();

    // ========== Export Functions ==========

    function exportToCSV() {
      let csv = 'Time Period,Page Views,Unique Visitors\n';
      trafficData.forEach(row => {
        csv += `${row.time_period},${row.page_views},${row.unique_visitors}\n`;
      });

      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.setAttribute('hidden', '');
      a.setAttribute('href', url);
      a.setAttribute('download', 'traffic_data.csv');
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    function exportToPDF() {
      html2canvas(document.getElementById('dashboard')).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('dashboard.pdf');
      });
    }
  </script>
</body>
</html>
