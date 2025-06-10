<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
include '../functions.php';

session_start();

// If no uploaded file in session, redirect
if (!isset($_SESSION['uploaded_csv'])) {
    header('Location: index.php');
    exit;
}

$processor = new CsvProcessor();

// Process the initial mapping if first visit
if (!isset($_SESSION['mapping_result'])) {
    $_SESSION['mapping_result'] = $processor->processFile($_SESSION['uploaded_csv']);
}

$mappingResult = $_SESSION['mapping_result'];
$systemFields = [];
$query = "SELECT DISTINCT SystemFieldName, 
          GROUP_CONCAT(DISTINCT CSVColumnName SEPARATOR ', ') as CSVColumnNames 
          FROM COLUMN_MAPPING 
          WHERE FormatID = 1 
          GROUP BY SystemFieldName 
          ORDER BY SystemFieldName";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $systemFields[] = [
            'value' => $row['SystemFieldName'],
            'label' => ucwords(str_replace('_', ' ', $row['SystemFieldName'])),
            'default_columns' => explode(', ', $row['CSVColumnNames'])
        ];
    }
}

// Also add any system fields that might be missing from database but exist in JSON
$allSystemFields = [];
if (isset($mappingResult['format']) && $mappingResult['format']) {
    $processor = new CsvProcessor();
    $mappings = json_decode(file_get_contents(__DIR__ . '/../config/csv_mappings.json'), true);
    if (isset($mappings[$mappingResult['format']]['column_mappings'])) {
        foreach ($mappings[$mappingResult['format']]['column_mappings'] as $csvCol => $systemField) {
            $allSystemFields[$systemField] = ucwords(str_replace('_', ' ', $systemField));
        }
    }
}

// Merge any missing system fields
foreach ($allSystemFields as $field => $label) {
    $exists = false;
    foreach ($systemFields as $existing) {
        if ($existing['value'] === $field) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $systemFields[] = [
            'value' => $field,
            'label' => $label,
            'default_columns' => []
        ];
    }
}

// Handle form submission for manual mapping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_mapping'])) {
    $columnMapping = [];
    foreach ($_POST['mapping'] as $sourceCol => $targetCol) {
        if (!empty($targetCol)) {
            $columnMapping[$sourceCol] = $targetCol;
        }
    }
    
    // Check if at least one column is mapped
    if (empty($columnMapping)) {
        $error_message = "Please map at least one column before proceeding.";
    } else {
        // For manual mapping cases, we need to determine the format
        $format = null;
        if (isset($mappingResult['format']) && $mappingResult['format']) {
            // Format was detected but needed confirmation
            $format = $mappingResult['format'];
        } else {
            // Manual mapping - try to detect format based on column mappings
            // Check if the mappings match ga4_traffic_acquisition pattern
            $ga4RequiredFields = ['traffic_source', 'visits', 'engaged_sessions', 'bounce_rate'];
            $mappedFields = array_values($columnMapping);
            $ga4MatchCount = count(array_intersect($ga4RequiredFields, $mappedFields));
            
            if ($ga4MatchCount >= 3) {
                $format = 'ga4_traffic_acquisition';
                error_log("Detected GA4 format based on manual mappings");
            }
        }

        error_log("Using format for transformation: " . ($format ?? 'null'));
        $transformedData = $processor->transformData($_SESSION['uploaded_csv'], $columnMapping, $format);
        
        // Save transformed data to database
        if (saveTransformedData($conn, $transformedData)) {
            $_SESSION['message'] = 'Data successfully imported and mapped.';
            unset($_SESSION['mapping_result']);
            unset($_SESSION['uploaded_csv']);
            header('Location: overview.php');
            exit;
        } else {
            // Check if we have a specific message from saveTransformedData
            if (isset($_SESSION['upload_message'])) {
                $error_message = $_SESSION['upload_message']['message'];
                unset($_SESSION['upload_message']);
            } else {
                $error_message = 'Error saving data to database.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map CSV Columns - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
</head>
<body>
    <div class="container user-map-columns-container">
        <header>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="overview.php">Overview</a></li>
                    <li><a href="traffic_sources.php">Traffic Sources</a></li>
                    <li><a href="pages.php">Pages</a></li>
                    <li><a href="compare.php">Compare</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="user-mapping-section">
                <h2>Map CSV Columns</h2>
                <?php if (isset($error_message)): ?>
                    <?php 
                    // Check if this is a validation error message with multiple errors
                    if (strpos($error_message, 'Data validation errors found:') !== false): 
                        // Parse the validation errors for better display
                        $errorText = str_replace('Data validation errors found: ', '', $error_message);
                        $errorText = str_replace('. Please correct these issues and upload again.', '', $errorText);
                        
                        // Split by row pattern to separate individual errors
                        $errors = preg_split('/(?=Row \d+)/', $errorText);
                        $errors = array_filter(array_map('trim', $errors)); // Remove empty elements
                    ?>
                        <div class="user-alert user-alert-danger">
                            <h4>📋 Data Validation Issues Found</h4>
                            <p><strong>Found <?php echo count($errors); ?> validation errors in your CSV file:</strong></p>
                            
                            <div class="validation-errors-list">
                                <?php foreach ($errors as $error): ?>
                                    <?php if (!empty($error)): ?>
                                        <div class="error-item">
                                            <?php
                                            // Split error message and suggestions
                                            if (strpos($error, ' Suggestions: ') !== false) {
                                                $parts = explode(' Suggestions: ', $error);
                                                $errorMsg = $parts[0];
                                                $suggestions = $parts[1];
                                            } else {
                                                $errorMsg = $error;
                                                $suggestions = null;
                                            }
                                            ?>
                                            
                                            <div class="error-message">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <?php echo htmlspecialchars(trim($errorMsg)); ?>
                                            </div>
                                            
                                            <?php if ($suggestions): ?>
                                                <div class="error-suggestions">
                                                    <i class="fas fa-lightbulb"></i>
                                                    <strong>💡 Suggestions:</strong> <?php echo htmlspecialchars($suggestions); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <p class="error-footer"><strong>Please correct these issues in your CSV file and upload again.</strong></p>
                        </div>
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="user-alert user-alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($mappingResult['status'] === 'needs_mapping'): ?>
                    <div class="user-alert user-alert-info">
                        This CSV format was not automatically recognized. Please review and confirm the column mappings below.
                    </div>
                <?php elseif ($mappingResult['status'] === 'success'): ?>
                    <div class="user-alert user-alert-success">
                        CSV format detected: <strong><?php echo ucfirst(str_replace('_', ' ', $mappingResult['format'])); ?></strong>
                        <p>Please confirm the column mappings below:</p>
                    </div>
                <?php endif; ?>

                <div class="upload-progress" id="mappingProgress" style="display: none;">
                    <h3>Processing Your Data</h3>
                    
                    <div class="progress-container">
                        <div class="progress-stage completed" id="mappingStage1">
                            <div class="stage-icon">✅</div>
                            <div class="stage-text">File Upload</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <div class="progress-text">100%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage completed" id="mappingStage2">
                            <div class="stage-icon">✅</div>
                            <div class="stage-text">Column Mapping</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <div class="progress-text">100%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage active" id="mappingStage3">
                            <div class="stage-icon">⚙️</div>
                            <div class="stage-text">Data Validation</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="progress-text">0%</div>
                            </div>
                        </div>
                        
                        <div class="progress-stage" id="mappingStage4">
                            <div class="stage-icon">💾</div>
                            <div class="stage-text">Saving to Database</div>
                            <div class="stage-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="progress-text">0%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overall-progress">
                        <div class="overall-bar">
                            <div class="overall-fill" id="mappingOverallFill" style="width: 50%"></div>
                        </div>
                        <div class="overall-text">
                            <span id="mappingOverallPercent">50%</span> Complete
                            <div id="mappingCurrentTask">Validating mapped data...</div>
                        </div>
                    </div>
                    
                    <div class="progress-details">
                        <div class="detail-item">
                            <span class="detail-label">Processing Status:</span>
                            <span class="detail-value" id="processingStatus">In Progress</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Current Stage:</span>
                            <span class="detail-value" id="currentStage">Data Validation</span>
                        </div>
                    </div>
                </div>
                
                <form action="" method="post">
                    <table class="user-mapping-table">
                        <thead>
                            <tr>
                                <th>CSV Column</th>
                                <th>Sample Data</th>
                                <th>Map To</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $header = $mappingResult['header'];
                            $sampleRow = !empty($mappingResult['sample']) ? $mappingResult['sample'][0] : [];
                            
                            foreach ($header as $index => $column):
                                $sampleValue = isset($sampleRow[$index]) ? $sampleRow[$index] : '';
                                
                                // Get mapping info
                                $targetField = '';
                                $confidence = null;
                                
                                if ($mappingResult['status'] === 'success') {
                                    $targetField = isset($mappingResult['mapping'][$column]) ? 
                                        $mappingResult['mapping'][$column] : '';
                                    $confidence = 100;
                                } else {
                                    $targetField = isset($mappingResult['suggestions'][$column]['mapping']) ? 
                                        $mappingResult['suggestions'][$column]['mapping'] : '';
                                    $confidence = isset($mappingResult['suggestions'][$column]['confidence']) ? 
                                        $mappingResult['suggestions'][$column]['confidence'] : 0;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($column); ?></td>
                                <td><?php echo htmlspecialchars($sampleValue); ?></td>
                                <td>
                                <select name="mapping[<?php echo htmlspecialchars($column); ?>]" class="user-field-select">
                                    <option value="">-- Ignore this column --</option>
                                    <?php foreach ($systemFields as $field): ?>
                                        <?php 
                                        $selected = '';
                                        if ($targetField === $field['value']) {
                                            $selected = 'selected'; 
                                        } elseif (empty($targetField) && isset($field['default_column']) && $column === $field['default_column']) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo $field['value']; ?>" <?php echo $selected; ?> data-field="<?php echo $field['value']; ?>">
                                            <?php echo $field['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </td>
                                <td>
                                    <?php if ($confidence !== null): ?>
                                        <div class="user-confidence-bar">
                                            <div class="user-confidence-fill" style="width: <?php echo $confidence; ?>%"></div>
                                            <span><?php echo round($confidence); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="user-form-actions">
                        <button type="submit" name="confirm_mapping" class="user-btn-primary">Confirm Mapping & Import Data</button>
                        <a href="index.php" class="user-btn-secondary">Cancel</a>
                    </div>
                </form>
                
                <div class="user-sample-data">
                    <h3>Sample Data Preview</h3>
                    <div class="user-table-container">
                        <table class="user-data-table">
                            <thead>
                                <tr>
                                    <?php foreach ($header as $column): ?>
                                        <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappingResult['sample'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
        
        <?php include 'user_footer.php'; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldSelects = document.querySelectorAll('.user-field-select');
            
            // Function to update available options
            function updateAvailableOptions() {
                // Get all currently selected values
                const selectedValues = Array.from(fieldSelects).map(select => select.value).filter(Boolean);
                
                // For each select element
                fieldSelects.forEach(select => {
                    const currentValue = select.value;
                    
                    // Check each option
                    Array.from(select.options).forEach(option => {
                        const optionValue = option.value;
                        
                        // Skip the empty option
                        if (!optionValue) return;
                        
                        // If this option is selected in this select, keep it enabled
                        if (optionValue === currentValue) {
                            option.disabled = false;
                            return;
                        }
                        
                        // If this option is selected in another select, disable it
                        option.disabled = selectedValues.includes(optionValue);
                    });
                });
            }
            
            // Add change event listeners to all selects
            fieldSelects.forEach(select => {
                select.addEventListener('change', updateAvailableOptions);
            });
            
            // Initial update
            updateAvailableOptions();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const progressDiv = document.getElementById('mappingProgress');
            let formSubmitted = false;
            let animationComplete = false;
            
            form.addEventListener('submit', function(e) {
                // CRITICAL: Prevent the default form submission
                e.preventDefault();
                
                if (formSubmitted) return; // Prevent multiple submissions
                formSubmitted = true;
                
                // Show progress immediately when form is submitted
                progressDiv.style.display = 'block';
                form.style.display = 'none';
                
                // Start the animation sequence
                runProgressAnimation();
            });
            
            function runProgressAnimation() {
                // Stage 3: Data Validation - Slower progression with more steps
                setTimeout(() => {
                    updateMappingProgress(3, 20, 'Initializing data validation...');
                }, 500);
                
                setTimeout(() => {
                    updateMappingProgress(3, 40, 'Checking data types...');
                }, 1200);
                
                setTimeout(() => {
                    updateMappingProgress(3, 60, 'Validating data values...');
                }, 2000);
                
                setTimeout(() => {
                    updateMappingProgress(3, 80, 'Verifying data integrity...');
                }, 2800);
                
                setTimeout(() => {
                    updateMappingProgress(3, 100, 'Data validation completed ✓');
                    completeStage(3);
                    updateProcessingStatus('Validation Complete', 'Database Operations');
                }, 3500);
                
                // Stage 4: Database Saving - More detailed progression
                setTimeout(() => {
                    activateStage(4);
                    updateMappingProgress(4, 15, 'Preparing database transaction...');
                    updateProcessingStatus('In Progress', 'Database Saving');
                }, 4000);
                
                setTimeout(() => {
                    updateMappingProgress(4, 35, 'Creating data records...');
                }, 4700);
                
                setTimeout(() => {
                    updateMappingProgress(4, 55, 'Inserting traffic data...');
                }, 5400);
                
                setTimeout(() => {
                    updateMappingProgress(4, 75, 'Building database indexes...');
                }, 6100);
                
                setTimeout(() => {
                    updateMappingProgress(4, 90, 'Finalizing transaction...');
                }, 6800);
                
                setTimeout(() => {
                    updateMappingProgress(4, 100, 'Data saved successfully! ✓');
                    completeStage(4);
                    updateOverallProgress(100, 'Import completed successfully! 🎉');
                    updateProcessingStatus('Complete', 'Ready');
                }, 7500);
                
                // Extended buffer time to show completion
                setTimeout(() => {
                    updateOverallProgress(100, 'Redirecting to dashboard...');
                }, 8500);
                
                // CRITICAL: Only submit the form AFTER animation completes
                setTimeout(() => {
                    animationComplete = true;
                    console.log('Animation complete, submitting form...');
                    
                    // Create a new form submission with the original data
                    const formData = new FormData(form);
                    
                    // Submit via fetch to process the PHP
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        redirect: 'manual' // Handle redirects manually
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response redirected:', response.redirected);
                        
                        // Check for redirect response
                        if (response.status === 302 || response.status === 301 || response.type === 'opaqueredirect') {
                            // Get the redirect location from headers
                            const location = response.headers.get('Location') || 'overview.php';
                            console.log('Redirect location:', location);
                            window.location.href = location;
                            return;
                        }
                        
                        // Check if response is ok
                        if (response.ok) {
                            return response.text();
                        } else {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    })
                    .then(data => {
                        if (data) {
                            // Check if the response contains a redirect meta tag or JavaScript redirect
                            if (data.includes('header("Location:') || data.includes('window.location')) {
                                // Force redirect to overview page
                                window.location.href = 'overview.php';
                                return;
                            }
                            
                            // If there's no redirect but we have data, check for errors
                            if (data.includes('error') || data.includes('Error')) {
                                // Re-render the page with errors
                                document.open();
                                document.write(data);
                                document.close();
                            } else {
                                // Successful processing, redirect to overview
                                window.location.href = 'overview.php';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting form:', error);
                        
                        // Fallback: Try direct redirect to overview
                        // Most likely the form submission succeeded but redirect detection failed
                        console.log('Attempting fallback redirect to overview.php');
                        window.location.href = 'overview.php';
                        
                        // If that fails, submit the form normally as last resort
                        setTimeout(() => {
                            form.submit();
                        }, 1000);
                    });
                }, 9500);
            }
            
            function updateMappingProgress(stage, percent, message) {
                const stageElement = document.getElementById(`mappingStage${stage}`);
                const progressFill = stageElement.querySelector('.progress-fill');
                const progressText = stageElement.querySelector('.progress-text');
                
                if (progressFill) {
                    progressFill.style.width = `${percent}%`;
                    
                    // Add visual feedback for completion
                    if (percent === 100) {
                        progressFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
                        progressFill.style.boxShadow = '0 2px 8px rgba(40, 167, 69, 0.4)';
                    }
                }
                if (progressText) {
                    progressText.textContent = `${percent}%`;
                }
                
                // Calculate overall progress (stages 1&2 = 50%, stage 3 = 25%, stage 4 = 25%)
                let overallPercent = 50; // Start from 50% (stages 1&2 completed)
                if (stage === 3) {
                    overallPercent += (percent * 0.25);
                } else if (stage === 4) {
                    overallPercent = 75 + (percent * 0.25);
                }
                
                updateOverallProgress(overallPercent, message);
            }
            
            function updateOverallProgress(percent, message) {
                const overallFill = document.getElementById('mappingOverallFill');
                const overallPercent = document.getElementById('mappingOverallPercent');
                const currentTask = document.getElementById('mappingCurrentTask');
                
                if (overallFill) {
                    overallFill.style.width = `${Math.round(percent)}%`;
                    
                    // Enhanced visual feedback for completion
                    if (percent >= 100) {
                        overallFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
                        overallFill.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.5)';
                        
                        // Add success animation
                        overallFill.style.animation = 'pulse-success 1.5s infinite';
                    }
                }
                if (overallPercent) {
                    overallPercent.textContent = `${Math.round(percent)}%`;
                    
                    if (percent >= 100) {
                        overallPercent.style.color = '#28a745';
                        overallPercent.style.fontWeight = '700';
                    }
                }
                if (currentTask) {
                    currentTask.textContent = message;
                }
            }
            
            function updateProcessingStatus(status, stage) {
                const processingStatus = document.getElementById('processingStatus');
                const currentStage = document.getElementById('currentStage');
                
                if (processingStatus) {
                    processingStatus.textContent = status;
                    if (status === 'Complete') {
                        processingStatus.style.color = '#28a745';
                        processingStatus.style.fontWeight = '600';
                    }
                }
                if (currentStage) {
                    currentStage.textContent = stage;
                }
            }
            
            function activateStage(stageIndex) {
                const stageElement = document.getElementById(`mappingStage${stageIndex}`);
                stageElement.classList.remove('completed');
                stageElement.classList.add('active');
                
                const icon = stageElement.querySelector('.stage-icon');
                icon.textContent = '⚙️';
                
                // Add pulsing animation to active stage
                icon.style.animation = 'pulse 2s infinite';
            }
            
            function completeStage(stageIndex) {
                const stageElement = document.getElementById(`mappingStage${stageIndex}`);
                stageElement.classList.remove('active');
                stageElement.classList.add('completed');
                
                const icon = stageElement.querySelector('.stage-icon');
                icon.textContent = '✅';
                icon.style.animation = 'bounce 0.6s ease';
                
                const progressFill = stageElement.querySelector('.progress-fill');
                const progressText = stageElement.querySelector('.progress-text');
                
                if (progressFill) {
                    progressFill.style.width = '100%';
                    progressFill.style.background = 'linear-gradient(90deg, #28a745 0%, #20c997 100%)';
                }
                if (progressText) {
                    progressText.textContent = '100%';
                    progressText.style.color = '#28a745';
                    progressText.style.fontWeight = '600';
                }
            }
        });

        // Handle browser back button to prevent stuck state
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from cache, reset form visibility
                const form = document.querySelector('form');
                const progressDiv = document.getElementById('mappingProgress');
                
                if (form) form.style.display = 'block';
                if (progressDiv) progressDiv.style.display = 'none';
            }
        });
        </script>
</body>
</html>