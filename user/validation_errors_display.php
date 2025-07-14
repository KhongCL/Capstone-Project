<?php
// CRITICAL FIX: Only manipulate session if it's safe to do so
$currentPage = basename($_SERVER['PHP_SELF']);
$isDashboardPage = in_array($currentPage, ['overview.php', 'traffic_sources.php', 'pages.php']);
$isComparePage = ($currentPage === 'compare.php');
$isIndexPage = ($currentPage === 'index.php');

// CRITICAL FIX: Only clear validation errors if session is safely accessible
$canManipulateSession = (session_status() === PHP_SESSION_ACTIVE) || (!headers_sent() && session_status() === PHP_SESSION_NONE);

// CRITICAL FIX: Check validation context to determine clearing behavior
$isComparisonContext = false;
if ($canManipulateSession && session_status() === PHP_SESSION_ACTIVE) {
    $isComparisonContext = isset($_SESSION['validation_errors_comparison_context']) && $_SESSION['validation_errors_comparison_context'] === true;
    error_log("validation_errors_display.php: Comparison context = " . ($isComparisonContext ? 'true' : 'false'));
}

// CRITICAL FIX: Modified clearing logic based on context
// For comparison context - clear on dashboard pages, preserve on compare.php
if ($isComparisonContext && $isDashboardPage && $_SERVER['REQUEST_METHOD'] === 'GET' && 
    !isset($_GET['upload_success']) && function_exists('clearValidationErrorsOnPageLoad') && $canManipulateSession) {
    clearValidationErrorsOnPageLoad();
    error_log("Cleared comparison validation errors on dashboard page: $currentPage");
}

// For regular context - DON'T clear on dashboard pages, clear on compare.php
if (!$isComparisonContext && $isComparePage && $_SERVER['REQUEST_METHOD'] === 'GET' && 
    !isset($_GET['mapping_failed']) && empty($_GET) && 
    function_exists('clearValidationErrorsOnPageLoad') && $canManipulateSession) {
    clearValidationErrorsOnPageLoad();
    error_log("Cleared regular validation errors on compare.php normal load");
}

// Clear validation errors on index.php page refresh (both contexts)
if ($isIndexPage && $_SERVER['REQUEST_METHOD'] === 'GET' && 
    !isset($_GET['upload_success']) && !isset($_GET['mapping_failed']) && 
    empty($_GET) && function_exists('clearValidationErrorsOnPageLoad') && $canManipulateSession) {
    clearValidationErrorsOnPageLoad();
    error_log("Cleared validation errors on index.php page refresh");
}

// Get persistent validation errors if they exist
$persistentErrors = null;
if (function_exists('getPersistentValidationErrors')) {
    $persistentErrors = getPersistentValidationErrors();
    error_log("getPersistentValidationErrors() returned: " . ($persistentErrors ? 'DATA' : 'NULL'));
    if ($persistentErrors) {
        error_log("Error count: " . $persistentErrors['error_count']);
    }
} else {
    error_log("Cannot call getPersistentValidationErrors - function not available");
}

if ($persistentErrors && !empty($persistentErrors['errors'])):
    $validationErrors = $persistentErrors['errors'];
    $errorCount = $persistentErrors['error_count'];
?>
<div class="validation-warnings-banner" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;">
    <div class="warning-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
        <i class="fas fa-exclamation-triangle" style="color: #856404; font-size: 1.2em;"></i>
        <h4 style="margin: 0; color: #856404; font-size: 1.1em;">Data Imported with Validation Warnings</h4>
        <button onclick="clearValidationWarnings()" style="margin-left: auto; background: none; border: 1px solid #856404; color: #856404; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em;">
            <i class="fas fa-times"></i> Dismiss
        </button>
    </div>
    
    <p style="margin: 0 0 10px 0; color: #856404; font-weight: 500;">
        Data successfully imported with <?php echo $errorCount; ?> validation warning<?php echo $errorCount !== 1 ? 's' : ''; ?>.
    </p>
    
    <details style="margin-top: 10px;">
        <summary style="cursor: pointer; color: #856404; font-weight: 600; padding: 5px 0;">
            <i class="fas fa-list-ul"></i> View Validation Warnings (<?php echo $errorCount; ?>)
        </summary>
        
        <div style="margin-top: 15px; max-height: 300px; overflow-y: auto; border: 1px solid #ffeaa7; border-radius: 6px; background: rgba(255, 255, 255, 0.8);">
            <?php foreach ($validationErrors as $index => $error): ?>
                <div style="padding: 12px; border-bottom: 1px solid #ffeaa7; background: <?php echo $index % 2 === 0 ? '#fff' : '#fffbf0'; ?>;">
                    <div style="color: #721c24; font-weight: 500; margin-bottom: 5px;">
                        <?php 
                        // Extract the main error message and suggestions
                        if (strpos($error, ' Suggestions: ') !== false) {
                            $parts = explode(' Suggestions: ', $error, 2);
                            echo htmlspecialchars($parts[0]);
                            if (!empty($parts[1])) {
                                echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 8px; margin-top: 8px; font-size: 0.9em; color: #856404;">';
                                echo '<strong>💡 Suggestions:</strong> ' . htmlspecialchars($parts[1]);
                                echo '</div>';
                            }
                        } else {
                            echo htmlspecialchars($error);
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border: 1px solid #28a745; border-radius: 6px;">
            <h5 style="margin: 0 0 8px 0; color: #155724;"><i class="fas fa-lightbulb"></i> Quick Fix Guide:</h5>
            <ul style="margin: 0; padding-left: 20px; color: #155724; font-size: 0.9em;">
                <li>Open your CSV file in Excel or Google Sheets</li>
                <li>Apply the suggested fixes for each validation warning</li>
                <li>Save the corrected file and upload again for clean data</li>
                <li>Current data is functional but may have some inconsistencies</li>
            </ul>
        </div>
    </details>
</div>

<script>
function clearValidationWarnings() {
    fetch('clear_validation_warnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    }).then(() => {
        document.querySelector('.validation-warnings-banner').style.display = 'none';
    }).catch(error => {
        console.error('Error clearing warnings:', error);
        document.querySelector('.validation-warnings-banner').style.display = 'none';
    });
}
</script>
<?php 
else:
    error_log("NOT DISPLAYING validation errors banner - no data or empty");
    if (!$persistentErrors) {
        error_log("persistentErrors is null/false");
    } elseif (empty($persistentErrors['errors'])) {
        error_log("persistentErrors['errors'] is empty");
    }
endif; 
?>