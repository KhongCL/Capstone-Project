<?php
require_once '../auth/flexible_auth.php';
require_once '../config.php';
include '../functions.php';

// Add debugging at the top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Check if this is a redirect after successful upload
if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1') {
    // Force clear any remaining sample data session variables
    if (isset($_SESSION['using_sample_data'])) {
        error_log("OVERVIEW: Clearing remaining sample data session after upload");
        unset($_SESSION['using_sample_data']);
        unset($_SESSION['sample_upload_id']);
        
        // Clear cached data
        unset($_SESSION['cached_metrics']);
        unset($_SESSION['cached_traffic_sources']);
        unset($_SESSION['pages_data_quality']);
    }
    
    // Show success message
    $uploadMessage = [
        'type' => 'success',
        'message' => '🎉 Upload completed successfully! You\'re now viewing your own data.'
    ];
    
    // Redirect to clean URL to remove the parameter
    header('Location: overview.php');
    exit();
}

// Set page variables for header
$title = "Overview Dashboard";
$active_page = "overview";

error_log("=== OVERVIEW PAGE DEBUG ===");
error_log("Session latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'NOT SET'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Get uploadId using sample-aware function
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
  <style>
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
        // For admin users, create a minimal header WITHOUT back button
        echo '<header style="background: #343a40; color: #fff; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <h1 style="margin: 0; font-size: 1.5em;"><i class="fas fa-chart-line"></i> Sample Data Preview - Admin View</h1>
                </div>
              </header>';
    } else {
        include 'user_header.php';
    }
    ?>

    <main>
			<section class="user-section">
      	<h2>Overview Dashboard</h2>

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

				<div class="overview-section">
					<div class="user-metric">
							<div class="user-metric-card">
      	  		    <h4>Total Page Views</h4>
      	  		    <p class="user-metric-value"><?php echo number_format($metrics		['total_page_views']); ?></p>
      	  		</div>
      	  		<div class="user-metric-card">
      	  		    <h4>Unique Visitors</h4>
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
      	  		        <small style="color: #666; font-size: 0.8em;">Data not available in uploaded 		CSV</small>
      	  		    <?php endif; ?>
      	  		</div>
      	  		<div class="user-metric-card">
      	  		    <h4>Avg. Session Duration</h4>
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
      	  		        <small style="color: #666; font-size: 0.8em;">Data not available in uploaded 		CSV</small>
      	  		    <?php endif; ?>
      	  		</div>
      	  		<div class="user-metric-card">
      	  		    <h4>Bounce Rate</h4>
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
      	  		        <small style="color: #666; font-size: 0.8em;">Data not available in uploaded 		CSV</small>
      	  		    <?php endif; ?>
      	  		</div>
						</div>
									
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

				</div>

				<div class="user-chart-section">
      			<h3><i class="fas fa-chart-line"></i> Website Traffic Over Time</h3>
      			<div class="user-chart-container">
      			  <canvas id="trafficChart"></canvas>
      			</div>
      			<div class="user-chart-controls">
      			  <button class="btn btn-small" data-interval="day">Daily</button>
      			  <button class="btn btn-small" data-interval="month">Monthly</button>
      			</div>
      	</div>
								

								
      	  <h3><i class="fas fa-sticky-note"></i> Annotations</h3>
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
      	    <button type="submit" class="btn">Save Annotation</button>
      	    <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear</button>
      	  </form>
      	  <div id="annotationsList" class="user-annotations-list"></div>
			</section>
    </main>

    <?php 
    // UPDATED: Only include footer for regular users
    if (!$isAdmin) {
        include 'user_footer.php';
    }
    ?>
  </div>

  <script>
    // Only declare once
    const trafficData = <?php echo json_encode($trafficData); ?>;
    const uploadId = <?php echo $uploadId ? $uploadId : 'null'; ?>;
    const isSampleData = <?php echo $sampleNotice['is_sample'] ? 'true' : 'false'; ?>;

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
                      <button class="btn btn-small user-annotation-edit" onclick="editAnnotation(${item.id})">Edit</button>
                      <button class="btn btn-small user-annotation-delete" onclick="deleteAnnotation(${item.id})">Delete</button>
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
        // Show confirmation dialog before deleting
        if (!confirm('Are you sure you want to delete this annotation? This action cannot be undone.')) {
            return; // User cancelled, don't proceed with deletion
        }
        
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
        
      const totalPageViews = metricCards[0].querySelector('.user-metric-value').textContent.trim().replace(/,/g, '');
      const uniqueVisitorsElement = metricCards[1].querySelector('.user-metric-value');
      const uniqueVisitors = uniqueVisitorsElement.textContent.trim().includes('N/A') ? 'N/A' : uniqueVisitorsElement.textContent.trim().replace(/,/g, '');
        
      const avgSessionElement = metricCards[2].querySelector('.user-metric-value');
      const avgSessionDuration = avgSessionElement.textContent.trim().includes('N/A') ? 'N/A' : avgSessionElement.textContent.trim();
        
      const bounceRateElement = metricCards[3].querySelector('.user-metric-value');
      const bounceRate = bounceRateElement.textContent.trim().includes('N/A') ? 'N/A' : bounceRateElement.textContent.trim();
        
      let csv = 'Metric,Value\n';
      csv += `"Total Page Views","${totalPageViews}"\n`;
      csv += `"Unique Visitors","${uniqueVisitors}"\n`;
      csv += `"Average Session Duration","${avgSessionDuration}"\n`;
      csv += `"Bounce Rate","${bounceRate}"\n`;
        
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'overview_key_metrics.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    async function exportToPDF() {
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
      pdf.text('Key Metrics Summary', margin, yPosition);
      yPosition += 10;
        
      // Get metric values from the page
      const metricCards = document.querySelectorAll('.user-metric-card');
      const totalPageViews = metricCards[0].querySelector('.user-metric-value').textContent.trim();
      const uniqueVisitors = metricCards[1].querySelector('.user-metric-value').textContent.trim();
      const avgSessionDuration = metricCards[2].querySelector('.user-metric-value').textContent.trim();
      const bounceRate = metricCards[3].querySelector('.user-metric-value').textContent.trim();
        
      // Metrics table
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
        
      // Create metrics table with background
      const metricsData = [
        ['Metric', 'Value'],
        ['Total Page Views', totalPageViews],
        ['Unique Visitors', uniqueVisitors],
        ['Average Session Duration', avgSessionDuration],
        ['Bounce Rate', bounceRate]
      ];
  
      metricsData.forEach((row, index) => {
        const isHeader = index === 0;
        if (isHeader) {
          pdf.setFont('helvetica', 'bold');
          pdf.setFillColor(240, 240, 240);
          pdf.rect(margin, yPosition - 5, pageWidth - (margin * 2), 8, 'F');
        } else {
          pdf.setFont('helvetica', 'normal');
        }
    
        pdf.text(row[0], margin + 2, yPosition);
        pdf.text(row[1], margin + 80, yPosition);
    
        // Draw table lines
        pdf.line(margin, yPosition + 2, pageWidth - margin, yPosition + 2);
    
        yPosition += 8;
      });
  
      yPosition += 15;
  
      // Traffic Chart section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Website Traffic Over Time Chart', margin, yPosition);
      yPosition += 10;
  
      // Check if we need a new page for the chart
      if (yPosition > 150) {
        pdf.addPage();
        yPosition = 30;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Website Traffic Over Time Chart', margin, yPosition);
        yPosition += 10;
      }
  
      // Capture chart as image
      const chartContainer = document.querySelector('.user-chart-container');
      const canvasImage = await html2canvas(chartContainer);
      const imageData = canvasImage.toDataURL("image/png");
  
      // Add chart to PDF
      const chartWidth = pageWidth - (margin * 2);
      const chartHeight = 120;
      pdf.addImage(imageData, 'PNG', margin, yPosition, chartWidth, chartHeight);
  
      yPosition += chartHeight + 15;
  
      // Traffic Data Table
      if (trafficData && trafficData.length > 0) {
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Traffic Data Breakdown', margin, yPosition);
        yPosition += 10;
    
        // Check if we need a new page
        if (yPosition > 200) {
          pdf.addPage();
          yPosition = 30;
          pdf.setFontSize(14);
          pdf.setFont('helvetica', 'bold');
          pdf.text('Traffic Data Breakdown', margin, yPosition);
          yPosition += 10;
        }
    
        const tableHeaders = ['Date', 'Page Views', 'Unique Visitors'];
        const tableData = [tableHeaders];
    
        // Add data rows (limit to first 15 to avoid overflow)
        trafficData.slice(0, 15).forEach(item => {
          tableData.push([
            item.time_period,
            parseInt(item.page_views).toLocaleString(),
            parseInt(item.unique_visitors).toLocaleString()
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
          pdf.text(row[1], margin + 60, yPosition);
          pdf.text(row[2], margin + 120, yPosition);
      
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
      }
  
      // Key Insights section
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Insights', margin, yPosition);
      yPosition += 10;
  
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
  
      // Calculate insights from traffic data
      if (trafficData && trafficData.length > 0) {
        const totalTrafficPageViews = trafficData.reduce((sum, item) => sum + parseInt(item.page_views), 0);
        const totalTrafficVisitors = trafficData.reduce((sum, item) => sum + parseInt(item.unique_visitors), 0);
        const avgPageViewsPerDay = Math.round(totalTrafficPageViews / trafficData.length);
        const avgVisitorsPerDay = Math.round(totalTrafficVisitors / trafficData.length);
    
        // Find peak traffic day
        const peakDay = trafficData.reduce((peak, current) => 
          parseInt(current.page_views) > parseInt(peak.page_views) ? current : peak
        );
    
        pdf.text(`• Peak traffic day: ${peakDay.time_period}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(peakDay.page_views).toLocaleString()} page views, ${parseInt(peakDay.unique_visitors).toLocaleString()} unique visitors)`, margin + 5, yPosition);
        yPosition += 8;
    
        pdf.text(`• Average daily page views: ${avgPageViewsPerDay.toLocaleString()}`, margin, yPosition);
        yPosition += 8;
    
        pdf.text(`• Average daily unique visitors: ${avgVisitorsPerDay.toLocaleString()}`, margin, yPosition);
        yPosition += 8;
    
        // Calculate trend (comparing first half vs second half)
        const midPoint = Math.floor(trafficData.length / 2);
        const firstHalf = trafficData.slice(0, midPoint);
        const secondHalf = trafficData.slice(midPoint);
        
        const firstHalfAvg = firstHalf.reduce((sum, item) => sum + parseInt(item.page_views), 0) / firstHalf.length;
        const secondHalfAvg = secondHalf.reduce((sum, item) => sum + parseInt(item.page_views), 0) / secondHalf.length;
        
        const trendPercentage = ((secondHalfAvg - firstHalfAvg) / firstHalfAvg * 100).toFixed(1);
        const trendDirection = trendPercentage > 0 ? 'increase' : 'decrease';
        
        pdf.text(`• Traffic trend: ${Math.abs(trendPercentage)}% ${trendDirection} in recent period`, margin, yPosition);
        yPosition += 10;
      }
  
      // Annotations section (if any exist)
      try {
        const annotations = await getAnnotations();
        if (annotations && annotations.length > 0) {
          pdf.setFontSize(14);
          pdf.setFont('helvetica', 'bold');
          pdf.text('Annotations', margin, yPosition);
          yPosition += 10;
        
          pdf.setFontSize(10);
          pdf.setFont('helvetica', 'normal');
        
          annotations.slice(0, 10).forEach(annotation => { // Limit to first 10 annotations
            pdf.text(`• ${annotation.date}: ${annotation.note}`, margin, yPosition);
            yPosition += 6;
        
            // Check if we need a new page
            if (yPosition > 250) {
              pdf.addPage();
              yPosition = 30;
            }
          });
      
          yPosition += 10;
        }
      } catch (error) {
        console.log('No annotations to include in PDF');
      }
  
      // Report Information section
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
      yPosition += 5;
      pdf.text(`Data Points: ${trafficData ? trafficData.length : 0} time periods`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Page Views: ${totalPageViews}`, margin, yPosition);
      yPosition += 5;
      pdf.text(`Total Unique Visitors: ${uniqueVisitors}`, margin, yPosition);
  
      // Save PDF with descriptive filename
      pdf.save(`TrafAnalyz_Overview_Report_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);
  
      // Log the PDF export into the database
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported overview dashboard comprehensive report as PDF (uploadId: ${uploadId})`
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