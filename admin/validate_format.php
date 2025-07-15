<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: validate_format.php
// Description: Admin CSV format validation API that validates format names, detection columns,
//              and target fields for CSV mapping configuration with duplicate checking.
// First Written On: 20 April 2025
// Edited On: 29 June 2025

require_once '../auth/admin_auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formatName = $_POST['format_name'] ?? '';
    $detectionColumns = $_POST['detection_columns'] ?? '';
    $targetFields = $_POST['target_fields'] ?? ''; // Add this parameter
    $isNew = $_POST['is_new'] ?? 'false';
    
    $response = ['valid' => true, 'errors' => []];
    
    // Validate format name
    if (empty(trim($formatName))) {
        $response['valid'] = false;
        $response['errors'][] = 'Format name is required';
    } else {
        // Check for spaces
        if (strpos($formatName, ' ') !== false) {
            $response['valid'] = false;
            $response['errors'][] = 'Format name cannot contain spaces. Use underscores instead.';
        }
        
        // Check for invalid characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $formatName)) {
            $response['valid'] = false;
            $response['errors'][] = 'Format name can only contain letters, numbers, and underscores';
        }
        
        // Check length
        if (strlen($formatName) < 3) {
            $response['valid'] = false;
            $response['errors'][] = 'Format name must be at least 3 characters long';
        }
    }
    
    // Validate detection columns
    if (empty(trim($detectionColumns))) {
        $response['valid'] = false;
        $response['errors'][] = 'Detection columns are required';
    } else {
        $columns = array_filter(array_map('trim', explode(',', $detectionColumns)));
        if (count($columns) < 2) {
            $response['valid'] = false;
            $response['errors'][] = 'At least 2 detection columns are required';
        }
        
        // Check for empty column names
        foreach ($columns as $column) {
            if (empty($column)) {
                $response['valid'] = false;
                $response['errors'][] = 'Detection columns cannot be empty';
                break;
            }
        }
    }
    
    // Validate target fields for duplicates
    if (!empty($targetFields)) {
        $targets = array_filter(array_map('trim', explode(',', $targetFields)));
        $duplicates = array_diff_assoc($targets, array_unique($targets));
        
        if (!empty($duplicates)) {
            $response['valid'] = false;
            $response['errors'][] = 'Duplicate target fields detected: ' . implode(', ', array_unique($duplicates)) . '. Each target field can only be used once per format.';
        }
    }
    
    // Check if format name already exists (only for new formats)
    if ($isNew === 'true' && !empty(trim($formatName))) {
        $mappingsFile = __DIR__ . '/../config/csv_mappings.json';
        if (file_exists($mappingsFile)) {
            $existingMappings = json_decode(file_get_contents($mappingsFile), true);
            $formatKey = strtolower(str_replace(' ', '_', $formatName));
            
            if (isset($existingMappings[$formatKey])) {
                $response['valid'] = false;
                $response['errors'][] = 'A format with this name already exists';
            }
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['valid' => false, 'errors' => ['Invalid request method']]);
}
?>