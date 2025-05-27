<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../classes/CsvProcessor.php';

// Get report types for sample upload dropdown
$reportTypes = [];
$sql = "SELECT DISTINCT ReportType FROM csv_upload ORDER BY ReportType";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reportTypes[] = $row['ReportType'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Sample Data - TrafAnalyz Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php 
            $title = "Upload Sample Data";
            $active_page = "sample_data";
            include 'header.php';
        ?>

        <main>
            <section class="admin-section">
                <h2>Upload Sample CSV Data</h2>
                
                <?php if (isset($_SESSION['sample_upload_message'])): ?>
                    <div class="message <?php echo $_SESSION['sample_upload_message']['success'] ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['sample_upload_message']['message']; ?>
                    </div>
                    <?php unset($_SESSION['sample_upload_message']); ?>
                <?php endif; ?>
                
                <div class="sample-upload-section">                    
                    <div class="card" style="margin-bottom: 30px;">
                        <h3><i class="fas fa-upload"></i> Upload New Sample File</h3>
                        <form action="upload_sample.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="sampleCsv">Select CSV File:</label>
                                <input type="file" name="sampleCsv" id="sampleCsv" accept=".csv" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reportType">Report Type:</label>
                                <select name="reportType" id="reportType" required>
                                    <option value="">-- Select Report Type --</option>
                                    <?php foreach ($reportTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">Define New Report Type...</option>
                                </select>
                            </div>
                            
                            <div id="newReportTypeField" class="form-group" style="display: none;">
                                <label for="newReportType">New Report Type Name:</label>
                                <input type="text" name="newReportType" id="newReportType" placeholder="Enter new report type name">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Upload Sample</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3><i class="fas fa-trash-alt"></i> Clear Sample Data</h3>
                        <p>Remove all sample data from the system if you need to start fresh.</p>
                        <div class="admin-actions" style="margin-top: 15px;">
                            <button id="clearSampleDataBtn" class="btn btn-danger">Clear All Sample Data</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz - Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Report Type Selection Logic
            const reportTypeSelect = document.getElementById('reportType');
            const newReportTypeField = document.getElementById('newReportTypeField');
            
            if (reportTypeSelect) {
                reportTypeSelect.addEventListener('change', function() {
                    if (this.value === 'new') {
                        newReportTypeField.style.display = 'block';
                    } else {
                        newReportTypeField.style.display = 'none';
                    }
                });
            }
            
            // Clear Sample Data Button
            const clearSampleDataBtn = document.getElementById('clearSampleDataBtn');
            if (clearSampleDataBtn) {
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
                                window.location.reload();
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
            }
        });
    </script>
</body>
</html>