<?php
// filepath: c:\xampp\htdocs\Capstone-Project\admin\clear_sample_data.php
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
    
    // Delete from PROCESSED_DATA_POINT for all sample uploads
    $uploadIdsList = implode(',', $uploadIds);
    $sql = "DELETE FROM PROCESSED_DATA_POINT WHERE UploadID IN ($uploadIdsList)";
    if (!$conn->query($sql)) {
        throw new Exception("Error clearing sample data points: " . $conn->error);
    }
    
    // Delete from CSV_UPLOAD
    $sql = "DELETE FROM csv_upload WHERE IsSampleData = 1";
    if (!$conn->query($sql)) {
        throw new Exception("Error clearing sample uploads: " . $conn->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Sample data cleared successfully.";
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $response['message'] = "Error: " . $e->getMessage();
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