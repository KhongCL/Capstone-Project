<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: logout.php
// Description: User logout functionality that destroys active sessions and redirects
//              users to the login page for secure session termination.
// First Written On: 17 April 2025
// Edited On: 14 July 2025

session_start();
$_SESSION = array();
session_destroy();
header("Location: login.php");

exit;
?>