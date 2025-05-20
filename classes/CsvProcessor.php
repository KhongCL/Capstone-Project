<?php
class CsvProcessor {
    private $mappingsFile;
    private $mappings;
    private $detectedFormat = null;
    private $columnMap = [];
    private $csvData = [];
    
    public function __construct() {
        // Use absolute path to root config file instead of relative path
        $this->mappingsFile = __DIR__ . '/../config/csv_mappings.json';
        
        error_log("Loading mappings from: " . $this->mappingsFile);

        if (!file_exists($this->mappingsFile)) {
            throw new Exception('Mappings file not found at: ' . $this->mappingsFile);
        }

        $this->mappings = json_decode(file_get_contents($this->mappingsFile), true);
        if (!$this->mappings) {
            throw new Exception('Failed to load CSV mapping configurations');
        }
    }
    
    /**
     * Process the uploaded CSV file and detect its format
     * @param string $filePath Path to the uploaded CSV file
     * @return array Processing result with status and mapping information
     */
    public function processFile($filePath) {
        // Check if file is empty
        if (filesize($filePath) === 0) {
            return [
                'status' => 'error',
                'message' => 'The CSV file is empty. Please upload a file with data.'
            ];
        }

        // First check if this is a Google Analytics format CSV (has metadata lines with #)
        $handle = fopen($filePath, "r");
        if ($handle) {
            $firstLine = fgets($handle);
            fclose($handle);
            
            if (substr(trim($firstLine), 0, 1) === '#') {
                // This looks like a Google Analytics export format
                return $this->processGoogleAnalyticsFormat($filePath);
            }
        }
        
        // Standard CSV format processing
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Read header and first few rows
            $header = fgetcsv($handle);
            
            // Check if header is valid
            if ($header === false || empty($header)) {
                fclose($handle);
                return [
                    'status' => 'error',
                    'message' => 'Invalid CSV file: No headers found or file is empty.'
                ];
            }
            
            $data = [];
            $i = 0;
            while (($row = fgetcsv($handle)) !== FALSE && $i < 5) {
                if (!empty(array_filter($row))) { // Skip empty rows
                    $data[] = $row;
                    $i++;
                }
            }
            fclose($handle);
            
            // Try to detect format based on headers
            try {
                $this->detectedFormat = $this->detectFormat($header);
                
                if ($this->detectedFormat) {
                    $format = $this->detectedFormat;
                    return [
                        'status' => 'success',
                        'format' => $format,
                        'header' => $header,
                        'mapping' => $this->mappings[$format]['column_mappings'],
                        'data_types' => $this->mappings[$format]['data_types'],
                        'sample' => $data
                    ];
                } else {
                    // Couldn't detect format automatically, need mapping
                    return [
                        'status' => 'needs_mapping',
                        'header' => $header,
                        'sample' => $data,
                        'suggestions' => $this->suggestColumnMapping($header)
                    ];
                }
            } catch (Exception $e) {
                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return [
            'status' => 'error',
            'message' => 'Failed to open or process the CSV file'
        ];
    }
    
    /**
     * Process the uploaded CSV file with Google Analytics format
     */
    private function processGoogleAnalyticsFormat($filePath) {
        $headerLine = null;
        $dataLines = [];
        $metadataLines = [];
        $requiredMetadataKeywords = [
            'Traffic acquisition',
            'Account:',
            'Property:',
            'Start date:',
            'End date:'
        ];
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Process the file line by line
            $foundMetadataCount = 0;
            $lineNum = 0;
            
            // Check first 50 lines for required GA4 metadata patterns
            while (($line = fgets($handle)) !== FALSE && $lineNum < 50) {
                $lineNum++;
                $line = trim($line);
                // Skip empty lines
                if (empty($line)) continue;
                
                // Check for metadata keywords
                foreach ($requiredMetadataKeywords as $keyword) {
                    if (strpos($line, $keyword) !== false) {
                        $foundMetadataCount++;
                        break;
                    }
                }
                
                // Collect metadata lines (lines starting with #)
                if (substr($line, 0, 1) === '#') {
                    $metadataLines[] = $line;
                    continue;
                }
                
                // First non-metadata line is the header
                if ($headerLine === null) {
                    $headerLine = $line;
                    error_log("Found header line: " . $headerLine);
                    continue;
                }
                
                // All other non-metadata lines are data
                $dataLines[] = $line;
            }
            fclose($handle);
            
            // If we don't find at least 3 of the required metadata patterns, reject the file
            if ($foundMetadataCount < 3) {
                return [
                    'status' => 'error',
                    'message' => 'This does not appear to be a Google Analytics export. Please upload a valid traffic data file.'
                ];
            }
        }
        
        error_log("Found " . count($dataLines) . " data lines");
        
        // Now process header and data
        $header = str_getcsv($headerLine);
        $data = [];
        foreach ($dataLines as $line) {
            if (trim($line) !== '') {
                $data[] = str_getcsv($line);
            }
        }
        
        // Header validation with relevance score
        $gaKeywords = [
            'Session', 'Sessions', 'Engagement', 'Traffic', 'Source', 
            'Medium', 'Channel', 'Events', 'Users', 'Revenue', 'Visit', 'Key'
        ];
        
        // Calculate how many GA-related headers we find
        $gaRelevanceScore = 0;
        foreach ($header as $headerColumn) {
            foreach ($gaKeywords as $keyword) {
                if (stripos($headerColumn, $keyword) !== false) {
                    $gaRelevanceScore++;
                    break;
                }
            }
        }
        
        // If the file doesn't look like analytics data at all, reject it
        if (count($header) > 3 && $gaRelevanceScore < 2) {
            return [
                'status' => 'error',
                'message' => 'This file does not appear to contain web analytics data.'
            ];
        }
        
        // Try to detect format
        if (count($header) > 0) {
            error_log("Checking format for header: " . implode(", ", $header));
            
            // Check if this matches any known format
            foreach ($this->mappings as $formatKey => $format) {
                $matched = true;
                $matchCount = 0;
                $requiredColumns = $format['format_detection'];
                
                foreach ($requiredColumns as $column) {
                    if (in_array($column, $header)) {
                        $matchCount++;
                    } else {
                        $matched = false;
                    }
                }
                
                // If we find an exact match or at least 70% of the expected columns
                if ($matched || ($matchCount >= count($requiredColumns) * 0.7)) {
                    error_log("Matched format: " . $formatKey);
                    
                    // Use ALL mappings directly from configuration
                    $mappingToUse = $format['column_mappings'];
                    $dataTypesToUse = $format['data_types'];
                    
                    // Log all mappings for debugging
                    error_log("Configuration mappings: " . json_encode(array_keys($mappingToUse)));
                    error_log("CSV header columns: " . json_encode($header));
                    
                    // Add mappings with improved comparison
                    foreach ($mappingToUse as $sourceCol => $targetCol) {
                        error_log("Including column in mapping: $sourceCol -> $targetCol");
                    }
                    
                    // Check if any CSV columns are not in the mapping with detailed debugging
                    $unfoundColumns = [];
                    foreach ($header as $csvColumn) {
                        $found = false;
                        $matchedConfigCol = null;

                        error_log("Checking CSV column: '$csvColumn'");
                        
                    foreach ($format['column_mappings'] as $configCol => $targetCol) {
                        error_log("  Comparing with config column: '$configCol'");
                        // Use case-insensitive, whitespace-insensitive comparison
                        if (strcasecmp(trim($csvColumn), trim($configCol)) === 0) {
                            $found = true;
                            $matchedConfigCol = $configCol;
                            error_log("  ✓ MATCHED: '$csvColumn' to config: '$configCol'");
                            break;
                        } else {
                            error_log("  ✗ NO MATCH: '" . trim($csvColumn) . "' vs '" . trim($configCol) . "'");
                            error_log("    - Lengths: " . strlen(trim($csvColumn)) . " vs " . strlen(trim($configCol)));
                            error_log("    - ASCII codes: " . implode(',', array_map(function($c) { return ord($c); }, str_split(trim($csvColumn)))) . 
                                     " vs " . implode(',', array_map(function($c) { return ord($c); }, str_split(trim($configCol)))));
                        }
                    }
                        
                        if (!$found) {
                            error_log("WARNING: CSV column '$csvColumn' has no mapping in configuration");
                            // Try to find closest match for debugging
                            foreach ($format['column_mappings'] as $configCol => $targetCol) {
                                error_log("  Compare with: '$configCol' - Length: " . strlen($configCol) . " vs " . strlen($csvColumn));
                            }
                            $unfoundColumns[] = $csvColumn;
                        } else {
                            // Ensure the mapping uses the exact CSV column name
                            // This is critical for the subsequent processing
                            $mappingToUse[$csvColumn] = $format['column_mappings'][$matchedConfigCol];
                        }
                    }

                    // Important: Add CSV columns to config mapping if they exist in config but with different case
                    foreach ($unfoundColumns as $csvColumn) {
                        foreach ($format['column_mappings'] as $configCol => $targetCol) {
                            if (strcasecmp(trim($csvColumn), trim($configCol)) === 0) {
                                error_log("Adding case-insensitive match: '$csvColumn' to '$targetCol'");
                                $mappingToUse[$csvColumn] = $targetCol;
                                break;
                            }
                        }
                    }

                    foreach ($format['column_mappings'] as $configCol => $targetCol) {
                        $exactMatch = false;
                        $caseInsensitiveMatch = false;
                        $matchedCsvColumn = null;
                        
                        // First check for exact match
                        if (in_array($configCol, $header)) {
                            $exactMatch = true;
                            $matchedCsvColumn = $configCol;
                        } else {
                            // Then try case-insensitive match
                            foreach ($header as $csvColumn) {
                                if (strcasecmp(trim($csvColumn), trim($configCol)) === 0) {
                                    $caseInsensitiveMatch = true;
                                    $matchedCsvColumn = $csvColumn;
                                    break;
                                }
                            }
                        }
                        
                        if ($exactMatch) {
                            error_log("Config column '$configCol' has exact match in CSV");
                            // Already handled above
                        } else if ($caseInsensitiveMatch) {
                            error_log("Config column '$configCol' has case-insensitive match to CSV column '$matchedCsvColumn'");
                            $mappingToUse[$matchedCsvColumn] = $targetCol;
                        } else {
                            error_log("Config column '$configCol' has NO match in CSV - but keeping it in the mapping");
                            // Keep the column in the mapping even if it doesn't exist in the CSV
                            // This is crucial for the problematic columns
                            $mappingToUse[$configCol] = $targetCol;
                        }
                    }
                    
                    return [
                        'status' => 'success',
                        'format' => $formatKey,
                        'header' => $header,
                        'mapping' => $mappingToUse,
                        'data_types' => $dataTypesToUse,
                        'sample' => array_slice($data, 0, 5)
                    ];
                }
            }
        }
        
        // If we got here, format not recognized but file appears to be valid analytics data
        error_log("No format matched");
        return [
            'status' => 'needs_mapping',
            'header' => $header,
            'sample' => array_slice($data, 0, 5),
            'suggestions' => $this->suggestColumnMapping($header)
        ];
    }
    
    /**
     * Detect the CSV format based on headers
     */
    private function detectFormat($headers) {
        // Validate headers input
        if (!is_array($headers) || empty($headers)) {
            throw new Exception('Invalid CSV headers: Headers must be a non-empty array.');
        }
        
        // First do analytics relevance check
        $gaKeywords = [
            'Session', 'Sessions', 'Engagement', 'Traffic', 'Source', 
            'Medium', 'Channel', 'Events', 'Users', 'Revenue', 'Visit'
        ];
        
        // Calculate how many GA-related headers we find
        $gaRelevanceScore = 0;
        foreach ($headers as $header) {
            foreach ($gaKeywords as $keyword) {
                if (stripos($header, $keyword) !== false) {
                    $gaRelevanceScore++;
                    break;
                }
            }
        }
        
        // If the file doesn't look like analytics data at all, reject it
        if (count($headers) > 3 && $gaRelevanceScore < 2) {
            throw new Exception('This file does not appear to contain web analytics data.');
        }
        
        // Continue with existing format detection
        foreach ($this->mappings as $format => $config) {
            $requiredColumns = $config['format_detection'];
            $matchCount = 0;
            
            foreach ($requiredColumns as $column) {
                if (in_array($column, $headers)) {
                    $matchCount++;
                }
            }
            
            // If we find at least 70% of the expected columns, consider it a match
            if ($matchCount >= count($requiredColumns) * 0.7) {
                return $format;
            }
        }
        
        return null;
    }
    
    /**
     * Suggest column mappings using fuzzy matching
     */
    private function suggestColumnMapping($headers) {
        $suggestions = [];
        $standardColumns = [
            'traffic_source', 'traffic_medium', 'visits', 
            'visitors', 'page_views', 'bounce_rate', 
            'avg_session_duration'
        ];
        
        $keywords = [
            'traffic_source' => ['source', 'referrer', 'origin', 'from', 'site'],
            'traffic_medium' => ['medium', 'channel', 'type'],
            'visits' => ['visits', 'sessions', 'hits'],
            'visitors' => ['visitors', 'users', 'unique'],
            'page_views' => ['page', 'view', 'pageview', 'impression', 'actions'],
            'bounce_rate' => ['bounce', 'exit'],
            'avg_session_duration' => ['duration', 'time', 'session', 'length', 'stay']
        ];
        
        foreach ($headers as $header) {
            $bestMatch = null;
            $bestScore = 0;
            
            // Check exact matches first
            foreach ($this->mappings as $format => $config) {
                if (isset($config['column_mappings'][$header])) {
                    $bestMatch = $config['column_mappings'][$header];
                    $bestScore = 100;
                    break;
                }
            }
            
            // If no exact match, try keyword matching
            if (!$bestMatch) {
                foreach ($keywords as $column => $keywordList) {
                    foreach ($keywordList as $keyword) {
                        if (stripos($header, $keyword) !== false) {
                            $score = 70 + (strlen($keyword) / strlen($header) * 30);
                            if ($score > $bestScore) {
                                $bestScore = $score;
                                $bestMatch = $column;
                            }
                        }
                    }
                }
            }
            
            // Add suggestion if confidence is above 60%
            if ($bestMatch && $bestScore >= 60) {
                $suggestions[$header] = [
                    'suggested_mapping' => $bestMatch,
                    'confidence' => $bestScore
                ];
            } else {
                $suggestions[$header] = [
                    'suggested_mapping' => null,
                    'confidence' => 0
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Transform data based on mapping
     */
    public function transformData($filePath, $columnMapping, $format = null) {
        $this->columnMap = $columnMapping;

        if ($format && isset($this->mappings[$format]['column_mappings'])) {
            // Get all mappings from configuration
            foreach ($this->mappings[$format]['column_mappings'] as $sourceCol => $targetCol) {
                $this->columnMap[$sourceCol] = $targetCol;
                error_log("Added mapping from config: $sourceCol -> $targetCol");
            }
            
            // Get all headers from the CSV file
            $csvHeaders = [];
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                // existing code to read headers...
            }
            
            // Check if any CSV headers don't have mappings
            foreach ($csvHeaders as $header) {
                if (!isset($this->columnMap[$header])) {
                    // Try case-insensitive matching
                    $foundMatch = false;
                    foreach ($this->mappings[$format]['column_mappings'] as $configCol => $targetCol) {
                        if (strcasecmp(trim($header), trim($configCol)) === 0) {
                            $this->columnMap[$header] = $targetCol;
                            $foundMatch = true;
                            error_log("Added case-insensitive mapping: $header -> $targetCol");
                            break;
                        }
                    }
                    
                    if (!$foundMatch) {
                        error_log("WARNING: No mapping found for CSV column: $header");
                    }
                }
            }
        }

        if ($format) {
            $this->detectedFormat = $format;
        }

        // Add missing mappings if they exist in the configuration but not in the provided mapping
        if ($this->detectedFormat && isset($this->mappings[$this->detectedFormat]['column_mappings'])) {
            $configMappings = $this->mappings[$this->detectedFormat]['column_mappings'];
            foreach ($configMappings as $sourceCol => $targetCol) {
                if (!isset($this->columnMap[$sourceCol])) {
                    $this->columnMap[$sourceCol] = $targetCol;
                    error_log("Added missing mapping: '$sourceCol' => '$targetCol'");
                }
            }
        }

        error_log("Transform data using format: " . ($this->detectedFormat ?? "No format detected"));
        // Log the full column mapping to see what's actually being mapped
        error_log("Full column mapping: " . json_encode($this->columnMap));
        
        // Add logging for data types configuration
        if ($this->detectedFormat) {
            error_log("Available data types from configuration: " . 
                json_encode(isset($this->mappings[$this->detectedFormat]['data_types']) ? 
                array_keys($this->mappings[$this->detectedFormat]['data_types']) : []));
        }
        
        $transformed = [];
        $validationErrors = [];
        $validRows = 0; // Initialize $validRows here to avoid undefined variable
        $rowNumber = 0; // Also initialize $rowNumber for consistent reporting
        error_log("Starting transformData with mapping: " . json_encode($columnMapping));
        
        $isGa4Format = false;
        $handle = fopen($filePath, "r");
        if ($handle) {
            $firstLine = fgets($handle);
            if (substr(trim($firstLine), 0, 1) === '#') {
                $isGa4Format = true;
            }
            fclose($handle);
        }
        
        if ($isGa4Format) {
            error_log("Processing as GA4 format");
            // For GA4 format, we need to skip metadata lines
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $headerLine = null;
                
                // Skip metadata lines and find the header
                while (($line = fgets($handle)) !== FALSE) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    if (substr($line, 0, 1) === '#') {
                        continue;
                    }
                    
                    // First non-metadata line is the header
                    if ($headerLine === null) {
                        $headerLine = $line;
                        break;
                    }
                }
                
                // Process header
                $header = str_getcsv($headerLine);
                $headerIndexes = array_flip($header);
                error_log("Header indexes: " . json_encode($headerIndexes));

                $rowNumber = 1;
                
                // Process data rows
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $rowNumber++;
                    
                    if (count($data) < count($header)) {
                        error_log("Row $rowNumber has fewer columns than the header. Skipping.");
                        $validationErrors[] = "Row $rowNumber has fewer columns than expected - please check for missing values";
                        continue; // Skip invalid rows
                    }
                    
                    $row = [];
                    $rowHasError = false;
                    $sourceName = $data[$headerIndexes['Session primary channel group (Default channel group)']] ?? 'Unknown';

                    $headerLookup = [];
                    foreach ($header as $index => $headerCol) {
                        $headerLookup[strtolower(trim($headerCol))] = $index;
                    }
                    
                    // Map each column according to our defined structure
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        // Log the column being processed
                        error_log("Processing column mapping: '$sourceCol' => '$targetCol'");
                        
                        // Store original column name for reporting
                        $originalSourceCol = $sourceCol;
                        
                        // First try exact index lookup
                        $columnIndex = $headerIndexes[$sourceCol] ?? null;
                        
                        // If not found, try case-insensitive lookup among header columns
                        if ($columnIndex === null) {
                            foreach (array_keys($headerIndexes) as $headerCol) {
                                if (strcasecmp(trim($sourceCol), trim($headerCol)) === 0) {
                                    $columnIndex = $headerIndexes[$headerCol];
                                    error_log("Found column match via case-insensitive comparison: '$sourceCol' matched to header '$headerCol'");
                                    $sourceCol = $headerCol; // Use the exact column name from the header
                                    break;
                                }
                            }
                        }
                        
                        // If still not found, try lowercase lookup
                        if ($columnIndex === null && isset($headerLookup[strtolower(trim($sourceCol))])) {
                            $columnIndex = $headerLookup[strtolower(trim($sourceCol))];
                            error_log("Found column match via lowercase lookup: '$sourceCol'");
                            
                            // Find the actual header column that matched
                            foreach ($header as $index => $headerCol) {
                                if ($index === $columnIndex) {
                                    $sourceCol = $headerCol; // Use the exact column name from the header
                                    break;
                                }
                            }
                        }
                        
                        // Skip columns that don't exist in this CSV
                        if ($columnIndex === null) {
                            error_log("Skipping column '$originalSourceCol' - not found in CSV headers");
                            continue;
                        }
                        
                        // Get the value from the data row
                        if (!isset($data[$columnIndex])) {
                            error_log("Skipping column '$sourceCol' - no data in this row");
                            continue; // Skip if the cell doesn't exist
                        }
                        
                        $value = $data[$columnIndex];
                        error_log("Column '$sourceCol' => '$targetCol' has value: '$value'");
                        
                        try {
                            // Check if column has a data type defined
                            $dataType = isset($this->mappings[$this->detectedFormat]['data_types'][$sourceCol]) ? 
                                $this->mappings[$this->detectedFormat]['data_types'][$sourceCol] : 'none';
                            error_log("Column '$sourceCol' has defined data type: '$dataType'");
                            
                            // Validate the value based on data type
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
                            error_log("Validation successful for '$sourceCol' with value '$value'");
                        } catch (Exception $e) {
                            // Log the error with more context about which row had the issue
                            error_log("Data validation error at row $rowNumber, column '$sourceCol': " . $e->getMessage());
                            
                            // Create a more user-friendly error message with row information
                            $errorWithRow = "Row " . $rowNumber . " ($sourceName): " . $e->getMessage();
                            $validationErrors[] = $errorWithRow;
                            
                            $rowHasError = true;
                        }
                    }
                    
                    // Only add row if it has no validation errors
                    if (!$rowHasError && !empty($row)) {
                        $transformed[] = $row;
                        $validRows++;
                    }
                }
                fclose($handle);
            }
        } else {
            // Standard CSV format processing
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $header = fgetcsv($handle);
                $headerIndexes = array_flip($header);
                $rowNumber = 0;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $rowNumber++;
                    $row = [];
                    $rowHasError = false;
                    $sourceName = isset($headerIndexes['Source']) && isset($data[$headerIndexes['Source']]) 
                        ? $data[$headerIndexes['Source']] 
                        : "Row $rowNumber";
                    
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        // Skip columns that don't exist in this CSV
                        if (!isset($headerIndexes[$sourceCol])) {
                            continue;
                        }
                        
                        // Get the value from the data row
                        $colIndex = $headerIndexes[$sourceCol];
                        if (!isset($data[$colIndex])) {
                            continue; // Skip if the cell doesn't exist
                        }
                        
                        $value = $data[$colIndex];
                        
                        try {
                            // Validate the value based on data type
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
                        } catch (Exception $e) {
                            // Log the error with more context
                            error_log("Data validation error: " . $e->getMessage() . " (Row: " . json_encode($data) . ")");
                            
                            // Add to validation errors collection with source info
                            $errorWithRow = "Row " . $rowNumber . " ($sourceName): " . $e->getMessage();
                            $validationErrors[] = $errorWithRow;
                            
                            $rowHasError = true;
                        }
                    }
                    
                    // Only add row if it has no validation errors
                    if (!$rowHasError && !empty($row)) {
                        $transformed[] = $row;
                        $validRows++;
                    }
                }
                fclose($handle);
            }
        }
        
        error_log("Transformed " . count($transformed) . " rows");
        
        // Better empty file detection
        if (count($transformed) === 0) {
            error_log("No data rows found after transformation");
            
            // Create a detailed error message depending on whether there were validation errors
            if (!empty($validationErrors)) {
                error_log("Validation errors found: " . implode("; ", $validationErrors));
                
                // Store error message in session with all errors
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['upload_message'] = [
                    'type' => 'error',
                    'message' => "Data validation errors found: " . implode("; ", $validationErrors) . ". Please correct these issues and upload again."
                ];
            } else {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['upload_message'] = [
                    'type' => 'warning',
                    'message' => "No valid data rows found in the CSV file after validation."
                ];
            }
            
            // Clean up the file when there are validation errors
            if (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv'])) {
                unlink($_SESSION['uploaded_csv']);
                unset($_SESSION['uploaded_csv']);
            }
            
            // Return empty array to indicate no valid data
            return [];
        }
        
        if (count($transformed) > 0) {
            error_log("First transformed row: " . json_encode($transformed[0]));
        }

        error_log("Validated $rowNumber rows, found " . count($validationErrors) . " errors, $validRows rows valid");

        if (!empty($validationErrors)) {
            error_log("Validation errors found: " . implode("; ", $validationErrors));
            
            // Store error message in session with all errors
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['upload_message'] = [
                'type' => 'error',
                'message' => "Data validation errors found: " . implode("; ", $validationErrors) . ". Please correct these issues and upload again."
            ];
            
            // Clean up the file when there are validation errors
            if (isset($_SESSION['uploaded_csv']) && file_exists($_SESSION['uploaded_csv'])) {
                unlink($_SESSION['uploaded_csv']);
                unset($_SESSION['uploaded_csv']);
            }
            
            // Return empty array to indicate no valid data
            return [];
        } else {
            return $transformed;
        }
    }
    
    /**
     * Format value based on data type
     */
    private function formatValue($value, $column) {
        error_log("Validating value: '$value' for column: '$column'");
            
        if (!$this->detectedFormat) {
            error_log("No detected format set for validation");
            return $value;
        }
        
        // If no data type is specified in the mappings, use special logic for certain target fields
        if (!isset($this->mappings[$this->detectedFormat]['data_types'][$column])) {
            // Check if this column maps to a known target field that needs validation
            $targetField = $this->columnMap[$column] ?? null;
            if ($targetField === 'traffic_source') {
                // Allow any text for traffic sources
                return $value;
            } else if ($targetField === 'total_revenue') {
                // If it maps to revenue, enforce numeric validation
                if (!is_numeric($value)) {
                    throw new Exception("Invalid revenue value: '$value' for column '$column' - Must be a number");
                }
                return (float) $value;
            } else if ($targetField === 'key_events') {
                // For key events, they should be integers
                if (!preg_match('/^[0-9]+$/', $value)) {
                    throw new Exception("Invalid key events value: '$value' for column '$column' - Must be a whole number");
                }
                return (int) $value;
            } else if ($targetField === 'session_key_event_rate' || strpos($targetField, 'rate') !== false) {
                // If it's a rate/percentage field without a defined type, validate as percentage
                if (!preg_match('/^([0-9]*\.?[0-9]+%?|[0-1](\.\d+)?)$/', $value)) {
                    throw new Exception("Invalid rate value: '$value' for column '$column' - Must be a number between 0-1 or percentage");
                }
                return (float) str_replace('%', '', $value) / (strpos($value, '%') !== false ? 100 : 1);
            }
            
            error_log("No data type defined for column '$column' in format: " . $this->detectedFormat);
            return $value;
        }
        
        $type = $this->mappings[$this->detectedFormat]['data_types'][$column];
        error_log("Validating as type: $type");
        
        // Skip validation for empty values
        if (trim($value) === '') {
            error_log("Empty value, returning default for type $type");
            return ($type === 'integer' || $type === 'float' || $type === 'currency') ? 0 : $value;
        }
        
        switch ($type) {
            case 'integer':
                // Stricter integer validation - only digits and commas as thousand separators
                if (!preg_match('/^[0-9,]+$/', $value)) {
                    throw new Exception("Invalid integer value: '$value' for column '$column' - Please use only digits");
                }
                return (int) preg_replace('/[^0-9]/', '', $value);
                
            case 'float':
                // Improved float validation - no multiple decimal points, properly formatted
                if (!preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                    throw new Exception("Invalid float value: '$value' for column '$column' - Please use numbers with a single decimal point");
                }
                
                // Add additional check for negative values if they shouldn't be allowed
                if (strpos($value, '-') === 0 && !in_array($column, ['Events per session'])) {
                    throw new Exception("Negative values are not allowed for column '$column'");
                }
                
                return (float) $value;
                
            case 'percentage':
                // Stricter percentage validation
                if (strpos($value, '%') !== false) {
                    // If it has a % sign, validate format and convert
                    if (!preg_match('/^[0-9,.]+%$/', $value)) {
                        throw new Exception("Invalid percentage value: '$value' for column '$column' - Format should be like '25%'");
                    }
                    return (float) preg_replace('/[^0-9.]/', '', $value) / 100;
                } else {
                    // Otherwise treat as decimal (between 0-1)
                    if (!is_numeric($value) || (float)$value < 0 || (float)$value > 1) {
                        throw new Exception("Invalid percentage value: '$value' for column '$column' - Value should be between 0-1 or include % sign");
                    }
                    return (float) $value;
                }
                
            case 'currency':
                // New currency type for revenue fields
                if (!is_numeric($value)) {
                    throw new Exception("Invalid currency value: '$value' for column '$column' - Must be a number");
                }
                return (float) $value;
                
            case 'time':
                // Robust time format validation
                if (strpos($value, ':') !== false) {
                    // Format: MM:SS or HH:MM:SS
                    $parts = array_map('intval', explode(':', $value));
                    if (count($parts) == 2) {
                        // Validate MM:SS format
                        if ($parts[0] < 0 || $parts[1] < 0 || $parts[1] > 59) {
                            throw new Exception("Invalid time value: '$value' for column '$column' - Minutes:Seconds format required with seconds 0-59");
                        }
                        return $parts[0] * 60 + $parts[1];
                    } elseif (count($parts) == 3) {
                        // Validate HH:MM:SS format
                        if ($parts[0] < 0 || $parts[1] < 0 || $parts[2] < 0 || 
                            $parts[1] > 59 || $parts[2] > 59) {
                            throw new Exception("Invalid time value: '$value' for column '$column' - Hours:Minutes:Seconds format required with minutes and seconds 0-59");
                        }
                        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
                    } else {
                        throw new Exception("Invalid time format: '$value' for column '$column' - Use MM:SS or HH:MM:SS format");
                    }
                } elseif (!preg_match('/^\d+(\.\d+)?$/', $value)) {
                    throw new Exception("Invalid time value: '$value' for column '$column' - Use either seconds (numeric) or MM:SS format");
                }
                return (float) $value;
                
            case 'text':
                // Allow any text value
                return $value;
                
            default:
                return $value;
        }
    }

    /**
     * Extract metadata from GA4 format CSV
     * @param string $filePath Path to the CSV file
     * @return array Metadata including dates, account name, property name
     */
    public function extractGa4Metadata($filePath) {
        
        $metadata = [
            'start_date' => null,
            'end_date' => null,
            'account_name' => null,
            'property_name' => null,
            'report_type' => null
        ];
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $lineNum = 0;
            while (($line = fgets($handle)) !== FALSE && $lineNum < 15) {
                // Extract account and property info
                if (strpos($line, 'Account:') !== false) {
                    $rawValue = trim(str_replace('# Account:', '', $line));
                    // Remove trailing commas
                    $metadata['account_name'] = preg_replace('/,+$/', '', $rawValue);
                    error_log("Found account name: " . $metadata['account_name']);
                }
                
                if (strpos($line, 'Property:') !== false) {
                    $rawValue = trim(str_replace('# Property:', '', $line));
                    // Remove trailing commas
                    $metadata['property_name'] = preg_replace('/,+$/', '', $rawValue);
                    error_log("Found property name: " . $metadata['property_name']);
                }
                
                // Extract report type
                if (strpos($line, 'Traffic acquisition:') !== false) {
                    $rawValue = trim(str_replace('# Traffic acquisition:', '', $line));
                    // Remove trailing commas
                    $metadata['report_type'] = preg_replace('/,+$/', '', $rawValue);
                    error_log("Found report type: " . $metadata['report_type']);
                }
                
                // Extract date range
                if (strpos($line, 'Start date:') !== false) {
                    $dateStr = trim(str_replace('# Start date:', '', $line));
                    // Format GA4 date (YYYYMMDD) to MySQL date (YYYY-MM-DD)
                    if (strlen($dateStr) == 8) {
                        $metadata['start_date'] = substr($dateStr, 0, 4) . '-' . 
                                                substr($dateStr, 4, 2) . '-' . 
                                                substr($dateStr, 6, 2);
                        error_log("Found start date: " . $metadata['start_date']);
                    }
                }
                
                if (strpos($line, 'End date:') !== false) {
                    $dateStr = trim(str_replace('# End date:', '', $line));
                    // Format GA4 date (YYYYMMDD) to MySQL date (YYYY-MM-DD)
                    if (strlen($dateStr) == 8) {
                        $metadata['end_date'] = substr($dateStr, 0, 4) . '-' . 
                                            substr($dateStr, 4, 2) . '-' . 
                                            substr($dateStr, 6, 2);
                        error_log("Found end date: " . $metadata['end_date']);
                    }
                }
                
                $lineNum++;
            }
            fclose($handle);
        }
        
        return $metadata;
    }
}
?>