<?php

// Name: Khong Chee Leong
// Position/Role: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: supported_formats.php
// Description: Supported CSV formats guide displaying format specifications, column mappings,
//              and export instructions for various analytics platforms integration.
// First Written On: 14 April 2025
// Edited On: 14 July 2025

// Load supported formats from config
$mappingsFile = __DIR__ . '/config/csv_mappings.json';
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --light-blue: #e0f2fe;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
        }
				
        .formats-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            opacity: 0;
            transform: translateY(20px);
        }

					.hero-section {
						background: #0ea5e9;
						color: white;
						text-align: center;
						backdrop-filter: blur(10px);
						position: relative;
						overflow: hidden;
						border-radius: 1rem;
						padding: 2rem;
						margin-bottom: 2rem;
						box-shadow: var(--shadow);
						border: 1px solid rgba(226, 232, 240, 0.6);
				}

        .hero-section h1 {
						color: white;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin: 0;
            opacity: 0.95;
            max-width: 48rem;
            margin: 0 auto;
        }

        .format-header h3,
        .format-header h3 *,
        .formats-content .format-header h3,
        .formats-content .format-header h3 *,
        .format-card .format-header h3,
        .format-card .format-header h3 * {
            color: white !important;
        }

        .export-guide {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-left: 4px solid var(--success);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .export-guide h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .export-guide > p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .platform-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .platform-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            transform: translateY(0);
        }

        .platform-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .platform-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .platform-card ol {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .platform-card li {
            margin: 0.75rem 0;
            line-height: 1.6;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .step-number {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            background: var(--light-blue);
            color: var(--primary-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.125rem;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--dark-gray);
        }

        .section-icon {
            padding: 0.5rem;
            background: var(--light-blue);
            border-radius: 0.5rem;
            color: var(--primary-color);
        }

        .section-description {
            color: var(--dark-gray);
            font-size: 1.125rem;
            line-height: 1.7;
        }

        .formats-grid {
            display: grid;
            gap: 1rem;
        }

        .format-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            opacity: 1;
            transform: translateY(2px);
        }


				.format-header {
						background: var(--primary-dark);
						color: white;
						padding: 1.5rem;
						cursor: pointer;
						display: flex;
						justify-content: space-between;
						align-items: center;
						transition: all 0.2s ease;
						position: relative;
						overflow: hidden;
				}


				.format-header:hover {
						background: var(--primary-color);
				}

				.format-header h3 {
						margin: 0;
						font-size: 1.25rem;
						font-weight: 600;
						display: flex;
						align-items: center;
						gap: 0.75rem;
				}

				.format-header i {
						color: white;
				}

					.format-toggle {
						font-size: 1.25rem;
						transition: all 0.3s ease;
						color: white;
				}

				.format-toggle.expanded {
						transform: rotate(180deg);
				}

        .format-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .format-content.expanded {
            padding: 1.5rem;
            max-height: 1000px;
            opacity: 1;
        }

        .detection-info {
            background: rgba(224, 242, 254, 0.5);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
        }

        .detection-info h4 {
            margin: 0 0 0.75rem 0;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .detection-info p {
            color: #1e40af;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .detection-columns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .detection-tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .mappings-section h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .mappings-section p {
            color: var(--dark-gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .mappings-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .mappings-table th,
        .mappings-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .mappings-table th {
            background: rgba(248, 250, 252, 0.8);
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .mappings-table tr:hover {
            background: rgba(248, 250, 252, 0.5);
        }

        .mappings-table td:first-child {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .data-type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid;
        }

        .data-type-badge.integer {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .data-type-badge.float {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        .data-type-badge.currency {
            background: #fecaca;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .data-type-badge.percentage {
            background: #e9d5ff;
            color: #6b21a8;
            border-color: #c4b5fd;
        }

        .data-type-badge.string {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }

        .tips-section {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .tips-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .tip-item {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.6);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .tip-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .tip-item h4 {
            margin: 0 0 1rem 0;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .tip-icon {
            padding: 0.5rem;
            background: #fef3c7;
            border-radius: 0.5rem;
            color: var(--warning);
        }

        /* Fix for tip icons - ensure they're all visible and properly colored */
        .tip-icon {
            padding: 0.5rem !important;
            background: #fef3c7 !important;
            border-radius: 0.5rem !important;
            color: var(--warning) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 2.5rem !important;
            height: 2.5rem !important;
            flex-shrink: 0 !important;
        }

        .tip-icon i {
            color: var(--warning) !important;
            font-size: 1rem !important;
            display: block !important;
            visibility: visible !important;
        }

        /* Ensure tip headers are properly aligned */
        .tip-item h4 {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        .tip-item ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .tip-item li {
            margin: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: var(--dark-gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .check-icon {
            color: var(--success);
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        .no-formats {
            text-align: center;
            padding: 4rem 1.5rem;
            color: var(--dark-gray);
        }

        .no-formats i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .formats-content {
                padding: 1rem;
            }

            .hero-section {
                padding: 2rem 1.5rem;
            }

            .hero-section h1 {
                font-size: 2rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .platform-steps {
                grid-template-columns: 1fr;
            }

            .tips-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

				.stats-highlight {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
						gap: 1rem;
						margin: 2rem 0;
				}

				.stat-card {
						background: rgba(255, 255, 255, 0.9);
						backdrop-filter: blur(10px);
						border: 1px solid rgba(14, 165, 233, 0.2);
						border-radius: 0.75rem;
						padding: 1.5rem;
						text-align: center;
						box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
						transition: all 0.3s ease;
				}

				.stat-card:hover {
						transform: translateY(-4px);
						box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
				}

				.stat-number {
						font-size: 2rem;
						font-weight: 700;
						color: var(--primary-color);
						display: block;
				}

				.stat-label {
						color: var(--slate);
						font-size: 0.9rem;
						margin-top: 0.5rem;
				}

				.divider {
						height: 1.5px;
						background: var(--primary-color);
						margin: 2rem 0;
						border-radius: 1px;
				}

    </style>
</head>
<body>
		<div class="container">
    		<?php include 'header.php'; ?>

    		<main>
            <!-- Hero Section -->
            <div class="hero-section">
                <h1>
                    <i class="fas fa-file-csv"></i>
                    Supported CSV Formats
                </h1>
                <p>Learn about the analytics CSV formats supported by TrafAnalyz and how to export your data correctly</p>
            </div>
            
            <!-- Stats Highlight - Moved up here -->
            <div class="stats-highlight">
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($supportedFormats); ?>+</span>
                    <div class="stat-label">Supported Formats</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number">100%</span>
                    <div class="stat-label">Auto-Detection</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number">5MB</span>
                    <div class="stat-label">Max File Size</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number">24/7</span>
                    <div class="stat-label">Processing</div>
                </div>
            </div>
            
            <!-- Export Guide -->
            <div class="export-guide">
                <h2>
                    <i class="fas fa-download"></i>
                    How to Export Your Analytics Data
                </h2>
                <p>Follow these step-by-step guides to export your analytics data from popular platforms:</p>
                
                <div class="platform-steps">
                    <div class="platform-card">
                        <h3>
                            <i class="fas fa-magnifying-glass"></i>
                            Google Analytics 4 (GA4)
                        </h3>
                        <ol>
                            <li>
                                <span class="step-number">1</span>
                                <span>Sign in to your Google Analytics account and navigate to the homepage</span>
                            </li>
                            <li>
                                <span class="step-number">2</span>
                                <span>Open the sidebar menu on the left and click on <strong>"Reports"</strong></span>
                            </li>
                            <li>
                                <span class="step-number">3</span>
                                <span>Under the <strong>"Reports snapshot"</strong> section, locate and expand the <strong>"Business objectives"</strong> dropdown</span>
                            </li>
                            <li>
                                <span class="step-number">4</span>
                                <span>Within Business objectives, find and click on the <strong>"Generate leads"</strong> dropdown</span>
                            </li>
                            <li>
                                <span class="step-number">5</span>
                                <span>Select the specific page you want to analyze web traffic for from the available options</span>
                            </li>
                            <li>
                                <span class="step-number">6</span>
                                <span>At the top right of the selected page report, click the <strong>"Share this report"</strong> icon</span>
                            </li>
                            <li>
                                <span class="step-number">7</span>
                                <span>From the sharing options, select <strong>"Download file"</strong></span>
                            </li>
                            <li>
                                <span class="step-number">8</span>
                                <span>Choose <strong>"Download CSV"</strong> format from the available file types</span>
                            </li>
                            <li>
                                <span class="step-number">9</span>
                                <span>Save the downloaded CSV file to your computer - it's now ready for upload to TrafAnalyz!</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Supported Formats -->
            <div class="formats-section">
                <?php if (!empty($supportedFormats)): ?>
                    <div class="section-header">
                        <h2 class="section-title">
                            <div class="section-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            Currently Supported Formats
                        </h2>
                        <p class="section-description">
                            TrafAnalyz automatically detects and processes the following CSV formats. Click on each format to see detailed information:
                        </p>
                    </div>
                    
                    <div class="formats-grid">
                        <?php foreach ($supportedFormats as $formatKey => $format): ?>
                            <div class="format-card">
                                <div class="format-header" onclick="toggleFormat('<?php echo $formatKey; ?>')">
                                    <h3>
                                        <i class="fas fa-file-csv"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $formatKey)); ?>
                                    </h3>
                                    <span class="format-toggle" id="toggle-<?php echo $formatKey; ?>">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </div>
                                
                                <div class="format-content" id="content-<?php echo $formatKey; ?>">
                                    <div class="detection-info">
                                        <h4>
                                            <i class="fas fa-search"></i>
                                            Auto-Detection
                                        </h4>
                                        <p>This format is automatically detected when your CSV contains these columns:</p>
                                        <div class="detection-columns">
                                            <?php foreach ($format['format_detection'] as $column): ?>
                                                <span class="detection-tag"><?php echo htmlspecialchars($column); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mappings-section">
                                        <h4>
                                            <i class="fas fa-table"></i>
                                            Column Mappings
                                        </h4>
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
                                                        <td><?php echo htmlspecialchars($csvColumn); ?></td>
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
            </div>
            
            <!-- Add this before the tips-section div -->
            <div class="divider"></div>

            <!-- Tips Section -->
            <div class="tips-section">
                <h2>
                    <i class="fas fa-lightbulb"></i>
                    Tips for Successful Upload
                </h2>
                <div class="tips-grid">
                    <div class="tip-item">
                        <h4>
                            <div class="tip-icon">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            File Requirements
                        </h4>
                        <ul>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>File must be in CSV format (.csv)</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Maximum file size: 5MB</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>UTF-8 encoding recommended</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Include column headers in first row</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4>
                            <div class="tip-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            Date Ranges
                        </h4>
                        <ul>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Export at least 7 days of data for meaningful insights</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Avoid exporting partial days</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Use consistent date ranges for comparisons</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4>
                            <div class="tip-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            Data Quality
                        </h4>
                        <ul>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Ensure no completely empty rows</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Remove any summary rows from your export</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Keep original column names when possible</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tip-item">
                        <h4>
                            <div class="tip-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            Need Help?
                        </h4>
                        <ul>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Format not recognized? Try manual column mapping</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Check our <a href="../faq.php" style="color: var(--primary-color);">FAQ section</a> for common issues</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle check-icon"></i>
                                <span>Contact support if you continue having problems</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
 
    		</main>
		
				<?php include 'footer.php'; ?>
		</div>

    <script>
        function toggleFormat(formatKey) {
            const content = document.getElementById('content-' + formatKey);
            const toggle = document.getElementById('toggle-' + formatKey);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.classList.remove('expanded');
            } else {
                content.classList.add('expanded');
                toggle.classList.add('expanded');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded - all format dropdowns are closed by default');
        });
    </script>
</body>
</html>
