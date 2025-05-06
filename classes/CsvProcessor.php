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
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Process the file line by line
            while (($line = fgets($handle)) !== FALSE) {
                $line = trim($line);
                // Skip empty lines
                if (empty($line)) continue;
                
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
        
        // Try to detect format
        if (count($header) > 0) {
            error_log("Checking format for header: " . implode(", ", $header));
            
            // Check if this matches any known format
            foreach ($this->mappings as $formatKey => $format) {
                $matched = true;
                foreach ($format['format_detection'] as $column) {
                    if (!in_array($column, $header)) {
                        $matched = false;
                        break;
                    }
                }
                
                if ($matched) {
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
        
        // If we got here, format not recognized
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
    // Update transformData method
    public function transformData($filePath, $columnMapping) {
        $this->columnMap = $columnMapping;
        $transformed = [];
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
                
                // Process data rows
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) < count($header)) continue; // Skip invalid rows
                    
                    $row = [];
                    
                    // Map each column according to our defined structure
                    foreach ($this->columnMap as $sourceCol => $targetCol) {
                        if (isset($headerIndexes[$sourceCol]) && isset($data[$headerIndexes[$sourceCol]])) {
                            $value = $data[$headerIndexes[$sourceCol]];
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
                        }
                    }
                    
                    if (!empty($row)) {
                        $transformed[] = $row;
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
                            $value = $data[$headerIndexes[$sourceCol]];
                            $row[$targetCol] = $this->formatValue($value, $sourceCol);
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
        if (count($transformed) > 0) {
            error_log("First transformed row: " . json_encode($transformed[0]));
        }
        
        return $transformed;
    }
    
    /**
     * Format value based on data type
     */
    private function formatValue($value, $column) {
        if (!$this->detectedFormat || !isset($this->mappings[$this->detectedFormat]['data_types'][$column])) {
            return $value; // Return as is if type unknown
        }
        
        $type = $this->mappings[$this->detectedFormat]['data_types'][$column];
        
        switch ($type) {
            case 'integer':
                return (int) preg_replace('/[^0-9]/', '', $value);
                
            case 'float':
                return (float) preg_replace('/[^0-9.]/', '', $value);
                
            case 'percentage':
                return (float) preg_replace('/[^0-9.]/', '', $value) / 100;
                
            case 'time':
                // Convert various time formats to seconds
                if (strpos($value, ':') !== false) {
                    // Format: MM:SS or HH:MM:SS
                    $parts = array_map('intval', explode(':', $value));
                    if (count($parts) == 2) {
                        return $parts[0] * 60 + $parts[1];
                    } elseif (count($parts) == 3) {
                        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
                    }
                }
                return (int) $value;
                
            default:
                return $value;
        }
    }

    // Add this method to the CsvProcessor class

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
                $metadata['account_name'] = trim(str_replace('# Account:', '', $line));
                error_log("Found account name: " . $metadata['account_name']);
            }
            
            if (strpos($line, 'Property:') !== false) {
                $metadata['property_name'] = trim(str_replace('# Property:', '', $line));
                error_log("Found property name: " . $metadata['property_name']);
            }
            
            // Extract report type
            if (strpos($line, 'Traffic acquisition:') !== false) {
                $metadata['report_type'] = trim(str_replace('# Traffic acquisition:', '', $line));
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