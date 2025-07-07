<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../classes/CsvProcessor.php';

// Track if this page was loaded after a form submission
$isPostRedirect = false;
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $isPostRedirect = (strpos($referer, 'upload_sample.php') !== false);
}

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
            include 'admin_header.php';
        ?>

        <main>
            <section class="admin-section">
                <h2>Upload Sample CSV Data</h2>
                
                <?php 
                // Only show message if it exists AND this was a post-redirect
                if (isset($_SESSION['sample_upload_message']) && $isPostRedirect): 
                ?>
                    <div class="message <?php echo $_SESSION['sample_upload_message']['success'] ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['sample_upload_message']['message']; ?>
                    </div>
                    <?php unset($_SESSION['sample_upload_message']); ?>
                <?php endif; ?>
                
                <div class="sample-upload-section">                    
                    <div class="card" style="margin-bottom: 30px;">
                        <h3><i class="fas fa-upload"></i> Upload New Sample File</h3>
                        <form action="upload_sample.php" method="post" enctype="multipart/form-data" onsubmit="return validateSampleFile()">
                            <div class="form-group">
                                <label for="sampleCsv">Select CSV File:</label>
                                <input type="file" name="sampleCsv" id="sampleCsv" accept=".csv" required>
                                <small class="help-text">Only CSV files up to 5MB are allowed</small>
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
        
        <?php include 'admin_footer.php'; ?>
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
            
            // Enhanced form submission with progress tracking
            const uploadForm = document.querySelector('form[action="upload_sample.php"]');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateSampleFile()) {
                        return false;
                    }
                    
                    // Show loading state
                    showUploadProgress();
                    
                    // Create FormData and submit via AJAX
                    const formData = new FormData(this);
                    
                    fetch('upload_sample.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideUploadProgress();
                        
                        if (data.success) {
                            showSuccessMessage(data.message);
                            // Reset form
                            uploadForm.reset();
                            newReportTypeField.style.display = 'none';
                        } else {
                            showErrorMessage(data.message);
                        }
                    })
                    .catch(error => {
                        hideUploadProgress();
                        console.error('Upload error:', error);
                        showErrorMessage('An error occurred during upload. Please try again.');
                    });
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
                                showSuccessMessage(data.message);
                            } else {
                                showErrorMessage(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showErrorMessage('An error occurred while clearing sample data.');
                        });
                    }
                });
            }
        });

        function showUploadProgress() {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            }
        }

        function hideUploadProgress() {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Upload Sample';
            }
        }

        function showSuccessMessage(message) {
            // Remove existing messages
            removeExistingMessages();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message success';
            messageDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            
            // Insert after the h2 title
            const title = document.querySelector('h2');
            if (title && title.parentNode) {
                title.parentNode.insertBefore(messageDiv, title.nextSibling);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 5000);
        }

        function showErrorMessage(message) {
            // Remove existing messages
            removeExistingMessages();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message error';
            messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            // Insert after the h2 title
            const title = document.querySelector('h2');
            if (title && title.parentNode) {
                title.parentNode.insertBefore(messageDiv, title.nextSibling);
            }
        }

        function removeExistingMessages() {
            const existingMessages = document.querySelectorAll('.message');
            existingMessages.forEach(msg => {
                if (msg.parentNode) {
                    msg.parentNode.removeChild(msg);
                }
            });
        }

        // Form validation (keep existing function)
        function validateSampleFile() {
            const fileInput = document.getElementById('sampleCsv');
            const reportType = document.getElementById('reportType');
            const newReportType = document.getElementById('newReportType');
            
            // File validation
            if (fileInput.files.length === 0) {
                alert('Please select a CSV file to upload');
                return false;
            }
            
            const file = fileInput.files[0];
            
            // Check file extension
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Only CSV files are allowed');
                return false;
            }
            
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds the 5MB limit');
                return false;
            }
            
            // Report type validation
            if (reportType.value === '') {
                alert('Please select a report type');
                return false;
            }
            
            // New report type validation
            if (reportType.value === 'new' && !newReportType.value.trim()) {
                alert('Please enter a name for the new report type');
                return false;
            }
            
            return true;
        }
        </script>
</body>
</html>