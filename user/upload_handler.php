<?php
// Suppress PHP warnings/notices in output for AJAX responses
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once '../auth/user_auth.php';
require_once '../config.php';
require_once '../classes/CsvProcessor.php';
include '../functions.php';

// Ensure this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    exit('Bad Request');
}

// Add debugging
error_log("Upload handler called");

header('Content-Type: application/json');

session_start();

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
        if ($uploadMessage['type'] === 'success') {
            $response['success'] = true;
            $response['message'] = $uploadMessage['message'];
            $response['stage'] = 4; // Completed
        } else if ($uploadMessage['type'] === 'needs_mapping') {
            // Handle column mapping scenario
            $response['success'] = true;
            $response['message'] = $uploadMessage['message'];
            $response['redirect'] = $uploadMessage['redirect'];
            $response['stage'] = 4; // Completed processing, ready for mapping
        } else {
            $response['success'] = false;
            $response['message'] = $uploadMessage['message'];
            
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