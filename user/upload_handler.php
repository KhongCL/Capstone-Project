<?php
// Suppress PHP warnings/notices in output for AJAX responses
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once '../auth/user_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
include '../functions.php';

// Add more debugging at the top
error_log("=== UPLOAD HANDLER START ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Files received: " . print_r($_FILES, true));
error_log("POST data: " . print_r($_POST, true));

// Ensure this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    error_log("ERROR: Not an AJAX request");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad Request - Not AJAX']);
    exit;
}

// Add debugging
error_log("Upload handler called");

header('Content-Type: application/json');

session_start();

// CRITICAL FIX: Log current session state for debugging
error_log("UPLOAD_HANDLER: Current session state:");
error_log("- using_sample_data: " . (isset($_SESSION['using_sample_data']) ? ($_SESSION['using_sample_data'] ? 'true' : 'false') : 'not set'));
error_log("- sample_upload_id: " . ($_SESSION['sample_upload_id'] ?? 'not set'));
error_log("- latest_upload_id: " . ($_SESSION['latest_upload_id'] ?? 'not set'));

$response = [
    'success' => false,
    'message' => '',
    'stage' => 0,
    'errors' => []
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    error_log("Processing file upload: " . $_FILES['csvFile']['name']);
    
    $uploadMessage = handleCsvUpload($conn, $_FILES['csvFile']);
    error_log("Upload result: " . json_encode($uploadMessage));
    
    if (is_array($uploadMessage)) {
        error_log("UPLOAD_HANDLER: Upload message type: " . $uploadMessage['type']);
        if ($uploadMessage['type'] === 'success') {
            $response['success'] = true;
            $response['message'] = $uploadMessage['message'];
            $response['stage'] = 4; // Completed
            error_log("UPLOAD_HANDLER: Success response prepared");
        } else if ($uploadMessage['type'] === 'warning') {
            // NEW: Handle validation warnings
            $response['success'] = true;
            $response['message'] = $uploadMessage['message'];
            $response['stage'] = 4; // Completed with warnings
            $response['validation_errors'] = $uploadMessage['validation_errors'] ?? [];
            error_log("UPLOAD_HANDLER: Warning response prepared with " . count($response['validation_errors']) . " validation errors");
        } else if ($uploadMessage['type'] === 'needs_mapping') {
            // Handle column mapping scenario
            $response['success'] = true;
            $response['message'] = $uploadMessage['message'];
            $response['redirect'] = $uploadMessage['redirect'];
            $response['stage'] = 4; // Completed processing, ready for mapping
            error_log("UPLOAD_HANDLER: Manual mapping response prepared");
            error_log("UPLOAD_HANDLER: Redirect URL: " . $uploadMessage['redirect']);
        } else {
            $response['success'] = false;
            $response['message'] = $uploadMessage['message'];
            error_log("UPLOAD_HANDLER: Error response prepared: " . $uploadMessage['message']);
            
            // Enhanced error parsing for validation errors
            if (strpos($uploadMessage['message'], 'Data validation errors found:') !== false) {
                // Extract the detailed validation errors
                $errorMessage = $uploadMessage['message'];
                
                // Remove the prefix and suffix to get just the error list
                $errorMessage = str_replace('Data validation errors found: ', '', $errorMessage);
                $errorMessage = str_replace('. Please correct these issues and upload again.', '', $errorMessage);
                
                $parsedErrors = [];
                
                // Use regex to extract each complete error message
                preg_match_all('/Row \d+ \([^)]*\)[^;]*(?:(?:; (?!Row \d+))[^;]*)*/', $errorMessage, $matches);
                
                foreach ($matches[0] as $error) {
                    $error = trim($error);
                    if (!empty($error)) {
                        addParsedError($parsedErrors, $error);
                    }
                }
                
                // If we didn't find any errors with the regex, fallback to the old method
                if (empty($parsedErrors)) {
                    $errorParts = preg_split('/(?=Row \d+ \()/', $errorMessage, -1, PREG_SPLIT_NO_EMPTY);
                    
                    foreach ($errorParts as $part) {
                        $part = trim($part);
                        if (!empty($part)) {
                            if (strpos($part, 'Row ') === 0) {
                                addParsedError($parsedErrors, $part);
                            }
                        }
                    }
                }
                
                $response['errors'] = $parsedErrors;
                $response['stage'] = 2; // Failed during processing
                
                error_log("Parsed validation errors: " . json_encode($parsedErrors));
            } else {
                $response['stage'] = 1; // Failed during basic validation
            }
        }
        error_log("UPLOAD_HANDLER: Final response: " . json_encode($response));
    } else {
        $response['success'] = false;
        $response['message'] = 'Invalid response from upload handler';
        $response['stage'] = 0;
    }
} else {
    $response['message'] = 'No file uploaded';
}

// Helper function to add parsed errors
function addParsedError(&$parsedErrors, $errorText) {
    $errorText = trim($errorText);
    if (empty($errorText)) return;
    
    // Check if this error contains suggestions
    if (strpos($errorText, ' Suggestions: ') !== false) {
        // Split error and suggestions
        $parts = explode(' Suggestions: ', $errorText, 2);
        $mainError = trim($parts[0]);
        $suggestions = isset($parts[1]) ? trim($parts[1]) : '';
        
        $parsedErrors[] = [
            'message' => $mainError,
            'suggestions' => $suggestions
        ];
    } else {
        // Check for inline suggestions pattern (Try: at the end)
        if (preg_match('/^(.+?)\s+Try:\s*(.+)$/s', $errorText, $matches)) {
            $mainError = trim($matches[1]);
            $suggestions = 'Try: ' . trim($matches[2]);
            
            $parsedErrors[] = [
                'message' => $mainError,
                'suggestions' => $suggestions
            ];
        } else {
            // No suggestions, just the error message
            $parsedErrors[] = [
                'message' => $errorText,
                'suggestions' => ''
            ];
        }
    }
}

error_log("Final response: " . json_encode($response));
echo json_encode($response);
?>