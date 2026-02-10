<?php
require '../../db.php';

$code = $_GET['code'] ?? '';

$stmt = $conn->prepare(
    "UPDATE users
     SET is_verified = 1, verification_code = NULL
     WHERE verification_code = ? AND is_verified = 0"
);
$stmt->bind_param('s', $code);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    redirectWithFlash(
        'Your account has been verified. You may now log in.',
        'success',
        '/bookstore/courses/admin/auth.php',
        5
    );
} else {
    redirectWithFlash(
        'Invalid or expired verification link.',
        'danger',
        '/bookstore/courses/admin/auth.php',
        5
    );
}
