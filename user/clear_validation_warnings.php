<?php
require_once '../auth/user_auth.php';
require_once '../functions.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clearPersistentValidationErrors();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
exit;
?>