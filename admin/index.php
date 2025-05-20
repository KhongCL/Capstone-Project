<?php
// filepath: c:\xampp\htdocs\Capstone-Project\admin\dashboard.php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../admin_login.php?key=trafanalyz");
    exit;
}

// Check if user is admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../user/");
    exit;
}

// Get summary statistics
$userStats = getUserStats($conn);
$csvStats = getCsvStats($conn);
$reportTypes = getReportTypes($conn);

// Helper functions to get statistics
function getUserStats($conn) {
    $stats = [
        'total' => 0,
        'active' => 0,
        'suspended' => 0,
        'admins' => 0,
        'users' => 0
    ];
    
    $sql = "SELECT COUNT(*) as count, Role, AccountStatus FROM user GROUP BY Role, AccountStatus";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['total']++;
            
            if ($row['AccountStatus'] == 'Active') {
                $stats['active']++;
            } else {
                $stats['suspended']++;
            }
            
            if ($row['Role'] == 'Admin') {
                $stats['admins']++;
            } else {
                $stats['users']++;
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

function getReportTypes($conn) {
    $reportTypes = [];
    
    // Get distinct report types from CSV_UPLOAD
    $sql = "SELECT DISTINCT ReportType FROM csv_upload ORDER BY ReportType";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reportTypes[] = $row['ReportType'];
        }
    }
    
    return $reportTypes;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TrafAnalyz</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #9333ea;
            --primary-dark: #7e22ce;
            --secondary: #4f46e5;
            --danger: #dc2626;
            --success: #16a34a;
            --warning: #f59e0b;
            --light: #f9fafb;
            --dark: #1f2937;
            --gray: #6b7280;
        }

        .welcome-banner {
            background-color: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .welcome-banner h2 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card .icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 40px;
            opacity: 0.2;
        }

        .stat-card .label {
            font-size: 14px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark);
        }

        .stat-card .trend {
            margin-top: 10px;
            font-size: 13px;
        }

        .stat-card .trend.up {
            color: var(--success);
        }

        .stat-card .trend.down {
            color: var(--danger);
        }

        .admin-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .admin-section h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            color: var(--dark);
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .quick-link {
            flex: 1;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark);
        }

        .quick-link:hover {
            background-color: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .quick-link i {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .quick-link h4 {
            margin-bottom: 8px;
        }

        .quick-link p {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .quick-link .link-action {
            margin-top: auto;
            font-weight: 500;
            color: var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: #f9fafb;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 12px;
        }

        .badge-success {
            background-color: #ecfdf5;
            color: var(--success);
        }

        .badge-warning {
            background-color: #fffbeb;
            color: var(--warning);
        }

        .badge-danger {
            background-color: #fef2f2;
            color: var(--danger);
        }

        .two-column {
            display: flex;
            gap: 20px;
        }

        .two-column > div {
            flex: 1;
        }

        .sample-upload {
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .sample-upload i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .sample-upload h4 {
            margin-bottom: 10px;
        }

        .sample-upload p {
            margin-bottom: 20px;
            color: var(--gray);
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            border: none;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        @media (max-width: 768px) {
            .stats-row, .two-column {
                flex-direction: column;
            }
            
            .quick-link {
                min-width: unset;
            }
        }

        .report-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .report-type {
            background-color: #f3f4f6;
            border-radius: 50px;
            padding: 8px 15px;
            font-size: 14px;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <main>
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
                    <i class="fas fa-chart-line icon"></i>
                    <div class="label">Report Types</div>
                    <div class="value"><?php echo count($reportTypes); ?></div>
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
                    <a href="#" class="quick-link" id="uploadSampleBtn">
                        <i class="fas fa-upload"></i>
                        <h4>Sample Data</h4>
                        <p>Upload sample CSV files for testing</p>
                        <span class="link-action">Upload Sample &rarr;</span>
                    </a>
                </div>
            </section>

            <div class="two-column">
                <section class="admin-section">
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
                
                <section class="admin-section">
                    <h3><i class="fas fa-file-alt"></i> Supported Report Types</h3>
                    <div class="report-types">
                        <?php if (count($reportTypes) > 0): ?>
                            <?php foreach ($reportTypes as $type): ?>
                                <span class="report-type"><?php echo htmlspecialchars($type); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No report types defined yet. Configure the CSV mappings to add report types.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sample-upload" id="sampleUploadSection" style="display: none; margin-top: 30px;">
                        <i class="fas fa-upload"></i>
                        <h4>Upload Sample CSV Data</h4>
                        <p>Use this feature to upload sample CSV files for testing and demonstration</p>
                        
                        <form action="upload_sample.php" method="post" enctype="multipart/form-data">
                            <input type="file" name="sampleCsv" accept=".csv" required>
                            <select name="reportType" required style="margin: 10px 0;">
                                <option value="">-- Select Report Type --</option>
                                <?php foreach ($reportTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new">Define New Report Type...</option>
                            </select>
                            <div id="newReportTypeField" style="display: none; margin: 10px 0;">
                                <input type="text" name="newReportType" placeholder="Enter new report type name">
                            </div>
                            <div style="margin-top: 15px;">
                                <button type="submit" class="btn btn-primary">Upload Sample</button>
                                <button type="button" class="btn btn-secondary" id="cancelUploadBtn">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <a href="admin_mappings.php" class="btn btn-sm btn-secondary">Manage Report Types</a>
                    </div>
                </section>
            </div>
            
            <section class="admin-section">
                <h3><i class="fas fa-shield-alt"></i> Admin Actions</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="../admin_register.php?key=trafanalyz" class="btn btn-primary">Register New Admin</a>
                    <a href="export_users_pdf.php" target="_blank" class="btn btn-secondary">Export User Report</a>
                    <button id="clearSampleDataBtn" class="btn btn-danger">Clear Sample Data</button>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz - Administrator Dashboard</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadSampleBtn = document.getElementById('uploadSampleBtn');
            const sampleUploadSection = document.getElementById('sampleUploadSection');
            const cancelUploadBtn = document.getElementById('cancelUploadBtn');
            const reportTypeSelect = document.querySelector('select[name="reportType"]');
            const newReportTypeField = document.getElementById('newReportTypeField');
            const clearSampleDataBtn = document.getElementById('clearSampleDataBtn');
            
            uploadSampleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sampleUploadSection.style.display = 'block';
                uploadSampleBtn.style.display = 'none';
            });
            
            cancelUploadBtn.addEventListener('click', function() {
                sampleUploadSection.style.display = 'none';
                uploadSampleBtn.style.display = 'block';
            });
            
            reportTypeSelect.addEventListener('change', function() {
                if (this.value === 'new') {
                    newReportTypeField.style.display = 'block';
                } else {
                    newReportTypeField.style.display = 'none';
                }
            });
            
            clearSampleDataBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all sample data? This action cannot be undone.')) {
                    window.location.href = 'clear_sample_data.php';
                }
            });
        });
    </script>
</body>
</html>