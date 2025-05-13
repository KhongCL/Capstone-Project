<?php
require_once 'config.php';
include 'functions.php';

// Check if user is logged in and has admin privileges
session_start();
// In a real app, add authentication check here
// For example: if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { ... }

$pageTitle = "User Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TrafAnalyz Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .user-management-container {
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-suspended {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 0.8rem;
            margin-right: 5px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn-suspend {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-restore {
            background-color: #28a745;
            color: #fff;
        }
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        .notification {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: none;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .search-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-controls input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            flex-grow: 1;
        }
        .filter-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-controls button {
            padding: 8px 12px;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-controls button.active {
            background-color: #4c78d0;
            color: white;
            border-color: #4c78d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>TrafAnalyz Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="overview.php">Overview</a></li>
                    <li><a href="user_management.php" class="active">User Management</a></li>
                    <li><a href="admin_mappings.php">CSV Mappings</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section>
                <h2>User Management</h2>
                
                <div id="notification" class="notification"></div>
                
                <div class="search-controls">
                    <input type="text" id="userSearch" placeholder="Search users by name, email, or username...">
                    <button class="btn" id="searchButton">Search</button>
                </div>
                
                <div class="filter-controls">
                    <button class="active" data-filter="all">All Users</button>
                    <button data-filter="active">Active Users</button>
                    <button data-filter="suspended">Suspended Users</button>
                    <button data-filter="admin">Admins</button>
                    <button data-filter="end-user">End Users</button>
                </div>
                
                <div id="userManagementContainer" class="user-management-container">
                    <p>Loading users...</p>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz - Web Traffic Analysis Dashboard</p>
        </footer>
    </div>

    <script src="js/user-management.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize user management interface
            UserManagement.initUserManagement('userManagementContainer');
            
            // Setup notification system
            const notification = document.getElementById('notification');
            function showNotification(message, type) {
                notification.textContent = message;
                notification.className = `notification ${type}`;
                notification.style.display = 'block';
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    notification.style.display = 'none';
                }, 5000);
            }
            
            // Override callback functions to show notifications
            const originalSuspendUser = UserManagement.suspendUser;
            UserManagement.suspendUser = function(userId, callback) {
                originalSuspendUser.call(UserManagement, userId, function(response) {
                    if (response.success) {
                        showNotification('User has been suspended.', 'success');
                    } else {
                        showNotification('Failed to suspend user: ' + response.message, 'error');
                    }
                    if (callback) callback(response);
                });
            };
            
            const originalRestoreUser = UserManagement.restoreUser;
            UserManagement.restoreUser = function(userId, callback) {
                originalRestoreUser.call(UserManagement, userId, function(response) {
                    if (response.success) {
                        showNotification('User has been restored.', 'success');
                    } else {
                        showNotification('Failed to restore user: ' + response.message, 'error');
                    }
                    if (callback) callback(response);
                });
            };
            
            const originalDeleteUser = UserManagement.deleteUser;
            UserManagement.deleteUser = function(userId, callback) {
                originalDeleteUser.call(UserManagement, userId, function(response) {
                    if (response.success) {
                        showNotification('User has been deleted.', 'success');
                    } else {
                        showNotification('Failed to delete user: ' + response.message, 'error');
                    }
                    if (callback) callback(response);
                });
            };
            
            // Setup filter controls
            document.querySelectorAll('.filter-controls button').forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button
                    document.querySelectorAll('.filter-controls button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Apply filter
                    const filter = this.getAttribute('data-filter');
                    const rows = document.querySelectorAll('#userManagementContainer tr[data-user-id]');
                    
                    rows.forEach(row => {
                        switch (filter) {
                            case 'all':
                                row.style.display = '';
                                break;
                            case 'active':
                                if (row.querySelector('.status-badge').textContent === 'Active') {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'suspended':
                                if (row.querySelector('.status-badge').textContent === 'Suspended') {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'admin':
                                if (row.children[4].textContent === 'Admin') {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'end-user':
                                if (row.children[4].textContent === 'End-User') {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                        }
                    });
                });
            });
            
            // Setup search functionality
            document.getElementById('searchButton').addEventListener('click', performSearch);
            document.getElementById('userSearch').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = document.getElementById('userSearch').value.toLowerCase();
                const rows = document.querySelectorAll('#userManagementContainer tr[data-user-id]');
                
                rows.forEach(row => {
                    const username = row.children[1].textContent.toLowerCase();
                    const fullName = row.children[2].textContent.toLowerCase();
                    const email = row.children[3].textContent.toLowerCase();
                    
                    if (username.includes(searchTerm) || 
                        fullName.includes(searchTerm) || 
                        email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>