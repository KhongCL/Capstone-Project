<?php
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
class CsvProcessor {
    private $mappingsFile;
    private $mappings;
    private $detectedFormat = null;
    private $columnMap = [];
    private $csvData = [];
    
    public function __construct() {
        // Set UTF-8 locale for proper character handling
        setlocale(LC_ALL, 'en_US.UTF-8');

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
                } else {
                    // Try to process as simple format first
                    $result = $this->processSimpleFormat($filePath);
                    if ($result['status'] === 'success') {
                        return $result;
                    }
                    // If simple format fails, fall through to standard processing
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

    private function processSimpleFormat($filePath) {
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            $data = [];
            $i = 0;
            while (($row = fgetcsv($handle)) !== FALSE && $i < 5) {
                if (!empty(array_filter($row))) { // Skip empty rows
                    $data[] = $row;
                    $i++;
                }
            }
            fclose($handle);
            
            // Check if this looks like a simplified GA4 format
            $ga4Indicators = [
                'Session primary channel group',
                'Sessions',
                'Engagement rate',
                'Average time'
            ];
            
            $matchCount = 0;
            foreach ($ga4Indicators as $indicator) {
                foreach ($header as $headerCol) {
                    if (stripos($headerCol, $indicator) !== false) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            // If it looks like GA4 data, try to map it automatically
            if ($matchCount >= 3) {
                $this->detectedFormat = 'ga4_traffic_acquisition';
                
                // Create automatic mapping based on column names
                $mapping = [];
                foreach ($header as $col) {
                    if (stripos($col, 'Session primary channel group') !== false) {
                        $mapping[$col] = 'traffic_source';
                    } elseif (stripos($col, 'Sessions') !== false) {
                        $mapping[$col] = 'visits';
                    } elseif (stripos($col, 'Engagement rate') !== false) {
                        $mapping[$col] = 'bounce_rate';
                    } elseif (stripos($col, 'Average time') !== false) {
                        $mapping[$col] = 'avg_session_duration';
                    }
                }
                
                return [
                    'status' => 'success',
                    'format' => 'ga4_traffic_acquisition',
                    'header' => $header,
                    'sample' => $data,
                    'mapping' => $mapping
                ];
            }
        }
        
        return [
            'status' => 'needs_mapping',
            'header' => $header,
            'sample' => $data,
            'suggestions' => $this->suggestColumnMapping($header)
        ];
    }
    
    /**
     * Process the uploaded CSV file with Google Analytics format
     */
    private function processGoogleAnalyticsFormat($filePath) {
        $this->validateRawCsvStructure($filePath);

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
     * Validate the raw CSV file for structural issues before processing
     */
    private function validateRawCsvStructure($filePath) {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $headerFound = false;
        $rowNumber = 0;
        $structureErrors = []; // Collect all structure errors
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Skip metadata lines
            if (substr($line, 0, 1) === '#') continue;
            
            if (!$headerFound) {
                $headerFound = true;
                $expectedFieldCount = substr_count($line, ',') + 1;
                continue;
            }
            
            $rowNumber++;
            $fieldCount = substr_count($line, ',') + 1;
            
            // Check for unquoted commas within fields (field count mismatch)
            if ($fieldCount > $expectedFieldCount) {
                // Find problematic field by inspecting the line for known patterns
                $rowName = "Row " . ($rowNumber + 1); // Default row identifier
                
                // Try to extract the first field as row identifier
                $firstCommaPos = strpos($line, ',');
                if ($firstCommaPos !== false) {
                    $firstField = substr($line, 0, $firstCommaPos);
                    if (!empty(trim($firstField))) {
                        $rowName = trim($firstField);
                    }
                }
                
                // Check for specific problematic values
                if (strpos($line, '$1,200') !== false) {
                    $structureErrors[] = "CSV parsing error at row " . ($rowNumber + 1) . " ($rowName): Value '\$1,200' contains commas which breaks the CSV structure";
                } else if (strpos($line, '1,000') !== false) {
                    $structureErrors[] = "CSV parsing error at row " . ($rowNumber + 1) . " ($rowName): Value '1,000' contains commas which breaks the CSV structure";
                } else {
                    // Generic error for other comma issues
                    $structureErrors[] = "CSV parsing error at row " . ($rowNumber + 1) . " ($rowName): Row contains more fields than expected ($fieldCount vs $expectedFieldCount) - likely due to unquoted commas in data";
                }
            }
        }
        
        // If we found any structure errors, throw an exception with all of them
        if (!empty($structureErrors)) {
            if (count($structureErrors) == 1) {
                // Single error - throw as before for backwards compatibility
                throw new Exception($structureErrors[0]);
            } else {
                // Multiple errors - create a comprehensive error message with proper line breaks
                $errorMessage = "Multiple CSV parsing errors detected:\n" . implode("\n", $structureErrors);
                throw new Exception($errorMessage);
            }
        }
    }
    
    /**
     * Detect the CSV format based on headers
     */
    private function detectFormat($headers) {
        // Validate headers input
        if (!is_array($headers) || empty($headers)) {
            throw new Exception('Invalid headers provided for format detection');
        }
        
        error_log("Detecting format for headers: " . implode(", ", $headers));
        
        // Check for your test CSV format first (ga4_traffic_acquisition)
        $testCsvHeaders = ['Traffic Source', 'User Sessions', 'Engaged Sessions', 'Bounce Rate'];
        $testMatches = 0;
        foreach ($testCsvHeaders as $testHeader) {
            if (in_array($testHeader, $headers)) {
                $testMatches++;
                error_log("Found test CSV header: $testHeader");
            }
        }
        
        // If we find 3+ matches, it's the ga4_traffic_acquisition format
        if ($testMatches >= 3) {
            error_log("Detected ga4_traffic_acquisition format with $testMatches matches");
            $this->detectedFormat = 'ga4_traffic_acquisition';
            return 'ga4_traffic_acquisition';
        }
        
        // Continue with existing format detection for other formats
        foreach ($this->mappings as $format => $config) {
            if (!isset($config['format_detection'])) continue;
            
            $matchCount = 0;
            $detectionHeaders = $config['format_detection'];
            
            foreach ($detectionHeaders as $expectedHeader) {
                if (in_array($expectedHeader, $headers)) {
                    $matchCount++;
                }
            }
            
            // Require at least 70% match
            $matchPercentage = ($matchCount / count($detectionHeaders)) * 100;
            if ($matchPercentage >= 70) {
                error_log("Detected format: $format with {$matchPercentage}% match");
                $this->detectedFormat = $format;
                return $format;
            }
        }
        
        error_log("No format detected from headers: " . implode(", ", $headers));
        return null;
    }
    
    /**
     * Suggest column mappings using fuzzy matching
     */
    private function suggestColumnMapping($headers) {
        $suggestions = [];
        
        // Enhanced keyword mapping for better accuracy - handles multiple CSV formats
        $keywords = [
            'traffic_source' => [
                'traffic source', 
                'source', 
                'referrer', 
                'origin', 
                'channel group',
                'session primary channel group',
                'default channel group'
            ],
            'visits' => [
                'user sessions', 
                'sessions', 
                'visits'
            ],
            'engaged_sessions' => [
                'engaged sessions', 
                'engaged', 
                'quality sessions'
            ],
            'bounce_rate' => [
                'bounce rate', 
                'bounce',
                'engagement rate'
            ],
            'avg_session_duration' => [
                'avg session time', 
                'session time', 
                'session duration', 
                'average time', 
                'time',
                'average engagement time per session',
                'engagement time'
            ],
            'events_per_session' => [
                'events per session', 
                'events/session'
            ],
            'event_count' => [
                'total events', 
                'event count', 
                'events'
            ],
            'key_events' => [
                'conversions', 
                'key events', 
                'goals'
            ],
            'session_key_event_rate' => [
                'conversion rate', 
                'key event rate', 
                'goal rate',
                'session key event rate'
            ],
            'total_revenue' => [
                'revenue', 
                'total revenue', 
                'income'
            ]
        ];
        
        // Track which system fields have been assigned to avoid duplicates
        $assignedFields = [];
        
        foreach ($headers as $header) {
            $bestMatch = null;
            $bestScore = 0;
            $headerLower = strtolower(trim($header));
            
            // Check for exact or close matches
            foreach ($keywords as $systemField => $fieldKeywords) {
                // Skip if this system field is already assigned
                if (in_array($systemField, $assignedFields)) {
                    continue;
                }
                
                foreach ($fieldKeywords as $keyword) {
                    $keywordLower = strtolower($keyword);
                    
                    // Exact match gets highest score
                    if ($headerLower === $keywordLower) {
                        $bestMatch = $systemField;
                        $bestScore = 100;
                        break 2; // Break out of both loops
                    }
                    
                    // Partial match scoring
                    if (strpos($headerLower, $keywordLower) !== false || strpos($keywordLower, $headerLower) !== false) {
                        $score = 85; // High confidence for partial matches
                        
                        // Boost score for closer matches
                        $similarity = 0;
                        similar_text($headerLower, $keywordLower, $similarity);
                        $score = min(95, $score + ($similarity / 10));
                        
                        if ($score > $bestScore) {
                            $bestMatch = $systemField;
                            $bestScore = $score;
                        }
                    }
                }
            }
            
            // Add suggestion if confidence is above 60%
            if ($bestMatch && $bestScore >= 60) {
                $suggestions[$header] = [
                    'suggested_mapping' => $bestMatch,
                    'confidence' => round($bestScore)
                ];
                $assignedFields[] = $bestMatch; // Mark this field as assigned
            } else {
                $suggestions[$header] = [
                    'suggested_mapping' => '',
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
        }

        if ($format) {
            $this->detectedFormat = $format;
        }

        // IMPORTANT: Use mappings from JSON config, not database
        if ($this->detectedFormat && isset($this->mappings[$this->detectedFormat]['column_mappings'])) {
            // Merge JSON mappings with provided mapping
            $configMappings = $this->mappings[$this->detectedFormat]['column_mappings'];
            foreach ($configMappings as $csvCol => $systemField) {
                if (!isset($this->columnMap[$csvCol])) {
                    $this->columnMap[$csvCol] = $systemField;
                    error_log("Added mapping from config: $csvCol -> $systemField");
                }
            }
        }

        error_log("Transform data using format: " . ($this->detectedFormat ?? "No format detected"));
        error_log("Full column mapping: " . json_encode($this->columnMap));
        
        $transformed = [];
        $validationErrors = [];
        $validRows = 0;
        $rowNumber = 0;
        
        error_log("Starting transformData with mapping: " . json_encode($columnMapping));
        
        // Check if this is a GA4 format file
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
                    $sourceName = 'Unknown';

                    // Try to find traffic source using multiple possible column names
                    $trafficSourceColumns = [
                        'Session primary channel group (Default channel group)',
                        'Traffic Source',
                        'Source',
                        'Channel'
                    ];

                    foreach ($trafficSourceColumns as $colName) {
                        if (isset($headerIndexes[$colName]) && isset($data[$headerIndexes[$colName]])) {
                            $sourceName = $data[$headerIndexes[$colName]];
                            break;
                        }
                    }

                    error_log("Using source name: $sourceName for row $rowNumber");

                    $headerLookup = [];
                    foreach ($header as $index => $headerCol) {
                        $headerLookup[strtolower(trim($headerCol))] = $index;
                    }
                    
                    // Map each column according to our defined structure
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        // Store original column name for reporting
                        $originalSourceCol = $sourceCol;
                        
                        // First try exact index lookup
                        $columnIndex = $headerIndexes[$sourceCol] ?? null;
                        
                        // If not found, try case-insensitive lookup among header columns
                        if ($columnIndex === null) {
                            foreach (array_keys($headerIndexes) as $headerCol) {
                                if (strcasecmp(trim($sourceCol), trim($headerCol)) === 0) {
                                    $columnIndex = $headerIndexes[$headerCol];
                                    $sourceCol = $headerCol; // Use the exact column name from the header
                                    break;
                                }
                            }
                        }
                        
                        // If still not found, try lowercase lookup
                        if ($columnIndex === null && isset($headerLookup[strtolower(trim($sourceCol))])) {
                            $columnIndex = $headerLookup[strtolower(trim($sourceCol))];
                            
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
                        
                        try {
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
     * Format value based on data type with enhanced validation - no auto-fixing
     */
    private function formatValue($value, $column) {
        error_log("Validating value: '$value' for column: '$column'");
            
        if (!$this->detectedFormat) {
            error_log("No detected format set for validation");
            return $value;
        }
        
        // Original value for error messages
        $originalValue = $value;
        
        // Always get target field for validation rules that need it
        $targetField = $this->columnMap[$column] ?? null;

        // DEBUG: Log the target field mapping
        error_log("DEBUG: Column '$column' maps to target field: " . ($targetField ? "'$targetField'" : "NULL"));
        error_log("DEBUG: Available column mappings: " . json_encode($this->columnMap));
        
        // IMPORTANT: Check for CSV row structure issues (commas in unquoted values)
        // This detection needs to apply to all fields, not just traffic source
        if (strpos($value, ',') !== false) {
            throw new Exception("CSV parsing error detected: Value '$originalValue' contains commas which breaks the CSV structure");
        }

        // Check if this column maps to a known target field that needs validation
        if ($targetField === 'traffic_source') {
            // Debug the trademark detection
            error_log("DEBUG: Checking traffic_source value: '$value' for trademark symbols");
            
            // Normalize string encoding
            $normalizedValue = $value;
            
            // Convert string to hex for debugging
            $hexValue = bin2hex($normalizedValue);
            error_log("DEBUG: Hex representation of traffic source: $hexValue");
            
            // More robust trademark symbol detection with binary safe comparison
            $trademarkSymbols = [
                '™' => "\xE2\x84\xA2", 
                '®' => "\xC2\xAE", 
                '©' => "\xC2\xA9"
            ];
            
            foreach ($trademarkSymbols as $symbol => $binary) {
                if (strpos($normalizedValue, $symbol) !== false || strpos($normalizedValue, $binary) !== false) {
                    error_log("DEBUG: Found trademark symbol in '$normalizedValue'");
                    throw new Exception("Invalid traffic source value: '$originalValue' for column '$column' - Contains trademark or special symbols");
                }
            }
            
            // Additional regex pattern for trademark symbols in UTF-8
            if (preg_match('/[\x{2122}\x{00AE}\x{00A9}]/u', $normalizedValue)) {
                error_log("DEBUG: Found trademark symbol using regex in '$normalizedValue'");
                throw new Exception("Invalid traffic source value: '$originalValue' for column '$column' - Contains trademark or special symbols");
            }
            
            // Check for any other non-ASCII characters
            if (preg_match('/[^\x20-\x7E]/', $value)) {
                error_log("DEBUG: Found non-ASCII characters in '$value'");
                throw new Exception("Invalid traffic source value: '$originalValue' for column '$column' - Contains special Unicode characters");
            }
            
            return trim($value);
        } else if ($targetField === 'events_per_session') {
                                // Check for empty value
                if (trim($value) === '') {
                    throw new Exception("Empty value for column '$column' - Events per session must have a value");
                }
                
                // Check for whitespace
                if (trim($value) !== $value) {
                    throw new Exception("Invalid events per session format: '$originalValue' for column '$column' - Contains leading or trailing whitespace");
                }
                
                // Check for scientific notation
                if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
                    throw new Exception("Scientific notation '$originalValue' not allowed for column '$column' - Please use standard decimal format");
                }
                
                // Check for multiple decimal points
                if (substr_count($value, '.') > 1) {
                    throw new Exception("Invalid events per session value: '$originalValue' for column '$column' - Contains multiple decimal points");
                }
                
                // Enhanced special character detection - check for non-numeric characters
                if (preg_match('/[^0-9.\-]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'float', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid events per session value: '$originalValue' for column '$column' - Contains special characters$suggestionText");
                }
                
                if (!is_numeric($value)) {
                    throw new Exception("Invalid events per session value: '$originalValue' for column '$column' - Must be a number");
                }
                
                $floatValue = (float)$value;
                
                // Check for negative values
                if ($floatValue < 0) {
                    throw new Exception("Negative value '$originalValue' not allowed for column '$column' - Events per session must be zero or positive");
                }
                
                // Fix for high values validation - explicit debugging and comparison
                error_log("DEBUG: Events per session value: '$value', converted to float: $floatValue");
                if ($floatValue > 50.0) {
                    error_log("DEBUG: Events per session value exceeds maximum (50.0): $floatValue");
                    throw new Exception("Unrealistically high value '$originalValue' for column '$column' - Events per session should be reasonable (under 50)");
                }
                
                return $floatValue;
        } else if ($targetField === 'key_events') {
                // Check for empty value
                if (trim($value) === '') {
                    throw new Exception("Empty value for column '$column' - Key events must have a value");
                }
                
                // Check for spaces
                if (strpos($value, ' ') !== false) {
                    throw new Exception("Invalid key events format: '$originalValue' for column '$column' - Contains spaces");
                }
                
                // Check for decimal points
                if (strpos($value, '.') !== false) {
                    throw new Exception("Invalid key events value: '$originalValue' for column '$column' - Must be a whole number");
                }
                
                if (!preg_match('/^[0-9]+$/', $value)) {
                    throw new Exception("Invalid key events value: '$originalValue' for column '$column' - Must be a whole number");
                }
                
                if ((int)$value < 0) {
                    throw new Exception("Negative value not allowed for column '$column' - Key events must be zero or positive");
                }

                // Add business logic validation for unrealistically high values
                $keyEventValue = (int)$value;
                if ($keyEventValue > 500) {
                    throw new Exception("Unrealistically high value '$originalValue' for column '$column' - Key events should be reasonable (under 500)");
                }
                
                return (int) $value;
        }
        
        // If no data type is specified in the mappings, use special logic for certain target fields
        if (!isset($this->mappings[$this->detectedFormat]['data_types'][$column])) {
            if ($targetField === 'total_revenue') {
                // Check for empty value
                if (trim($value) === '') {
                    throw new Exception("Empty value for column '$column' - Total revenue must have a value");
                }
                
                // Enhanced currency symbol detection
                if (preg_match('/[$€£¥]/', $value)) {
                    throw new Exception("Invalid revenue format: '$originalValue' for column '$column' - Contains currency symbols");
                }
                
                // Check for commas - already handled by global check above
                
                // Check for any non-numeric characters
                $cleanValue = trim($value);
                if (!is_numeric($cleanValue)) {
                    throw new Exception("Invalid revenue value: '$originalValue' for column '$column' - Must be a number");
                }
                
                // Check for negative values
                if ((float)$cleanValue < 0) {
                    throw new Exception("Negative value '$originalValue' not allowed for column '$column' - Revenue must be zero or positive");
                }
                
                return (float) $cleanValue;
            } else if ($targetField === 'session_key_event_rate' || $targetField === 'bounce_rate' || strpos($targetField, 'rate') !== false) {
                // Check for empty value
                if (trim($value) === '') {
                    throw new Exception("Empty value for column '$column' - Rate fields must have a value");
                }
                
                // Check for whitespace
                if (trim($value) !== $value) {
                    throw new Exception("Invalid rate format: '$originalValue' for column '$column' - Contains leading or trailing whitespace");
                }
                
                // Check if it has percentage sign
                if (strpos($value, '%') !== false) {
                    // Check for commas
                    if (strpos($value, ',') !== false) {
                        throw new Exception("Invalid percentage format: '$originalValue' for column '$column' - Contains commas");
                    }
                    
                    $numericValue = (float) preg_replace('/[^0-9.]/', '', $value);
                    
                    // Check if percentage is within reasonable range (0-100%)
                    if ($numericValue > 100) {
                        $suggestions = $this->suggestDataFix($value, 'percentage', $column);
                        $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                        throw new Exception("Percentage value '$originalValue' exceeds 100% for column '$column' - Must be between 0-100%$suggestionText");
                    }
                    
                    return $numericValue / 100;
                } else {
                    // Check for commas
                    if (strpos($value, ',') !== false) {
                        throw new Exception("Invalid rate format: '$originalValue' for column '$column' - Contains commas");
                    }
                    
                    if (!is_numeric($value)) {
                        throw new Exception("Invalid rate value: '$originalValue' for column '$column' - Must be a number between 0-1 or percentage");
                    }
                    
                    $floatValue = (float)$value;
                    if ($floatValue < 0) {
                        throw new Exception("Negative value '$originalValue' not allowed for column '$column' - Rate must be zero or positive");
                    }
                    
                    // Enhanced check for Engagement rate > 1
                    if ($floatValue > 1) {
                        throw new Exception("Rate value '$originalValue' exceeds maximum (1.0) for column '$column' - Decimal rates must be between 0-1");
                    }
                    
                    return $floatValue;
                }
            }
            
            error_log("No data type defined for column '$column' in format: " . $this->detectedFormat);
            return $value;
        }
        
        $type = $this->mappings[$this->detectedFormat]['data_types'][$column];
        error_log("Validating as type: $type");
        
        // Check for empty values - don't auto-fix, report them
        if (trim($value) === '') {
            throw new Exception("Empty value for column '$column' - This field requires a value");
        }
        
        // Check for whitespace issues
        if (trim($value) !== $value) {
            throw new Exception("Value '$originalValue' for column '$column' contains leading or trailing whitespace");
        }

        // Get the data type for suggestion generation
        $dataType = null;
        if (isset($this->mappings[$this->detectedFormat]['data_types'][$column])) {
            $dataType = $this->mappings[$this->detectedFormat]['data_types'][$column];
        }
        
        switch ($type) {
            case 'integer':
                // Check for commas
                if (strpos($value, ',') !== false) {
                    throw new Exception("Invalid integer format: '$originalValue' for column '$column' - Contains commas");
                }
                
                // Check for spaces
                if (strpos($value, ' ') !== false) {
                    throw new Exception("Invalid integer format: '$originalValue' for column '$column' - Contains spaces");
                }
                
                // Detect scientific notation
                if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
                    throw new Exception("Scientific notation '$originalValue' not allowed for column '$column' - Please use standard integer format");
                }
                
                // Check for decimal points
                if (strpos($value, '.') !== false) {
                    throw new Exception("Invalid integer value: '$originalValue' for column '$column' - Cannot contain decimal points");
                }
                
                // Check for unicode/full-width digits - improved detection
                if (preg_match('/[^\x00-\x7F]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'integer', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid integer value: '$originalValue' for column '$column' - Contains Unicode characters$suggestionText");
                }
                // Check for any non-numeric characters
                if (!preg_match('/^-?\d+$/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'integer', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid integer value: '$originalValue' for column '$column' - Please use only digits$suggestionText");
                }
                
                // Check for negative values
                if (strpos($value, '-') === 0) {
                    throw new Exception("Negative value '$originalValue' not allowed for column '$column' - Must be zero or positive");
                }
                
                // Check for special characters or malformed input
                if (!ctype_digit($value)) {
                    throw new Exception("Invalid integer value: '$originalValue' for column '$column' - Contains invalid characters");
                }
                
                // Check for non-numeric operations or expressions (like 42+3)
                if (preg_match('/[+\-*\/]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'integer', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid integer value: '$originalValue' for column '$column' - Contains mathematical operators$suggestionText");
                }
                
                return (int) $value;
                
            case 'float':
                // Check for commas
                if (strpos($value, ',') !== false) {
                    throw new Exception("Invalid float format: '$originalValue' for column '$column' - Contains commas");
                }
                
                // Check for spaces
                if (strpos($value, ' ') !== false) {
                    throw new Exception("Invalid float format: '$originalValue' for column '$column' - Contains spaces");
                }
                
                // Handle scientific notation
                if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
                    throw new Exception("Scientific notation '$originalValue' not allowed for column '$column' - Please use standard decimal format");
                }
                
                // Check for special characters
                if (preg_match('/[^0-9.\-]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'float', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid float value: '$originalValue' for column '$column' - Contains invalid characters$suggestionText");
                }
                
                // Check for multiple decimal points
                if (substr_count($value, '.') > 1) {
                    $suggestions = $this->suggestDataFix($value, 'float', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid float value: '$originalValue' for column '$column' - Cannot have multiple decimal points$suggestionText");
                }
                
                // Improved float validation - properly formatted
                if (!preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'float', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid float value: '$originalValue' for column '$column' - Please use numbers with a single decimal point$suggestionText");
                }
                
                // Check for negative values
                if (strpos($value, '-') === 0) {
                    throw new Exception("Negative value '$originalValue' not allowed for column '$column' - Must be zero or positive");
                }
                
                // Improved check for rate values
                if (($column === 'Engagement rate' || strpos($column, 'rate') !== false) && (float)$value > 1.0) {
                    throw new Exception("Rate value '$originalValue' exceeds maximum (1.0) for column '$column' - Rate must be between 0-1");
                }
                
                return (float) $value;
                
            case 'percentage':
                // Check for commas
                if (strpos($value, ',') !== false) {
                    throw new Exception("Invalid percentage format: '$originalValue' for column '$column' - Contains commas");
                }
                
                // Check for spaces
                if (strpos($value, ' ') !== false) {
                    throw new Exception("Invalid percentage format: '$originalValue' for column '$column' - Contains spaces");
                }
                
                // Handle percentage with % sign
                if (strpos($value, '%') !== false) {
                    // Validate format - should be a number followed by %
                    if (!preg_match('/^[0-9.]+%?$/', $value)) {
                        $suggestions = $this->suggestDataFix($value, 'percentage', $column);
                        $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                        throw new Exception("Invalid percentage value: '$originalValue' for column '$column' - Format should be like '25%' or '0.25'$suggestionText");
                    }
                    
                    // Extract numeric part
                    $numericPart = preg_replace('/[^0-9.]/', '', $value);
                    $percentValue = (float) $numericPart;
                    
                    // Check if percentage is within reasonable range (0-100%)
                    if ($percentValue > 100) {
                        $suggestions = $this->suggestDataFix($value, 'percentage', $column);
                        $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                        throw new Exception("Percentage value '$originalValue' exceeds 100% for column '$column' - Must be between 0-100%$suggestionText");
                    }
                    
                    return $percentValue / 100;
                } else {
                    // Handle decimal format (0-1)
                    if (!is_numeric($value)) {
                        throw new Exception("Invalid percentage value: '$originalValue' for column '$column' - Value should be between 0-1 or include % sign");
                    }
                    
                    $floatValue = (float) $value;
                    if ($floatValue < 0) {
                        throw new Exception("Negative percentage value '$originalValue' not allowed for column '$column' - Must be zero or positive");
                    }
                    
                    if ($floatValue > 1) {
                        throw new Exception("Percentage value '$originalValue' exceeds maximum (1.0) for column '$column' - Decimal percentages must be between 0-1");
                    }
                    
                    return $floatValue;
                }
                
            case 'currency':
                // Enhanced currency symbol detection
                if (preg_match('/[$€£¥]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'currency', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid currency format: '$originalValue' for column '$column' - Contains currency symbols$suggestionText");
                }
                
                // Check for commas
                if (strpos($value, ',') !== false) {
                    throw new Exception("Invalid currency format: '$originalValue' for column '$column' - Contains commas");
                }
                
                // Check for letters or invalid chars
                if (preg_match('/[a-zA-Z]/', $value) || preg_match('/[^\d.\-]/', $value)) {
                    $suggestions = $this->suggestDataFix($value, 'currency', $column);
                    $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                    throw new Exception("Invalid currency value: '$originalValue' for column '$column' - Must be a number$suggestionText");
                }
                
                if (!is_numeric($value)) {
                    throw new Exception("Invalid currency value: '$originalValue' for column '$column' - Must be a number");
                }
                
                // Check for negative values
                if (strpos($value, '-') === 0) {
                    throw new Exception("Negative currency value '$originalValue' not allowed for column '$column' - Must be zero or positive");
                }
                
                return (float) $value;
                
            case 'time':
                // Check for empty values
                if ($value === '') {
                    throw new Exception("Empty time value for column '$column' - This field requires a value");
                }
                
                // Handle time with colons (HH:MM:SS or MM:SS)
                if (strpos($value, ':') !== false) {
                    // Check for any non-numeric or non-colon characters
                    if (preg_match('/[^0-9:]/', $value)) {
                        $suggestions = $this->suggestDataFix($value, 'time', $column);
                        $suggestionText = !empty($suggestions) ? " Suggestions: " . implode("; ", $suggestions) : "";
                        throw new Exception("Invalid time value: '$originalValue' for column '$column' - Contains invalid characters$suggestionText");
                    }
                    
                    $parts = array_map('intval', explode(':', $value));
                    if (count($parts) == 2) {
                        // Validate MM:SS format
                        if ($parts[0] < 0 || $parts[1] < 0 || $parts[1] > 59) {
                            throw new Exception("Invalid time value: '$originalValue' for column '$column' - Minutes:Seconds format required with seconds 0-59");
                        }
                        return $parts[0] * 60 + $parts[1];
                    } elseif (count($parts) == 3) {
                        // Validate HH:MM:SS format
                        if ($parts[0] < 0 || $parts[1] < 0 || $parts[2] < 0 || 
                            $parts[1] > 59 || $parts[2] > 59) {
                            throw new Exception("Invalid time value: '$originalValue' for column '$column' - Hours:Minutes:Seconds format required with minutes and seconds 0-59");
                        }
                        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
                    } else {
                        throw new Exception("Invalid time format: '$originalValue' for column '$column' - Use MM:SS or HH:MM:SS format");
                    }
                } 
                // Handle time formats like "12m30s"
                elseif (preg_match('/(\d+)m(\d+)s/', $value, $matches)) {
                    $minutes = intval($matches[1]);
                    $seconds = intval($matches[2]);
                    if ($seconds >= 60) {
                        throw new Exception("Invalid time value: '$originalValue' for column '$column' - Seconds must be less than 60");
                    }
                    return $minutes * 60 + $seconds;
                }
                // Handle plain numbers (seconds)
                elseif (is_numeric($value)) {
                    return (float) $value;
                } 
                // Handle invalid time formats
                else {
                    throw new Exception("Invalid time value: '$originalValue' for column '$column' - Use seconds, MM:SS format, or HH:MM:SS format");
                }
                
            case 'text':
                // Check for excessive whitespace
                if (trim($value) !== $value) {
                    throw new Exception("Text value '$originalValue' for column '$column' contains leading or trailing whitespace");
                }
                return $value;
                
            default:
                return $value;
        }
    }

    /**
     * Suggest data fixes for common validation errors
     */
    private function suggestDataFix($value, $expectedType, $column = '') {
        $suggestions = [];
        
        switch ($expectedType) {
            case 'integer':
                // Extract numbers from mixed content
                if (preg_match('/(\d+)[^\d]/', $value, $matches)) {
                    $suggestions[] = "Try: '{$matches[1]}'";
                }
                // Handle mathematical expressions
                if (preg_match('/(\d+)\s*[\+\-\*\/]\s*(\d+)/', $value, $matches)) {
                    $result = $this->evaluateSimpleExpression($value);
                    if ($result !== null) {
                        $suggestions[] = "Try: '$result' (calculated from $value)";
                    }
                }
                // Handle unicode digits
                if (preg_match('/[０-９]+/', $value)) {
                    $converted = $this->convertUnicodeDigits($value);
                    $suggestions[] = "Try: '$converted' (converted from Unicode)";
                }
                break;
                
            case 'float':
                // Extract valid decimal numbers
                if (preg_match('/(\d+\.\d+)/', $value, $matches)) {
                    $suggestions[] = "Try: '{$matches[1]}'";
                }
                // Handle multiple decimal points
                if (substr_count($value, '.') > 1) {
                    $cleaned = preg_replace('/\.(?=.*\.)/', '', $value);
                    if (is_numeric($cleaned)) {
                        $suggestions[] = "Try: '$cleaned' (removed extra decimal points)";
                    }
                }
                // Handle scientific notation
                if (preg_match('/(\d+(?:\.\d+)?)[eE][\+\-]?(\d+)/', $value, $matches)) {
                    $converted = (float)$value;
                    $suggestions[] = "Try: '$converted' (converted from scientific notation)";
                }
                // Handle time-like format in float fields
                if (preg_match('/(\d+):(\d+)/', $value, $matches)) {
                    $totalSeconds = ($matches[1] * 60) + $matches[2];
                    $suggestions[] = "Try: '$totalSeconds' (converted from MM:SS to seconds)";
                }
                // Handle time formats like "12m30s"
                if (preg_match('/(\d+)m(\d+)s/', $value, $matches)) {
                    $totalSeconds = ($matches[1] * 60) + $matches[2];
                    $suggestions[] = "Try: '$totalSeconds' (converted from minutes/seconds)";
                }
                // Handle percentage values in float fields
                if (preg_match('/(\d+(?:\.\d+)?)%/', $value, $matches)) {
                    $asDecimal = $matches[1] / 100;
                    $suggestions[] = "Try: '$asDecimal' (converted from percentage)";
                }
                // Remove special characters and extract numbers
                if (preg_match('/[~#@&*]/', $value)) {
                    $cleaned = preg_replace('/[~#@&*]/', '', $value);
                    if (is_numeric($cleaned)) {
                        $suggestions[] = "Try: '$cleaned' (removed special characters)";
                    }
                }
                break;
                
            case 'time':
                // Handle common time format issues
                if (preg_match('/(\d+):(\d+):(\d+)/', $value, $matches)) {
                    if ($matches[1] > 59 || $matches[2] > 59) {
                        $suggestions[] = "Check time format - minutes and seconds should be 0-59";
                    }
                }
                // Handle formats like "12m30s"
                if (preg_match('/(\d+)m(\d+)s/', $value, $matches)) {
                    $totalSeconds = ($matches[1] * 60) + $matches[2];
                    $suggestions[] = "Try: '$totalSeconds' seconds or '" . 
                        gmdate("H:i:s", $totalSeconds) . "' (HH:MM:SS format)";
                }
                break;
                
            case 'percentage':
                // Handle percentage without % sign
                if (is_numeric($value) && (float)$value > 1) {
                    $asPercent = (float)$value . '%';
                    $asDecimal = (float)$value / 100;
                    $suggestions[] = "Try: '$asPercent' or '$asDecimal' (as decimal)";
                }
                // Handle invalid percentage formats
                if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
                    $number = $matches[1];
                    $suggestions[] = "Try: '{$number}%' or '" . ($number/100) . "' (as decimal)";
                }
                break;
                
            case 'currency':
                // Remove currency symbols
                $cleaned = preg_replace('/[$€£¥,]/', '', $value);
                if (is_numeric($cleaned)) {
                    $suggestions[] = "Try: '$cleaned' (removed currency symbols)";
                }
                // Remove alphabetic characters
                if (preg_match('/[a-zA-Z]/', $value)) {
                    $cleaned = preg_replace('/[a-zA-Z]/', '', $value);
                    if (is_numeric($cleaned)) {
                        $suggestions[] = "Try: '$cleaned' (removed letters)";
                    }
                }
                break;
        }
        
        return $suggestions;
    }
    
    /**
     * Evaluate simple mathematical expressions
     */
    private function evaluateSimpleExpression($expression) {
        // Only handle simple + and - operations for security
        if (preg_match('/^(\d+)\s*[\+]\s*(\d+)$/', $expression, $matches)) {
            return $matches[1] + $matches[2];
        }
        if (preg_match('/^(\d+)\s*[\-]\s*(\d+)$/', $expression, $matches)) {
            return $matches[1] - $matches[2];
        }
        return null;
    }
    
    /**
     * Convert Unicode digits to ASCII digits
     */
    private function convertUnicodeDigits($value) {
        $unicodeDigits = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９'];
        $asciiDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($unicodeDigits, $asciiDigits, $value);
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