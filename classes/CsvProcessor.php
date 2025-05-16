<?php
class CsvProcessor {
    private $mappingsFile = 'config/csv_mappings.json';
    private $mappings;
    private $detectedFormat = null;
    private $columnMap = [];
    private $csvData = [];
    
    public function __construct() {
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
            
            // Check first 15 lines for required GA4 metadata patterns
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
            'Medium', 'Channel', 'Events', 'Users', 'Revenue', 'Visit'
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
                    return [
                        'status' => 'success',
                        'format' => $formatKey,
                        'header' => $header,
                        'mapping' => $format['column_mappings'],
                        'data_types' => $format['data_types'],
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
        if ($format) {
            $this->detectedFormat = $format;
        }
    
        error_log("Transform data using format: " . ($this->detectedFormat ?? "No format detected"));
        $transformed = [];
        $validationErrors = [];
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

                $validRows = 0;
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
                    
                    // Map each column according to our defined structure
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        if (isset($headerIndexes[$sourceCol]) && isset($data[$headerIndexes[$sourceCol]])) {
                            try {
                                $value = $data[$headerIndexes[$sourceCol]];
                                $row[$targetCol] = $this->formatValue($value, $sourceCol);
                            } catch (Exception $e) {
                                // Log the error with more context about which row had the issue
                                error_log("Data validation error at row $rowNumber: " . $e->getMessage());
                                
                                // Create a more user-friendly error message with row information
                                $errorWithRow = "Row " . $rowNumber . " (" . $data[$headerIndexes['Session primary channel group (Default channel group)']] . "): " . $e->getMessage();
                                $validationErrors[] = $errorWithRow;
                                
                                $rowHasError = true;
                            }
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
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row = [];
                    
                foreach ($this->columnMap as $sourceCol => $targetCol) {
                    if (isset($headerIndexes[$sourceCol]) && isset($data[$headerIndexes[$sourceCol]])) {
                        try {
                            $value = $data[$headerIndexes[$sourceCol]];
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
                        } catch (Exception $e) {
                            // Log the error with more context
                            error_log("Data validation error: " . $e->getMessage() . " (Row: " . json_encode($data) . ")");
                            // Add to validation errors collection
                            $validationErrors[] = $e->getMessage();
                        }
                    }
                }
                    
                    if (!empty($row)) {
                        $transformed[] = $row;
                    }
                }
                fclose($handle);
            }
        }
        
        error_log("Transformed " . count($transformed) . " rows");
        
        // ADD THIS: Better empty file detection
        if (count($transformed) === 0) {
            error_log("No data rows found after transformation");
            return [];
        }
        
        if (count($transformed) > 0) {
            error_log("First transformed row: " . json_encode($transformed[0]));
        }

        error_log("Validated $rowNumber rows, found " . count($validationErrors) . " errors, $validRows rows valid");

        if (!empty($validationErrors)) {
        error_log("Validation errors found: " . implode("; ", $validationErrors));
        
        // Store error message in session with all errors, not just first 3
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
        
        if (!isset($this->mappings[$this->detectedFormat]['data_types'][$column])) {
            error_log("No data type defined for column '$column' in format: " . $this->detectedFormat);
            return $value;
        }
        
        $type = $this->mappings[$this->detectedFormat]['data_types'][$column];
        error_log("Validating as type: $type");
        
        // Skip validation for empty values
        if (trim($value) === '') {
            error_log("Empty value, returning default for type $type");
            return ($type === 'integer' || $type === 'float') ? 0 : $value;
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
                    if (!preg_match('/^(0|0\.\d+|1(\.0+)?)$/', $value)) {
                        throw new Exception("Invalid percentage value: '$value' for column '$column' - Value should be between 0-1 or include % sign");
                    }
                    return (float) $value;
                }
                
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