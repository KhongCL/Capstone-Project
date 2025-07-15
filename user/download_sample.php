<?php

// Name: Justin Yong Cheng Xun
// Position: Developer
// TP Number: TP077360
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: download_sample.php
// Description: End-user sample data download utility that allows users to download
//              sample CSV files they are currently viewing with secure validation.
// First Written On: 14 April 2025
// Edited On: 3 July 2025

require_once '../auth/user_auth.php';
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is currently viewing sample data
if (!isset($_SESSION['using_sample_data']) || $_SESSION['using_sample_data'] !== true) {
    header("Location: index.php");
    exit();
}

// Get the sample file information
$sampleUploadId = $_SESSION['sample_upload_id'] ?? null;
if (!$sampleUploadId) {
    header("Location: index.php");
    exit();
}

// Get the file path from the database
$stmt = $conn->prepare("SELECT FileName, AccountName, PropertyName FROM csv_upload WHERE UploadID = ? AND IsSampleData = 1");
$stmt->bind_param("i", $sampleUploadId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !($row = $result->fetch_assoc())) {
    header("Location: index.php");
    exit();
}

$fileName = $row['FileName'];
$accountName = $row['AccountName'];
$propertyName = $row['PropertyName'];
$filePath = __DIR__ . '/../uploads/' . $fileName;

// Check if file exists
if (!file_exists($filePath)) {
    error_log("Sample CSV file not found: $filePath");
    header("Location: index.php?error=file_not_found");
    exit();
}

// Set headers for file download
$downloadName = 'TrafAnalyz_Sample_Data_' . date('Y-m-d') . '.csv';
$fileSize = filesize($filePath);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output file contents
readfile($filePath);

// Log the download
error_log("Sample CSV downloaded by user " . $_SESSION['user_id'] . ": $fileName");
exit();
?>