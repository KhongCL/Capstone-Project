<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: admin_logout.php
// Description: Admin logout functionality that destroys admin sessions and redirects
//              to the admin login page for secure session termination.
// First Written On: 20 April 2025
// Edited On: 14 July 2025

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../admin_login.php?key=trafanalyz");
exit;
?>