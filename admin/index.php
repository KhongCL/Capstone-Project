<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: index.php
// Description: Admin dashboard main page displaying system statistics, user management,
//              CSV upload analytics, and quick access to administrative functions.
// First Written On:  April 2025
// Edited On: 14 July 2025

require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../functions.php';

// Get summary statistics
$userStats = getUserStats($conn);
$csvStats = getCsvStats($conn);
$exportStats = getExportStats($conn);
$supportedFormats = getSupportedFormats();

// Helper functions to get statistics
function getUserStats($conn) {
    $stats = [
        'total' => 0,
        'active' => 0,
        'suspended' => 0,
        'admins' => 0,
        'users' => 0
    ];
    
    // Get actual user counts, not group counts
    $sql = "SELECT Role, AccountStatus, COUNT(*) as count FROM user GROUP BY Role, AccountStatus";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $count = $row['count'];
            $stats['total'] += $count;
            
            if ($row['AccountStatus'] == 'Active') {
                $stats['active'] += $count;
            } else {
                $stats['suspended'] += $count;
            }
            
            if ($row['Role'] == 'Admin') {
                $stats['admins'] += $count;
            } else {
                $stats['users'] += $count;
            }
        }
    }
    
    return $stats;
}

function getCsvStats($conn) {
    $stats = [
        'total_uploads' => 0,
        'validated' => 0,
        'recent' => []
    ];
    
    // Get total uploads
    $sql = "SELECT COUNT(*) as count FROM csv_upload";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_uploads'] = $row['count'];
    }
    
    // Get validated uploads
    $sql = "SELECT COUNT(*) as count FROM csv_upload WHERE IsValidated = 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['validated'] = $row['count'];
    }
    
    // Get most recent uploads
    $sql = "SELECT cu.UploadID, cu.FileName, cu.UploadDate, cu.ReportType, u.Username 
            FROM csv_upload cu 
            JOIN user u ON cu.UserID = u.UserID 
            ORDER BY cu.UploadDate DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['recent'][] = $row;
        }
    }
    
    return $stats;
}

function getExportStats($conn) {
    $stats = [
        'total_exports' => 0,
        'pdf_exports' => 0,
        'csv_exports' => 0,
        'recent' => []
    ];
    
    // Get total exports
    $sql = "SELECT COUNT(*) as count FROM export_history";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_exports'] = $row['count'];
    }
    
    // Get PDF exports
    $sql = "SELECT COUNT(*) as count FROM export_history WHERE ExportType = 'PDF'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['pdf_exports'] = $row['count'];
    }
    
    // Get CSV exports
    $sql = "SELECT COUNT(*) as count FROM export_history WHERE ExportType = 'CSV'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['csv_exports'] = $row['count'];
    }
    
    // Get most recent exports
    $sql = "SELECT eh.ExportID, eh.ExportType, eh.ExportTimestamp, eh.ExportedDataDescription, u.Username 
            FROM export_history eh 
            JOIN user u ON eh.UserID = u.UserID 
            ORDER BY eh.ExportTimestamp DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['recent'][] = $row;
        }
    }
    
    return $stats;
}

function getSupportedFormats() {
    $supportedFormats = [];
    
    // Read from CSV mappings configuration file
    $mappingsFile = __DIR__ . '/../config/csv_mappings.json';
    if (file_exists($mappingsFile)) {
        $mappings = json_decode(file_get_contents($mappingsFile), true);
        if ($mappings && is_array($mappings)) {
            $supportedFormats = array_keys($mappings);
        }
    }
    
    return $supportedFormats;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TrafAnalyz</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'admin_header.php'; ?>

        <main>
            <?php if (isset($_SESSION['sample_clear_message'])): ?>
                <div class="message <?php echo $_SESSION['sample_clear_message']['success'] ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['sample_clear_message']['message']; ?>
                </div>
                <?php unset($_SESSION['sample_clear_message']); ?>
            <?php endif; ?>
            
            <section class="welcome-banner">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Here's an overview of your TrafAnalyz system status.</p>
            </section>

            <section class="stats-row">
                <div class="stat-card">
                    <i class="fas fa-users icon"></i>
                    <div class="label">Total Users</div>
                    <div class="value"><?php echo $userStats['total']; ?></div>
                    <div class="trend"><?php echo $userStats['active']; ?> active, <?php echo $userStats['suspended']; ?> suspended</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-shield icon"></i>
                    <div class="label">Admin Users</div>
                    <div class="value"><?php echo $userStats['admins']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-csv icon"></i>
                    <div class="label">CSV Uploads</div>
                    <div class="value"><?php echo $csvStats['total_uploads']; ?></div>
                    <div class="trend"><?php echo $csvStats['validated']; ?> validated</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-download icon"></i>
                    <div class="label">Total Exports</div>
                    <div class="value"><?php echo $exportStats['total_exports']; ?></div>
                    <div class="trend"><?php echo $exportStats['pdf_exports']; ?> PDF, <?php echo $exportStats['csv_exports']; ?> CSV</div>
                </div>
            </section>

            <section class="admin-section">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="quick-links">
                    <a href="admin_users.php" class="quick-link">
                        <i class="fas fa-users"></i>
                        <h4>User Management</h4>
                        <p>View, suspend, restore or delete user accounts</p>
                        <span class="link-action">Manage Users &rarr;</span>
                    </a>
                    <a href="admin_mappings.php" class="quick-link">
                        <i class="fas fa-table"></i>
                        <h4>CSV Mappings</h4>
                        <p>Configure CSV format mappings for data import</p>
                        <span class="link-action">Configure Mappings &rarr;</span>
                    </a>
                    <a href="export_users_pdf.php" target="_blank" class="quick-link">
                        <i class="fas fa-file-pdf"></i>
                        <h4>Export Users Report</h4>
                        <p>Generate a PDF report with user account details</p>
                        <span class="link-action">Export PDF &rarr;</span>
                    </a>
                    <a href="upload_sample_data.php" class="quick-link">
                        <i class="fas fa-upload"></i>
                        <h4>Sample Data</h4>
                        <p>Upload sample CSV files for testing</p>
                        <span class="link-action">Upload Sample &rarr;</span>
                    </a>
                </div>
            </section>

            <section class="admin-section" id="recent-uploads">
                <h3><i class="fas fa-clock"></i> Recent Uploads</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>User</th>
                            <th>Report Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($csvStats['recent']) > 0): ?>
                            <?php foreach ($csvStats['recent'] as $upload): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($upload['FileName']); ?></td>
                                <td><?php echo htmlspecialchars($upload['Username']); ?></td>
                                <td><?php echo htmlspecialchars($upload['ReportType']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($upload['UploadDate'])); ?></td>
                                <td>
                                    <span class="badge badge-success">Processed</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No recent uploads found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="admin-section" id="recent-exports">
                <h3><i class="fas fa-download"></i> Recent Exports</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>User</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($exportStats['recent']) > 0): ?>
                            <?php foreach ($exportStats['recent'] as $export): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($export['ExportType']); ?>">
                                        <?php echo htmlspecialchars($export['ExportType']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($export['Username']); ?></td>
                                <td>
                                    <?php 
                                    $description = $export['ExportedDataDescription'];
                                    echo htmlspecialchars(strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description); 
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($export['ExportTimestamp'])); ?></td>
                                <td>
                                    <span class="badge badge-success">Completed</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No recent exports found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="admin-section" id="supported-formats">
                <h3><i class="fas fa-file-alt"></i> Supported CSV Formats</h3>
                <div class="report-types">
                    <?php if (count($supportedFormats) > 0): ?>
                        <?php foreach ($supportedFormats as $format): ?>
                            <span class="report-type"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $format))); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No CSV formats configured yet. Configure the CSV mappings to add supported formats.</p>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="admin_mappings.php" class="btn btn-secondary">Manage CSV Formats</a>
                </div>
            </section>
            
            <section class="admin-section">
                <h3><i class="fas fa-shield-alt"></i> Admin Actions</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="../admin_register.php?key=trafanalyz" class="btn">Register New Admin</a>
                    <a href="export_users_pdf.php" target="_blank" class="btn">Export User Report</a>
                    <button id="clearSampleDataBtn" class="btn btn-danger">Clear Sample Data</button>
                </div>
            </section>
        </main>
        
        <?php include 'admin_footer.php'; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clearSampleDataBtn = document.getElementById('clearSampleDataBtn');
            
            clearSampleDataBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all sample data? This action cannot be undone.')) {
                    fetch('clear_sample_data.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while clearing sample data.');
                    });
                }
            });
        });
    </script>
</body>
</html>