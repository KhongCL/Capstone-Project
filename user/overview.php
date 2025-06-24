<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set page variables for header
$title = "Overview";
$active_page = "overview";

// Get uploadId - check for sample data first
$uploadId = getCurrentUploadId($conn, $_SESSION['user_id']);

// Get sample data notice
$sampleNotice = getSampleDataNotice();

$metrics = getKeyMetrics($conn, $uploadId);
$trafficData = getTrafficOverTime($conn, 'day', $uploadId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Overview - Web Traffic Analysis Dashboard</title>
  <link rel="stylesheet" href="../styles.css" />
  <link rel="stylesheet" href="user_style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0/dist/chartjs-plugin-annotation.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>

<body>
  <div class="container user-overview-container" id="dashboard">
    <?php include 'user_header.php'; ?>

    <main>
      <h2>Overview Dashboard</h2>
      
      <!-- Sample Data Notice -->
      <?php if ($sampleNotice['is_sample']): ?>
        <div class="sample-data-notice">
          <div class="notice-content">
            <i class="fas fa-vial"></i>
            <span><?php echo $sampleNotice['message']; ?></span>
            <?php echo $sampleNotice['action']; ?>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="user-export-controls">
        <button class="user-export-btn csv" onclick="exportToCSV()">
          <span class="icon">📊</span>
          <span class="text">Export to CSV</span>
        </button>
        <button class="user-export-btn pdf" onclick="exportToPDF()">
          <span class="icon">📄</span>
          <span class="text">Export to PDF</span>
        </button>
      </div>

      <section class="user-metrics-grid" id="metricsSection">
        <div class="user-metric-card">
            <h3>Total Page Views</h3>
            <p class="user-metric-value"><?php echo number_format($metrics['total_page_views']); ?></p>
        </div>
        <div class="user-metric-card">
            <h3>Unique Visitors</h3>
            <p class="user-metric-value">
                <?php 
                if ($metrics['unique_visitors'] === 'N/A') {
                    echo '<span style="color: #999; font-size: 0.9em;">N/A</span>';
                } else {
                    echo number_format($metrics['unique_visitors']);
                }
                ?>
            </p>
            <?php if ($metrics['unique_visitors'] === 'N/A'): ?>
                <small style="color: #666; font-size: 0.8em;">Data not available in uploaded CSV</small>
            <?php endif; ?>
        </div>
        <div class="user-metric-card">
            <h3>Avg. Session Duration</h3>
            <p class="user-metric-value">
                <?php 
                if ($metrics['avg_session_duration'] === 'N/A') {
                    echo '<span style="color: #999; font-size: 0.9em;">N/A</span>';
                } else {
                    echo $metrics['avg_session_duration'];
                }
                ?>
            </p>
            <?php if ($metrics['avg_session_duration'] === 'N/A'): ?>
                <small style="color: #666; font-size: 0.8em;">Data not available in uploaded CSV</small>
            <?php endif; ?>
        </div>
          <div class="user-metric-card">
              <h3>Bounce Rate</h3>
              <p class="user-metric-value">
                  <?php 
                  if ($metrics['bounce_rate'] === 'N/A') {
                      echo '<span style="color: #999; font-size: 0.9em;">N/A</span>';
                  } else {
                      echo $metrics['bounce_rate'] . '%';
                  }
                  ?>
              </p>
              <?php if ($metrics['bounce_rate'] === 'N/A'): ?>
                  <small style="color: #666; font-size: 0.8em;">Data not available in uploaded CSV</small>
              <?php endif; ?>
          </div>
    </section>

      <section class="user-chart-section" id="chartSection">
        <h3>Website Traffic Over Time</h3>
        <div class="user-chart-container">
          <canvas id="trafficChart"></canvas>
        </div>
        <div class="user-chart-controls">
          <button class="btn btn-sm" data-interval="day">Daily</button>
          <button class="btn btn-sm" data-interval="month">Monthly</button>
        </div>
      </section>

      <section class="user-annotation-section">
        <h3>📝 Annotations</h3>
        <form id="annotationForm" class="user-annotation-form">
          <input type="hidden" id="annotationId" />
          <label>
            Date:
            <input type="date" id="annotationDate" required />
          </label>
          <label>
            Note:
            <input type="text" id="annotationNote" required placeholder="Enter annotation note..." />
          </label>
          <button type="submit">Save Annotation</button>
          <button type="button" onclick="resetForm()">Clear</button>
        </form>
        <div id="annotationsList" class="user-annotations-list"></div>
      </section>
    </main>

    <?php include 'user_footer.php'; ?>
  </div>

  <script>
    // Only declare once
    const trafficData = <?php echo json_encode($trafficData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    const isSampleData = <?php echo $sampleNotice['is_sample'] ? 'true' : 'false'; ?>;

    // Rest of your existing JavaScript remains the same
    Chart.register(window['chartjs-plugin-annotation']);

    const ctx = document.getElementById('trafficChart').getContext('2d');
    const trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trafficData.map(item => item.time_period),
            datasets: [
                {
                    label: 'Page Views',
                    data: trafficData.map(item => parseInt(item.page_views)),
                    borderColor: '#4c78d0',
                    backgroundColor: 'rgba(76, 120, 208, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Unique Visitors',
                    data: trafficData.map(item => parseInt(item.unique_visitors)),
                    borderColor: '#72b966',
                    backgroundColor: 'rgba(114, 185, 102, 0.1)',
                    tension: 0.1,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: isSampleData ? 'Website Traffic Over Time (Sample Data)' : 'Website Traffic Over Time'
                },
                annotation: {
                    annotations: {}
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });


    // Interval Switcher
    document.querySelectorAll('.user-chart-controls .btn').forEach(button => {
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

        document.querySelectorAll('.user-chart-controls .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // ========== Annotations Logic ==========
    function getAnnotations() {
        return fetch(`get_annotations.php?uploadId=${uploadId}`)
            .then(response => response.json())
            .catch(error => {
                console.error('Error fetching annotations:', error);
                return [];
            });
    }

    function saveAnnotations(data) {
      localStorage.setItem('annotations', JSON.stringify(data));
    }

    function resetForm() {
      document.getElementById('annotationForm').reset();
      document.getElementById('annotationId').value = '';
    }

    async function renderAnnotationsList() {
      const list = document.getElementById('annotationsList');
      const annotations = await getAnnotations();
      list.innerHTML = '';
      
      // Group annotations by date
      const annotationsByDate = {};
      annotations.forEach(item => {
          if (!annotationsByDate[item.date]) {
              annotationsByDate[item.date] = [];
          }
          annotationsByDate[item.date].push(item);
      });
      
      // Display annotations grouped by date
      Object.entries(annotationsByDate).forEach(([date, items]) => {
          items.forEach((item, index) => {
              const div = document.createElement('div');
              div.className = 'user-annotation-item';
              div.innerHTML = `
                  <div>
                      <strong>${item.date} (${index + 1}/5)</strong>: ${item.note}
                  </div>
                  <div class="user-annotation-actions">
                      <button class="user-annotation-edit" onclick="editAnnotation(${item.id})">Edit</button>
                      <button class="user-annotation-delete" onclick="deleteAnnotation(${item.id})">Delete</button>
                  </div>
              `;
              list.appendChild(div);
          });
      });
      
      // Update chart annotations
      trafficChart.options.plugins.annotation.annotations = {};
      Object.entries(annotationsByDate).forEach(([date, items]) => {
          items.forEach((item, i) => {
              const offset = i * 20; // Offset each annotation vertically
              trafficChart.options.plugins.annotation.annotations[`line${item.id}`] = {
                  type: 'line',
                  scaleID: 'x',
                  value: item.date,
                  borderColor: 'red',
                  borderWidth: 2,
                  label: {
                      content: `${i + 1}. ${item.note}`,
                      enabled: true,
                      position: 'top',
                      yAdjust: offset // Adjust vertical position to prevent overlap
                  }
              };
          });
      });
      trafficChart.update();
  }

    async function editAnnotation(id) {
        try {
            const annotations = await getAnnotations();
            const item = annotations.find(annotation => annotation.id === id);

            if (item) {
                document.getElementById('annotationId').value = item.id;
                document.getElementById('annotationDate').value = item.date;
                document.getElementById('annotationNote').value = item.note;
            }
        } catch (error) {
            console.error('Error editing annotation:', error);
        }
    }

    async function deleteAnnotation(id) {
        try {
            const response = await fetch('delete_annotation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `annotationId=${id}&uploadId=${uploadId}`
            });
          
            const result = await response.json();
            if (result.success) {
                await renderAnnotationsList(); // Refresh the list after successful deletion
                resetForm();
            } else {
                alert('Error deleting annotation: ' + result.message);
            }
        } catch (error) {
            console.error('Error deleting annotation:', error);
            alert('Error deleting annotation');
        }
    }

    console.log('Upload ID:', uploadId); // Debug log
    document.getElementById('annotationForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        
        if (!uploadId) {
            alert('No CSV file has been uploaded yet. Please upload a file first.');
            return;
        }
      
        const id = document.getElementById('annotationId').value;
        const date = document.getElementById('annotationDate').value;
        const note = document.getElementById('annotationNote').value;
        
        try {
            // Check number of annotations for this date
            const annotations = await getAnnotations();
            const sameDataAnnotations = annotations.filter(annotation => 
                annotation.date === date
            );
          
            if (sameDataAnnotations.length >= 5 && !id) {
                alert('Maximum of 5 annotations per date allowed. Please edit existing annotations instead.');
                return;
            }
          
            const formData = new FormData();
            formData.append('annotationId', id);
            formData.append('uploadId', uploadId);
            formData.append('dataDate', date);
            formData.append('note', note);
            
            const response = await fetch('save_annotation.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                await renderAnnotationsList();
                resetForm();
            } else {
                alert(result.message || 'Error saving annotation');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving annotation');
        }
    });

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Set first button as active
        document.querySelector('.user-chart-controls .btn').classList.add('active');
        
        // Initialize annotations
        renderAnnotationsList();
    });

    // ========== Export Functions ==========

    function exportToCSV() {
      const metricCards = document.querySelectorAll('.user-metric-card');
        
      // Extract values more carefully, handling N/A cases
      const totalPageViews = metricCards[0].querySelector('.user-metric-value').textContent.trim().replace(/,/g, '');
      const uniqueVisitorsElement = metricCards[1].querySelector('.user-metric-value');
      const uniqueVisitors = uniqueVisitorsElement.textContent.trim().includes('N/A') ? 'N/A' : uniqueVisitorsElement.textContent.trim().replace(/,/g, '');
        
      const avgSessionElement = metricCards[2].querySelector('.user-metric-value');
      const avgSessionDuration = avgSessionElement.textContent.trim().includes('N/A') ? 'N/A' : avgSessionElement.textContent.trim();
        
      const bounceRateElement = metricCards[3].querySelector('.user-metric-value');
      const bounceRate = bounceRateElement.textContent.trim().includes('N/A') ? 'N/A' : bounceRateElement.textContent.trim();
        
      // CSV generation with proper formatting
      let csv = 'Metric,Value\n';
      csv += `"Total Page Views","${totalPageViews}"\n`;
      csv += `"Unique Visitors","${uniqueVisitors}"\n`;
      csv += `"Average Session Duration","${avgSessionDuration}"\n`;
      csv += `"Bounce Rate","${bounceRate}"\n`;
        
      // Trigger download
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'overview_key_metrics.csv';
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
        body: `exportType=CSV&description=Exported overview key metrics (uploadId: ${uploadId})`
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

    function exportToPDF() {
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
        
      // Get username from session (you may need to pass this from PHP)
      const username = '<?php echo $_SESSION['username'] ?? 'Unknown User'; ?>';
        
      // PDF styling
      const pageWidth = pdf.internal.pageSize.getWidth();
      const margin = 20;
      let yPosition = 30;
        
      // Header
      pdf.setFontSize(20);
      pdf.setFont('helvetica', 'bold');
      pdf.text('TrafAnalyz Overview Dashboard Report', pageWidth/2, yPosition, { align: 'center' });
        
      yPosition += 15;
        
      // Generated info
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
      yPosition += 5;
      pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });
        
      yPosition += 20;
        
      // Key Metrics Section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Metrics', margin, yPosition);
      yPosition += 10;
        
      // Get metric values
      const metricCards = document.querySelectorAll('.user-metric-card');
      const totalPageViews = metricCards[0].querySelector('.user-metric-value').textContent.trim();
      const uniqueVisitors = metricCards[1].querySelector('.user-metric-value').textContent.trim();
      const avgSessionDuration = metricCards[2].querySelector('.user-metric-value').textContent.trim();
      const bounceRate = metricCards[3].querySelector('.user-metric-value').textContent.trim();
        
      // Metrics table
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
        
      const metrics = [
        ['Metric', 'Value'],
        ['Total Page Views', totalPageViews],
        ['Unique Visitors', uniqueVisitors],
        ['Average Session Duration', avgSessionDuration],
        ['Bounce Rate', bounceRate]
      ];
      
      // Draw table
      metrics.forEach((row, index) => {
        const isHeader = index === 0;
        if (isHeader) {
          pdf.setFont('helvetica', 'bold');
          pdf.setFillColor(240, 240, 240);
          pdf.rect(margin, yPosition - 5, pageWidth - (margin * 2), 8, 'F');
        } else {
          pdf.setFont('helvetica', 'normal');
        }
        
        pdf.text(row[0], margin + 5, yPosition);
        pdf.text(row[1], margin + 80, yPosition);
        
        // Draw table lines
        pdf.line(margin, yPosition + 2, pageWidth - margin, yPosition + 2);
        
        yPosition += 10;
      });
      
      yPosition += 10;
      
      // Chart section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Over Time Chart', margin, yPosition);
      yPosition += 10;
      
      // Capture chart as image
      const chartCanvas = document.getElementById('trafficChart');
      const chartImage = chartCanvas.toDataURL('image/png');
      
      // Add chart to PDF
      const chartWidth = pageWidth - (margin * 2);
      const chartHeight = 100; // Fixed height for chart
      pdf.addImage(chartImage, 'PNG', margin, yPosition, chartWidth, chartHeight);
      
      yPosition += chartHeight + 15;
      
      // Upload info section
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Report Information', margin, yPosition);
      yPosition += 8;
      
      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(10);
      pdf.text(`Upload ID: ${uploadId || 'N/A'}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Report Type: Overview Dashboard`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Data Source: CSV Upload`, margin, yPosition);
      
      // Save PDF
      pdf.save(`TrafAnalyz_Overview_Report_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);
      
      // Log the PDF export into the database
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported overview dashboard report as PDF (uploadId: ${uploadId})`
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