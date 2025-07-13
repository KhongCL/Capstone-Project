<?php
require_once '../auth/admin_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';

$mappingsFile = __DIR__ . '/../config/csv_mappings.json';
$message = '';
$error = '';

// Handle form submission for updating mappings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mappings'])) {
    $mappings = [];
    
    foreach ($_POST['formats'] as $formatId => $format) {
        // Better format key generation - handle spaces and special characters
        $formatName = trim($format['name']);
        if (empty($formatName)) {
            continue; // Skip empty format names
        }
        
        // Convert spaces to underscores and remove special characters
        $formatKey = strtolower(preg_replace('/[^a-zA-Z0-9_\s]/', '', $formatName));
        $formatKey = str_replace(' ', '_', $formatKey);
        $formatKey = preg_replace('/_{2,}/', '_', $formatKey); // Remove multiple underscores
        $formatKey = trim($formatKey, '_'); // Remove leading/trailing underscores
        
        if (empty($formatKey)) {
            continue; // Skip if format key becomes empty after sanitization
        }
        
        $mappings[$formatKey] = [
            'format_detection' => array_filter(array_map('trim', explode(',', $format['detection']))),
            'column_mappings' => [],
            'data_types' => []
        ];
        
        if (isset($format['columns'])) {
            foreach ($format['columns'] as $columnKey => $mapping) {
                if (!empty($mapping['source'])) {
                    $sourceColumnName = trim($mapping['source']);
                    
                    // Handle custom target fields
                    $targetField = '';
                    if (!empty($mapping['target'])) {
                        if ($mapping['target'] === '__custom__' && !empty($mapping['custom_target'])) {
                            $customTarget = strtolower(trim($mapping['custom_target']));
                            if (validateSystemFieldName($customTarget)) {
                                $targetField = $customTarget;
                                // Add to system fields for future use
                                $systemFields[$customTarget] = ucwords(str_replace('_', ' ', $customTarget));
                            } else {
                                $error = "Invalid custom field name: $customTarget. Use only lowercase letters, numbers, and underscores.";
                                continue;
                            }
                        } else {
                            $targetField = $mapping['target'];
                        }
                    }
                    
                    // Handle custom data types
                    $dataType = 'string'; // default
                    if (!empty($mapping['type'])) {
                        if ($mapping['type'] === '__custom_type__' && !empty($mapping['custom_type'])) {
                            $customType = trim($mapping['custom_type']);
                            if (validateDataTypeName($customType)) {
                                $dataType = $customType;
                                // Add to data types for future use
                                $dataTypes[$customType] = ucwords(str_replace('_', ' ', $customType));
                            } else {
                                $error = "Invalid custom data type: $customType";
                                continue;
                            }
                        } else {
                            $dataType = $mapping['type'];
                        }
                    }
                    
                    if (!empty($targetField)) {
                        $mappings[$formatKey]['column_mappings'][$sourceColumnName] = $targetField;
                        $mappings[$formatKey]['data_types'][$sourceColumnName] = $dataType;
                    }
                }
            }
        }
    }
    
    // Save updated mappings
    if (file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT))) {
        $message = 'CSV mappings successfully updated.';
        
        // Also update the database mappings
        try {
            updateDatabaseMappings($conn, $mappings);
            updateMetricTypes($conn, $mappings); // Add this line
        } catch (Exception $e) {
            $error = 'CSV mappings saved but database update failed: ' . $e->getMessage();
        }
    } else {
        $error = 'Error saving CSV mappings.';
    }
}

// Add validation function for data type names
function validateDataTypeName($typeName) {
    // Check format: only letters, numbers, and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $typeName)) {
        return false;
    }
    
    // Check length
    if (strlen($typeName) < 2 || strlen($typeName) > 30) {
        return false;
    }
    
    return true;
}

// Load current mappings
if (!file_exists($mappingsFile)) {
    // Create default mappings if file doesn't exist
    $defaultMappings = [
        "ga4_traffic_acquisition" => [
            "format_detection" => [
                "Session primary channel group (Default channel group)",
                "Sessions", 
                "Engaged sessions", 
                "Engagement rate"
            ],
            "column_mappings" => [
                "Session primary channel group (Default channel group)" => "traffic_source",
                "Sessions" => "visits",
                "Engaged sessions" => "engaged_sessions",
                "Engagement rate" => "bounce_rate",
                "Average engagement time per session" => "avg_session_duration",
                "Events per session" => "events_per_session",
                "Event count" => "event_count",
                "Key events" => "key_events",
                "Session key event rate" => "session_key_event_rate",
                "Total revenue" => "total_revenue"
            ],
            "data_types" => [
                "Session primary channel group (Default channel group)" => "string",
                "Sessions" => "integer",
                "Engaged sessions" => "integer",
                "Engagement rate" => "float",
                "Average engagement time per session" => "float",
                "Events per session" => "float",
                "Event count" => "integer",
                "Key events" => "integer",
                "Session key event rate" => "float",
                "Total revenue" => "currency"
            ]
        ]
    ];
    
    if (!is_dir(dirname($mappingsFile))) {
        mkdir(dirname($mappingsFile), 0755, true);
    }
    
    file_put_contents($mappingsFile, json_encode($defaultMappings, JSON_PRETTY_PRINT));
}

$mappings = json_decode(file_get_contents($mappingsFile), true) ?: [];

// Function to update database mappings
function updateDatabaseMappings($conn, $mappings) {
    // Clear existing mappings
    $conn->query("DELETE FROM column_mapping");
    $conn->query("DELETE FROM csv_format");
    
    $formatId = 1;
    foreach ($mappings as $formatKey => $format) {
        // Insert format
        $stmt = $conn->prepare("INSERT INTO csv_format (FormatID, FormatName, ReportType, AdminUserID, CreatedAt) VALUES (?, ?, ?, 1, NOW())");
        $formatName = ucfirst(str_replace('_', ' ', $formatKey));
        $reportType = $formatName;
        $stmt->bind_param("iss", $formatId, $formatName, $reportType);
        $stmt->execute();
        
        // Insert column mappings
        foreach ($format['column_mappings'] as $csvCol => $systemField) {
            $dataType = $format['data_types'][$csvCol] ?? 'string';
            $stmt = $conn->prepare("INSERT INTO column_mapping (FormatID, CSVColumnName, SystemFieldName, DataType) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $formatId, $csvCol, $systemField, $dataType);
            $stmt->execute();
        }
        
        $formatId++;
    }
}

// NEW: Function to update metric types
function updateMetricTypes($conn, $mappings) {
    error_log("=== Starting updateMetricTypes function ===");
    
    $predefinedSystemFields = [
        'traffic_source' => 'Traffic Source',
        'traffic_medium' => 'Traffic Medium', 
        'visits' => 'Number of visits/sessions',
        'visitors' => 'Number of unique visitors',
        'page_views' => 'Total number of page views',
        'bounce_rate' => 'Bounce rate percentage',
        'avg_session_duration' => 'Average session duration',
        'engaged_sessions' => 'Number of engaged sessions',
        'events_per_session' => 'Average events per session',
        'event_count' => 'Total event count',
        'key_events' => 'Number of key events/conversions',
        'session_key_event_rate' => 'Session key event rate',
        'total_revenue' => 'Total revenue generated'
    ];
    
    // Get all unique system field names from mappings
    $usedSystemFields = [];
    foreach ($mappings as $formatKey => $format) {
        error_log("Processing format: $formatKey");
        if (isset($format['column_mappings'])) {
            foreach ($format['column_mappings'] as $csvCol => $systemField) {
                $usedSystemFields[$systemField] = true;
                error_log("Found system field: $systemField (from CSV column: $csvCol)");
            }
        }
    }
    
    error_log("Total unique system fields found: " . count($usedSystemFields));
    error_log("System fields: " . implode(", ", array_keys($usedSystemFields)));
    
    // Insert missing metric types
    $insertedCount = 0;
    $skippedCount = 0;
    
    foreach ($usedSystemFields as $systemField => $unused) {
        // Use predefined description if available, otherwise create a custom one
        if (isset($predefinedSystemFields[$systemField])) {
            $description = $predefinedSystemFields[$systemField];
        } else {
            // Create description for custom system fields
            $description = "Custom metric: " . ucwords(str_replace('_', ' ', $systemField));
            error_log("Creating custom metric type for: $systemField");
        }
        
        error_log("Attempting to insert metric type: $systemField with description: $description");
        
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO metric_type (MetricName, Description) VALUES (?, ?)");
            $stmt->bind_param("ss", $systemField, $description);
            $result = $stmt->execute();
            
            if ($result) {
                $affectedRows = $conn->affected_rows;
                if ($affectedRows > 0) {
                    $insertedCount++;
                    error_log("Successfully inserted metric type: $systemField");
                } else {
                    $skippedCount++;
                    error_log("Metric type already exists: $systemField");
                }
            } else {
                error_log("Failed to insert metric type: $systemField - Error: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Exception inserting metric type $systemField: " . $e->getMessage());
        }
    }
    
    error_log("Metric types update complete. Inserted: $insertedCount, Skipped: $skippedCount");
    error_log("=== End updateMetricTypes function ===");
}

function cleanupOrphanedMetricTypes($conn, $mappings) {
    error_log("=== Starting cleanup of orphaned metric types ===");
    
    // Get all system fields currently in use
    $usedSystemFields = [];
    foreach ($mappings as $format) {
        if (isset($format['column_mappings'])) {
            foreach ($format['column_mappings'] as $systemField) {
                $usedSystemFields[] = $systemField;
            }
        }
    }
    
    if (empty($usedSystemFields)) {
        error_log("No system fields in use, skipping cleanup");
        return;
    }
    
    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($usedSystemFields) - 1) . '?';
    
    // Find metric types that are no longer used
    $query = "SELECT MetricName FROM metric_type WHERE MetricName NOT IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($usedSystemFields)), ...$usedSystemFields);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orphanedMetrics = [];
    while ($row = $result->fetch_assoc()) {
        $orphanedMetrics[] = $row['MetricName'];
    }
    
    if (!empty($orphanedMetrics)) {
        error_log("Found orphaned metric types: " . implode(", ", $orphanedMetrics));
        
        // Optional: Remove orphaned metric types (commented out for safety)
        // foreach ($orphanedMetrics as $metricName) {
        //     $deleteStmt = $conn->prepare("DELETE FROM metric_type WHERE MetricName = ?");
        //     $deleteStmt->bind_param("s", $metricName);
        //     $deleteStmt->execute();
        //     error_log("Removed orphaned metric type: $metricName");
        // }
    } else {
        error_log("No orphaned metric types found");
    }
    
    error_log("=== End cleanup of orphaned metric types ===");
}

function validateSystemFieldName($fieldName) {
    // Check format: only lowercase letters, numbers, and underscores
    if (!preg_match('/^[a-z0-9_]+$/', $fieldName)) {
        return false;
    }
    
    // Check length
    if (strlen($fieldName) < 3 || strlen($fieldName) > 50) {
        return false;
    }
    
    // Check it doesn't start or end with underscore
    if (strpos($fieldName, '_') === 0 || strrpos($fieldName, '_') === strlen($fieldName) - 1) {
        return false;
    }
    
    return true;
}

// ENHANCED: Update the main form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mappings'])) {
    $mappings = [];
    
    foreach ($_POST['formats'] as $formatId => $format) {
        // Better format key generation - handle spaces and special characters
        $formatName = trim($format['name']);
        if (empty($formatName)) {
            continue; // Skip empty format names
        }
        
        // Convert spaces to underscores and remove special characters
        $formatKey = strtolower(preg_replace('/[^a-zA-Z0-9_\s]/', '', $formatName));
        $formatKey = str_replace(' ', '_', $formatKey);
        $formatKey = preg_replace('/_{2,}/', '_', $formatKey); // Remove multiple underscores
        $formatKey = trim($formatKey, '_'); // Remove leading/trailing underscores
        
        if (empty($formatKey)) {
            continue; // Skip if format key becomes empty after sanitization
        }
        
        $mappings[$formatKey] = [
            'format_detection' => array_filter(array_map('trim', explode(',', $format['detection']))),
            'column_mappings' => [],
            'data_types' => []
        ];
        
        if (isset($format['columns'])) {
            foreach ($format['columns'] as $columnKey => $mapping) {
                if (!empty($mapping['source'])) {
                    $sourceColumnName = trim($mapping['source']);
                    
                    // Handle custom target fields
                    $targetField = '';
                    if (!empty($mapping['target'])) {
                        if ($mapping['target'] === '__custom__' && !empty($mapping['custom_target'])) {
                            $customTarget = strtolower(trim($mapping['custom_target']));
                            if (validateSystemFieldName($customTarget)) {
                                $targetField = $customTarget;
                            } else {
                                $error = "Invalid custom field name: $customTarget. Use only lowercase letters, numbers, and underscores.";
                                continue;
                            }
                        } else {
                            $targetField = $mapping['target'];
                        }
                    }
                    
                    // Handle custom data types - THIS WAS MISSING!
                    $dataType = 'string'; // default
                    if (!empty($mapping['type'])) {
                        if ($mapping['type'] === '__custom_type__' && !empty($mapping['custom_type'])) {
                            $customType = trim($mapping['custom_type']);
                            if (validateDataTypeName($customType)) {
                                $dataType = $customType;
                                error_log("Using custom data type: $customType for field: $targetField");
                            } else {
                                $error = "Invalid custom data type: $customType. Use only letters, numbers, and underscores.";
                                continue;
                            }
                        } else {
                            $dataType = $mapping['type'];
                        }
                    }
                    
                    if (!empty($targetField)) {
                        $mappings[$formatKey]['column_mappings'][$sourceColumnName] = $targetField;
                        $mappings[$formatKey]['data_types'][$sourceColumnName] = $dataType; // Make sure this saves the custom data type
                        error_log("Saved mapping: $sourceColumnName -> $targetField with data type: $dataType");
                    }
                }
            }
        }
    }
    
    error_log("Processing form submission with " . count($mappings) . " formats");
    
    // Save updated mappings
    if (file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT))) {
        $message = 'CSV mappings successfully updated.';
        error_log("CSV mappings saved to JSON file successfully");
        
        // Also update the database mappings
        try {
            error_log("Starting database updates...");
            updateDatabaseMappings($conn, $mappings);
            error_log("Database mappings updated successfully");
            
            updateMetricTypes($conn, $mappings);
            error_log("Metric types updated successfully");
            
            // Optional: Clean up orphaned metric types (uncomment if needed)
            // cleanupOrphanedMetricTypes($conn, $mappings);
            
        } catch (Exception $e) {
            $error = 'CSV mappings saved but database update failed: ' . $e->getMessage();
            error_log("Database update failed: " . $e->getMessage());
        }
    } else {
        $error = 'Error saving CSV mappings.';
        error_log("Failed to save CSV mappings to JSON file");
    }
}


// Base predefined system fields (commonly used ones)
$predefinedSystemFields = [
    'traffic_source' => 'Traffic Source',
    'traffic_medium' => 'Traffic Medium',
    'visits' => 'Visits/Sessions',
    'unique_visitors' => 'Unique Visitors',
    'page_views' => 'Page Views',
    'bounce_rate' => 'Bounce Rate',
    'avg_session_duration' => 'Avg. Session Duration',
    'engaged_sessions' => 'Engaged Sessions',
    'events_per_session' => 'Events Per Session',
    'event_count' => 'Event Count',
    'key_events' => 'Key Events',
    'session_key_event_rate' => 'Session Key Event Rate',
    'total_revenue' => 'Total Revenue'
];

// Get all system fields that are currently used in mappings (dynamic)
$usedSystemFields = [];
foreach ($mappings as $format) {
    if (isset($format['column_mappings'])) {
        foreach ($format['column_mappings'] as $csvCol => $systemField) {
            // Only include fields that follow the proper naming convention
            if (preg_match('/^[a-z0-9_]+$/', $systemField)) {
                $usedSystemFields[$systemField] = ucwords(str_replace('_', ' ', $systemField));
            }
        }
    }
}

// Get all system fields from the metric_type table (in case some were added directly to DB)
$dbSystemFields = [];
$result = $conn->query("SELECT DISTINCT MetricName FROM metric_type WHERE MetricName REGEXP '^[a-z0-9_]+$' ORDER BY MetricName");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Only include fields that follow the proper naming convention
        if (preg_match('/^[a-z0-9_]+$/', $row['MetricName'])) {
            $dbSystemFields[$row['MetricName']] = ucwords(str_replace('_', ' ', $row['MetricName']));
        }
    }
}

// Combine all system fields: predefined + used in mappings + from database
$systemFields = array_merge($predefinedSystemFields, $usedSystemFields, $dbSystemFields);

// Remove duplicates and sort
$systemFields = array_unique($systemFields, SORT_REGULAR);
ksort($systemFields);

// Get available data types
$dataTypes = [
    'string' => 'String',
    'integer' => 'Integer', 
    'float' => 'Float',
    'percentage' => 'Percentage',
    'time' => 'Time',
    'currency' => 'Currency',
    'date' => 'Date',
    'datetime' => 'Date & Time',
    'url' => 'URL',
    'email' => 'Email',
    'boolean' => 'Boolean (True/False)',
    'json' => 'JSON Data'
];

// Get all custom data types from existing mappings
$customDataTypes = [];
foreach ($mappings as $format) {
    if (isset($format['data_types'])) {
        foreach ($format['data_types'] as $dataType) {
            if (!array_key_exists($dataType, $dataTypes)) {
                $customDataTypes[$dataType] = ucwords(str_replace('_', ' ', $dataType));
            }
        }
    }
}

// Combine standard and custom data types for display
$allDataTypes = array_merge($dataTypes, $customDataTypes);
ksort($allDataTypes);


error_log("DEBUG: dataTypes = " . print_r($dataTypes, true));
error_log("DEBUG: customDataTypes = " . print_r($customDataTypes, true)); 
error_log("DEBUG: allDataTypes = " . print_r($allDataTypes, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CSV Mappings Configuration</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php 
            $title = "CSV Mappings";
            $active_page = "mappings";
            include 'admin_header.php';
        ?>

        <main>
            <section class="admin-section">
                <h2>Manage CSV Format Mappings</h2>
                
                <div class="info-box">
                    <p><strong>Instructions:</strong> Configure how different CSV formats are detected and mapped to system fields. Each format needs detection columns to identify it and column mappings to transform the data.</p>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" id="mappingsForm">
                    <div id="formats-container">
                        <?php $formatCount = 0; ?>
                        <?php foreach ($mappings as $formatKey => $format): ?>
                            <?php $formatCount++; ?>
                            <div class="format-section" id="format-<?php echo $formatCount; ?>">
                                <div class="format-header" onclick="toggleFormat(<?php echo $formatCount; ?>)">
                                    <h3>
                                        <i class="fas fa-file-csv"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $formatKey)); ?>
                                    </h3>
                                    <div class="header-right">
                                        <span class="format-indicator"><?php echo count($format['column_mappings']); ?> mappings</span>
                                        <span class="toggle-icon">▼</span>
                                    </div>
                                </div>
                                
                                <div class="format-content" id="format-content-<?php echo $formatCount; ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label><i class="fas fa-tag"></i> Format Name:</label>
                                            <input type="text" name="formats[<?php echo $formatCount; ?>][name]" 
                                                   value="<?php echo htmlspecialchars($formatKey); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label><i class="fas fa-search"></i> Detection Columns:</label>
                                            <input type="text" name="formats[<?php echo $formatCount; ?>][detection]" 
                                                   value="<?php echo htmlspecialchars(implode(',', $format['format_detection'])); ?>" required>
                                            <small class="help-text">Comma-separated list of column names that identify this format</small>
                                        </div>
                                    </div>
                                    
                                    <h4><i class="fas fa-arrows-alt-h"></i> Column Mappings</h4>
                                    <div class="table-container">
                                        <table class="mapping-table">
                                            <thead>
                                                <tr>
                                                    <th>Source Column</th>
                                                    <th>Target Field</th>
                                                    <th>Data Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="columns-container" id="columns-<?php echo $formatCount; ?>">
                                                <?php foreach ($format['column_mappings'] as $sourceCol => $targetField): ?>
                                                    <tr class="column-row">
                                                        <td>
                                                            <input type="text" name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][source]" 
                                                                   value="<?php echo htmlspecialchars($sourceCol); ?>" required>
                                                        </td>
                                                        <td>
                                                            <select name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][target]" required class="target-select">
                                                                <option value="">-- Select Target --</option>
                                                                <?php foreach ($systemFields as $value => $label): ?>
                                                                    <option value="<?php echo $value; ?>" <?php echo $targetField === $value ? 'selected' : ''; ?>>
                                                                        <?php echo $label; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                                <option value="__custom__">-- Create Custom Field --</option>
                                                            </select>
                                                            <input type="text" 
                                                                name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][custom_target]" 
                                                                placeholder="Enter custom field name (e.g., custom_metric)" 
                                                                class="custom-field-input" 
                                                                style="display: none; margin-top: 5px; width: 100%;"
                                                                pattern="[a-z0-9_]+"
                                                                title="Only lowercase letters, numbers, and underscores allowed">
                                                        </td>
                                                        <td>
                                                            <select name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][type]" required class="type-select">
                                                                <?php 
                                                                $currentType = $format['data_types'][$sourceCol] ?? 'string';
                                                                $isCustomType = !array_key_exists($currentType, $dataTypes);
                                                                
                                                                // Show ALL data types (predefined + custom) in the dropdown
                                                                foreach ($allDataTypes as $value => $label): 
                                                                ?>
                                                                    <option value="<?php echo $value; ?>" <?php echo ($currentType === $value) ? 'selected' : ''; ?>>
                                                                        <?php echo $label; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                                <option value="__custom_type__">-- Create New Data Type --</option>
                                                            </select>
                                                            <input type="text" 
                                                                name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][custom_type]" 
                                                                placeholder="Enter new data type" 
                                                                class="custom-type-input" 
                                                                value=""
                                                                style="display: none; margin-top: 5px; width: 100%;">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn-small btn-danger remove-column" title="Remove Column">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn-small add-column" data-format="<?php echo $formatCount; ?>">
                                            <i class="fas fa-plus"></i> Add Column
                                        </button>
                                        <button type="button" class="btn-small btn-danger remove-format" data-format="<?php echo $formatCount; ?>">
                                            <i class="fas fa-trash"></i> Remove Format
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="main-actions">
                        <button type="button" class="btn btn-secondary" id="add-format">
                            <i class="fas fa-plus"></i> Add New Format
                        </button>
                        <button type="submit" name="update_mappings" class="btn">
                            <i class="fas fa-save"></i> Save All Mappings
                        </button>
                        <a href="upload_sample_data.php" class="btn btn-info">
                            <i class="fas fa-upload"></i> Upload Sample Data
                        </a>
                    </div>
                </form>
            </section>
        </main>
        
        <?php include 'admin_footer.php'; ?>
    </div>
    
<script>
    // System fields and data types for dynamic form generation
    const systemFields = <?php echo json_encode($systemFields); ?>;
    const dataTypes = <?php echo json_encode($allDataTypes); ?>; // Use allDataTypes instead of just dataTypes
    
    // DEBUG: Log the data types to console
    console.log('All Data Types:', dataTypes);
    console.log('Predefined Data Types:', <?php echo json_encode($dataTypes); ?>);
    console.log('Custom Data Types:', <?php echo json_encode($customDataTypes); ?>);

    // Enhanced function to create target field select with custom option
    function createTargetFieldSelect(formatId, timestamp, selectedValue = '') {
        let systemFieldsOptions = '<option value="">-- Select Target --</option>';
        
        // Add existing system fields
        for (const [value, label] of Object.entries(systemFields)) {
            const selected = selectedValue === value ? 'selected' : '';
            systemFieldsOptions += `<option value="${value}" ${selected}>${label}</option>`;
        }
        
        // Add option to create custom field
        const customSelected = selectedValue === '__custom__' ? 'selected' : '';
        systemFieldsOptions += `<option value="__custom__" ${customSelected}>-- Create Custom Field --</option>`;
        
        return `
            <select name="formats[${formatId}][columns][${timestamp}][target]" required class="target-select">
                ${systemFieldsOptions}
            </select>
            <input type="text" 
                   name="formats[${formatId}][columns][${timestamp}][custom_target]" 
                   placeholder="Enter custom field name (e.g., custom_metric)" 
                   class="custom-field-input" 
                   style="display: ${selectedValue === '__custom__' ? 'block' : 'none'}; margin-top: 5px; width: 100%;"
                   pattern="[a-z0-9_]+"
                   title="Only lowercase letters, numbers, and underscores allowed">
        `;
    }
    
    // Enhanced function to create data type select with custom option
    function createDataTypeSelect(formatId, timestamp, selectedValue = 'string') {
        let dataTypesOptions = '';
        
        // Check if selectedValue is a custom type (not in the original predefined dataTypes)
        const predefinedDataTypes = <?php echo json_encode($dataTypes); ?>; // Original predefined types only
        const isCustomType = selectedValue && 
            !Object.prototype.hasOwnProperty.call(predefinedDataTypes, selectedValue) && 
            selectedValue !== '__custom_type__';
        
        // Add ALL available data types (including previously created custom ones)
        // Use the global dataTypes variable which contains allDataTypes
        for (const [value, label] of Object.entries(dataTypes)) {
            const selected = (selectedValue === value) ? 'selected' : '';
            dataTypesOptions += `<option value="${value}" ${selected}>${label}</option>`;
        }
        
        // Add option to create NEW custom data type
        const customSelected = (selectedValue === '__custom_type__') ? 'selected' : '';
        dataTypesOptions += `<option value="__custom_type__" ${customSelected}>-- Create New Data Type --</option>`;
        
        const customInputValue = isCustomType ? selectedValue : '';
        const customInputDisplay = (selectedValue === '__custom_type__' || isCustomType) ? 'block' : 'none';
        
        return `
            <select name="formats[${formatId}][columns][${timestamp}][type]" required class="type-select">
                ${dataTypesOptions}
            </select>
            <input type="text" 
                name="formats[${formatId}][columns][${timestamp}][custom_type]" 
                placeholder="Enter new data type" 
                class="custom-type-input" 
                value="${customInputValue}"
                style="display: ${customInputDisplay}; margin-top: 5px; width: 100%;">
        `;
    }

    function updateDataTypeOptions() {
        console.log('Updating data type options with current dataTypes:', dataTypes);
        
        // Update all existing data type selects with the latest options
        document.querySelectorAll('.type-select').forEach(select => {
            const currentValue = select.value;
            const predefinedDataTypes = <?php echo json_encode($dataTypes); ?>;
            const isCustomType = currentValue && 
                !Object.prototype.hasOwnProperty.call(predefinedDataTypes, currentValue) && 
                currentValue !== '__custom_type__';
            
            console.log(`Updating select with current value: ${currentValue}, isCustomType: ${isCustomType}`);
            
            // Clear existing options
            select.innerHTML = '';
            
            // Add all available data types
            for (const [value, label] of Object.entries(dataTypes)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                if (currentValue === value) {
                    option.selected = true;
                }
                select.appendChild(option);
            }
            
            // Add custom type option
            const customOption = document.createElement('option');
            customOption.value = '__custom_type__';
            customOption.textContent = '-- Create New Data Type --';
            if (currentValue === '__custom_type__') {
                customOption.selected = true;
            }
            select.appendChild(customOption);
        });
    }

    // ADD THIS MISSING FUNCTION:
    function toggleFormat(formatId) {
        const formatSection = document.getElementById(`format-${formatId}`);
        const formatContent = document.getElementById(`format-content-${formatId}`);
        const toggleIcon = formatSection.querySelector('.toggle-icon');
        
        if (formatContent.style.display === 'none') {
            formatContent.style.display = 'block';
            toggleIcon.textContent = '▼';
            formatSection.classList.add('expanded');
        } else {
            formatContent.style.display = 'none';
            toggleIcon.textContent = '▶';
            formatSection.classList.remove('expanded');
        }
    }

    // Enhanced event listeners for custom fields
    document.addEventListener('change', function(e) {
        // Handle custom target field creation
        if (e.target.classList.contains('target-select')) {
            const customInput = e.target.parentNode.querySelector('.custom-field-input');
            
            if (e.target.value === '__custom__') {
                customInput.style.display = 'block';
                customInput.required = true;
                e.target.required = false;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
                e.target.required = true;
            }
            
            // Update available target fields after change
            const formatElement = e.target.closest('.format-section');
            updateAvailableTargetFields(formatElement);
        }
        
        // Handle custom data type creation
        if (e.target.classList.contains('type-select')) {
            const customInput = e.target.parentNode.querySelector('.custom-type-input');
            
            if (e.target.value === '__custom_type__') {
                customInput.style.display = 'block';
                customInput.required = true;
                e.target.required = false;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
                e.target.required = true;
            }
        }
        
        // Handle when a custom type input loses focus (blur event would be better, but change works too)
        if (e.target.classList.contains('custom-type-input')) {
            const value = e.target.value.trim();
            if (value && value.length >= 2) {
                // Add the new data type to the global dataTypes object
                const label = value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');
                dataTypes[value] = label;
                
                // Update all dropdown options
                updateDataTypeOptions();
            }
        }
    });
    
    // Enhanced validation for custom fields
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('custom-field-input')) {
            const value = e.target.value;
            
            // Real-time validation for system field names
            if (value && !/^[a-z0-9_]+$/.test(value)) {
                e.target.setCustomValidity('Only lowercase letters, numbers, and underscores allowed');
            } else if (value && value.length < 3) {
                e.target.setCustomValidity('Field name must be at least 3 characters long');
            } else if (value && (value.startsWith('_') || value.endsWith('_'))) {
                e.target.setCustomValidity('Field name cannot start or end with underscore');
            } else {
                e.target.setCustomValidity('');
            }
        }
    });

    // Add event listener for custom data type input to update global dataTypes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('custom-type-input')) {
            const value = e.target.value.trim();
            if (value && value.length >= 2) {
                // Add the new data type to the global dataTypes object
                const label = value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');
                dataTypes[value] = label;
                
                console.log('Added new custom data type:', value, 'with label:', label);
                console.log('Updated dataTypes object:', dataTypes);
                
                // Update all dropdown options immediately
                updateDataTypeOptions();
            }
        }
        
        if (e.target.classList.contains('custom-field-input')) {
            const value = e.target.value;
            
            // Real-time validation for system field names
            if (value && !/^[a-z0-9_]+$/.test(value)) {
                e.target.setCustomValidity('Only lowercase letters, numbers, and underscores allowed');
            } else if (value && value.length < 3) {
                e.target.setCustomValidity('Field name must be at least 3 characters long');
            } else if (value && (value.startsWith('_') || value.endsWith('_'))) {
                e.target.setCustomValidity('Field name cannot start or end with underscore');
            } else {
                e.target.setCustomValidity('');
            }
        }
    });
    
    // Function to update available target field options
    function updateAvailableTargetFields(formatElement) {
        const selects = formatElement.querySelectorAll('select[name*="[target]"]');
        const selectedValues = Array.from(selects).map(select => select.value).filter(value => value !== '');
        
        selects.forEach(currentSelect => {
            const currentValue = currentSelect.value;
            
            // Check each option in the select
            Array.from(currentSelect.options).forEach(option => {
                const optionValue = option.value;
                
                // Skip the empty option
                if (!optionValue) {
                    option.disabled = false;
                    return;
                }
                
                // If this option is selected in the current select, keep it enabled
                if (optionValue === currentValue) {
                    option.disabled = false;
                    return;
                }
                
                // If this option is selected in another select within the same format, disable it
                option.disabled = selectedValues.includes(optionValue);
                
                // Add visual indicator for disabled options
                if (option.disabled) {
                    option.style.color = '#999';
                    option.style.fontStyle = 'italic';
                } else {
                    option.style.color = '';
                    option.style.fontStyle = '';
                }
            });
        });
    }

    // Enhanced real-time format validation with duplicate target validation
    function validateFormat(formatElement, isNew = false) {
        const nameInput = formatElement.querySelector('input[name*="[name]"]');
        const detectionInput = formatElement.querySelector('input[name*="[detection]"]');
        
        if (!nameInput || !detectionInput) return;
        
        // Check for duplicate target fields within this format
        const targetSelects = formatElement.querySelectorAll('select[name*="[target]"]');
        const targetValues = Array.from(targetSelects)
            .map(select => select.value)
            .filter(value => value !== '');
        
        const duplicateTargets = targetValues.filter((value, index) => 
            targetValues.indexOf(value) !== index
        );
        
        // Remove existing error messages
        formatElement.querySelectorAll('.validation-error').forEach(el => el.remove());
        
        // Show duplicate target error if found
        if (duplicateTargets.length > 0) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'validation-error';
            errorDiv.style.color = 'red';
            errorDiv.style.fontSize = '0.9em';
            errorDiv.style.marginTop = '5px';
            errorDiv.textContent = `Duplicate target fields detected: ${[...new Set(duplicateTargets)].join(', ')}. Each target field can only be used once per format.`;
            
            const mappingsSection = formatElement.querySelector('.table-container');
            if (mappingsSection) {
                mappingsSection.appendChild(errorDiv);
            }
            return; // Don't proceed with server validation if there are client-side errors
        }
        
        // Proceed with existing server-side validation
        const formData = new FormData();
        formData.append('format_name', nameInput.value);
        formData.append('detection_columns', detectionInput.value);
        formData.append('is_new', isNew.toString());
        
        fetch('validate_format.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                data.errors.forEach(error => {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.style.color = 'red';
                    errorDiv.style.fontSize = '0.9em';
                    errorDiv.style.marginTop = '5px';
                    errorDiv.textContent = error;
                    
                    if (error.includes('format name') || error.includes('Format name')) {
                        nameInput.parentNode.appendChild(errorDiv);
                    } else if (error.includes('detection') || error.includes('Detection')) {
                        detectionInput.parentNode.appendChild(errorDiv);
                    }
                });
            }
        })
        .catch(error => console.error('Validation error:', error));
    }

    // Add validation event listeners with better debouncing
    let validationTimeout;
    document.addEventListener('input', function(e) {
        if (e.target.name && (e.target.name.includes('[name]') || e.target.name.includes('[detection]'))) {
            const formatElement = e.target.closest('.format-section');
            if (formatElement) {
                clearTimeout(validationTimeout);
                validationTimeout = setTimeout(() => {
                    const isNewFormat = formatElement.querySelector('input[name*="[name]"]').value === '';
                    validateFormat(formatElement, isNewFormat);
                }, 800); // Increased debounce time
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all formats as collapsed except the first one
        document.querySelectorAll('.format-section').forEach((section, index) => {
            if (index > 0) {
                section.querySelector('.format-content').style.display = 'none';
                section.querySelector('.toggle-icon').textContent = '▶';
            } else {
                section.classList.add('expanded');
                section.querySelector('.format-content').style.display = 'block';
            }
        });

        // Initialize custom field visibility for existing rows
        document.querySelectorAll('.target-select').forEach(select => {
            const customInput = select.parentNode.querySelector('.custom-field-input');
            if (select.value === '__custom__' && customInput) {
                customInput.style.display = 'block';
                customInput.required = true;
                select.required = false;
            }
        });
        
        // Initialize custom data type visibility for existing rows
        document.querySelectorAll('.type-select').forEach(select => {
            const customInput = select.parentNode.querySelector('.custom-type-input');
            if (select.value === '__custom_type__' && customInput) {
                customInput.style.display = 'block';
                customInput.required = true;
                select.required = false;
            }
        });
        
        // Add new format
        const addFormatBtn = document.getElementById('add-format');
        if (addFormatBtn) {
            addFormatBtn.addEventListener('click', function() {
                const formatsContainer = document.getElementById('formats-container');
                const formatCount = formatsContainer.children.length + 1;
                
                const formatSection = document.createElement('div');
                formatSection.className = 'format-section expanded';
                formatSection.id = `format-${formatCount}`;
                
                formatSection.innerHTML = `
                    <div class="format-header" onclick="toggleFormat(${formatCount})">
                        <h3>
                            <i class="fas fa-file-csv"></i>
                            New Format
                        </h3>
                        <div class="header-right">
                            <span class="format-indicator">0 mappings</span>
                            <span class="toggle-icon">▼</span>
                        </div>
                    </div>
                    
                    <div class="format-content" id="format-content-${formatCount}" style="display: block;">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Format Name:</label>
                                <input type="text" name="formats[${formatCount}][name]" required placeholder="e.g., google_analytics_4">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-search"></i> Detection Columns:</label>
                                <input type="text" name="formats[${formatCount}][detection]" required placeholder="Sessions,Users,Page Views">
                                <small class="help-text">Comma-separated list of column names that identify this format</small>
                            </div>
                        </div>
                        
                        <h4><i class="fas fa-arrows-alt-h"></i> Column Mappings</h4>
                        <div class="table-container">
                            <table class="mapping-table">
                                <thead>
                                    <tr>
                                        <th>Source Column</th>
                                        <th>Target Field</th>
                                        <th>Data Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="columns-container" id="columns-${formatCount}">
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-small add-column" data-format="${formatCount}">
                                <i class="fas fa-plus"></i> Add Column
                            </button>
                            <button type="button" class="btn-small btn-danger remove-format" data-format="${formatCount}">
                                <i class="fas fa-trash"></i> Remove Format
                            </button>
                        </div>
                    </div>
                `;
                
                formatsContainer.appendChild(formatSection);
                addButtonListeners();
                
                // Initialize target field listeners for the new format
                addTargetFieldListeners(formatSection);
                updateAvailableTargetFields(formatSection);
            });
        }

        // Add event listeners for target field changes
        function addTargetFieldListeners(formatElement) {
            const targetSelects = formatElement.querySelectorAll('select[name*="[target]"]');
            
            targetSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Update available options for this format
                    updateAvailableTargetFields(formatElement);
                    
                    // Trigger validation
                    const isNewFormat = formatElement.querySelector('input[name*="[name]"]').value === '';
                    validateFormat(formatElement, isNewFormat);
                });
            });
        }
        
        // Add button event listeners
        function addButtonListeners() {
            // Add column buttons
            document.querySelectorAll('.add-column').forEach(button => {
                button.replaceWith(button.cloneNode(true));
            });
            
            document.querySelectorAll('.add-column').forEach(button => {
                button.addEventListener('click', function() {
                    const formatId = this.dataset.format;
                    const formatElement = document.getElementById(`format-${formatId}`);
                    const columnsContainer = document.getElementById(`columns-${formatId}`);
                    const timestamp = Date.now();
                    const newRow = document.createElement('tr');
                    newRow.className = 'column-row';
                    
                    newRow.innerHTML = `
                        <td>
                            <input type="text" name="formats[${formatId}][columns][new_${timestamp}][source]" required placeholder="CSV Column Name">
                        </td>
                        <td>
                            ${createTargetFieldSelect(formatId, `new_${timestamp}`)}
                        </td>
                        <td>
                            ${createDataTypeSelect(formatId, `new_${timestamp}`)}
                        </td>
                        <td>
                            <button type="button" class="btn-small btn-danger remove-column" title="Remove Column">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    columnsContainer.appendChild(newRow);
                    
                    // Add event listeners to new elements
                    addTargetFieldListeners(formatElement);
                    
                    // Add event listener to new remove button
                    newRow.querySelector('.remove-column').addEventListener('click', function() {
                        this.closest('tr').remove();
                        updateMappingCount(formatId);
                        updateAvailableTargetFields(formatElement);
                    });
                    
                    // Update available options and mapping count
                    updateAvailableTargetFields(formatElement);
                    updateMappingCount(formatId);
                });
            });
            
            // Remove column buttons
            document.querySelectorAll('.remove-column').forEach(button => {
                button.replaceWith(button.cloneNode(true)); // Remove existing listeners
            });
            
            document.querySelectorAll('.remove-column').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const formatSection = row.closest('.format-section');
                    const formatId = formatSection.id.replace('format-', '');
                    
                    row.remove();
                    updateMappingCount(formatId);
                    updateAvailableTargetFields(formatSection);
                });
            });
            
            // Remove format buttons
            document.querySelectorAll('.remove-format').forEach(button => {
                button.replaceWith(button.cloneNode(true)); // Remove existing listeners
            });
            
            document.querySelectorAll('.remove-format').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to remove this entire format? This action cannot be undone.')) {
                        const formatId = this.dataset.format;
                        document.getElementById(`format-${formatId}`).remove();
                    }
                });
            });
        }
        
        // Update mapping count
        function updateMappingCount(formatId) {
            const formatSection = document.getElementById(`format-${formatId}`);
            const columnsCount = formatSection.querySelectorAll(`.columns-container tr`).length;
            const indicator = formatSection.querySelector('.format-indicator');
            
            if (indicator) {
                indicator.textContent = `${columnsCount} mappings`;
            }
        }
        
        // Initialize event listeners
        addButtonListeners();
        
        // Initialize target field listeners for existing formats
        document.querySelectorAll('.format-section').forEach(formatElement => {
            addTargetFieldListeners(formatElement);
            updateAvailableTargetFields(formatElement);
        });
        
        // Enhanced form validation
        document.getElementById('mappingsForm').addEventListener('submit', function(e) {
            const formats = document.querySelectorAll('.format-section');
            let hasValidFormat = false;
            let errors = [];
            
            formats.forEach((format, index) => {
                const nameInput = format.querySelector('input[name*="[name]"]');
                const detectionInput = format.querySelector('input[name*="[detection]"]');
                const mappings = format.querySelectorAll('.column-row');
                
                if (nameInput && nameInput.value.trim()) {
                    // Check for spaces in format name
                    if (nameInput.value.includes(' ')) {
                        errors.push(`Format ${index + 1}: Format name cannot contain spaces. Use underscores instead.`);
                    }
                    
                    // Check detection columns
                    if (!detectionInput || !detectionInput.value.trim()) {
                        errors.push(`Format ${index + 1}: Detection columns are required.`);
                    } else {
                        const columns = detectionInput.value.split(',').map(s => s.trim()).filter(s => s);
                        if (columns.length < 2) {
                            errors.push(`Format ${index + 1}: At least 2 detection columns are required.`);
                        }
                    }
                    
                    // Check for duplicate target fields
                    const targetSelects = format.querySelectorAll('select[name*="[target]"]');
                    const targetValues = Array.from(targetSelects)
                        .map(select => select.value)
                        .filter(value => value !== '');
                    
                    const duplicateTargets = targetValues.filter((value, index) => 
                        targetValues.indexOf(value) !== index
                    );
                    
                    if (duplicateTargets.length > 0) {
                        errors.push(`Format ${index + 1}: Duplicate target fields detected: ${[...new Set(duplicateTargets)].join(', ')}. Each target field can only be used once per format.`);
                    }
                    
                    // Check mappings
                    if (mappings.length === 0) {
                        errors.push(`Format ${index + 1}: At least one column mapping is required.`);
                    } else {
                        hasValidFormat = true;
                    }
                }
            });
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            if (!hasValidFormat) {
                e.preventDefault();
                alert('Please ensure at least one format has a name, detection columns, and column mappings.');
                return false;
            }
        });
    });
</script>
</body>
</html>