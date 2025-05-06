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
                // Skip metadata lines (lines starting with #)
                if (substr(trim($line), 0, 1) === '#') {
                    $metadataLines[] = $line;
                    continue;
                }
                
                // First non-metadata line is the header
                if ($headerLine === null) {
                    $headerLine = $line;
                    continue;
                }
                
                // All other non-metadata lines are data
                $dataLines[] = $line;
            }
            fclose($handle);
        }
        
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
    public function transformData($filePath, $columnMapping) {
        $this->columnMap = $columnMapping;
        $transformed = [];
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            $headerIndexes = array_flip($header);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
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
}
?>