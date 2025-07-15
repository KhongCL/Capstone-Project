<?php

// Name: Kum Yong Jun
// Position: Developer
// TP Number: TP077408
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: traffic_sources.php
// Description: Traffic sources analysis dashboard with interactive charts, advanced filtering,
//              source selection capabilities, and comprehensive export functionality for data analysis.
// First Written On: 14 April 2025
// Edited On: 12 July 2025

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

// Check user role and adjust navigation accordingly
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$backUrl = $isAdmin ? '../admin/upload_sample_data.php' : 'index.php';

// Set page variables for header
$title = "Traffic Sources";
$active_page = "traffic_sources";

// Get uploadId using sample-aware function
$uploadId = getCurrentUploadId($conn, $_SESSION['user_id']);

// Get sample data notice (from current)
$sampleNotice = getSampleDataNotice();

// Get traffic sources data
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
    .admin-notice {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        padding: 15px 20px;
        margin: 20px 0;
        border-radius: 8px;
        color: #fff;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
    }

    .admin-notice .btn {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: white;
    }

    .admin-notice .btn:hover {
        background-color: rgba(255, 255, 255, 0.3);
    }

    /* Filter Panel Styles */
        .filter-panel-section {
            margin-bottom: 30px
        }

    .filter-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 500;
        color: var(--text-muted);
        font-size: 0.9em;
    }

    .filter-select, .filter-input {
        padding: 8px 12px;
        border: 1px solid var(--border-medium);
        border-radius: 4px;
        font-size: 0.9em;
        min-width: 150px;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .filter-btn {
        padding: 10px 20px;
        border: none;
        background-color: var(--primary-color);
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-family: inherit;
        font-size: 0.9em;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
    }

    .filter-btn.secondary {
        background-color: var(--dark-gray);
    }

    .filter-btn.secondary:hover {
        background-color: var(--hover-gray);
    }

    .source-selection {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid var(--border-light);
        border-radius: 4px;
        background-color: white;
    }

    .source-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        cursor: pointer;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }

    .source-checkbox:hover {
        background-color: var(--background-light);
    }

    .source-checkbox input[type="checkbox"] {
        margin: 0;
        cursor: pointer;
    }

    .source-checkbox label {
        margin: 0;
        cursor: pointer;
        font-size: 0.9em;
        flex: 1;
    }

    .filter-summary {
        margin-top: 10px;
        padding: 10px;
        background-color: var(--light-blue);
        border-radius: 4px;
        font-size: 0.9em;
        color: var(--text-primary);
    }

    .table-row-selected {
        background-color: var(--light-blue) !important;
    }

    .table-row-filtered {
        display: none;
    }

    .chart-legend-container {
      max-height: 400px;
      overflow-y: auto;
      overflow-x: hidden;
      padding-right: 10px;
      margin-left: 10px;
    }

    /* Custom scrollbar for better appearance */
    .chart-legend-container::-webkit-scrollbar {
        width: 8px;
    }

    .chart-legend-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .chart-legend-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .chart-legend-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Improve legend item spacing */
    .chartjs-legend {
        max-height: none !important;
    }

    .chartjs-legend ul {
        max-height: none !important;
    }

    @media (max-width: 768px) {
        .filter-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .source-selection {
            grid-template-columns: 1fr;
        }
        
        .filter-buttons {
            justify-content: center;
        }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php 
    // Use appropriate header based on user role
    if ($isAdmin) {
        echo '<header style="background-color: #343a40; color: #fff; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <h1 style="margin: 0; font-size: 1.5em;"><i class="fas fa-share-alt"></i> Traffic Sources - Admin View</h1>
                </div>
              </header>';
    } else {
        include 'user_header.php';
    }
    ?>

    <?php include 'validation_errors_display.php'; ?>
    
    <main>
            <section class="user-section">
              <h2>Traffic Sources Dashboard</h2>

              <!-- Filter Panel -->
              <div class="filter-panel-section">
                <h3><i class="fas fa-filter"></i> Filter Traffic Sources</h3>

                <div class="filter-controls">
                  <!-- Top Sources Filter -->
                  <div class="filter-group">
                    <label for="topSourcesFilter">Show Top Sources:</label>
                    <select id="topSourcesFilter" class="filter-select">
                      <option value="all">All Sources</option>
                      <option value="5">Top 5</option>
                      <option value="10">Top 10</option>
                      <option value="15">Top 15</option>
                      <option value="20">Top 20</option>
                    </select>
                  </div>

                  <!-- Minimum Percentage Filter -->
              <div class="filter-group">
                <label for="minPercentageFilter">Min Percentage (%):</label>
                <input type="number" id="minPercentageFilter" class="filter-input" min="0" max="100" step="0.1" placeholder="0.0" oninput="validatePercentageInput(this)">
              </div>
                </div>

                        <!-- Quick Filter Buttons -->
                <div class="filter-buttons">
                  <button class="filter-btn" onclick="applyQuickFilter('major')">Major Sources (>5%)</button>
                  <button class="filter-btn" onclick="applyQuickFilter('moderate')">Moderate (1-5%)</button>
                  <button class="filter-btn" onclick="applyQuickFilter('minor')">Minor (<1%)</button>
                  <button class="filter-btn secondary" onclick="clearAllFilters()">Clear Filters</button>
                </div>

                <!-- Source Selection Area -->
                <div style="margin-top: 20px;">
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="font-weight: 500; color: #495057;">Select Specific Sources:</label>
                  </div>
                  <div class="source-selection" id="sourceSelection">
                    <!-- Checkboxes will be populated by JavaScript -->
                  </div>
                            <div>
                      <button class="filter-btn" onclick="selectAllSources()">Select All</button>
                      <button class="filter-btn secondary" onclick="deselectAllSources()">Deselect All</button>
                  </div>
                </div>

                <!-- Filter Summary -->
                <div class="filter-summary" id="filterSummary" style="display: none;">
                  <span id="filterSummaryText"></span>
                </div>
              </div>

              <!-- Sample Data Notice (from current) -->
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
                                
              <div class="user-chart-section">
                <h3><i class="fas fa-chart-pie"></i> Traffic Sources Distribution</h3>
                <div class="user-sources-chart-container" id="chartContainer">
                  <canvas id="sourcesChart"></canvas>
                </div>
                <div class="user-chart-type-toggle">
                  <button class="btn btn-small active" data-chart-type="pie">Pie Chart</button>
                  <button class="btn btn-small" data-chart-type="bar">Bar Chart</button>
                </div>
                <div class="user-export-controls">
                  <button onclick="exportChartToPDF()" class="user-export-btn pdf">
                    <span class="icon">📄</span>
                    <span class="text">Export to PDF</span>
                  </button>
                </div>
                    </div>
                                
              <div class="user-data-table-section">
                <h3><i class="fas fa-table"></i> Traffic Sources Breakdown</h3>
                <div class="user-sources-table-container">
                  <table class="user-data-table" id="sourcesTable">
                    <thead>
                      <tr>
                        <th>
                          <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllRows(this)">
                          Source
                        </th>
                        <th>Visits</th>
                        <th>Percentage</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($sourcesData as $index => $source): ?>
                        <tr data-source-index="<?php echo $index; ?>" data-source-name="<?php echo htmlspecialchars($source['traffic_source']); ?>" 
                            data-visit-count="<?php echo $source['visit_count']; ?>" data-percentage="<?php echo $source['percentage']; ?>"
                            onclick="toggleRowSelection(this)" style="cursor: pointer;">
                          <td>
                            <input type="checkbox" class="row-checkbox" onclick="event.stopPropagation(); toggleRowSelection(this.closest('tr'))">
                            <?php echo htmlspecialchars($source['traffic_source']); ?>
                          </td>
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

    // Filtering variables
    let filteredData = [...sourcesData];
    let selectedSources = new Set();
    let currentFilters = {
      topSources: 'all',
      minPercentage: 0,
      selectedSources: []
    };


    // Create chart context
    let currentChart = null;
    const ctx = document.getElementById('sourcesChart').getContext('2d');

    // Function to create chart
    function createChart(type) {
      // Destroy existing chart if it exists  
      if (currentChart) currentChart.destroy();
      
      // Use filtered data for chart
      const chartData = getFilteredChartData();
      
      // Check if there's no data to display
      if (chartData.labels.length === 0) {
        // Hide canvas and show no data message
        const canvas = document.getElementById('sourcesChart');
        const container = document.getElementById('chartContainer');
        canvas.style.display = 'none';
        
        // Create or show no data message
        let noDataMsg = container.querySelector('.no-data-chart-message');
        if (!noDataMsg) {
          noDataMsg = document.createElement('div');
          noDataMsg.className = 'no-data-chart-message';
          noDataMsg.style.cssText = 'text-align: center; padding: 40px; color: #666; font-size: 16px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;';
          noDataMsg.innerHTML = '<i class="fas fa-chart-pie" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i><strong>No Data to Display</strong><br><small>Apply different filters to see chart data</small>';
          container.appendChild(noDataMsg);
        }
        noDataMsg.style.display = 'block';
        return;
      }
      
      // Show canvas and hide no data message if data exists
      const canvas = document.getElementById('sourcesChart');
      const container = document.getElementById('chartContainer');
      canvas.style.display = 'block';
      
      const noDataMsg = container.querySelector('.no-data-chart-message');
      if (noDataMsg) {
        noDataMsg.style.display = 'none';
      }
      
      // Chart configuration
      const config = {
        type: type,
        data: {
          labels: chartData.labels,
          datasets: [{
            data: type === 'pie' ? chartData.percentages : chartData.visitCounts,
            backgroundColor: backgroundColors.slice(0, chartData.labels.length),
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
                    ? `${label}: ${value}% (${chartData.visitCounts[context.dataIndex]} visits)`
                    : `${label}: ${value} visits (${chartData.percentages[context.dataIndex]}%)`;
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

    // Get filtered data for chart
    function getFilteredChartData() {
      const data = getFilteredData();
      return {
        labels: data.map(item => item.traffic_source),
        visitCounts: data.map(item => parseInt(item.visit_count)),
        percentages: data.map(item => parseFloat(item.percentage))
      };
    }

    // Get filtered data based on current filters
    function getFilteredData() {
      let data = [...sourcesData];

      // Apply top sources filter
      if (currentFilters.topSources !== 'all') {
        const limit = parseInt(currentFilters.topSources);
        data = data.sort((a, b) => parseInt(b.visit_count) - parseInt(a.visit_count)).slice(0, limit);
      }

      // Apply minimum percentage filter, Handle negative values properly
      if (currentFilters.minPercentage > 0) {
        data = data.filter(source => parseFloat(source.percentage) >= currentFilters.minPercentage);
      } else if (currentFilters.minPercentage < 0) {
        // If negative value is entered, treat it as 0 and show warning
        console.warn('Negative percentage filter detected, treating as 0');
        currentFilters.minPercentage = 0;
        // Update the input field to reflect the correction
        document.getElementById('minPercentageFilter').value = '0';
      }

      // Apply selected sources filter
      if (currentFilters.selectedSources.length > 0) {
        data = data.filter(source => currentFilters.selectedSources.includes(source.traffic_source));
      }

      // Recalculate percentages for filtered data
      const totalFilteredVisits = data.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      data = data.map(source => ({
        ...source,
        percentage: totalFilteredVisits > 0 ? ((parseInt(source.visit_count) / totalFilteredVisits) * 100).toFixed(1) : '0.0'
      }));

      return data;
    }

    function validatePercentageInput(input) {
      let value = parseFloat(input.value);
      
      if (isNaN(value)) {
        return;
      }
      
      if (value < 0) {
        input.value = '0';
        showInputFeedback(input, 'Minimum percentage cannot be negative');
      } else if (value > 100) {
        input.value = '100';
        showInputFeedback(input, 'Percentage cannot exceed 100%');
      }
    }

    function showInputFeedback(input, message) {
      // Visual feedback
      const originalBorder = input.style.border;
      const originalBackground = input.style.backgroundColor;
      
      input.style.border = '2px solid #ff6b6b';
      input.style.backgroundColor = '#ffe6e6';
      
      // Create tooltip
      const tooltip = document.createElement('div');
      tooltip.style.cssText = `
        position: absolute;
        background: #ff6b6b;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        margin-top: 5px;
        margin-left: 0px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      `;
      tooltip.textContent = message;
      
      input.parentNode.style.position = 'relative';
      input.parentNode.appendChild(tooltip);
      
      setTimeout(() => {
        input.style.border = originalBorder;
        input.style.backgroundColor = originalBackground;
        if (tooltip.parentNode) {
          tooltip.parentNode.removeChild(tooltip);
        }
      }, 2500);
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

    // Initialize filter components
    initializeFilters();

    // Filter Functions
    function initializeFilters() {
      // Populate source selection checkboxes
      populateSourceSelection();
      
      // Add event listeners
      document.getElementById('topSourcesFilter').addEventListener('change', handleTopSourcesChange);
      document.getElementById('minPercentageFilter').addEventListener('input', handleMinPercentageChange);
      
      // Initialize with all sources selected
      selectAllSources();
    }

    function populateSourceSelection() {
      const container = document.getElementById('sourceSelection');
      container.innerHTML = '';
      
      sourcesData.forEach((source, index) => {
        const checkboxDiv = document.createElement('div');
        checkboxDiv.className = 'source-checkbox';
        
        checkboxDiv.innerHTML = `
          <input type="checkbox" id="source_${index}" value="${source.traffic_source}" 
                 onchange="handleSourceSelectionChange()">
          <label for="source_${index}">${source.traffic_source} (${source.percentage}%)</label>
        `;
        
        container.appendChild(checkboxDiv);
      });
    }

    function handleTopSourcesChange() {
      currentFilters.topSources = document.getElementById('topSourcesFilter').value;
      applyFilters();
    }

    function handleMinPercentageChange() {
      let value = parseFloat(document.getElementById('minPercentageFilter').value) || 0;
      
      // Validate minimum percentage input
      if (value < 0) {
        value = 0;
        document.getElementById('minPercentageFilter').value = '0';
        
        // Show user feedback
        const inputField = document.getElementById('minPercentageFilter');
        const originalBorder = inputField.style.border;
        inputField.style.border = '2px solid #ff6b6b';
        inputField.style.backgroundColor = '#ffe6e6';
        
        setTimeout(() => {
          inputField.style.border = originalBorder;
          inputField.style.backgroundColor = '';
        }, 2000);
        
        console.log('Negative percentage not allowed, reset to 0');
      } else if (value > 100) {
        value = 100;
        document.getElementById('minPercentageFilter').value = '100';
        
        // Show user feedback
        const inputField = document.getElementById('minPercentageFilter');
        const originalBorder = inputField.style.border;
        inputField.style.border = '2px solid #ff6b6b';
        inputField.style.backgroundColor = '#ffe6e6';
        
        setTimeout(() => {
          inputField.style.border = originalBorder;
          inputField.style.backgroundColor = '';
        }, 2000);
        
        console.log('Percentage cannot exceed 100%, reset to 100');
      }
      
      currentFilters.minPercentage = value;
      applyFilters();
    }

    function handleSourceSelectionChange() {
      const checkboxes = document.querySelectorAll('#sourceSelection input[type="checkbox"]');
      const selected = [];
      
      checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
          selected.push(checkbox.value);
        }
      });
      
      currentFilters.selectedSources = selected;
      updateTableRowSelection();
      applyFilters();
    }

    function applyQuickFilter(type) {
      
      // Clear other filters first
      document.getElementById('topSourcesFilter').value = 'all';
      document.getElementById('minPercentageFilter').value = '';
      
      // Reset filters to default
      currentFilters.topSources = 'all';
      currentFilters.minPercentage = 0;
      currentFilters.selectedSources = [];
      
      let filterName = '';
      
      switch(type) {
        case 'major':
          // For major sources, show only sources > 5%
          filterName = 'Major Sources (>5%)';
          const majorSources = sourcesData.filter(source => {
            const pct = parseFloat(source.percentage);
            return pct > 5;
          }).map(source => source.traffic_source);
          currentFilters.selectedSources = majorSources;
          updateSourceCheckboxes();
          break;
        case 'moderate':
          // For moderate sources, show only sources between 1-5%
          filterName = 'Moderate Sources (1-5%)';
          const moderateSources = sourcesData.filter(source => {
            const pct = parseFloat(source.percentage);
            return pct >= 1 && pct <= 5;
          }).map(source => source.traffic_source);
          currentFilters.selectedSources = moderateSources;
          updateSourceCheckboxes();
          break;
        case 'minor':
          // For minor sources, show only sources < 1%
          filterName = 'Minor Sources (<1%)';
          console.log('Processing minor filter');
          const minorSources = sourcesData.filter(source => {
            const pct = parseFloat(source.percentage);
            console.log(`Source: ${source.traffic_source}, Percentage: ${pct}, Is < 1%: ${pct < 1}`);
            return pct < 1;
          }).map(source => source.traffic_source);
          console.log('Minor sources found:', minorSources);
          currentFilters.selectedSources = minorSources;
          updateSourceCheckboxes();
          break;
      }
      
      applyFilters();
      
      // Show alert if no data found after filtering
      if (currentFilters.selectedSources.length === 0) {
        alert(`No data found for ${filterName}. All sources in your data fall outside this range.`);
      }
    }

    function clearAllFilters() {
      // Reset all filters
      document.getElementById('topSourcesFilter').value = 'all';
      document.getElementById('minPercentageFilter').value = '';
      
      currentFilters = {
        topSources: 'all',
        minPercentage: 0,
        selectedSources: []
      };
      
      // Select all sources
      selectAllSources();
      applyFilters();
    }

    function selectAllSources() {
      const checkboxes = document.querySelectorAll('#sourceSelection input[type="checkbox"]');
      checkboxes.forEach(checkbox => checkbox.checked = true);
      
      currentFilters.selectedSources = sourcesData.map(source => source.traffic_source);
      updateTableRowSelection();
      applyFilters();
    }

    function deselectAllSources() {
      const checkboxes = document.querySelectorAll('#sourceSelection input[type="checkbox"]');
      checkboxes.forEach(checkbox => checkbox.checked = false);
      
      currentFilters.selectedSources = [];
      updateTableRowSelection();
      applyFilters();
    }

    function updateSourceCheckboxes() {
      const checkboxes = document.querySelectorAll('#sourceSelection input[type="checkbox"]');
      checkboxes.forEach(checkbox => {
        checkbox.checked = currentFilters.selectedSources.includes(checkbox.value);
      });
      updateTableRowSelection();
    }

    function applyFilters() {
      filteredData = getFilteredData();
      updateTable();
      updateChart();
      updateFilterSummary();
    }

    function updateTable() {
      const tableRows = document.querySelectorAll('#sourcesTable tbody tr');
      let visibleRowCount = 0;
      
      tableRows.forEach(row => {
        const sourceName = row.dataset.sourceName;
        const percentage = parseFloat(row.dataset.percentage);
        
        let showRow = true;
        
        // Check top sources filter
        if (currentFilters.topSources !== 'all') {
          const sortedSources = [...sourcesData].sort((a, b) => parseInt(b.visit_count) - parseInt(a.visit_count));
          const topSources = sortedSources.slice(0, parseInt(currentFilters.topSources));
          showRow = showRow && topSources.some(source => source.traffic_source === sourceName);
        }
        
        // Check minimum percentage filter
        if (currentFilters.minPercentage > 0) {
          showRow = showRow && percentage >= currentFilters.minPercentage;
        }
        
        // Check selected sources filter
        if (currentFilters.selectedSources.length > 0) {
          showRow = showRow && currentFilters.selectedSources.includes(sourceName);
        }
        
        if (showRow) {
          row.style.display = '';
          row.classList.remove('table-row-filtered');
          visibleRowCount++;
        } else {
          row.style.display = 'none';
          row.classList.add('table-row-filtered');
        }
      });
      
      // Show/hide no data message for table
      updateTableNoDataMessage(visibleRowCount === 0);
      
      // Update table percentages for visible rows
      updateTablePercentages();
    }

    function updateTableNoDataMessage(showNoDataMessage) {
      const tableContainer = document.querySelector('.user-sources-table-container');
      let noDataMsg = tableContainer.querySelector('.no-data-table-message');
      
      if (showNoDataMessage) {
        if (!noDataMsg) {
          noDataMsg = document.createElement('div');
          noDataMsg.className = 'no-data-table-message';
          noDataMsg.style.cssText = 'text-align: center; padding: 40px; color: #666; font-size: 16px; background: #f8f9fa; border-radius: 8px; margin: 20px 0; border: 2px dashed #dee2e6;';
          noDataMsg.innerHTML = '<i class="fas fa-table" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i><strong>No Data to Display</strong><br><small>The current filters exclude all traffic sources. Try adjusting your filters or click "Clear Filters" to see all data.</small>';
          tableContainer.appendChild(noDataMsg);
        }
        noDataMsg.style.display = 'block';
        document.getElementById('sourcesTable').style.display = 'none';
      } else {
        if (noDataMsg) {
          noDataMsg.style.display = 'none';
        }
        document.getElementById('sourcesTable').style.display = 'table';
      }
    }

    function updateTablePercentages() {
      const visibleRows = document.querySelectorAll('#sourcesTable tbody tr:not(.table-row-filtered)');
      const totalVisits = filteredData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      
      visibleRows.forEach(row => {
        const sourceName = row.dataset.sourceName;
        const filteredSource = filteredData.find(source => source.traffic_source === sourceName);
        
        if (filteredSource) {
          const percentageCell = row.cells[2];
          percentageCell.textContent = `${filteredSource.percentage}%`;
        }
      });
    }

    function updateChart() {
      const currentChartType = document.querySelector('.user-chart-type-toggle .btn.active').dataset.chartType;
      createChart(currentChartType);
    }

    function updateFilterSummary() {
      const summaryDiv = document.getElementById('filterSummary');
      const summaryText = document.getElementById('filterSummaryText');
      
      // Check if there's no data after filtering
      if (filteredData.length === 0) {
        summaryDiv.style.display = 'none';
        return;
      }
      
      if (filteredData.length === sourcesData.length) {
        summaryDiv.style.display = 'none';
        return;
      }
      
      const totalOriginal = sourcesData.length;
      const totalFiltered = filteredData.length;
      const totalVisitsOriginal = sourcesData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      const totalVisitsFiltered = filteredData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      
      summaryText.innerHTML = `
        Showing ${totalFiltered} of ${totalOriginal} sources 
        (${((totalVisitsFiltered / totalVisitsOriginal) * 100).toFixed(1)}% of total visits)
      `;
      
      summaryDiv.style.display = 'block';
    }

    // Table row selection functions
    function toggleRowSelection(row) {
      const checkbox = row.querySelector('.row-checkbox');
      checkbox.checked = !checkbox.checked;
      
      if (checkbox.checked) {
        row.classList.add('table-row-selected');
      } else {
        row.classList.remove('table-row-selected');
      }
      
      updateSourceCheckboxFromTable();
    }

    function toggleAllRows(masterCheckbox) {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      const rows = document.querySelectorAll('#sourcesTable tbody tr');
      
      checkboxes.forEach((checkbox, index) => {
        checkbox.checked = masterCheckbox.checked;
        if (masterCheckbox.checked) {
          rows[index].classList.add('table-row-selected');
        } else {
          rows[index].classList.remove('table-row-selected');
        }
      });
      
      updateSourceCheckboxFromTable();
    }

    function updateTableRowSelection() {
      const rows = document.querySelectorAll('#sourcesTable tbody tr');
      
      rows.forEach(row => {
        const sourceName = row.dataset.sourceName;
        const checkbox = row.querySelector('.row-checkbox');
        const isSelected = currentFilters.selectedSources.includes(sourceName);
        
        checkbox.checked = isSelected;
        if (isSelected) {
          row.classList.add('table-row-selected');
        } else {
          row.classList.remove('table-row-selected');
        }
      });
      
      // Update master checkbox
      updateMasterCheckbox();
    }

    function updateSourceCheckboxFromTable() {
      const tableCheckboxes = document.querySelectorAll('.row-checkbox');
      const selected = [];
      
      tableCheckboxes.forEach((checkbox, index) => {
        if (checkbox.checked) {
          const row = checkbox.closest('tr');
          selected.push(row.dataset.sourceName);
        }
      });
      
      currentFilters.selectedSources = selected;
      updateSourceCheckboxes();
      updateMasterCheckbox();
      applyFilters();
    }

    function updateMasterCheckbox() {
      const masterCheckbox = document.getElementById('selectAllCheckbox');
      const tableCheckboxes = document.querySelectorAll('.row-checkbox');
      const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
      
      if (checkedCount === 0) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
      } else if (checkedCount === tableCheckboxes.length) {
        masterCheckbox.checked = true;
        masterCheckbox.indeterminate = false;
      } else {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = true;
      }
    }

    // Export table to CSV
    function exportTableToCSV() {
      // Check if there's data to export
      if (filteredData.length === 0) {
        alert('No data to export. Please adjust your filters to include some data.');
        return;
      }
      
      let csv = [];
      
      // Add header
      const headers = ['Source', 'Visits', 'Percentage'];
      csv.push(headers.map(h => `"${h}"`).join(","));
      
      // Add only filtered/visible data
      filteredData.forEach(source => {
        const row = [
          source.traffic_source,
          parseInt(source.visit_count).toLocaleString(),
          `${source.percentage}%`
        ];
        csv.push(row.map(cell => `"${cell}"`).join(","));
      });

      const blob = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      
      // Include filter info in filename
      const filterSuffix = filteredData.length === sourcesData.length ? '' : '_filtered';
      a.download = `traffic_sources_table${filterSuffix}${isSampleData ? '_sample' : ''}.csv`;
      
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      // Log export in DB
      const filterInfo = filteredData.length === sourcesData.length ? '' : ` (${filteredData.length}/${sourcesData.length} sources)`;
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=CSV&description=Exported traffic sources table data${filterInfo} (uploadId: ${uploadId}, sample: ${isSampleData})`
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
    async function exportChartToPDF() {
      // Check if there's data to export
      if (filteredData.length === 0) {
        alert('No data to export. Please adjust your filters to include some data.');
        return;
      }
      
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
      const titleText = filteredData.length === sourcesData.length ? 
        'TrafAnalyz Traffic Sources Report' : 
        'TrafAnalyz Traffic Sources Report (Filtered)';
      pdf.text(titleText, pageWidth/2, yPosition, { align: 'center' });
        
      yPosition += 15;
        
      // Generated info
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
      yPosition += 5;
      pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });
      
      // Sample data indicator
      if (isSampleData) {
        yPosition += 5;
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(255, 0, 0);
        pdf.text('⚠️ Sample Data Report', pageWidth - margin, yPosition, { align: 'right' });
        pdf.setTextColor(0, 0, 0);
      }
        
      yPosition += 20;

      // Filter Information (if filtered)
      if (filteredData.length !== sourcesData.length) {
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Filter Information', margin, yPosition);
        yPosition += 10;

        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Showing ${filteredData.length} of ${sourcesData.length} total sources`, margin, yPosition);
        yPosition += 5;

        if (currentFilters.topSources !== 'all') {
          pdf.text(`Top Sources Filter: Top ${currentFilters.topSources}`, margin, yPosition);
          yPosition += 5;
        }

        if (currentFilters.minPercentage > 0) {
          pdf.text(`Minimum Percentage: ${currentFilters.minPercentage}%`, margin, yPosition);
          yPosition += 5;
        }

        if (currentFilters.selectedSources.length > 0 && currentFilters.selectedSources.length < sourcesData.length) {
          pdf.text(`Custom Selection: ${currentFilters.selectedSources.length} sources selected`, margin, yPosition);
          yPosition += 5;
        }

        yPosition += 10;
      }
        
      // Traffic Sources Summary Section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Summary', margin, yPosition);
      yPosition += 10;
        
      // Calculate totals and insights (use filtered data)
      const totalSources = filteredData.length;
      const totalVisits = filteredData.reduce((sum, source) => sum + parseInt(source.visit_count), 0);
      const topSource = filteredData.reduce((top, current) => 
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
    
      // Traffic Sources detailed table (use filtered data)
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Traffic Sources Breakdown', margin, yPosition);
      yPosition += 10;
    
      const tableHeaders = ['Source', 'Visits', 'Percentage'];
      const tableData = [tableHeaders];
    
      // Add filtered data rows
      filteredData.forEach(source => {
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
    
      // Charts section
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      const chartTitle = filteredData.length === sourcesData.length ? 
        'Traffic Sources Distribution (Pie Chart)' : 
        'Filtered Traffic Sources Distribution (Pie Chart)';
      pdf.text(chartTitle, margin, yPosition);
      yPosition += 10;
    
      // Check if we need a new page for the chart
      if (yPosition > 150) {
        pdf.addPage();
        yPosition = 30;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text(chartTitle, margin, yPosition);
        yPosition += 10;
      }
    
      // Save current chart state
      const currentChartType = document.querySelector('.user-chart-type-toggle .btn.active').dataset.chartType;
      
      // Switch to pie chart and capture
      createChart('pie');
      await new Promise(resolve => setTimeout(resolve, 1000)); // Wait for chart to render
    
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
      const barChartTitle = filteredData.length === sourcesData.length ? 
        'Traffic Sources by Visit Count (Bar Chart)' : 
        'Filtered Traffic Sources by Visit Count (Bar Chart)';
      pdf.text(barChartTitle, margin, yPosition);
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
    
      // Key Insights section (use filtered data)
      if (yPosition > 200) {
        pdf.addPage();
        yPosition = 30;
      }
    
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'bold');
      pdf.text('Key Insights', margin, yPosition);
      yPosition += 10;
    
      pdf.setFontSize(10);
      pdf.setFont('helvetica', 'normal');
    
      // Find top 3 sources from filtered data
      const sortedSources = [...filteredData].sort((a, b) => parseInt(b.visit_count) - parseInt(a.visit_count));
      const topSources = sortedSources.slice(0, 3);
    
      if (topSources[0]) {
        pdf.text(`• Primary traffic source: ${topSources[0].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[0].visit_count).toLocaleString()} visits, ${parseFloat(topSources[0].percentage).toFixed(1)}% of filtered traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      if (topSources[1]) {
        pdf.text(`• Secondary traffic source: ${topSources[1].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[1].visit_count).toLocaleString()} visits, ${parseFloat(topSources[1].percentage).toFixed(1)}% of filtered traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      if (topSources[2]) {
        pdf.text(`• Third largest source: ${topSources[2].traffic_source}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`  (${parseInt(topSources[2].visit_count).toLocaleString()} visits, ${parseFloat(topSources[2].percentage).toFixed(1)}% of filtered traffic)`, margin + 5, yPosition);
        yPosition += 8;
      }
    
      // Calculate diversity metrics for filtered data
      const topThreePercentage = topSources.slice(0, 3).reduce((sum, source) => sum + parseFloat(source.percentage), 0);
      pdf.text(`• Top 3 sources account for ${topThreePercentage.toFixed(1)}% of filtered traffic`, margin, yPosition);
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
      
      if (filteredData.length !== sourcesData.length) {
        yPosition += 5;
        pdf.text(`Filters Applied: ${sourcesData.length - filteredData.length} sources excluded`, margin, yPosition);
      }
      
      if (isSampleData) {
        yPosition += 10;
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'italic');
        pdf.setTextColor(255, 0, 0);
        pdf.text('Note: This report was generated using sample data for demonstration purposes.', margin, yPosition);
        pdf.setTextColor(0, 0, 0);
      }
    
      // Save PDF with descriptive filename
      const filterSuffix = filteredData.length === sourcesData.length ? '' : '_Filtered';
      pdf.save(`TrafAnalyz_Traffic_Sources${filterSuffix}_Report_${isSampleData ? 'Sample_' : ''}${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`);
    
      // Log the PDF export into the database
      const filterInfo = filteredData.length === sourcesData.length ? '' : ` (${filteredData.length}/${sourcesData.length} sources)`;
      fetch('log_export.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exportType=PDF&description=Exported traffic sources comprehensive report${filterInfo} with both pie and bar charts as PDF (uploadId: ${uploadId}, sample: ${isSampleData})`
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