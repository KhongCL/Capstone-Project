<?php
require_once '../config.php';
include '../functions.php';

// Handle CSV upload
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $uploadMessage = handleCsvUpload($conn, $_FILES['csvFile']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../scripts.js"></script>

</head>
<body>
    <div class="container">
        <header>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="overview.php">Overview</a></li>
                    <li><a href="traffic_sources.php">Traffic Sources</a></li>
                    <li><a href="pages.php">Pages</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
        <section class="welcome-section">
            <h2>Welcome to TrafAnalyz</h2>
            <p>Your one-stop solution for analyzing web traffic data. Upload your data and start exploring!</p>
        <section class="upload-section">
            <h2>Upload Traffic Data</h2>
            <?php if (isset($uploadMessage['type']) && $uploadMessage['type'] === 'error' && 
                strpos($uploadMessage['message'], 'Data validation errors') !== false): ?>
                <h3>Data Validation Errors Found</h3>
                
                <?php 
                // Extract the actual error details
                $errorMessage = $uploadMessage['message'];
                
                // Remove the prefix "Data validation errors found: " if it exists
                $errorMessage = str_replace("Data validation errors found: ", "", $errorMessage);
                
                // Remove the "Please correct these issues and upload again" part
                $errorMessage = preg_replace('/\. Please correct these issues and upload again\./', '', $errorMessage);
                
                // Split by semicolons
                $errorList = explode(';', $errorMessage);
                ?>
                
                <div class="error-container">
                    <p class="error-summary">Found <?php echo count($errorList); ?> validation errors in your CSV file:</p>
                    <ul class="error-list">
                        <?php foreach($errorList as $error): ?>
                            <?php $error = trim($error); ?>
                            <?php if(!empty($error)): ?>
                                <li><?php echo $error; ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="validation-help">
                    <h4>Common Validation Issues:</h4>
                    <ul>
                        <li>Integer fields: Use only whole numbers (e.g., "123" not "123a")</li>
                        <li>Float fields: Use decimal numbers (e.g., "12.34" not "12:34" or "12.34.5")</li>
                        <li>Time fields: Use proper time format (e.g., "12:34" or "1:23:45")</li>
                        <li>Percentage fields: Use decimal numbers (e.g., "0.25" or "25%")</li>
                    </ul>
                </div>
                <p>Please correct these issues and upload again.</p>
            <?php else: ?>
                <?php echo isset($uploadMessage['message']) ? $uploadMessage['message'] : $uploadMessage; ?>
            <?php endif; ?>
            <p>Upload your CSV file containing web traffic data. 
                <i class="fas fa-info-circle tooltip-trigger" title="Expected format: GA4 export with columns for date, sessions, users, etc."></i>
            </p>
            <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label for="csvFile">Select CSV File:</label>
                    <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                </div>
                <div class="upload-progress" style="display: none;">
                    <div class="progress-bar"></div>
                    <span class="progress-text">Uploading... 0%</span>
                </div>
                <button type="submit" class="btn" id="uploadBtn">Upload Data</button>
            </form>
            <div class="sample-data">
                <p>New to TrafAnalyz? Try with our sample data:</p>
                <a href="?load_sample=1" class="btn btn-secondary">Load Sample Data</a>
            </div>
        </section>
            
            <section class="dashboard-links">
                <h2>Dashboard Navigation</h2>
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Overview</h3>
                        <p>View key metrics and website traffic over time.</p>
                        <a href="overview.php" class="btn">Go to Overview</a>
                    </div>
                    <div class="card">
                        <h3>Traffic Sources</h3>
                        <p>Analyze where your website traffic is coming from.</p>
                        <a href="traffic_sources.php" class="btn">Go to Traffic Sources</a>
                    </div>
                    <div class="card">
                        <h3>Pages</h3>
                        <p>Discover your most visited webpages.</p>
                        <a href="pages.php" class="btn">Go to Pages</a>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
</body>
</html>
