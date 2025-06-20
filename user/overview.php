<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Add debugging at the top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page variables for header
$title = "Overview Dashboard";
$active_page = "overview";

error_log("=== OVERVIEW PAGE DEBUG ===");
error_log("Session latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'NOT SET'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Check what uploads exist for this user
$userId = $_SESSION['user_id'] ?? 1;
$debugQuery = "SELECT UploadID, FileName, UploadDate, ReportType FROM CSV_UPLOAD WHERE UserID = ? ORDER BY UploadID DESC LIMIT 5";
$debugStmt = $conn->prepare($debugQuery);
$debugStmt->bind_param("i", $userId);
$debugStmt->execute();
$debugResult = $debugStmt->get_result();
error_log("Recent uploads for user $userId:");
while ($debugRow = $debugResult->fetch_assoc()) {
    error_log("  Upload ID: {$debugRow['UploadID']}, File: {$debugRow['FileName']}, Date: {$debugRow['UploadDate']}, Type: {$debugRow['ReportType']}");
}

// Check data points
$dataQuery = "SELECT COUNT(*) as count FROM PROCESSED_DATA_POINT pdp 
              JOIN CSV_UPLOAD cu ON pdp.UploadID = cu.UploadID 
              WHERE cu.UserID = ?";
$dataStmt = $conn->prepare($dataQuery);
$dataStmt->bind_param("i", $userId);
$dataStmt->execute();
$dataResult = $dataStmt->get_result();
if ($dataRow = $dataResult->fetch_assoc()) {
    error_log("Total data points for user $userId: " . $dataRow['count']);
}
error_log("=== END OVERVIEW DEBUG ===");

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
          <p class="user-metric-value"><?php echo number_format($metrics['unique_visitors']); ?></p>
        </div>
        <div class="user-metric-card">
          <h3>Avg. Session Duration</h3>
          <p class="user-metric-value"><?php echo $metrics['avg_session_duration']; ?></p>
        </div>
        <div class="user-metric-card">
          <h3>Bounce Rate</h3>
          <p class="user-metric-value"><?php echo $metrics['bounce_rate']; ?></p>
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

    Chart.register(window['chartjs-plugin-annotation']); // ✅ correct plugin registration

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
                    text: 'Website Traffic Over Time'
                },
                annotation: {
                    annotations: {} // populated later by annotation logic
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