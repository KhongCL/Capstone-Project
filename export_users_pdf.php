<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in as admin
session_start();
// Uncomment in production to enforce authentication
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
//     header('Location: login.php');
//     exit;
// }

// Include TCPDF library
// You need to download TCPDF from https://github.com/tecnickcom/TCPDF/releases
// and extract to a directory named 'tcpdf' in your project
require_once('tcpdf/tcpdf.php');

// Get users from the database
$users = [];
$sql = "SELECT UserID, Username, Email, Role, AccountStatus, CreatedAt FROM user ORDER BY UserID";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Create new PDF document
class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        // Logo
        $image_file = 'images/loginbg.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Set font
        $this->SetFont('helvetica', 'B', 18);
        
        // Title
        $this->Cell(0, 15, 'TrafAnalyz User Management Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Date and time
        $this->SetFont('helvetica', '', 10);
        $this->SetY(20);
        $this->SetX(15);
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Export information
        $exportedBy = isset($_SESSION['username']) ? $_SESSION['username'] : 'Administrator';
        $this->SetY(-10);
        $this->Cell(0, 10, 'Exported by: ' . $exportedBy, 0, false, 'L', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('TrafAnalyz');
$pdf->SetAuthor('TrafAnalyz Admin');
$pdf->SetTitle('User Management Report');
$pdf->SetSubject('User Accounts List');
$pdf->SetKeywords('TrafAnalyz, User, Management, Report');

// Set default header data
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set margins
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(1.25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 14);

// Title
$pdf->Cell(0, 10, 'User Accounts Summary', 0, 1, 'L');

// Add summary statistics
$pdf->SetFont('helvetica', '', 11);

// Calculate statistics
$totalUsers = count($users);
$activeUsers = 0;
$suspendedUsers = 0;
$adminUsers = 0;
$endUsers = 0;

foreach ($users as $user) {
    if ($user['AccountStatus'] === 'Active') {
        $activeUsers++;
    } else {
        $suspendedUsers++;
    }
    
    if ($user['Role'] === 'Admin') {
        $adminUsers++;
    } else {
        $endUsers++;
    }
}

// Display statistics
$pdf->Cell(0, 8, 'Total users: ' . $totalUsers, 0, 1, 'L');
$pdf->Cell(0, 8, 'Active users: ' . $activeUsers, 0, 1, 'L');
$pdf->Cell(0, 8, 'Suspended users: ' . $suspendedUsers, 0, 1, 'L');
$pdf->Cell(0, 8, 'Admin users: ' . $adminUsers, 0, 1, 'L');
$pdf->Cell(0, 8, 'End users: ' . $endUsers, 0, 1, 'L');

// Add users table
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'User Accounts', 0, 1, 'L');

// Table header
$pdf->SetFillColor(232, 232, 232);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(15, 8, 'ID', 1, 0, 'C', 1);
$pdf->Cell(40, 8, 'Username', 1, 0, 'C', 1);
$pdf->Cell(60, 8, 'Email', 1, 0, 'C', 1);
$pdf->Cell(25, 8, 'Role', 1, 0, 'C', 1);
$pdf->Cell(25, 8, 'Status', 1, 0, 'C', 1);
$pdf->Cell(25, 8, 'Created', 1, 1, 'C', 1);

// Table data
$pdf->SetFont('helvetica', '', 9);

foreach ($users as $user) {
    // Set status color
    if ($user['AccountStatus'] === 'Active') {
        $pdf->SetFillColor(213, 239, 218); // Light green for active
    } else {
        $pdf->SetFillColor(248, 215, 218); // Light red for suspended
    }
    
    // Add row data
    $pdf->Cell(15, 7, $user['UserID'], 1, 0, 'C');
    $pdf->Cell(40, 7, $user['Username'], 1, 0, 'L');
    $pdf->Cell(60, 7, $user['Email'], 1, 0, 'L');
    $pdf->Cell(25, 7, $user['Role'], 1, 0, 'C');
    $pdf->Cell(25, 7, $user['AccountStatus'], 1, 0, 'C', 1); // Fill only the status cell
    $pdf->Cell(25, 7, date('Y-m-d', strtotime($user['CreatedAt'])), 1, 1, 'C');
}

// Add report footer with additional information
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 9);

// Add report timestamp and confidentiality notice
$pdf->MultiCell(0, 5, 'This report was generated automatically by the TrafAnalyz system on ' . date('Y-m-d') . ' at ' . date('H:i:s') . '.' . 
                     "\nThis document contains confidential information and is for authorized personnel only.", 0, 'L', 0, 1);

// Add record of export to database if table exists
try {
    // Check if the export_history table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'export_history'");
    if ($tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO export_history (UserID, ExportType, ExportedDataDescription) VALUES (?, ?, ?)");
        $userId = $_SESSION['user_id'] ?? 1; // Default to admin if not set
        $exportType = 'PDF-User-List';
        $description = 'User management report - ' . count($users) . ' users';
        $stmt->bind_param("iss", $userId, $exportType, $description);
        $stmt->execute();
    }
} catch (Exception $e) {
    // Just log the error, don't stop PDF generation
    error_log("Error logging export: " . $e->getMessage());
}

// Output the PDF
$pdf->Output('user_management_report_' . date('Y-m-d') . '.pdf', 'I');
?>