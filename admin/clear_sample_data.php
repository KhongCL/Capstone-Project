<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';

// Check if request is AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // First, get all sample upload IDs
    $uploadIds = [];
    $sql = "SELECT UploadID FROM csv_upload WHERE IsSampleData = 1";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $uploadIds[] = $row['UploadID'];
        }
    }
    
    if (empty($uploadIds)) {
        throw new Exception("No sample data found to clear.");
    }

    // Get all sample file names before deleting
    $stmt = $conn->prepare("SELECT FileName FROM csv_upload WHERE IsSampleData = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $sampleFiles = [];
    while ($row = $result->fetch_assoc()) {
        $sampleFiles[] = $row['FileName'];
    }
    
    // Delete from PROCESSED_DATA_POINT for all sample uploads (safer version)
    if (!empty($uploadIds)) {
        // Create placeholders for prepared statement
        $placeholders = str_repeat('?,', count($uploadIds) - 1) . '?';
        $sql = "DELETE FROM PROCESSED_DATA_POINT WHERE UploadID IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparing data points deletion: " . $conn->error);
        }
        
        // Create types string (all integers)
        $types = str_repeat('i', count($uploadIds));
        $stmt->bind_param($types, ...$uploadIds);
        
        if (!$stmt->execute()) {
            throw new Exception("Error clearing sample data points: " . $stmt->error);
        }
        
        error_log("Deleted data points for " . count($uploadIds) . " sample uploads");
    }
    
    // Delete from CSV_UPLOAD
    $sql = "DELETE FROM csv_upload WHERE IsSampleData = 1";
    if (!$conn->query($sql)) {
        throw new Exception("Error clearing sample uploads: " . $conn->error);
    }

    // Clean up the physical files
    $uploadDir = __DIR__ . '/../uploads/';
    $filesDeleted = 0;
    foreach ($sampleFiles as $fileName) {
        $filePath = $uploadDir . $fileName;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $filesDeleted++;
                error_log("Deleted sample file: " . $filePath);
            } else {
                error_log("Failed to delete sample file: " . $filePath);
            }
        }
    }

    error_log("Deleted $filesDeleted sample files");
    
    // Clear any active sample data sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear sample data session variables if they exist
    if (isset($_SESSION['using_sample_data'])) {
        unset($_SESSION['using_sample_data']);
        error_log("Cleared using_sample_data session variable");
    }
    
    if (isset($_SESSION['sample_upload_id'])) {
        unset($_SESSION['sample_upload_id']);
        error_log("Cleared sample_upload_id session variable");
    }
    
    // If user was viewing sample data, restore their latest upload
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT UploadID FROM csv_upload WHERE UserID = ? AND (IsSampleData = 0 OR IsSampleData IS NULL) ORDER BY UploadDate DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $_SESSION['latest_upload_id'] = $row['UploadID'];
            error_log("Restored user upload ID: " . $row['UploadID']);
        } else {
            unset($_SESSION['latest_upload_id']);
            error_log("No user uploads found, cleared latest_upload_id");
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Sample data cleared successfully. Deleted $filesDeleted files from disk.";
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $response['message'] = "Error: " . $e->getMessage();
    error_log("Error clearing sample data: " . $e->getMessage());
}

// Return JSON response for AJAX requests, otherwise redirect
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $_SESSION['sample_clear_message'] = $response;
    header('Location: index.php');
}
exit;