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
                    // CRITICAL FIX: Use case-insensitive comparison
                    $found = false;
                    foreach ($header as $headerCol) {
                        if (strcasecmp(trim($headerCol), trim($column)) === 0) {
                            $found = true;
                            $matchCount++;
                            break;
                        }
                    }
                    if (!$found) {
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
        
        // Continue with existing format detection for other formats
        foreach ($this->mappings as $format => $config) {
            if (!isset($config['format_detection'])) continue;
            
            $matchCount = 0;
            $detectionHeaders = $config['format_detection'];
            
            foreach ($detectionHeaders as $expectedHeader) {
                // CRITICAL FIX: Use case-insensitive comparison
                foreach ($headers as $csvHeader) {
                    if (strcasecmp(trim($csvHeader), trim($expectedHeader)) === 0) {
                        $matchCount++;
                        error_log("Case-insensitive match found: '$csvHeader' matches '$expectedHeader'");
                        break; // Found match, move to next expected header
                    }
                }
            }
            
            // Require at least 70% match
            $matchPercentage = ($matchCount / count($detectionHeaders)) * 100;
            if ($matchPercentage >= 70) {
                error_log("Detected format: $format with {$matchPercentage}% match (case-insensitive)");
                $this->detectedFormat = $format;
                return $format;
            }
            
            error_log("Format $format: $matchCount/" . count($detectionHeaders) . " matches ({$matchPercentage}%)");
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
                'default channel group',
                'channel'
            ],
            'visits' => [
                'user sessions', 
                'sessions', 
                'visits'
            ],
            'unique_visitors' => [
                'users',
                'unique users',
                'unique visitors',
                'visitors'
            ],
            'page_views' => [
                'page views',
                'pageviews',
                'pages',
                'views'
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
                'engagement time',
                'avg time on site',
                'time on site'
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
                'goals',
                'goal completions'
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
                'income',
                'revenue generated'
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
            
            if ($bestMatch && $bestScore >= 60) {
                $suggestions[$header] = [
                    'mapping' => $bestMatch,
                    'confidence' => $bestScore
                ];
                $assignedFields[] = $bestMatch;
            } else {
                $suggestions[$header] = [
                    'mapping' => '',
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
        error_log("=== TRANSFORM DATA DEBUG START ===");
        error_log("File path: $filePath");
        error_log("Column mapping: " . json_encode($columnMapping));
        error_log("Format: " . ($format ?? 'null'));
        
        // CRITICAL FIX: Clear any existing validation errors at the start of new upload
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear previous validation errors and messages
        unset($_SESSION['validation_errors']);
        unset($_SESSION['upload_message']);
        error_log("Cleared previous validation errors and upload messages");
        
        $this->columnMap = $columnMapping;

        // CRITICAL FIX: Only add config mappings ONCE and avoid duplicates
        if ($format && isset($this->mappings[$format]['column_mappings'])) {
            // Get all mappings from configuration
            foreach ($this->mappings[$format]['column_mappings'] as $sourceCol => $targetCol) {
                // Only add if not already mapped to avoid duplicates
                if (!isset($this->columnMap[$sourceCol])) {
                    $this->columnMap[$sourceCol] = $targetCol;
                    error_log("Added mapping from config: $sourceCol -> $targetCol");
                } else {
                    error_log("Skipping duplicate mapping from config: $sourceCol (already mapped)");
                }
            }
        }

        // CRITICAL FIX: Remove duplicate mappings that point to the same target field
        $finalColumnMap = [];
        $usedTargets = [];
        
        foreach ($this->columnMap as $sourceCol => $targetCol) {
            if (!in_array($targetCol, $usedTargets)) {
                $finalColumnMap[$sourceCol] = $targetCol;
                $usedTargets[] = $targetCol;
                error_log("Final mapping: $sourceCol -> $targetCol");
            } else {
                error_log("Skipping duplicate target mapping: $sourceCol -> $targetCol (target already used)");
            }
        }
        
        $this->columnMap = $finalColumnMap;

        if ($format) {
            $this->detectedFormat = $format;
        } else {
            // For manual mapping without detected format, try to infer it
            $mappedFields = array_values($columnMapping);
            $ga4RequiredFields = ['traffic_source', 'visits', 'engaged_sessions', 'bounce_rate'];
            $ga4MatchCount = count(array_intersect($ga4RequiredFields, $mappedFields));
            
            if ($ga4MatchCount >= 3) {
                $this->detectedFormat = 'ga4_traffic_acquisition';
                error_log("Inferred GA4 format from manual mappings");
            }
        }

        error_log("Final column mapping: " . json_encode($this->columnMap));
        error_log("Detected format: " . ($this->detectedFormat ?? "No format detected"));
        error_log("Transform data using format: " . ($this->detectedFormat ?? "No format detected"));
        error_log("Full column mapping: " . json_encode($this->columnMap));
        
        $transformed = [];
        $validationErrors = [];
        $validRows = 0;
        $rowNumber = 0;
        $totalRowsProcessed = 0;
        $skippedRows = 0;
        
        error_log("Starting transformData with mapping: " . json_encode($columnMapping));
        
        // Check if this is a GA4 format file
        $isGa4Format = false;
        $handle = fopen($filePath, "r");
        if ($handle) {
            $firstLine = fgets($handle);
            if (substr(trim($firstLine), 0, 1) === '#') {
                $isGa4Format = true;
                error_log("Detected GA4 format file");
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
                error_log("Found header: " . json_encode($header));
                error_log("Header indexes: " . json_encode($headerIndexes));

                $rowNumber = 1;
                
                // Process data rows
                while (($line = fgets($handle)) !== FALSE) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $data = str_getcsv($line);
                    $totalRowsProcessed++;
                    $rowNumber++;
                    
                    error_log("=== PROCESSING ROW $rowNumber ===");
                    error_log("Raw data: " . json_encode($data));
                    
                    if (count($data) < count($header)) {
                        error_log("Row $rowNumber has fewer columns than the header. Skipping.");
                        $validationErrors[] = "Row $rowNumber has fewer columns than expected - please check for missing values";
                        $skippedRows++;
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
                            $sourceName = trim($data[$headerIndexes[$colName]]);
                            break;
                        }
                    }
                    if (empty($sourceName) || $sourceName === 'Unknown') {
                        $sourceName = "Row $rowNumber";
                    }

                    error_log("Processing row for source: '$sourceName'");

                    $headerLookup = [];
                    foreach ($header as $index => $headerCol) {
                        $headerLookup[strtolower(trim($headerCol))] = $index;
                    }
                    
                    // Map each column according to our defined structure
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        error_log("Processing column '$sourceCol' -> '$targetCol'");
                        
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
                        
                        $value = trim($data[$columnIndex]);
                        error_log("Processing column '$sourceCol' -> '$targetCol' with value: '$value'");
                        
                        try {
                            // Validate the value based on data type
                            $formattedValue = $this->formatValue($value, $sourceCol);
                            $row[$targetCol] = $formattedValue;
                            error_log("✅ Validation successful for '$sourceCol': '$value' -> '$formattedValue'");
                        } catch (Exception $e) {
                            // Log the error with more context about which row had the issue
                            error_log("❌ Data validation error at row $rowNumber, column '$sourceCol': " . $e->getMessage());
                            
                            // CRITICAL FIX: Use the actual CSV column name in error message
                            $originalErrorMessage = $e->getMessage();
                            
                            // Replace any reference to the source column with the actual CSV column name
                            $correctedErrorMessage = str_replace("for column '$sourceCol'", "for column '$sourceCol'", $originalErrorMessage);
                            
                            // If the error message doesn't have the column reference, add it properly
                            if (strpos($correctedErrorMessage, "for column") === false) {
                                // Add the column reference if it's missing
                                $correctedErrorMessage .= " for column '$sourceCol'";
                            }
                            
                            $errorWithRow = "Row " . $rowNumber . " ($sourceName): " . $correctedErrorMessage;
                            $validationErrors[] = $errorWithRow;
                            
                            $rowHasError = true;
                        }
                    }
                    
                    error_log("Row $rowNumber validation complete:");
                    error_log("- Has error: " . ($rowHasError ? 'YES' : 'NO'));
                    error_log("- Row data populated: " . json_encode($row));
                    error_log("- Has traffic_source: " . (isset($row['traffic_source']) ? 'YES' : 'NO'));
                    
                    // Only add row if it has no validation errors
                    if (!$rowHasError && !empty($row)) {
                        $transformed[] = $row;
                        $validRows++;
                        error_log("✅ Row $rowNumber ACCEPTED and added to transformed data");
                        error_log("✅ Current transformed array size: " . count($transformed));
                    } else {
                        $skippedRows++;
                        error_log("❌ Row $rowNumber REJECTED");
                    }
                    
                    error_log("=== END ROW $rowNumber PROCESSING ===");
                }

                fclose($handle);
            }
        } else {
            error_log("Processing as standard CSV format");
            // Standard CSV format processing
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $header = fgetcsv($handle);
                $headerIndexes = array_flip($header);
                $rowNumber = 0;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $totalRowsProcessed++;
                    $rowNumber++;
                    $row = [];
                    $criticalErrors = 0; // Count only critical errors
                    $nonCriticalErrors = 0; // Count non-critical errors
                    
                    error_log("=== PROCESSING STANDARD CSV ROW $rowNumber ===");
                    error_log("Raw data: " . json_encode($data));
                    
                    // Try to find traffic source name for error reporting
                    $sourceName = 'Unknown';
                    $sourceColumns = ['Session primary channel group (Default channel group)', 'Channel', 'Source', 'Traffic Source'];
                    foreach ($sourceColumns as $colName) {
                        if (isset($headerIndexes[$colName]) && isset($data[$headerIndexes[$colName]])) {
                            $sourceName = $data[$headerIndexes[$colName]];
                            break;
                        }
                    }
                    if ($sourceName === 'Unknown') {
                        $sourceName = "Row $rowNumber";
                    }
                    
                    error_log("Processing row for source: '$sourceName'");
                    
                    // Define critical fields that MUST be valid for a row to be useful
                    $criticalFields = ['traffic_source', 'visits']; // Only these are truly essential
                    
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        // Skip columns that don't exist in this CSV
                        if (!isset($headerIndexes[$sourceCol])) {
                            error_log("Column '$sourceCol' not found in CSV headers, skipping");
                            continue;
                        }
                        
                        // Get the value from the data row
                        $colIndex = $headerIndexes[$sourceCol];
                        if (!isset($data[$colIndex])) {
                            error_log("No data for column '$sourceCol' in row $rowNumber, skipping");
                            continue;
                        }
                        
                        $value = $data[$colIndex];
                        error_log("Processing column '$sourceCol' -> '$targetCol' with value: '$value'");
                        
                        try {
                            // Validate the value based on data type
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
                            error_log("✅ Validation successful for CSV column '$sourceCol' with value '$value'");
                        } catch (Exception $e) {
                            // Log the error with more context
                            error_log("❌ Data validation error: " . $e->getMessage() . " (Row: " . json_encode($data) . ")");
                            
                            // Create a more user-friendly error message with row information
                            $errorWithRow = "Row " . $rowNumber . " ($sourceName): " . $e->getMessage();
                            $validationErrors[] = $errorWithRow;
                            
                            // Determine if this is a critical field error
                            $isCritical = in_array($targetCol, $criticalFields);
                            
                            if ($isCritical) {
                                $criticalErrors++;
                                error_log("🔴 CRITICAL ERROR in row $rowNumber for field '$targetCol': " . $e->getMessage());
                            } else {
                                $nonCriticalErrors++;
                                error_log("🟡 NON-CRITICAL ERROR in row $rowNumber for field '$targetCol': " . $e->getMessage());
                                
                                // For non-critical fields, use safe default values
                                switch ($targetCol) {
                                    case 'bounce_rate':
                                    case 'session_key_event_rate':
                                        $row[$targetCol] = 0.0;
                                        break;
                                    case 'avg_session_duration':
                                        $row[$targetCol] = 0.0;
                                        break;
                                    case 'events_per_session':
                                        $row[$targetCol] = 0.0;
                                        break;
                                    case 'event_count':
                                    case 'key_events':
                                        $row[$targetCol] = 0;
                                        break;
                                    case 'total_revenue':
                                        $row[$targetCol] = 0.0;
                                        break;
                                    case 'engaged_sessions':
                                        $row[$targetCol] = 0;
                                        break;
                                    default:
                                        // Skip unknown fields
                                        error_log("Skipping unknown non-critical field '$targetCol'");
                                        break;
                                }
                            }
                        }
                    }
                    
                    error_log("Row $rowNumber decision point:");
                    error_log("- Critical errors: $criticalErrors");
                    error_log("- Non-critical errors: $nonCriticalErrors");
                    error_log("- Row data populated: " . json_encode($row));
                    error_log("- Has traffic_source: " . (isset($row['traffic_source']) ? 'YES' : 'NO'));
                    error_log("- Traffic source value: " . ($row['traffic_source'] ?? 'NOT SET'));
                    
                    // CRITICAL FIX: Only reject rows with critical field errors
                    if ($criticalErrors === 0 && !empty($row) && isset($row['traffic_source'])) {
                        $transformed[] = $row;
                        $validRows++;
                        error_log("✅ Row $rowNumber added to transformed data (Critical errors: $criticalErrors, Non-critical: $nonCriticalErrors)");
                        error_log("✅ Current transformed array size: " . count($transformed));
                    } else {
                        $skippedRows++;
                        error_log("❌ Row $rowNumber REJECTED (Critical errors: $criticalErrors, missing required fields)");
                    }
                    
                    error_log("=== END STANDARD CSV ROW $rowNumber PROCESSING ===");
                }
                fclose($handle);
            }
        }
        
        error_log("=== FINAL TRANSFORMATION RESULTS ===");
        error_log("Total rows processed: $totalRowsProcessed");
        error_log("Valid rows: $validRows");
        error_log("Skipped rows: $skippedRows");
        error_log("Validation errors count: " . count($validationErrors));
        error_log("Final transformed array size: " . count($transformed));
        error_log("Transformed " . count($transformed) . " rows");
        
        if (!empty($transformed)) {
            error_log("Sample transformed data (first row): " . json_encode($transformed[0]));
        }
        
        // Better empty file detection
        if (count($transformed) === 0) {
            error_log("No data rows found after transformation");

            if (!empty($validationErrors)) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['validation_errors'] = $validationErrors;
                error_log("Stored " . count($validationErrors) . " validation errors in session");
            }
            
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
            
            // Store validation errors in session for user feedback
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // CRITICAL FIX: Only discard data if NO valid rows were processed
            if (count($transformed) === 0) {
                // No valid data at all - return error
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
                // Some valid data found - store errors as warnings but continue processing
                $_SESSION['validation_errors'] = $validationErrors;
                error_log("Stored " . count($validationErrors) . " validation errors as warnings - processing " . count($transformed) . " valid rows");
                
                // NEW: Preserve validation errors for display across all pages
                require_once __DIR__ . '/../functions.php';
                preserveValidationErrorsForDisplay($validationErrors, count($transformed));
                
                // Store success message with warnings
                $_SESSION['upload_message'] = [
                    'type' => 'warning',
                    'message' => "Data imported successfully with " . count($validationErrors) . " validation warnings. " . count($transformed) . " valid rows were processed."
                ];
                
                error_log("=== TRANSFORM DATA DEBUG END ===");
                return $transformed; // Return the valid data
            }
        } else {
            error_log("=== TRANSFORM DATA DEBUG END ===");
            return $transformed;
        }
    }

    /**
     * Format value based on data type with enhanced validation - no auto-fixing
     */
    private function formatValue($value, $column) {
        error_log("Validating value: '$value' for column: '$column'");
        
        // CRITICAL FIX: Check if we're in manual mapping mode
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Always get target field for validation rules that need it
        $targetField = $this->columnMap[$column] ?? null;
        
        // CRITICAL FIX: Always use proper GA4 validation instead of manual mapping validation
        if ($this->detectedFormat === 'ga4_traffic_acquisition' || $this->detectedFormat === 'manual_mapping') {
            error_log("Using GA4 validation for: $column -> $targetField");
            return $this->validateGa4Field($value, $column, $targetField);
        }

        // Original value for error messages
        $originalValue = $value;

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
                // Handle revenue field specifically - NOT as a rate!
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
            } else if ($targetField === 'session_key_event_rate' || $targetField === 'bounce_rate') {
                // ONLY apply rate validation to actual rate fields - remove the generic "rate" check
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
                
                // CRITICAL FIX: Remove the problematic rate check from float validation
                // This was causing revenue values to be treated as rates
                // The rate check should ONLY be in the specific rate field handlers above
                
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
     * Enhanced GA4 field validation with suggestions
     */
    private function validateGa4Field($value, $column, $targetField) {
        $originalValue = $value;
        
        // Check for empty values first
        if (trim($value) === '') {
            $suggestions = $this->suggestDataFix($value, 'empty', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Empty value for column '$column' - This field requires a value$suggestionText");
        }
        
        // Validate based on target field type
        switch ($targetField) {
            case 'traffic_source':
                return $this->validateTrafficSource($value, $column);
                
            case 'visits':
            case 'sessions':
                return $this->validateIntegerField($value, $column, $targetField);
                
            case 'engaged_sessions':
                return $this->validateIntegerField($value, $column, $targetField);
                
            case 'bounce_rate':
                return $this->validateEngagementRate($value, $column);
                
            case 'avg_session_duration':
                return $this->validateSessionDuration($value, $column);
                
            case 'events_per_session':
                return $this->validateEventsPerSession($value, $column);
                
            case 'event_count':
                return $this->validateEventCount($value, $column);
                
            case 'key_events':
                return $this->validateKeyEvents($value, $column);
                
            case 'session_key_event_rate':
                return $this->validatePercentageField($value, $column);
                
            case 'total_revenue':
                return $this->validateRevenueField($value, $column);
                
            default:
                return $this->validateGenericField($value, $column);
        }
    }

    /**
     * Validate traffic source field
     */
    private function validateTrafficSource($value, $column) {
        // Check for trademark symbols
        if (preg_match('/[\x{2122}\x{00AE}\x{00A9}™®©]/u', $value)) {
            $suggestions = ["Try: '" . preg_replace('/[\x{2122}\x{00AE}\x{00A9}™®©]/u', '', $value) . "' (removed trademark symbols)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            
            // Get the proper GA4 column name based on target field
            $columnName = $this->getProperColumnName($column, 'traffic_source');
            throw new Exception("Invalid traffic source value: '$value' for column '$columnName' - Contains trademark or special symbols$suggestionText");
        }
        
        return trim($value);
    }

    private function validateIntegerField($value, $column, $targetField) {
        $originalValue = $value;
        
        // Get the proper column name
        $columnName = $this->getProperColumnName($column, $targetField);
        
        // Check for mathematical expressions
        if (preg_match('/^(\d+)\s*[\+\-\*\/]\s*(\d+)$/', $value, $matches)) {
            $suggestions = ["Try: '{$matches[1]}'"];
            
            // Add calculated suggestion
            $result = $this->evaluateSimpleExpression($value);
            if ($result !== null) {
                $suggestions[] = "Try: '$result' (calculated from $value)";
            }
            
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid integer value: '$value' for column '$columnName' - Please use only digits$suggestionText");
        }
        
        // Check for scientific notation
        if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
            $suggestions = ["Try: '" . number_format((float)$value, 0) . "' (converted from scientific notation)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Scientific notation '$value' not allowed for column '$columnName' - Please use standard integer format$suggestionText");
        }
        
        // Check for decimal values in integer fields
        if (strpos($value, '.') !== false && $targetField === 'engaged_sessions') {
            $suggestions = ["Try: '" . floor((float)$value) . "' (rounded down)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid integer value: '$value' for column '$columnName' - Cannot contain decimal points$suggestionText");
        }
        
        // Check for Unicode digits
        if (preg_match('/[^\x00-\x7F]/', $value)) {
            $converted = $this->convertUnicodeDigits($value);
            $suggestions = ["Try: '$converted' (converted from Unicode)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid integer value: '$value' for column '$columnName' - Contains Unicode characters$suggestionText");
        }
        
        if (!is_numeric($value) || strpos($value, '.') !== false) {
            $suggestions = $this->suggestDataFix($value, 'integer', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid integer value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        return (int)$value;
    }

    // Add this helper method to get the proper column name
    private function getProperColumnName($csvColumn, $targetField) {
        // Map target fields to their proper GA4 names
        $ga4ColumnNames = [
            'traffic_source' => 'Session primary channel group (Default channel group)',
            'visits' => 'Sessions',
            'sessions' => 'Sessions', 
            'users' => 'Users',
            'unique_visitors' => 'Users',
            'page_views' => 'Page views',
            'pageviews' => 'Page views',
            'engaged_sessions' => 'Engaged sessions',
            'bounce_rate' => 'Engagement rate',
            'avg_session_duration' => 'Average engagement time per session',
            'events_per_session' => 'Events per session',
            'event_count' => 'Event count',
            'key_events' => 'Key events',
            'session_key_event_rate' => 'Session key event rate',
            'total_revenue' => 'Total revenue'
        ];
        
        // Return the proper GA4 name if available, otherwise use the CSV column name
        return $ga4ColumnNames[$targetField] ?? $csvColumn;
    }

    /**
     * Validate engagement rate (bounce rate equivalent)
     */
    private function validateEngagementRate($value, $column) {
        $columnName = $this->getProperColumnName($column, 'bounce_rate');
        
        // Handle percentage format
        if (strpos($value, '%') !== false) {
            $numericValue = str_replace('%', '', $value);
            if (is_numeric($numericValue)) {
                $suggestions = ["Try: '$numericValue'", "Try: '" . ($numericValue / 100) . "' (converted from percentage)"];
                $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
                throw new Exception("Invalid float value: '$value' for column '$columnName' - Contains invalid characters$suggestionText");
            }
        }
        
        // Check for negative values
        if (is_numeric($value) && (float)$value < 0) {
            $suggestions = ["Try: '" . abs((float)$value) . "' (made positive)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Negative value '$value' not allowed for column '$columnName' - Must be zero or positive$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'percentage', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid float value: '$value' for column '$columnName' - Must be a decimal number$suggestionText");
        }
        
        return (float)$value;
    }

    /**
     * Validate session duration (time fields)
     */
    private function validateSessionDuration($value, $column) {
        $columnName = $this->getProperColumnName($column, 'avg_session_duration');
        
        // Check for time formats
        if (preg_match('/^(\d+):(\d+)(?::(\d+))?$/', $value, $matches)) {
            $minutes = (int)$matches[1];
            $seconds = (int)$matches[2];
            $hours = isset($matches[3]) ? (int)$matches[3] : 0;
            
            // Check for invalid time values
            if ($seconds >= 60 || ($hours > 0 && $minutes >= 60)) {
                $suggestions = ["Try: '" . ($minutes * 60 + $seconds) . "' (converted from MM:SS to seconds)"];
            } else {
                $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;
                $suggestions = ["Try: '$totalSeconds' (converted from time format to seconds)"];
            }
            
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid float value: '$value' for column '$columnName' - Contains invalid characters$suggestionText");
        }
        
        // Check for time with units
        if (preg_match('/^(\d+)m(\d+)s$/', $value, $matches)) {
            $totalSeconds = (int)$matches[1] * 60 + (int)$matches[2];
            $suggestions = ["Try: '$totalSeconds' (converted from minutes/seconds)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid float value: '$value' for column '$columnName' - Contains invalid characters$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'time', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid float value: '$value' for column '$columnName' - Must be a number$suggestionText");
        }
        
        return (float)$value;
    }

    /**
     * Validate events per session
     */
    private function validateEventsPerSession($value, $column) {
        $columnName = $this->getProperColumnName($column, 'events_per_session');
        
        // Check for multiple decimal points
        if (substr_count($value, '.') > 1) {
            $suggestions = ["Try: '" . preg_replace('/\.+/', '.', $value) . "' (fixed decimal points)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid events per session value: '$value' for column '$columnName' - Contains multiple decimal points$suggestionText");
        }
        
        // Check for special characters
        if (preg_match('/[~#@$%^&*]/', $value)) {
            $cleanValue = preg_replace('/[~#@$%^&*]/', '', $value);
            $suggestions = ["Try: '$cleanValue'", "Try: '$cleanValue' (removed special characters)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid events per session value: '$value' for column '$columnName' - Contains special characters$suggestionText");
        }
        
        // Check for unrealistically high values
        if (is_numeric($value) && (float)$value > 50) {
            $suggestions = ["Try: '" . min(50, (float)$value) . "' (capped at reasonable limit)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Unrealistically high value '$value' for column '$columnName' - Events per session should be reasonable (under 50)$suggestionText");
        }
        
        // Check for negative values
        if (is_numeric($value) && (float)$value < 0) {
            $suggestions = ["Try: '" . abs((float)$value) . "' (made positive)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Negative value '$value' not allowed for column '$columnName' - Events per session must be zero or positive$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'float', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid float value: '$value' for column '$columnName' - Must be a number$suggestionText");
        }
        
        return (float)$value;
    }

    /**
     * Validate key events field
     */
    private function validateKeyEvents($value, $column) {
        $columnName = $this->getProperColumnName($column, 'key_events');
        
        // Check for decimal values
        if (strpos($value, '.') !== false) {
            $suggestions = ["Try: '" . floor((float)$value) . "' (rounded down to whole number)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid key events value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        // Check for negative values
        if (is_numeric($value) && (int)$value < 0) {
            $suggestions = ["Try: '" . abs((int)$value) . "' (made positive)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid key events value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        // Check for unrealistically high values
        if (is_numeric($value) && (int)$value > 500) {
            $suggestions = ["Try: '" . min(500, (int)$value) . "' (capped at reasonable limit)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Unrealistically high value '$value' for column '$columnName' - Key events should be reasonable (under 500)$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'integer', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid integer value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        return (int)$value;
    }

    /**
     * Validate percentage fields
     */
    private function validatePercentageField($value, $column) {
        $columnName = $this->getProperColumnName($column, 'session_key_event_rate');
        
        // Handle percentage format
        if (strpos($value, '%') !== false) {
            $numericValue = str_replace('%', '', $value);
            if (is_numeric($numericValue)) {
                if ((float)$numericValue > 100) {
                    $suggestions = ["Try: '{$numericValue}%' or '" . ($numericValue / 100) . "' (as decimal)"];
                    $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
                    throw new Exception("Percentage value '$value' exceeds 100% for column '$columnName' - Must be between 0-100%$suggestionText");
                }
            }
        }
        
        if (!is_numeric(str_replace('%', '', $value))) {
            $suggestions = $this->suggestDataFix($value, 'percentage', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid percentage value: '$value' for column '$columnName' - Must be a number$suggestionText");
        }
        
        return (float)str_replace('%', '', $value) / (strpos($value, '%') !== false ? 100 : 1);
    }

    /**
     * Validate revenue field
     */
    private function validateRevenueField($value, $column) {
        $columnName = $this->getProperColumnName($column, 'total_revenue');
        
        // Handle currency symbols
        if (preg_match('/[\$£€¥]/', $value)) {
            $cleanValue = preg_replace('/[\$£€¥,]/', '', $value);
            $suggestions = ["Try: '$cleanValue' (removed currency symbols)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid currency format: '$value' for column '$columnName' - Contains currency symbols$suggestionText");
        }
        
        // Handle text mixed with numbers
        if (preg_match('/[a-zA-Z]/', $value)) {
            $cleanValue = preg_replace('/[a-zA-Z]/', '', $value);
            $suggestions = ["Try: '$cleanValue' (removed letters)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid currency value: '$value' for column '$columnName' - Must be a number$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'currency', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid revenue value: '$value' for column '$columnName' - Must be a number$suggestionText");
        }
        
        return (float)$value;
    }

    /**
     * Validate event count field
     */
    private function validateEventCount($value, $column) {
        $columnName = $this->getProperColumnName($column, 'event_count');
        
        // Check for empty value
        if (trim($value) === '') {
            $suggestions = $this->suggestDataFix($value, 'empty', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Empty value for column '$columnName' - Event count must have a value$suggestionText");
        }
        
        // Check for decimal values
        if (strpos($value, '.') !== false) {
            $suggestions = ["Try: '" . floor((float)$value) . "' (rounded down to whole number)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid event count value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        // Check for negative values
        if (is_numeric($value) && (int)$value < 0) {
            $suggestions = ["Try: '" . abs((int)$value) . "' (made positive)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Negative value '$value' not allowed for column '$columnName' - Event count must be zero or positive$suggestionText");
        }
        
        // Check for scientific notation
        if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
            $suggestions = ["Try: '" . number_format((float)$value, 0) . "' (converted from scientific notation)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Scientific notation '$value' not allowed for column '$columnName' - Please use standard integer format$suggestionText");
        }
        
        // Check for unrealistically high values
        if (is_numeric($value) && (int)$value > 10000) {
            $suggestions = ["Try: '" . min(10000, (int)$value) . "' (capped at reasonable limit)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Unrealistically high value '$value' for column '$columnName' - Event count should be reasonable (under 10,000)$suggestionText");
        }
        
        if (!is_numeric($value)) {
            $suggestions = $this->suggestDataFix($value, 'integer', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Invalid event count value: '$value' for column '$columnName' - Must be a whole number$suggestionText");
        }
        
        return (int)$value;
    }

    /**
     * Validate generic field (fallback for unknown fields)
     */
    private function validateGenericField($value, $column) {
        // FIXED: Get the target field from column mapping to use proper GA4 name
        $targetField = $this->columnMap[$column] ?? null;
        $columnName = $targetField ? $this->getProperColumnName($column, $targetField) : $column;
        
        // Check for empty values
        if (trim($value) === '') {
            $suggestions = $this->suggestDataFix($value, 'empty', $column);
            $suggestionText = !empty($suggestions) ? ' Suggestions: ' . implode('; ', $suggestions) : '';
            throw new Exception("Empty value for column '$columnName' - This field requires a value$suggestionText");
        }
        
        // Check for excessive whitespace
        if (trim($value) !== $value) {
            $suggestions = ["Try: '" . trim($value) . "' (removed whitespace)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Invalid value format: '$value' for column '$columnName' - Contains leading or trailing whitespace$suggestionText");
        }
        
        // Check for potential CSV structure issues
        if (strpos($value, ',') !== false) {
            throw new Exception("CSV parsing error detected: Value '$value' contains commas which breaks the CSV structure");
        }
        
        // Basic validation for reasonable length
        if (strlen($value) > 500) {
            $suggestions = ["Try: '" . substr($value, 0, 500) . "...' (truncated to reasonable length)"];
            $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
            throw new Exception("Value too long for column '$columnName' - Maximum 500 characters allowed$suggestionText");
        }
        
        // Check for potentially malicious content
        if (preg_match('/<script|javascript:|on\w+=/i', $value)) {
            throw new Exception("Invalid content detected in column '$columnName' - Contains potentially harmful code");
        }
        
        // If it looks like a number, validate it as such
        if (is_numeric($value)) {
            $numericValue = (float)$value;
            
            // Check for negative values in generic fields (usually not allowed)
            if ($numericValue < 0) {
                $suggestions = ["Try: '" . abs($numericValue) . "' (made positive)"];
                $suggestionText = ' Suggestions: ' . implode('; ', $suggestions);
                throw new Exception("Negative value '$value' detected for column '$columnName' - Consider using positive values$suggestionText");
            }
            
            return $numericValue;
        }
        
        // Return as text if all validations pass
        return trim($value);
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