<?php
/**
 * Authentication Check Script
 *
 * This script verifies that a user is logged in. If not, it redirects them
 * to the login page. It should be included at the top of any page
 * that requires user authentication.
 */

// Include the core functions which also starts the session.
require_once 'functions.php';

// Check if the user is logged in.
if (!is_logged_in()) {
    // If not, destroy any potential partial session data.
    session_destroy();
    // Redirect to the login page and stop script execution.
    redirect('login.php');
}

// Optional: Also check if the user is a normal user, not an admin,
// if this check is for user-specific pages.
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Admins should be on their own dashboard, not the user one.
    redirect('admin/index.php');
}
?>
