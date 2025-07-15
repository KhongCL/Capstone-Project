<?php

// Name: Mervin Ooi Zhian Yang
// Position: Developer
// TP Number: TP076578
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: export_users_pdf.php
// Description: Admin user management report generator that creates detailed PDF reports
//              with user statistics, account analysis, and comprehensive export functionality.
// First Written On: 20 April 2025
// Edited On: 12 July 2025

require_once '../auth/admin_auth.php';
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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

				/* Non-print section */
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

				.admin-user-table {
					border-radius: 1rem;
				}

				/* Export page tables styles */
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

				/* Export page badges styles */
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

				/* Footer for export pages - separate container */
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
						<h2>User Management Report - Export Dashboard</h2>
						<p>Generate a comprehensive PDF report with user statistics, detailed tables, key insights, and report information.</p>
						<button onclick="exportToPDF()" class="btn">
							Export to PDF
						</button>
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
						<div class="admin-user-table">
							<table id="usersTable">
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
    // Parse PHP data to JavaScript
    const usersData = <?php echo json_encode($users); ?>;
    const totalUsers = <?php echo $totalUsers; ?>;
    const activeUsers = <?php echo $activeUsers; ?>;
    const suspendedUsers = <?php echo $suspendedUsers; ?>;
    
    async function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        // Get current date and time
        const now = new Date();
        const generatedDate = now.getFullYear() + '-' + 
            String(now.getMonth() + 1).padStart(2, '0') + '-' + 
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');

        // Get username from session
        const username = '<?php echo $_SESSION['username'] ?? 'Administrator'; ?>';

        // PDF styling
        const pageWidth = pdf.internal.pageSize.getWidth();
        const margin = 20;
        let yPosition = 30;

        // Header
        pdf.setFontSize(20);
        pdf.setFont('helvetica', 'bold');
        pdf.text('TrafAnalyz User Management Report', pageWidth/2, yPosition, { align: 'center' });

        yPosition += 15;

        // Generated info
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Generated on: ${generatedDate}`, pageWidth - margin, yPosition, { align: 'right' });
        yPosition += 5;
        pdf.text(`Generated by: ${username}`, pageWidth - margin, yPosition, { align: 'right' });
        
        yPosition += 20;

        // User Management Summary Section
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('User Management Summary', margin, yPosition);
        yPosition += 10;

        // Summary statistics
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Total Registered Users: ${totalUsers}`, margin, yPosition);
        yPosition += 8;
        pdf.text(`Active Users: ${activeUsers}`, margin, yPosition);
        yPosition += 8;
        pdf.text(`Suspended Users: ${suspendedUsers}`, margin, yPosition);
        yPosition += 8;
        
        const activePercentage = totalUsers > 0 ? ((activeUsers / totalUsers) * 100).toFixed(1) : 0;
        pdf.text(`Active User Percentage: ${activePercentage}%`, margin, yPosition);
        yPosition += 15;

        // User Accounts detailed table
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('User Accounts Breakdown', margin, yPosition);
        yPosition += 10;

        const tableHeaders = ['ID', 'Username', 'Email', 'Role', 'Status', 'Created'];
        const tableData = [tableHeaders];

        // Add data rows
        usersData.forEach(user => {
            const email = user.Email.length > 25 ? user.Email.substring(0, 25) + '...' : user.Email;
            const username = user.Username.length > 15 ? user.Username.substring(0, 15) + '...' : user.Username;
            const createdDate = new Date(user.CreatedAt).toLocaleDateString();
            
            tableData.push([
                user.UserID.toString(),
                username,
                email,
                user.Role,
                user.AccountStatus,
                createdDate
            ]);
        });

        // Draw table
        pdf.setFontSize(8);
        tableData.forEach((row, index) => {
            const isHeader = index === 0;
            if (isHeader) {
                pdf.setFont('helvetica', 'bold');
                pdf.setFillColor(240, 240, 240);
                pdf.rect(margin, yPosition - 5, pageWidth - (margin * 2), 8, 'F');
            } else {
                pdf.setFont('helvetica', 'normal');
            }

            // Column positions - adjusted for better spacing
            pdf.text(row[0], margin + 2, yPosition);           // ID
            pdf.text(row[1], margin + 15, yPosition);          // Username
            pdf.text(row[2], margin + 50, yPosition);          // Email
            pdf.text(row[3], margin + 90, yPosition);          // Role
            pdf.text(row[4], margin + 115, yPosition);         // Status
            pdf.text(row[5], margin + 145, yPosition);         // Created

            // Draw table lines
            pdf.line(margin, yPosition + 2, pageWidth - margin, yPosition + 2);

            yPosition += 8;

            // Check if we need a new page
            if (yPosition > 250) {
                pdf.addPage();
                yPosition = 30;
            }
        });

        yPosition += 15;

        // Role Distribution Section
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Role Distribution Analysis', margin, yPosition);
        yPosition += 10;

        // Count roles
        const roleCount = {};
        usersData.forEach(user => {
            roleCount[user.Role] = (roleCount[user.Role] || 0) + 1;
        });

        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'normal');
        Object.entries(roleCount).forEach(([role, count]) => {
            const percentage = totalUsers > 0 ? ((count / totalUsers) * 100).toFixed(1) : 0;
            pdf.text(`${role}: ${count} users (${percentage}%)`, margin, yPosition);
            yPosition += 8;
        });

        yPosition += 15;

        // Account Status Analysis
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Account Status Analysis', margin, yPosition);
        yPosition += 10;

        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Active Accounts: ${activeUsers} (${activePercentage}%)`, margin, yPosition);
        yPosition += 8;
        
        const suspendedPercentage = totalUsers > 0 ? ((suspendedUsers / totalUsers) * 100).toFixed(1) : 0;
        pdf.text(`Suspended Accounts: ${suspendedUsers} (${suspendedPercentage}%)`, margin, yPosition);
        yPosition += 15;

        // Check if we need a new page
        if (yPosition > 200) {
            pdf.addPage();
            yPosition = 30;
        }

        // Key Insights section
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Key Insights', margin, yPosition);
        yPosition += 10;

        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');

        // Recent registrations
        const sortedUsers = [...usersData].sort((a, b) => new Date(b.CreatedAt) - new Date(a.CreatedAt));
        const recentUsers = sortedUsers.slice(0, 3);

        if (recentUsers.length > 0) {
            pdf.text('• Most Recent Registrations:', margin, yPosition);
            yPosition += 5;
            recentUsers.forEach((user, index) => {
                const createdDate = new Date(user.CreatedAt).toLocaleDateString();
                pdf.text(`  ${index + 1}. ${user.Username} (${user.Role}) - ${createdDate}`, margin + 5, yPosition);
                yPosition += 5;
            });
            yPosition += 8;
        }

        // Account health
        pdf.text('• Account Health Overview:', margin, yPosition);
        yPosition += 5;
        
        if (activePercentage >= 90) {
            pdf.text('  Excellent: Over 90% of accounts are active', margin + 5, yPosition);
        } else if (activePercentage >= 75) {
            pdf.text('  Good: 75-90% of accounts are active', margin + 5, yPosition);
        } else if (activePercentage >= 50) {
            pdf.text('  Fair: 50-75% of accounts are active', margin + 5, yPosition);
        } else {
            pdf.text('  Attention needed: Less than 50% of accounts are active', margin + 5, yPosition);
        }
        yPosition += 8;

        // User base insights
        pdf.text('• User Base Insights:', margin, yPosition);
        yPosition += 5;
        pdf.text(`  Total registered users: ${totalUsers}`, margin + 5, yPosition);
        yPosition += 5;
        
        if (suspendedUsers > 0) {
            pdf.text(`  ${suspendedUsers} account(s) require attention (suspended)`, margin + 5, yPosition);
            yPosition += 5;
        }

        const adminCount = roleCount['Admin'] || 0;
        const userCount = roleCount['User'] || 0;
        pdf.text(`  Admin to User ratio: ${adminCount}:${userCount}`, margin + 5, yPosition);
        yPosition += 15;

        // Report Information section
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Report Information', margin, yPosition);
        yPosition += 8;

        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(10);
        pdf.text(`Report Type: User Management Analysis`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Total Users Analyzed: ${totalUsers}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Active Users: ${activeUsers}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Suspended Users: ${suspendedUsers}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Data Source: TrafAnalyz User Database`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Report Generated: ${generatedDate}`, margin, yPosition);
        yPosition += 5;
        pdf.text(`Generated by: ${username}`, margin, yPosition);

        // Save PDF with descriptive filename
        const filename = `TrafAnalyz_User_Management_Report_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}.pdf`;
        pdf.save(filename);

        // Log the PDF export into the database (keeping existing logging functionality)
        fetch('export_users_pdf.php?export=1', {
            method: 'GET'
        }).then(response => {
            console.log('Export logged successfully');
        }).catch(error => {
            console.error('Error logging export:', error);
        });
    }
    </script>
</html>