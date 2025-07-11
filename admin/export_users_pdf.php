<?php
require_once '../auth/admin_auth.php'; // Admin Login Validation
require_once '../config.php';
require_once '../functions.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Log export to database if coming from export action
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $userId = $_SESSION['user_id'] ?? null;
    
    if ($userId) {
        $exportType = 'PDF';
        $currentDate = date('Y-m-d');
        $description = "Admin exported user management report as PDF - $currentDate";
        
        $stmt = $conn->prepare("INSERT INTO export_history (UserID, ExportType, ExportedDataDescription) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $exportType, $description);
        $stmt->execute();
    }
}

// Get users
$users = [];
$sql = "SELECT UserID, Username, Email, Role, AccountStatus, CreatedAt FROM user ORDER BY UserID";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Count user types
$totalUsers = count($users);
$activeUsers = 0;
$suspendedUsers = 0;
foreach ($users as $user) {
    if ($user['AccountStatus'] === 'Active') {
        $activeUsers++;
    } else {
        $suspendedUsers++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management Report - TrafAnalyz</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
				body h1 {
					color: white;
					padding: 2rem;
					margin: 0;
					text-align: center;
					font-size: 2.5rem;
					font-weight: 700;
				}

				h2 {
					color: var(--primary-dark);
					font-size: 1.5rem;
				}


				/* Enhanced non-print section */
				.non-print {
					background: rgba(255, 255, 255, 0.9);
					backdrop-filter: blur(10px);
					border: 1px solid rgba(226, 232, 240, 0.6);
					border-radius: 1rem;
					padding: 1.5rem;
					margin-bottom: 2rem;
					box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
					margin-left: auto;
					margin-right: auto;
				}

				.non-print h2 {
					color: var(--text-color);
					margin-top: 0;
					margin-bottom: 1rem;
					display: flex;
					align-items: center;
					gap: 0.75rem;
				}

				.non-print p {
					color: var(--dark-gray);
					margin-bottom: 1.5rem;
				}

				.header-title {
					background: var(--primary-dark);
					color: white;
					padding: 1.5rem;
					margin-bottom: 20px;
					border-radius: 1rem;
					box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
					text-align: center;
					font-size: 2.5rem;
					font-weight: 700;
					position: relative;
					overflow: hidden;
				}

				/* Position the header-info as a separate container between title and stats */
				.header-info {
					position: static;
					background: rgba(224, 242, 254, 0.8);
					backdrop-filter: blur(10px);
					padding: 1rem 1.5rem;
					border-radius: 0.75rem;
					border: 1px solid rgba(14, 165, 233, 0.2);
					box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
					font-size: 0.875rem;
					color: var(--dark-gray);
					text-align: center;
					max-width: 1200px;
					margin-bottom: 20px;
					z-index: 10;
				}

				.header-info p {
					margin: 0.25rem 0;
					color: var(--dark-gray);
					font-size: 0.875rem;
				}

				.stats-container {
						margin-bottom: 32px;
						margin-top: 32px;
						max-width: 1200px;
						position: relative;
				}

				.stats {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
						gap: 20px;
				}

				.stat-item {
						background: rgba(255, 255, 255, 0.8);
						backdrop-filter: blur(10px);
						border: 1px solid rgba(226, 232, 240, 0.6);
						border-radius: 0.75rem;
						padding: 2rem;
						box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
						color: var(--text-color);
						font-size: 1rem;
						text-align: center;
						transition: transform 0.3s ease;
				}

				.stat-item:hover {
						box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
						transform: translateY(-5px);
						transition: transform 0.3s ease;
				}

				.stat-item h3 {
						margin: 0;
						font-family: inherit;
						font-size: 1.5rem;
						font-weight: 700;
						color: var(--primary-dark);
				}

				.stat-item p {
						margin: 0.5rem 0 0;
						font-family: inherit;
						font-size: 1.5rem;
						font-weight: 500;
				}

				.user-details {
						background: rgba(255, 255, 255, 0.9);
						backdrop-filter: blur(10px);
						border: 1px solid rgba(226, 232, 240, 0.6);
						border-radius: 1rem;
						padding: 2rem;
						margin: 0 auto 2rem auto;
						max-width: 1200px;
						box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
						position: relative;
				}

				.user-table {
					border-radius: 1rem;
				}

				/* Enhanced export page tables - now inside main container */
				body table {
					width: 100%;
					border-collapse: collapse;
					background: white;
					overflow: hidden;
				}

				body th {
					background: var(--primary-dark);
					color: white;
					padding: 1rem;
					text-align: left;
					font-weight: 600;
					font-size: 0.875rem;
					border: none;
				}

				body td {
					padding: 1rem;
					border-bottom: 1px solid var(--gray-200);
					color: var(--text-color);
					border-left: none;
					border-right: none;
				}

				body tr:hover {
					background: rgba(224, 242, 254, 0.3);
				}

				body tr:last-child td {
					border-bottom: none;
				}

				body tr:nth-child(even) {
					background: rgba(248, 250, 252, 0.5);
				}

				/* Enhanced export page badges */
				body .badge {
					display: inline-block;
					padding: 0.25rem 0.75rem;
					border-radius: 1rem;
					font-size: 0.75rem;
					font-weight: 500;
					border: 1px solid;
				}

				body .badge-active {
					background: var(--success-light);
					color: var(--success);
					border-color: var(--success-border);
				}

				body .badge-suspended {
					background: var(--danger-light);
					color: var(--danger);
					border-color: var(--danger-border);
				}

				/* Enhanced footer for export pages - separate container */
				body .footer {
					background: rgba(255, 255, 255, 0.9);
					backdrop-filter: blur(10px);
					border: 1px solid rgba(226, 232, 240, 0.6);
					border-radius: 1rem;
					margin: 0 auto 2rem auto;
					padding: 2rem;
					max-width: 1200px;
					font-size: 0.875rem;
					font-style: italic;
					text-align: center;
					color: var(--dark-gray);
				}

				/* Print-specific overrides for export pages */
				@media print {
					body {
						background: white;
						margin: 0;
						padding: 15px;
					}

					body .header-info {
						background: #f8f9fa;
						border: 1px solid #ddd;
						color: #333;
					}

					body .stats {
						background: white;
						border: 1px solid #ddd;
					}
				}

    </style>
</head>
<body>
		<div class="container">
		
				<div class="non-print">
						<h2>User Management Report - Print View</h2>
						<p>This page is formatted for printing. When you click Print/Save, the export will be logged automatically.</p>
						<a class="btn" id="printButton">Print / Save as PDF</a>
						<a class="btn" href="admin_users.php" style="background-color: #6c757d;">Back to User Management</a>
				</div>

				<div class="header-title">
						<h1>TrafAnalyz User Management Report</h1>
				</div>
				
				
				<div class="header-info">
						<p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
						<p>Generated by: <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Administrator'; ?></p>
				</div>
				
				<div class="stats-container">
						<div class="stats">
								<div class="stat-item">
									<h3>Total Users</h3>
									<p><?php echo $totalUsers; ?></p>
								</div>

								<div class="stat-item">
									<h3>Active Users</h3>
									<p><?php echo $activeUsers; ?></p>
								</div>

								<div class="stat-item">
									<h3>Suspended Users</h3>
									<p><?php echo $suspendedUsers; ?></p>
								</div>
						</div>
				</div>

				
				<div class="user-details">

						<h2>User Accounts</h2>
						<div class="user-table">
							<table>
									<thead>
											<tr>
													<th>ID</th>
													<th>Username</th>
													<th>Email</th>
													<th>Role</th>
													<th>Status</th>
													<th>Created</th>
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
															<span class="badge badge-<?php echo strtolower($user['AccountStatus']); ?>">
																	<?php echo $user['AccountStatus']; ?>
															</span>
													</td>
													<td><?php echo date('Y-m-d', strtotime($user['CreatedAt'])); ?></td>
											</tr>
											<?php endforeach; ?>
									</tbody>
							</table>
						</div>
				</div>
				
				<?php include 'admin_footer.php'; ?>
		</div>
    
    <script>
    // Handle export with logging
    document.getElementById('printButton').addEventListener('click', function() {
        // Log the export action first
        fetch('export_users_pdf.php?export=1', {
            method: 'GET'
        }).then(response => {
            // Generate filename for PDF
            const today = new Date();
            const dateStr = today.getFullYear() + '-' + 
                          String(today.getMonth() + 1).padStart(2, '0') + '-' +
                          String(today.getDate()).padStart(2, '0');
            const filename = 'TrafAnalyz_User_Report_' + dateStr + '.pdf';
            
            // Set the document title (will be used as default filename)
            document.title = filename;
            
            // Trigger print dialog
            window.print();
        });
    });
    </script>
</html>