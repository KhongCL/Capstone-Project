<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../functions.php';

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
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="container admin-user-management-container">
        <?php 
            $title = "User Management";
            $active_page = "users";
            include 'header.php';
        ?>
        
        <main>
            <section>
                <h2>User Management</h2>
                
                <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Debug information -->
                <div class="admin-debug-info">
                    <p>Database query found: <?php echo $debugCount; ?> user(s)</p>
                    <p>PHP array contains: <?php echo count($users); ?> user(s)</p>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="admin-no-users-message">
                        <p>No users found in the database.</p>
                    </div>
                <?php else: ?>
                    <table class="admin-user-table">
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
                                    <span class="admin-status-badge admin-status-<?php echo strtolower($user['AccountStatus']); ?>">
                                        <?php echo $user['AccountStatus']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['CreatedAt'])); ?></td>
                                <td class="admin-user-actions">
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                        <?php if ($user['AccountStatus'] === 'Active'): ?>
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="admin-btn-suspend" 
                                                    onclick="return confirm('Are you sure you want to suspend this user?')">
                                                Suspend
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit" class="admin-btn-restore">
                                                Restore
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-btn-delete" 
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
        
        <?php include 'admin_footer.php'; ?>
    </div>
</body>
</html>