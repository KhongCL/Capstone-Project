<?php
require_once '../auth/admin_auth.php';
require_once '../config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

// Get the sample file information
$stmt = $conn->prepare("SELECT FileName, AccountName, PropertyName FROM csv_upload WHERE IsSampleData = 1 ORDER BY UploadDate DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !($row = $result->fetch_assoc())) {
    header("Location: upload_sample_data.php?error=no_sample_data");
    exit();
}

$fileName = $row['FileName'];
$accountName = $row['AccountName'];
$propertyName = $row['PropertyName'];
$filePath = __DIR__ . '/../uploads/' . $fileName;

// Check if file exists
if (!file_exists($filePath)) {
    error_log("Sample CSV file not found for admin download: $filePath");
    header("Location: upload_sample_data.php?error=file_not_found");
    exit();
}

// Set headers for file download
$downloadName = 'TrafAnalyz_Sample_Data_Admin_' . date('Y-m-d') . '.csv';
$fileSize = filesize($filePath);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output file contents
readfile($filePath);

// Log the download
error_log("Sample CSV downloaded by admin " . $_SESSION['user_id'] . ": $fileName");
exit();
?>