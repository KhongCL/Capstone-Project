<?php
// filepath: c:\xampp\htdocs\Capstone-Project\api_users.php
require_once 'config.php';
include 'functions.php';  // This is the correct way to include functions

// Set header to return JSON response
header('Content-Type: application/json');

// Check if request is AJAX and has proper authentication
// In a production environment, implement proper authentication here
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => 'No action specified',
    'data' => null
];

// Process user account actions via POST or GET
if (isset($_REQUEST['action']) && isset($_REQUEST['user_id'])) {
    $userId = (int)$_REQUEST['user_id'];
    
    switch ($_REQUEST['action']) {
        case 'suspend':
            if (updateUserStatus($conn, $userId, 'Suspended')) {
                $response = [
                    'success' => true,
                    'message' => "User account has been suspended.",
                    'data' => ['userId' => $userId, 'status' => 'Suspended']
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "Failed to suspend user account."
                ];
            }
            break;
            
        case 'restore':
            if (updateUserStatus($conn, $userId, 'Active')) {
                $response = [
                    'success' => true,
                    'message' => "User account has been restored.",
                    'data' => ['userId' => $userId, 'status' => 'Active']
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "Failed to restore user account."
                ];
            }
            break;
            
        case 'delete':
            if (deleteUser($conn, $userId)) {
                $response = [
                    'success' => true,
                    'message' => "User account has been deleted.",
                    'data' => ['userId' => $userId, 'deleted' => true]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "Failed to delete user account."
                ];
            }
            break;
            
        case 'get_users':
            $users = getAllUsers($conn);
            $response = [
                'success' => true,
                'message' => "Users retrieved successfully.",
                'data' => $users
            ];
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => "Invalid action specified."
            ];
    }
} else {
    $response = [
        'success' => false,
        'message' => "Missing required parameters."
    ];
}

// Return JSON response
echo json_encode($response);
?>