<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Handle CSV upload (fallback for non-JavaScript)
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
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
    <link rel="stylesheet" href="user_style.css">
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
            </section>
            
            <section class="upload-section">
                <h2>Upload Traffic Data</h2>
                
                <?php if (!empty($uploadMessage)): ?>
                    <?php 
                    // Normalize $uploadMessage to always be an array
                    if (is_string($uploadMessage)) {
                        $uploadMessage = ['type' => 'info', 'message' => $uploadMessage];
                    }
                    ?>
                    
                    <?php if ($uploadMessage['type'] === 'error' && 
                              strpos($uploadMessage['message'], 'Data validation errors') !== false): ?>
                        <?php
                        // Enhanced error message parsing to extract suggestions
                        $errorMessage = $uploadMessage['message'];
                        $errorMessage = str_replace("Data validation errors found: ", "", $errorMessage);
                        $errorMessage = preg_replace('/\. Please correct these issues and upload again\./', '', $errorMessage);
                        
                        // Split by semicolons and parse suggestions
                        $errorList = explode(';', $errorMessage);
                        ?>
                        
                        <div class="error-container">
                            <p class="error-summary">Found <?php echo count($errorList); ?> validation errors in your CSV file:</p>
                            <ul class="error-list">
                                <?php foreach($errorList as $error): ?>
                                    <?php $error = trim($error); ?>
                                    <?php if(!empty($error)): ?>
                                        <?php
                                        // Parse error and suggestions
                                        $parts = explode(' Suggestions: ', $error);
                                        $mainError = $parts[0];
                                        $suggestions = isset($parts[1]) ? $parts[1] : '';
                                        ?>
                                        <li class="error-item">
                                            <div class="error-message"><?php echo htmlspecialchars($mainError); ?></div>
                                            <?php if (!empty($suggestions)): ?>
                                                <div class="error-suggestions">
                                                    <strong>💡 Suggestions:</strong> 
                                                    <span class="suggestions-text"><?php echo htmlspecialchars($suggestions); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="validation-help">
                            <h4>Quick Fix Guide:</h4>
                            <div class="fix-guide">
                                <div class="fix-item">
                                    <strong>🔢 Integer Issues:</strong>
                                    <ul>
                                        <li>Remove letters: "15a" → "15"</li>
                                        <li>Evaluate expressions: "42+3" → "45"</li>
                                        <li>Convert Unicode: "５０" → "50"</li>
                                    </ul>
                                </div>
                                <div class="fix-item">
                                    <strong>📊 Float/Decimal Issues:</strong>
                                    <ul>
                                        <li>Fix multiple decimals: "8..5" → "8.5"</li>
                                        <li>Convert scientific: "1.2e3" → "1200"</li>
                                        <li>Remove special chars: "~5.3" → "5.3"</li>
                                    </ul>
                                </div>
                                <div class="fix-item">
                                    <strong>⏰ Time Format Issues:</strong>
                                    <ul>
                                        <li>Use proper format: "10:65:30" → "11:05:30"</li>
                                        <li>Convert units: "12m30s" → "12:30" or "750"</li>
                                    </ul>
                                </div>
                                <div class="fix-item">
                                    <strong>💰 Currency Issues:</strong>
                                    <ul>
                                        <li>Remove symbols: "$1,200" → "1200"</li>
                                        <li>Remove commas: "500.abc" → "500"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <p class="error-footer">Please correct these issues and upload again.</p>
                        
                    <?php else: ?>
                        <!-- Display other types of messages -->
                        <div class="message <?php echo $uploadMessage['type']; ?>">
                            <?php echo htmlspecialchars($uploadMessage['message']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <p>Upload your CSV file containing web traffic data. 
                    <i class="fas fa-info-circle tooltip-trigger" title="Expected format: GA4 export with columns for date, sessions, users, etc."></i>
                </p>
                <form action="" method="post" enctype="multipart/form-data" id="uploadForm" data-ajax-handler="upload_handler.php">
                    <div class="form-group">
                        <label for="csvFile">Select CSV File:</label>
                        <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                        <div class="file-info" id="fileInfo" style="display: none;">
                            <span class="file-name"></span>
                            <span class="file-size"></span>
                        </div>
                    </div>
                    
                    <!-- Enhanced Progress Indicators -->
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-container">
                            <div class="progress-stage active" id="stage1">
                                <div class="stage-icon">📁</div>
                                <div class="stage-text">Uploading File</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="uploadBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="uploadPercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage2">
                                <div class="stage-icon">🔍</div>
                                <div class="stage-text">Validating Structure</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="validateBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="validatePercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage3">
                                <div class="stage-icon">⚙️</div>
                                <div class="stage-text">Processing Data</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="processBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="processPercent">0%</span>
                                </div>
                            </div>
                            
                            <div class="progress-stage" id="stage4">
                                <div class="stage-icon">💾</div>
                                <div class="stage-text">Saving to Database</div>
                                <div class="stage-progress">
                                    <div class="progress-bar" id="saveBar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text" id="savePercent">0%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overall-progress">
                            <div class="overall-bar">
                                <div class="overall-fill" id="overallFill" style="width: 0%"></div>
                            </div>
                            <div class="overall-text">
                                <span id="overallPercent">0%</span> Complete
                                <span id="currentTask">Ready to upload...</span>
                            </div>
                        </div>
                        
                        <div class="progress-details" id="progressDetails">
                            <div class="detail-item">
                                <span class="detail-label">File Size:</span>
                                <span class="detail-value" id="fileSizeDetail">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Upload Speed:</span>
                                <span class="detail-value" id="uploadSpeed">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time Remaining:</span>
                                <span class="detail-value" id="timeRemaining">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Rows Processed:</span>
                                <span class="detail-value" id="rowsProcessed">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" id="uploadBtn">Upload Data</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn" style="display: none;">Cancel Upload</button>
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

        <!-- Add before the footer -->
        <div style="text-align: center; margin: 20px 0;">
            <form action="../logout.php" method="post" style="display: inline;">
                <button type="submit" class="btn" style="background-color: #dc3545; color: white;">Logout</button>
            </form>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
<script src="upload_progress.js"></script>
</body>
</html>