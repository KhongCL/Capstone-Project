<?php

require_once '../auth/user_auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Load supported formats from config
$mappingsFile = __DIR__ . '/../config/csv_mappings.json';
$supportedFormats = [];

if (file_exists($mappingsFile)) {
    $mappings = json_decode(file_get_contents($mappingsFile), true);
    if ($mappings) {
        $supportedFormats = $mappings;
    }
}

// Set page variables for header
$title = "Supported CSV Formats";
$active_page = "supported_formats";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supported CSV Formats - TrafAnalyz</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .formats-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .intro-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .intro-section h1 {
            margin: 0 0 15px 0;
            font-size: 2.5em;
        }
        
        .intro-section p {
            font-size: 1.2em;
            margin: 0;
            opacity: 0.9;
        }
        
        .export-guide {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 25px;
            margin-bottom: 40px;
            border-radius: 8px;
        }
        
        .export-guide h2 {
            color: #28a745;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .platform-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .platform-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .platform-card h3 {
            color: #dc3545;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .platform-card ol {
            margin: 15px 0 0 0;
            padding-left: 20px;
        }
        
        .platform-card li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .formats-grid {
            display: grid;
            gap: 30px;
        }
        
        .format-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .format-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .format-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px 25px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .format-header h3 {
            margin: 0;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .format-toggle {
            font-size: 1.2em;
            transition: transform 0.3s ease;
        }
        
        .format-content {
            padding: 25px;
            display: none;
        }
        
        .format-content.expanded {
            display: block;
        }
        
        .detection-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .detection-info h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detection-columns {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .detection-tag {
            background: #2196f3;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .mappings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .mappings-table th,
        .mappings-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .mappings-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .mappings-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-type-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .data-type-badge.integer {
            background: #28a745;
        }
        
        .data-type-badge.float {
            background: #ffc107;
            color: #212529;
        }
        
        .data-type-badge.currency {
            background: #dc3545;
        }
        
        .data-type-badge.percentage {
            background: #6f42c1;
        }
        
        .tips-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 25px;
            margin-top: 40px;
        }
        
        .tips-section h2 {
            color: #856404;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .tip-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #ffc107;
        }
        
        .tip-item h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .no-formats {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-formats i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .formats-content {
                padding: 15px;
            }
            
            .intro-section {
                padding: 30px 20px;
            }
            
            .intro-section h1 {
                font-size: 2em;
            }
            
            .platform-steps {
                grid-template-columns: 1fr;
            }
            
            .tips-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <main class="content-page">
        <div class="formats-content">
            <div class="intro-section">
                <h1><i class="fas fa-file-csv"></i> Supported CSV Formats</h1>
                <p>Learn about the analytics CSV formats supported by TrafAnalyz and how to export your data correctly</p>
            </div>
            
            <div class="export-guide">
                <h2><i class="fas fa-download"></i> How to Export Your Analytics Data</h2>
                <p>Follow these step-by-step guides to export your analytics data from popular platforms:</p>
                
                <div class="platform-steps">
                    <div class="platform-card">
                        <h3><i class="fab fa-google"></i> Google Analytics 4 (GA4)</h3>
                        <ol>
                            <li>Sign in to your Google Analytics account</li>
                            <li>Go to <strong>Reports</strong> → <strong>Life cycle</strong> → <strong>Acquisition</strong> → <strong>Traffic acquisition</strong></li>
                            <li>Set your desired date range using the date picker</li>
                            <li>Click the <strong>Share</strong> button (export icon) in the top right</li>
                            <li>Select <strong>"Download file"</strong> → <strong>"Download CSV"</strong></li>
                            <li>Choose <strong>"Full report"</strong> to include all data</li>
                            <li>Click <strong>"Download"</strong> and save the file</li>
                        </ol>
                    </div>
                    
                    <div class="platform-card">
                        <h3><i class="fas fa-chart-line"></i> Universal Analytics (GA3)</h3>
                        <ol>
                            <li>Access your Google Analytics account</li>
                            <li>Navigate to <strong>Acquisition</strong> → <strong>All Traffic</strong> → <strong>Channels</strong></li>
                            <li>Set your date range at the top right</li>
                            <li>Click <strong>"Export"</strong> at the top of the report</li>
                            <li>Select <strong>"CSV"</strong> from the dropdown menu</li>
                            <li>The CSV file will be downloaded automatically</li>
                        </ol>
                    </div>
                    
                    <div class="platform-card">
                        <h3><i class="fas fa-chart-bar"></i> Adobe Analytics</h3>
                        <ol>
                            <li>Log into Adobe Analytics workspace</li>
                            <li>Create or open a traffic acquisition report</li>
                            <li>Include dimensions like Traffic Source, Medium, Sessions</li>
                            <li>Right-click on the table and select <strong>"Download data as CSV"</strong></li>
                            <li>Choose your preferred format and click <strong>"Download"</strong></li>
                        </ol>
                    </div>
                </div>
            </div>

            <?php if (!empty($supportedFormats)): ?>
                <h2><i class="fas fa-cogs"></i> Currently Supported Formats</h2>
                <p>TrafAnalyz automatically detects and processes the following CSV formats. Click on each format to see detailed information:</p>
                
                <div class="formats-grid">
                    <?php foreach ($supportedFormats as $formatKey => $format): ?>
                        <div class="format-card">
                            <div class="format-header" onclick="toggleFormat('<?php echo $formatKey; ?>')">
                                <h3>
                                    <i class="fas fa-file-csv"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $formatKey)); ?>
                                </h3>
                                <span class="format-toggle" id="toggle-<?php echo $formatKey; ?>">▼</span>
                            </div>
                            
                            <div class="format-content" id="content-<?php echo $formatKey; ?>">
                                <div class="detection-info">
                                    <h4><i class="fas fa-search"></i> Auto-Detection</h4>
                                    <p>This format is automatically detected when your CSV contains these columns:</p>
                                    <div class="detection-columns">
                                        <?php foreach ($format['format_detection'] as $column): ?>
                                            <span class="detection-tag"><?php echo htmlspecialchars($column); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <h4><i class="fas fa-table"></i> Column Mappings</h4>
                                <p>Your CSV columns will be mapped to our system fields as follows:</p>
                                
                                <table class="mappings-table">
                                    <thead>
                                        <tr>
                                            <th>Your CSV Column</th>
                                            <th>Maps to System Field</th>
                                            <th>Data Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($format['column_mappings'] as $csvColumn => $systemField): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($csvColumn); ?></strong></td>
                                                <td><?php echo ucwords(str_replace('_', ' ', $systemField)); ?></td>
                                                <td>
                                                    <?php 
                                                    $dataType = $format['data_types'][$csvColumn] ?? 'string';
                                                    $badgeClass = strtolower($dataType);
                                                    ?>
                                                    <span class="data-type-badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst($dataType); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-formats">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>No Formats Configured</h3>
                    <p>No CSV formats have been configured yet. Please contact your administrator.</p>
                </div>
            <?php endif; ?>
            
            <div class="tips-section">
                <h2><i class="fas fa-lightbulb"></i> Tips for Successful Upload</h2>
                <div class="tips-grid">
                    <div class="tip-item">
                        <h4><i class="fas fa-file-check"></i> File Requirements</h4>
                        <ul>
                            <li>File must be in CSV format (.csv)</li>
                            <li>Maximum file size: 5MB</li>
                            <li>UTF-8 encoding recommended</li>
                            <li>Include column headers in first row</li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4><i class="fas fa-calendar-alt"></i> Date Ranges</h4>
                        <ul>
                            <li>Export at least 7 days of data for meaningful insights</li>
                            <li>Avoid exporting partial days</li>
                            <li>Use consistent date ranges for comparisons</li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4><i class="fas fa-database"></i> Data Quality</h4>
                        <ul>
                            <li>Ensure no completely empty rows</li>
                            <li>Remove any summary rows from your export</li>
                            <li>Keep original column names when possible</li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4><i class="fas fa-question-circle"></i> Need Help?</h4>
                        <ul>
                            <li>Format not recognized? Try manual column mapping</li>
                            <li>Check our <a href="../faq.php">FAQ section</a> for common issues</li>
                            <li>Contact support if you continue having problems</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>
    
    <script>
        function toggleFormat(formatKey) {
            const content = document.getElementById('content-' + formatKey);
            const toggle = document.getElementById('toggle-' + formatKey);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                content.style.display = 'none';
                toggle.textContent = '▼';
            } else {
                content.classList.add('expanded');
                content.style.display = 'block';
                toggle.textContent = '▲';
            }
        }
        
        // Initialize - show first format expanded
        document.addEventListener('DOMContentLoaded', function() {
            const firstFormat = document.querySelector('.format-content');
            if (firstFormat) {
                const formatKey = firstFormat.id.replace('content-', '');
                toggleFormat(formatKey);
            }
        });
    </script>
</body>
</html>