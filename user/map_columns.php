<?php
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
$query = "SELECT DISTINCT SystemFieldName, CSVColumnName FROM COLUMN_MAPPING ORDER BY SystemFieldName";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $systemFields[] = [
            'value' => $row['SystemFieldName'],
            'label' => ucwords(str_replace('_', ' ', $row['SystemFieldName'])),
            'default_column' => $row['CSVColumnName']
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
        // Transform data using the mapping
        $transformedData = $processor->transformData($_SESSION['uploaded_csv'], $columnMapping);
        
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
</head>
<body>
    <div class="container">
        <header>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#" class="active">Map Columns</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="mapping-section">
                <h2>Map CSV Columns</h2>
                <?php if (isset($error_message)): ?>
                    <div class="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($mappingResult['status'] === 'needs_mapping'): ?>
                    <div class="alert">
                        This CSV format wasn't automatically recognized. Please review and confirm the column mappings below.
                    </div>
                <?php elseif ($mappingResult['status'] === 'success'): ?>
                    <div class="success">
                        CSV format detected: <strong><?php echo ucfirst(str_replace('_', ' ', $mappingResult['format'])); ?></strong>
                        <p>Please confirm the column mappings below:</p>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post">
                    <table class="mapping-table">
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
                                    $targetField = isset($mappingResult['suggestions'][$column]['suggested_mapping']) ? 
                                        $mappingResult['suggestions'][$column]['suggested_mapping'] : '';
                                    $confidence = isset($mappingResult['suggestions'][$column]['confidence']) ? 
                                        $mappingResult['suggestions'][$column]['confidence'] : 0;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($column); ?></td>
                                <td><?php echo htmlspecialchars($sampleValue); ?></td>
                                <td>
                                <select name="mapping[<?php echo htmlspecialchars($column); ?>]" class="field-select">
                                    <option value="">-- Ignore this column --</option>
                                    <?php foreach ($systemFields as $field): ?>
                                        <?php 
                                        $selected = '';
                                        if ($targetField === $field['value']) {
                                            $selected = 'selected'; 
                                        } elseif (empty($targetField) && $column === $field['default_column']) {
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
                                        <div class="confidence-bar">
                                            <div class="confidence-fill" style="width: <?php echo $confidence; ?>%"></div>
                                            <span><?php echo round($confidence); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="form-actions">
                        <button type="submit" name="confirm_mapping" class="btn">Confirm Mapping & Import Data</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                
                <div class="sample-data">
                    <h3>Sample Data Preview</h3>
                    <div class="table-container">
                        <table class="data-table">
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
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fieldSelects = document.querySelectorAll('.field-select');
            
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
        </script>
</body>
</html>