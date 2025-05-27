<?php
require_once '../auth/user_auth.php';
require_once '../config.php';
include '../functions.php';

// Add debugging
error_log("Upload handler called");

header('Content-Type: application/json');

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
                
                // Better parsing: Look for row patterns to split errors properly
                $parsedErrors = [];
                
                // Split by "Row X (" pattern to separate individual errors
                $errorParts = preg_split('/(?=Row \d+ \([^)]+\):)/', $errorMessage);
                
                foreach ($errorParts as $errorPart) {
                    $errorPart = trim($errorPart);
                    if (empty($errorPart)) continue;
                    
                    // Check if this error contains suggestions
                    if (strpos($errorPart, ' Suggestions: ') !== false) {
                        // Split error and suggestions
                        $parts = explode(' Suggestions: ', $errorPart, 2);
                        $mainError = trim($parts[0]);
                        $suggestions = isset($parts[1]) ? trim($parts[1]) : '';
                        
                        $parsedErrors[] = [
                            'message' => $mainError,
                            'suggestions' => $suggestions
                        ];
                    } else {
                        // No suggestions, just the error message
                        $parsedErrors[] = [
                            'message' => $errorPart,
                            'suggestions' => ''
                        ];
                    }
                }
                
                // If the regex split didn't work well, fall back to semicolon splitting
                // but be more careful about suggestions
                if (count($parsedErrors) <= 1 && !empty($errorMessage)) {
                    $parsedErrors = [];
                    
                    // Try a different approach: split by semicolon but reconstruct errors with suggestions
                    $rawErrors = explode(';', $errorMessage);
                    $currentError = '';
                    $inSuggestions = false;
                    
                    foreach ($rawErrors as $part) {
                        $part = trim($part);
                        if (empty($part)) continue;
                        
                        // Check if this part starts a new error (contains "Row X (")
                        if (preg_match('/^Row \d+ \([^)]+\):/', $part)) {
                            // If we have a current error, save it
                            if (!empty($currentError)) {
                                $this->addParsedError($parsedErrors, $currentError);
                                $currentError = '';
                            }
                            $currentError = $part;
                            $inSuggestions = false;
                        } 
                        // Check if this part is a suggestion (starts with "Try:")
                        else if (strpos($part, 'Try:') === 0) {
                            if (!$inSuggestions) {
                                $currentError .= ' Suggestions: ' . $part;
                                $inSuggestions = true;
                            } else {
                                $currentError .= '; ' . $part;
                            }
                        }
                        // This is likely a continuation of the current error or suggestion
                        else {
                            if ($inSuggestions) {
                                $currentError .= '; ' . $part;
                            } else {
                                // Check if it contains suggestions
                                if (strpos($part, 'Suggestions: ') !== false) {
                                    $currentError .= ' ' . $part;
                                    $inSuggestions = true;
                                } else {
                                    $currentError .= ' ' . $part;
                                }
                            }
                        }
                    }
                    
                    // Don't forget the last error
                    if (!empty($currentError)) {
                        $this->addParsedError($parsedErrors, $currentError);
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
        // No suggestions, just the error message
        $parsedErrors[] = [
            'message' => trim($errorText),
            'suggestions' => ''
        ];
    }
}

error_log("Final response: " . json_encode($response));
echo json_encode($response);
?>