<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: migrate_mappings.php
// Description: Database migration script that transfers existing JSON CSV mappings
//              to database format with transaction support and error handling.
// First Written On: 20 April 2025
// Edited On: 14 July 2025

require_once '../auth/admin_auth.php';
require_once '../config.php';

// This script migrates existing JSON mappings to database format
$mappingsFile = __DIR__ . '/../config/csv_mappings.json';

if (!file_exists($mappingsFile)) {
    die('Mappings file not found');
}

$mappings = json_decode(file_get_contents($mappingsFile), true);

try {
    $conn->begin_transaction();
    
    // Clear existing data
    $conn->query("DELETE FROM column_mapping");
    $conn->query("DELETE FROM csv_format");
    $conn->query("ALTER TABLE csv_format AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE column_mapping AUTO_INCREMENT = 1");
    
    $formatId = 1;
    foreach ($mappings as $formatKey => $format) {
        // Insert CSV format
        $stmt = $conn->prepare("INSERT INTO csv_format (FormatID, FormatName, ReportType, AdminUserID, CreatedAt, LastModifiedDate) VALUES (?, ?, ?, 1, NOW(), NOW())");
        $formatName = ucfirst(str_replace('_', ' ', $formatKey));
        $reportType = implode(', ', $format['format_detection']);
        $stmt->bind_param("iss", $formatId, $formatName, $reportType);
        $stmt->execute();
        
        // Insert column mappings
        foreach ($format['column_mappings'] as $csvCol => $systemField) {
            $stmt = $conn->prepare("INSERT INTO column_mapping (FormatID, CSVColumnName, SystemFieldName) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $formatId, $csvCol, $systemField);
            $stmt->execute();
        }
        
        $formatId++;
    }
    
    $conn->commit();
    echo "Migration completed successfully!\n";
    echo "Migrated " . count($mappings) . " formats.\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>