<?php
/**
 * Root Router Redirection
 */
require_once __DIR__ . '/includes/auth.php';

if (is_employee_logged_in()) {
    header("Location: /employee/dashboard.php");
} else {
    header("Location: /employee/login.php");
}
exit;
