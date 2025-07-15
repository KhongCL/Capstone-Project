<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: security.php
// Description: Security utility functions providing input sanitization, output encoding,
//              and validation for usernames, emails, and passwords with XSS protection.
// First Written On: 20 April 2025
// Edited On: 19 June 2025

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}
?>