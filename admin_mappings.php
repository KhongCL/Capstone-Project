<?php
require_once 'config.php';
require_once 'classes/CsvProcessor.php';

// Admin authentication check would go here

$mappingsFile = 'config/csv_mappings.json';
$message = '';
$error = '';

// Handle form submission for updating mappings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mappings'])) {
    $mappings = [];
    
    foreach ($_POST['formats'] as $formatId => $format) {
        $formatKey = preg_replace('/[^a-z0-9_]/', '', strtolower($format['name']));
        
        $mappings[$formatKey] = [
            'format_detection' => explode(',', $format['detection']),
            'column_mappings' => [],
            'data_types' => []
        ];
        
        foreach ($format['columns'] as $sourceCol => $mapping) {
            if (!empty($mapping['target'])) {
                $mappings[$formatKey]['column_mappings'][$sourceCol] = $mapping['target'];
                $mappings[$formatKey]['data_types'][$sourceCol] = $mapping['type'];
            }
        }
    }
    
    // Save updated mappings
    if (file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT))) {
        $message = 'CSV mappings successfully updated.';
    } else {
        $error = 'Error saving CSV mappings.';
    }
}

// Load current mappings
$mappings = json_decode(file_get_contents($mappingsFile), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CSV Mappings Configuration</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Web Traffic Analysis Dashboard - Admin</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="admin.php">Admin Panel</a></li>
                    <li><a href="#" class="active">CSV Mappings</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="admin-section">
                <h2>Manage CSV Format Mappings</h2>
                
                <?php if (!empty($message)): ?>
                    <div class="success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="post" id="mappingsForm">
                    <div id="formats-container">
                        <?php $formatCount = 0; ?>
                        <?php foreach ($mappings as $formatKey => $format): ?>
                            <?php $formatCount++; ?>
                            <div class="format-section" id="format-<?php echo $formatCount; ?>">
                                <h3>Format: <?php echo ucfirst(str_replace('_', ' ', $formatKey)); ?></h3>
                                
                                <div class="form-group">
                                    <label>Format Name:</label>
                                    <input type="text" name="formats[<?php echo $formatCount; ?>][name]" 
                                           value="<?php echo htmlspecialchars($formatKey); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Detection Columns (comma-separated):</label>
                                    <input type="text" name="formats[<?php echo $formatCount; ?>][detection]" 
                                           value="<?php echo htmlspecialchars(implode(',', $format['format_detection'])); ?>" required>
                                    <p class="help-text">List of column names that identify this format</p>
                                </div>
                                
                                <h4>Column Mappings</h4>
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
                                        <?php $colCount = 0; ?>
                                        <?php foreach ($format['column_mappings'] as $sourceCol => $targetField): ?>
                                            <?php $colCount++; ?>
                                            <tr class="column-row">
                                                <td>
                                                    <input type="text" name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][source]" 
                                                           value="<?php echo htmlspecialchars($sourceCol); ?>" readonly>
                                                </td>
                                                <td>
                                                    <select name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][target]">
                                                        <option value="">-- Ignore --</option>
                                                        <option value="traffic_source" <?php echo $targetField === 'traffic_source' ? 'selected' : ''; ?>>Traffic Source</option>
                                                        <option value="traffic_medium" <?php echo $targetField === 'traffic_medium' ? 'selected' : ''; ?>>Traffic Medium</option>
                                                        <option value="visits" <?php echo $targetField === 'visits' ? 'selected' : ''; ?>>Visits/Sessions</option>
                                                        <option value="visitors" <?php echo $targetField === 'visitors' ? 'selected' : ''; ?>>Unique Visitors</option>
                                                        <option value="page_views" <?php echo $targetField === 'page_views' ? 'selected' : ''; ?>>Page Views</option>
                                                        <option value="bounce_rate" <?php echo $targetField === 'bounce_rate' ? 'selected' : ''; ?>>Bounce Rate</option>
                                                        <option value="avg_session_duration" <?php echo $targetField === 'avg_session_duration' ? 'selected' : ''; ?>>Avg. Session Duration</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="formats[<?php echo $formatCount; ?>][columns][<?php echo htmlspecialchars($sourceCol); ?>][type]">
                                                        <option value="string" <?php echo $format['data_types'][$sourceCol] === 'string' ? 'selected' : ''; ?>>String</option>
                                                        <option value="integer" <?php echo $format['data_types'][$sourceCol] === 'integer' ? 'selected' : ''; ?>>Integer</option>
                                                        <option value="float" <?php echo $format['data_types'][$sourceCol] === 'float' ? 'selected' : ''; ?>>Float</option>
                                                        <option value="percentage" <?php echo $format['data_types'][$sourceCol] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                                        <option value="time" <?php echo $format['data_types'][$sourceCol] === 'time' ? 'selected' : ''; ?>>Time</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn-small btn-danger remove-column">Remove</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn-small add-column" data-format="<?php echo $formatCount; ?>">Add Column</button>
                                    <button type="button" class="btn-small btn-danger remove-format" data-format="<?php echo $formatCount; ?>">Remove Format</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="add-format">Add New Format</button>
                        <button type="submit" name="update_mappings" class="btn">Save Mappings</button>
                    </div>
                </form>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add new format
            document.getElementById('add-format').addEventListener('click', function() {
                const formatsContainer = document.getElementById('formats-container');
                const formatCount = formatsContainer.children.length + 1;
                
                const formatSection = document.createElement('div');
                formatSection.className = 'format-section';
                formatSection.id = `format-${formatCount}`;
                
                formatSection.innerHTML = `
                    <h3>New Format</h3>
                    
                    <div class="form-group">
                        <label>Format Name:</label>
                        <input type="text" name="formats[${formatCount}][name]" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Detection Columns (comma-separated):</label>
                        <input type="text" name="formats[${formatCount}][detection]" required>
                        <p class="help-text">List of column names that identify this format</p>
                    </div>
                    
                    <h4>Column Mappings</h4>
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
                    
                    <div class="form-actions">
                        <button type="button" class="btn-small add-column" data-format="${formatCount}">Add Column</button>
                        <button type="button" class="btn-small btn-danger remove-format" data-format="${formatCount}">Remove Format</button>
                    </div>
                `;
                
                formatsContainer.appendChild(formatSection);
                
                // Add event listeners to new buttons
                addButtonListeners();
            });
            
            // Add button event listeners
            function addButtonListeners() {
                // Add column buttons
                document.querySelectorAll('.add-column').forEach(button => {
                    button.addEventListener('click', function() {
                        const formatId = this.dataset.format;
                        const columnsContainer = document.getElementById(`columns-${formatId}`);
                        const newRow = document.createElement('tr');
                        newRow.className = 'column-row';
                        
                        newRow.innerHTML = `
                            <td>
                                <input type="text" name="formats[${formatId}][columns][new_${Date.now()}][source]" required>
                            </td>
                            <td>
                                <select name="formats[${formatId}][columns][new_${Date.now()}][target]">
                                    <option value="">-- Ignore --</option>
                                    <option value="traffic_source">Traffic Source</option>
                                    <option value="traffic_medium">Traffic Medium</option>
                                    <option value="visits">Visits/Sessions</option>
                                    <option value="visitors">Unique Visitors</option>
                                    <option value="page_views">Page Views</option>
                                    <option value="bounce_rate">Bounce Rate</option>
                                    <option value="avg_session_duration">Avg. Session Duration</option>
                                </select>
                            </td>
                            <td>
                                <select name="formats[${formatId}][columns][new_${Date.now()}][type]">
                                    <option value="string">String</option>
                                    <option value="integer">Integer</option>
                                    <option value="float">Float</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="time">Time</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn-small btn-danger remove-column">Remove</button>
                            </td>
                        `;
                        
                        columnsContainer.appendChild(newRow);
                        
                        // Add event listener to new remove button
                        newRow.querySelector('.remove-column').addEventListener('click', function() {
                            this.closest('tr').remove();
                        });
                    });
                });
                
                // Remove column buttons
                document.querySelectorAll('.remove-column').forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('tr').remove();
                    });
                });
                
                // Remove format buttons
                document.querySelectorAll('.remove-format').forEach(button => {
                    button.addEventListener('click', function() {
                        const formatId = this.dataset.format;
                        document.getElementById(`format-${formatId}`).remove();
                    });
                });
            }
            
            // Initialize event listeners
            addButtonListeners();
        });
    </script>
</body>
</html>