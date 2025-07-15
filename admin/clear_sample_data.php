<?php

// Name: Khong Chee Leong
// Position: Developer
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: clear_sample_data.php
// Description: Admin utility to clear sample data from the database and file system
//              with transaction support for data integrity and error handling.
// First Written On: 20 April 2025
// Edited On: 14 July 2025

require_once '../auth/admin_auth.php';
require_once '../config.php';

$response = [
    'success' => false,
    'message' => ''
];

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction to ensure data integrity
        $conn->begin_transaction();
        
        // Get all sample upload IDs first
        $sampleUploadsStmt = $conn->prepare("SELECT UploadID, FileName FROM csv_upload WHERE IsSampleData = 1");
        $sampleUploadsStmt->execute();
        $sampleUploads = $sampleUploadsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($sampleUploads)) {
            $response['success'] = true;
            $response['message'] = 'No sample data found to clear.';
        } else {
            $uploadIds = array_column($sampleUploads, 'UploadID');
            $uploadIdPlaceholders = str_repeat('?,', count($uploadIds) - 1) . '?';
            
            error_log("Found " . count($uploadIds) . " sample uploads to delete: " . implode(', ', $uploadIds));
            
            // 1. Delete annotations first (child table)
            if (!empty($uploadIds)) {
                $deleteAnnotationsStmt = $conn->prepare("DELETE FROM annotation WHERE UploadID IN ($uploadIdPlaceholders)");
                $deleteAnnotationsStmt->bind_param(str_repeat('i', count($uploadIds)), ...$uploadIds);
                $deleteAnnotationsStmt->execute();
                $deletedAnnotations = $deleteAnnotationsStmt->affected_rows;
                error_log("Deleted $deletedAnnotations annotations for sample uploads");
            }
            
            // 2. Delete processed data points (child table)
            if (!empty($uploadIds)) {
                $deleteDataPointsStmt = $conn->prepare("DELETE FROM processed_data_point WHERE UploadID IN ($uploadIdPlaceholders)");
                $deleteDataPointsStmt->bind_param(str_repeat('i', count($uploadIds)), ...$uploadIds);
                $deleteDataPointsStmt->execute();
                $deletedDataPoints = $deleteDataPointsStmt->affected_rows;
                error_log("Deleted $deletedDataPoints data points for sample uploads");
            }
            
            // 3. Delete comparison file links (child table)
            if (!empty($uploadIds)) {
                $deleteComparisonLinksStmt = $conn->prepare("DELETE FROM comparison_file_link WHERE UploadID IN ($uploadIdPlaceholders)");
                $deleteComparisonLinksStmt->bind_param(str_repeat('i', count($uploadIds)), ...$uploadIds);
                $deleteComparisonLinksStmt->execute();
                $deletedComparisonLinks = $deleteComparisonLinksStmt->affected_rows;
                error_log("Deleted $deletedComparisonLinks comparison file links for sample uploads");
            }
            
            // 4. Finally, delete CSV uploads (parent table)
            $deleteCsvStmt = $conn->prepare("DELETE FROM csv_upload WHERE IsSampleData = 1");
            $deleteCsvStmt->execute();
            $deletedUploads = $deleteCsvStmt->affected_rows;
            error_log("Deleted $deletedUploads sample CSV upload records");
            
            // 5. Delete physical CSV files from uploads directory
            $deletedFiles = 0;
            foreach ($sampleUploads as $upload) {
                $filePath = __DIR__ . '/../uploads/' . $upload['FileName'];
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $deletedFiles++;
                        error_log("Deleted file: " . $upload['FileName']);
                    } else {
                        error_log("Failed to delete file: " . $upload['FileName']);
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = "Successfully cleared all sample data. Removed $deletedUploads upload records, $deletedDataPoints data points, $deletedAnnotations annotations, $deletedComparisonLinks comparison links, and $deletedFiles files.";
            
            // Clear session sample data if any user is currently using it
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Clear sample data session variables
            if (isset($_SESSION['using_sample_data'])) {
                unset($_SESSION['using_sample_data']);
                unset($_SESSION['sample_upload_id']);
                error_log("Cleared sample data session variables");
            }
            
            error_log("Sample data clearing completed successfully");
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error clearing sample data: " . $e->getMessage());
        $response['message'] = 'Error clearing sample data: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return response
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $_SESSION['sample_clear_message'] = $response;
    header('Location: upload_sample_data.php');
    exit;
}
?>