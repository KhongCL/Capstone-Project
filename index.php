<?php
require_once 'config.php';
include 'functions.php';

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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="scripts.js"></script>

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
            <section class="upload-section">
                <h2>Upload Traffic Data</h2>
                <?php if (!empty($uploadMessage)): ?>
                    <div class="message <?php echo isset($uploadMessage['type']) ? $uploadMessage['type'] : ''; ?>">
                        <?php echo isset($uploadMessage['message']) ? $uploadMessage['message'] : $uploadMessage; ?>
                    </div>
                <?php endif; ?>
                <p>Upload your CSV file containing web traffic data.</p>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="csvFile">Select CSV File:</label>
                        <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn">Upload Data</button>
                </form>
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
