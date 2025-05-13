<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in as admin (implement proper authentication)
session_start();

$message = '';
$messageType = '';

// Process user account actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'suspend':
                if (updateUserStatus($conn, $userId, 'Suspended')) {
                    $message = "User account has been suspended.";
                    $messageType = "success";
                } else {
                    $message = "Failed to suspend user account.";
                    $messageType = "error";
                }
                break;
                
            case 'restore':
                if (updateUserStatus($conn, $userId, 'Active')) {
                    $message = "User account has been restored.";
                    $messageType = "success";
                } else {
                    $message = "Failed to restore user account.";
                    $messageType = "error";
                }
                break;
                
            case 'delete':
                if (deleteUser($conn, $userId)) {
                    $message = "User account has been deleted.";
                    $messageType = "success";
                } else {
                    $message = "Failed to delete user account.";
                    $messageType = "error";
                }
                break;
        }
    }
}

// Debug SQL directly - this helps diagnose the issue
$debugSql = "SELECT COUNT(*) as user_count FROM user";
$debugResult = $conn->query($debugSql);
$debugCount = 0;
if ($debugResult) {
    $debugCount = $debugResult->fetch_assoc()['user_count'];
}

// Get users from the database
$users = [];
$sql = "SELECT UserID, Username, Email, Role, AccountStatus, CreatedAt FROM user ORDER BY UserID";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TrafAnalyz Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            padding: 20px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .user-table th, .user-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .user-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
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
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-suspended {
            background-color: #f8d7da;
            color: #721c24;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .debug-info {
            background-color: #e9ecef;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-family: monospace;
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
                    <li><a href="admin_users.php" class="active">User Management</a></li>
                    <li><a href="admin_mappings.php">CSV Mappings</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section>
                <h2>User Management</h2>
                
                <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Debug information -->
                <div class="debug-info">
                    <p>Database query found: <?php echo $debugCount; ?> user(s)</p>
                    <p>PHP array contains: <?php echo count($users); ?> user(s)</p>
                </div>
                
                <?php if (empty($users)): ?>
                    <p>No users found in the database.</p>
                <?php else: ?>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['UserID']; ?></td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td><?php echo $user['Role']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($user['AccountStatus']); ?>">
                                        <?php echo $user['AccountStatus']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['CreatedAt'])); ?></td>
                                <td class="actions">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                        <?php if ($user['AccountStatus'] === 'Active'): ?>
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn btn-suspend" 
                                                    onclick="return confirm('Are you sure you want to suspend this user?')">
                                                Suspend
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit" class="btn btn-restore">
                                                Restore
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-delete" 
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz - Web Traffic Analysis Dashboard</p>
        </footer>
    </div>
</body>
</html>