<?php
require '../../db.php';

// Remove only auth-related session data
unset($_SESSION['user_id']);

// Optional: regenerate session ID for security
session_regenerate_id(true);

redirectWithFlash(
    'You have been logged out.',
    'info',
    '/bookstore/courses/admin/auth.php',
    5
);
?>